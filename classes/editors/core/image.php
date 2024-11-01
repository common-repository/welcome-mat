<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

// Image editor


add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miImageEditor', 'init'));

class miImageEditor extends miEditor
{

	static $editor_name = 'image'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}
	
	public function __construct($name) 
	{
		parent::__construct($name);
			
		$this->fields["img"] = MI()->editors()->getNewField($this->name . '_src', 'image')
									->set('title', __("", "maxinbound")
									 )
								;

		
		/*$this->panels["alt"] = (new miTextField($this->name . '_alt')) 
									->set('title', __("Image Title","maxinbound") )
								; 
		*/
	}


}
