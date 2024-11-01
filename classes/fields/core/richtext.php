<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/**  Richtext editor

This editor supports longer text areas. 

*/
add_action('maxinbound_register_field', array(__NAMESPACE__ .'\miRichtextField', 'init'));

class miRichtextField extends miField
{
	static $field_name = 'richtext';

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());	
	}
	
	public function __construct($name) 
	{
		$this->name = $name; 
		$this->template = __DIR__ . '/tpl/richtext.tpl'; 
		
	
	}
	
	public function admin() 
	{
		$tpl = new stdClass; 
		$tpl->name = $this->name;
		$tpl->title = $this->title; 
		$tpl->value = $this->value;	 
		
		$settings = array(
			
		);
		
		ob_start(); 
		wp_editor($this->value, $this->name, $settings); 
		$editor = ob_get_contents();
		ob_end_clean(); 
		
		$tpl->editor = $editor;
	 	
	 	return simpleTemplate::parse($this->template, $tpl);
	}
		
	 function populate($domObj, $field, $data) {
		$value = (isset($data["value"])) ? $data["value"] : ''; 

		$domObj->innertext = $value; 
		return $domObj; 
		
	}
 


	 function get_admin($field, $data, $args = array()) {

	   $controls =  "<textarea class='wp-editor-container' type='text' id='$field' name='$field'>$value</textarea>";  
		$args["controls"] = $controls; 
	   
	   $args["editor"] = 'richtext';
	 
	 	/*	<div class="siteorigin-widget-tinymce-container"
		     data-mce-settings="<?php echo esc_attr( json_encode( $settings['tinymce'] ) ) ?>"
		     data-qt-settings="<?php echo esc_attr( json_encode( array() ) ) ?>"
		     data-widget-id-base="<?php echo esc_attr( $widget_id_base ) ?>"
			>
			<?php
			wp_editor( $value, esc_attr( $this->element_id ), $settings )
			?>
		</div> */
		  
	   return parent::get_admin($field, $data, $args);  
	}
		
}
