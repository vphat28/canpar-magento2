<?php
namespace CollinsHarper\Canpar\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

/**
 * Configuration data of carrier
 */
class Data extends AbstractHelper
{
    /**
     * @var \CollinsHarper\Canpar\Model\SoapClient $soapClient
     */
    protected $soapClient;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    /**
     * @var \CollinsHarper\Canpar\Logger\Logger $logger
     */
    protected $logger;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productLoader;
    /**
     * @var \CollinsHarper\MeasureUnit\Helper\Data
     */
    protected $chUnitHelper;
    
    /**
     * Constructor
     *
     * @param \CollinsHarper\Canpar\Model\SoapClient $soapClient
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \CollinsHarper\Canpar\Logger\Logger $logger
     * @param \Magento\Catalog\Model\ProductFactory $productLoader
     * @param \CollinsHarper\MeasureUnit\Helper\Data $chUnitHelper
     */
    public function __construct(
        \CollinsHarper\Canpar\Model\SoapClient $soapClient,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \CollinsHarper\Canpar\Logger\Logger $logger,
        \Magento\Catalog\Model\ProductFactory $productLoader,
        \CollinsHarper\MeasureUnit\Helper\Data $chUnitHelper
    ) {
        
        $this->soapClient       = $soapClient;
        $this->scopeConfig      = $scopeConfig;
        $this->logger           = $logger;
        $this->productLoader    = $productLoader;
        $this->chUnitHelper     = $chUnitHelper;
    }
    /**
     * Get all service types
     *
     * @return array
     */
    public function getServiceType()
    {
        return [
            "1" => "GROUND",
            "2" => "U.S.A",
            "3" => "SELECT LETTER",
            "4" => "SELECT PAK",
            "5" => "SELECT",
            "C" => "EXPRESS LETTER",
            "D" => "EXPRESS PAK",
            "E" => "EXPRESS",
            "F" => "U.S.A LETTER",
            "G" => "U.S.A PAK",
            "H" => "SELECT U.S.A",
            "I" => "INTERNATIONAL"
        ];
    }
    /**
     * Get all service constraints
     *
     * @return array
     */
    public function getServiceConstraints()
    {
        return [
            "1"=>[
                "min"=>1,
                "max"=>1000
            ],
            "2"=>[
                "min"=>1,
                "max"=>1000
            ],
            "3"=>[
                "min"=>0,
                "max"=>1
            ],
            "4"=>[
                "min"=>1,
                "max"=>5
            ],
            "5"=>[
                "min"=>1,
                "max"=>75
            ],
            "C"=>[
                "min"=>0,
                "max"=>1
            ],
            "D"=>[
                "min"=>1,
                "max"=>3
            ],
            "E"=>[
                "min"=>1,
                "max"=>75
            ],
            "F"=>[
                "min"=>1,
                "max"=>999999
            ],
            "G"=>[
                "min"=>1,
                "max"=>3
            ],
            "H"=>[
                "min"=>1,
                "max"=>70
            ],
            "I"=>[
                "min"=>1,
                "max"=>999999
            ],
        ];
    }
    /*
     *  Get API Soapclient
     */
    public function getClient($type)
    {
        return $this->soapClient->getClient($type);
    }
    /**
     * Is debugging enabled
     *
     * @return boolean
     */
    public function isDebug()
    {
        return $this->scopeConfig->getValue('carriers/canpar/debug') == 1;
    }
    /**
     *  Logging
     */
    public function log($message)
    {
        if ($this->scopeConfig->getValue('carriers/canpar/debug')) {
            $this->logger->info($message);
        }
    }
    /**
     *  Get product package weight
     */
    public function getPackageWeightLb($request)
    {
        $weight = 0;
        $defaultWeightUnit = $this->scopeConfig->getValue('catalog/measure_units/weight');
        $weightUnit = $this->scopeConfig->getValue('carriers/canpar/default_weight_unit');

        foreach ($request->getAllItems() as $item) {
            $qty = ($item->getQty() * 1);
            $product = $this->productLoader->create()->load($item->getProductId());
            $weight += $this->chUnitHelper->getConvertedWeight(($qty * $product->getWeight()), $defaultWeightUnit, strtolower($weightUnit));
        }
        return $weight;
    }
    /**
     * Get Current Date Time
     *
     * @return datetime
     */
	function getCurrentDateTime() {
		$timezone  = 0; //(GMT -5:00) EST (U.S. & Canada) 
	    $currentDateTime = gmdate("Y-m-d\TH:i:s\Z", time() + 3600*($timezone+date("I"))); 
		return $currentDateTime;
	}
}
