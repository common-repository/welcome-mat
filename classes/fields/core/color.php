<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/**	Color editor
*
* The color editors display a color picker where the user can set colors for various objects.
*
*/
add_action('maxinbound_register_field', array(__NAMESPACE__ . '\miColorField', 'init'));
use MaxInbound\simpleTemplate as simpleTemplate;

class miColorField extends miField
{
	static $field_name = 'color';

	protected $pseudo = null;
	protected $removable = false; // aka transparency or no color set. For backgrounds / things that might not need a color

	public function __construct($name)
	{
		$this->template = __DIR__ .  '/tpl/color.tpl';
		parent::__construct($name);

		// defaults
		$this->scope = 'color';
		$this->title = __('Color', 'maxinbound');
		return $this;
	}

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());
	}


	public function admin()
	{
		$tpl = new \stdClass;

		$tpl->name = $this->name;
		$tpl->title = $this->title;

		$value = $this->value;

		if (strlen(str_replace('#','', $value)) == 3)
		{
			$split = str_split(str_replace('#','',$value));
			$new_val = '#';
			foreach($split as $sp)  // remove shorthand since colorpicker doens't like it.
			{
				$new_val .= $sp . $sp;
			}
			$value = $new_val;
		}

		$tpl->value = $value;
		$tpl->scope = $this->scope;

		$colvalue = $this->checkColor($value);
		if ($colvalue == 'transparent') $colvalue = ''; // transparent is value for view, not for editor (it's empty)
		$tpl->colvalue = $colvalue;

		if ($this->removable)
			$tpl->removable = true; // allow to set transparency

		return simpleTemplate::parse($this->template, $tpl);
	}

	public function view($domObj)
	{
		$value = $this->checkColor($this->value);
		if ($value == '')
			return;  // no value no color

		// this could be done without switch probably. scope: value.
		if (is_null($this->pseudo))
		{
			$element = 'style';
		}
		elseif ($this->pseudo == 'hover')
			$element = 'hover';

		switch($this->scope)
		{
			case "background-color":
				//$value = $this->value;
				//if ($this->value == 'none')
				//	$value = 'transparent';
				$domObj->{$element} .= 'background-color: ' . $value . ';';
			break;
			case 'border-color':
				$domObj->{$element} .= 'border-color: '. $value . ';';
			break;
			case "color":
			default:
				$domObj->{$element} .= 'color: ' . $value . ';' ;

			break;
		}

	}

	public function checkColor($value)
	{
		$removable = array('background-color', 'border-color');

		if (in_array($this->scope, $removable))
			$this->removable = true;

		// check for none or transparent.
		if ($value == 'transparent' || $value == 'none' || ($value == '' && $this->removable))
			return 'transparent';
		elseif ($value === '')
			return $value;

		if (strpos($value, '#') === false)
			$value = '#' . $value;

		return $value;
	}


}
