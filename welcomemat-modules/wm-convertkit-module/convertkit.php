<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');
/*

Plugin Name: Welcome Mat - Convertkit Module
Plugin URI: http://welcomemat.io
Description: Convertkit Module for Welcome Mat PRO
Version: 1.0
Author: Max Foundry
Author URI: http://maxfoundry.com

Copyright 2017 Max Foundry, LLC (http://maxfoundry.com)
*/

require_once('api/base.php');
require_once('api/the_interface.php');

require_once('api/subscriber.php'); 
require_once('api/forms.php'); 
require_once('api/sequences.php'); 


add_action('maxinbound_register_pro_module', function ($modules) {
	require_once('convertkit-module.php'); 

	$version = '1.0'; 
	$root_file = __FILE__; 
	
	/*
	$wmpro = wmpro::getInstance(); 
	$license = $wmpro->getLicense(); 
	if ($license)
	{
		$license->check_module_update(
					array('root_file' => $root_file,
						  'version' => $version, 
						  'product_name' => 'ConvertKit Module', 
					)
		); 
							
	}
	*/
});

