<?php
namespace CollinsHarper\Canpar\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Contact Resource Model
 *
 * @author Goutam Dutta
 */
class Manifest extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('ch_canpar_manifest', 'manifest_id');
    }
}
