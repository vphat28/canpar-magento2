<?php
namespace CollinsHarper\Canpar\Model;

use Magento\Framework\Model\AbstractModel;

class Rate extends AbstractModel
{
    
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \CollinsHarper\Canpar\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \CollinsHarper\MeasureUnit\Helper\Data
     */
    protected $chUnitHelper;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productLoader;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory
     */
    protected $rateErrorFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;
    /**
     * Store service type as an array
     *
     * @var array
     */
    protected $type;
    /**
     * Store service constraints as an array
     *
     * @var array
     */
    protected $constraints;
    /**
     * @var \CollinsHarper\Canpar\Model\ManifestFactory $manifestFactory
     */
    protected $manifestFactory;
    
    /**
     * Rate constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface            $scopeConfig
     * @param \Magento\Checkout\Model\Session                               $checkoutSession
     * @param \CollinsHarper\Canpar\Helper\Data                             $dataHelper,
     * @param \CollinsHarper\MeasureUnit\Helper\Data                        $chUnitHelper,
     * @param \Magento\Catalog\Model\ProductFactory                         $productLoader,
     * @param \Magento\Checkout\Model\Cart                                  $cart,
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory    $rateErrorFactory,
     * @param \Magento\Framework\Message\ManagerInterface                   $messageManager
     * @param \Magento\Directory\Model\RegionFactory                        $regionFactory
     * @param \CollinsHarper\Canpar\Model\ManifestFactory                   $manifestFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \CollinsHarper\Canpar\Helper\Data $dataHelper,
        \CollinsHarper\MeasureUnit\Helper\Data $chUnitHelper,
        \Magento\Catalog\Model\ProductFactory $productLoader,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \CollinsHarper\Canpar\Model\ManifestFactory $manifestFactory
    ) {
        
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->cart        = $cart;
        $this->dataHelper  = $dataHelper;
        $this->chUnitHelper  = $chUnitHelper;
        $this->productLoader    = $productLoader;
        $this->type             = $this->dataHelper->getServiceType();
        $this->messageManager   = $messageManager;
        $this->rateErrorFactory = $rateErrorFactory;
        $this->regionFactory    = $regionFactory;
        $this->manifestFactory      = $manifestFactory;
        $this->constraints = $this->dataHelper->getServiceConstraints();
    }
    /**
     * Rate Request to get available services
     *
     * @param RateRequest $request
     * @return array|false
     */
    public function createRateRequest($request)
    {
        $rateRequest = [];
        $rateRequest["delivery_country"]     = $request->getDestCountryId();
        $rateRequest["delivery_postal_code"] = str_replace(' ', '', $request->getDestPostcode());
        $rateRequest["pickup_postal_code"]   = str_replace(' ', '', $request->getPostcode());
        $currentDate = $this->dataHelper->getCurrentDateTime();
        $rateRequest["shipping_date"]       = $currentDate;
        $rateRequest["shipper_num"] = $this->scopeConfig->getValue('carriers/canpar/shippingaccount');
        $rateRequest["user_id"]     = $this->scopeConfig->getValue('carriers/canpar/email');
        $rateRequest["password"]    = $this->scopeConfig->getValue('carriers/canpar/password');
       
        try {
            $client = $this->dataHelper->getClient('rating');
            $response = $client->getAvailableServices(["request" => $rateRequest]);
            
            if ($response->return->error != "") {
                $message = sprintf(__("Error in create Rate Request %s"), $response->return->error);
                $this->dataHelper->log($message);
                $this->dataHelper->log(var_export($response, true));
                $this->dataHelper->log(var_export($rateRequest, true));
                return false;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataHelper->log($e->getMessage());
        }
        
        return $response;
    }
    /**
     * Get rates for available services
     *
     * @param array $request
     * @param array $type
     * @param int   $weight
     * @return array|false
     */
    public function getRate($request, $type, $weight)
    {
        $options = unserialize($this->checkoutSession->getData('canpar_options'));
        $nsr = 0;
        $res = 0;
        $xc = 0;

        $selectedRate = isset($options['selectedrate']) && $options['selectedrate'] == (string)$type->type ? $type->type : false;
        if ($selectedRate) {
            if (isset($options["nsr"]) && $options["nsr"] == true) {
                $nsr = 2;
            }
            if (isset($options["residential"]) && $options["residential"] == true) {
                $res = true;
            }
            if (isset($options["xc"]) && $options["xc"] == true) {
                $xc = true;
            }
        }
        $rateRequest = [];
        $rateRequest["apply_association_discount"] = $this->scopeConfig->getValue('carriers/canpar/association_disc');
        $rateRequest["apply_individual_discount"]  = $this->scopeConfig->getValue('carriers/canpar/individual_disc');
        $rateRequest["apply_invoice_discount"]     = $this->scopeConfig->getValue('carriers/canpar/invoice_disc');
        $rateRequest["password"] = $this->scopeConfig->getValue('carriers/canpar/password');
        $rateRequest["user_id"]  = $this->scopeConfig->getValue('carriers/canpar/email');
        $currentDate = $this->dataHelper->getCurrentDateTime();

        $origin = $this->scopeConfig->getValue('shipping/origin', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $region = $this->regionFactory->create()->load($origin["region_id"]);

        //Shipment related information
        $rateRequest["shipment"] = [
            "cod_type" => "N",
            "collect" => true,
//            "description" => "Description",
            "dg" => $this->scopeConfig->getValue('carriers/canpar/dg'),
            "handling" => $this->scopeConfig->getValue('carriers/canpar/handling'),
            "handling_type" => $this->scopeConfig->getValue('carriers/canpar/handling_type'),
            "instruction" => $this->scopeConfig->getValue('carriers/canpar/instruction'),
            "nsr" => $nsr,//$this->_helper->getConfig('nsr'),
            "premium" => "N",
            "reported_weight_unit" => $this->scopeConfig->getValue('carriers/canpar/default_weight_unit'),
            "send_email_to_delivery" => true,
            "send_email_to_pickup" => true,
            "service_type" => $type->type,
            "shipper_num" => $this->scopeConfig->getValue('carriers/canpar/shippingaccount'),
            "shipping_date" => $currentDate,
            'dimention_unit' => $this->scopeConfig->getValue('carriers/canpar/default_dimentional_unit')
        ];

        //Shopping cart page , Estimates hack - checkout/cart/. The default string can be any random string
        $addressLine1 = "address line one";
        if (strlen($request->getDestStreet()) > 40) {
            $addressLine1 = substr($request->getDestStreet(), 0, 40);
        }
        
        $rateRequest["shipment"]["delivery_address"] = [
            "address_line_1" => $addressLine1,
            "attention" => substr($this->cart->getQuote()->getData('customer_firstname') ? $this->cart->getQuote()->getData('customer_firstname') : 'John Doe', 0, 40),
            "city" => $request->getDestCity(),
            "country" => $request->getDestCountryId(),
            "email" => substr($this->cart->getQuote()->getData('customer_email'), 0, 256),
            "name" => substr($this->cart->getQuote()->getData('customer_firstname') ? ($this->cart->getQuote()->getData('customer_firstname') . " " . $this->cart->getQuote()->getData('customer_lastname')) : 'John Doe', 0, 40),
            "postal_code" => str_replace(' ', '', $request->getDestPostcode()),
            "province" => $request->getDestRegionCode(),
            "residential" => $res,//false,
        ];
        //Pick up address
        $rateRequest["shipment"]["pickup_address"] = [
            "address_line_1" => $origin["street_line1"],
            "address_line_2" => $origin["street_line2"],
            "city" => substr($origin["city"], 0, 40),
            "country" => $origin["country_id"],
            "email" => substr($this->scopeConfig->getValue('trans_email/ident_general/email'), 0, 40),
            "name" => substr($this->scopeConfig->getValue('trans_email/ident_general/name'), 0, 40),
            "postal_code" => str_replace(' ', '', $origin["postcode"]),
            "province" => $region->getCode(),
            "residential" => $this->scopeConfig->getValue('carriers/canpar/pickup_is_residential'),
        ];
        
        $rateRequest["shipment"]["packages"] = $this->createPackages($request, $type->type);
        
        $req = [];
        $req["request"] = $rateRequest;
        try {
            $client = $this->dataHelper->getClient('rating');
            $response = $client->rateShipment($req);
            
            if ($response->return->error != "") {
                $message = sprintf(__("Error in rateShipment %s"), $response->return->error);
                $this->dataHelper->log($message);
                $this->dataHelper->log(var_export($response, true));
                return false;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataHelper->log($e->getMessage());
        }
        
        return $response;
    }
    /**
     * Get packages for available services
     *
     * @param array  $request
     * @param string $shipmentType
     * @return array
     */
    public function createPackages($request, $shipmentType)
    {
        $defaultWeightUnit = $this->scopeConfig->getValue('catalog/measure_units/weight');
        $defaultDimUnit    = $this->scopeConfig->getValue('catalog/measure_units/length');

        //Apply constraints. Reset the max_box_weight to the constraints if it is over the constraint. Change all unit to lb (consist with the _constraint array)
        $maxBoxWeight = $this->scopeConfig->getValue('carriers/canpar/max_box_weight');
        $maxBoxWeight = $this->chUnitHelper->getConvertedWeight($maxBoxWeight, strtolower($this->scopeConfig->getValue('carriers/canpar/default_weight_unit')), 'lb');
        if ($maxBoxWeight > $this->constraints[$shipmentType]['max']) {
            $maxBoxWeight = $this->constraints[$shipmentType]['max'];
        } elseif ($maxBoxWeight < $this->constraints[$shipmentType]["min"]) {
            $maxBoxWeight = $this->constraints[$shipmentType]["max"];
        } elseif ($maxBoxWeight == 0) {
            $maxBoxWeight = $this->constraints[$shipmentType]["max"];
        }
        
        //Make sure there is no item in the order heavier than the max box weight
        $maxItemWeight = 0;
        foreach ($request->getAllItems() as $item) {
            $product = $this->productLoader->create()->load($item->getProductId());
            // make sure each item has the weight in lb
            $pWeight = $this->chUnitHelper->getConvertedWeight($product->getWeight(), $this->scopeConfig->getValue('catalog/measure_units/weight'), "lb");
            if ($pWeight > $maxItemWeight) {
                $maxItemWeight = $pWeight;
            }
        }
        $weightInBox = 0;
        $numBoxes = 1;
        
        foreach ($request->getAllItems() as $item) {
            // Skip the configurable item itself. Only take its associate product.
            if ($item->getProductType() == "configurable") {
                continue;
            }
            $product = $this->productLoader->create()->load($item->getProductId());
            $pWeight = $this->chUnitHelper->getConvertedWeight($product->getWeight(), $this->scopeConfig->getValue('catalog/measure_units/weight'), "lb");
            $itemCount = $item->getQty();

            for ($idx = 0; $idx < $itemCount; $idx++) {
                $weightInBox += $pWeight;
                if ($weightInBox > $maxBoxWeight) {
                    $numBoxes++;
                    $weightInBox = 0;
                }
            }
        }
        // Calculate number of boxes needed
        $height = $this->scopeConfig->getValue('carriers/canpar/height');
        $width  = $this->scopeConfig->getValue('carriers/canpar/width');
        $length = $this->scopeConfig->getValue('carriers/canpar/length');
        
        //Create a package array for each box
        for ($inx = 0; $inx < $numBoxes; $inx++) {
            $package{$inx} = [
                "declared_value" => 0,
                "height" => $this->chUnitHelper->getConvertedLength($height, $this->scopeConfig->getValue('carriers/canpar/default_dimentional_unit'), $defaultDimUnit),
                "length" => $this->chUnitHelper->getConvertedLength($length, $this->scopeConfig->getValue('carriers/canpar/default_dimentional_unit'), $defaultDimUnit),
                "width"  => $this->chUnitHelper->getConvertedLength($width, $this->scopeConfig->getValue('carriers/canpar/default_dimentional_unit'), $defaultDimUnit),
                "reported_weight" => 0,
                "store_num" => \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                //"xc" => $xc,//$this->_helper->getConfig('xc'),
            ];
        }
        
        // Start putting the products in the boxes
        $weightInBox = 0;
        $boxIndex = 0;
        $declaredValueForConfigurable = 0;
        foreach ($request->getAllItems() as $item) {
            $declaredValue = $item->getPrice();
    
            // don't ship configurable item itself. Ship only the simple item instead.
            if ($item->getProductType() == "configurable") {
                // save the declared value for the next item (its associate product);
                $declaredValueForConfigurable = $item->getPrice();
                continue;
            } elseif ($declaredValueForConfigurable != 0) {
                // if it is associate product ($declaredValueForConfigurable != 0), retrieve the declared value from $declaredValueForConfigurable and reset $declaredValueForConfigurable to 0
                $declaredValue = $declaredValueForConfigurable;
                $declaredValueForConfigurable = 0;
            }

            $product = $this->productLoader->create()->load($item->getProductId());
            $pWeight = $this->chUnitHelper->getConvertedWeight($product->getWeight(), $this->scopeConfig->getValue('catalog/measure_units/weight'), "lb");
            
            $itemCount = 0;
            if (method_exists($item, "getQty")) {
                $itemCount = $item->getQty();
            } else {
                $itemCount = $item->getQtyOrdered();
            }
            //Start putting items in boxes
            for ($idx = 0; $idx < $itemCount; $idx++) {
                $weightInBox += $pWeight;
                if ($weightInBox > $maxBoxWeight) {
                    $boxIndex++;
                    $weightInBox = $pWeight;
                }
                
                if (isset($package[$boxIndex])) {
                    $package{$boxIndex}['reported_weight'] += $pWeight;
                    $package{$boxIndex}['declared_value'] += $declaredValue;
                }
            }
        }
        // initialize the array "$package"
        $packages = [];
        for ($inx = 0; $inx < $numBoxes; $inx++) {
            array_push($packages, $package{$inx});
        }
        return $packages;
    }
    /**
     * Get Carrier Tracking Number
     *
     * @param array  $request
     * @return array
     */
    public function createShipmentRequest($request)
    {
        $order = $request->getOrderShipment()->getOrder();
        $shipping_method = explode('_', $order->getShippingMethod());
        
        $ops = unserialize($order->getCanparShippingOptions());
        $nsr = 0;
        $res = 0;
        $xc = 0;

        if ($ops != false && is_array($ops)) {
            if (isset($ops["nsr"]) && $ops["nsr"] == true) {
                $nsr = 2;
            }
            if (isset($ops["residential"]) && $ops["residential"] == true) {
                $res = true;
            }
            if (isset($ops["xc"]) && $ops["xc"] == true) {
                $xc = true;
            }
        }
        
        $origin = $this->scopeConfig->getValue('shipping/origin', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $region = $this->regionFactory->create()->load($origin["region_id"]);
        
        $weight = $this->dataHelper->getPackageWeightLb($order);
        //Minimum weight will always be 1lb if it falls below.
        if ($weight < 1) {
            $weight = 1;
        }
        
        $requestClient = [];
        $requestClient["password"] =  $this->scopeConfig->getValue('carriers/canpar/password');
        $requestClient["user_id"] =  $this->scopeConfig->getValue('carriers/canpar/email');
        $currentDate = $this->dataHelper->getCurrentDateTime();

        $requestClient["shipment"] = [
            "cod_type" => "N",
            "collect" => true,
            "dg" => $this->scopeConfig->getValue('carriers/canpar/dg'),
            "handling" => $this->scopeConfig->getValue('carriers/canpar/handling'),
            "handling_type" => $this->scopeConfig->getValue('carriers/canpar/handling_type'),
            "instruction" => $this->scopeConfig->getValue('carriers/canpar/instruction'),
            "nsr" => $nsr,
            "premium" => "N",
            "reported_weight_unit" => $this->scopeConfig->getValue('carriers/canpar/default_weight_unit'),
            "send_email_to_delivery" => true,
            "send_email_to_pickup" => true,
            "service_type" => $shipping_method[1],
            "shipper_num" => $this->scopeConfig->getValue('carriers/canpar/shippingaccount'),
            "shipping_date" => $currentDate,
            'dimention_unit' => $this->scopeConfig->getValue('carriers/canpar/default_dimentional_unit')
        ];

        $_shippingAddress = $order->getShippingAddress();
        $address_line_1 = "address line one";
        $address_line_1 = $_shippingAddress->getStreetLine(1);

        $deliveryRegionModel = $this->regionFactory->create();
        $deliveryRegionCode = $deliveryRegionModel->loadByName($_shippingAddress->getRegion(), $_shippingAddress->getCountryId())->getCode();

        $requestClient["shipment"]["delivery_address"] = [
            "address_line_1" => $address_line_1,
            "attention" => $_shippingAddress->getFirstname(),
            "city" => $_shippingAddress->getCity(),
            "country" => $_shippingAddress->getCountryId(),
            "email" => $_shippingAddress->getEmail(),
            "name" => $_shippingAddress->getFirstname() . " " . $_shippingAddress->getLastname(),
            "postal_code" => str_replace(' ', '', $_shippingAddress->getPostcode()),
            "province" => $deliveryRegionCode,
            "residential" => $res,
        ];
        
        $requestClient["shipment"]["pickup_address"] = [
            "address_line_1" => $origin["street_line1"],
            "address_line_2" => $origin["street_line2"],
            "city" => substr($origin["city"], 0, 40),
            "country" => $origin["country_id"],
            "email" => substr($this->scopeConfig->getValue('trans_email/ident_general/email'), 0, 40),
            "name" => substr($this->scopeConfig->getValue('trans_email/ident_general/name'), 0, 40),
            "postal_code" => str_replace(' ', '', $origin["postcode"]),
            "province" => $region->getCode(),
            "residential" => $this->scopeConfig->getValue('carriers/canpar/pickup_is_residential'),
        ];
        
        $packages = $request->getPackages();
        $inx = 0;
        foreach ($packages as &$piece) {
            $params = $piece['params'];
            $package{$inx} = [
                "declared_value" => 0,
                "height" => $params['height'],
                "length" => $params['length'],
                "width"  => $params['width'],
                "reported_weight" => $params['weight'],
                "store_num" => \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ];
            $inx++;
        }
        $requestClient["shipment"]["packages"] = $package;
  
        $req = [];
        $req["request"] = $requestClient;
        try {
            $client = $this->dataHelper->getClient('business');
            $response = $client->processShipment($req);

            if ($response->return->error != "") {
                $this->dataHelper->log("Error occured when calling createShipmentRequest method.");
                $this->dataHelper->log(var_export($response, true));
                $this->dataHelper->log(var_export($requestClient, true));
                return false;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataHelper->log($e->getMessage());
        }
        
        return $response;
    }
    /**
     * Get Carrier Labels
     *
     * @param int $shipmentId
     * @return array
     */
    public function getLabel($shipmentId)
    {
        $request["id"] = $shipmentId;
        $request["password"] =  $this->scopeConfig->getValue('carriers/canpar/password');
        $request["user_id"] =  $this->scopeConfig->getValue('carriers/canpar/email');
        $request["thermal"] =  false;
        
        $req = [];
        $req["request"] = $request;
        try {
            $client = $this->dataHelper->getClient('business');
            $response = $client->getLabels($req);
    
            if ($response->return->error != "") {
                $this->dataHelper->log("Error occured when calling getLabels method.");
                $this->dataHelper->log(var_export($response, true));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataHelper->log($e->getMessage());
        }
        
        return $response;
    }
    /**
     * Convert PNG to PDF
     *
     * @param string $shippingLabelContent
     * @param int $shipmentId
     */
    public function getPdfContent($labels, $shipmentId)
    {
        $pdf = new \Zend_Pdf();
        try {
            // http://office.collinsharper.com/label.png
            $file =  sys_get_temp_dir() . '/' . $shipmentId . rand() . '.png';
            foreach ($labels as $label) {
                $content = base64_decode($label);
                if ($content == false) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('File content empty! Please try again.'));
                }
                file_put_contents($file, $content);
                $image = \Zend_Pdf_Image::imageWithPath($file);
                unlink($file);
        
                $page = $pdf->newPage(\Zend_Pdf_Page::SIZE_LETTER);
                $x1 = 6;
                $y1 = 6;
    
                $imageWidth = $image->getPixelWidth();
                $imageHeight = $image->getPixelHeight();
    
                // scale image to page width
                $pageWidth = $page->getWidth();
                $pageHeight = $page->getHeight();
                $scale = ($pageHeight-$y1) / $imageHeight;
    
                $y2 = $pageHeight;
                $x2 = $x1 + ($imageWidth * $scale);
    
                $scale = $pageWidth / $imageWidth;
                $x2 = $pageWidth;
                $y2 = $y1 + ($imageHeight * $scale);
    
                $page->drawImage($image, $x1, $y1, $x2, $y2);
                $pdf->pages[] = $page;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataHelper->log($e->getMessage());
        }
        return $pdf->render();
    }
    /**
     * Create Manifests
     *
     * @return array
     */
    public function endOfDay()
    {
        $request = [];
        $request["date"] = $this->dataHelper->getCurrentDateTime();
        $request["password"] =  $this->scopeConfig->getValue('carriers/canpar/password');
        $request["user_id"]  =  $this->scopeConfig->getValue('carriers/canpar/email');
        $request["shipper_num"] = $this->scopeConfig->getValue('carriers/canpar/shippingaccount');

        $req = [];
        $req["request"] = $request;
        try {
            $client = $this->dataHelper->getClient('business');
            $response = $client->endOfDay($req);
            
            if ($response->return->error != "") {
                $this->dataHelper->log("Error occured when calling endOfDay method.");
                $this->dataHelper->log(var_export($response, true));
                return false;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataHelper->log($e->getMessage());
        }
        return $response;
    }
    /**
     * Get Manifests
     *
     * @return string
     */
    public function getManifestPdfById($mId, $type = 'F')
    {
        /*
        * S = SUMMARY
          D = DETAIL
          F = FULL DETAIL
        */
        $manifest = $this->manifestFactory->create()->load($mId);
        $man_types = ['S', 'D', 'F'];
        if (!isset($man_types[$type])) {
            $type = 'F';
        }

        $request = [];
        $request["manifest_num"] = $manifest->getCanparManifestNum();
        $request["type"] = $type;
        $request["password"] =  $this->scopeConfig->getValue('carriers/canpar/password');
        $request["user_id"]  =  $this->scopeConfig->getValue('carriers/canpar/email');
        $request["shipper_num"] = $this->scopeConfig->getValue('carriers/canpar/shippingaccount');
        
        $req = [];
        $req["request"] = $request;
        try {
            $client = $this->dataHelper->getClient('business');
            $response = $client->getManifest($req);
    
            if ($response->return->error != "" || !$response->return->manifest) {
                $this->dataHelper->log("Error in Gettinng manifest");
                $this->dataHelper->log(var_export($response, true));
                return false;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataHelper->log($e->getMessage());
        }
        
        return base64_decode($response->return->manifest);
    }	
}
