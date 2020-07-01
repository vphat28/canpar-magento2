<?php
namespace CollinsHarper\Canpar\Model;

use Magento\Framework\Model\AbstractModel;

class SoapClient extends AbstractModel
{
    
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
                $sandboxMode = $this->scopeConfig->getValue('carriers/canpar/sandbox_mode');
                if ($sandboxMode) {
                    $url = $this->scopeConfig->getValue('carriers/canpar/sandbox_endpoint');
                } else {
                    $url = $this->scopeConfig->getValue('carriers/canpar/endpoint');
                }
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
