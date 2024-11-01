<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');
/*

Plugin Name: Welcome Mat - Drip Module
Plugin URI: http://welcomemat.io
Description: Drip Module for Welcome Mat PRO 
Version: 1.0
Author: Max Foundry
Author URI: http://maxfoundry.com

Copyright 2017 Max Foundry, LLC (http://maxfoundry.com)
*/


add_action('maxinbound_register_pro_module', function ($modules) {
	$version = '1.0'; 
	$root_file = __FILE__; 
		
	
	require_once('api/class-getdrip-wp-api.php'); 
	require_once('drip-module.php'); 

	/*$wmpro = wmpro::getInstance(); 
	$license = $wmpro->getLicense(); 
	if ($license)
	{
		$license->check_module_update(
					array('root_file' => $root_file,
						  'version' => $version, 
						  'product_name' => 'Drip Module', 
					)
		); 
							
	} */
	

});

