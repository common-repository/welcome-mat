<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miTemplate
{
	protected $post_id; 
	
	public $template_name; 
	protected $template_path; 
	public $template_url; 
	public $template_type; // ( page / part / box ) 
	protected $template_filepath;
    protected $fields_filepath; 
 
	protected $document_fields = array(); // field found in template, with field tag and their definitions. 
	protected $fields = array(); // defined fields in xml
	protected $data = array();  // set field data
	protected $options = array(); // set field options. 
	
	var $parts = array(); // tabs

	// parse data
	var $current_part = null; 

	var $csstidy = null; // CSSTidy instance
	var $cssparser = null; // CssParser (new)
	var $element_css = array(); 
	
	protected $document;  // Dom Object
	
	var $is_preview = false; 
	var $is_parsed = false; 
	var $has_data = false;

	function __construct($post_id)
	{
		$template_name = get_post_meta($post_id, '_maxinbound_template', true); 

		if ($template_name == '') 
		{
			MI()->errors()->add( new \Exception ("Template of $post_id does not exist") ); 		
			return; // not MIB, don't mingle. 
		}
			
		$template_data = MI()->templates()->findTemplate($template_name);

		$this->template_path = $template_data['path']; 
		$this->template_url = $template_data['url']; 
		
		$this->template_filepath =  $this->template_path . $template_name . '.tpl';	

		$info = $this->template_path . $template_name . '.txt'; // template info file  	
		$info = MI()->templates()->getTemplateInfo($info);

		if (isset($info['template']) && $info['template'] !== '')
		{
			$paths = MI()->templates()->getTemplatesPaths(); 

			foreach($paths as $path)
			{
				if (file_exists($path . $info['template'] . '.xml')) 
					$fields_filepath = $path . $info['template'] . '.xml'; 
				elseif(file_exists($path . $template_name . '/'. $info['template']  . '.xml'))
					$fields_filepath = $path . $template_name . '/'. $info['template'] . '.xml';
		
				if (isset($fields_file_path))
					break;
			}
		}
		else
		{		
			$fields_filepath = $this->template_path . "$template_name.xml";
 		}

		if (! isset($fields_filepath)) { 

			MI()->errors()->add( new \Exception('Template XML Definition file not found') );
 			return;
		 }
		 
 		$this->fields_filepath = $fields_filepath; 
 		
		$this->template_name = $template_name;
		
		if (! file_exists($this->template_filepath)) { 

			MI()->errors()->add( new \Exception('Template not found') );
 			return;
		 }

		MI()->tell('template/post-id', $post_id); 
		MI()->tell('template/post-url', get_permalink($post_id)); 
		$this->post_id = $post_id; 
	}
	
	/** Check if the template construct has been properly finished without errors
		@return boolean
	*/
	function check() 
	{
		$post_id = MI()->ask('template/post-id');

		if ( is_null($this->template_path) )
			return false;
		if ( is_null($this->fields_filepath) )
			return false; 
		if ( is_null($post_id )) 
			return false; 
					
		return true;
	}

	function load($args = array() ) 
	{
		$defaults = array( 
			"preview" => false, 
		); 
		$args = wp_parse_args($args, $defaults); 
		MI()->tell('template/load', array_merge($args, array('id' => $this->post_id) ) ); 
		
		$post_id = $this->post_id; 
		$this->setPreview($args["preview"]);
		
		if( $post_id > 0 ) 
		{
			$data = get_post_meta($post_id, "_maxinbound_data", true); 
 			$this->options = get_post_meta($post_id, '_maxinbound_options', true);
 			
 			if ($data == '')  // no data / empty values
 			{
 				$this->has_data = false; 
 			}
 			else
 			{
 				$this->has_data = true; 
 				$this->data = $data; 
 			}
 				
 		}

		/* In time might be moved to something more 'cached'  */
		if (! $this->is_parsed)
		{
			$this->parseFields(); 
			$this->parseTemplate(); 
		} 	
	}

	function getData()
	{
		return $this->data;
	}
	
	function hasData() 
	{
		return $this->has_data; 
	}

	function getParts() 
	{
		return $this->parts;
	
	}
	
	function getFields () 
	{
		return $this->fields; 
	}
	
	function getOptions() 
	{
		return $this->options;
	}
	
	function getTemplateName() 
	{
		return $this->template_name;
	}
	
	function getTemplatePath() 
	{
		return $this->template_filepath;
	}
	
	function getFieldsPath() 
	{
		return $this->fields_filepath;
	}
	
	function getDomDocument() 
	{
		return $this->document; 
	}
	
	function setPreview($bool = true)
	{
		$this->is_preview = $bool;
	}
	
	/** Loads the Fields XML file with definitions **/ 
	function parseFields()
	{
		if (! function_exists('simplexml_load_file')) 
		{
			MI()->errors()->add(new \Exception('Simple Load File does not exists! Probable module error') );  
			return false;
		}
		
		// some decent error handling
		\libxml_use_internal_errors(true);
		
		$xml = \simplexml_load_file($this->fields_filepath);

		if (!$xml) {
    		$errors = \libxml_get_errors();
    		foreach($errors as $liberror)		
			{
				
				$error = new \Exception('[LIBXML] ' . $liberror->message, $liberror->code);
				MI()->errors()->add($error);  
			}
			return false;
		}
		$this->recurse_fields($xml);

	}
	
	/** Recurses through the XML definitions file **/ 
	private function recurse_fields($root, $prefix = '')  // curse!
	{

		foreach($root->children() as $name => $field) 
		{	
			if ($name == 'part') 
			{
				$part_name = $field->attributes()->name; 
 
				$this->current_part = (string) $part_name; 
				$this->parts[$this->current_part] = array();
			}			
			
			if (isset($field->attributes()->editor) )
			{	
				
				$attrs = $this->extract_attributes($field->attributes()); 
				$children = $field->children(); 
					
				if ($prefix != '') // not sure if in use . 
				{
					if (! in_array($prefix, $this->parts[$this->current_part]))
						$this->parts[$this->current_part][] = $prefix; 	
					$attrs["field"] = $prefix; // the parent / main field of operation
					$this->fields[$prefix][$name] = $attrs; 
				} 
				else
				{ 
					$attrs["field"] = $name; 
					$this->parts[$this->current_part][] = $name; 
					$this->fields[$name] = $attrs; 
					if (count($children) > 0)
						$this->fields[$name]["children"] = $this->parseChildFields($children, $name);
				}	

										
 			}
 			else
 			{
 				if ($name == 'part') $name = $prefix; 
 				$this->recurse_fields($field, $name);
 			}
 		}	
	}
	
	/** Parse the XML file's Children and attach attributes to the child **/
	private function parseChildFields($children, $parent_name)
	{
		$array = array(); 
		foreach($children as $element => $child)
		{
			$attrs = $this->extract_attributes($child->attributes()); 

			$type = isset($attrs["type"]) ? $attrs["type"] : ''; 
			//$scope = isset($attrs['scope']) ? $attrs['scope'] : false; 		

			if (! isset($attrs['name'])) 
			{
				$name = $parent_name;
				$name = ($type != '') ? $parent_name . "_$type" : '';
			}
			else
				$name = $attrs['name']; 
		
			$array[$name] = $attrs; // just like parent does
			$array[$name]['element'] = $element; 	
		}
		return $array; 	
	}
	
	/** Takes the attributes from an XML defined field **/
	private function extract_attributes($attr)
	{

		$editor =(isset($attr->editor)) ? (string) $attr->editor : '';
		$group = (isset($attr->group)) ? (string) $attr->group : '';
		$name = (isset($attr->name)) ? (string) $attr->name : ''; 
		$type = (isset($attr->type)) ? (string) $attr->type : ''; 

		$default = (isset($attr["default"])) ? (string) $attr["default"] : ''; 
		$title = (isset($attr["title"])) ? (string) $attr["title"] : ''; 
		$target = (isset($attr["target"])) ? (string) $attr["target"] : '';
		//$unit = (isset($attr["unit"])) ? (string) $attr["unit"] : ''; 
		$options = (isset($attr["options"])) ? (string) $attr["options"] : ''; 
		$scope = (isset($attr["scope"])) ? (string) $attr["scope"] : '';
		 
		$attrs = array(
				"editor" => $editor,
				"group" => $group, 
				"name" => $name, 
				"type" => $type, 
				"value" => '',
				"default" => $default,
				"title" => $title,
				"target" => $target,
		//		"unit" => $unit, 
				"options" => $options,
				"scope" => $scope, 
				); 

		return array_filter($attrs); 	
	}
	
	/** Render the template fields and editors
	*
	*/
	protected function parseTemplate($args = array())
	{
		if (! file_exists($this->template_filepath)) 
			return false;
			
		$domObj = \pQuery::parseFile($this->template_filepath);

		$this->document = $domObj; 
		
		// check for a css declaration 
		$stylesheets = $domObj->query('link[rel=stylesheet]'); // multiple.

		$this->parseStyleSheets($stylesheets);

		$scripts = $domObj->query('script');

		$this->parseJS($scripts);
		
		// This is a new pQuery object!
		$queryObj = $domObj->query('[field]');
 		$iterator = $queryObj->getIterator(); 
 
		while($iterator->valid()) 
	 	{
			$tfield = $iterator->current();
			
	 		$name = $tfield->attr('field'); 	 
			$this->document_fields[$name] = array('tag' => $tfield->getTag(), 'field' => $tfield ); 
			MI()->tell('template/field/' . $name, $this->document_fields[$name]);
						
	 		if ( $this->is_preview)
	 			$tfield->class .= " field field-$name"; 
	 		else
	 			$tfield->class .= " $name"; 
 			
	 		// compare to field definitions 
	 		if (! isset($this->fields[$name])) 
	 		{
	 			$iterator->next(); // prevent getting stuck
	 			continue; 
 			
 			}
				if ($this->is_preview)
		 		$tfield->id = $name; 	 	
 
				$options = $this->fields[$name];
				$title = isset($options['title']) ? $options['title'] : ''; 
				$scope = isset($options['scope']) ? $options['scope'] : ''; 
				$editor_options = isset($options['options']) ? $options['options'] : false;
				
				if ($editor_options) 
					$option_array = shortcode_parse_atts($editor_options); 
				
				
				$editor = MI()->editors()->getNew($options["editor"], $name)
							->set('title', $title)
							->set('field', $tfield)
							->setData($this->data, ! $this->has_data)
							;
				if ($scope !== '') 
					$editor->set('scope', $scope);
				
				if (isset($option_array)) 
				{
					foreach($option_array as $option_name => $option_value) 
					{
						$editor->set($option_name, $option_value);
					}
				}
							 
				$children = isset($options["children"]) ? $options["children"] : false;
				

				// inverse - if no data then do defaults. 	
				
				if($children) 
					$this->parseTemplateChildren($editor, $children, $tfield, $name); 

				//Causes views to load in editor.
				//$editor->set('field', $tfield);

 			$tfield->class = trim($tfield->class); 
 			
		    $iterator->next();	 			
	 	}
	 	
	 	$iterator->rewind();

		$this->is_parsed = true;
	}
	
	/** Main Child Template parser. Add child to the editor child property */ 
	protected function parseTemplateChildren($editor, $children, $tfield, $parent_name) 
	{
		
		$curChildren = array(); 
 		foreach($children as $name => $child) 
 		{
 			$type = isset($child['type']) ? $child['type'] : '';  
 			$title = isset($child['title']) ? $child['title'] : ''; 
 			$scope = isset($child['scope']) ? $child['scope'] : ''; 
			$options = isset($child['options']) ? $child['options'] : false; 
			$element = $child['element']; 

			$option_array = array(); 
			if ($options) 
				$option_array = shortcode_parse_atts($options); 
		 			
 			if ($element == 'field') 
 			{
 				$child_name = $name . '_' . $type; 
 				$field = MI()->editors()->getNewField($name, $type)
						   ->set('title', $title)
						   ->set('document_field', $tfield)
						   ;
			
				if ($scope !== '') 
					$field->set('scope', $scope);
					
				foreach($option_array as $option_name => $option_value) 
				{
					$field->set($option_name, $option_value); 
				}	
 				$editor->addChild($field, $element); 
 			}
 			elseif ($element == 'editor') 
 			{
				$child_editor = MI()->editors()->getNew($type, $name) 
					 	 	->set('title', $title )	
					 	 	->set('parent', $parent_name)
					 	 	->set('field', $tfield)
					 	 	->setData($this->data, !$this->has_data);
				if ($scope !== '') 
					$child_editor->set('scope', $scope); 	 	

				foreach($option_array as $option_name => $option_value) 
				{
					$child_editor->set($option_name, $option_value); 
				}	
									 	 	
				$editor->addChild($child_editor, $element);
 			}
 		}

 		//$editor->set('children', $curChildren);
 		//return $editor;
	}
	
	/** Parse the template stylesheet ( from file ) **/
	protected function parseStyleSheets($sheets)
	{
		$styles = ''; 
		$parsed_url = parse_url(MI()->get_plugin_url()); 

		foreach($sheets as $sheet) // Parse all the sheet. 
		{
			$local = true; 
			$url = parse_url($sheet->href); // find out if it's local.

			if (isset($url["host"]) && $url["host"] !== $parsed_url["host"])
			{
				$local = false; 
				if ($this->is_preview)
				{
					$sheet->remove();
				}
					
				continue; // leave remotes to rest. 
			}	

			if (! file_exists($this->template_path . $sheet->href)) 
			{
			  MI()->errors()->add( new \Exception('Template stylesheet not found :' . $this->template_path . $sheet->href) );
			  return false;
			}
			$styles .= file_get_contents($this->template_path . $sheet->href);
							
			if (! $this->is_preview)  // read the file or just link.
			{
				$sheet->href = $this->template_url . $sheet->href; 
					
			}
			else
			{
				$sheet->remove();
			}
		}

	//	if ( $this->is_preview)
	//	{ 
			$this->loadCSSParser($styles);
	//	}
	}
	
	protected function parseJS($scripts)
	{

		$parsed_url = parse_url(MI()->get_plugin_url()); 
		
		foreach($scripts as $script)
		{
			if (! isset($script->src)) 
				continue; // only things with a link
				
			$url = parse_url($script->src); 
		
			if (isset($url["host"]) && $url["host"] !== $parsed_url["host"])
			{
				$local = false; 
				if ($this->is_preview)
				{
					$script->remove();
				}
					
				continue; // leave remotes to rest. 
			}	
			
			if (! $this->is_preview)  // read the file or just link.
			{
				$script->src = $this->template_url . $script->src; 
					
			}
			else
			{
				$script->remove();
			}			
		
		}
	}
	
	/** Find default value of field in styles
	*
	* Attempts to auto-find the default value for a field. On field the variable scope should be set unless the field is of a default 'text' type
	*
	* @param $name String Field Name
	* @param $field Object A miField object
	*/
	public function findDefault($name, $field) 
	{

		// Find the field in the template.
		$tag = $this->document->query('[field=' . $name . ']'); 		

		if ($tag->count() > 0) 
			$tag_element = $tag[0]; 
		else
			return null; // Field that we are looking for not found in the document.

		// Get the CSS name 'scope' from the asked field.
		$scope = $field->get('scope');
		$pseudo = $field->get('pseudo'); 
		
		if ($scope == 'text' || $scope == '') // special
		{
			if (isset($tag_element->children[0]))
			return $tag_element->children[0]->text(); 
		}

		$tag_class = ''; 
		if (isset($tag_element->class))
			$tag_class = $tag_element->class; 
					
		// If the CSS rule is set on the field directly.
		if (isset($tag_element->{$scope}))
			return $tag_element->{$scope}; 
		// Test the Attr element for the scope
 
		if (! is_null($tag_element->attr($scope)))
		{
			return $tag_element->attr($scope);
		}
		
		
		$parser = $this->getCSSParser(); 
 		$tag_class = trim(str_replace(array('field-' . $name, 'field'),'', $tag_class)); // remove template field names from declarations.

		if (! is_null($pseudo) && ! $pseudo === false )
			$tag_class .= ':' . $pseudo;

		
		$result = $parser->getNearestDeclaration($tag_class);

		$best_value = null; 
		$max_score = 0; 

		foreach($result as $data)  // For each found result check if it has the CSS Rule, and check the highest score.
		{
			$rule = $data['rule']; 
			$score = $data['score']; 
			
			if( $rule == $scope) 
			{
				$value = $data['value']; 
				$selector = $data['selector'];
 
				if ($score > $max_score)
				{
					$best_value = $value;
				}
			}
		} 

		return $best_value; 
	}

	/** Check if the field from the fields XML file is actually present in the template document. */
	public function isFieldInTemplate($name)
	{
		if (isset($this->document_fields[$name])) 
			return true;
		else
			return false;
	}

	public function getCSSParser() 
	{
		if (is_null($this->cssparser))
		{
			MI()->log('GetCSSParser - CSS Parser is null'); 
			return $this->loadCSSParser(''); 
		}
	
		return $this->cssparser; 
		
	}
	public function loadCSSParser($css) 
	{
		if (is_null($this->cssparser))
		{
			$cssparser = new cssParser(); 
			$cssparser->parse($css);
			$this->cssparser = $cssparser;
		}	
		
		return $this->cssparser; 
	}
	
 
	/** Generate a template preview [unused currently]
	*
	*
	*/
	function preview($args = array()) 
	{	
		MI()->tell('template/preview-start'); 
		$output = '<div class="live-preview">'; 

		$this->is_preview = true; 
		
		$document = $this->document; 
		
		if (is_array($csstidy->css))
			$css_array = array_shift($csstidy->css); 
 		
 		if (is_null($css_array))
 			$css_array = array();

		$new_css = array();
		foreach($css_array as $element => $values)
		{
			$new_css[".live-preview " . $element] = $values; 
		}
		
 		$output .= "<style type='text/css'>".  $csstidy->print->plain() . "</style>"; 
		
		$fields = $this->fields;  

		$output .= "<div class='hidden' id='maxinbound_fields'>" . json_encode($this->fields) . "</div>"; 
		$output .= $document->html();
		$output .= "</div>"; 

		echo $output; 
		MI()->tell('template/preview-end'); 
	}
	
	/** Takes the style element from rendered fields and put them in an array for CSS output.
	*
	*/
	protected function parse_css_elements()
	{
		$queryObj = $this->document->query('[field]');
 		$iterator = $queryObj->getIterator(); 
 
		while($iterator->valid()) 
		{
			$tfield = $iterator->current();
 
			$class = trim($tfield->attr('class')); 
			$class = array_filter(explode(' ', $class)); 	 	
			$class = implode('.', $class);  // join all classes on the field to increase 'importance' ( css )
			
			if($tfield->style != '')
 			{
 				$name = $class;
				$this->element_css[$name][] = $tfield->style; 
	 			$tfield->removeAttr('style'); 
			}
			if ($tfield->hover != '') 
			{
				$name = $class . ':hover'; 
				$this->element_css[$name][] = $tfield->hover; 
				$tfield->removeAttr('hover');
			}
			$iterator->next();	 
		}	
 		$iterator->rewind();					
	}
	
	function output($args= array()) 
	{
		MI()->tell('template/output-start'); 
		
		$this->parse_css_elements(); 
		
		$this->is_preview = false; 
	 	
	 	/*$title = $this->document->query('title'); 
	 	
	 	if($title)
	 	{
	 		$title->text(get_the_title());
	 	} */
	 	
		//$body = $this->document->query('body');
		$style = '<style type="text/css">'; 
 
		if (count($this->element_css) > 0) // inline css for the configurable elements
		{
			foreach ($this->element_css as $el => $defs)
			{
				$style .= " .$el { \n "; 
				foreach($defs as $def) 
				{
					$style .= " $def  \n "; 
				
				}
				$style .= "   } \n"; 
			}
		
		}
		$style .= "</style>"; 
 
 		$this->document->query('[field]')->RemoveAttr('field'); 
 		
		$ajax_action = MI()->ask('system/ajax_action'); 
 		$is_preview = is_preview(); 
 
 		$template_args = MI()->collect('template/maintag'); 
 		
 		$tag_args = array('class' => 'mib mib-' . $this->post_id . ' fullscreen', 
 						  'id' => '', 
 						  'data' => array(), 
 						 ); 
 		
 		foreach($template_args as $item) 
 		{
 			if (isset($item['class'])) 
 				$tag_args['class'] .= ' ' . $item['class']; 

 			if (isset($item['data'])) 
 				$tag_args['data'] = array_merge($tag_args['data'],$item['data']); 
 			
 		}
 			
		$main_tag = '<div class="' . $tag_args['class'] . '"'; 
		
		if ($tag_args['id'] !== '') 
			$main_tag .= ' id="' . $tag_args['id'] . '"'; 
		if (count( $tag_args['data'] ) > 0 )
		{
			foreach($tag_args['data'] as $data => $value )
			{
				$main_tag .= ' data-' . $data . '="' . $value . '"';
			}
		}
		
		echo $main_tag . ">";  		
 		 
		//echo '<div class="mib mib-' . $this->post_id . ' mib-front-template">';
		echo '<form id="mib-form" method="POST">';  
		wp_nonce_field($ajax_action,'nonce', false); 
		echo '<input type="hidden" name="action" value="' . $ajax_action . '">'; 
		echo '<input type="hidden" name="post_id" value="' . $this->post_id . '">'; 
		echo '<input type="hidden" name="is_preview" value="' . $is_preview . '">'; 

		echo $this->document->html();
		echo $style; 
		MI()->tell('template/output-end'); 
		echo '</form>'; 
		echo '</div>'; 

	}

} // class

 
 
?>
