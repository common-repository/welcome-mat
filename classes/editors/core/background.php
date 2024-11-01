<?php 
namespace MaxInbound;

defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miBackgroundEditor', 'init'));

class miBackgroundEditor extends miEditor
{

	static $editor_name = 'background'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}
	
	public function __construct($name) 
	{
		parent::__construct($name);
			
		$this->fields["image"] = MI()->editors()->getNewField($this->name . '_image', 'image') 
									->set('title', __("Image","maxinbound") )
									->set('scope', 'background-image')
								 ;

 		return $this;		 
	}


	public function admin() 
	{
		$output = parent::admin(); 
		// if there is no value, don't bother to show the background ( aka not in template since there a no function buttons 
		if ($this->fields['image']->get('value') == '') 
			return ''; 
		
		return $output;
	
	}


} // class
