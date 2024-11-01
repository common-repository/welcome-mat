<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/**  Font Editor
* 
* This editor display various properties of a typical font setting and allows them to be changed.
*/
add_action('maxinbound_register_field', array(__NAMESPACE__ .'\miIconField', 'init'));


class miIconField extends miField
{
	static $field_name = 'icon';
	
	protected $iconset = 'default'; 
	protected $icon_font = 'dashicons'; 
	
	
	public function __construct($name) 
	{
		$this->template = __DIR__ .  '/tpl/icon.tpl'; 
		parent::__construct($name); 
	
		$this->title = __('Icon', 'maxinbound');
		return $this;	
	}
	
	/** Temporary function. In time this should be expanded to a interface to select any icon from a big set **/
	public function loadIcons() 
	{
		if ($this->iconset == 'default')
		{
		
			$icons = array( 
					'arrow-down' => 'dashicons-arrow-down-alt2', 
				//	'arrow-down2' => 'dashicons-arrow-down', 
					'cross' => 'dashicons-no',
					'arrow-down-alt' => 'dashicons-arrow-down-alt',
					'yes' => 'dashicons-yes',
					
			);
			
			$icon_titles = array(
					'arrow-down' => __('Arrow Down','maxinbound'), 
					'cross' => __('Cross', 'maxinbound'), 
			); 
			
			$this->icon_font = 'dashicons'; 
		}
		if ($this->iconset == 'social') 
		{
			
			$icons = array(
					'facebook' => 'fa-facebook',
					'twitter'  => 'fa-twitter', 
					'instagram' => 'fa-instagram', 
					'thumblr' => 'fa-tumblr', 
					'flickr' => 'fa-flickr', 
					'googleplus' => 'fa-google-plus', 
					'vimeo' => 'fa-vimeo', 
					'wordpress' => 'fa-wordpress', 
					'youtube' => 'fa-youtube', 
					'reddit' => 'fa-reddit', 
					
			); 
			$icon_titles = array(
					'facebook' => __('Facebook', 'maxinbound'), 
					'twitter' => __('Twitter', 'maxinbound'), 
					'instagram' => __('Instagram', 'maxinbound'), 
					'tumblr' => __('Thumblr' ,'maxinbound'), 
					'flickr' => __('Flickr', 'maxinbound'), 
					'googleplus' => __('Google Plus', 'maxinbound'), 
					'vimeo' => __('Vimeo', 'maxinbound'), 
					'wordpress' => __('WordPress', 'maxinbound'), 
					'youtube' => __('Youtube', 'maxinbound'), 
					'reddit' => __('Reddit', 'maxinbound'),
			); 
			
			ksort($icons);
			$this->icon_font = 'fa'; 
		}
		
		
		return array($icons, $icon_titles);
	}
	
	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());	
	}
		
	 public function admin() {
		$tpl = new \stdClass;

		$tpl->name = $this->name; 
		$tpl->inputclass = $this->inputclass; 
		
		if ($this->title != '') 
			$tpl->title = $this->title; 

		list($icons, $icon_titles) = $this->loadIcons();
		$value = $this->value; 

		if ($this->icon_font == 'fa') 
		{
			$sysslug = MI()->ask('system/slug');
			wp_enqueue_style($sysslug . '-font-awesome'); 
		}
		
		$search_class = explode(' ', $value); // get the class which defined the icon
		
		foreach($search_class as $class)
		{
			if (in_array($class, $icons)) // only match for potentials in icons list
			{
				$pattern = "/$class/i";
					
				$match = preg_grep($pattern, $icons);
			
				if (count($match) > 0) 
				{
					$value = key($match); 
					break;
				}
			}
		}

		$icons_assoc = array(); 
		
		foreach($icons as $key => $icon)
		{
			if ($key == $value){
				$icons_assoc[$key]['checked'] = 'checked'; 
			}
			else
			{
				$icons_assoc[$key]['checked'] = ''; 
			}
			$icons_assoc[$key]['id'] = $tpl->name . '_' . $key; 
			$icons_assoc[$key]['item'] = $this->icon_font . ' ' . $icon;
			if (isset($icon_titles[$key]))
				$icons_assoc[$key]['title'] = $icon_titles[$key]; 
		}
	
		$tpl->icons = $icons_assoc;	
		$tpl->checked = $value;

	 	return simpleTemplate::parse($this->template, $tpl);
 
	 }
	 
	public function view($domObj)
	{
		$value = $this->value; 
 		
 		list($icons, $icon_titles) = $this->loadIcons();

 		
 		if (isset($icons[$value])) 
 		{

 			$class = $domObj->class; 
 			foreach($icons as $icon)
 			{
 				$class = str_replace($icon,'', $class);  // removing existing values.
 			}

 			$domObj->class = $class . ' ' . $this->icon_font . ' ' . $icons[$value]; 
 			 			
 			if ($this->icon_font == 'dashicons') 	
 				wp_enqueue_style( 'dashicons' ); // load dashicons on front
	 		if ($this->icon_font == 'fa') 
			{
				$sysslug = MI()->ask('system/slug');
				wp_enqueue_style($sysslug . '-font-awesome'); 
			}
 		}
		
		MI()->tell('field/icon/view', array('document' => $domObj, 
											 'value' => $value, 
											 'field' => $this
											)
		);

	} 
	

} // class
