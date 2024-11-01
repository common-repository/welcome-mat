<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miInstall
{

	public static function activate() 
	{
		MI()->modules(); // init modules
		MI()->modules()->getModules();
		self::database(); 

		$slug = self::get_plugin_slug();						
		delete_transient($slug . '_structure'); 

	}
	
	public static function deactivate() 
	{
	
	}
	
	
	/** Try to install require custom database tables 
	*
	*  Custom tables are needed for modules mostly, not for 'base functions' of the plugin which uses WP posts. 
	**/
	public static function database() 
	{
		$results = MI()->collect('database/install'); 
 		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		foreach($results as $sql)
		{
			try{
				$result = dbDelta($sql); // dbDelta doesn't error on error :( 
			}			
			catch(Exception $e)
			{
				MI()->errors()->add($e);  
			}
		}
		
		if (MI()->errors()->hasErrors()) 
		{
			MI()->errors()->admin_notice(); 
			return false; 
		}	

		return true; 
	}
	
	public static function check_database() 
	{
		$results = MI()->collect('database/check'); 

		foreach($results as $index => $result)
		{
			if ($result === false) // if any check comes back false, run the database table creation procedure. 
			{
				self::database(); 
				return; 
			}
		}

	}
	
	/** Check if the plugin has been updated **/ 
	public static function check_version() 
	{
		$prefix = MI()->ask('system/slug'); 
		$db_version = get_option($prefix . '_version');  // version from database, the 'last known' installed version
		$this_version = MI()->ask('system/version');  // the actual constant version

		$slug = self::get_plugin_slug();						
		$update = get_transient($slug . '_structure'); 
		if (! $update)
		{
			self::gather_structure(); 
		}
		
		// check if current (database) version is lower than system version. 
		if(version_compare($db_version, $this_version, '<') )
		{
			self::check_version_update($db_version); 	
			self::database(); // run dbase init scripts to reflect updates
			self::gather_structure(); // for uninstall
			update_option($prefix . '_version', $this_version);
		}

	}
	
	public static function check_version_update($db_version) 
	{
		global $wpdb; 
		
		// 0.9.7 change :: moved static prefix mi_xx to plugin class prefix.
		if ( version_compare($db_version,'0.9.7', '<') )
		{
			 $prefix = MI()->ask('system/option-prefix'); 
			 $old_val = 'mi_module_'; 
			 
			 $like = $wpdb->esc_like($old_val);
			 $like = '%' . $like . '%'; 
			 $sql = "UPDATE " . $wpdb->postmeta  . " set meta_key = REPLACE(meta_key, %s, %s) where 
			 		 meta_key like %s";
			 $sql = $wpdb->prepare($sql, $old_val, $prefix, $like); 
			 $wpdb->query($sql); 
			 
			 $sql = "UPDATE " . $wpdb->options . "  set option_name = REPLACE(option_name, %s, %s) where 
			 		 option_name like %s";
			 $sql = $wpdb->prepare($sql, $old_val, $prefix, $like);				 
			 $wpdb->query($sql); 
			 
		}

		// convert old template names to the final ones.
		if ( version_compare($db_version,'0.9.9', '<') )
		{
			global $wpdb; 
			
			$templates = array('atomic' => 'clean',
							'balloon' => 'air', 
							'brand' => 'agency', 
							'glass' => 'shine', 
							'prosper' => 'offer',
						); 
			$sql = " SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key = '_maxinbound_template' "; 
			 
			$results = $wpdb->get_results($sql); 
 
			foreach($results as $result) 
			{
				$post_id = $result->post_id; 
				$template = $result->meta_value; 
				
				if ( isset($templates[$template]) )
				{
					$new_template = $templates[$template]; 
 
					update_post_meta($post_id, '_maxinbound_template', $new_template);
				}
			}
 
		}
	}
	
	public static function get_plugin_slug() 
	{
		return dirname(plugin_basename(WM_ROOT_FILE));
	}	
	
	/** Collect relevant data from the plugin ( custom tables, other information ) that will be used for uninstalling the plugin and deleting it's data */
	public static function gather_structure()
	{
		$modules = MI()->modules()->getModules();
		$struct = array(
			'post_type' => MI()->ask('system/post_type'), 
			'option_prefix' => MI()->ask('system/option-prefix'),
			'system_slug' => MI()->ask('system/slug'), 
			'modules' => array(),
			'tables' => array(), 
			'transients' => array(), 
			); 
			
		foreach($modules as $name => $moduleObj)
		{
			$class = get_class($moduleObj);
			$struct['modules'][] = $name; 
			
			if (isset($class::$table_name)) 			
			{
				$struct['tables'][] = $class::$table_name; 
			}
			
			$transients = $moduleObj->get_transients(); 

			if (count($transients) > 0)
				$struct['transients'] = array_merge($struct['transients'], $transients);
		}

		$slug = self::get_plugin_slug();		
		update_option($slug . '_uninstall', $struct); 
		$prefix = MI()->ask('system/slug'); 
		set_transient($slug . '_structure', true, WEEK_IN_SECONDS); 	
	}
	
	/** Remove data function. This will attempt to delete ALL data connected to the plugin. **/
	public static function uninstall() 
	{
		$slug = self::get_plugin_slug();
		
		$remove_data = get_option($slug . '_remove_data'); 
		$struct = get_option($slug . '_uninstall');
		
		if ($remove_data != 'delete') 
			return; // fail-safe
		
		if ($remove_data === 'delete') 
		{
			global $wpdb; 
			$post_type = $struct['post_type'];
			$option_prefix = $struct['option_prefix']; 
			
			
			$sql = ' SELECT ID from ' . $wpdb->posts . ' WHERE post_type = %s'; 
			$sql = $wpdb->prepare($sql, $post_type); 
			$post_ids  = $wpdb->get_col($sql);
			
			$replace_array = implode( ', ', array_fill( 0, count( $post_ids ), '%d'));
			
			
			$sql = 'DELETE FROM ' . $wpdb->postmeta . ' WHERE post_id IN ( ' . $replace_array . ' ) '; 
			$sql = $wpdb->prepare($sql, $post_ids);
			$wpdb->query($sql);
			
			$sql = ' DELETE FROM ' . $wpdb->posts . ' WHERE post_type = %s'; 
			$sql = $wpdb->prepare($sql, $post_type); 		
			$wpdb->query($sql);
			
			if ( isset($struct['modules']))
			{
				$modules = array_filter($struct['modules']); 
				
				foreach($modules as $index => $module)
				{
					
					$sql = 'DELETE FROM ' . $wpdb->options . ' WHERE option_name = %s'; 
					$sql = $wpdb->prepare($sql, $option_prefix . $module); 
					$wpdb->query($sql);
				
				}
			}
			
			if (isset($struct['tables'])) 
			{
				$tables = array_filter($struct['tables']); 
				foreach($tables as $index => $table) 
				{
					$sql = "DROP TABLE " . $table; 
					$wpdb->query($sql);
				}
			}
			
			if (isset($struct['transients'])) 
			{
				$transients = $struct['transients']; 
				foreach($transients as $transient) 
				{
					delete_transient($transient);
				}
			}
			
			MI()->tell('system/uninstall_delete');
						
		}

		$system_slug = $struct['system_slug']; 
		delete_option($system_slug . '_version');
		delete_option($slug . '_remove_data');
		delete_option($slug . '_uninstall'); 
	}

	
	public function simplexml_notloaded() 
	{
		$message = __("PHP Module SimpleXML not install or active. This is required for Welcome Mat plugin. The plugin will not work.","maxinbound") ;
		echo"<div class='error'> <h4>$message</h4></div>"; 
		
	}
}

register_activation_hook(WM_ROOT_FILE, array('MaxInbound\miInstall', 'activate')); 
register_deactivation_hook(WM_ROOT_FILE, array('MaxInbound\miInstall', 'deactivate')); 
register_uninstall_hook(WM_ROOT_FILE, array('MaxInbound\miInstall','uninstall'));
