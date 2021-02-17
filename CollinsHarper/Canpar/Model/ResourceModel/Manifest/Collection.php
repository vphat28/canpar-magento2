<?php
namespace CollinsHarper\Canpar\Model\ResourceModel\Manifest;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Contact Resource Model Collection
 *
 * @author      Pierre FAY
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('CollinsHarper\Canpar\Model\Manifest', 'CollinsHarper\Canpar\Model\ResourceModel\Manifest');
    }
}
