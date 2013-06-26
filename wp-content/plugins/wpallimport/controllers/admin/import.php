<?php 
/**
 * Import configuration wizard
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */

class PMXI_Admin_Import extends PMXI_Controller_Admin {
	protected $isWizard = true; // indicates whether controller is in wizard mode (otherwize it called to be deligated an edit action)
	protected $isTemplateEdit = false; // indicates whether controlled is deligated by manage imports controller	

	protected function init() {
		parent::init();
		
		try{
			$path = session_save_path(); 
			if ( ! is_dir($path) or ! is_writable($path)){
				@ini_set("session.save_handler", "files");
				session_save_path(sys_get_temp_dir());	
			}
		} catch (XmlImportException $e) {
			$this->errors->add('form-validation', __('Can not create session.', 'pmxi_plugin')); 
		}

		// enable sessions
		if ( ! session_id()) session_start();
		
		if ('PMXI_Admin_Manage' == PMXI_Plugin::getInstance()->getAdminCurrentScreen()->base) { // prereqisites are not checked when flow control is deligated
			$id = $this->input->get('id');
			$this->data['import'] = $import = new PMXI_Import_Record();			
			if ( ! $id or $import->getById($id)->isEmpty()) { // specified import is not found
				wp_redirect(add_query_arg('page', 'pmxi-admin-manage', admin_url('admin.php'))); die();
			}
			$this->isWizard = false;
			
		} else {						
			$action = PMXI_Plugin::getInstance()->getAdminCurrentScreen()->action; 
			$this->_step_ready($action);
			$this->isInline = 'process' == $action;
		}		
		
		XmlImportConfig::getInstance()->setCacheDirectory(sys_get_temp_dir());
		
		// preserve id parameter as part of baseUrl
		$id = $this->input->get('id') and $this->baseUrl = add_query_arg('id', $id, $this->baseUrl);
	}

	public function set($var, $val)
	{
		$this->{$var} = $val;
	}
	public function get($var)
	{
		return $this->{$var};
	} 

