<?php

class PMXI_Import_Record extends PMXI_Model_Record {
	
	/**
	 * Some pre-processing logic, such as removing control characters from xml to prevent parsing errors
	 * @param string $xml
	 */
	public static function preprocessXml( & $xml) {		
		
		$xml = str_replace("&", "&amp;", str_replace("&amp;","&", $xml));
		
	}

	/**
	 * Validate XML to be valid for improt
	 * @param string $xml
	 * @param WP_Error[optional] $errors
	 * @return bool Validation status
	 */
	public static function validateXml( & $xml, $errors = NULL) {
		if (FALSE === $xml or '' == $xml) {
			$errors and $errors->add('form-validation', __('XML file does not exist, not accessible or empty', 'pmxi_plugin'));
		} else {
						
			PMXI_Import_Record::preprocessXml($xml);																						

			libxml_use_internal_errors(true);
			libxml_clear_errors();
			$_x = @simplexml_load_string($xml);
			$xml_errors = libxml_get_errors();			
			libxml_clear_errors();
			if ($xml_errors) {								
				$error_msg = '<strong>' . __('Invalid XML', 'pmxi_plugin') . '</strong><ul>';
				foreach($xml_errors as $error) {
					$error_msg .= '<li>';
					$error_msg .= __('Line', 'pmxi_plugin') . ' ' . $error->line . ', ';
					$error_msg .= __('Column', 'pmxi_plugin') . ' ' . $error->column . ', ';
					$error_msg .= __('Code', 'pmxi_plugin') . ' ' . $error->code . ': ';
					$error_msg .= '<em>' . trim(esc_html($error->message)) . '</em>';
					$error_msg .= '</li>';
				}
				$error_msg .= '</ul>';
				$errors and $errors->add('form-validation', $error_msg);				
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) {
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}

	/**
	 * Check whether current import should be perfomed again according to scheduling options
	 */
	public function isDue()
	{
		if ( ! $this->scheduled or date('YmdHi') <= date('YmdHi', strtotime($this->registered_on))) return false; // scheduling is disabled or the task has been executed this very minute
		if ('0000-00-00 00:00:00' == $this->registered_on) return true; // never executed but scheduled
		
		$task = new _PMXI_Import_Record_Cron_Parser($this->scheduled);
		return $task->isDue($this->registered_on);
	}
	
	/**
	 * Import all files matched by path
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function execute($logger = NULL, $cron = true) {
		$this->set('registered_on', date('Y-m-d H:i:s'))->save(); // update registered_on to indicated that job has been exectured even if no files are going to be imported by the rest of the method
		
		$uploads = wp_upload_dir();	

		if ($this->path) {
			if (in_array($this->type, array('ftp'))) { // file paths support patterns
				$logger and call_user_func($logger, __('Reading files for import...', 'pmxi_plugin'));
				$files = PMXI_Helper::safe_glob($this->path, PMXI_Helper::GLOB_NODIR | PMXI_Helper::GLOB_PATH);
				$logger and call_user_func($logger, sprintf(_n('%s file found', '%s files found', count($files), 'pmxi_plugin'), count($files)));
			} else {  // single file path
				$files = array($this->path);
			}
			
			foreach ($files as $ind => $path) {

				!$cron and $logger and call_user_func($logger, sprintf(__('Importing %s (%s of %s)', 'pmxi_plugin'), $path, $ind + 1, count($files)));

				if ($this->type == 'url'){

					if ('zip' == $this->feed_type or '' == $this->feed_type and preg_match('%\W(zip)$%i', trim($path))) {		

						$filePath = '';					
						
						if ((empty($this->large_import) or $this->large_import == 'No') or ( ! $this->queue_chunk_number and $this->processing == 0 )) {

							$tmpname = $uploads['path'] . '/' . wp_unique_filename($uploads['path'], basename($path));

							@copy($path, $tmpname);				
							
							if (!file_exists($tmpname)) {
								
								get_file_curl($path, $tmpname);

							    if (!file_exists($tmpname)) $this->errors->add('form-validation', __('Failed upload ZIP archive', 'pmxi_plugin'));
							
							}							

							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

							$archive = new PclZip($tmpname);
						    if (($v_result_list = $archive->extract(PCLZIP_OPT_PATH, $uploads['path'], PCLZIP_OPT_REPLACE_NEWER)) == 0) {
						    	$this->errors->add('form-validation', 'Failed to open uploaded ZIP archive : '.$archive->errorInfo(true));			    	
						   	}
							else {																

								if (!empty($v_result_list)){
									foreach ($v_result_list as $unzipped_file) {
										if ($unzipped_file['status'] == 'ok') $filePath = $unzipped_file['filename'];
									}
								}
						    	if($uploads['error']){
									 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
								}

								if(empty($filePath)){						
									$zip = zip_open(trim($tmpname));
									if (is_resource($zip)) {
										
										$filePath = '';
										while ($zip_entry = zip_read($zip)) {
											$filePath = zip_entry_name($zip_entry);												
										    $fp = fopen($uploads['path']."/".$filePath, "w");
										    if (zip_entry_open($zip, $zip_entry, "r")) {
										      $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
										      fwrite($fp,"$buf");
										      zip_entry_close($zip_entry);
										      fclose($fp);
										    }
										    break;
										}
										zip_close($zip);							

									} else {
								        $this->errors->add('form-validation', __('Failed to open uploaded ZIP archive. Can\'t extract files.', 'pmxi_plugin'));
								    }						
								}

								if (preg_match('%\W(csv|txt|dat|psv)$%i', trim($filePath))){
																
									if (empty($this->large_import) or $this->large_import == 'No') {
										$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
									}
									elseif ( $this->queue_chunk_number == 0 and $this->processing == 0 ) {
									
										$this->set(array('processing' => 1))->save(); // lock cron requests		

										include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
										$csv = new PMXI_CsvParser($filePath, true); // create chunks
										$filePath = $csv->xml_path;									   					  

										$this->set(array('queue_chunk_number' => 1, 'processing' => 0))->save(); // set pointer to the first chunk and unlock cron process    					  

									}	
									else $filePath = '';

								}							
							}
						}
						
					} elseif ('csv' == $this->feed_type or '' == $this->feed_type and preg_match('%\W(csv|txt|dat|psv)$%i', trim($path))) {																				

						if (empty($this->large_import) or $this->large_import == 'No') {																				
							// copy remote file in binary mode
							$filePath = pmxi_copy_url_file($path);									
							$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																					
						}
						elseif ( $this->queue_chunk_number == 0 and $this->processing == 0 ) {	

							$this->set(array('processing' => 1))->save(); // lock cron requests	
							// copy remote file in binary mode
							$filePath = pmxi_copy_url_file($path);									
							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
							$csv = new PMXI_CsvParser($filePath, true); // create chunks
							$filePath = $csv->xml_path;	

							$this->set(array('queue_chunk_number' => 1, 'processing' => 0))->save(); // set pointer to the first chunk and unlock cron process    					  					
						}
						else $filePath = '';

					} else {
						
						if (empty($this->large_import) or $this->large_import == 'No') {	
							$fileInfo = ('gz' == $this->feed_type or '' == $this->feed_type and preg_match('%\W(gz)$%i', trim($path))) ? pmxi_gzfile_get_contents($path) : pmxi_copy_url_file($path, true);
							$filePath = $fileInfo['localPath'];				
							// detect CSV or XML 										
							if ( $fileInfo['type'] == 'csv') { // it is CSV file
								$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																						
							}
						} elseif( $this->queue_chunk_number == 0 and $this->processing == 0 ){
							
							$this->set(array('processing' => 1))->save(); // lock cron requests

							$fileInfo = ('gz' == $this->feed_type or '' == $this->feed_type and preg_match('%\W(gz)$%i', trim($path))) ? pmxi_gzfile_get_contents($path) : pmxi_copy_url_file($path, true);
							$filePath = $fileInfo['localPath'];		
							// detect CSV or XML 
							if ( $fileInfo['type'] == 'csv') { // it is CSV file																												
								include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
								$csv = new PMXI_CsvParser($filePath, true); // create chunks
								$filePath = $csv->xml_path;								
							}

							$this->set(array('queue_chunk_number' => 1, 'processing' => 0))->save(); // set pointer to the first chunk and unlock cron process 
						}											
					}

				} else { // if import type NOT URL

					if ($this->type == 'ftp'){
					
						// path to remote file
						$remote_file = $this->path;
										
						// set up basic connection
						$ftp_url = $this->path;
						$parsed_url = parse_url($ftp_url);
						$ftp_server = $parsed_url['host'] ;
						$conn_id = ftp_connect( $ftp_server );
						$is_ftp_ok = TRUE;				

						// login with username and password
						$ftp_user_name = rawurldecode($parsed_url['user']);
						$ftp_user_pass = rawurldecode($parsed_url['pass']);

						// hide warning message
						echo '<span style="display:none">';
						if ( !ftp_login($conn_id, $ftp_user_name, $ftp_user_pass) ){
							$this->errors->add('form-validation', __('Login authentication failed', 'pmxi_plugin'));
							$is_ftp_ok = false;
						}
						echo '</span>';

						if ( $is_ftp_ok ){						

							$filePath = $uploads['path']  .'/'. basename($path);										
							
							get_file_curl($path, $filePath);						

							// close the connection and the file handler
							ftp_close($conn_id);

							$path = $filePath;

						}					

					}

					if (preg_match('%\W(zip)$%i', trim(basename($path)))) {
						
						include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

						$archive = new PclZip(trim($path));
					    if (($v_result_list = $archive->extract(PCLZIP_OPT_PATH, $uploads['path'], PCLZIP_OPT_REPLACE_NEWER)) == 0) {
					    	$this->errors->add('form-validation', 'Failed to open uploaded ZIP archive : '.$archive->errorInfo(true));			    	
					   	}
						else {
							
							$filePath = '';

							if (!empty($v_result_list)){
								foreach ($v_result_list as $unzipped_file) {
									if ($unzipped_file['status'] == 'ok') $filePath = $unzipped_file['filename'];
								}
							}
					    	if($uploads['error']){
								 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
							}

							if(empty($filePath)){						
								$zip = zip_open(trim($path));
								if (is_resource($zip)) {																		
									while ($zip_entry = zip_read($zip)) {
										$filePath = zip_entry_name($zip_entry);												
									    $fp = fopen($uploads['path']."/".$filePath, "w");
									    if (zip_entry_open($zip, $zip_entry, "r")) {
									      $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
									      fwrite($fp,"$buf");
									      zip_entry_close($zip_entry);
									      fclose($fp);
									    }
									    break;
									}
									zip_close($zip);							

								} else {
							        $this->errors->add('form-validation', __('Failed to open uploaded ZIP archive. Can\'t extract files.', 'pmxi_plugin'));
							    }						
							}																					

							if (preg_match('%\W(csv|txt|dat|psv)$%i', trim($filePath))){ // If CSV file found in archieve						

								if($uploads['error']){
									 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
								}																		
								if (empty($this->large_import) or $this->large_import == 'No') {
									$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
								}
								elseif ( $this->queue_chunk_number == 0 and $this->processing == 0 ){	

									$this->set(array('processing' => 1))->save(); // lock cron requests	

									include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
									$csv = new PMXI_CsvParser($filePath, true); // create chunks
									$filePath = $csv->xml_path;					

									$this->set(array('queue_chunk_number' => 1, 'processing' => 0))->save(); // set pointer to the first chunk and unlock cron process

								}
								else $filePath = '';
							}							
						}							

					} elseif ( preg_match('%\W(csv|txt|dat|psv)$%i', trim($path))) { // If CSV file uploaded										
						if($uploads['error']){
							 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
						}									
		    			
		    			$filePath = $post['filepath'];					
						
						if (empty($this->large_import) or $this->large_import == 'No') {
							$filePath = PMXI_Plugin::csv_to_xml($path);					
						} 
						elseif ( $this->queue_chunk_number == 0 and $this->processing == 0 ) {		

							$this->set(array('processing' => 1))->save(); // lock cron requests	

							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
							$csv = new PMXI_CsvParser($path, true);					
							$filePath = $csv->xml_path;			

							$this->set(array('queue_chunk_number' => 1, 'processing' => 0))->save(); // set pointer to the first chunk and unlock cron process			
						}
						else $filePath = '';	

					} elseif(preg_match('%\W(gz)$%i', trim($path))) { // If gz file uploaded
						$fileInfo = pmxi_gzfile_get_contents($path);
						$filePath = $fileInfo['localPath'];		

						// detect CSV or XML 
						if ( $fileInfo['type'] == 'csv') { // it is CSV file									
							if (empty($this->large_import) or $this->large_import == 'No') {																
								$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																						
							}
							elseif ( $this->queue_chunk_number == 0 and $this->processing == 0 ) {	

								$this->set(array('processing' => 1))->save(); // lock cron requests

								include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
								$csv = new PMXI_CsvParser($filePath, true); // create chunks
								$filePath = $csv->xml_path;		

								$this->set(array('queue_chunk_number' => 1, 'processing' => 0))->save(); // set pointer to the first chunk and unlock cron process																				
							}
							else $filePath = '';
						}					

					} else {

						$this->set(array('queue_chunk_number' => 1, 'processing' => 0))->save(); // set pointer to the first chunk and unlock cron process																				

						$filePath = $path;

					}
				}
				
				// if empty file path, than it's mean feed in cron process. Take feed path from history.
				if (empty($filePath)){
					$history = new PMXI_File_List();
					$history->setColumns('id', 'name', 'registered_on', 'path')->getBy(array('import_id' => $this->id), 'id DESC');				
					if ($history->count()){
						$history_file = new PMXI_File_Record();
						$history_file->getBy('id', $history[0]['id']);
						$filePath =	$history_file->path;						
					}
				}
				
				// if feed path founded
				if ( ! empty($filePath) and @file_exists($filePath) ) {

					if ( $this->large_import == 'Yes' and $this->queue_chunk_number == 1 and $this->processing == 0 ){ // large import first cron request

						$this->set(array('processing' => 1))->save(); // lock cron requests																		
						
						set_time_limit(0);							

						$file = new PMXI_Chunk($filePath, array('element' => $this->root_element, 'path' => $uploads['path']));					
					    
					    // chunks counting		    
					    $chunks = 0;
					    while ($xml = $file->read()) {
					    	if (!empty($xml)) {				
					    		$xml = $file->encoding . "\n" . $xml;
					      		PMXI_Import_Record::preprocessXml($xml);
						      	$dom = new DOMDocument('1.0', 'UTF-8');
								$old = libxml_use_internal_errors(true);
								$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load
								libxml_use_internal_errors($old);
								$xpath = new DOMXPath($dom);
								if (($elements = @$xpath->query($this->xpath)) and $elements->length) $chunks++;
								unset($dom, $xpath, $elements);
						    }
						}
						unset($file);

						$this->set(array('count' => $chunks, 'processing' => 0))->save(); // set pointer to the first chunk, updates feed elements count and unlock cron process					    

					} elseif ($this->large_import == 'No') {
						ob_start();
						$filePath && @readgzfile($filePath);					
						$xml = ob_get_clean();										
				
						if (empty($xml)) {
							$xml = @file_get_contents($filePath);										
							if (empty($xml)) get_file_curl($filePath, $uploads['path']  .'/'. basename($filePath));
							if (empty($xml)) $xml = @file_get_contents($uploads['path']  .'/'. basename($filePath));
						}
					}
					if (($this->large_import == 'Yes' and $this->queue_chunk_number == 1 and $this->processing == 0) or $this->large_import == 'No'){						

						// unlick previous files
						$history = new PMXI_File_List();
						$history->setColumns('id', 'name', 'registered_on', 'path')->getBy(array('import_id' => $this->id), 'id DESC');				
						if ($history->count()){
							foreach ($history as $file){						
								if (@file_exists($file['path']) and $file['path'] != $filePath) @unlink($file['path']);
								$history_file = new PMXI_File_Record();
								$history_file->getBy('id', $file['id']);
								if ( ! $history_file->isEmpty()) $history_file->delete();
							}
						}

						// update history
						$history_file = new PMXI_File_Record();
						$history_file->set(array(
							'name' => $this->name,
							'import_id' => $this->id,
							'path' => $filePath,
							'contents' => (isset($xml)) ? $xml : '',
							'registered_on' => date('Y-m-d H:i:s'),
						))->save();
					}

					do_action( 'pmxi_before_xml_import', $this->id );										

					// compose data to look like result of wizard steps									
					if (empty($this->large_import) or $this->large_import == 'No'){

						PMXI_Import_Record::preprocessXml($xml);					

						$this->process($xml, false, false, $cron );

					}
					elseif( $this->large_import == 'Yes' and $this->queue_chunk_number and $this->processing == 0 ){	

						$this->set(array('processing' => 1))->save(); // lock cron requests						

						set_time_limit(0);							

						$file = new PMXI_Chunk($filePath, array('element' => $this->root_element, 'path' => $uploads['path']));					
					    
					    $loop = 1; $start_cron = time();
					    while ($xml = $file->read()) {					      						    					    					    					    	
					    	if (!empty($xml)) {				 
					    		$xml = $file->encoding . "\n" . $xml;     	
					      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      							      					      		
						      	$dom = new DOMDocument('1.0', 'UTF-8');															
								$old = libxml_use_internal_errors(true);
								$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
								libxml_use_internal_errors($old);
								$xpath = new DOMXPath($dom);
								if (($elements = @$xpath->query($this->xpath)) and $elements->length){
									if ($loop >= $this->queue_chunk_number){
										$this->process($xml, false, $this->queue_chunk_number, $cron);
										if ( $this->count != $this->queue_chunk_number ) $this->set(array('queue_chunk_number' => $this->queue_chunk_number + 1))->save();								  		
									}
									$loop++;
								}
								unset($dom, $xpath, $elements);							
						    }
						    if (time() - $start_cron > 120) // (2 mins) skipping scheduled imports if any for the next hit					    	
						    	break; 
						    
						}
						unset($file);			

						$this->set(array('processing' => 0))->save(); // unlock cron requests			

						// delect, if cron process if finished
						if ( $this->count == $this->queue_chunk_number ){
							
							if (! empty($this->options['is_delete_missing'])){
								$postList = new PMXI_Post_List();				
								$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	
								$missing_ids = array();
								foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
									empty($this->options['is_keep_attachments']) and wp_delete_attachments($missingPost['post_id']);
									$missing_ids[] = $missingPost['post_id'];
									
									$sql = "delete a
									FROM ".PMXI_Plugin::getInstance()->getTablePrefix()."posts a
									WHERE a.id=%d";
									
									$this->wpdb->query( 
										$this->wpdb->prepare($sql, $missingPost['id'])
									);
																	
								}

								if (!empty($missing_ids) && is_array($missing_ids)){
									$sql = "delete a,b,c
									FROM ".$this->wpdb->posts." a
									LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
									LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )				
									WHERE a.ID IN (".implode(',', $missing_ids).");";

									$this->wpdb->query( 
										$this->wpdb->prepare($sql, '')
									);
								}
							}

							// Set out of stock status for missing records [Woocommerce add-on option]
							if (empty($this->options['is_delete_missing']) and $this->options['custom_type'] == "product" and class_exists('PMWI_Plugin') and !empty($this->options['missing_records_stock_status'])) {
								
								$postList = new PMXI_Post_List();											
								$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	
								foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
									update_post_meta( $missingPost['post_id'], '_stock_status', 'outofstock' );
									update_post_meta( $missingPost['post_id'], '_stock', 0 );
								}

							}

							// Set custom fields for missing records
							if ( empty($this->options['is_delete_missing']) and $this->options['is_update_missing_cf'] ) {
								
								$postList = new PMXI_Post_List();											
								$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	
								foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
									update_post_meta( $missingPost['post_id'], $this->options['update_missing_cf_name'], $this->options['update_missing_cf_value'] );
								}

							}

							$this->set(array(
								'processing' => 0,
								'triggered' => 0,
								'queue_chunk_number' => 0,
								'current_post_ids' => ''
							))->save();
						}					
					}					
				}
				else {
					$this->set(array(
						'processing' => 0,
						'triggered' => 0,
						'queue_chunk_number' => 0,
						'current_post_ids' => ''
					))->save();
				}
											
				

				do_action( 'pmxi_after_xml_import', $this->id );			

			}			
		}
		return $this;
	}
	
	/**
	 * Perform import operation
	 * @param string $xml XML string to import
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function process($xml, $logger = NULL, $chunk = false, $is_cron = false) {
		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content
		
		$this->options += PMXI_Plugin::get_default_import_options(); // make sure all options are defined
		
		$avoid_pingbacks = PMXI_Plugin::getInstance()->getOption('pingbacks');
		$legacy_handling = PMXI_Plugin::getInstance()->getOption('legacy_special_character_handling');

		if ( $avoid_pingbacks and ! defined( 'WP_IMPORTING' ) ) define( 'WP_IMPORTING', true );		

		$postRecord = new PMXI_Post_Record();		
		
		$tmp_files = array();
		// compose records to import
		$records = array();
		$chunk_records = array();
		if ($this->options['is_import_specified']) {
			foreach (preg_split('% *, *%', $this->options['import_specified'], -1, PREG_SPLIT_NO_EMPTY) as $chank) {
				if (preg_match('%^(\d+)-(\d+)$%', $chank, $mtch)) {
					$records = array_merge($records, range(intval($mtch[1]), intval($mtch[2])));
				} else {
					$records = array_merge($records, array(intval($chank)));
				}
			}
			
			$chunk_records = $records;

			if ($this->large_import == 'Yes' and !empty($records)){

				PMXI_Plugin::$session['pmxi_import']['count'] = count($records); pmxi_session_commit();									

				$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];

				if (!in_array($chunk, $records)){
					$this->set(array(
						'skipped' => $this->skipped + 1
					))->save();
					PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;
					$logger and call_user_func($logger, __('<b>SKIPPED</b>: by specified records option', 'pmxi_plugin'));
					PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
					// Time Elapsed
					if ( ! $is_cron ){						
						$progress_msg = '<p class="import_process_bar"> Created ' . $this->created . ' / Updated ' . $this->updated . ' of '. PMXI_Plugin::$session->data['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/PMXI_Plugin::$session['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}
					PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];
					pmxi_session_commit();
					return;
				}
				else $records = array();
			}				
		}
		try { 						
			
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing titles...', 'pmxi_plugin'));
			$titles = XmlImportParser::factory($xml, $this->xpath, $this->template['title'], $file)->parse($records); $tmp_files[] = $file;
			if ($this->large_import != 'Yes') PMXI_Plugin::$session['pmxi_import']['count'] = count($titles);

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing excerpts...', 'pmxi_plugin'));			
			$post_excerpt = array();
			if (!empty($this->options['post_excerpt'])){
				$post_excerpt = XmlImportParser::factory($xml, $this->xpath, $this->options['post_excerpt'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $post_excerpt = array_fill(0, count($titles), '');
			}

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing authors...', 'pmxi_plugin'));			
			$post_author = array();
			$current_user = wp_get_current_user();

			if (!empty($this->options['author'])){
				$post_author = XmlImportParser::factory($xml, $this->xpath, $this->options['author'], $file)->parse($records); $tmp_files[] = $file;
				foreach ($post_author as $key => $author) {
					$user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);
					$post_author[$key] = (!empty($user)) ? $user->ID : $current_user->ID;
				}
			}
			else{								
				count($titles) and $post_author = array_fill(0, count($titles), $current_user->ID);
			}			

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing slugs...', 'pmxi_plugin'));			
			$post_slug = array();
			if (!empty($this->options['post_slug'])){
				$post_slug = XmlImportParser::factory($xml, $this->xpath, $this->options['post_slug'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $post_slug = array_fill(0, count($titles), '');
			}

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing contents...', 'pmxi_plugin'));			 						
			$contents = XmlImportParser::factory(
				(intval($this->template['is_keep_linebreaks']) ? $xml : preg_replace('%\r\n?|\n%', ' ', $xml)),
				$this->xpath,
				$this->template['content'],
				$file)->parse($records
			); $tmp_files[] = $file;						
										
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing dates...', 'pmxi_plugin'));
			if ('specific' == $this->options['date_type']) {
				$dates = XmlImportParser::factory($xml, $this->xpath, $this->options['date'], $file)->parse($records); $tmp_files[] = $file;
				$warned = array(); // used to prevent the same notice displaying several times
				foreach ($dates as $i => $d) {
					$time = strtotime($d);
					if (FALSE === $time) {
						in_array($d, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $d));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						$time = time();
					}
					$dates[$i] = date('Y-m-d H:i:s', $time);
				}
			} else {
				$dates_start = XmlImportParser::factory($xml, $this->xpath, $this->options['date_start'], $file)->parse($records); $tmp_files[] = $file;
				$dates_end = XmlImportParser::factory($xml, $this->xpath, $this->options['date_end'], $file)->parse($records); $tmp_files[] = $file;
				$warned = array(); // used to prevent the same notice displaying several times
				foreach ($dates_start as $i => $d) {
					$time_start = strtotime($dates_start[$i]);
					if (FALSE === $time_start) {
						in_array($dates_start[$i], $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $dates_start[$i]));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						$time_start = time();
					}
					$time_end = strtotime($dates_end[$i]);
					if (FALSE === $time_end) {
						in_array($dates_end[$i], $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $dates_end[$i]));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						$time_end = time();
					}					
					$dates[$i] = date('Y-m-d H:i:s', mt_rand($time_start, $time_end));
				}
			}
			
			$tags = array();
			if ($this->options['tags']) {
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing tags...', 'pmxi_plugin'));
				$tags_raw = XmlImportParser::factory($xml, $this->xpath, $this->options['tags'], $file)->parse($records); $tmp_files[] = $file;
				foreach ($tags_raw as $i => $t_raw) {
					$tags[$i] = '';
					if ('' != $t_raw) $tags[$i] = implode(', ', str_getcsv($t_raw, $this->options['tags_delim']));
				}
			} else {
				count($titles) and $tags = array_fill(0, count($titles), '');
			}

			// [posts categories]
			require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

			if ('post' == $this->options['type']) {				
								
				$cats = array();

				$categories_hierarchy = (!empty($this->options['categories'])) ?  json_decode($this->options['categories']) : array();

				if ((!empty($categories_hierarchy) and is_array($categories_hierarchy))){						

					($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing categories...', 'pmxi_plugin'));
					$categories = array();
					
					foreach ($categories_hierarchy as $k => $category): if ("" == $category->xpath) continue;							
						$cats_raw = XmlImportParser::factory($xml, $this->xpath, str_replace('\'','"',$category->xpath), $file)->parse($records); $tmp_files[] = $file;										
						$warned = array(); // used to prevent the same notice displaying several times
						foreach ($cats_raw as $i => $c_raw) {
							if (empty($categories_hierarchy[$k]->cat_ids[$i])) $categories_hierarchy[$k]->cat_ids[$i] = array();
							if (empty($cats[$i])) $cats[$i] = array();
							$count_cats = count($cats[$i]);
							
							$delimeted_categories = explode($this->options['categories_delim'],  $c_raw);
							
							if ('' != $c_raw) foreach (explode($this->options['categories_delim'], $c_raw) as $j => $cc) if ('' != $cc) {																								
								$cat = get_term_by('name', trim($cc), 'category') or $cat = get_term_by('slug', trim($cc), 'category') or ctype_digit($cc) and $cat = get_term_by('id', trim($cc), 'category');									
								if ( !empty($category->parent_id) ) {
									foreach ($categories_hierarchy as $key => $value){
										if ($value->item_id == $category->parent_id and !empty($value->cat_ids[$i])){												
											foreach ($value->cat_ids[$i] as $parent) {		
												if (!$j or !$this->options['categories_auto_nested']){
													$cats[$i][] = array(
														'name' => trim($cc),
														'parent' => (is_array($parent)) ? $parent['name'] : $parent, // if parent taxonomy exists then return ID else return TITLE
														'assign' => $category->assign
													);
												}
												elseif($this->options['categories_auto_nested']){
													$cats[$i][] = array(
														'name' => trim($cc),
														'parent' => (!empty($delimeted_categories[$j - 1])) ? trim($delimeted_categories[$j - 1]) : false, // if parent taxonomy exists then return ID else return TITLE
														'assign' => $category->assign
													);	
												}													
											}
										}
									}
								}
								else {
									if (!$j or !$this->options['categories_auto_nested']){
										$cats[$i][] = array(
											'name' => trim($cc),
											'parent' => false,
											'assign' => $category->assign
										);
									}
									elseif ($this->options['categories_auto_nested']){
										$cats[$i][] = array(
											'name' => trim($cc),
											'parent' => (!empty($delimeted_categories[$j - 1])) ? trim($delimeted_categories[$j - 1]) : false,
											'assign' => $category->assign
										);
									}
									
								}									
							}
							if ($count_cats < count($cats[$i])) $categories_hierarchy[$k]->cat_ids[$i][] = $cats[$i][count($cats[$i]) - 1];
						}						
					endforeach;					
				} else{
					count($titles) and $cats = array_fill(0, count($titles), '');
				}
				
			}			
			// [/posts categories]
			
			// [custom taxonomies]
			$taxonomies = array();
			$taxonomies_param = $this->options['type'].'_taxonomies';
			if ('page' == $this->options['type']) {
				$taxonomies_object_type = 'page';
			} elseif ('' != $this->options['custom_type']) {
				$taxonomies_object_type = $this->options['custom_type'];
			} else {
				$taxonomies_object_type = 'post';
			}

			if (!empty($this->options[$taxonomies_param]) and is_array($this->options[$taxonomies_param])): foreach ($this->options[$taxonomies_param] as $tx_name => $tx_template) if ('' != $tx_template) {
				$tx = get_taxonomy($tx_name);		
				$taxonomies[$tx_name] = array();
				if (!empty($tx->object_type) and in_array($taxonomies_object_type, $tx->object_type)) {
					($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, sprintf(__('Composing terms for `%s` taxonomy...', 'pmxi_plugin'), $tx->labels->name));
					$txes = array();
					
					$taxonomies_hierarchy = json_decode($tx_template);
					foreach ($taxonomies_hierarchy as $k => $taxonomy){	if ("" == $taxonomy->xpath) continue;								
						$txes_raw =  XmlImportParser::factory($xml, $this->xpath, str_replace('\'','"',$taxonomy->xpath), $file)->parse($records); $tmp_files[] = $file;						
						$warned = array();
						foreach ($txes_raw as $i => $tx_raw) {
							if (empty($taxonomies_hierarchy[$k]->txn_names[$i])) $taxonomies_hierarchy[$k]->txn_names[$i] = array();
							if (empty($taxonomies[$tx_name][$i])) $taxonomies[$tx_name][$i] = array();
							$count_cats = count($taxonomies[$tx_name][$i]);
							
							$delimeted_taxonomies = explode((!empty($taxonomy->delim)) ? $taxonomy->delim : ',', $tx_raw);

							if ('' != $tx_raw) foreach (explode((!empty($taxonomy->delim)) ? $taxonomy->delim : ',', $tx_raw) as $j => $cc) if ('' != $cc) {										
																																
								$cat = get_term_by('name', trim($cc), $tx_name) or $cat = get_term_by('slug', trim($cc), $tx_name) or ctype_digit($cc) and $cat = get_term_by('id', $cc, $tx_name);
								if (!empty($taxonomy->parent_id)) {																			
									foreach ($taxonomies_hierarchy as $key => $value){
										if ($value->item_id == $taxonomy->parent_id and !empty($value->txn_names[$i])){													
											foreach ($value->txn_names[$i] as $parent) {	
												if (!$j or !$taxonomy->auto_nested){																																																																
													$taxonomies[$tx_name][$i][] = array(
														'name' => trim($cc),
														'parent' => $parent,
														'assign' => $taxonomy->assign
													);
												}
												elseif ($taxonomy->auto_nested){
													$taxonomies[$tx_name][$i][] = array(
														'name' => trim($cc),
														'parent' => (!empty($delimeted_taxonomies[$j - 1])) ? trim($delimeted_taxonomies[$j - 1]) : false,
														'assign' => $taxonomy->assign
													);
												}																	
											}											
										}
									}
									
								}
								else {	
									if (!$j or !$taxonomy->auto_nested){
										$taxonomies[$tx_name][$i][] = array(
											'name' => trim($cc),
											'parent' => false,
											'assign' => $taxonomy->assign
										);
									}
									elseif ($taxonomy->auto_nested) {
										$taxonomies[$tx_name][$i][] = array(
											'name' => trim($cc),
											'parent' => (!empty($delimeted_taxonomies[$j - 1])) ? trim($delimeted_taxonomies[$j - 1]) : false,
											'assign' => $taxonomy->assign
										);
									}
								}								
							}
							if ($count_cats < count($taxonomies[$tx_name][$i])) $taxonomies_hierarchy[$k]->txn_names[$i][] = $taxonomies[$tx_name][$i][count($taxonomies[$tx_name][$i]) - 1];
						}
					}
				}
			}; endif;
			// [/custom taxonomies]				

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing custom parameters...', 'pmxi_plugin'));
			$meta_keys = array(); $meta_values = array();			
			
			foreach ($this->options['custom_name'] as $j => $custom_name) {
				$meta_keys[$j]   = XmlImportParser::factory($xml, $this->xpath, $custom_name, $file)->parse($records); $tmp_files[] = $file;
				$meta_values[$j] = XmlImportParser::factory($xml, $this->xpath, $this->options['custom_value'][$j], $file)->parse($records); $tmp_files[] = $file;
			}					
			// serialized custom post fields
			$serialized_meta = array();
			if (!empty($meta_keys)){
				foreach ($meta_keys as $j => $custom_name) {													
					if (!in_array($custom_name[0], array_keys($serialized_meta))){
						$serialized_meta[stripcslashes($custom_name[0])] = array($meta_values[$j]);						
					}
					else{
						$serialized_meta[stripcslashes($custom_name[0])][] = $meta_values[$j];
					}
				}
			}					

			// serialized featured images
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $uploads['error']);
				$logger and call_user_func($logger, __('<b>WARNING</b>: No featured images will be created', 'pmxi_plugin'));				
				PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;				
			} else {
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing URLs for featured images...', 'pmxi_plugin'));
				$featured_images = array();				
				if ($this->options['featured_image']) {
					// Detect if images is separated by comma
					$imgs = explode(',',$this->options['featured_image']);										
					if (!empty($imgs)){
						$parse_multiple = true;
						foreach($imgs as $img) if (!preg_match("/{.*}/", trim($img))) $parse_multiple = false;			

						if ($parse_multiple)
						{
							foreach($imgs as $img) 
							{								
								$posts_images = XmlImportParser::factory($xml, $this->xpath, trim($img), $file)->parse($records); $tmp_files[] = $file;								
								foreach($posts_images as $i => $val) $featured_images[$i][] = $val;								
							}
						}
						else
						{
							$featured_images = XmlImportParser::factory($xml, $this->xpath, $this->options['featured_image'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $featured_images = array_fill(0, count($titles), '');
				}
			}	

			// Composing images suffix
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $this->options['auto_rename_images'] and $logger and call_user_func($logger, __('Composing images suffix...', 'pmxi_plugin'));			
			$auto_rename_images = array();
			if ( $this->options['auto_rename_images'] and ! empty($this->options['auto_rename_images_suffix'])){
				$auto_rename_images = XmlImportParser::factory($xml, $this->xpath, $this->options['auto_rename_images_suffix'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $auto_rename_images = array_fill(0, count($titles), '');
			}

			// serialized attachments
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $uploads['error']);				
				$logger and call_user_func($logger, __('<b>WARNING</b>: No attachments will be created', 'pmxi_plugin')); 				
				PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session['pmxi_import']['warnings']++;
			} else {
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing URLs for attachments files...', 'pmxi_plugin'));
				$attachments = array();

				if ($this->options['attachments']) {
					// Detect if attachments is separated by comma
					$atchs = explode(',', $this->options['attachments']);					
					if (!empty($atchs)){
						$parse_multiple = true;
						foreach($atchs as $atch) if (!preg_match("/{.*}/", trim($atch))) $parse_multiple = false;			

						if ($parse_multiple)
						{							
							foreach($atchs as $atch) 
							{								
								$posts_attachments = XmlImportParser::factory($xml, $this->xpath, trim($atch), $file)->parse($records); $tmp_files[] = $file;																
								foreach($posts_attachments as $i => $val) $attachments[$i][] = $val;								
							}
						}
						else
						{
							$attachments = XmlImportParser::factory($xml, $this->xpath, $this->options['attachments'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $attachments = array_fill(0, count($titles), '');
				}
			}				

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing unique keys...', 'pmxi_plugin'));
			$unique_keys = XmlImportParser::factory($xml, $this->xpath, $this->options['unique_key'], $file)->parse($records); $tmp_files[] = $file;
			
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Processing posts...', 'pmxi_plugin'));
			
			if ('post' == $this->options['type'] and '' != $this->options['custom_type']) {
				$post_type = $this->options['custom_type'];
			} else {
				$post_type = $this->options['type'];
			}					

			// Import WooCommerce products
			if ( $post_type == "product" and class_exists('PMWI_Plugin')) {				

				$product = new PMWI_Import_Record();

				extract( $product->process($this, count($titles), $xml, $logger, $chunk) );
												
			}

			$current_post_ids = array();
			foreach ($titles as $i => $void) {							

				if (empty($titles[$i])) {
					if (class_exists('PMWI_Plugin') and !empty($single_product_parent_ID[$i])){
						$titles[$i] = $single_product_parent_ID[$i] . ' Product Variation';
					}
					else{
						$logger and call_user_func($logger, __('<b>SKIPPED</b>: by empty title', 'pmxi_plugin'));						
						PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];	
						PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						$this->set(array(							
							'skipped' => $this->skipped + 1
						))->save();				
						PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;	
						pmxi_session_commit();	
						continue;										
					}
				}
						
				$articleData = array(
					'post_type' => $post_type,
					'post_status' => $this->options['status'],
					'comment_status' => $this->options['comment_status'],
					'ping_status' => $this->options['ping_status'],
					'post_title' => ($this->template['fix_characters']) ? utf8_encode(( ! $legacy_handling) ? html_entity_decode($titles[$i]) : htmlspecialchars_decode($titles[$i])) : (($this->template['is_leave_html']) ? (( ! $legacy_handling) ? html_entity_decode($titles[$i]) : htmlspecialchars_decode($titles[$i])) : $titles[$i]),
					'post_excerpt' => ($this->template['fix_characters']) ? utf8_encode(( ! $legacy_handling) ? html_entity_decode($post_excerpt[$i]) : htmlspecialchars_decode($post_excerpt[$i])) : (($this->template['is_leave_html']) ? (( ! $legacy_handling) ? html_entity_decode($post_excerpt[$i]) : htmlspecialchars_decode($post_excerpt[$i])) : $post_excerpt[$i]),
					'post_name' => $post_slug[$i],
					'post_content' => ($this->template['fix_characters']) ? utf8_encode(( ! $legacy_handling) ? html_entity_decode($contents[$i]) : htmlspecialchars_decode($contents[$i])) : (($this->template['is_leave_html']) ? (( ! $legacy_handling) ? html_entity_decode($contents[$i]) : htmlspecialchars_decode($contents[$i])) : $contents[$i]),
					'post_date' => $dates[$i],
					'post_date_gmt' => get_gmt_from_date($dates[$i]),
					'post_author' => $post_author[$i] ,
					'tags_input' => $tags[$i]
				);				

				if ('post' != $articleData['post_type']){					
					$articleData += array(
						'menu_order' => $this->options['order'],
						'post_parent' => $this->options['parent'],
					);
				}				
				
				// Re-import Records Matching
				$post_to_update = false; $post_to_update_id = false;
				
				// if Auto Matching re-import option selected
				if ("manual" != $this->options['duplicate_matching']){
					$postRecord->clear();
					// find corresponding article among previously imported
					$postRecord->getBy(array(
						'unique_key' => $unique_keys[$i],
						'import_id' => $this->id,
					));
					if ( ! $postRecord->isEmpty() ) 
						$post_to_update = get_post($post_to_update_id = $postRecord->post_id);
											
				// if Manual Matching re-import option seleted
				} else {
					
					$postRecord->clear();
					// find corresponding article among previously imported
					$postRecord->getBy(array(
						'unique_key' => $unique_keys[$i],
						'import_id' => $this->id,
					));
					
					if ('custom field' == $this->options['duplicate_indicator']) {
						$custom_duplicate_value = XmlImportParser::factory($xml, $this->xpath, $this->options['custom_duplicate_value'], $file)->parse($records); $tmp_files[] = $file;
						$custom_duplicate_name = XmlImportParser::factory($xml, $this->xpath, $this->options['custom_duplicate_name'], $file)->parse($records); $tmp_files[] = $file;
					}
					else{
						count($titles) and $custom_duplicate_name = $custom_duplicate_value = array_fill(0, count($titles), '');					
					}

					// handle duplicates according to import settings
					if ($duplicates = $this->findDuplicates($articleData, $custom_duplicate_name[$i], $custom_duplicate_value[$i], $this->options['duplicate_indicator'])) {															
						$duplicate_id = array_shift($duplicates);
						if ($duplicate_id) {														
							$post_to_update = get_post($post_to_update_id = $duplicate_id);
						}						
					}
				}
				
				// Duplicate record is founded
				if ($post_to_update){
					// Do not update already existing records option selected
					if ("yes" == $this->options['is_keep_former_posts']) {
												
						$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
						$tmp_array[] = $post_to_update_id;
						$this->set(array(
							'current_post_ids' => json_encode($tmp_array)
						))->save();
											
						// Do not update product variations
						if ($post_type == "product" and class_exists('PMWI_Plugin')){
							
							$children = get_posts( array(
								'post_parent' 	=> $post_to_update_id,
								'posts_per_page'=> -1,
								'post_type' 	=> 'product_variation',
								'fields' 		=> 'ids',
								'post_status'	=> 'publish'
							) );

							if ( $children ) {
								foreach ( $children as $child ) {
									
									$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
									$tmp_array[] = $child;
									$this->set(array(
										'current_post_ids' => json_encode($tmp_array)
									))->save();
									
								}
							}
						}
						$this->set(array(
							'skipped' => $this->skipped + 1
						))->save();
						PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;	
						$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: Previously imported record found for `%s`', 'pmxi_plugin'), $articleData['post_title']));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						if ( ! $is_cron ){
							$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];
							$progress_msg = '<p class="import_process_bar"> ' . __('Created', 'pmxi_plugin') . ' ' . $this->created . ' / ' . __('Updated','pmxi_plugin') . ' ' . $this->updated . ' of '. PMXI_Plugin::$session->data['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/PMXI_Plugin::$session->data['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session->data['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session->data['pmxi_import']['errors'] . '</span>';
							$logger and call_user_func($logger, $progress_msg);
						}					
						PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];	
						pmxi_session_commit();	
						continue;
					}					

					$articleData['ID'] = $post_to_update_id;
					// preserve date of already existing article when duplicate is found					
					if ($this->options['is_keep_categories']) { // preserve categories and tags of already existing article if corresponding setting is specified
						$cats_list = get_the_category($articleData['ID']);
						$existing_cats = array();
						if (is_wp_error($cats_list)) {
							$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current categories for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
							PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						} else {
							$cats_new = array();
							foreach ($cats_list as $c) {
								$cats_new[] = $c->slug;
							}
							$existing_cats[$i] = $cats_new;							
						}
						
						$tags_list = get_the_tags($articleData['ID']);
						if (is_wp_error($tags_list)) {
							$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current tags for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
							PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						} else {
							$tags_new = array();
							if ($tags_list) foreach ($tags_list as $t) {
								$tags_new[] = $t->name;
							}
							$articleData['tags_input'] = implode(', ', $tags_new);
						}
						$existing_taxonomies = array();
						foreach (array_keys($taxonomies) as $tx_name) {
							$txes_list = get_the_terms($articleData['ID'], $tx_name);
							if (is_wp_error($txes_list)) {
								$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current taxonomies for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
								PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
							} else {
								$txes_new = array();
								if (!empty($txes_list)):
									foreach ($txes_list as $t) {
										$txes_new[] = $t->slug;
									}
								endif;
								$existing_taxonomies[$tx_name][$i] = $txes_new;								
							}
						}							
					}	
					else{
						foreach (array_keys($taxonomies) as $tx_name) wp_set_object_terms($articleData['ID'], NULL, $tx_name); 
					}						
					if ($this->options['is_keep_dates']) { // preserve date of already existing article when duplicate is found
						$articleData['post_date'] = $post_to_update->post_date;
						$articleData['post_date_gmt'] = $post_to_update->post_date_gmt;
					}
					if ($this->options['is_keep_status']) { // preserve status and trashed flag
						$articleData['post_status'] = $post_to_update->post_status;
					}
					if ($this->options['is_keep_content']){ 
						$articleData['post_content'] = $post_to_update->post_content;
					}
					if ($this->options['is_keep_title']){ 
						$articleData['post_title'] = $post_to_update->post_title;												
					}
					if ($this->options['is_keep_excerpt']){ 
						$articleData['post_excerpt'] = $post_to_update->post_excerpt;												
					}										
					if ($this->options['is_keep_menu_order']){ 
						$articleData['menu_order'] = $post_to_update->menu_order;
					}
					if ($this->options['is_keep_parent']){ 
						$articleData['post_parent'] = $post_to_update->post_parent;
					}
					// handle obsolete attachments (i.e. delete or keep) according to import settings
					if ( ! $this->options['is_keep_images'] and ! $this->options['is_keep_attachments_on_update'] and ! $this->options['no_create_featured_image'] ){ 						
						wp_delete_attachments($articleData['ID']);
					}

				}
				elseif ( ! $postRecord->isEmpty() ){
					
					// existing post not found though it's track was found... clear the leftover, plugin will continue to treat record as new
					$postRecord->delete();
					
				}
				
				// no new records are created. it will only update posts it finds matching duplicates for
				if ($this->options['not_create_records'] and empty($articleData['ID'])){ 
					PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
					$this->set(array(
						'skipped' => $this->skipped + 1
					))->save();
					PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;
					$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: by "Not add new records" option for `%s`', 'pmxi_plugin'), $articleData['post_title']));
					if ( ! $is_cron ){
						$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];
						$progress_msg = '<p class="import_process_bar"> '. __('Created','pmxi_plugin') . ' ' . $this->created . ' / ' . __('Updated','pmxi_plugin') . ' ' . $this->updated . ' of '. PMXI_Plugin::$session->data['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/PMXI_Plugin::$session->data['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session->data['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session->data['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}					
					PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];					
					pmxi_session_commit();	
					continue;
				}

				// cloak urls with `WP Wizard Cloak` if corresponding option is set
				if ( ! empty($this->options['is_cloak']) and class_exists('PMLC_Plugin')) {
					if (preg_match_all('%<a\s[^>]*href=(?(?=")"([^"]*)"|(?(?=\')\'([^\']*)\'|([^\s>]*)))%is', $articleData['post_content'], $matches, PREG_PATTERN_ORDER)) {
						$hrefs = array_unique(array_merge(array_filter($matches[1]), array_filter($matches[2]), array_filter($matches[3])));
						foreach ($hrefs as $url) {
							if (preg_match('%^\w+://%i', $url)) { // mask only links having protocol
								// try to find matching cloaked link among already registered ones
								$list = new PMLC_Link_List(); $linkTable = $list->getTable();
								$rule = new PMLC_Rule_Record(); $ruleTable = $rule->getTable();
								$dest = new PMLC_Destination_Record(); $destTable = $dest->getTable();
								$list->join($ruleTable, "$ruleTable.link_id = $linkTable.id")
									->join($destTable, "$destTable.rule_id = $ruleTable.id")
									->setColumns("$linkTable.*")
									->getBy(array(
										"$linkTable.destination_type =" => 'ONE_SET',
										"$linkTable.is_trashed =" => 0,
										"$linkTable.preset =" => '',
										"$linkTable.expire_on =" => '0000-00-00',
										"$ruleTable.type =" => 'ONE_SET',
										"$destTable.weight =" => 100,
										"$destTable.url LIKE" => $url,
									), NULL, 1, 1)->convertRecords();
								if ($list->count()) { // matching link found
									$link = $list[0];
								} else { // register new cloaked link
									global $wpdb;
									$slug = max(
										intval($wpdb->get_var("SELECT MAX(CONVERT(name, SIGNED)) FROM $linkTable")),
										intval($wpdb->get_var("SELECT MAX(CONVERT(slug, SIGNED)) FROM $linkTable")),
										0
									);
									$i = 0; do {
										is_int(++$slug) and $slug > 0 or $slug = 1;
										$is_slug_found = ! intval($wpdb->get_var("SELECT COUNT(*) FROM $linkTable WHERE name = '$slug' OR slug = '$slug'"));
									} while( ! $is_slug_found and $i++ < 100000);
									if ($is_slug_found) {
										$link = new PMLC_Link_Record(array(
											'name' => strval($slug),
											'slug' => strval($slug),
											'header_tracking_code' => '',
											'footer_tracking_code' => '',
											'redirect_type' => '301',
											'destination_type' => 'ONE_SET',
											'preset' => '',
											'forward_url_params' => 1,
											'no_global_tracking_code' => 0,
											'expire_on' => '0000-00-00',
											'created_on' => date('Y-m-d H:i:s'),
											'is_trashed' => 0,
										));
										$link->insert();
										$rule = new PMLC_Rule_Record(array(
											'link_id' => $link->id,
											'type' => 'ONE_SET',
											'rule' => '',
										));
										$rule->insert();
										$dest = new PMLC_Destination_Record(array(
											'rule_id' => $rule->id,
											'url' => $url,
											'weight' => 100,
										));
										$dest->insert();
									} else {
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to create cloaked link for %s', 'pmxi_plugin'), $url));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
										$link = NULL;
									}
								}
								if ($link) { // cloaked link is found or created for url
									$articleData['post_content'] = preg_replace('%' . preg_quote($url, '%') . '(?=([\s\'"]|$))%i', $link->getUrl(), $articleData['post_content']);
								}
							}
						}
					}
				}															

				// insert article being imported				
				$pid = wp_insert_post($articleData, true);				

				if (is_wp_error($pid)) {
					$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
					PMXI_Plugin::$session['pmxi_import']['errors'] = PMXI_Plugin::$session->data['pmxi_import']['errors']++;
				} else {										
					
					$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
					$tmp_array[] = $pid;
					$this->set(array(
						'current_post_ids' => json_encode($tmp_array)
					))->save();
					
					if ("manual" != $this->options['duplicate_matching'] or empty($articleData['ID'])){						
						// associate post with import
						$postRecord->isEmpty() and $postRecord->set(array(
							'post_id' => $pid,
							'import_id' => $this->id,
							'unique_key' => $unique_keys[$i],
							'product_key' => (class_exists('PMWI_Plugin')) ? $single_product_ID[$i] : ''
						))->insert();
					}
					
					// [custom fields]
					$existing_meta_keys = array();
					foreach (get_post_meta($pid, '') as $cur_meta_key => $cur_meta_val) $existing_meta_keys[] = $cur_meta_key;
					
					$keep_custom_fields_specific = (!$this->options['keep_custom_fields'] and !empty($this->options['keep_custom_fields_specific'])) ? array_map('trim', explode(',', $this->options['keep_custom_fields_specific'])) : array();

					$current_meta_keys = array();					
					$encoded_meta = array();
					foreach ($serialized_meta as $m_key => $values) { 
						
						if (($this->options['keep_custom_fields'] and in_array($m_key, $existing_meta_keys)) or (in_array($m_key, $existing_meta_keys) and !$this->options['keep_custom_fields'] and in_array($m_key, $keep_custom_fields_specific))) continue;												
						
						if (count($values) > 1)
						{
							$current_meta_keys[] = $m_key;
							$serialized_value = array();
							foreach ($values as $k => $value) { if (is_numeric($k)) $serialized_value[] = $value[$i]; else $serialized_value[$k] = $value[$i]; }							
							update_post_meta($pid, $m_key, $serialized_value);
							do_action( 'pmxi_update_post_meta', $pid, $m_key, $serialized_value); // hook that was triggered after serialized post meta data updated
						}
						else 
						{												
							if (strpos($m_key, '[') !== false){
								$m_key = str_replace(array('\'','"'), "", $m_key);								
								$meta_name = substr($m_key, 0, strpos($m_key, '['));
								$meta_key = substr($m_key, strpos($m_key, '[') + 1, -1);
								$current_meta_keys[] = $meta_name;
								if (!empty($meta_name)){ 
									if (!is_array($encoded_meta[$meta_name])) $encoded_meta[$meta_name] = array();
									$encoded_meta[$meta_name][$meta_key] = $values[0][$i];
								}								
							}	
							else{
								$current_meta_keys[] = $m_key;								
								update_post_meta($pid, $m_key, $values[0][$i]);								
							}
							do_action( 'pmxi_update_post_meta', $pid, $m_key, $values[0][$i]); // hook that was triggered after post meta data updated													
						}
					}
					
					if (!empty($encoded_meta))
						foreach ($encoded_meta as $key => $value) 
							update_post_meta($pid, $key, $value);										

					if ( ! $this->options['keep_custom_fields'] ) {
						foreach (get_post_meta($pid, '') as $cur_meta_key => $cur_meta_val) { // delete keys which are no longer correspond to import settings
							if ( ! in_array($cur_meta_key, $current_meta_keys) and  ! empty($articleData['ID']) and ! in_array($cur_meta_key, $keep_custom_fields_specific)) {
								if ($cur_meta_key != '_thumbnail_id' or !$this->options['is_keep_images'] and !$this->options['no_create_featured_image']) delete_post_meta($pid, $cur_meta_key);
							}
						}
					}
					// [/custom fields]

					// Woocommerce add-on
					if ( $post_type == "product" and class_exists('PMWI_Plugin')){

						$product->import($pid, $i, $this, $articleData, $xml, $is_cron);						

					} 

					if ('post' != $articleData['post_type'] and !empty($this->options['page_template'])) update_post_meta($pid, '_wp_page_template', $this->options['page_template']);
					
					// [featured image]
					if ( ! empty($uploads) and false === $uploads['error'] and !empty($featured_images[$i]) and (empty($articleData['ID']) or empty($this->options['is_keep_images']))) {
						
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						
						if ( ! is_array($featured_images[$i]) ) $featured_images[$i] = array($featured_images[$i]);
						$post_thumbnail = false;	
						$success_images = false;	
						$gallery_attachment_ids = array();											
						foreach ($featured_images[$i] as $featured_image)
						{							
							$imgs = str_getcsv($featured_image, $this->options['featured_delim']);						
							if (!empty($imgs)) {								
								foreach ($imgs as $img_url) { if (empty($img_url)) continue;									
									$create_image = false;
									$download_image = true;
									if (base64_decode($img_url, true) !== false){
										$img = @imagecreatefromstring(base64_decode($img_url));									    
									    if($img)
									    {	
									    	$image_filename = md5(time()) . '.jpg';
									    	$image_filepath = $uploads['path'] . '/' . $image_filename;
									    	imagejpeg($img, $image_filepath);
									    	if( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
												$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $image_filepath));
												PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
											} else {
												$create_image = true;											
											}	
									    } 
									} 
									else {

										$img_ext = pmxi_get_remote_image_ext($img_url);
										
										$image_name = (($this->options['auto_rename_images'] and "" != $auto_rename_images[$i]) ? url_title($auto_rename_images[$i] . '_' . (($this->options['images_name'] != 'auto') ? basename($img_url) : uniqid())) : (($this->options['images_name'] != 'auto') ? basename($img_url) : uniqid())) . (("" != $img_ext and $this->options['images_name'] == 'auto') ? '.'.$img_ext : '');	
										$image_filename = wp_unique_filename($uploads['path'], $image_name);
										$image_filepath = $uploads['path'] . '/' . url_title($image_filename);

										// keep existing and add newest images
										if ( $this->options['no_create_featured_image'] ){ 																																											
											$attachment_imgs = get_posts( array(
												'post_type' => 'attachment',
												'posts_per_page' => -1,
												'post_parent' => $pid,												
											) );

											if ( $attachment_imgs ) {
												foreach ( $attachment_imgs as $attachment_img ) {													
													if ($attachment_img->guid == $uploads['url'] . '/' . $image_name){
														$download_image = false;														
														$logger and call_user_func($logger, sprintf(__('<b>Image SKIPPED</b>: The image %s is always exists for the %s', 'pmxi_plugin'), basename($attachment_img->guid), $articleData['post_title']));							
													}
												}
												
											}
										}

										if ($download_image){

											$img_url = str_replace(" ", "%20", trim($img_url));

											if ( ! get_file_curl($img_url, $image_filepath) and ! @file_put_contents($image_filepath, @file_get_contents($img_url))) {
												$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: File %s cannot be saved locally as %s', 'pmxi_plugin'), $img_url, $image_filepath));
												PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
												unlink($image_filepath); // delete file since failed upload may result in empty file created										
											} elseif( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
												$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $img_url));
												PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
											} else {
												$create_image = true;											
											}
										}	
									}									

									if ($create_image){
										$attachment = array(
											'post_mime_type' => image_type_to_mime_type($image_info[2]),
											'guid' => $uploads['url'] . '/' . $image_filename,
											'post_title' => $image_filename,
											'post_content' => '',
										);
										if (($image_meta = wp_read_image_metadata($image_filepath))) {
											if (trim($image_meta['title']) && ! is_numeric(sanitize_title($image_meta['title'])))
												$attachment['post_title'] = $image_meta['title'];
											if (trim($image_meta['caption']))
												$attachment['post_content'] = $image_meta['caption'];
										}
										$attid = wp_insert_attachment($attachment, $image_filepath, $pid);
										if (is_wp_error($attid)) {
											$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
											PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
										} else {
											// you must first include the image.php file
											// for the function wp_generate_attachment_metadata() to work
											require_once(ABSPATH . 'wp-admin/includes/image.php');
											wp_update_attachment_metadata($attid, wp_generate_attachment_metadata($attid, $image_filepath));																							
											
											do_action( 'pmxi_gallery_image', $pid, $attid, $image_filepath); 

											$success_images = true;
											if ( ! $post_thumbnail ) { 												
												if ( ! $this->options['no_create_featured_image'] or ! has_post_thumbnail($pid)){ 
													set_post_thumbnail($pid, $attid); 
													$post_thumbnail = true; 
												}
												else $gallery_attachment_ids[] = $attid;
											}
											else $gallery_attachment_ids[] = $attid;												
										}
									}																	
								}									
							}
						}						
						// Set product gallery images
						if ( $post_type == "product" and !empty($gallery_attachment_ids))
							update_post_meta($pid, '_product_image_gallery', implode(',', $gallery_attachment_ids));
						// Create entry as Draft if no images are downloaded successfully
						if ( ! $success_images and "yes" == $this->options['create_draft'] ) wp_update_post(array('ID' => $pid, 'post_status' => 'draft'));						
					}
					// [/featured image]

					// [attachments]
					if ( ! empty($uploads) and false === $uploads['error'] and !empty($attachments[$i])) {

						// you must first include the image.php file
						// for the function wp_generate_attachment_metadata() to work
						require_once(ABSPATH . 'wp-admin/includes/image.php');

						if ( ! is_array($attachments[$i]) ) $attachments[$i] = array($attachments[$i]);

						foreach ($attachments[$i] as $attachment) { if ("" == $attachment) continue;
							
							$atchs = str_getcsv($attachment, $this->options['atch_delim']);

							if (!empty($atchs)) {
								foreach ($atchs as $atch_url) {	if (empty($atch_url)) continue;									

									$attachment_filename = wp_unique_filename($uploads['path'], basename(parse_url(trim($atch_url), PHP_URL_PATH)));										
									$attachment_filepath = $uploads['path'] . '/' . url_title($attachment_filename);
																		
									if ( ! get_file_curl(trim($atch_url), $attachment_filepath) and ! @file_put_contents($attachment_filepath, @file_get_contents(trim($atch_url)))) {												
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Attachment file %s cannot be saved locally as %s', 'pmxi_plugin'), trim($atch_url), $attachment_filepath));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
										unlink($attachment_filepath); // delete file since failed upload may result in empty file created												
									} elseif( ! $wp_filetype = wp_check_filetype(basename($attachment_filename), null )) {
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Can\'t detect attachment file type %s', 'pmxi_plugin'), trim($atch_url)));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
									} else {

										$attachment_data = array(
										    'guid' => $uploads['baseurl'] . '/' . _wp_relative_upload_path( $attachment_filepath ), 
										    'post_mime_type' => $wp_filetype['type'],
										    'post_title' => preg_replace('/\.[^.]+$/', '', basename($attachment_filepath)),
										    'post_content' => '',
										    'post_status' => 'inherit'
										);
										$attach_id = wp_insert_attachment( $attachment_data, $attachment_filepath, $pid );												

										if (is_wp_error($attach_id)) {
											$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
											PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
										} else {
											wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $attachment_filepath));											
										}										
									}																
								}
							}
						}
					}
					// [/attachments]
					
					// [custom taxonomies]
					if (!empty($taxonomies)){					
						foreach ($taxonomies as $tx_name => $txes) {
							
							if ( empty($articleData['ID']) or !$this->options['is_keep_categories'] or ( $this->options['is_keep_categories'] and $this->options['is_add_newest_categories'] ) ){

								$assign_taxes = array();

								if ($this->options['is_add_newest_categories'] and !empty($existing_taxonomies[$tx_name][$i])){
									$assign_taxes = $existing_taxonomies[$tx_name][$i];	
									unset($existing_taxonomies[$tx_name][$i]);
								}

								// create term if not exists						
								if (!empty($txes[$i])): 
									foreach ($txes[$i] as $key => $single_tax) {	
										if (is_array($single_tax)){								

											$parent_id = (!empty($single_tax['parent'])) ? $this->recursion_taxes($single_tax['parent'], $tx_name, $txes[$i], $key) : '';

											$term = term_exists( trim($single_tax['name']), $tx_name, $parent_id );																

											if ( empty($term) and !is_wp_error($term) ){																		
												$term_attr = array('parent'=> (!empty($parent_id)) ? $parent_id : 0);									
												$term = wp_insert_term(
													$single_tax['name'], // the term 
												  	$tx_name, // the taxonomy
												  	$term_attr
												);									
											}
											
											if ( is_wp_error($term) ){									
												$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: `%s`', 'pmxi_plugin'), $term->get_error_message()));
												PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
											}
											elseif (!empty($term)) {
												$cat_id = $term['term_id'];
												if ($cat_id and $single_tax['assign']) 
												{
													$term = get_term_by('id', $cat_id, $tx_name);
													if (!in_array($term->slug, $assign_taxes)) $assign_taxes[] = $term->slug;		
												}									
											}									
										}
									}				
								endif;										
								if (!empty($assign_taxes)){
									// associate taxes with post
									$term_ids = wp_set_object_terms($pid, $assign_taxes, $tx_name);
									if (is_wp_error($term_ids)) {
										$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$term_ids->get_error_message());
										PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
									}
								}
							}
						}
						if (!empty($existing_taxonomies) and $this->options['is_keep_categories'] and $this->options['is_add_newest_categories']) {
							foreach ($existing_taxonomies as $tx_name => $txes) {
								if (!empty($txes[$i])){
									$term_ids = wp_set_object_terms($pid, $txes[$i], $tx_name);
									if (is_wp_error($term_ids)) {
										$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$term_ids->get_error_message());
										PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
									}
								}
							}
						}
					}					
					// [/custom taxonomies]
					
					// [categories]
					if (!empty($cats[$i])) {
					
						if ( empty($articleData['ID']) or !$this->options['is_keep_categories'] or ( $this->options['is_keep_categories'] and $this->options['is_add_newest_categories'] ) ){

							wp_set_object_terms( $pid, NULL, 'category' );

							$assign_cats = array();

							if ($this->options['is_add_newest_categories'] and !empty($existing_cats[$i])){
								$assign_cats = $existing_cats[$i];	
								unset($existing_cats[$i]);
							}

							// create categories if it's doesn't exists						
							foreach ($cats[$i] as $key => $single_cat) {												

								if (is_array($single_cat)){								

									$parent_id = (!empty($single_cat['parent'])) ? $this->recursion_taxes($single_cat['parent'], 'category', $cats[$i], $key) : '';

									$term = term_exists( trim($single_cat['name']), 'category', $parent_id );																
									
									if ( empty($term) and !is_wp_error($term) ){																		
										$term_attr = array('parent'=> (!empty($parent_id)) ? $parent_id : 0);									
										$term = wp_insert_term(
											$single_cat['name'], // the term 
										  	'category', // the taxonomy
										  	$term_attr
										);									
									}
									
									if ( is_wp_error($term) ){									
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: `%s`', 'pmxi_plugin'), $term->get_error_message()));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
									}
									elseif ( ! empty($term) ) {
										$cat_id = $term['term_id'];
										if ($cat_id and $single_cat['assign']) 
										{
											$term = get_term_by('id', $cat_id, 'category');
											if ( ! in_array($term->slug, $assign_cats)) $assign_cats[] = $term->slug;		
										}									
									}									
								}
							}	

							// associate categories with post
							$cats_ids = wp_set_object_terms($pid, $assign_cats, 'category');
							if (is_wp_error($cats_ids)) {
								$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$cats_ids->get_error_message());
								PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
							}
						}
					}
					
					if (!empty($existing_cats[$i]) and $this->options['is_keep_categories']) {												
						$cats_ids = wp_set_object_terms($pid, $existing_cats[$i], 'category');
						
						if (is_wp_error($cats_ids)) {
							$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$cats_ids->get_error_message());
							PMXI_Plugin::$session['pmxi_import']['warnings'] = PMXI_Plugin::$session->data['pmxi_import']['warnings']++;
						}
					}
					// [/categories]

					if (empty($articleData['ID'])) {
						PMXI_Plugin::$session['pmxi_import']['created_records'] = $this->created + 1;																								
						$logger and call_user_func($logger, sprintf(__('`%s` post created successfully', 'pmxi_plugin'), $articleData['post_title']));
					} else {
						PMXI_Plugin::$session['pmxi_import']['updated_records'] = $this->updated + 1;									
						$logger and call_user_func($logger, sprintf(__('`%s` post updated successfully', 'pmxi_plugin'), $articleData['post_title']));
					}
					
					do_action( 'pmxi_saved_post', $pid); // hook that was triggered immediately after post saved
					
					if ($this->large_import == 'Yes' and $chunk){
						$this->set(array(
							'imported' => $this->imported + 1,	
							'created'  => (empty($articleData['ID'])) ? $this->created + 1 : $this->created,
							'updated'  => (empty($articleData['ID'])) ? $this->updated : $this->updated + 1			
						))->save();
						PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];	
					}	
					
					$records_count = 0;

					// Time Elapsed
					if ( ! $is_cron){																								
						
						$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];

						$progress_msg = '<p class="import_process_bar"> '.__('Created','pmxi_plugin'). ' ' . $this->created . ' / '.__('Updated','pmxi_plugin') . ' ' . $this->updated . ' of '. PMXI_Plugin::$session->data['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/PMXI_Plugin::$session->data['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session->data['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session->data['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}
					
				}											

				wp_cache_flush();
			}			
			
			pmxi_session_commit();	

			$is_import_complete = ($records_count == PMXI_Plugin::$session->data['pmxi_import']['count']);

			if ( ! $is_cron and $is_import_complete and ! empty($this->options['is_delete_missing'])) { // delete posts which are not in current import set

				$logger and call_user_func($logger, 'Removing previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();				
				$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	

				$missing_ids = array();
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
					empty($this->options['is_keep_attachments']) and wp_delete_attachments($missingPost['post_id']);
					$missing_ids[] = $missingPost['post_id'];
					
					$sql = "delete a
					FROM ".PMXI_Plugin::getInstance()->getTablePrefix()."posts a
					WHERE a.id=%d";
					
					$this->wpdb->query( 
						$this->wpdb->prepare($sql, $missingPost['id'])
					);					
				}

				if (!empty($missing_ids)){
					$sql = "delete a,b,c
					FROM ".$this->wpdb->posts." a
					LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
					LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )				
					WHERE a.ID IN (".implode(',', $missing_ids).");";

					$this->wpdb->query( 
						$this->wpdb->prepare($sql, '')
					);
				}								

			}

			// Set out of stock status for missing records [Woocommerce add-on option]
			if ( ! $is_cron and $is_import_complete and empty($this->options['is_delete_missing']) and $post_type == "product" and class_exists('PMWI_Plugin') and !empty($this->options['missing_records_stock_status'])) {

				$logger and call_user_func($logger, 'Update stock status previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();				
				$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
					update_post_meta( $missingPost['post_id'], '_stock_status', 'outofstock' );
					update_post_meta( $missingPost['post_id'], '_stock', 0 );
				}

			}

			if ( ! $is_cron and $is_import_complete and empty($this->options['is_delete_missing']) and $this->options['is_update_missing_cf'] ) {
				
				$logger and call_user_func($logger, 'Update custom fields previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();											
				$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
					update_post_meta( $missingPost['post_id'], $this->options['update_missing_cf_name'], $this->options['update_missing_cf_value'] );
				}

			}
			
		} catch (XmlImportException $e) {
			$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $e->getMessage());
			PMXI_Plugin::$session['pmxi_import']['errors'] = PMXI_Plugin::$session->data['pmxi_import']['errors']++;	
		}		

		$this->set('registered_on', date('Y-m-d H:i:s'))->save(); // specify execution is successful
		
		!$is_cron and $is_import_complete and $logger and call_user_func($logger, __('Cleaning temporary data...', 'pmxi_plugin'));
		foreach ($tmp_files as $file) { // remove all temporary files created
			unlink($file);
		}
		
		if (($is_cron or $is_import_complete) and $this->options['is_delete_source']) {
			$logger and call_user_func($logger, __('Deleting source XML file...', 'pmxi_plugin'));
			if ( ! @unlink($this->path)) {
				$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to remove %s', 'pmxi_plugin'), $this->path));
			}
		}
		if ( ! $is_cron and $is_import_complete ){
			$this->set(array(
				'processing' => 0,
				'triggered' => 0,
				'queue_chunk_number' => 0,
				'current_post_ids' => ''
			))->save();
			$logger and call_user_func($logger, 'Done');			
		}
		
		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure
		
		return $this;
	}

	public function recursion_taxes($parent, $tx_name, $txes, $key){		
		if (is_array($parent)){
			$parent['name'] = sanitize_text_field($parent['name']);
			if (empty($parent['parent'])){
				$term = term_exists( htmlspecialchars($parent['name']), $tx_name);
				if ( empty($term) and !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent['name'], // the term 
					  	$tx_name // the taxonomy			  	
					);
				}
				return ( ! is_wp_error($term)) ? $term['term_id'] : '';
			}
			else{
				$parent_id = $this->recursion_taxes($parent['parent'], $tx_name, $txes, $key);
				$term = term_exists( htmlspecialchars($parent['name']), $tx_name, $parent_id);
				if ( empty($term) and  !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name, // the taxonomy			  	
					  	array('parent'=> (!empty($parent_id)) ? $parent_id : 0)
					);
				}
				return ( ! is_wp_error($term)) ? $term['term_id'] : '';
			}			
		}
		else{	

			if ( !empty($txes[$key - 1]) and !empty($txes[$key - 1]['parent'])) {				
				$parent_id = $this->recursion_taxes($txes[$key - 1]['parent'], $tx_name, $txes, $key - 1);

				$term = term_exists( htmlspecialchars($parent), $tx_name, $parent_id);
				if ( empty($term) and !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name, // the taxonomy			  	
					  	array('parent'=> (!empty($parent_id)) ? $parent_id : 0)
					);
				}
				return ( ! is_wp_error($term)) ? $term['term_id'] : '';
			}
			else{
				$term = term_exists( htmlspecialchars($parent), $tx_name);
				if ( empty($term) and !is_wp_error($term) ){					
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name // the taxonomy			  	
					);
				}
				
				return ( ! is_wp_error($term)) ? $term['term_id'] : '';				 
			}
		}
	}
	
	public function _filter_has_cap_unfiltered_html($caps)
	{
		$caps['unfiltered_html'] = true;
		return $caps;
	}
	
	/**
	 * Find duplicates according to settings
	 */
	public function findDuplicates($articleData, $custom_duplicate_name = '', $custom_duplicate_value = '', $duplicate_indicator = 'title')
	{		
		if ('custom field' == $duplicate_indicator){
			$duplicate_ids = array();
			$args = array(
				'post_type' => $articleData['post_type'],
				'meta_query' => array(
					array(
						'key' => $custom_duplicate_name,
						'value' => $custom_duplicate_value,						
					)
				)
			);			
			$query = new WP_Query( $args );
			
			if ( $query->have_posts() ) $duplicate_ids[] = $query->post->ID;

			wp_reset_postdata();

			return $duplicate_ids;
		}
		else{
			$field = 'post_' . $duplicate_indicator; // post_title or post_content
			return $this->wpdb->get_col($this->wpdb->prepare("
				SELECT ID FROM " . $this->wpdb->posts . "
				WHERE
					post_type = %s
					AND ID != %s
					AND REPLACE(REPLACE(REPLACE($field, ' ', ''), '\\t', ''), '\\n', '') = %s
				",
				$articleData['post_type'],
				isset($articleData['ID']) ? $articleData['ID'] : 0,
				preg_replace('%[ \\t\\n]%', '', $articleData[$field])
			));
		}
	}
	
	/**
	 * Clear associations with posts
	 * @param bool[optional] $keepPosts When set to false associated wordpress posts will be deleted as well
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function deletePosts($keepPosts = TRUE) {
		$post = new PMXI_Post_List();
		if ($keepPosts) {
			$this->wpdb->query($this->wpdb->prepare('DELETE FROM ' . $post->getTable() . ' WHERE import_id = %s', $this->id));
		} else {
			$ids = array();
			foreach ($post->getBy('import_id', $this->id)->convertRecords() as $p) {
				empty($this->options['is_keep_attachments']) and wp_delete_attachments($p->post_id);
				$ids[] = $p->post_id;								
			}
			if (!empty($ids)){				

				$sql = "delete a,b,c
				FROM ".$this->wpdb->posts." a
				LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
				LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )				
				WHERE a.ID IN (".implode(',', $ids).");";

				$this->wpdb->query( 
					$this->wpdb->prepare($sql, '')
				);
			}
		}
		return $this;
	}
	/**
	 * Delete associated files
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function deleteFiles() {
		$fileList = new PMXI_File_List();
		foreach($fileList->getBy('import_id', $this->id)->convertRecords() as $f) {
			$f->delete();
		}
		return $this;
	}
	
	/**
	 * @see parent::delete()
	 * @param bool[optional] $keepPosts When set to false associated wordpress posts will be deleted as well
	 */
	public function delete($keepPosts = TRUE) {
		$this->deletePosts($keepPosts)->deleteFiles();
		
		return parent::delete();
	}
	
}

/**
 * Cron schedule parser 
 */
class _PMXI_Import_Record_Cron_Parser
{
    /**
     * @var array Cron parts
     */
    private $_cronParts;

    /**
     * Constructor
     *
     * @param string $schedule Cron schedule string (e.g. '8 * * * *').  The 
     *      schedule can handle ranges (10-12) and intervals
     *      (*\/10 [remove the backslash]).  Schedule parts should map to
     *      minute [0-59], hour [0-23], day of month, month [1-12], day of week [1-7]
     *
     * @throws InvalidArgumentException if $schedule is not a valid cron schedule
     */
    public function __construct($schedule)
    {
        $this->_cronParts = explode(' ', $schedule);
        if (count($this->_cronParts) != 5) {
            throw new Exception($schedule . ' is not a valid cron schedule string');
        }
    }

    /**
     * Check if a date/time unit value satisfies a crontab unit
     *
     * @param DateTime $nextRun Current next run date
     * @param string $unit Date/time unit type (e.g. Y, m, d, H, i)
     * @param string $schedule Cron schedule variable
     *
     * @return bool Returns TRUE if the unit satisfies the constraint
     */
    public function unitSatisfiesCron(DateTime $nextRun, $unit, $schedule)
    {
        $unitValue = (int)$nextRun->format($unit);

        if ($schedule == '*') {
            return true;
        } if (strpos($schedule, '-')) {
            list($first, $last) = explode('-', $schedule);
            return $unitValue >= $first && $unitValue <= $last;
        } else if (strpos($schedule, '*/') !== false) {
            list($delimiter, $interval) = explode('*/', $schedule);
            return $unitValue % (int)$interval == 0;
        } else {
            return $unitValue == (int)$schedule;
        }
    }

    /**
     * Get the date in which the cron will run next
     *
     * @param string|DateTime (optional) $fromTime Set the relative start time
     *
     * @return DateTime
     */
    public function getNextRunDate($fromTime = 'now')
    {
        $nextRun = ($fromTime instanceof DateTime) ? $fromTime : new DateTime($fromTime);
        $nextRun->setTime($nextRun->format('H'), $nextRun->format('i'), 0);
        $nextRun->modify('+1 minute'); // make sure we don't return the very date is submitted to the function
        $nextRunLimit = clone $nextRun; $nextRunLimit->modify('+1 year');
        
        while ($nextRun < $nextRunLimit) { // Set a hard limit to bail on an impossible date

            // Adjust the month until it matches.  Reset day to 1 and reset time.
            if ( ! $this->unitSatisfiesCron($nextRun, 'm', $this->getSchedule('month'))) {
                $nextRun->modify('+1 month');
                $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), 1);
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of the month by incrementing the day until it matches. Reset time.
            if ( ! $this->unitSatisfiesCron($nextRun, 'd', $this->getSchedule('day_of_month'))) {
                $nextRun->modify('+1 day');
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of week by incrementing the day until it matches.  Resest time.
            if ( ! $this->unitSatisfiesCron($nextRun, 'N', $this->getSchedule('day_of_week'))) {
                $nextRun->modify('+1 day');
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the hour until it matches the set hour.  Set seconds and minutes to 0
            if ( ! $this->unitSatisfiesCron($nextRun, 'H', $this->getSchedule('hour'))) {
                $nextRun->modify('+1 hour');
                $nextRun->setTime($nextRun->format('H'), 0, 0);
                continue;
            }

            // Adjust the minutes until it matches a set minute
            if ( ! $this->unitSatisfiesCron($nextRun, 'i', $this->getSchedule('minute'))) {
                $nextRun->modify('+1 minute');
                continue;
            }

            break;
        }

        return $nextRun;
    }

    /**
     * Get all or part of the cron schedule string
     *
     * @param string $part Specify the part to retrieve or NULL to get the full
     *      cron schedule string.  $part can be the PHP date() part of a date
     *      formatted string or one of the following values:
     *      NULL, 'minute', 'hour', 'month', 'day_of_week', 'day_of_month'
     *
     * @return string
     */
    public function getSchedule($part = null)
    {
        switch ($part) {
            case 'minute': case 'i':
                return $this->_cronParts[0];
            case 'hour': case 'H':
                return $this->_cronParts[1];
            case 'day_of_month': case 'd':
                return $this->_cronParts[2];
            case 'month': case 'm':
                return $this->_cronParts[3];
            case 'day_of_week': case 'N':
                return $this->_cronParts[4];
            default:
                return implode(' ', $this->_cronParts);
        }
    }

    /**
     * Deterime if the cron is due to run based on the current time, last run
     * time, and the next run time.
     * 
     * If the relative next run time based on the last run time is not equal to 
     * the next suggested run time based on the current time, then the cron 
     * needs to run.
     *
     * @param string|DateTime $lastRun (optional) Date the cron was last run.
     *
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     */
    public function isDue($lastRun = 'now')
    {
        return $this->getNextRunDate($lastRun) < $this->getNextRunDate();
    }
}
