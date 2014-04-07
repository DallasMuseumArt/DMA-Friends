<?php 

namespace DMA;

use BadgeOS\Log;
use Monolog\Handler\StreamHandler;

class DmaLog extends Log {
    //public static $id = 'dma_log';
    public function pushHandlers() {
        parent::pushHandlers();
        //$this->pushHandler(new StreamHandler(__DIR__ . '/../test-stream.log'), 'info');
        // TODO add elastic search to handlers
    }   
}
