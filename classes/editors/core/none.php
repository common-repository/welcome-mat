<?php 
// Placeholder class for templates that need an empty parent and define fields by the children ( in case of scope e.g. ) . 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miNoneEditor', 'init'));

class miNoneEditor extends miEditor
{
	static $editor_name = 'none'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}

 
	
		
}//class
