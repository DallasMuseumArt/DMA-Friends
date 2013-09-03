<?php

function pmxi_wp_loaded() {				
	
	$start = time();
	// scheduling import executing logic
	$imports = new PMXI_Import_List();
	$imports->setColumns('id', 'scheduled', 'registered_on', 'processing', 'triggered', 'large_import', 'queue_chunk_number', 'current_post_ids')->getBy('scheduled !=', '', 'registered_on');
	if (!$imports->isEmpty()){
		foreach ($imports->setColumns('id', 'scheduled', 'registered_on', 'processing', 'triggered', 'large_import', 'queue_chunk_number', 'current_post_ids')->getBy('scheduled !=', '', 'registered_on')->convertRecords() as $imp) { /* @var $imp PMXI_Import_Record */		
			if (strlen($imp->scheduled) > 1 and $imp->isDue()) {
				$imp->getById($imp->id);
				if (!$imp->isEmpty()){ 
					if (empty($imp->large_import) or $imp->large_import == 'No'){
						$imp->execute(); // repull record from database since list didn't contain all the fileds for performance optimization purposes
					}
					elseif($imp->large_import == 'Yes'){ 
						if ($imp->triggered == 0){
							$imp->set(array(
								'triggered' => 1,
								'imported' => 0,
								'created' => 0,
								'updated' => 0,
								'skipped' => 0								
							))->save();
						} elseif($imp->processing == 0) {
							$imp->execute();
						} elseif($imp->processing == 1 and time() - strtotime($imp->registered_on) > 300){ // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
							$imp->set(array(
								'processing' => 0
							))->save()->execute();						
						}
					}
				}			
			}
			if (time() - $start > 4) break; // try not to delay server response for too long (4 secs) skipping scheduled imports if any for the next hit
		}
	}
	@ini_set("max_input_time", PMXI_Plugin::getInstance()->getOption('max_input_time'));
	@ini_set("max_execution_time", PMXI_Plugin::getInstance()->getOption('max_execution_time'));		

	/* Check if cron is manualy, then execute import */
	$cron_job_key = PMXI_Plugin::getInstance()->getOption('cron_job_key');
	
	if (!empty($cron_job_key) and !empty($_GET['import_id']) and !empty($_GET['import_key']) and $_GET['import_key'] == $cron_job_key and !empty($_GET['action']) and in_array($_GET['action'], array('processing','trigger'))) {		
		$import = new PMXI_Import_Record();
		$import->getById($_GET['import_id']);		
		if (!$import->isEmpty()){
			if (empty($import->large_import) or $import->large_import == 'No'){
				$import->execute();
			}
			elseif($import->large_import == 'Yes'){

				switch ($_GET['action']) {
					case 'trigger':
						$import->set(array(
							'triggered' => 1,
							'imported' => 0,
							'created' => 0,
							'updated' => 0,
							'skipped' => 0							
						))->save();
						break;
					case 'processing':
						if($import->processing == 1 and time() - strtotime($import->registered_on) > 300){ // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
							$import->set(array(
								'processing' => 0
							))->save();
						}
						break;					
				}
			} 			
		}
		$start = time();
		// start execution imports that is in the cron process
		$imports = new PMXI_Import_List();
		foreach ($imports->setColumns('id', 'scheduled', 'registered_on', 'processing', 'triggered', 'large_import', 'queue_chunk_number', 'current_post_ids')->getBy(array('processing' => 0, 'triggered' => 1))->convertRecords() as $imp) { /* @var $imp PMXI_Import_Record */
			$imp->getById($imp->id);
			if (!$imp->isEmpty())
				$imp->execute(); // repull record from database since list didn't contain all the fileds for performance optimization purposes
			
			if (time() - $start > 4) break; // try not to delay server response for too long (4 secs) skipping scheduled imports if any for the next hit
		}
	}	
}