<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/** 
Main Editor Class

The editor class is responsible for populating values on the DOM Document, showing admin fields to edit settings and 
figure out which post data is relevant on save actions. 


*/

use MaxInbound\simpleTemplate as simpleTemplate;

class miEditor
{	
	 var $template;
	 var $fields = array();  // fields as in the MiFields Object
	 var $field = null; // field as in the template field
	 var $panel = array(); 
	 var $data; 
	 var $do_default = true;
	 var $children = array(); 
	 var $parent;  // an editor can be child of editor. This will be reflected in the parent attribute
	 
	 var $scope; // scope can be an attribute of editor. The editor will have to decide on what field to apply. 

	public function __debugInfo() { 
		$result = get_object_vars($this); 
		unset($result['field']); 
		return $result; 
	} 

	 static function init($editor)
	 {}
	 
	 public function __construct($name)
	 {

		$this->name = $name;		
		$this->template = __DIR__ . "/core/tpl/default.tpl";	 
	 
	 	return $this;
	 }
	
 	 public function panel() 
 	 {
 	 	$panel = ''; 
 	
 	 	foreach($this->panel as $item) 
 	 	{
 	 		$panel .= $item->admin(); 
 	 	}
 	 
 	 	return $panel; 
 	 	
 	 }
 	 
 	 public function hasPanel() 
 	 {
 	 	if (isset($this->panel) && count($this->panel) > 0) 
 	 		return true; 
 	 
	 	return false; 
 	 
 	 }
 	 
 	 public function hasFields() 
 	 {
 	 	if (isset($this->fields) && count($this->fields) > 0) 
 	 		return true; 
 	 	
 	 	return false; 
 	 }
 
	 /* Function to collect fields from a $_POST string. Can be overridden per editor */ 
	 function getPostData($post)
	 {
		//$this->addChildren(); 
		$this->setupData(); 
		
	 	$data = array(); 
	 	foreach($this->fields as $field_name => $field)
	 	{
	 		$data[$this->name][$field_name] = $field->getPostData($post) ; 
	 	}
	 	foreach($this->children as $child) 
	 	{
	 		if ($child['element'] == 'field') 
	 		{
	 			$field = $child['obj']; 
	 			$field_name = $field->get('name');
	 			$data[$this->name][$field_name] = $field->getPostData($post);
	 		}
	 	}
	 	foreach($this->panel as $field_name => $field) 
	 	{
	 		$data[$this->name][$field_name] = $field->getPostData($post); 
	 	}
	 	
	 	return $data;
	 }

	public function set($field, $value) 
	{
			$this->$field = $value;
			return $this;
	}
 
 
 	 
	 function setData($data, $default = true)
	 {
	 	$this->data = $data;
	 	$this->do_default = $default;
	 	return $this;
	 }

	 
	 
	 function setupData()
	 {
	 	$data = $this->data;
	 	$default = $this->do_default; 
		if (isset($this->parent) && $this->parent !== '') 
			$default_name = $this->parent;
		else
			$default_name = $this->name;
						 	
	 	foreach($this->fields as $field_name => $field) 
	 	{
			$value = null; 
	 		if (isset($data[$this->name][$field_name]))
	 		{	
	 			$value = $data[$this->name][$field_name]; 	
			}	
			elseif($default)
			{
				$value = MI()->templates()->get()->findDefault($default_name, $field);
			}
			if (! is_null($value))
				$this->fields[$field_name]->set('value',$value); 	
	 	}
	 	foreach($this->children as $child)
	 	{
	 		$value = null;
	 		
	 		if ($child['element'] == 'field') 
	 		{
	 			$field = $child['obj'];
	 			$field_name = $field->get('name');

		 		if (isset($data[$this->name][$field_name]))
		 		{	
		 			$value = $data[$this->name][$field_name]; 	
				}	
				elseif($default)
				{
					$value = MI()->templates()->get()->findDefault($default_name, $field);
				}
				if (! is_null($value))
					$field->set('value',$value); 
		 	}
	 	}
	 	
	 	foreach($this->panel as $field_name => $field)
	 	{
	 		$value = null;
	 		
	 		if (isset($data[$this->name][$field_name])) 
	 			$value = $data[$this->name][$field_name] ;
			elseif($default)
				$value = MI()->templates()->get()->findDefault($this->name, $field);	 	
			if (! is_null($value)) 
				$this->panel[$field_name]->set('value', $value);	
	 	}
	 	
	 	$this->registerDocumentField();

	 }

