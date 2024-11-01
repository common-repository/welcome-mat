<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/** Button Editor

Allow the inclusion of a button dialog into the template. The user can pick between using a Maxbutton button, or define it's own button 
with limited properties. 

*/
add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miButtonEditor', 'init'));

class miButtonEditor extends miEditor
{
	static $editor_name = 'button'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}

	
	public function __construct($name) 
	{
		parent::__construct($name);


		$this->fields["text"] = MI()->editors()->getNewField($this->name . 'text', 'text')
									->set('title', __("Button text", "maxinbound") )
							//		->set('scope','text')
								;
		
		/* $this->fields["url"] =  (new miURLField($this->name . '_url')) 
									->set('title', __("URL (link)", "maxinbound") )
									->set('scope', 'href')
								; 
		*/
		$this->panel["text_color"] = MI()->editors()->getNewField($this->name . 'textcolor', 'color')

									->set('title', __("Text Color","maxinbound") )
							//		->set('scope', 'color')
									 ;
		


		$this->panel["background_color"] = MI()->editors()->getNewField($this->name . 'bgcolor', 'color') 
									->set('title', __("Background color","maxinbound"))
									->set('scope', 'background-color')
									  ;
						
									
		return $this;
 		 
	}

	
	function view () 
	{
		$element = $this->field;
		
		$text = $this->fields['text']->get('value'); 
		//$url =  $this->fields['url']->get('value'); 
		
		switch($element->tag) 
		{
			case 'a':  // button is anchor
				$element->href = $url; 
				$element->children[0]->text($text); 
							
			break; 
			case 'button':  // button be button 
				$element->children[0]->text($text); 				
			break; 
			default: 
			
			break; 
		}
		
		if (isset($this->fields['text_color']))    
			$this->fields['text_color']->view($element); 
		if (isset($this->panel['background_color'])) 
			$this->panel['background_color']->view($element); 

		return $element;
	}

	
} // button editor
