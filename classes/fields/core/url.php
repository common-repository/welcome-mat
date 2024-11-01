<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');
/** URL Editor
*
* The URL Field can be used to create an URL with several properties.
*
*/
add_action('maxinbound_register_field', array(__NAMESPACE__ . '\miURLField', 'init'));

class miURLField extends miField
{
	static $field_name = 'url';

	protected $placeholder = 'https://';
	protected $inputclass = 'large';

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

	public function admin() {
		$name = $this->name;
		$value = esc_attr($this->value);
		$title = esc_html($this->title);
		$placeholder = esc_html($this->placeholder);
		$inputclass = $this->inputclass;

		$tpl = new \stdClass;
		$tpl->name = $name;
		$tpl->value = $value;
		if (strlen($title) > 0)
			$tpl->title = $title;
		$tpl->placeholder = $placeholder;
		$tpl->type = 'text';
		$tpl->inputclass = $inputclass;

		//$tpl->placeholder = 'http://';

	 	return simpleTemplate::parse($this->template, $tpl);
	}

} // class
