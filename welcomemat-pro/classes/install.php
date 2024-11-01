<?php
namespace MaxInbound;

class wmpro_install
{
	public static $load_status = false; 
	
	public static function check_runtime() 
	{
		if ( version_compare(PHP_VERSION, '5.3', '<' ) ) {
			if (is_admin()) 
			{
				self::required_phpversion(); 
			}
			self::$load_status = false;
			return false;
		}
		
		if (! class_exists('MaxInbound\maxinbound') ) {
			add_action('admin_notices', array('MaxInbound\wmpro_install','required_wm') ); 
			self::$load_status = false;
			return false;
		}
		
		self::$load_status = true;
	}
	
	public static function required_phpversion()
	{

		$message = sprintf( __("Welcome Mat PRO requires at least PHP version 5.3. You are running version: %s ","maxinbound"), PHP_VERSION);
		echo"<div class='error'> <h4>$message</h4></div>"; 
		return; 
	
	}

	public static function required_wm()
	{
		$action = 'install-plugin';
		$slug = 'welcome-mat';
		$url = wp_nonce_url(
			add_query_arg(
				array(
				    'action' => $action,
				    'plugin' => $slug
				),
				admin_url( 'update.php' )
			),
			$action.'_'.$slug
		);	
 
		$message = sprintf(__( "Welcome Mat PRO requires Welcome Mat to function. %s Click here to install %s. ","maxinbound"), 
			"<a href='$url'>", "</a>") ;
		echo"<div class='error'> <h4>$message</h4></div>"; 

		//
	}

} // class
