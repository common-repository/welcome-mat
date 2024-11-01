<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array('MaxInbound\moduleGoogleFonts', 'init'));

class moduleGoogleFonts extends miModule 
{
	static $name = 'googleFonts'; 
	static $table_name = ''; 
	
	var $google_url = '//fonts.googleapis.com/css?family='; 
	var $requested_fonts = array(); 
	protected $webfonts = false; 

	public static function init($modules) 
	{
		$modules->register(self::$name, get_called_class() );	
	
	}

	public function __construct() 
	{
		parent::__construct(); 
	
		$this->title = __('Google Fonts','maxinbound'); 

		MI()->offer('field/font/load_fonts', array($this, 'getGoogleFonts') ); 
		MI()->listen('editor/view/end', array($this, 'view') ); // This is hook for -every- editor - . 
		MI()->listen('template/output-end', array($this, 'output_view') ); 
 
		
	}
	
	public function getGoogleFonts() 
	{
			$fonts = array(
			'Antic Slab ' => 'Antic Slab',
			'Arimo' => 'Arimo', 
			'Arvo' => 'Arvo',
			'Droid Sans' => 'Droid Sans',
			'Droid Sans Mono' => 'Droid Sans Mono',
			'Droid Serif' => 'Droid Serif',
			'Josefin Slab' => 'Josefin Slab',
			'Lato' => 'Lato',
			'Lora' => 'Lora', 
			'Merriweather' => 'Merriweather',
			'Montserrat' => 'Montserrat',
			'Noto Sans' => 'Noto Sans',
			'Open Sans' => 'Open Sans',
			'Open Sans Condensed' => 'Open Sans Condensed',
			'Oswald' => 'Oswald',
			'Pacifico' => 'Pacifico',
			'PT Sans' => 'PT Sans',
			'PT Sans Narrow' => 'PT Sans Narrow',
			'Quicksand' => 'Quicksand',
			'Raleway' => 'Raleway', 
			'Roboto' => 'Roboto', 
			'Roboto Slab' => 'Roboto Slab', 
			'Rokkitt' => 'Rokkitt',
			'Shadows Into Light' => 'Shadows Into Light',
			'Source Sans Pro' => 'Source Sans Pro',
			'Ubuntu' => 'Ubuntu',
			'Ubuntu Condensed' => 'Ubuntu Condensed',
		);	
		
		return $fonts;
	
	}
	
	protected function getWebFonts() 
	{
		if (! $this->webfonts)
		{
			$file = trailingslashit(dirname(__FILE__)) . 'webfonts.json'; 
			$this->webfonts = json_decode(file_get_contents($file), true); 
		}
		
		return $this->webfonts; 

	}
	
	protected function getFontWeight($font, $weight_cur) 
	{	
		$webfonts = $this->getWebFonts();

	/* This is much nicer, but < PHP 5.5 			
		$result = array_search($font, array_column($webfonts['items'],'family') );
	*/
		$result = false;
		foreach($webfonts['items'] as $i => $wfont)
		{	
			if (isset($wfont['family']) && $wfont['family'] == $font) 
				$result = $wfont;
		} 
	
		if (! $result) 
			return 400; // panic to default

			
		$webfont = $result; //$webfonts['items'][$result];
		$weights = $webfont['variants'];

		if ($weight_cur == 400) 
			$check_weight = 'regular'; 
		else
			$check_weight = $weight_cur; 
		
		if (in_array($check_weight, $weights))
		{	
			return $weight_cur; 
		}
		else
		{
			if (in_array('regular', $weights)) 
				return 400; 
			elseif (in_array('300', $weights))
				return 300; 
		
		}
		return 1000;
	}
	
	protected function addFont($font, $weight) 
	{
		$gfonts = $this->getGoogleFonts(); 
			
		if (in_array($font, $gfonts)) 
		{
			$weight = intval($this->getFontWeight($font, $weight));
			$value = $font . ':' . $weight; 
			
			if (! isset($this->requested_fonts[$value]))
			{
				
				$font = preg_replace('/\s/i', '+', $font); 
				$font_url = $this->google_url . $font; 

				if ($weight !== 400) 
					$font_url .= ':' . $weight; 
					
				$this->requested_fonts[$value] = $font_url;		
			}
		}	
		
	}
	
	public function view($args)
	{		
		if ($args['editor'] !== 'font') 
			return;

		$doc = $args['document']; 
		$styles = explode(';', $doc->style);
 
		$font = false;
		$weight = false;
		
		foreach($styles as $style) 
		{
			if (strpos($style, 'font-family') !== false)
			{	
				$font = trim(str_replace(array('font-family',';',':'),'', $style));
			}
			if (strpos($style, 'font-weight') !== false)
			{
				$weight = trim(str_replace(array('font-weight',';',':'), '', $style));
			}
			
		}
		
		if (! $font) 
			return; // no font, not our problem. 
		

		$this->addFont($font, $weight); 
		
	}
	
	public function output_view() 
	{
 
		$cssParser = MI()->templates->get()->getCSSParser(); 
		
		$rules = $cssParser->getAllOfRule('font-'); 
			
		foreach($rules as $i => $data)
		{
			if (! isset($data['font-family'])) 
				continue; 
				
			$font =  str_replace(array('\'','"'),'',$data['font-family']); 
			
			$weight = isset($data['font-weight']) ? $data['font-weight'] : 400; 
			$this->addFont($font, $weight);
		}
		
		$fonts = $this->requested_fonts; 
		$output = ''; 

		foreach($fonts as $font => $url) 
		{
			$output .= "<link rel='stylesheet' type='text/css' href='$url' />";
		
		}
		echo $output;
	
	}
	
	
} // class
