<?php
namespace MaxInbound;

defined('ABSPATH') or die('No direct access permitted');

abstract class miField
{
	// class fields
	var $template;

	// field vars
	protected $sanitize_method = 'text'; // how to sanitize post field [text / int / ]

	protected $title; // title / label
	protected $name = '';  // input name - must be unique
	protected $value = '';  // single value for this field
	protected $scope = false; // controls which part the field should edit
	protected $document_field = ''; // the field in the template corresponding.
	protected $label;

	//var $children = array(); //anything child of this. Extra fields and all. Unused as of writing, probably should not be implemented.

	abstract public function admin();
	  static function init($editor) {}

	public function __construct($name)
	{
		$this->name = $name;
		return $this;
	}

	public function __debugInfo() {
		$result = get_object_vars($this);
		unset($result['document_field']);
		return $result;
	}

	public function get($field)
	{
		if (isset($this->$field))
			return $this->$field;
		else
			return false;
	}

	public function set($field, $value)
	{
		$this->{trim($field)} = trim($value);
		return $this;
	}

	public function setTemplate($name, $package = 'core')
	{
		$this->template = __DIR__ . '/' . $package . '/tpl/' . $name;
	}

 	public function getPostData($post)
 	{
 		if (isset($post[$this->name]))
 			return $this->sanitizePostField($post[$this->name]);
 		else
 			return '';
 	}

 	/** Sanitize Post Fields upon saving or getting something from PHP Post
  *	
 	*	@param mixed $value Value to sanitize
 	*	@return mixed Sanitized Value
 	**/
 	protected function sanitizePostField($value)
 	{

 		switch($this->sanitize_method)
 		{
 			case "text":
 				$value = sanitize_text_field($value);
 			break;
 			case "int":
				$value = intval($value);
 			break;

 		}

 		return $value;

 	}

	public function view($domObj)
	{
		return $domObj;
	}


	/** Util function to generate options
	*
	* Generates <option> tags with value set as selected
	* @param $options Array Array of Options
	* @param $value String Selected option
	* @return string HTML Options
	*/
	public function generate_options($options, $select_value = '')
	{
		$output = '';
		foreach($options as $name => $value)
		{

			$output .= "<option value='$name' " . selected($select_value,$value,false) . " > $value </option>";
		}
		return $output;
	}

	/** Filter values on basis of scope
	*
	* Function to check and change output on basis of scope. I.e. add px declarations to CSS sizes, or others.
	*/
	public function scope_filter ($scope, $value)
	{
		$ask_value = MI()->ask('field/scope/' . $scope);
		if (! is_null($ask_value)) // knows better..
			return $ask_value;

		switch($scope)
		{
			case 'font-size':

				if (substr($value, -2) !== 'px')
					$value .= "px";
			break;

		}

		return $value;
	}
}