	/** Run the editor through the fields and add values and options directly to the templates for output **/
	public function view()
	{
		$domObj = $this->field;
		
		$this->setupData(); 
		
		MI()->tell('editor/view/start', array('name' =>  $this->name,
										      'editor' => static::$editor_name,
											  'document' => $domObj, 
										));
		
		foreach($this->fields as $field_name => $field) 
		{
			$field->view($domObj);
			MI()->tell('editor/view/' . $field_name, 
				array('document' => $domObj,
					  'field' => $field)
			
			);
			
		}
		foreach($this->children as $child) 
		{
			if ($child['element'] == 'field') 
			{
				$field = $child['obj']; 
				$field->view($domObj);	
				$field_name = $field->get('name');		
				MI()->tell('editor/view/' . $field_name, 
					array('document' => $domObj,
						  'field' => $field) );
			}
		
		}
		foreach($this->panel as $field_name => $field) 
		{
			$field->view($domObj);
		}

		MI()->tell('editor/view/end', array('name' =>  $this->name,
										    'editor' => static::$editor_name, 
										    'document' => $domObj,
									)); 			
	}
	
	/** Bound the editor to a field in the template **/
	protected function registerDocumentField() 
	{
		if (isset($this->parent) ) 
		{
			$docfield = $this->parent;
		}
		else
		{
			$docfield = $this->name; 
		}
	
				
		foreach($this->fields as $fname => $field) 
		{
			$field->set('document_field', $docfield);
		
		}
	
	}

	protected function setupAdmin() 
	{

		// process additional fields
		//$this->addChildren(); 
		$this->setupData();
		$this->registerDocumentField();
	
		// setup all template fields
	 	$tpl = new \stdClass; 	 
	 	
	 	if(isset($this->title) && $this->title != '')
			$tpl->label = $this->title; 
	
	 	$tpl->name = $this->name; 	 	
	 	$tpl->editor_name = static::$editor_name; 
	 	$tpl->content = ''; 
	 	$tpl->has_panel = ( count($this->panel) > 0) ? 'true' : 'false';
		return $tpl;
	}
	

	/** Add a child element to this editor. A child can be a Field type or an Other editor
		@param String Name of the child
		@param Array  Attributes of the child ( type is required ) 
	*/
	public function addChild($object, $element) 
	{
		/*if (! isset($attrs["type"])) 
		{	
			MI()->errors()->add(new \Exception('No type set on ' . $name . ' field')); 
			continue; 
		}*/	
		$this->children[] = array('obj' => $object,
								  'element' => $element,
								 );
		return;
		
		$element = $attrs['element']; // editor or field. 
		$type = $attrs["type"]; 
		$title = isset($attrs["title"]) ? $attrs['title'] : ''; 
		$scope = isset($attrs['scope']) ? $attrs['scope'] : false; 
		
		$options = isset($attrs['options']) ? $attrs['options'] : false; 

		$option_array = array(); 
		if ($options) 
		{
			$option_array = shortcode_parse_atts($options); 
		
		}
		
		$field = false;

		
		try // technically not needed now - maybe good for error catching though
		{
			$field = MI()->editors()->getFieldClass($type); 
		}
		catch (\Exception $e) 
		{ 
			MI()->errors()->add($e); 
		} 

		if ($field)
		{

			$this->fields[$name] = MI()->editors()->getNewField($name, $type)
								   ->set('title', $title);
			
			foreach($option_array as $name => $value) 
				$this->fields[$name]->set($name, $value); 

		}
	}	
	

	public function admin() 
	{
 
		$tpl = $this->setupAdmin(); 
		$child_output = ''; 
		
		foreach($this->fields as $field) 
		{
			$tpl->content .= $field->admin(); 
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


}

  
