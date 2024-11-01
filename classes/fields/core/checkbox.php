<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_field', array(__NAMESPACE__ . '\miCheckboxField', 'init'));


class miCheckboxField extends miField
{
	static $field_name = 'checkbox';

	protected $inputvalue = ''; // value of input itself
	protected $icon;
	protected $type = 'checkbox';
	protected $id;
	protected $inputclass;
	protected $unsetvalue = false;

	static function init($editor)
	{
		$editor->registerField(self::$field_name,  get_called_class());
	}

	public function __construct($name)
	{
		$this->template = __DIR__ .  '/tpl/checkbox.tpl';
		parent::__construct($name);

		// defaults
		$this->scope = '';

		return $this;
	}

	 public function admin() {
		$tpl = new \stdClass;

		$tpl->name = $this->name;
		$tpl->title = $this->title;
		$tpl->inputvalue = $this->inputvalue;
		$tpl->icon = $this->icon;
		$tpl->value = $this->value;
		$tpl->type = $this->type;
		$tpl->inputclass = $this->inputclass;
		$tpl->label = $this->label;

		if ($this->id)
			$tpl->id= $this->id;
		else
			$tpl->id= $this->name . $this->inputvalue;	 // keep it unique

 		$tpl->checked = checked($this->value,$this->inputvalue,false);


	 	return simpleTemplate::parse($this->template, $tpl);
	 }

 	public function getPostData($post)
 	{
 		// get post data. Don't return value is option is not selected
 		if (isset($post[$this->name]) && $post[$this->name] == $this->inputvalue )
 			return $post[$this->name];
 		else
 			return '';
 	}


	 public function view($domObj)
	 {
	 	if ($this->value == $this->inputvalue)
	 	{
	 		switch($this->scope)
	 		{
	 			default;
	 				$domObj->style .=  ' ' . $this->scope . ':' . $this->inputvalue . ';';
	 			break;
			}
	 	}
	 	// unsetvalue is to override default stylesheet value if checkbox is *not* active.
	 	else if ($this->unsetvalue != '' && $this->unsetvalue !== false )
	 	{
	 		switch($this->scope)
	 		{
	 			default;
	 				$domObj->style .=  ' ' . $this->scope . ':' . $this->unsetvalue . ';';
	 			break;
			}


	 	}

	 }



} // checkbox
