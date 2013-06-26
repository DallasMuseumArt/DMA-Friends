<?php 
/**
 * Manage Imports
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXI_Admin_Manage extends PMXI_Controller_Admin {
	
	public function init() {
		parent::init();
		
		if ('update' == PMXI_Plugin::getInstance()->getAdminCurrentScreen()->action) {
			$this->isInline = true;
			if ( ! session_id()) session_start(); // prevent session initialization throw a notification in inline mode of delegated plugin 
		}
	}
	
	/**
	 * Previous Imports list
	 */
	public function index() {
		
		$get = $this->input->get(array(
			's' => '',
			'order_by' => 'registered_on',
			'order' => 'DESC',
			'pagenum' => 1,
			'perPage' => 10,
		));
		$get['pagenum'] = absint($get['pagenum']);
		extract($get);
		$this->data += $get;
		
		$list = new PMXI_Import_List();
		$post = new PMXI_Post_Record();
		$by = NULL;
		if ('' != $s) {
			$like = '%' . preg_replace('%\s+%', '%', preg_replace('/[%?]/', '\\\\$0', $s)) . '%';
			$by[] = array(array('name LIKE' => $like, 'type LIKE' => $like, 'path LIKE' => $like), 'OR');
		}
		
		$this->data['list'] = $list->join($post->getTable(), $list->getTable() . '.id = ' . $post->getTable() . '.import_id', 'LEFT')
			->setColumns(
				$list->getTable() . '.*',
				'COUNT(' . $post->getTable() . '.post_id' . ') AS post_count'
			)
			->getBy($by, "$order_by $order", $pagenum, $perPage, $list->getTable() . '.id');
			
		$this->data['page_links'] = paginate_links(array(
			'base' => add_query_arg('pagenum', '%#%', $this->baseUrl),
			'format' => '',
			'prev_text' => __('&laquo;', 'pmxi_plugin'),
			'next_text' => __('&raquo;', 'pmxi_plugin'),
			'total' => ceil($list->total() / $perPage),
			'current' => $pagenum,
		));
		
		unset($_SESSION['pmxi_import']);

		$this->render();
	}
	
	/**
	 * Edit Template
	 */
	public function edit() {
		// deligate operation to other controller
		$controller = new PMXI_Admin_Import();
		$controller->set('isTemplateEdit', true);
		$controller->template();
	}
	
	/**
	 * Edit Options
	 */
	public function options() {
		// deligate operation to other controller
		$controller = new PMXI_Admin_Import();
		$controller->set('isTemplateEdit', true);
		$controller->options();
	}

	/**
	 * Cron Scheduling
	 */
	public function scheduling() {
		$this->data['id'] = $id = $this->input->get('id');
		$this->data['cron_job_key'] = PMXI_Plugin::getInstance()->getOption('cron_job_key');
		$this->data['item'] = $item = new PMXI_Import_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}

		$this->render();
	}
	
	/**
	 * Reimport
	 */
	public function update() {
		$id = $this->input->get('id');
		$action_type = $this->input->get('type');
		$pointer = 0;

		$this->data['item'] = $item = new PMXI_Import_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}				
		
		unset($_SESSION['pmxi_import']);

		if ($this->input->post('is_confirmed')) {

			check_admin_referer('update-import', '_wpnonce_update-import');		
		
			$uploads = wp_upload_dir();

			if (in_array($item->type, array('ftp')) and empty($_SESSION['pmxi_import']['chunk_number'])) {
				
				// path to remote file
				$remote_file = $item->path;
								
				// set up basic connection
				$ftp_url = $item->path;
				$parsed_url = parse_url($ftp_url);
				$ftp_server = $parsed_url['host'] ;
				$conn_id = ftp_connect( $ftp_server );
				$is_ftp_ok = TRUE;				

				// login with username and password
				$ftp_user_name = $parsed_url['user'];
				$ftp_user_pass = urldecode($parsed_url['pass']);

				// hide warning message
				echo '<span style="display:none">';
				if ( !ftp_login($conn_id, $ftp_user_name, $ftp_user_pass) ){
					$this->errors->add('form-validation', __('Login authentication failed', 'pmxi_plugin'));
					$is_ftp_ok = false;
				}
				echo '</span>';

				if ( $is_ftp_ok ){						

					$files = PMXI_Helper::safe_glob($item->path, PMXI_Helper::GLOB_NODIR | PMXI_Helper::GLOB_PATH);
					$local_paths = array();

					if ($files) {
						foreach ($files as $singlePath) {

							$parsed_url = parse_url($singlePath);						

							$local_file = $uploads['path']  .'/'. basename($parsed_url['path']);										
							
							$c = curl_init($singlePath);
							// $local is the location to store file on local machine
							$fh = fopen($local_file, 'w') or $this->errors->add('form-validation', __('There was a problem while downloading ' . $singlePath . ' to ' . $local_file, 'pmxi_plugin'));
							curl_setopt($c, CURLOPT_FILE, $fh);
							curl_exec($c);
							curl_close($c);							

							$local_paths[] = $local_file;
						}
						
						foreach ($local_paths as $key => $path) {											

							if ( preg_match('%\W(gz)$%i', $path)){
								
								$fileInfo = pmxi_gzfile_get_contents($path);

								$local_paths[$key] = $fileInfo['localPath'];
							}
							elseif ( preg_match('%\W(zip)$%i', $path) ){

								include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

								$archive = new PclZip($path);
							    if (($v_result_list = $archive->extract(PCLZIP_OPT_PATH, $uploads['path'], PCLZIP_OPT_REPLACE_NEWER)) == 0) {
							    	$this->errors->add('form-validation', 'Failed to open uploaded ZIP archive : '.$archive->errorInfo(true));			    	
							   	}
								else {
									
									$localPath = '';

									if (!empty($v_result_list)){
										foreach ($v_result_list as $unzipped_file) {
											if ($unzipped_file['status'] == 'ok') $localPath = $unzipped_file['filename'];
										}
									}
							    	if($uploads['error']){
										 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
									}

									if(empty($localPath)){						
										$zip = zip_open(trim($path));
										if (is_resource($zip)) {														
											while ($zip_entry = zip_read($zip)) {
												$localPath = zip_entry_name($zip_entry);												
											    $fp = fopen($uploads['path']."/".$localPath, "w");
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

									$local_paths[$key] = $localPath;												
								}
							}

							if ( preg_match('%\W(csv)$%i', trim($local_paths[$key])) or (!empty($fileInfo) and $fileInfo['type'] == 'csv') ){																																					
								include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');		
								$csv = new PMXI_CsvParser($local_paths[$key], true); // create chunks
								$local_paths[$key] = $csv->xml_path;																	
							}
							
						}

					} else $filePath = '';																								
					
					// close the connection and the file handler
					ftp_close($conn_id);										

				}		
				
			} 

			if ($item->large_import == 'No' or ($item->large_import == 'Yes' and empty($_SESSION['pmxi_import']['chunk_number']))) {			
				
				if ($item->type == 'url'){

					if (preg_match('%\W(zip)$%i', trim($item->path))) {							
					
						$tmpname = $uploads['path'] . '/' . wp_unique_filename($uploads['path'], basename($item->path));

						@copy($item->path, $tmpname);				
						
						if (!file_exists($tmpname)) {
							
							get_file_curl($item->path, $tmpname);

						    if (!file_exists($tmpname)) $this->errors->add('form-validation', __('Failed upload ZIP archive', 'pmxi_plugin'));
						
						}						

						include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

						$archive = new PclZip($tmpname);
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
								$zip = zip_open(trim($tmpname));
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

							if (preg_match('%\W(csv)$%i', trim($filePath))){
															
								if (empty($item->large_import) or $item->large_import == 'No') {
									$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
								}
								else{										
									include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
									$csv = new PMXI_CsvParser($filePath, true); // create chunks
									$filePath = $csv->xml_path;									   					  
								}	

							}							
						}
						
					} elseif (preg_match('%\W(csv)$%i', trim($item->path))) {
														
						// copy remote file in binary mode
						$filePath = pmxi_copy_url_file($item->path);									

						if (empty($item->large_import) or $item->large_import == 'No') {																				
							$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																					
						}
						else {					
							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
							$csv = new PMXI_CsvParser($filePath, true); // create chunks
							$filePath = $csv->xml_path;						
						}

					} else {
						
						$fileInfo = (preg_match('%\W(gz)$%i', trim($item->path))) ? pmxi_gzfile_get_contents($item->path) : pmxi_copy_url_file($item->path, true);
						$filePath = $fileInfo['localPath'];														

						// detect CSV or XML 
						if ( $fileInfo['type'] == 'csv') { // it is CSV file									
							if (empty($item->large_import) or $item->large_import == 'No') {																
								$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																						
							}
							else{																
								include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
								$csv = new PMXI_CsvParser($filePath, true); // create chunks
								$filePath = $csv->xml_path;												
							}
						}
					}

				} elseif ( ! in_array($item->type, array('ftp')) ) { // if import type NOT URL

					if (preg_match('%\W(zip)$%i', trim(basename($item->path)))) {
						
						include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

						$archive = new PclZip(trim($item->path));
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
								$zip = zip_open(trim($item->path));
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

							if (preg_match('%\W(csv)$%i', trim($filePath))){ // If CSV file found in archieve						

								if($uploads['error']){
									 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
								}																		
								if (empty($item->large_import) or $item->large_import == 'No') {
									$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
								}
								else{										
									include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
									$csv = new PMXI_CsvParser($filePath, true); // create chunks
									$filePath = $csv->xml_path;								
								}
							}							
						}					

					} elseif ( preg_match('%\W(csv)$%i', trim($item->path))) { // If CSV file uploaded										
						if($uploads['error']){
							 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
						}									
		    			$filePath = $post['filepath'];					
						if (empty($item->large_import) or $item->large_import == 'No') {
							$filePath = PMXI_Plugin::csv_to_xml($item->path);					
						} else{										
							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
							$csv = new PMXI_CsvParser($item->path, true);					
							$filePath = $csv->xml_path;						
						}					   					
					} elseif(preg_match('%\W(gz)$%i', trim($item->path))){ // If gz file uploaded
						$fileInfo = pmxi_gzfile_get_contents($item->path);
						$filePath = $fileInfo['localPath'];				
						// detect CSV or XML 
						if ( $fileInfo['type'] == 'csv') { // it is CSV file									
							if (empty($item->large_import) or $item->large_import == 'No') {																
								$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																						
							}
							else{																
								include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
								$csv = new PMXI_CsvParser($filePath, true); // create chunks
								$filePath = $csv->xml_path;												
							}
						}
					} else { // If XML file uploaded					
						
						$filePath = $item->path;
						
					}

				}

				if (empty($xml)){
					
					if ($item->large_import == 'Yes'){
						
						set_time_limit(0);			

						$chunks = 0;
						
						$chunk_path = '';

						$local_paths = !empty($local_paths) ? $local_paths : array($filePath);				

						foreach ($local_paths as $key => $path) {

							$file = new PMXI_Chunk($path, array('element' => $item->root_element, 'path' => $uploads['path']));					
						    						    
						    while ($xml = $file->read()) {					      						    					    					    	
						    	
						    	if (!empty($xml))
						      	{				
						      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      		
							      					      		
							      	$dom = new DOMDocument('1.0', 'UTF-8');															
									$old = libxml_use_internal_errors(true);
									$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
									libxml_use_internal_errors($old);
									$xpath = new DOMXPath($dom);
									if (($this->data['elements'] = $elements = @$xpath->query($item->xpath)) and $elements->length) { 
										if ( !$chunks) {
											$chunk_path = $uploads['path'] .'/'. wp_unique_filename($uploads['path'], "chunk_".basename($path));
											if (file_exists($chunk_path)) unlink($chunk_path);				  	
										    file_put_contents($chunk_path, '<?xml version="1.0" encoding="utf-8"?>'."\n".$xml);
										    chmod($chunk_path, 0755);
										}
										$chunks++; 
										
										if (!empty($action_type) and $action_type == 'continue' and $chunks == $item->imported) $pointer = $file->pointer;
																					
									}
									unset($dom, $xpath, $elements);
							    }
							}	
							unset($file);		

							!$key and $filePath = $path;					
						}

						if (empty($chunks)) 
							$this->errors->add('form-validation', __('No matching elements found for Root element and XPath expression specified', 'pmxi_plugin'));
						else
							$xml = @file_get_contents($chunk_path);
						
					} else {

						ob_start();
						$filePath && @readgzfile($filePath);					
						$xml = ob_get_clean();										
				
						if (empty($xml)){
							$xml = @file_get_contents($filePath);										
							if (empty($xml)) get_file_curl($filePath, $uploads['path']  .'/'. basename($filePath));
							if (empty($xml)) $xml = @file_get_contents($uploads['path']  .'/'. basename($filePath));
						}
					}								   
				}					
			}
			
			if (!empty($_SESSION['pmxi_import']['xml'])) $xml = $_SESSION['pmxi_import']['xml'];

			if ($item->large_import == 'Yes' or PMXI_Import_Record::validateXml($xml, $this->errors)) { // xml is valid		
				
				$item->set(array(
						'processing' => 0,
						'queue_chunk_number' => 0,
						'current_post_ids' => ''
					))->save();				

				if (empty($action_type) || $action_type == 'continue'){
					$item->set(array(						
						'imported' => 0,
						'created' => 0,
						'updated' => 0,
						'skipped' => 0
					))->save();
				}

				// compose data to look like result of wizard steps				
				$_SESSION['pmxi_import'] = (empty($_SESSION['pmxi_import']['chunk_number'])) ? array(
					'xml' => (isset($xml)) ? $xml : '',
					'filePath' => $filePath,
					'source' => array(
						'name' => $item->name,
						'type' => $item->type,						
						'path' => $item->path,
						'root_element' => $item->root_element,
					),
					'update_previous' => $item->id,
					'xpath' => $item->xpath,
					'template' => $item->template,
					'options' => $item->options,
					'scheduled' => $item->scheduled,				
					'current_post_ids' => '',
					'large_file' => ($item->large_import == 'Yes') ? true : false,
					'chunk_number' => (!empty($action_type) and $action_type == 'continue') ? $item->imported : 1,
					'pointer' => $pointer,
					'log' => '',
					'created_records' => (!empty($action_type) and $action_type == 'continue') ? $item->created : 0,
					'updated_records' => (!empty($action_type) and $action_type == 'continue') ? $item->updated : 0,
					'skipped_records' => (!empty($action_type) and $action_type == 'continue') ? $item->skipped : 0,
					'warnings' => 0,
					'errors' => 0,
					'start_time' => 0,
					'count' => (isset($chunks)) ? $chunks : 0,
					'local_paths' => (!empty($local_paths)) ? $local_paths : array(), // ftp import local copies of remote files
					'action' => (!empty($action_type) and $action_type == 'continue') ? 'continue' : 'update',					
				) : $_SESSION['pmxi_import'];
				
				// deligate operation to other controller
				$controller = new PMXI_Admin_Import();
				$controller->data['update_previous'] = $item;
				$controller->process();
				return;
			}
		}				
		$this->render();
	}
	
	/**
	 * Delete an import
	 */
	public function delete() {
		$id = $this->input->get('id');
		$this->data['item'] = $item = new PMXI_Import_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		
		if ($this->input->post('is_confirmed')) {
			check_admin_referer('delete-import', '_wpnonce_delete-import');
			
			$item->delete( ! $this->input->post('is_delete_posts'));
			wp_redirect(add_query_arg('pmxi_nt', urlencode(__('Import deleted', 'pmxi_plugin')), $this->baseUrl)); die();
		}
		
		$this->render();
	}
	
	/**
	 * Bulk actions
	 */
	public function bulk() {
		check_admin_referer('bulk-imports', '_wpnonce_bulk-imports');
		if ($this->input->post('doaction2')) {
			$this->data['action'] = $action = $this->input->post('bulk-action2');
		} else {
			$this->data['action'] = $action = $this->input->post('bulk-action');
		}
		$this->data['ids'] = $ids = $this->input->post('items');
		$this->data['items'] = $items = new PMXI_Import_List();
		if (empty($action) or ! in_array($action, array('delete')) or empty($ids) or $items->getBy('id', $ids)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		
		if ($this->input->post('is_confirmed')) {
			$is_delete_posts = $this->input->post('is_delete_posts');
			foreach($items->convertRecords() as $item) {
				$item->delete( ! $is_delete_posts);
			}
			
			wp_redirect(add_query_arg('pmxi_nt', urlencode(sprintf(__('<strong>%d</strong> %s deleted', 'pmxi_plugin'), $items->count(), _n('import', 'imports', $items->count(), 'pmxi_plugin'))), $this->baseUrl)); die();
		}
		
		$this->render();
	}

	/*
	 * Download import log file
	 *
	 */
	public function log(){

		$id = $this->input->get('id');
		
		$wp_uploads = wp_upload_dir();

		PMXI_download::csv($wp_uploads['basedir'] . '/wpallimport_logs/' .$id.'.html');

	}
}