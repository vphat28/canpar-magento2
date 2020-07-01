<?php
namespace CollinsHarper\Canpar\Controller\Adminhtml\Manifest;

class Create extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \CollinsHarper\Canpar\Model\Rate $rateModel
     */
    protected $rateModel;
    /**
     * @var \CollinsHarper\Canpar\Model\Manifest $manifestModel
     */
    protected $manifestModel;
    
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \CollinsHarper\Canpar\Model\Rate $rateModel,
     * @param \CollinsHarper\Canpar\Model\Manifest $manifestModel,
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \CollinsHarper\Canpar\Model\Rate $rateModel,
        \CollinsHarper\Canpar\Model\Manifest $manifestModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory  = $resultPageFactory;
        $this->rateModel          = $rateModel;
        $this->manifestModel      = $manifestModel;
    }

    public function execute()
    {
        $result = $this->rateModel->endOfDay();
        if ($result != false) {
            $this->manifestModel->setCanparManifestNum($result->return->manifest_num);
            $this->manifestModel->save();
            //$rate->updateManifestShipments($result->return->manifest_num);
            $this->messageManager->addSuccess("Manifest created");
        } else {
            $this->messageManager->addError("Manifest not created. Please review logs");
        }
        $this->_redirect('*/*/index');
    }
}
