<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_field', array(__NAMESPACE__ . '\miCustomField', 'init'));


class miCustomField extends miField
{
	static $field_name = 'custom';
	protected $content;
	

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());	
	}
 
	public function __construct($name) 
	{
		$this->template = __DIR__ .  '/tpl/custom.tpl'; 
		parent::__construct($name); 
		return $this; 
	}
	
	public function admin() 
	{
		$tpl = new \stdClass;	
		$tpl->title = $this->title; 
		$tpl->content = $this->content;	
		$tpl->name = $this->name;
		
		return simpleTemplate::parse($this->template, $tpl);
	}
	
	
} // class

