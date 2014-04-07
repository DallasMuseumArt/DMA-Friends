<?php

namespace DMA;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class DmaLogHandler extends AbstractProcessingHandler {
    private $statement;

    public function __construct() {
        parent::__construct($level = Logger::DEBUG, $bubble = TRUE);
    }   

    protected function write(array $record) {
        var_dump('record');
        var_dump($record);
        global $wpdb;
    }   
}
