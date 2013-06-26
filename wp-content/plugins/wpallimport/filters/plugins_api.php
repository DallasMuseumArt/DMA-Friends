<?php

function pmxi_plugins_api($res, $action, $args)
{
	if (false === $res and 'plugin_information' == $action and isset($args->slug) and 'PMXI_Plugin' == $args->slug) {
		$raw_response = wp_remote_get(PMXI_Plugin::getInstance()->getOption('info_api_url'));
		if ( is_wp_error($raw_response) ) {
			$res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.'), $raw_response->get_error_message() );
		} elseif (200 == $raw_response['response']['code']) {
			$res = unserialize($raw_response['body']);
		}
		if (false === $res) {
			$res = new WP_Error('plugins_api_failed', __('An unknown error occurred.'), $raw_response['body']);
		}
	}
	return $res;
}