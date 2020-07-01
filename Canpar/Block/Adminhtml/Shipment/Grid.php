<?php
namespace CollinsHarper\Canpar\Block\Adminhtml\Shipment;

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
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $collectionFactory,
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
        
        $collection->getSelect()->joinLeft(['o'=>$collection->getTable('sales_order')], 'main_table.order_id=o.entity_id', ['order_increment_id'=>'o.increment_id',
            'order_created_date'=>'o.created_at',
            'o.shipping_description']);
        $collection->addFieldToFilter('o.shipping_description', ['like'=>'%canpar%']);
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
        $this->addColumn('increment_id', ['header' => __('Shipment #'), 'index' => 'increment_id']);
        $this->addColumn('entity_id', ['header' => __('Shipment #'), 'index' => 'entity_id']);
        $this->addColumn('created_at', ['header' => __('Date Shipped'), 'index' => 'created_at','type'=>'datetime']);
        $this->addColumn('order_increment_id', ['header' => __('Order #'), 'index' => 'order_increment_id']);
        $this->addColumn('order_created_date', ['header' => __('Order Date'), 'index' => 'order_created_date','type'=>'datetime']);
        $this->addColumn('total_qty', ['header' => __('Total Qty'), 'index' => 'total_qty','type'  => 'number']);
        
        $this->addColumn(
            'action',
            [
                'header'    => __('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => [
                    [
                        'caption' => __('View'),
                        'url'     => ['base'=>'*/sales_shipment/view'],
                        'field'   => 'shipment_id'
                    ]
                ],
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true
            ]
        );
        return parent::_prepareColumns();
    }
    /**
     * Prepare and set options for massaction
     *
     * @return
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('shipment_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('create_shipment', [
            'label'=> __('Create Canpar Shipments'),
            'url'  => $this->getUrl('*/shipment/massCreate'),
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
            '*/sales_shipment/view',
            [
                'shipment_id'=> $row->getEntityId(),
            ]
        );
    }
}
