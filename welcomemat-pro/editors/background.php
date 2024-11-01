<?php
namespace MaxInbound;

defined('ABSPATH') or die('No direct access permitted');

remove_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miBackgroundEditor', 'init'));
add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miBackgroundProEditor', 'init'));


class miBackgroundProEditor extends miBackgroundEditor
{

	public function __construct($name) 
	{
		parent::__construct($name);
		
		$this->fields["color"] = MI()->editors()->getNewField($this->name . '_backgroundcolor', 'color')
								 	->set('scope', 'background-color')
								 	->set('title', __('Background color', 'maxinbound') )
									;
 
							
		return $this;
	}
	
	public function admin()
	{
		
		return miEditor::admin(); 
		
	
	}


}
