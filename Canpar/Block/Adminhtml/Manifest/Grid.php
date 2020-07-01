<?php
namespace CollinsHarper\Canpar\Block\Adminhtml\Manifest;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    protected $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \CollinsHarper\Canpar\Model\ResourceModel\Manifest\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn('manifest_id', ['header' => __('Manifest #'), 'index' => 'manifest_id']);
        $this->addColumn('canpar_manifest_num', ['header' => __('Canpar Manifest #'), 'index' => 'canpar_manifest_num']);
        $this->addColumn('created_at', ['header' => __('Date Manifested'), 'index' => 'created_at','type'=>'datetime']);
        
        return parent::_prepareColumns();
    }
    /**
     * Prepare and set options for massaction
     *
     * @return
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('manifest_id');
        $this->getMassactionBlock()->setFormFieldName('manifest_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('create_shipment', [
            'label'=> __('Print Manifests'),
            'url'  => $this->getUrl('*/manifest/massPrint'),
        ]);

        return $this;
    }
    /**
     * Get url for row
     *
     * @param string $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/massPrint',
            [
                'manifest_ids'=> $row->getManifestId(),
            ]
        );
    }
}
