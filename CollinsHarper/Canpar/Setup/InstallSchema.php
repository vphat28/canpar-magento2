<?php
namespace Collinsharper\Canpar\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * install tables
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        
        /**
         * Create table 'ch_canpar_shipment'
         */
        if (!$installer->tableExists('ch_canpar_shipment')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('ch_canpar_shipment')
            )
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'ID'
            )
            ->addColumn(
                'shipment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable => true'],
                'Shipment Id'
            )
            ->addColumn(
                'magento_shipment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11,
                ['nullable => true'],
                'Magento Shipment Id'
            )
            ->addColumn(
                'manifest_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable => true'],
                'Manifest Id'
            )
            ->addColumn(
                'tracking_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable => true'],
                'Tracking Code'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                [],
                'Created At'
            )
            ->setComment('Canpar Shipment Table');
            $installer->getConnection()->createTable($table);
        }
        /**
         * Create table 'ch_canpar_manifest'
         */
        if (!$installer->tableExists('ch_canpar_manifest')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('ch_canpar_manifest')
            )
            ->addColumn(
                'manifest_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'nullable' => false,
                    'primary'  => true,
                    'unsigned' => true,
                ],
                'Manifest Id'
            )
            ->addColumn(
                'canpar_manifest_num',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable => false'],
                'Canpar Manifest Num'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->setComment('Canpar Manifest Table');
            $installer->getConnection()->createTable($table);
        }
        $installer->endSetup();
    }
}
