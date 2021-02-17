<?php
namespace CollinsHarper\Canpar\Model;

use Magento\Framework\Model\AbstractModel;

class SoapClient extends AbstractModel
{
    const API_URL = 'https://canship.canpar.com/canshipws/services/CanparRatingService';
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * SoapClient constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     */
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        
        $this->scopeConfig = $scopeConfig;
    }
    
    public function getServiceType($type)
    {
        $return = [];
        switch ($type) {
            case 'rating':
                $url = self::API_URL;
                $return = [
                    'wsdl' => $url.'?wsdl',
                    'endpoint' => $url
                ];
                break;
            case 'business':
                $url = $this->scopeConfig->getValue('carriers/canpar/business_endpoint');
                $return = [
                    'wsdl' => $url.'?wsdl',
                    'endpoint' => $url
                ];
                break;
            case 'addon':
                $url = $this->scopeConfig->getValue('carriers/canpar/addon_endpoint');
                $return = [
                    'wsdl' => $url.'?wsdl',
                    'endpoint' => $url
                ];
                break;
        }
        return $return;
    }
    /**
     * Get API Soapclient
     *
     * @param string $type
     * @return object
     */
    public function getClient($type)
    {
        $serviceType = $this->getServiceType($type);
        $url         = $serviceType['wsdl'];
        $soapClient  = new \Zend\Soap\Client($url);
        $soapClient->setSoapVersion(SOAP_1_2);
        return $soapClient;
    }
}
