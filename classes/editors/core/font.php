<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miFontEditor', 'init'));

class miFontEditor extends miEditor
{

	static $editor_name = 'font'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}

	public function __construct($name) 
	{
		parent::__construct($name);

		$this->template = __DIR__ . "/tpl/font.tpl";	 
									
		$this->fields["font"] = MI()->editors()->getNewField($this->name . '_font', 'font')	
								->set('scope', 'font-family')
								->set('title', '')
		 ;
	
		$this->fields["size"] = MI()->editors()->getNewField($this->name . '_size', 'text')	
								->set('type','number')
								->set('min', '10')
								->set('inputclass', 'tiny')
								->set('scope','font-size')
								;  
		//$this->fields['spacer'] = (new miSpacerField('space') )
		
								;
		$this->fields["weight"] = MI()->editors()->getNewField($this->name . '_weight', 'checkbox')	
									->set('inputvalue','700')
									->set('unsetvalue','400')
									->set('title', __('Bold','maxinbound') )
									->set('icon', 'dashicons dashicons-editor-bold') 
									->set('inputclass', 'check_button icon')	
									->set('scope', 'font-weight') 								
									;
		$this->fields["style"] = MI()->editors()->getNewField($this->name . '_style', 'checkbox')	
									->set('inputvalue','italic')
									->set('title', __('Italic','maxinbound') ) 
									->set('icon', 'dashicons dashicons-editor-italic')
									->set('inputclass', 'check_button icon')
									->set('scope', 'font-style')									
		; 
		//$this->fields['spacer2'] = (new miSpacerField('space') )
								;
		
		$this->fields["align_left"] = MI()->editors()->getNewField($this->name . '_align', 'checkbox')	
									->set('inputvalue','left')
									->set('title', __('Align Left','maxinbound') )
									->set('icon', 'dashicons dashicons-editor-alignleft')
									->set('type','radio')
									->set('id', $this->name . '_align_left')
									->set('inputclass', 'check_button icon')
									->set('scope', 'text-align')
		; 
		$this->fields["align_center"] = MI()->editors()->getNewField($this->name . '_align', 'checkbox')	
									->set('inputvalue', 'center')
									->set('title', __('Align Center','maxinbound') )
									->set('icon', 'dashicons dashicons-editor-aligncenter') 
									->set('type','radio')
									->set('id', $this->name . '_align_center')									
									->set('inputclass', 'check_button icon')
									->set('scope', 'text-align')
		 ; 
		$this->fields["align_right"] = MI()->editors()->getNewField($this->name . '_align', 'checkbox')	
									->set('inputvalue', 'right') 
									->set('title', __('Align Right', 'maxinbound') )
									->set('icon', 'dashicons dashicons-editor-alignright') 
									->set('type','radio')
									->set('id', $this->name . '_align_right')		
									->set('inputclass', 'check_button icon')	
									->set('scope', 'text-align')															
		 ; 				
		
		// sets the linked document field to all field objects. 
		foreach($this->fields as $fname => $field) 
		{
			$field->set('document_field', $name);
		}
		// Button for opening font manager. 
		//$this->panel["font-manager"] = (new miButtonField($this->name . '_fontmanager') ); 
		
 		return $this;		 
	}
	
	public function admin() 
	{
		$in_tpl = array_keys($this->fields); // before setup!
		
		$tpl = $this->setupAdmin(); 
		
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

