<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_editor', array(__NAMESPACE__ . '\miLinkEditor', 'init'));

class miLinkEditor extends miEditor
{
	static $editor_name = 'link'; 

	static function init($editor)
	{
		$editor->registerEditor(self::$editor_name, get_called_class() );	
	}

	public function __construct($name) 
	{
		parent::__construct($name);

		$this->template = __DIR__ . "/tpl/link.tpl";	 
	
		$this->fields["link"] = MI()->editors()->getNewField($this->name . '_link', 'text')	
								->set('scope', 'href')
								->set('title', '')
								 ;
	}
	
	public function admin() 
	{
		$tpl = $this->setupAdmin(); 
		$tpl->button_label = __('Select Page / Posts','maxinbound');
		
		foreach ($this->fields as $name => $field) 
		{
			$tpl->content .= $field->admin(); 		
		}
		
	 	$output = simpleTemplate::parse($this->template, $tpl);		
		return $output;
	
	} 
}
