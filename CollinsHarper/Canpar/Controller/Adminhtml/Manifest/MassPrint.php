<?php
namespace CollinsHarper\Canpar\Controller\Adminhtml\Manifest;

use Magento\Framework\App\Filesystem\DirectoryList;

class MassPrint extends \Magento\Backend\App\Action
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
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    
    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \CollinsHarper\Canpar\Model\Rate $rateModel,
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \CollinsHarper\Canpar\Model\Rate $rateModel,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory  = $resultPageFactory;
        $this->rateModel          = $rateModel;
        $this->fileFactory = $fileFactory;
    }
    /**
     * Mass print Manifests
     *
     * @return pdf
     */
    public function execute()
    {
        try {
            $manifestIds = $this->getRequest()->getParam('manifest_ids');
            
            if (!is_array($manifestIds) && is_numeric($manifestIds)) {
                $manifestIds = [$manifestIds];
            }
            $resultPdf = new \Zend_Pdf();

            foreach ($manifestIds as $mId) {
                $manifestPdfString = $this->rateModel->getManifestPdfById($mId);
                $manifestPdf = new \Zend_Pdf($manifestPdfString);

                foreach ($manifestPdf->pages as $page) {
                    $resultPdf->pages[] = clone $page;
                }
            }
            $fileName = 'canpar-manifests-' . date('Y-m-d_H-i-s') . '.pdf';
            return $this->fileFactory->create($fileName, $resultPdf->render(), DirectoryList::VAR_DIR);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
        }
        $this->_redirect('*/*/index');
    }
}
