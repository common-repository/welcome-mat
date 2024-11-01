<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miModules
{
	protected $current_template = null; 
	private static $instance; 
	protected $modules = null;
	protected $screens = array(); 

	static function getInstance() 
	{
		if (is_null(self::$instance)) 
		{
			$inst = new miModules();
			self::$instance = $inst;
			if (is_null(self::$instance))
				die("Fatal error, Modules instance null after init");
		}
		
		return self::$instance;	
	}
	
	public function __construct() 
	{
	}
	
	public function loadModules() 
	{
		if (! is_null($this->modules)) 
			return; // already loaded. 
			
		$paths = MI()->collect('system/modules_paths'); 
		//if (! is_array($paths)) // this should not err - since core path should be loaded anyhow
		//	return false;
		if (! is_array($paths)) 
			$paths = array();
		
		$core_path = array(MI()->get_plugin_path() . 'modules/');
		$paths = array_merge($core_path, $paths);
		
		foreach($paths as $path)
		{

			$dir_iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
			$iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);	
			$regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::MATCH);

			foreach( $regex as $fileinfo) 
			{
				$filename = $fileinfo->getFileName(); 
				if (substr($filename, 0,1) == '_')  // exclude files starting with _
					continue; 
				$file = $fileinfo->getPathName(); 

				include( $file ) ; 
			}
		}
			
			$this->load();
	}
	
	public function getModules() 
	{
		if (is_null($this->modules)) 
			$this->loadModules();
		
		
		return $this->modules; 
	}
	
	public function load() 
	{
		do_action('maxinbound_register_module', $this);
		do_action('maxinbound_register_screen', $this); 
		MI()->listen('screens_done', array($this, 'addScreens')); // after adding the screens, add them in WP
		//MI()->offer('system/settings-page', array($this, 'module_manager')); 
	}
	
	public function registerScreen($key, $name, $function) 
	{
		$this->screens[$key] = array("name" => $name, 
									"function" => $function,
									"active" => false,
									"module" => null,  
								); 
	}
	
	public function unRegisterScreen($key) 
	{

		if (isset($this->screens[$key])) 
		{
			unset($this->screens[$key]); 
			return true; 
		}
		
		return false;
	}

	public function addScreens() 
	{
		$main = MI()->ask('parent-menu-slug'); 

		foreach($this->screens as $key => $screen)
		{	
 
			if (! isset($screen['name']) || ! isset($screen['function'])) 
			{
				if (! isset($screen['name'])) 
					MI()->errors()->add( new \Exception('Screen ' . $key . ' name not found') );
				else
					MI()->errors()->add( new \Exception('Screen ' . $key . ' function not found') );					
				continue;
	 		}
			$title = $screen["name"];
			$function =  $screen["function"];  

			if ($screen["active"])
			{
				add_submenu_page($main, MI()->ask('system/nice_name') . ' : ' . $title,  $title, 'edit_posts', $key, $function);
			}
		}	
	}
	
	/** Check if the module screen is active. 
	*
	*	Checks the current screen by id. ID is compromised of post_type _page_ name of module right now. This function is useful checking
	* 	if specific resources, like JS and CSS should be included to keep the loading clean.
	*
	*	@param string screen_id Same name as used by register screen in the screen
	*	@return boolean True or False
	*/
	public function is_screen_active($screen_id)
	{
		$wp_screen = get_current_screen(); 
		
		$post_type = MI()->ask('system/post_type'); 
		$base = '_page_'; 
		
		$combined_id = $post_type . $base . $screen_id; 
		if ($combined_id == $wp_screen->id) 
			return true;
		else
			return false;
	}
	
	/** Attach a screen to a module class 
	*
	*	@param $module Class A Module Class to attach screen to 
	* 	@param $screen String The name of the registered screen to attach 
	*/ 
	public function attachScreen($module, $screen)
	{	
		// Screen might not exist if not registered properly or it's unregistered
		if (isset($this->screens[$screen]))
		{
			$this->screens[$screen]['active'] = true; 
			$this->screens[$screen]['module'] = $module; 
		}
	}
	
	/** Screen objects can get their underlying module for requesting core data */ 
	public function getAttachedScreen($screen) 
	{
		MI()->tell('screen-' . $screen); 

		$module = $this->screens[$screen]['module'];

		return $module; 
	}
	
	/** Output custom page header 
	* 
	*
	*/
	public function header($args = array())
	{
		$defaults = array( 
			"title" => '',
			"mainclass" => 'modules', 
		);
		
		$args = wp_parse_args($args, $defaults); 
		extract($args); 
		
		$path = MI()->get_plugin_path() . 'admin/module-header.php'; 
 
		if (file_exists($path))
		{
			include($path);
		}
	}
	
	/** Output custom page footer
	*
	*
	*/
	public function footer() 
	{
		$path = MI()->get_plugin_path() . 'admin/module-footer.php'; 			
		if (file_exists($path))
		{
			include($path);
		}		
	}
	
	public function register($name, $class)
	{
		$this->modules[$name] = new $class; 

	}

	public function module_manager() 
	{
		ob_start(); 
		include(MI()->get_plugin_path() . '/admin/settings-modules.php'); 
		$output = ob_get_contents(); 
		ob_end_clean(); 
		
		return array('page' => 'modules', 
					 'content' => $output );
	}
		
	public function flush_transients() 
	{
		$modules = $this->getModules();

		foreach($modules as $module) 
		{
			$module->flush_transients();	
		}
	
	}
		
} // class	
