<?php
namespace CollinsHarper\Canpar\Controller\Adminhtml\Shipment;

class massCreate extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \CollinsHarper\Canpar\Model\Rate $rateModel
     */
    protected $rateModel;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \CollinsHarper\Canpar\Model\Rate $rateModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->rateModel          = $rateModel;
    }

    public function execute()
    {
        $shipment_ids = $this->getRequest()->getParam('shipment_ids');
        $this->rateModel->createShipments($shipment_ids);
    }
}
