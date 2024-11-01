<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miDatabase
{
	static $last_sql = ''; 
	static $doing_dbase_check = false; 

	public static function query($sql, $format = OBJECT)
	{
		global $wpdb;
			
		self::$last_sql = $sql;
		$results = $wpdb->get_results($sql, $format);

		self::checkError(); 
		return $results;		
	}

	public static function getvar($sql) 
	{
		global $wpdb; 
		self::$last_sql = $sql; 
		
		$value = $wpdb->get_var($sql); 
		
		self::checkError();
		return $value; 
	}

	public static function checkError() 
	{
		global $wpdb; 
		
		if ($wpdb->last_error && self::$doing_dbase_check === false) 
		{	
			self::$doing_dbase_check = true;
			MI()->errors()->add(new \Exception('' . $wpdb->last_error . ' SQL - ' . self::$last_sql));
			MiInstall::check_database(); 
			return false; 
			
		}	
	}

	/* Get posts from WP 
	*
	*  Central function to query posts with the correct post type. 
	*/
	public static function posts() 
	{
		$args = array( 
					"post_type" => MI()->ask('system/post_type'),
		);
	
		$posts = get_posts($args); 
		return $posts;	
	
	}

}
