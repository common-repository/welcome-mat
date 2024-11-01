<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ . '\moduleRemove', 'init'));

class moduleRemove extends miModule
{
	static $name = 'plugin_removal'; 
	
	public static function init($modules) 
	{
		$modules->register(self::$name, get_called_class() );	

	}

	public function __construct()
	{
		parent::__construct(); 
		
		MI()->offer('system/settings-page', array($this, 'settings_page'), array(), 90 );
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		MI()->tell('settings/advanced/title', __('Advanced','maxinbound') );
		MI()->tell('settings/advanced/icon', 'warning');
	
		MI()->listen('system/uninstall', array($this, 'check_delete') ); 
	}
	
	
	public function settings_page() 
	{
		$settings = $this->get_settings(); 
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl'; 


		$tpl = new \stdClass;
		$tpl->title = __('Plugin Removal','maxinbound'); 
		$tpl->name = 'stats'; 
		
		$do_remove = ( isset($settings['do_remove']) && $settings['do_remove'] === 1)  ? true : false; 
		
		
		ob_start(); 
		include('_removal-settings.php');

		$tpl->content = ob_get_contents(); 
		
		ob_end_clean(); 

		$output = simpleTemplate::parse($template, $tpl);		
		return array('page' => 'advanced', 
					 'priority' => 99,
					 'content' => $output,
		);
		
	}	

	public function save_settings($post) 
	{
		
		$settings = $this->get_settings(); 
		
		$do_remove = isset($post['plugin_remove_data']) ? intval($post['plugin_remove_data']) : 0; 

		$settings = array('do_remove' => $do_remove) ; 
		
		$this->update_settings($settings);
		$this->check_delete();
	}
	
	
	public function check_delete() 
	{
		$settings = $this->get_settings(); 
		
		
		$do_remove = isset($settings['do_remove']) ? $settings['do_remove'] : false; 
		$slug = miInstall::get_plugin_slug();   
		delete_option($slug . '_remove_data'); 

		if ($do_remove === 1) 
		{
			if ($do_remove <> 1 ) 
			{
				return; // fail-safe
			}

			miInstall::gather_structure();
			update_option($slug . '_remove_data', 'delete', false);	
		}

	}



}
