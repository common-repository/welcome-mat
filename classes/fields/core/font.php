<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/**  Font Editor
* 
* This editor display various properties of a typical font setting and allows them to be changed.
*/
add_action('maxinbound_register_field', array(__NAMESPACE__ .'\miFontField', 'init'));


class miFontField extends miField
{
	static $field_name = 'font';
	

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());	
	}

	public function defaultFonts() 
	{
		$fonts = array( 
			'Arial' => 'Arial',
			'Courier New' => 'Courier New', 
			'Georgia' => 'Georgia',
			'Tahoma' => 'Tahoma',
			'Times New Roman' => 'Times New Roman',
			'Trebuchet MS' => 'Trebuchet MS',
			'Verdana' => 'Verdana'
		);
		
		return $fonts;	
	}
	
	protected function loadFonts() 
	{
		$fonts = MI()->collect('field/font/load_fonts'); 
		
		$load_fonts = array(); 
		
		foreach($fonts as $index => $fonts)
		{
			$load_fonts = array_merge($load_fonts, $fonts); 
		}
		ksort($load_fonts);
		
		return $load_fonts;
	}

	public function __construct($name) 
	{
		MI()->offer('field/font/load_fonts', array($this,'defaultFonts') ); 
				
		$this->template = __DIR__ .  '/tpl/font.tpl'; 
		parent::__construct($name); 
		
		// defaults
		$this->scope = 'font'; 
		$this->title = __('Font', 'maxinbound'); 
	
		return $this; 
	}
		
	 public function admin() {
		$tpl = new \stdClass;

		$tpl->name = $this->name; 
		if ($this->title != '') 
			$tpl->title = $this->title; 
		
		$value = esc_attr(str_replace(array('"','\''),'',$this->value)); 
		$tpl->options = $this->generate_options($this->loadFonts(), $value);  

	 	return simpleTemplate::parse($this->template, $tpl);
	 }
	 
	public function view($domObj)
	{
		$value = $this->value; 
 		if ($value != '')
		 	$domObj->style .= 'font-family:' . $value . ';'; 
		
		
		MI()->tell('field/font/view', array('document' => $domObj, 
											 'value' => $value, 
											 'field' => $this
											)
		);

	} 


} // font editor



