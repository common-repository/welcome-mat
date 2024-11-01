<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miUtils
{	
	static function get_font_options()
	{
		$font_families = array(
		'' => '',
		'Arial' => 'Arial',
		'Courier New' => 'Courier New', 
		'Georgia' => 'Georgia',
		'Tahoma' => 'Tahoma',
		'Times New Roman' => 'Times New Roman',
		'Trebuchet MS' => 'Trebuchet MS',
		'Verdana' => 'Verdana'
		);	
		$font_families = apply_filters('mi_font_options', $font_families); 
		return $font_families;
	}
	
	static function get_form_options()
	{
		$options = array(
					0 => "",
					1 => __("Plugin Shortcode",'maxinbound'), 
					2 => __("PHP Function", 'maxinbound'), 	
					3 => __("Custom HTML", "maxinbound"), 
		);
		return $options;
	
	}
	
	static function selectify($name, $array, $selected, $target = '')
	{
		// optional target for js updating
		if ($target != '' ) 
			$target = " data-target='$target' "; 
		$output = "<select name='$name' id='$name' $target>";
		
		foreach($array as $key => $value) 
		{
			$output .= "<option value='$key' " . selected($key, $selected, false) . ">$value</option>"; 
		}
		$output .= "</select>"; 
		
		return $output;
	
	}

 

	
}

