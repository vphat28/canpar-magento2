<?php
namespace CollinsHarper\Canpar\Model\Carrier;

use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Framework\Xml\Security;

class ShippingMethod extends AbstractCarrierOnline implements CarrierInterface
{
    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = 'canpar';
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $rateMethodFactory;
    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $rateResultFactory;
    /**
     * @var \CollinsHarper\Canpar\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var \CollinsHarper\Canpar\Model\Rate
     */
    protected $rateModel;
    /**
     * @var \CollinsHarper\Canpar\Model\Track
     */
    protected $trackModel;
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
     * Shipping constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \CollinsHarper\Canpar\Helper\Data $dataHelper
     * @param \CollinsHarper\Canpar\Model\Rate $rateModel
     * @param \CollinsHarper\Canpar\Model\Track $trackModel
     * @param array                            $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \CollinsHarper\Canpar\Helper\Data $dataHelper,
        \CollinsHarper\Canpar\Model\Rate $rateModel,
        \CollinsHarper\Canpar\Model\Track $trackModel,
        array $data = []
    ) {
        $this->rateResultFactory  = $rateResultFactory;
        $this->rateMethodFactory  = $rateMethodFactory;
        $this->dataHelper         = $dataHelper;
        $this->scopeConfig        = $scopeConfig;
        $this->rateModel          = $rateModel;
        $this->trackModel         = $trackModel;
        $this->type               = $this->dataHelper->getServiceType();
        $this->constraints        = $this->dataHelper->getServiceConstraints();

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateResultFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }
    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return Result|bool|null
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->scopeConfig->getValue('carriers/canpar/active')) {
            return false;
        }
        $result = $this->rateResultFactory->create();
        $response = $this->rateModel->createRateRequest($request);
        if (!$response) {
            if ($this->scopeConfig->getValue('carriers/canpar/failover_rate') > 0) {
                $method = $this->rateMethodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->scopeConfig->getValue('carriers/canpar/title'));
                $method->setMethod('Regular');
                $method->setMethodTitle($this->scopeConfig->getValue('carriers/canpar/failover_ratetitle'));
                $amount = $this->scopeConfig->getValue('carriers/canpar/failover_rate');
                $method->setPrice($amount);
                $method->setCost($amount);
                $result->append($method);
            } else {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->scopeConfig->getValue('carriers/canpar/title'));
                $result->append($error);
            }
            return $result;
        }
        
        $allowedMethods = $this->getAllowedMethods();
        $availableResult = [];
        if (isset($response->return->getAvailableServicesResult)) {
            $availableResult = $response->return->getAvailableServicesResult;
        }
            
        if (isset($response->return->getAvailableServicesResult->type)) {
            $availableResult = [];
            $availableResult[] = $response->return->getAvailableServicesResult;
        }

        $hasAvailableMethods = false;
        foreach ($availableResult as $type) {
            if (!isset($allowedMethods[$type->type])) {
                continue;
            }
            $weight = $this->dataHelper->getPackageWeightLb($request);
            //Minimum weight will always be 1lb if it falls below.
            if ($weight < 1) {
                $weight = 1;
            }
            
            if ($weight < $this->constraints[$type->type]["min"] || $weight > $this->constraints[$type->type]["max"]) {
                continue;
            }

            $rateObject = $this->rateModel->getRate($request, $type, $weight);
            if (!is_object($rateObject)) {
                continue;
            }
            
            $price = $rateObject->return->processShipmentResult->shipment->freight_charge +
                $rateObject->return->processShipmentResult->shipment->fuel_surcharge;

            $price += $rateObject->return->processShipmentResult->shipment->collect_charge;
            $price += $rateObject->return->processShipmentResult->shipment->tax_charge_1;
            $price += $rateObject->return->processShipmentResult->shipment->tax_charge_2;
            $price += $rateObject->return->processShipmentResult->shipment->dg_charge;
            $price += $rateObject->return->processShipmentResult->shipment->ea_charge;
            $price += $rateObject->return->processShipmentResult->shipment->xc_charge;
            $price += $rateObject->return->processShipmentResult->shipment->ra_charge;

            $cost = $price;

            if ($this->scopeConfig->getValue('carriers/canpar/handling_type') == "fixed" && $this->scopeConfig->getValue('carriers/canpar/handling')) {
                $price +=  $this->scopeConfig->getValue('carriers/canpar/handling');
            } elseif ($this->scopeConfig->getValue('carriers/canpar/handling')) {
                $price +=  ($this->scopeConfig->getValue('carriers/canpar/handling') * $price);
            }

            if ($request->getFreeShipping() == true || $request->getPackageQty() == $this->getFreeBoxes()) {
                $price = 0.00;
            }

            $date = (string)$rateObject->return->processShipmentResult->shipment->estimated_delivery_date;
            $deliveryTitle = "%s";

            if ($date) {
                $date = date('Y-m-d', strtotime($date));
                if ($this->scopeConfig->getValue('carriers/canpar/lead_time_days')) {
                    $days = (int)$this->scopeConfig->getValue('carriers/canpar/lead_time_days');
                    $modifiedDeliveryDate = strtotime("+{$days} days", strtotime($date));
                    if (date('N', $modifiedDeliveryDate) == 7) {
                        $modifiedDeliveryDate = strtotime("+1 days", strtotime($date));
                    }
                    $date = date('Y-m-d', $modifiedDeliveryDate);
                }
                $deliveryTitle = "%s Estimated Delivery Date %s";
            }

            $rTitle = isset($this->type[$rateObject->return->processShipmentResult->shipment->service_type]) ? $this->type[$rateObject->return->processShipmentResult->shipment->service_type] : 'Standard';
            $methodTite = sprintf(__($deliveryTitle), $rTitle, $date);

            $method = $this->rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->scopeConfig->getValue('carriers/canpar/title'));
            $method->setMethod($type->type);
            $method->setCost($cost);
            $method->setPrice($price);
            $method->setMethodTitle($methodTite);
            $result->append($method);

            $hasAvailableMethods = true;
        }

        if (!$hasAvailableMethods) {
            if ($this->scopeConfig->getValue('carriers/canpar/failover_rate') > 0) {
                $method = $this->rateMethodFactory->create();
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->scopeConfig->getValue('carriers/canpar/title'));
                $method->setMethod('Regular');
                $method->setMethodTitle($this->scopeConfig->getValue('carriers/canpar/failover_ratetitle'));
                $amount = $this->scopeConfig->getValue('carriers/canpar/failover_rate');
                $method->setPrice($amount);
                $method->setCost($amount);
                $result->append($method);
            } else {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->scopeConfig->getValue('carriers/canpar/title'));
                $result->append($error);
            }
        }
        return $result;
    }
    /**
     * Processing additional validation to check if carrier applicable.
     *
     * @param \Magento\Framework\DataObject $request
     * @return $this|bool|\Magento\Framework\DataObject
     */
    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return true;
    }
    /**
     * Get configuration data of available services
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(',', $this->scopeConfig->getValue('carriers/canpar/allowed_methods'));
        $arr = [];
        foreach ($allowed as $k) {
            $arr[$k] = $this->getCode('method', $k);
        }
        return $arr;
    }
    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|false
     */
    public function getCode($type, $code = '')
    {
        $codes = [
            'method' => $this->type,
        ];

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }
    /**
     * Check if carrier has shipping tracking option available
     * All \Magento\Usa carriers have shipping tracking option available
     *
     * @return boolean
     */
    public function isTrackingAvailable()
    {
        return true;
    }
    /**
     * Check if carrier has shipping label option available
     *
     * @return boolean
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }
    /**
     * Do request to shipment
     *
     * @param \Magento\Shipping\Model\Shipment\Request $request
     * @return array|\Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function requestToShipment($request)
    {
        $packages = $request->getPackages();
        if (!is_array($packages) || !$packages) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No packages for request'));
        }
        $result = $this->_doShipmentRequest($request);

        $response = new \Magento\Framework\DataObject(
            [
                'info' => [
                    [
                        'tracking_number' => $result->getTrackingNumber(),
                        'label_content' => $result->getShippingLabelContent(),
                    ],
                ],
            ]
        );
        $request->setMasterTrackingId($result->getTrackingNumber());
        return $response;
    }
    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        $this->_prepareShipmentRequest($request);
        $result = new \Magento\Framework\DataObject();
       
        $responseShipment = $this->rateModel->createShipmentRequest($request);
        
        $trackingNumber = '';
        $packageCount = count($responseShipment->return->processShipmentResult->shipment->packages);
        if ($packageCount == 1 && isset($responseShipment->return->processShipmentResult->shipment->packages->barcode)) {
            $trackingNumber = (string)$responseShipment->return->processShipmentResult->shipment->packages->barcode;
        } elseif ($packageCount > 1 && isset($responseShipment->return->processShipmentResult->shipment->packages[0]->barcode)) {
            $trackingNumber = (string)$responseShipment->return->processShipmentResult->shipment->packages[0]->barcode;
        }

        if (empty($trackingNumber)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No Tracking Number available'));
        }

        if (!empty($responseShipment->return->processShipmentResult->shipment->id)) {
            $shipmentId = $responseShipment->return->processShipmentResult->shipment->id;
            $responseLabel = $this->rateModel->getLabel($shipmentId);
            $labels = $responseLabel->return->labels;
            
            if (!is_array($labels)) {
                $labels = [$labels];
            }
            $labelContent = $this->rateModel->getPdfContent($labels, $shipmentId);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('No Shipment Id to get Labels'));
        }
        
        $result->setShippingLabelContent($labelContent);
        $result->setTrackingNumber($trackingNumber);
        return $result;
    }

    /**
     * Get tracking information from number
     *
     * @param $trackNumber
     * @return string|\Magento\Shipping\Model\Tracking\Result|null
     */
    public function getTracking($trackNumber) {
        $trackingResuls = $this->_trackFactory->create();
        $trackingResulsFromService = $this->trackModel->getTrackingResultFromNumber($trackNumber);

        $tracking = $this->_trackStatusFactory->create();
        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle($this->scopeConfig->getValue('carriers/canpar/title'));
        $tracking->setTracking($trackNumber);

        if ($trackingResulsFromService->return->error) {
            $tracking->setTrackSummary(__('No tracking information'));
        } else {
            $tracking->setTrackSummary($trackingResulsFromService->summary);
        }

        $trackingResuls->append($tracking);

        return $trackingResuls;
    }
}
