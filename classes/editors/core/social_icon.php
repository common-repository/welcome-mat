<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

// Icon Editor


add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miSocialIconEditor', 'init'));

class miSocialIconEditor extends miEditor
{

	static $editor_name = 'social_icon'; 
	
	protected $iconset = 'social'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}
	
	public function __construct($name) 
	{
		parent::__construct($name);
		$this->template = __DIR__ . "/tpl/social_icon.tpl";	 

		
		$this->fields["icon"] = 	MI()->editors()->getNewField($this->name . '_icon', 'icon')	
									->set('inputclass', 'check_button icon medium')
									->set('scope', 'class')
									;
 		$this->fields['link'] =  MI()->editors()->getNewField($this->name . '_link' , 'text')
 								 	->set('scope', 'href') 	
 								 	->set('label', __('Link', 'maxinbound') )
 								 	;																				
		$this->fields['color'] = MI()->editors()->getNewField($this->name . '_color', 'color')								  
									->set('scope', 'color')
									->set('title', __('Icon Color', 'maxinbound') )
									;
		$this->fields['color_hover'] = MI()->editors()->getNewField($this->name . '_color_hover', 'color')								  
									->set('scope', 'color')
									->set('title', __('Hover','maxinbound') )
									->set('pseudo', 'hover')
									;


		$this->fields['background_color'] = MI()->editors()->getNewField($this->name . '_backgroundcolor', 'color') 
									->set('title', __('Background Color', 'maxinbound'))
									->set('scope', 'background-color')
								;									 


		$this->fields['background_color_hover'] = MI()->editors()->getNewField($this->name . '_backgroundcolor_hover', 'color')								  
									->set('scope', 'background-color')
									->set('title', __('Hover','maxinbound') )
									->set('pseudo', 'hover')
									;
		
	}
	
	public function setupData() 
	{
		if ($this->iconset !== '') 
			$this->fields['icon']->set('iconset', $this->iconset);
		
		// if item has a child ( <i> ) take those class values into the findDefault routine. 
		$field = $this->field; 
		$children = $field->children;
		foreach($children as $name => $field) 
		{

			if ($field->tag == 'i') 
			{
				$icon_class = $field->class; 
			}

		}	

		parent::setupData();
		
		if (! isset($this->data[$this->name]['icon']) && isset($icon_class) )
		{
			$this->fields['icon']->set('value', $icon_class); // replacement of default FindDefault
		}
				

	}
	
	public function view() 
	{
		parent::view(); 
		$domObj = $this->field; 
			
		$children = $domObj->children;
		$icon_field = $this->fields['icon'];  	
		list($icons, $icon_titles) = $icon_field->loadIcons();
				
		// Check for <i> tag as child. If so, move the icon definition class from the main item, to the child item		
		foreach($children as $name => $field) 
		{

			if ($field->tag == 'i') 
			{
				$icon_font =  $icon_field->get('icon_font'); 
				$value = $icon_field->get('value'); 
				
				$icon = (isset($icons[$value])) ? $icons[$value] : false; 
		
				$domObj->class = str_replace( array( $icon, $icon_font), '', $domObj->class);
				$field->class = $icon_font . ' ' . $icon; 
			}

		}			
	}

	
	public function admin() 
	{
		$in_tpl = array('icon'); 

		$tpl = $this->setupAdmin(); 
		//$tpl->label = $this->label;
		
		foreach($this->fields as $name => $field)
		{
			if (in_array($name, $in_tpl)) // these are mentioned in .tpl file.
			{	
				$tpl->{$name} = $field->admin(); 
		
			}
			else
			{
				$tpl->content .= $field->admin(); 
			}
		}
	 	$output = simpleTemplate::parse($this->template, $tpl);		
		return $output;
	}
	
} // class
