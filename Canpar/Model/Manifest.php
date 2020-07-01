<?php
namespace CollinsHarper\Canpar\Model;

use Magento\Cron\Exception;
use Magento\Framework\Model\AbstractModel;

/**
 * Manifest Model
 *
 * @author Goutam Dutta
 */
class Manifest extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\CollinsHarper\Canpar\Model\ResourceModel\Manifest::class);
    }
}
