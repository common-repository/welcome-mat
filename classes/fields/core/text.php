<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');
/** Text Editor
*
* The text editor can be used for simple lines like titles, but also for other settings than can be presented as a
* single line input field. For instance certain CSS properties ( font-size and others ).
* 
*/
add_action('maxinbound_register_field', array(__NAMESPACE__ . '\miTextField', 'init'));

class miTextField extends miField
{
	static $field_name = 'text';

	var $type = 'text';
	var $placeholder;
	var $inputclass;

 	// type=number
 	var $min = 0;
 	var $max = -1;
 	var $step = 1;

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());
	}

	public function __construct($name)
	{
		$this->template = __DIR__ .  '/tpl/text.tpl';

		parent::__construct($name);
		return $this;

	}

	protected function sanitizePostField($value)
	{
		if ($this->type == 'number')
			$this->sanitize_method = 'int';

		return parent::sanitizePostField($value);
	}

	public function admin() {

		$tpl = new \stdClass;

		$tpl->name = esc_attr($this->name);
		$tpl->value = esc_attr($this->value);
		if ($this->title != '')
			$tpl->title = esc_html($this->title);
		$tpl->type = $this->type;
		$tpl->placeholder = $this->placeholder;
		$tpl->inputclass = $this->inputclass;

		if ($this->type == 'number')
		{
			$tpl->value = intval($tpl->value);
			$tpl->min = $this->min;
			if ($this->max > 0)
				$tpl->max = $this->max;
			$tpl->step = $this->step;
		}


		return simpleTemplate::parse($this->template, $tpl);
	}

	public function view($domObj)
	{
		if (! $this->scope) // scope is not defined by editor, find scope.
		{
			$document_field = $this->document_field;
			$docfield_info = MI()->ask('template/field/' . $document_field);
			if (! is_null($docfield_info) && isset($docfield_info['tag']) )
			{
				$tag = $docfield_info['tag'];
				switch($tag)
				{
					case "input":
						$this->scope = 'placeholder';
					break;
					default:
						$this->scope = 'text';
					break;
				}
			}
			//$this->scope = 'text';
		}

		//if ($this->value == '')
		//	return $domObj; // don't write empty values.

		switch($this->scope)
		{
			case "href":
				$domObj->href = esc_url(trim($this->value));
			break;
			case "placeholder":
				$value =   esc_attr($this->value);
				$domObj->attr('placeholder', $value);
			break;
			case "text":
				if(isset($domObj->children[0]))
					$domObj->children[0]->text( esc_html($this->value) );
			break;
			default: //css
				$domObj->style .= $this->scope . ":" . $this->scope_filter($this->scope,$this->value) . ";";
			break;
		}
	}

} // class
