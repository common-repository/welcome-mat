<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miMetaBoxEditor', 'init'));

// Metabox editor is a special editor used for designing and outputting metaboxes in the template editor
class miMetaBoxEditor extends miEditor
{
	static $editor_name = 'metabox'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}

	public function __construct($name) 
	{
		parent::__construct($name);
		$this->template = __DIR__ . "/tpl/metabox.tpl";
		return $this;
	} 	
	
	public function addField($field_name, $field_object)
	{
 
		$this->fields[$field_name] = $field_object;
	}

	/* Only displays all the fields added - not the template mumbo of a regular editor */	
	public function admin() 
	{
	 	$tpl = new \stdClass; 	 
	 	
	 	if(isset($this->title) && $this->title != '')
			$tpl->label = $this->title; 
	
	 	$tpl->name = $this->name; 	 	
	 	$tpl->editor_name = static::$editor_name; 
	 	$tpl->content = ''; 
	 	
		foreach($this->fields as $field)
		{
 
			$tpl->content .= $field->admin(); 
		}

	 	$output = simpleTemplate::parse($this->template, $tpl);		
		return $output;	
	
	}
	
	public function view()
	{
		// no views for metaboxes. 
	}

} // class
