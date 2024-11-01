<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miTextLineEditor', 'init'));

class miTextLineEditor extends miEditor
{
	static $editor_name = 'text'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}

	public function __construct($name) 
	{
		parent::__construct($name);
 
 
 		 
		$this->fields["text"] = MI()->editors()->getNewField($this->name . '_text', 'text')	
 								;
 								
 		return $this;		 
	}
	
	public function setupData() 
	{
		if (! is_null($this->scope) ) 
			$this->fields['text']->set('scope', $this->scope);
			
		parent::setupData();
	}	

	
		
}//class
