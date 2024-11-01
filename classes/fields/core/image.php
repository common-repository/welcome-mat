<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/** Image Editor

Allow an user to interact with images, add them to the WordPress library and include the into the template. 

*/
add_action('maxinbound_register_field', array(__NAMESPACE__ .'\miImageField', 'init'));

class miImageField extends miField
{
	static $field_name = 'image';
	
	protected $src = false;

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());	
	}

	public function __construct($name) 
	{
		$this->template = __DIR__ .  '/tpl/image.tpl'; 
		parent::__construct($name); 
	}

	public function checkScope() 
	{
		if ($this->scope == '') 
		{
			$doc = $this->document_field; 
			if (isset($doc->src))
				$this->scope = 'image'; 
			else
				$this->scope = 'background-image'; 
		}	
	}
		
	public function admin()
	{
		if (! $this->src && $this->value != 'none') 
			$image_src = $this->getImage($this->value); 
		else
			$image_src = $this->src; 
				
		$tpl = new \stdClass; 
		$tpl->name = $this->name;
		$tpl->title = $this->title; 
		$tpl->value = esc_attr($this->value);
		
		if ($image_src)
			$tpl->src = $image_src;
		$tpl->toolbar = __('This feature is only available in the PRO version','maxinbound'); 
		$tpl->button_text = __("Select an image", "maxinbound"); 
	 	
	 	return simpleTemplate::parse($this->template, $tpl);
	}
	
	public function view($domObj) 
	{
		$this->checkScope(); 
		//if ($this->value == '') 
			//return; // empty value leads to no image. 

		$value = $this->value; 

		if ($value !== 'none') 
			$image_src = $this->getImage($this->value);
	
		if (! isset($image_src) || ! $image_src)
			$image_src = 'none';
		
		if ($this->scope == 'background-image')
		{
			if ($image_src == 'none') 
				$domObj->style .= ' background-image: none;' ; 		
			else
				$domObj->style .= ' background-image: url("' . $image_src . '"); '; 
		}
		else
		{
			if ($image_src !== 'none')
				$domObj->src = esc_attr($image_src);  
		}
	}
	

	// get the image from a value or redirect to the template format
	protected function getImage($value)
	{
		if (is_numeric($value))  // numeric mean a WP-attachment
		{	 $img = wp_get_attachment_image_src($value, 'full');
	 		 $url = $img[0];
		}
		else // non-numeric ( aka not changed by user ) comes from the stylesheet. 
		{
			if ($this->scope == '') 
				$this->checkScope(); 
				
			if ($this->scope == 'image') 
			{
				$url = $this->get_template_img($value);
			}
			elseif ($this->scope == 'background-image')
			{	

				$img = MI()->templates()->get()->findDefault($this->document_field, $this);
				$img = str_replace(array('../','\'','"'),'', $img);
				$img = preg_replace('/url\((.*)\)/i', '$1', $img); // match url('../img.png') and get filename.
				
				if ($img == 'transparent' || $img == 'none') 
					return false; // no background from template
		
				$template_url = MI()->templates()->get()->template_url;
				$url = $template_url . $img;
			}	
		}
		
		return $url;
	}
	
	protected function get_template_img($value)
	{
			$value = trim($value); // yes that matters 

			$pattern = '/^\[(.*)\]/i';
			preg_match($pattern, $value, $tname );
 
			if (count($tname) > 0) 	
			{	$template_name = $tname[1];
				$value = preg_replace($pattern,'',$value);
			}
			else
				$template_name = ''; 
			$url = MI()->get_plugin_url() . 'templates/'.  $template_name . '/' . $value; 	
 
 			
			return $url;		
	}

} // class
