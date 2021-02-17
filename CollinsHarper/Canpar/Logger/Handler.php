<?php
namespace CollinsHarper\Canpar\Logger;

use Monolog\Logger;
use Magento\Framework\Filesystem\DriverInterface;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = 'canpar.log';
    
    /**
     * @param DriverInterface $filesystem
     * @param string $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        $filePath = BP.'/var/log/'
    ) {
        $this->filesystem = $filesystem;
        parent::__construct(
            $filesystem,
            $filePath
        );
    }
}