	/**
	 * Checks whether corresponding step of wizard is complete
	 * @param string $action
	 */
	protected function _step_ready($action) {		
		// step #1: xml selction - has no prerequisites
		if ('index' == $action) return true;
		
		// step #2: element selection
		$this->data['dom'] = $dom = new DOMDocument('1.0', 'UTF-8');
		$this->data['update_previous'] = $update_previous = new PMXI_Import_Record();
		$old = libxml_use_internal_errors(true);
		
		if (empty($_SESSION['pmxi_import'])
			or ! $dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $_SESSION['pmxi_import']['xml']))// FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load
			//or empty($_SESSION['pmxi_import']['source'])
			or ! empty($_SESSION['pmxi_import']['update_previous']) and $update_previous->getById($_SESSION['pmxi_import']['update_previous'])->isEmpty()
		) {
			$this->errors->add('form-validation', __('Can not create DOM object for provided feed.', 'pmxi_plugin')); 
			wp_redirect_or_javascript($this->baseUrl); die();
		}
		libxml_use_internal_errors($old);
		if ('element' == $action) return true;
		if ('evaluate' == $action) return true;

		// step #3: template
		$xpath = new DOMXPath($dom);

		if (empty($_SESSION['pmxi_import']['xpath']) or ! ($this->data['elements'] = $elements = $xpath->query($_SESSION['pmxi_import']['xpath'])) or ! $elements->length) {
			$this->errors->add('form-validation', __('No matching elements found.', 'pmxi_plugin')); 
			wp_redirect_or_javascript(add_query_arg('action', 'element', $this->baseUrl)); die();
		}
		if ('template' == $action or 'preview' == $action or 'tag' == $action) return true;
		
		// step #4: options
		if (empty($_SESSION['pmxi_import']['template']) or empty($_SESSION['pmxi_import']['template']['title']) or empty($_SESSION['pmxi_import']['template']['title'])) {
			wp_redirect_or_javascript(add_query_arg('action', 'template', $this->baseUrl)); die();
		}
		if ('options' == $action) return true;
		
		if (empty($_SESSION['pmxi_import']['options'])) {
			wp_redirect(add_query_arg('action', 'options', $this->baseUrl)); die();
		}
	}
	
	/**
	 * Step #1: Choose File
	 */
	public function index() {
		
		$this->data['reimported_import'] = $import = new PMXI_Import_Record();
		$this->data['id'] = $id = $this->input->get('id');
		if ($id and $import->getById($id)->isEmpty()) { // update requested but corresponding import is not found
			wp_redirect(remove_query_arg('id', $this->baseUrl)); die();
		}
		
		$this->data['post'] = $post = $this->input->post(array(
			'type' => 'upload',
			'url' => 'http://',
			'ftp' => array('url' => 'ftp://'),
			'file' => '',
			'reimport' => '',
			'is_update_previous' => $id ? 1 : 0,
			'update_previous' => $id,
			'xpath' => '/',
			'large_file' => '',
			'filepath' => '',
			'root_element' => ''
		));
		
		/*$this->data['imports'] = $imports = new PMXI_Import_List();
		$imports->setColumns('id', 'name', 'registered_on', 'path')->getBy(NULL, 'name ASC, registered_on DESC');*/
		
		$this->data['history'] = $history = new PMXI_File_List();
		$history->setColumns('id', 'name', 'registered_on', 'path')->getBy(NULL, 'id DESC');
		
		if ($history->count())
		{
			$uploads = wp_upload_dir();			
			foreach ($history as $file){
				if (file_exists(PMXI_Plugin::ROOT_DIR . '/history/' . $file['id']) and !file_exists($uploads['basedir']  . '/wpallimport_history/' . $file['id']))
				{
					$fdata = @file_get_contents(PMXI_Plugin::ROOT_DIR . '/history/' . $file['id']);								
					@file_put_contents($uploads['basedir']  . '/wpallimport_history/' . $file['id'], $fdata);										
				}						
			}
		}

		if ($this->input->post('is_submitted_continue')) { 
			if ( ! empty($_SESSION['pmxi_import']['xml'])) {
				wp_redirect(add_query_arg('action', 'element', $this->baseUrl)); die();
			}
		} elseif ('upload' == $this->input->post('type')) { 						
			
			$uploads = wp_upload_dir();

			if (empty($post['filepath'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif (!is_file($post['filepath'])) {
				$this->errors->add('form-validation', __('Uploaded file is empty', 'pmxi_plugin'));
			} elseif ( ! preg_match('%\W(xml|gzip|zip|csv|gz)$%i', trim(basename($post['filepath'])))) {				
				$this->errors->add('form-validation', __('Uploaded file must be XML, CSV or ZIP, GZIP', 'pmxi_plugin'));
			} elseif (preg_match('%\W(zip)$%i', trim(basename($post['filepath'])))) {
								
				include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

				$archive = new PclZip($post['filepath']);
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
						$zip = zip_open(trim($post['filepath']));
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

					// Detect if file is very large
					$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;
					$source = array(
						'name' => basename($post['filepath']),
						'type' => 'upload',							
						'path' => $post['filepath'],					
					); 

					if (preg_match('%\W(csv)$%i', trim($filePath))){ // If CSV file found in archieve						

						if($uploads['error']){
							 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
						}																								
						if (empty($post['large_file'])) {
							$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
						}
						else{										
							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
							$csv = new PMXI_CsvParser($filePath, true); // create chunks
							$filePath = $csv->xml_path;
							$post['root_element'] = 'node';		
						}
					}					
				}

			} elseif ( preg_match('%\W(csv)$%i', trim($post['filepath']))) { // If CSV file uploaded
				
				// Detect if file is very large
				$post['large_file'] = (filesize($post['filepath']) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;

				if($uploads['error']){
					 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
				}									
    			$filePath = $post['filepath'];
				$source = array(
					'name' => basename($post['filepath']),
					'type' => 'upload',
					'path' => $filePath,
				);				
				if (empty($post['large_file'])) {
					$filePath = PMXI_Plugin::csv_to_xml($post['filepath']);					
				} else{										
					include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
					$csv = new PMXI_CsvParser($post['filepath'], true);					
					$filePath = $csv->xml_path;
					$post['root_element'] = 'node';
				}					   					
			} elseif(preg_match('%\W(gz)$%i', trim($post['filepath']))){ // If gz file uploaded
				$fileInfo = pmxi_gzfile_get_contents($post['filepath']);
				$filePath = $fileInfo['localPath'];				
				
				// Detect if file is very large
				$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;

				$source = array(
					'name' => basename($post['filepath']),
					'type' => 'upload',
					'path' => $post['filepath'],					
				);

				// detect CSV or XML 
				if ( $fileInfo['type'] == 'csv') { // it is CSV file									
					if (empty($post['large_file'])) {																
						$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																						
					}
					else{																
						include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
						$csv = new PMXI_CsvParser($filePath, true); // create chunks
						$filePath = $csv->xml_path;
						$post['root_element'] = 'node';						
					}
				}
			} else { // If XML file uploaded

				// Detect if file is very large
				$post['large_file'] = (filesize($post['filepath']) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;
				
				$filePath = $post['filepath'];
				$source = array(
					'name' => basename($post['filepath']),
					'type' => 'upload',
					'path' => $filePath,
				);
			}		
		} elseif ('url' == $this->input->post('type')) { 

			$uploads = wp_upload_dir();

			if (empty($post['url'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif ( ! preg_match('%^https?://%i', $post['url'])) {
				$this->errors->add('form-validation', __('Specified URL has wrong format'), 'pmxi_plugin');
			} elseif( ! is_writeable($uploads['path'])){
				$this->errors->add('form-validation', __('Uploads folder '.$uploads['path'].' is not writable.'), 'pmxi_plugin');
			} elseif (preg_match('%\W(zip)$%i', trim($post['url']))) {							
				
				$tmpname = $uploads['path'] . '/' . wp_unique_filename($uploads['path'], basename($post['url']));
				
				@copy($post['url'], $tmpname);				
				
				if (!file_exists($tmpname)) {										

					get_file_curl($post['url'], $tmpname);

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

					// Detect if file is very large
					$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;
					$source = array(
						'name' => basename(parse_url($post['url'], PHP_URL_PATH)),
						'type' => 'url',
						'path' => $post['url'],							
					);  

					if (preg_match('%\W(csv)$%i', trim($filePath))){
						
						$source = array(
							'name' => basename(parse_url($post['url'], PHP_URL_PATH)),
							'type' => 'url',
							'path' => $post['url'],							
						); 
						if (empty($post['large_file'])) {
							$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
						}
						else{										
							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
							$csv = new PMXI_CsvParser($filePath, true); // create chunks
							$filePath = $csv->xml_path;
							$post['root_element'] = 'node';			   					   
						}	

					}								
				}

			} elseif (preg_match('%\W(csv)$%i', trim($post['url']))) {
							
				$source = array(
					'name' => basename(parse_url($post['url'], PHP_URL_PATH)),
					'type' => 'url',
					'path' => $post['url'],					
				); 

				// copy remote file in binary mode
				$filePath = pmxi_copy_url_file($post['url']);	

				if (!file_exists($filePath)) {
				    $this->errors->add('form-validation', __('Failed upload CSV file', 'pmxi_plugin'));
				}

				// Detect if file is very large
				$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;				

				if (empty($post['large_file'])) {																				
					$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																					
				}
				else {					
					include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
					$csv = new PMXI_CsvParser($filePath, true); // create chunks
					$filePath = $csv->xml_path;
					$post['root_element'] = 'node';		
				}

			} else {
				
				$fileInfo = (preg_match('%\W(gz)$%i', trim($post['url']))) ? pmxi_gzfile_get_contents($post['url']) : pmxi_copy_url_file($post['url'], true);
				$filePath = $fileInfo['localPath'];				
				
				if (!file_exists($filePath)) {
				    $this->errors->add('form-validation', __('Failed upload XML file', 'pmxi_plugin'));
				}

				// Detect if file is very large
				$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;

				$source = array(
					'name' => basename(parse_url($post['url'], PHP_URL_PATH)),
					'type' => 'url',
					'path' => $post['url'],					
				);				

				// detect CSV or XML 
				if ( $fileInfo['type'] == 'csv') { // it is CSV file									
					if (empty($post['large_file'])) {																
						$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																						
					}
					else{																
						include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
						$csv = new PMXI_CsvParser($filePath, true); // create chunks
						$filePath = $csv->xml_path;
						$post['root_element'] = 'node';							
					}
				}
			}
		} elseif ('ftp' == $this->input->post('type')) {			
			
			$uploads = wp_upload_dir();

			if (empty($post['ftp']['url'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif ( ! preg_match('%^ftps?://%i', $post['ftp']['url'])) {
				$this->errors->add('form-validation', __('Specified FTP resource has wrong format'), 'pmxi_plugin');
			} else {
				// path to remote file
				$remote_file = $post['ftp']['url'];
								
				// set up basic connection
				$ftp_url = $post['ftp']['url'];
				$parsed_url = parse_url($ftp_url);
				$ftp_server = $parsed_url['host'] ;
				$conn_id = @ftp_connect( $ftp_server );
				$is_ftp_ok = TRUE;				

				// login with username and password
				$ftp_user_name = $post['ftp']['user'];
				$ftp_user_pass = $post['ftp']['pass'];

				// hide warning message
				echo '<span style="display:none">';
				if ( !ftp_login($conn_id, $ftp_user_name, $ftp_user_pass) ){
					$this->errors->add('form-validation', __('Login authentication failed', 'pmxi_plugin'));
					$is_ftp_ok = false;
				}
				echo '</span>';
				

				if ( $is_ftp_ok ){							

					$filePath = preg_replace('%://([^@/]*@)?%', '://' . urlencode($post['ftp']['user']) . ':' . urlencode($post['ftp']['pass']) . '@', $ftp_url, 1);
					$file_path_array = PMXI_Helper::safe_glob($filePath, PMXI_Helper::GLOB_NODIR | PMXI_Helper::GLOB_PATH);
					$local_paths = array();

					if ($file_path_array) {
						foreach ($file_path_array as $singlePath) {
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
						$singlePath = array_shift($file_path_array); // take only 1st matching one
					} else {
						$singlePath = FALSE;
					}															
					
					// close the connection and the file handler
					ftp_close($conn_id);					

					// copy file in temporary folder						
					if($uploads['error']){
						$this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
					}	

					// Detect if file is very large					
					$post['large_file'] = 'on';

					$source = array(
						'name' => basename($singlePath),
						'type' => 'ftp',
						'path' => $filePath,							
					);					

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
							$post['root_element'] = 'node';												
						}
						
					}
					$filePath = (!empty($local_paths)) ? $local_paths[0] : '';
				}
			}
		} elseif ('file' == $this->input->post('type')) {

			$wp_uploads = wp_upload_dir();

			if (empty($post['file'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif (preg_match('%\W(zip)$%i', trim($post['file']))) {

				$uploads = PMXI_Plugin::ROOT_DIR . '/upload/';
				
				if($wp_uploads['error']){
					 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
				}																
				
				$zipfilePath = $uploads . basename($post['file']);

				include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

				$archive = new PclZip($zipfilePath);
			    if (($v_result_list = $archive->extract(PCLZIP_OPT_PATH, $wp_uploads['path'], PCLZIP_OPT_REPLACE_NEWER)) == 0) {
			    	$this->errors->add('form-validation', 'Failed to open uploaded ZIP archive : '.$archive->errorInfo(true));			    	
			   	}
				else {
					
					$filePath = '';

					if (!empty($v_result_list)){
						foreach ($v_result_list as $unzipped_file) {
							if ($unzipped_file['status'] == 'ok') $filePath = $unzipped_file['filename'];
						}
					}
			    	if($wp_uploads['error']){
						 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
					}

					if(empty($filePath)){						
						$zip = zip_open(trim($zipfilePath));
						if (is_resource($zip)) {														
							while ($zip_entry = zip_read($zip)) {
								$filePath = zip_entry_name($zip_entry);												
							    $fp = fopen($wp_uploads['path']."/".$filePath, "w");
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

					// Detect if file is very large
					$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;
					$source = array(
						'name' => basename($post['file']),
						'type' => 'file',							
						'path' => $uploads . $post['file'],					
					); 

					if (preg_match('%\W(csv)$%i', trim($filePath))){ // If CSV file found in archieve						

						if($wp_uploads['error']){
							 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
						}																								
						if (empty($post['large_file'])) {
							$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
						}
						else{										
							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
							$csv = new PMXI_CsvParser($filePath, true); // create chunks
							$filePath = $csv->xml_path;
							$post['root_element'] = 'node';		
						}
					}					
				}
			} elseif (preg_match('%\W(csv)$%i', trim($post['file']))) {
				$uploads = PMXI_Plugin::ROOT_DIR . '/upload/';
				
				if($wp_uploads['error']){
					 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
				}		
				// copy file in temporary folder
				// hide warning message
				echo '<span style="display:none">';
				copy( $uploads . $post['file'], $wp_uploads['path']  . '/' . basename($post['file']));
				echo '</span>';												
				
				$filePath = $wp_uploads['path'] . '/' . basename($post['file']);
				$source = array(
					'name' => basename($post['file']),
					'type' => 'file',
					'path' => $uploads . $post['file'],
				); 				
				
				// Detect if file is very large
				$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;		

				if (empty($post['large_file'])) {
					$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
				}
				else{					
					include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');	
					$csv = new PMXI_CsvParser($filePath, true); // create chunks
					$filePath = $csv->xml_path;
					$post['root_element'] = 'node';						
				}
				
			}
			else {
				$uploads = PMXI_Plugin::ROOT_DIR . '/upload/';
				
				if($wp_uploads['error']){
					$this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
				}		
				// copy file in temporary folder
				// hide warning message
				echo '<span style="display:none">';
				copy( $uploads . $post['file'], $wp_uploads['path']  . '/' . basename($post['file']));
				echo '</span>';						

				$source = array(
					'name' => basename($post['file']),
					'type' => 'file',
					'path' => $uploads . $post['file'],
				);

				$filePath = $wp_uploads['path'] . '/' . basename($post['file']);

				if ( preg_match('%\W(gz)$%i', basename($post['file']))){

					$fileInfo = pmxi_gzfile_get_contents($filePath);

					$filePath = $fileInfo['localPath'];
				}					

				if ( preg_match('%\W(csv)$%i', trim($path)) or (!empty($fileInfo) and $fileInfo['type'] == 'csv') ){																																					
					include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');		
					$csv = new PMXI_CsvParser($filePath, true); // create chunks
					$filePath = $csv->xml_path;
					$post['root_element'] = 'node';												
				}																			

				// Detect if file is very large
				$post['large_file'] = (filesize($filePath) > PMXI_Plugin::LARGE_SIZE) ? 'on' : false;	

			}
		}

		if ($post['is_update_previous'] and empty($post['update_previous'])) {
			$this->errors->add('form-validation', __('Previous import for update must be selected to proceed with a new one', 'pmxi_plugin'));
		}
		
		if ($this->input->post('is_submitted') and ! $this->errors->get_error_codes()) {
				
			check_admin_referer('choose-file', '_wpnonce_choose-file');					 								
			
			$elements_cloud = array();

			$is_validate = true;

			if (empty($xml)){

				$wp_uploads = wp_upload_dir();
				
				if (!empty($post['large_file'])){
					
					set_time_limit(0);							

					$chunks  = 0;
					
					$chunk_path = '';

					$local_paths = !empty($local_paths) ? $local_paths : array($filePath);
					
					foreach ($local_paths as $key => $path) {
															
						$file = new PMXI_Chunk($path, array('element' => $post['root_element'], 'path' => $wp_uploads['path']));					
					    
					    while ($chunk_xml = $file->read()) {					      						    					    					    	
					    	
					    	if (!empty($chunk_xml))
					      	{									      		
					      		PMXI_Import_Record::preprocessXml($chunk_xml);
						      	if ( ! $chunks ){ // save first chunk to the file
							      	$chunk_path = $wp_uploads['path'] .'/'. wp_unique_filename($wp_uploads['path'], "chunk_".basename($path));
									if (file_exists($chunk_path)) unlink($chunk_path);	
									
									$errors = array(); 
									$checking_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"."\n".utf8_encode($chunk_xml);
									if (PMXI_Import_Record::validateXml($checking_xml, $errors))
								    	file_put_contents($chunk_path, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"."\n".utf8_encode($chunk_xml));
								    else
								    	file_put_contents($chunk_path, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"."\n".$chunk_xml);
								    chmod($chunk_path, 0755);
								    $is_validate = $file->is_validate; 
								}
							    $chunks++;
						    }
						}
						if ( ! $key ){

							if ( ! empty($file->options['element'])) { 
								
								$post['root_element'] = $file->options['element']; 

								$xpath = "/".$post['root_element'];

								$elements_cloud = $file->cloud;
								
								if (empty($chunks)) { $this->errors->add('form-validation', __('No matching elements found for Root element and XPath expression specified', 'pmxi_plugin')); }

							    $filePath && $xml = @file_get_contents($chunk_path);
							}
							else $this->errors->add('form-validation', __('Unable to find root element for this feed. Please open the feed in your browser or a text editor and ensure it is a valid feed.', 'pmxi_plugin')); 
						}
					}							
					
				} else {

					ob_start();
					$filePath && @readgzfile($filePath);					
					$xml = ob_get_clean();										
					
					if (empty($xml)){
						$xml = @file_get_contents($filePath);										
						if (empty($xml)) get_file_curl($filePath, $wp_uploads['path']  .'/'. basename($filePath));
						if (empty($xml)) $xml = @file_get_contents($wp_uploads['path']  .'/'. basename($filePath));
					}
				}								   
			}					
			
			if ((!$is_validate or PMXI_Import_Record::validateXml($xml, $this->errors)) and (empty($post['large_file']) or (!empty($post['large_file']) and !empty($chunks)))) {
				// xml is valid
				if (!empty($post['large_file'])){
					$source['large_import'] = 'Yes';
					$source['root_element'] = $post['root_element'];
				} 
				else {
					$source['large_import'] = 'No';
					$source['root_element'] = '';
				}
				
				$source['first_import'] = date("Y-m-d H:i:s");

				$_SESSION['pmxi_import'] = array(
					'xml' => $xml,
					'filePath' => $filePath,
					'xpath' => (!empty($xpath)) ? $xpath : '',
					'source' => $source,					
					'large_file' => (!empty($post['large_file'])) ? true : false,
					'chunk_number' => 1,
					'log' => '',
					'current_post_ids' => '',
					'processing' => 0,
					'queue_chunk_number' => 0,
					'count' => (isset($chunks)) ? $chunks : 0,
					'created_records' => 0,
					'updated_records' => 0,
					'skipped_records' => 0,
					'warnings' => 0,
					'errors' => 0,
					'start_time' => 0,
					'local_paths' => (!empty($local_paths)) ? $local_paths : array(), // ftp import local copies of remote files
					'action' => 'import',
					'elements_cloud' => (!empty($elements_cloud)) ? $elements_cloud : array()
				);								

				unset($xml);				
				$update_previous = new PMXI_Import_Record();
				if ($post['is_update_previous'] and ! $update_previous->getById($post['update_previous'])->isEmpty()) {
					$_SESSION['pmxi_import'] += array(
						'update_previous' => $update_previous->id,
						'xpath' => $update_previous->xpath,
						'template' => $update_previous->template,
						'options' => $update_previous->options,
					);
				} else {
					$_SESSION['pmxi_import']['update_previous'] = '';
				}		
								
				wp_redirect(add_query_arg('action', 'element', $this->baseUrl)); die();

			}
		}
		
		$this->render();
	}
	
	/**
	 * Step #2: Choose elements
	 */
	public function element()
	{
					
		$xpath = new DOMXPath($this->data['dom']);		
		$post = $this->input->post(array('xpath' => ''));
		$this->data['post'] =& $post;
		$this->data['elements_cloud'] = $_SESSION['pmxi_import']['elements_cloud'];

		$wp_uploads = wp_upload_dir();

		if ($this->input->post('is_submitted')) {
			check_admin_referer('choose-elements', '_wpnonce_choose-elements');
			if ('' == $post['xpath']) {
				$this->errors->add('form-validation', __('No elements selected', 'pmxi_plugin'));
			} else {
				$node_list = @ $xpath->query($post['xpath']); // make sure only element selection is allowed; prevent parsing warning to be displayed
				
				if (FALSE === $node_list) {
					$this->errors->add('form-validation', __('Invalid XPath expression', 'pmxi_plugin'));
				} elseif ( ! $node_list->length) {
					$this->errors->add('form-validation', __('No matching elements found for XPath expression specified', 'pmxi_plugin'));
				} else {
					foreach ($node_list as $el) {
						if ( ! $el instanceof DOMElement) {
							$this->errors->add('form-validation', __('XPath must match only elements', 'pmxi_plugin'));
							break;
						};
					}
				}
			}

			if ( ! $this->errors->get_error_codes()) {

				$_SESSION['pmxi_import']['xpath'] = $post['xpath'];
				// counting element selected by xPath
				if ($_SESSION['pmxi_import']['large_file']){									

					$_SESSION['pmxi_import']['count'] = 0;

					$this->data['node_list_count'] = 0;

					// loop through the file until all lines are read				    				    			   
				    $first_loop = true; 

					foreach ($_SESSION['pmxi_import']['local_paths'] as $key => $path) {
						
						$file = new PMXI_Chunk($path, array('element' => $_SESSION['pmxi_import']['source']['root_element'], 'path' => $wp_uploads['path'], 'type' => $this->input->post('type')));									   				    
					    
					    while ($xml = $file->read()) {					      						    					    					    	
					    	
					    	if (!empty($xml))
					      	{				
					      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      		
								
						      	$dom = new DOMDocument('1.0', 'UTF-8');															
								$old = libxml_use_internal_errors(true);
								$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
								libxml_use_internal_errors($old);
								$xpath = new DOMXPath($dom);
								if (($this->data['elements'] = $elements = @$xpath->query($post['xpath'])) and $elements->length){ 
									$_SESSION['pmxi_import']['count']++;
									$this->data['node_list_count']++;
									if ($first_loop){
										$_SESSION['pmxi_import']['xml'] = $xml;
										$first_loop = false;
									}
								}
								unset($dom, $xpath, $elements);
								
						    }
						}
					}
					
					if ( ! $this->data['node_list_count']) {
						$this->errors->add('form-validation', __('No matching elements found for XPath expression specified', 'pmxi_plugin'));
					}
				}
				wp_redirect(add_query_arg('action', 'template', $this->baseUrl)); die();
			}
		} else {
			
			if (isset($_SESSION['pmxi_import']['xpath']) and $_SESSION['pmxi_import']['large_file']) {
				$post['xpath'] = $_SESSION['pmxi_import']['xpath'];
				$this->data['elements'] = $elements = $xpath->query($post['xpath']);
				if ( ! $elements->length and ! empty($_SESSION['pmxi_import']['update_previous'])) {
					$_GET['pmxi_nt'] = __('<b>Warning</b>: No matching elements found for XPath expression from the import being updated. It probably means that new XML file has different format. Though you can update XPath, procceed only if you sure about update operation being valid.', 'pmxi_plugin');
				}
			} else {
				// suggest 1st repeating element as default selection
				$post['xpath'] = $this->xml_find_repeating($this->data['dom']->documentElement);
				if (!empty($post['xpath'])){
					$this->data['elements'] = $elements = $xpath->query($post['xpath']);
				}
			}

		}
		
		// workaround to prevent rendered XML representation to eat memory since it has to be stored in momory when output is bufferred
		$this->render();
		add_action('pmxi_action_after', array($this, 'element_after'));
	}
	public function element_after()
	{
		$this->render();
	}
	
	/**
	 * Helper to evaluate xpath and return matching elements as direct paths for javascript side to highlight them
	 */
	public function evaluate()
	{
		if ( ! PMXI_Plugin::getInstance()->getAdminCurrentScreen()->is_ajax) { // call is only valid when send with ajax
			wp_redirect(add_query_arg('action', 'element', $this->baseUrl)); die();
		}

		$xpath = new DOMXPath($this->data['dom']);
		$post = $this->input->post(array('xpath' => '', 'show_element' => 1, 'root_element' => $_SESSION['pmxi_import']['source']['root_element']));
		$wp_uploads = wp_upload_dir();

		if ('' == $post['xpath']) {
			$this->errors->add('form-validation', __('No elements selected', 'pmxi_plugin'));
		} else {			
			// counting selected elements
			if ($_SESSION['pmxi_import']['large_file']){ // in large mode				
				
				$this->data['node_list_count'] = 0;				

				$_SESSION['pmxi_import']['count'] = 0;
							
				$xpath_elements = explode('[', $post['xpath']);
				$xpath_parts = explode('/', $xpath_elements[0]);				
				
				$_SESSION['pmxi_import']['source']['root_element'] = $xpath_parts[count($xpath_parts) - 1];

				$loop = 1; 

				foreach ($_SESSION['pmxi_import']['local_paths'] as $key => $path) {

					$file = new PMXI_Chunk($path, array('element' => $_SESSION['pmxi_import']['source']['root_element'], 'path' => $wp_uploads['path']));								    
				    // loop through the file until all lines are read				    				    			   				    
				    while ($xml = $file->read()) {					      						    					    					    	
				    	
				    	if (!empty($xml))
				      	{
				      		PMXI_Import_Record::preprocessXml($xml);
					      					      		
					      	$dom = new DOMDocument('1.0', 'UTF-8');
							$old = libxml_use_internal_errors(true);
							$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load
							libxml_use_internal_errors($old);
							$xpath = new DOMXPath($dom);
							if (($this->data['elements'] = $elements = @$xpath->query($post['xpath'])) and $elements->length){
								$_SESSION['pmxi_import']['count']++;
								$this->data['node_list_count']++;
								if ($loop == $post['show_element'] ){
									$_SESSION['pmxi_import']['xml'] = $xml;
									$this->data['dom'] = $dom;
								}
								else unset($dom, $xpath, $elements);
								$loop++;
							}
					    }
					}
					unset($file);
				}
				if ( ! $this->data['node_list_count']) {
					$this->errors->add('form-validation', __('No matching elements found for XPath expression specified', 'pmxi_plugin'));
				}
			}
			else{ // in default mode
				$this->data['elements'] = $elements = @ $xpath->query($post['xpath']); // prevent parsing warning to be displayed
				$this->data['node_list_count'] = $elements->length;
				if (FALSE === $elements) {
					$this->errors->add('form-validation', __('Invalid XPath expression', 'pmxi_plugin'));
				} elseif ( ! $elements->length) {
					$this->errors->add('form-validation', __('No matching elements found for XPath expression specified', 'pmxi_plugin'));
				} else {
					foreach ($elements as $el) {
						if ( ! $el instanceof DOMElement) {
							$this->errors->add('form-validation', __('XPath must match only elements', 'pmxi_plugin'));
							break;
						};
					}
				}
			}
		}
		if ( ! $this->errors->get_error_codes()) {

			//$this->shrink_xml_element($this->data['dom']->documentElement);
			$xpath = new DOMXPath($this->data['dom']);
			$this->data['elements'] = $elements = @ $xpath->query($post['xpath']); // prevent parsing warning to be displayed
			
			$paths = array(); $this->data['paths'] =& $paths;
			if (PMXI_Plugin::getInstance()->getOption('highlight_limit') and $elements->length <= PMXI_Plugin::getInstance()->getOption('highlight_limit')) {
				foreach ($elements as $el) {
					if ( ! $el instanceof DOMElement) continue;
					
					$p = $this->get_xml_path($el, $xpath) and $paths[] = $p;
				}
			}
			$this->render();
		} else {
			$this->error();
		}
	}
	
	/**
	 * Step #3: Choose template
	 */
	public function template()
	{
		
		$template = new PMXI_Template_Record();
		$default = array(
			'title' => '',
			'content' => '',
			'name' => '',
			'is_keep_linebreaks' => 0,
			'is_leave_html' => 0
		);		
		if ($this->isWizard) {			
			$this->data['post'] = $post = $this->input->post(
				(isset($_SESSION['pmxi_import']['template']) ? $_SESSION['pmxi_import']['template'] : array())
				+ $default
			);
		} else {			
			$this->data['post'] = $post = $this->input->post(
				$this->data['import']->template
				+ $default
			);			
		}		
		
		if (($load_template = $this->input->post('load_template'))) { // init form with template selected
			if ( ! $template->getById($load_template)->isEmpty()) {
				$this->data['post'] = array(
					'title' => $template->title,
					'content' => $template->content,
					'is_keep_linebreaks' => $template->is_keep_linebreaks,	
					'is_leave_html' => $template->is_leave_html,				
					'name' => '', // template is always empty
				);
				$_SESSION['pmxi_import']['is_loaded_template'] = $load_template;
			}

		} elseif ($this->input->post('is_submitted')) { // save template submission
			check_admin_referer('template', '_wpnonce_template');
			
			if (empty($post['title'])) {
				$this->errors->add('form-validation', __('Post title is empty', 'pmxi_plugin'));
			} else {
				$this->_validate_template($post['title'], 'Post title');
			}

			if (empty($post['content'])) {
				$this->errors->add('form-validation', __('Post content is empty', 'pmxi_plugin'));
			} else {
				$this->_validate_template($post['content'], 'Post content');
			}							
			
			if ( ! $this->errors->get_error_codes()) {				
				if ( ! empty($post['name'])) { // save template in database
					$template->getByName($post['name'])->set($post)->save();
					$_SESSION['pmxi_import']['saved_template'] = $template->id;				
				}
				if ($this->isWizard) {
					$_SESSION['pmxi_import']['template'] = $post;					
					wp_redirect(add_query_arg('action', 'options', $this->baseUrl)); die();
				} else {					
					$this->data['import']->set('template', $post)->save();
					wp_redirect(add_query_arg(array('page' => 'pmxi-admin-manage', 'pmlc_nt' => urlencode(__('Template updated', 'pmxi_plugin'))) + array_intersect_key($_GET, array_flip($this->baseUrlParamNames)), admin_url('admin.php'))); die();
				}
			}
		}
		
		if (user_can_richedit()) {
			wp_enqueue_script('editor');
		}
		wp_enqueue_script('word-count');
		add_thickbox();
		wp_enqueue_script('media-upload');
		add_action('admin_print_footer_scripts', 'wp_tiny_mce', 25);
		wp_enqueue_script('quicktags');
		$this->render();
	}

	protected function _validate_template($text, $field_title)
	{
		try {
			$scanner = new XmlImportTemplateScanner();
			$tokens = $scanner->scan(new XmlImportStringReader($text));
			$parser = new XmlImportTemplateParser($tokens);
			$tree = $parser->parse();
		} catch (XmlImportException $e) {
			$this->errors->add('form-validation', sprintf(__('%s template is invalid: %s', 'pmxi_plugin'), $field_title, $e->getMessage()));
		}
	}
	
	/**
	 * Preview selected xml tag (called with ajax from `template` step)
	 */
	public function tag()
	{					

		$wp_uploads = wp_upload_dir();
		
		if (empty($this->data['elements']->length))
		{
			$update_previous = new PMXI_Import_Record();
			if ($update_previous->getById($this->input->get('id'))) {				
				$_SESSION['pmxi_import'] = array(
					'update_previous' => $update_previous->id,
					'xpath' => $update_previous->xpath,
					'template' => $update_previous->template,
					'options' => $update_previous->options,
				);
				$history = new PMXI_File_List();
				$history->setColumns('id', 'name', 'registered_on', 'path')->getBy(array('import_id' => $update_previous->id), 'id DESC');				
				if ($history->count()){
					$history_file = new PMXI_File_Record();
					$history_file->getBy('id', $history[0]['id']);

					if ($update_previous->large_import == 'Yes'){
						$_SESSION['pmxi_import']['filePath'] = $history_file->path;
						$_SESSION['pmxi_import']['source']['root_element'] = $update_previous->root_element;
						$_SESSION['pmxi_import']['large_file'] = true;
						$_SESSION['pmxi_import']['count'] = $update_previous->count;
					}
					else{ 						
						$_SESSION['pmxi_import']['xml'] = @file_get_contents($history_file->path);																	
						$this->data['dom'] = $dom = new DOMDocument('1.0', 'UTF-8');			
						$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $_SESSION['pmxi_import']['xml']));
						$xpath = new DOMXPath($dom);

						$this->data['elements'] = $elements = $xpath->query($update_previous->xpath);
						if ( !$elements->length ) $this->data['elements'] = $elements = $xpath->query('.');
					}
				}	

			} else {
				$_SESSION['pmxi_import']['update_previous'] = '';
			}								
		}
		
		$this->data['tagno'] = min(max(intval($this->input->getpost('tagno', 1)), 1), ( ! $_SESSION['pmxi_import']['large_file'] ) ? $this->data['elements']->length : $_SESSION['pmxi_import']['count']);
		
		if ($_SESSION['pmxi_import']['large_file'] and $this->data['tagno']){	
			$loop = 1;			
			$_SESSION['pmxi_import']['local_paths'] = (!empty($_SESSION['pmxi_import']['local_paths'])) ? $_SESSION['pmxi_import']['local_paths'] : array($_SESSION['pmxi_import']['filePath']);
			foreach ($_SESSION['pmxi_import']['local_paths'] as $key => $path) {												
				$file = new PMXI_Chunk($path, array('element' => $_SESSION['pmxi_import']['source']['root_element']));								   
			    // loop through the file until all lines are read				    				    			   			    
			    while ($xml = $file->read()) {					      						    					    					    			    	
			    	if (!empty($xml))
			      	{				
			      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      		
				      					      		
				      	$dom = new DOMDocument('1.0', 'UTF-8');															
						$old = libxml_use_internal_errors(true);
						$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
						libxml_use_internal_errors($old);
						$xpath = new DOMXPath($dom);
						if (($this->data['elements'] = $elements = @$xpath->query($_SESSION['pmxi_import']['xpath'])) and $elements->length){ 						
							if ($loop == $this->data['tagno']){ 
								$_SESSION['pmxi_import']['xml'] = $xml;
								break; 
							} else unset($dom, $xpath, $elements);
							$loop++;
						}											  					 
				    }
				}	
				unset($file);
			}
		}

		$this->render();
	}
	
	/**
	 * Preview future post based on current template and tag (called with ajax from `template` step)
	 */
	public function preview()
	{
		$post = $this->input->post(array(
			'title' => '',
			'content' => '',
			'is_keep_linebreaks' => 0,
			'is_leave_html' => 0
		));		
		$wp_uploads = wp_upload_dir();

		$tagno = min(max(intval($this->input->getpost('tagno', 1)), 1), ( ! $_SESSION['pmxi_import']['large_file']) ? $this->data['elements']->length : $_SESSION['pmxi_import']['count']);		
		if ($_SESSION['pmxi_import']['large_file']){ 
			$loop = 1; 
			foreach ($_SESSION['pmxi_import']['local_paths'] as $key => $path) {
				$file = new PMXI_Chunk($path, array('element' => $_SESSION['pmxi_import']['source']['root_element'], 'path' => $wp_uploads['path']));								   
			    // loop through the file until all lines are read				    				    			   			    
			    while ($xml = $file->read()) {					      						    					    					    			    	
			    	if (!empty($xml))
			      	{				
			      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      		
				      					      		
				      	$dom = new DOMDocument('1.0', 'UTF-8');															
						$old = libxml_use_internal_errors(true);
						$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
						libxml_use_internal_errors($old);
						$xpath = new DOMXPath($dom);
						if (($this->data['elements'] = $elements = @$xpath->query($_SESSION['pmxi_import']['xpath'])) and $elements->length){ 						
							if ( $loop == $tagno ) { 
								$_SESSION['pmxi_import']['xml'] = $xml;
								break; 
							} 
							unset($dom, $xpath, $elements);												
							$loop++;
						}											  					 
				    }
				}
				unset($file);
			}
			$tagno = 1;			
		}				
		$xpath = "(" . $_SESSION['pmxi_import']['xpath'] . ")[$tagno]";		
		// validate
		try {
			if (empty($post['title'])) {
				$this->errors->add('form-validation', __('Post title is empty', 'pmxi_plugin'));
			} else {
				list($this->data['title']) = XmlImportParser::factory($_SESSION['pmxi_import']['xml'], $xpath, $post['title'], $file)->parse(); unlink($file);
				if ( ! isset($this->data['title']) or '' == strval(trim(strip_tags($this->data['title'], '<img><input><textarea><iframe><object><embed>')))) {
					$this->errors->add('xml-parsing', __('<strong>Warning</strong>: resulting post title is empty', 'pmxi_plugin'));
				}
				else $this->data['title'] = ($post['is_leave_html']) ?  utf8_encode(html_entity_decode($this->data['title'])) : $this->data['title'];
			}
		} catch (XmlImportException $e) {
			$this->errors->add('form-validation', sprintf(__('Error parsing title: %s', 'pmxi_plugin'), $e->getMessage()));
		}
		try {			
			if (empty($post['content'])) {
				$this->errors->add('form-validation', __('Post content is empty', 'pmxi_plugin'));
			} else {
				list($this->data['content']) = XmlImportParser::factory($post['is_keep_linebreaks'] ? $_SESSION['pmxi_import']['xml'] : preg_replace('%\r\n?|\n%', ' ', $_SESSION['pmxi_import']['xml']), $xpath, $post['content'], $file)->parse(); unlink($file);
				if ( ! isset($this->data['content']) or '' == strval(trim(strip_tags($this->data['content'], '<img><input><textarea><iframe><object><embed>')))) {
					$this->errors->add('xml-parsing', __('<strong>Warning</strong>: resulting post content is empty', 'pmxi_plugin'));
				}
				else $this->data['content'] = ($post['is_leave_html']) ?  utf8_encode(html_entity_decode($this->data['content'])) : $this->data['content'];
			}
		} catch (XmlImportException $e) {
			$this->errors->add('form-validation', sprintf(__('Error parsing content: %s', 'pmxi_plugin'), $e->getMessage()));
		}
		
		$this->render();
	}
	
	/**
	 * Step #4: Options
	 */
	public function options()
	{
		include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');

		$default = PMXI_Plugin::get_default_import_options();
		
		if ($this->isWizard) {			
			$this->data['source_type'] = $_SESSION['pmxi_import']['source']['type'];
			$default['unique_key'] = $_SESSION['pmxi_import']['template']['title'];
			
			// auto searching ID element
			if (!empty($this->data['dom'])){
				$this->find_unique_key($this->data['dom']->documentElement);
				if (!empty($this->_unique_key)){
					$id_finded = false;
					foreach ($this->_unique_key as $key) {
						if (stripos($key, 'id') !== false) { 
							$default['unique_key'] .= ' - {'.$key.'[1]}';
							$id_finded = true;
							break;
						}
					}
					if (!$id_finded){
						foreach ($this->_unique_key as $key) {
							if (stripos($key, 'url') !== false) { 
								$default['unique_key'] .= ' - {'.$key.'[1]}';
								$id_finded = true;
								break;
							}
						}	
					}
				}
			}					

			if ( class_exists('PMWI_Plugin') )
				$post = $this->input->post(
					(isset($_SESSION['pmxi_import']['options']) ? $_SESSION['pmxi_import']['options'] : array())
					+ $default
					+ PMWI_Plugin::get_default_import_options()
				);
			else 
				$post = $this->input->post(
					(isset($_SESSION['pmxi_import']['options']) ? $_SESSION['pmxi_import']['options'] : array())
					+ $default
				);

			$scheduled = $this->input->post(array(
				'is_scheduled' => ! empty($_SESSION['pmxi_import']['scheduled']),
				'scheduled_period' => ! empty($_SESSION['pmxi_import']['scheduled']) ? $_SESSION['pmxi_import']['scheduled'] : '0 0 * * *', // daily by default
			));
	
		} else {
			$this->data['source_type'] = $this->data['import']->type;			
			if ( class_exists('PMWI_Plugin') )
				$post = $this->input->post(
					$this->data['import']->options
					+ $default
					+ PMWI_Plugin::get_default_import_options()
				);
			else
				$post = $this->input->post(
					$this->data['import']->options
					+ $default
				);
			$scheduled = $this->input->post(array(
				'is_scheduled' => ! empty($this->data['import']->scheduled),
				'scheduled_period' => ! empty($this->data['import']->scheduled) ? $this->data['import']->scheduled : '0 0 * * *', // daily by default
			));
		}		

		$this->data['post'] =& $post;		
		$this->data['scheduled'] =& $scheduled;
		$this->data['is_loaded_template'] = $_SESSION['pmxi_import']['is_loaded_template'];

		// Get All meta keys in the system
		$this->data['meta_keys'] = $keys = new PMXI_Model_List();
		$keys->setTable(PMXI_Plugin::getInstance()->getWPPrefix() . 'postmeta');
		$keys->setColumns('meta_id', 'meta_key')->getBy(NULL, "meta_id", NULL, NULL, "meta_key");
		
		if (($load_options = $this->input->post('load_options'))) { // init form with template selected
			$this->data['load_options'] = true;
			$template = new PMXI_Template_Record();
			if ( ! $template->getById($this->data['is_loaded_template'])->isEmpty()) {								
				$post = (!empty($template->options) ? $template->options : array()) + $default;
				$scheduled = array(
					'is_scheduled' => ! empty($template->scheduled),
					'scheduled_period' => ! empty($template->scheduled) ? $template->scheduled : '0 0 * * *', // daily by default
				);
			}
			
		} elseif (($reset_options = $this->input->post('reset_options'))){
			$post = $default;
			$scheduled = $this->input->post(array(
				'is_scheduled' => ! empty($post['scheduled']),
				'scheduled_period' => ! empty($post['scheduled']) ? $post['scheduled_period'] : '0 0 * * *', // daily by default
			));
		} elseif ($this->input->post('is_submitted')) {
			check_admin_referer('options', '_wpnonce_options');		
			
			// remove entires where both custom_name and custom_value are empty 
			$not_empty = array_flip(array_values(array_merge(array_keys(array_filter($post['custom_name'])), array_keys(array_filter($post['custom_value'])))));

			$post['custom_name'] = array_intersect_key($post['custom_name'], $not_empty);
			$post['custom_value'] = array_intersect_key($post['custom_value'], $not_empty);
			
			// validate
			if (array_keys(array_filter($post['custom_name'])) != array_keys(array_filter($post['custom_value']))) {
				$this->errors->add('form-validation', __('Both name and value must be set for all custom parameters', 'pmxi_plugin'));
			} else {
				foreach ($post['custom_name'] as $custom_name) {
					$this->_validate_template($custom_name, __('Custom Field Name', 'pmxi_plugin'));
				}
				foreach ($post['custom_value'] as $custom_value) {
					$this->_validate_template($custom_value, __('Custom Field Value', 'pmxi_plugin'));
				}
			}
			if ('page' == $post['type'] and ! preg_match('%^(-?\d+)?$%', $post['order'])) {
				$this->errors->add('form-validation', __('Order must be an integer number', 'pmxi_plugin'));
			}
			if ('post' == $post['type']) {
				/*'' == $post['categories'] or $this->_validate_template($post['categories'], __('Categories', 'pmxi_plugin'));*/
				'' == $post['tags'] or $this->_validate_template($post['tags'], __('Tags', 'pmxi_plugin'));
			}
			if ('specific' == $post['date_type']) {
				'' == $post['date'] or $this->_validate_template($post['date'], __('Date', 'pmxi_plugin'));
			} else {
				'' == $post['date_start'] or $this->_validate_template($post['date_start'], __('Start Date', 'pmxi_plugin'));
				'' == $post['date_end'] or $this->_validate_template($post['date_end'], __('Start Date', 'pmxi_plugin'));
			}			
			if ('' == $post['tags_delim']) {
				$this->errors->add('form-validation', __('Tag list delimiter must cannot be empty', 'pmxi_plugin'));
			}
			if ($post['is_import_specified']) {
				if (empty($post['import_specified'])) {
					$this->errors->add('form-validation', __('Records to import must be specified or uncheck `Import only specified records` option to process all records', 'pmxi_plugin'));
				} else {
					$chanks = preg_split('% *, *%', $post['import_specified']);
					foreach ($chanks as $chank) {
						if ( ! preg_match('%^([1-9]\d*)( *- *([1-9]\d*))?$%', $chank, $mtch)) {
							$this->errors->add('form-validation', __('Wrong format of `Import only specified records` value', 'pmxi_plugin'));
							break;
						} elseif ($this->isWizard and empty($_SESSION['pmxi_import']['large_file']) and (intval($mtch[1]) > $this->data['elements']->length or isset($mtch[3]) and intval($mtch[3]) > $this->data['elements']->length)) {
							$this->errors->add('form-validation', __('One of the numbers in `Import only specified records` value exceeds record quantity in XML file', 'pmxi_plugin'));
							break;
						}
					}
				}
			}
			if ('' == $post['unique_key']) {
				$this->errors->add('form-validation', __('Expression for `Post Unique Key` must be set, use the same expression as specified for post title if you are not sure what to put there', 'pmxi_plugin'));
			} else {
				$this->_validate_template($post['unique_key'], __('Post Unique Key', 'pmxi_plugin'));
			}
			if ( $post['is_duplicates'] and 'custom field' == $post['duplicate_indicator']){
				if ('' == $post['custom_duplicate_name'])
					$this->errors->add('form-validation', __('Custom field name must be specified.', 'pmxi_plugin'));
				if ('' == $post['custom_duplicate_value'])
					$this->errors->add('form-validation', __('Custom field value must be specified.', 'pmxi_plugin'));
			}
			
			if ( ! $this->errors->get_error_codes()) { // no validation errors found
				// assign some defaults
				'' !== $post['date'] or $post['date'] = 'now';
				'' !== $post['date_start'] or $post['date_start'] = 'now';
				'' !== $post['date_end'] or $post['date_end'] = 'now';
				
				if ($this->isWizard) {
					$_SESSION['pmxi_import']['options'] = $post;
					$_SESSION['pmxi_import']['scheduled'] = $scheduled['is_scheduled'] ? $scheduled['scheduled_period'] : '';

					// Update template options
					if (!empty($_SESSION['pmxi_import']['saved_template']))  {
						$template = new PMXI_Template_Record();
						$template->getById($_SESSION['pmxi_import']['saved_template'])->set(array(
																								'options' => $_SESSION['pmxi_import']['options'],
																								'scheduled' => $_SESSION['pmxi_import']['scheduled']))->update();
					}
					elseif (!empty($_SESSION['pmxi_import']['is_loaded_template']))
					{
						$template = new PMXI_Template_Record();
						$template->getById($_SESSION['pmxi_import']['is_loaded_template'])->set(array(
																								'options' => $_SESSION['pmxi_import']['options'],
																								'scheduled' => $_SESSION['pmxi_import']['scheduled']))->update();
					}

					if ( ! $this->input->post('save_only')) { 						
						wp_redirect(add_query_arg('action', 'process', $this->baseUrl)); die();
					} else {
						$import = $this->data['update_previous'];
						$is_update = ! $import->isEmpty();
						$import->set(
							$_SESSION['pmxi_import']['source']
							+ array(
								'xpath' => $_SESSION['pmxi_import']['xpath'],
								'template' => $_SESSION['pmxi_import']['template'],
								'options' => $_SESSION['pmxi_import']['options'],
								'scheduled' => $_SESSION['pmxi_import']['scheduled'],
								'count' => $_SESSION['pmxi_import']['count'],
								'friendly_name' => $this->data['post']['friendly_name'],
							)
						)->save();
						
						$history_file = new PMXI_File_Record();
						$history_file->set(array(
							'name' => $import->name,
							'import_id' => $import->id,
							'path' => $_SESSION['pmxi_import']['filePath'],
							'contents' => $_SESSION['pmxi_import']['xml'],
							'registered_on' => date('Y-m-d H:i:s'),
						))->save();	
						unset($_SESSION['pmxi_import']); // clear session data
						wp_redirect(add_query_arg(array('page' => 'pmxi-admin-manage', 'pmlc_nt' => urlencode($is_update ? __('Import updated', 'pmxi_plugin') : __('Import created', 'pmxi_plugin'))), admin_url('admin.php'))); die();
					}
				} else {

					$this->data['import']->set('options', $post)->set( array( 'scheduled' => $scheduled['is_scheduled'] ? $scheduled['scheduled_period'] : '', 'friendly_name' => $this->data['post']['friendly_name'] ) )->save();
					$template = new PMXI_Template_Record();

					if (!$template->getByName($this->data['import']->template['name'])->isEmpty()){
						$template->set(array(
											'options' => $post,
											'scheduled' => ($scheduled['is_scheduled'] ? $scheduled['scheduled_period'] : '')))->update();
					}
					wp_redirect(add_query_arg(array('page' => 'pmxi-admin-manage', 'pmlc_nt' => urlencode(__('Options updated', 'pmxi_plugin'))) + array_intersect_key($_GET, array_flip($this->baseUrlParamNames)), admin_url('admin.php'))); die();
				} 
			}
		}
		
		! empty($post['custom_name']) or $post['custom_name'] = array('') and $post['custom_value'] = array('');
		
		$this->render();
	}

	/**
	 * Import processing step (status console)
	 */
	public function process($save_history = true)
	{
		$wp_uploads = wp_upload_dir();

		set_time_limit(0);
													
		$import = $this->data['update_previous'];				
		$import->set(
			(empty($_SESSION['pmxi_import']['source']) ? array() : $_SESSION['pmxi_import']['source'])
			+ array(
				'xpath' => $_SESSION['pmxi_import']['xpath'],
				'template' => $_SESSION['pmxi_import']['template'],
				'options' => $_SESSION['pmxi_import']['options'],				
				'scheduled' => $_SESSION['pmxi_import']['scheduled'],	
				'count' => $_SESSION['pmxi_import']['count'],
				'friendly_name' => $_SESSION['pmxi_import']['options']['friendly_name'],
			)
		);		

		if ( ! PMXI_Plugin::is_ajax()) {
			
			$this->render();
			wp_ob_end_flush_all(); flush();						

			// Save import history
			if ( $_SESSION['pmxi_import']['chunk_number'] === 1 ){
				// store import info in database			
				$import->save();			
				
				if ($_SESSION['pmxi_import']['large_file']) $_SESSION['pmxi_import']['update_previous'] = $import->id;

				if ($save_history){
					$history_file = new PMXI_File_Record();
					$history_file->set(array(
						'name' => $import->name,
						'import_id' => $import->id,
						'path' => $_SESSION['pmxi_import']['filePath'],
						'contents' => $_SESSION['pmxi_import']['xml'],
						'registered_on' => date('Y-m-d H:i:s'),
					))->save();
				}												
			}			
		}		

		// create chunks 
		if ($_SESSION['pmxi_import']['large_file'] and $_SESSION['pmxi_import']['chunk_number'] === 1 and $_SESSION['pmxi_import']['options']['create_chunks'] and ! PMXI_Plugin::is_ajax()){			
			
			$_SESSION['pmxi_import']['chunks_files'] = array();								

			foreach ($_SESSION['pmxi_import']['local_paths'] as $key => $path) {				
				$file = new PMXI_Chunk($path, array('element' => $_SESSION['pmxi_import']['source']['root_element'], 'path' => $wp_uploads['path']));								   				    				    	   
				$new_chunk = true;
				$loop = 0;
				$o = false;
				
			    // loop through the file until all lines are read				    				    			   			   	    			    			    
			    while ($xml = $file->read()) {					      						    					    					    			    				    			    	

			    	if (!empty($xml))
			      	{							      		

			      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      		
				      					      		
				      	$dom = new DOMDocument('1.0', 'UTF-8');															
						$old = libxml_use_internal_errors(true);
						$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
						libxml_use_internal_errors($old);
						$xpath = new DOMXPath($dom);
						if (($elements = @$xpath->query($_SESSION['pmxi_import']['xpath'])) and $elements->length){

							if ($new_chunk){
								$tmpname = $wp_uploads['path']  .'/'. wp_unique_filename($wp_uploads['path'], basename($path));		
								$c = fopen($tmpname, 'w');
								fwrite($c, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<".$_SESSION['pmxi_import']['source']['root_element'] ."s>\n");
							} 
							
							fwrite($c, $xml . "\n");

							if ($loop == $_SESSION['pmxi_import']['options']['records_per_request'] - 1) {								
								fwrite($c, "</".$_SESSION['pmxi_import']['source']['root_element'] . "s>");
								fclose($c);
								$_SESSION['pmxi_import']['chunks_files'][] = $tmpname;

								$loop = 0;
								$new_chunk = true;
							}
							else {								
								$loop++;
								$new_chunk = false;
							}
						}						
				    }				    				    				   
				}
				if ($loop){			    	
					fwrite($c, "</".$_SESSION['pmxi_import']['source']['root_element'] . "s>");
					fclose($c);
					$_SESSION['pmxi_import']['chunks_files'][] = $tmpname;
			    }				
			}
			$_SESSION['pmxi_import']['local_paths'] = $_SESSION['pmxi_import']['chunks_files'];		

		}		
		
		$logger = create_function('$m', 'echo "<div class=\\"progress-msg\\">$m</div>\\n"; if ( "" != strip_tags(pmxi_strip_tags_content($m))) { $_SESSION[\'pmxi_import\'][\'log\'] .= "<p>".strip_tags(pmxi_strip_tags_content($m))."</p>"; flush(); }');		

		$_SESSION['pmxi_import']['start_time'] = (empty($_SESSION['pmxi_import']['start_time'])) ? time() : $_SESSION['pmxi_import']['start_time'];					
		
		in_array($import->type, array('ftp')) and !PMXI_Plugin::is_ajax() and $logger and call_user_func($logger, __('Reading files for import...', 'pmxi_plugin'));
		in_array($import->type, array('ftp')) and !PMXI_Plugin::is_ajax() and $logger and call_user_func($logger, sprintf(_n('%s file found', '%s files found', count($_SESSION['pmxi_import']['local_paths']), 'pmxi_plugin'), count($_SESSION['pmxi_import']['local_paths'])));
		in_array($import->type, array('ftp')) and !PMXI_Plugin::is_ajax() and $logger and call_user_func($logger, sprintf(__('Importing %s (%s of %s)', 'pmxi_plugin'), $_SESSION['pmxi_import']['local_paths'][0], 1, count($_SESSION['pmxi_import']['local_paths'])));	

		if (empty($_SESSION['pmxi_import']['large_file'])){				
			$import->process($_SESSION['pmxi_import']['xml'], $logger, false);
		}
		elseif (PMXI_Plugin::is_ajax()) {
			
			$_SESSION['pmxi_import']['current_post_ids'] = (empty($_SESSION['pmxi_import']['current_post_ids'])) ? array() : $_SESSION['pmxi_import']['current_post_ids'];
			
			$_SESSION['pmxi_import']['pointer'] = (empty($_SESSION['pmxi_import']['pointer'])) ? 0 : $_SESSION['pmxi_import']['pointer'];
			
			$loop = 0;																

			foreach ($_SESSION['pmxi_import']['local_paths'] as $key => $path) {												

				if ($_SESSION['pmxi_import']['options']['create_chunks'] and $_SESSION['pmxi_import']['action'] != 'continue'){

					if ($_SESSION['pmxi_import']['created_records'] + $_SESSION['pmxi_import']['updated_records'] == $_SESSION['pmxi_import']['count']) break;

					ob_start();	

					$xml = file_get_contents($path);										
					
					if (!empty($xml))
			      	{				
			      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      						      					      		
									      		
						$import->set(array('xpath' => '/' . $_SESSION['pmxi_import']['source']['root_element'] . 's' . $_SESSION['pmxi_import']['xpath']))->save();															
						$import->process($xml, $logger, $_SESSION['pmxi_import']['chunk_number']);																											
						$import->set(array('xpath' => $_SESSION['pmxi_import']['xpath']))->save();

						array_shift($_SESSION['pmxi_import']['local_paths']);
						if (!empty($_SESSION['pmxi_import']['chunks_files'])) {
							$imported_file = array_shift($_SESSION['pmxi_import']['chunks_files']);							
							@unlink($imported_file);
						}
				    }	
					
					exit(ob_get_clean());
				}
				else {

					$file = new PMXI_Chunk($path, array('element' => $_SESSION['pmxi_import']['source']['root_element'], 'path' => $wp_uploads['path']), $_SESSION['pmxi_import']['pointer']);							  	 					

				    // loop through the file until all lines are read				    				    			   			   	    			    			    
				    while ($xml = $file->read()) {
				    	
				    	if (!empty($xml))
				      	{
				      		PMXI_Import_Record::preprocessXml($xml);
					      					      		
					      	$dom = new DOMDocument('1.0', 'UTF-8');
							$old = libxml_use_internal_errors(true);
							$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load
							libxml_use_internal_errors($old);
							$xpath = new DOMXPath($dom);
							if (($this->data['elements'] = $elements = @$xpath->query($_SESSION['pmxi_import']['xpath'])) and $elements->length){
								$_SESSION['pmxi_import']['pointer'] = $file->pointer;
								$_SESSION['pmxi_import']['xml'] = $xml;
								if ( ! $loop ) ob_start();
								$import->process($_SESSION['pmxi_import']['xml'], $logger, $_SESSION['pmxi_import']['chunk_number']);
								if ( $loop == $_SESSION['pmxi_import']['options']['records_per_request'] - 1 ) exit(ob_get_clean());
								$loop++;
							}
					    }
					    
					    if ($_SESSION['pmxi_import']['created_records'] + $_SESSION['pmxi_import']['updated_records'] == $_SESSION['pmxi_import']['count']){
					    	array_shift($_SESSION['pmxi_import']['local_paths']);
					    	exit(ob_get_clean());	
					    } 

					    $_SESSION['pmxi_import']['pointer'] = 0;									

						in_array($import->type, array('ftp')) and !empty($_SESSION['pmxi_import']['local_paths']) and $logger and call_user_func($logger, sprintf(__('Importing %s', 'pmxi_plugin'), $_SESSION['pmxi_import']['local_paths'][0]));	

					}					
				}
			}								
		}
		
		do_action( 'pmxi_after_xml_import' );		
				
		if (! $_SESSION['pmxi_import']['large_file'] or PMXI_Plugin::is_ajax()){
			
			// Save import process log
			$log_file = $wp_uploads['basedir'] . '/wpallimport_logs/' . $import->id . '.html';
			if (file_exists($log_file)) unlink($log_file);
			@file_put_contents($log_file, $_SESSION['pmxi_import']['log']);			

			// clear import session
			unset($_SESSION['pmxi_import']); // clear session data (prevent from reimporting the same data on page refresh)		

			// [indicate in header process is complete]
			$msg = addcslashes(__('Complete', 'pmxi_plugin'), "'\n\r");					

			ob_start();

			echo '<a id="download_pmxi_log" class="update" href="'.esc_url(add_query_arg(array('id' => $import->id, 'action' => 'log', 'page' => 'pmxi-admin-manage'), $this->baseUrl)).'">Download log</a>';
echo <<<COMPLETE
<script type="text/javascript">
//<![CDATA[
(function($){
	var percents = $('.import_percent:last').html();
	if (percents != null && percents != ''){	
		$('#center_progress').html($('.import_process_bar:last').html());
		$('#right_progress').html(percents + '%');
	    $('#progressbar div').css({'width': ((parseInt(percents) > 100) ? 100 : percents) + '%'});
	}
	$('#status').html('$msg');
	window.onbeforeunload = false;
})(jQuery);
//]]>
</script>
COMPLETE;
// [/indicate in header process is complete]	

			echo ob_get_clean(); die;

		}		
	}

	protected $_sibling_limit = 20;
	protected function get_xml_path(DOMElement $el, DOMXPath $xpath)
	{
		for($p = '', $doc = $el; $doc and ! $doc instanceof DOMDocument; $doc = $doc->parentNode) {
			if (($ind = $xpath->query('preceding-sibling::' . $doc->nodeName, $doc)->length)) {
				$p = '[' . ++$ind . ']' . $p;
			} elseif ( ! $doc->parentNode instanceof DOMDocument) {
				$p = '[' . ($ind = 1) . ']' . $p;
			}
			$p = '/' . $doc->nodeName . $p;
		}
		return $p;
	}
	
	protected function shrink_xml_element(DOMElement $el)
	{
		$prev = null; $sub_ind = null;
		for ($i = 0; $i < $el->childNodes->length; $i++) {
			$child = $el->childNodes->item($i);
			if ($child instanceof DOMText) {
				if ('' == trim($child->wholeText)) {
					$el->removeChild($child);
					$i--;
					continue;
				}
			}
			if ($child instanceof DOMComment) {
				continue;
			}
			if ($prev instanceof $child and $prev->nodeName == $child->nodeName) {
				$sub_ind++;
			} else {
				if ($sub_ind > $this->_sibling_limit) {
					$el->insertBefore(new DOMComment('[pmxi_more:' . ($sub_ind - $this->_sibling_limit) . ']'), $child);
					$i++;
				}
				$sub_ind = 1;
				$prev = null;
			}
			if ($child instanceof DOMElement) {
				$prev = $child;
				if ($sub_ind <= $this->_sibling_limit) {
					$this->shrink_xml_element($child); 
				} else {
					$el->removeChild($child);
					$i--;
				}
			}
		}
		if ($sub_ind > $this->_sibling_limit) {
			$el->appendChild(new DOMComment('[pmxi_more:' . ($sub_ind - $this->_sibling_limit) . ']'));
		}
		return $el;
	}
	protected function render_xml_element(DOMElement $el, $shorten = false, $path = '/', $ind = 1, $lvl = 0)
	{
		$path .= $el->nodeName;		
		if ( ! $el->parentNode instanceof DOMDocument and $ind > 0) {
			$path .= "[$ind]";
		}		
		
		echo '<div class="xml-element lvl-' . $lvl . ' lvl-mod4-' . ($lvl % 4) . '" title="' . $path . '">';
		if ($el->hasChildNodes()) {
			$is_render_collapsed = $ind > 1;
			if ($el->childNodes->length > 1 or ! $el->childNodes->item(0) instanceof DOMText or strlen(trim($el->childNodes->item(0)->wholeText)) > 40) {
				echo '<div class="xml-expander">' . ($is_render_collapsed ? '+' : '-') . '</div>';
			}
			echo '<div class="xml-tag opening">&lt;<span class="xml-tag-name">' . $el->nodeName . '</span>'; $this->render_xml_attributes($el, $path . '/'); echo '&gt;</div>';
			if (1 == $el->childNodes->length and $el->childNodes->item(0) instanceof DOMText) {
				$this->render_xml_text(trim($el->childNodes->item(0)->wholeText), $shorten, $is_render_collapsed);
			} else {
				echo '<div class="xml-content' . ($is_render_collapsed ? ' collapsed' : '') . '">';
				$indexes = array();
				foreach ($el->childNodes as $child) {
					if ($child instanceof DOMElement) {
						empty($indexes[$child->nodeName]) and $indexes[$child->nodeName] = 0; $indexes[$child->nodeName]++;
						$this->render_xml_element($child, $shorten, $path . '/', $indexes[$child->nodeName], $lvl + 1); 
					} elseif ($child instanceof DOMText) {
						$this->render_xml_text(trim($child->wholeText), $shorten); 
					} elseif ($child instanceof DOMComment) {
						if (preg_match('%\[pmxi_more:(\d+)\]%', $child->nodeValue, $mtch)) {
							$no = intval($mtch[1]);
							echo '<div class="xml-more">[ &dArr; ' . sprintf(__('<strong>%s</strong> %s more', 'pmxi_plugin'), $no, _n('element', 'elements', $no, 'pmxi_plugin')) . ' &dArr; ]</div>';
						}
					}
				}
				echo '</div>';
			}
			echo '<div class="xml-tag closing">&lt;/<span class="xml-tag-name">' . $el->nodeName . '</span>&gt;</div>';
		} else {
			echo '<div class="xml-tag opening empty">&lt;<span class="xml-tag-name">' . $el->nodeName . '</span>'; $this->render_xml_attributes($el); echo '/&gt;</div>';
		}
		echo '</div>';
	}
	protected $_unique_key = array();
	protected function find_unique_key(DOMElement $el){
		if ($el->hasChildNodes()) {
			if ($el->childNodes->length) {
				foreach ($el->childNodes as $child) {
					if ($child instanceof DOMElement) {
						if (!in_array($child->nodeName, $this->_unique_key)) $this->_unique_key[] = $child->nodeName;						
						$this->find_unique_key($child); 
					} 
				}
			}
		}
	}
	protected function render_xml_text($text, $shorten = false, $is_render_collapsed = false)
	{
		if (empty($text)) {
			return; // do not display empty text nodes
		}
		if (preg_match('%\[more:(\d+)\]%', $text, $mtch)) {
			$no = intval($mtch[1]);
			echo '<div class="xml-more">[ &dArr; ' . sprintf(__('<strong>%s</strong> %s more', 'pmxi_plugin'), $no, _n('element', 'elements', $no, 'pmxi_plugin')) . ' &dArr; ]</div>';
			return;
		}
		$more = '';
		if ($shorten and preg_match('%^(.*?\s+){20}(?=\S)%', $text, $mtch)) {
			$text = $mtch[0];
			$more = '<span class="xml-more">[' . __('more', 'pmxi_plugin') . ']</span>';
		}
		$is_short = strlen($text) <= 40;
		$text = esc_html($text); 
		$text = preg_replace('%(?<!\s)\b(?!\s|\W[\w\s])|\w{20}%', '$0&#8203;', $text); // put explicit breaks for xml content to wrap
		echo '<div class="xml-content textonly' . ($is_short ? ' short' : '') . ($is_render_collapsed ? ' collapsed' : '') . '">' . $text . $more . '</div>';
	}
	protected function render_xml_attributes(DOMElement $el, $path = '/')
	{
		foreach ($el->attributes as $attr) {
			echo ' <span class="xml-attr" title="' . $path . '@' . $attr->nodeName . '"><span class="xml-attr-name">' . $attr->nodeName . '</span>=<span class="xml-attr-value">"' . esc_attr($attr->value) . '"</span></span>';
		}
	}
	
	protected function render_xml_element_table(DOMElement $el, $shorten = false, $path = '/', $ind = 0, $lvl = 0)
	{
		$path .= $el->nodeName;
		if ($ind > 0) {
			$path .= "[$ind]";
		}
		
		$is_render_collapsed = $ind > 1;
		echo '<tr class="xml-element lvl-' . $lvl . ($is_render_collapsed ? ' collapsed' : '') . '" title="' . $path . '">';
			echo '<td style="padding-left:' . ($lvl + 1) * 15 . 'px">';
				$is_inline = true;
				if ( ! (0 == $el->attributes->length and 1 == $el->childNodes->length and $el->childNodes->item(0) instanceof DOMText and strlen($el->childNodes->item(0)->wholeText) <= 40)) {
					$is_inline = false;
					echo '<div class="xml-expander">' . ($is_render_collapsed ? '+' : '-') . '</div>';
				}
				echo '<div class="xml-tag opening"><span class="xml-tag-name">' . $el->nodeName . '</span></div>';
			echo '</td>';
			echo '<td>';
				$is_inline and $this->render_xml_text_table(trim($el->childNodes->item(0)->wholeText), $shorten, NULL, NULL, $is_inline = true);
			echo '</td>';
		echo '</tr>';
		if ( ! $is_inline) {
			echo '<tr class="xml-content' . ($is_render_collapsed ? ' collapsed' : '') . '">';
				echo '<td colspan="2">';
					echo '<table>';
						$this->render_xml_attributes_table($el, $path . '/', $lvl + 1);
						$indexes = array();
						foreach ($el->childNodes as $child) {
							if ($child instanceof DOMElement) {
								empty($indexes[$child->nodeName]) and $indexes[$child->nodeName] = 1;
								$this->render_xml_element_table($child, $shorten, $path . '/', $indexes[$child->nodeName]++, $lvl + 1);
							} elseif ($child instanceof DOMText) {
								$this->render_xml_text_table(trim($child->wholeText), $shorten, $path . '/', $lvl + 1);
							}
						}
					echo '</table>';
				echo '</td>';
			echo '</tr>';
		}
	}
	protected function render_xml_text_table($text, $shorten = false, $path = '/', $lvl = 0, $is_inline = false)
	{
		if (empty($text)) {
			return; // do not display empty text nodes
		}
		$more = '';
		if ($shorten and preg_match('%^(.*?\s+){20}(?=\S)%', $text, $mtch)) {
			$text = $mtch[0];
			$more = '<span class="xml-more">[' . __('more', 'pmxi_plugin') . ']</span>';
		}
		$is_short = strlen($text) <= 40;
		$text = esc_html($text); 
		$text = preg_replace('%(?<!\s)\b(?!\s|\W[\w\s])|\w{20}%', '$0&#8203;', $text); // put explicit breaks for xml content to wrap
		if ($is_inline) {
			echo $text . $more;
		} else {
			echo '<tr class="xml-content-tr textonly lvl-' . $lvl . ($is_short ? ' short' : '') . '" title="' . $path . 'text()">';
				echo '<td style="padding-left:' . ($lvl + 1) * 15 . 'px"><span class="xml-attr-name">text</span></td>';
				echo '<td>' . $text . $more . '</td>';
			echo '</tr>';
		}
	}
	protected function render_xml_attributes_table(DOMElement $el, $path = '/', $lvl = 0)
	{
		foreach ($el->attributes as $attr) {
			echo '<tr class="xml-attr lvl-' . $lvl . '" title="' . $path . '@' . $attr->nodeName . '">';
				echo '<td style="padding-left:' . ($lvl + 1) * 15 . 'px"><span class="xml-attr-name">@' . $attr->nodeName . '</span></td>';
				echo '<td><span class="xml-attr-value">' . esc_attr($attr->value) . '</span></td>';
			echo '</tr>';
		}
	}
	
	protected function xml_find_repeating(DOMElement $el, $path = '/')
	{
		$path .= $el->nodeName;
		if ( ! $el->parentNode instanceof DOMDocument) {
			$path .= '[1]';
		}
		$children = array();
		foreach ($el->childNodes as $child) {
			if ($child instanceof DOMElement) {
				if ( ! empty($children[$child->nodeName])) {
					return $path . '/' . $child->nodeName;
				} else {
					$children[$child->nodeName] = true;
				}
			}
		}
		// reaching this point means we didn't find anything among current element children, so recursively ask children to find something in them
		foreach ($el->childNodes as $child) {
			if ($child instanceof DOMElement) {
				$result = $this->xml_find_repeating($child, $path . '/');
				if ($result) {
					return $result;
				}
			}
		}
		// reaching this point means we didn't find anything, so return element itself if the function was called for it
		if ('/' . $el->nodeName == $path) {
			return $path;
		}
		
		return NULL;		
	}	

	protected function sxml_append(SimpleXMLElement $to, SimpleXMLElement $from) {
	    $toDom = dom_import_simplexml($to);
	    $fromDom = dom_import_simplexml($from);
	    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
	}
	
}
