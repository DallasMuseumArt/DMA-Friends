<?php 

namespace DMA;

use BadgeOS\Log;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ElasticSearchHandler;
use Elastica\Client as Elastica;

class DmaLog extends Log {
    public function pushHandlers() {
        $use_elastic = get_option('elastic_search');
    
        if ($use_elastic) {    
            $elastica = new Elastica(array(
                'host'  => get_option('elastic_search_host'),
                'port'  => get_option('elastic_search_port'),
            ));
    
            $this->pushHandler(new ElasticSearchHandler($elastica));
        }

        parent::pushHandlers();
    }   
}
