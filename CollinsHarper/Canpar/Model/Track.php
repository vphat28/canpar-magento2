<?php

namespace CollinsHarper\Canpar\Model;

use CollinsHarper\Canpar\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\TemplateFactory;

class Track
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var ScopeConfigInterface
     */
    protected $storeConfig;

    /**
     * @var TemplateFactory
     */
    protected $templateFactory;

    /**
     * @param Data $helper
     * @param ScopeConfigInterface $storeConfig
     * @param TemplateFactory $templateFactory
     */
    public function __construct(
        Data $helper,
        ScopeConfigInterface $storeConfig,
        TemplateFactory $templateFactory
    ) {
        $this->helper = $helper;
        $this->storeConfig = $storeConfig;
        $this->templateFactory = $templateFactory;
    }

    /**
     * Get tracking results from canpar web service
     *
     * @param string $trackNumber
     * @return array
     */
    public function getTrackingResultFromNumber($trackNumber) {
        $client = $this->helper->getClient('addon');
        $return = array();

        if ($client) {
            // A true or false flag to instruct trackByBarcode to either return tracking
            // for all the parcels associated with the barcode’s shipment,
            // or only the parcel itself.
            $return = $client->trackByBarCode(["request" => ['barcode' => $trackNumber, "track_shipment" => false]]);
            $template = $this->templateFactory->create();

            /** @var Template $template */
            $template->setTrackData($return->return);
            $template->setTemplate("CollinsHarper_Canpar::trackProgress.phtml");
            $return->summary = $template->toHtml();
        }

        return $return;
    }
}