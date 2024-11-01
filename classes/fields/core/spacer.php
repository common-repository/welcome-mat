<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_field', array(__NAMESPACE__ . '\miSpacerField', 'init'));
use MaxInbound\simpleTemplate as simpleTemplate;

/** Multi-purpose styling tool for layouts **/
class miSpacerField extends miField
{

	static $field_name = 'spacer';
	protected $type = 'spacer'; 
	protected $content = '';

	public function __construct($name) 
	{
		$this->template = __DIR__ .  '/tpl/spacer.tpl'; 
		parent::__construct($name); 
		
		// defaults
		$this->space = 10; 
		
		return $this; 
	}
	
	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());	
	}
	
	public function admin() 
	{
		$tpl = new \stdClass;

		$tpl->space = $this->space; 
		$tpl->type =  $this->type; 
		$tpl->content = $this->content;
		//$tpl->title = $this->title; 
		//$tpl->value = '#000000'; 
																	
		return simpleTemplate::parse($this->template, $tpl);
	}	

}
