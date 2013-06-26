<?php

function pmxi_pre_set_site_transient_update_plugins($new_option) {
	$raw_response = wp_remote_get(PMXI_Plugin::getInstance()->getOption('info_api_url') . '?version=1&download_link=1');
	if ( ! is_wp_error($raw_response) and 200 == $raw_response['response']['code']) {
		$info = unserialize($raw_response['body']);
		if (PMXI_Plugin::getInstance()->getVersion() != $info->version) {
			$plugin_basename = plugin_basename(PMXI_Plugin::FILE);
			$new_option->response[$plugin_basename] = (object)array(
				'slug' => 'PMXI_Plugin',
				'new_version' => $info->version,
				'url' => PMXI_Plugin::getInstance()->getPluginURI(),
				'package' => $info->download_link,
			);
		}
	}
	
	return $new_option;
}