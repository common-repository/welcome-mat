<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miEditors
{
	static $instance = null;
	static $default_editor = 'text'; // failback if editor not found 
	static $default_field = 'text'; 
	
	protected $editors = array(); 
	protected $fields = array(); 
	protected $core_path = array();
	
	protected $active_editors = array(); 
						
	static function getInstance() 
	{
		if (is_null(self::$instance)) 
		{
			$inst = new miEditors();
			self::$instance = $inst;
			if (is_null(self::$instance))
				die("Fatal error, editors instance null after init");
		}
		
		return self::$instance;	
	}
	
	protected function __construct() 
	{
		$path =	MI()->get_plugin_path(); 
		$this->core_path = array(
			$path . 'classes/editors/core/', 
			$path . 'classes/fields/core/', 
		);	

		$this->loadEditors();
	}
	
	public function registerEditor($name, $class) 
	{
 
		$this->editors[$name] = $class;
	}
	
	public function registerField($name, $class)
	{
		$this->fields[$name] = $class;
	}
	
	public function getFieldClass($name) // get a field class by the registered name 
	{
		if (isset($this->fields[$name])) 
			return $this->fields[$name]; 
		else
			throw new \Exception('Field ' . $name . ' not found'); 
	}
	
	/* Load the core files */
	protected function loadEditors() 
	{
		//$path = apply_filters('maxinbound_file_paths', $this->core_path); 
		$editor_paths = MI()->collect('system/editors_paths'); 
		$paths = array(); 

		foreach($editor_paths as $ed_paths) 
		{
			if (is_array($ed_paths)) 
				$paths = array_merge($paths, $ed_paths); 
			else
				$paths[] = $ed_paths; 
		}
		
		$core_path = $this->core_path; 
		$paths = array_merge($core_path, $paths); 

		$this->loadFromPath($paths);	

		do_action('maxinbound_register_editor', $this); 
		do_action('maxinbound_register_field', $this);
		 
	}
	
	public function view() 
	{
		foreach($this->active_editors as $editor)
		{
			$editor->view(); 
		
		}
	}
	
	public function admin($editor_name = '') 
	{
		$output = ''; 
		
		if ($editor_name == '') 
		{
			foreach($this->active_editors as $editor) 
			{
				$output .= $editor->admin();
			}
		}
		else
		{
			$editor = $this->active_editors[$editor_name]; 
			if (is_object($editor)) 
				$output .= $editor->admin(); 	
		}	
		
		return $output;
	}

	protected function loadFromPath($paths) // load classes from certain paths. 
	{
		foreach($paths as $path)
		{
			$files = scandir($path); 
			foreach($files as $f) 
			{
				if ($f != '.' && $f != '..') 
				{
					if (strpos($f,'.php') !== false)
					{
						require_once($path . $f);
					}
				}
			}
  
		}
	}
	
	/** Get a new field instance. Returns class instance */ 
 	public function getNewField($instance_name, $field_id) 
 	{
 		try
 		{
			if (! isset($this->fields[$field_id])) 
			{
				throw new \Exception('Field (' . $field_id . ' for ' . $instance_name . ') not found');		
			}	
				//echo " NEW FIELD EDITORS - $field_id - $instance_name <BR>";
				$field = new $this->fields[$field_id]($instance_name);
				//print_R($field);
				return $field;
		}
		catch (\Exception $e) { 
				MI()->errors()->add($e);  
				
				$field = new $this->fields[static::$default_field]($instance_name); 
				return $field;
		} 
 	} 
 	
 	/** Name is name of editor, field_name is name of the (template) field instance being represented by the editor */ 
	public function getNew($name, $field_name)
	{
 		try
 		{
			if (! isset($this->editors[$name])) 
			{
				
				throw new \Exception('Editor (' . $name . ' for ' . $field_name . ') not found');		
			}	

				$editor = new $this->editors[$name]($field_name);
				$this->active_editors[$field_name] = $editor; // not sure if this causes overwriting
				return $editor;
		}
		catch (\Exception $e) { 
				MI()->errors()->add($e);  
				$editor = new $this->editors[static::$default_editor]($field_name); 
				$this->active_editors[$field_name] = $editor; 
				return $editor;
		} 
 
	}
	
	public function all() 
	{
		return $this->active_editors; 
	}

	
} //class
