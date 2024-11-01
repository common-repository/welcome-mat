<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');
/*
Plugin Name: Welcome Mat PRO
Plugin URI: https://welcomemat.io
Description: Welcome Mat PRO
Version: 1.7.1
Author: Max Foundry
Author URI: http://maxfoundry.com

Copyright 2019 Max Foundry, LLC (http://maxfoundry.com)
*/

const WMPRO_VERSION_NUM = "1.7.1";
const WMPRO_ROOT_FILE = __FILE__;

require_once('classes/install.php');
require_once('classes/EDD_SL_Plugin_Updater.php');

add_action('plugins_loaded', function () {

	wmpro_install::check_runtime();

	if (wmpro_install::$load_status === true)
	{
		require_once('classes/license.php');
		require_once('classes/wmpro-class.php');
		$wm_license = new wmpro_license();
		$wm_license->update_check();
		$wm_license->check_license();

		$wmpro = wmpro::getInstance();
		$wmpro->setLicense($wm_license);

		if ($wm_license->is_valid() )
		{
			$wmpro->license_construct();

		}

	}

});
