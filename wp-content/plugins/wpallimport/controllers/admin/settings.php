<?php 
/**
 * Admin Statistics page
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXI_Admin_Settings extends PMXI_Controller_Admin {
	
	public function index() {
		$this->data['post'] = $post = $this->input->post(PMXI_Plugin::getInstance()->getOption());
		
		if ($this->input->post('is_settings_submitted')) { // save settings form
			check_admin_referer('edit-settings', '_wpnonce_edit-settings');
			
			if ( ! preg_match('%^\d+$%', $post['history_file_count'])) {
				$this->errors->add('form-validation', __('History File Count must be a non-negative integer', 'pmxi_plugin'));
			}
			if ( ! preg_match('%^\d+$%', $post['history_file_age'])) {
				$this->errors->add('form-validation', __('History Age must be a non-negative integer', 'pmxi_plugin'));
			}
			if (empty($post['html_entities'])) $post['html_entities'] = 0;
			if (empty($post['utf8_decode'])) $post['utf8_decode'] = 0;
			
			if ( ! $this->errors->get_error_codes()) { // no validation errors detected

				PMXI_Plugin::getInstance()->updateOption($post);
				$files = new PMXI_File_List(); $files->sweepHistory(); // adjust file history to new settings specified
				
				wp_redirect(add_query_arg('pmxi_nt', urlencode(__('Settings saved', 'pmxi_plugin')), $this->baseUrl)); die();
			}
		}
		
		if ($this->input->post('is_templates_submitted')) { // delete templates form
			$templates_ids = $this->input->post('templates', array());
			if (empty($templates_ids)) {
				$this->errors->add('form-validation', __('Templates must be selected', 'pmxi_plugin'));
			}
			if ( ! $this->errors->get_error_codes()) { // no validation errors detected
				$template = new PMXI_Template_Record();
				foreach ($templates_ids as $template_id) {
					$template->clear()->set('id', $template_id)->delete();
				}
				wp_redirect(add_query_arg('pmxi_nt', urlencode(sprintf(_n('%d template deleted', '%d templates deleted', count($templates_ids), 'pmxi_plugin'), count($templates_ids))), $this->baseUrl)); die();
			}
		}
		
		$this->render();
	}
	
	/**
	 * upload.php
	 *
	 * Copyright 2009, Moxiecode Systems AB
	 * Released under GPL License.
	 *
	 * License: http://www.plupload.com/license
	 * Contributing: http://www.plupload.com/contributing
	 */
	public function upload(){	
		
		// HTTP headers for no cache etc
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		// Settings
		//$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
		$uploads = wp_upload_dir();	

		$targetDir = $uploads['path'];

		if (! is_dir($targetDir) || ! is_writable($targetDir))
			exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 100, "message" => "Uploads folder is not writable."), "id" => "id")));

		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// 5 minutes execution time
		@set_time_limit(5 * 60);

		// Uncomment this one to fake upload time
		// usleep(5000);

		// Get parameters
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
		$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

		// Clean the fileName for security reasons
		$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

		// Make sure the fileName is unique but only if chunking is disabled
		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);

			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
				$count++;

			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}

		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

		// Create target dir
		if (!file_exists($targetDir))
			@mkdir($targetDir);

		// Remove old temp files	
		if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
					@unlink($tmpfilePath);
				}
			}

			closedir($dir);
		} else
			exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 100, "message" => "Failed to open temp directory."), "id" => "id")));
			

		// Look for the content type header
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = $_SERVER["CONTENT_TYPE"];

		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
				// Open temp file
				$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen($_FILES['file']['tmp_name'], "rb");

					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else
						exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 101, "message" => "Failed to open input stream."), "id" => "id")));
					fclose($in);
					fclose($out);
					@unlink($_FILES['file']['tmp_name']);
				} else
					die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
			} else
				exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 103, "message" => "Failed to move uploaded file."), "id" => "id")));
		} else {
			// Open temp file
			$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else
					exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 101, "message" => "Failed to open input stream."), "id" => "id")));

				fclose($in);
				fclose($out);
			} else
				exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 102, "message" => "Failed to open output stream."), "id" => "id")));
		}

		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
			// Strip the temp .part suffix off 
			rename("{$filePath}.part", $filePath); chmod($filePath, 0755);
		}
		
		// Return JSON-RPC response
		exit(json_encode(array("jsonrpc" => "2.0", "result" => null, "id" => "id", "name" => $filePath)));

	}		

	public function download(){
		PMXI_download::csv(PMXI_Plugin::ROOT_DIR.'/logs/'.$_GET['file'].'.txt');
	}	
}