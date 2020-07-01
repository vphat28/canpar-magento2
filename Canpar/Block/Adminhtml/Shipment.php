<?php
namespace CollinsHarper\Canpar\Block\Adminhtml;

class Shipment extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_shipment';
        $this->_blockGroup = 'CollinsHarper_Canpar';
        $this->_headerText = __('Canpar Shipment');
        
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
