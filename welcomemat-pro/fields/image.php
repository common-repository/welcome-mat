<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/** Image Editor

Allow an user to interact with images, add them to the WordPress library and include the into the template. 

*/
remove_action('maxinbound_register_field', array(__NAMESPACE__ .'\miImageField', 'init'));
add_action('maxinbound_register_field', array(__NAMESPACE__ .'\miImagePROField', 'init'));

class miImagePROField extends miImageField
{
	static $field_name = 'image';

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());
	}

	public function __construct($name) 
	{
		parent::__construct($name); 
		//$this->template = __DIR__ .  '/tpl/image.tpl'; 
	}
	
	public function admin()
	{
		
		parent::admin(); 

		$tpl = simpleTemplate::get_last_object(); 
				
		$toolbar_tpl = __DIR__ .  '/tpl/image.tpl'; 
		
		$default_img =  $this->getImage(''); // force default check
		
		$toolbar = new \stdClass;
		$toolbar->button_text =  __("Select an image", "maxinbound");
		$toolbar->button_none =  __('No Background','maxinbound'); 
		$toolbar->button_default = __('Revert to template default', 'maxinbound'); 
		if ($default_img && $default_img != '') 
			$toolbar->image_default = $default_img; 
		
		$toolbar->name = $tpl->name;
		
		$tpl->toolbar = simpleTemplate::parse($toolbar_tpl, $toolbar); 
		
	 	return simpleTemplate::parse($this->template, $tpl);
	}


} // class
