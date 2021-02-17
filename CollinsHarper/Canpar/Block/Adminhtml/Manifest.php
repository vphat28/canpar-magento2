<?php
namespace CollinsHarper\Canpar\Block\Adminhtml;

class Manifest extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_manifest';
        $this->_blockGroup = 'CollinsHarper_Canpar';
        $this->_headerText = __('Canpar Manifests');
        
        
        $this->buttonList->add('Run End Of Day', [
            'label' => __('Run End Of Day'),
            'onclick'   => 'setLocation(\''.$this->getUrl('*/*/create').'\');',
            'class'     => 'primary',
        ]);
        parent::_construct();
        $this->buttonList->remove('add');
    }
}
