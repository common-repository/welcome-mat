<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_field', array(__NAMESPACE__ . '\miRadiusField', 'init'));

class miRadiusField extends miField
{
	static $field_name = 'radius'; 
	
	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());	
	}
 
	public function __construct($name) 
	{
		$this->template = __DIR__ .  '/tpl/icon.tpl'; 
		parent::__construct($name); 
	
		return $this; 
	}
	
	public function admin() 
	{
		$tpl = new \stdClass;

		$tpl->name = $this->name; 
		$tpl->inputclass = 'check_button'; 
				
		$value = $this->value; 
		
		$options = array('square', 'rounded', 'round'); 
		$option_array = array(); 
		foreach($options as $option) 
		{
			$checked = ($option == $value) ? ' checked ' : false;
			$option_array[$option] = array('checked' => $checked, 
										    'item' => "border-radius $option",
										    'id' => $this->name . '_' . $option, 
									  );
								
		}
		
		$tpl->icons = $option_array; 
		$tpl->checked = $value; 							
		
	 	return simpleTemplate::parse($this->template, $tpl);
	 	
	}

	public function view($domObj)
	{
	
	}

}
