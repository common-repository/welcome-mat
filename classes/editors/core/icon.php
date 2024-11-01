<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

// Icon Editor


add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miIconEditor', 'init'));

class miIconEditor extends miEditor
{

	static $editor_name = 'icon'; 
	
	protected $iconset = ''; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}
	
	public function __construct($name) 
	{
		parent::__construct($name);
		$this->template = __DIR__ . "/tpl/icon.tpl";	 

		
		$this->fields["icon"] = 	MI()->editors()->getNewField($this->name . '_icon', 'icon')	
									->set('inputclass', 'check_button icon big')
									->set('scope', 'class')
									;
								
		$this->fields['border_style_square'] = MI()->editors()->getNewField($this->name . '_radius', 'checkbox')
									->set('inputvalue','0')
									->set('title', __('Square','maxinbound') )
									->set('icon', 'border-radius square')
									->set('type','radio')
									->set('id', $this->name . '_border_square')
									->set('inputclass', 'check_button border_style big')
									->set('scope', 'border-radius')
									;

		$this->fields['border_style_rounded'] = MI()->editors()->getNewField($this->name . '_radius', 'checkbox')
									->set('inputvalue','25%')
									->set('title', __('Rounded','maxinbound') )
									->set('icon', 'border-radius rounded')
									->set('type','radio')
									->set('id', $this->name . '_border_rounded')
									->set('inputclass', 'check_button border_style big')
									->set('scope', 'border-radius')
									;
									
		$this->fields['border_style_round'] = MI()->editors()->getNewField($this->name . '_radius', 'checkbox')
									->set('inputvalue','50%')
									->set('title', __('Round','maxinbound') )
									->set('icon', 'border-radius round')
									->set('type','radio')
									->set('id', $this->name . '_border_round')
									->set('inputclass', 'check_button border_style big')
									->set('scope', 'border-radius')
									;	
 																								
		$this->fields['color'] = MI()->editors()->getNewField($this->name . '_color', 'color')								  
									->set('scope', 'color')
									;

		$this->fields['background_color'] = MI()->editors()->getNewField($this->name . '_backgroundcolor', 'color') 
									->set('title', __('Background Color', 'maxinbound'))
									->set('scope', 'background-color')
								;									 
		$this->fields['border'] = MI()->editors()->getNewField($this->name . '_bordercolor', 'color')								 
									->set('title', __('Border Color', 'maxinbound'))
									->set('scope','border-color')
								;							
		
	}
	
	public function setupData() 
	{
		if ($this->iconset !== '') 
			$this->fields['icon']->set('iconset', $this->iconset);
				
		parent::setupData();
	}

	
	public function admin() 
	{
		$in_tpl = array('icon', 'border_style_square', 'border_style_rounded', 'border_style_round'); 

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
		foreach($this->children as $child)
		{
			$object = $child['obj']; 
			
			if ($child['element'] == 'field')
				$tpl->content .= $object->admin(); 
			else
				$tpl->content .= $object->admin();
		}
		
				
	 	$output = simpleTemplate::parse($this->template, $tpl);		
		return $output;
	}
	
} // class
