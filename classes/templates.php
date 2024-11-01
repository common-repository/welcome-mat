<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miTemplates
{
	protected $current_template = null;
	private static $instance;

	protected $templates_paths = array();
	protected $template_array;

	static function getInstance()
	{
		if (is_null(self::$instance))
		{
			$inst = new miTemplates();
			self::$instance = $inst;
			if (is_null(self::$instance))
				die("Fatal error, templates instance null after init");
		}

		return self::$instance;

	}

	public function __construct()
	{
		MI()->offer('system/templates', array($this, 'getLocal') );
 		add_action('before_delete_post', array($this, 'delete') );
		add_action('save_post', array($this , 'save'));
	}

	/** Get the core path for templates */
	public function getTemplatesPaths()
	{
		return $this->templates_paths;
	}

	public function getTemplates()
	{
		if (! isset($this->template_array) )
		{
			$template_array = array();
			$tpl_sort = array();

			$responses = MI()->collect('system/templates');
			foreach($responses as $response)
			{
				foreach($response as $index => $templ_item)
				{
					$tpl_sort[] = $templ_item['nicename'];
					$template_array[] = $templ_item;
				}

			}
			array_multisort($tpl_sort, SORT_ASC, $template_array);
			$this->template_array = $template_array;

		}

		return $this->template_array;
	}

	/** Find the Template definition by name **/
	public function findTemplate($name)
	{
	 	$template_array = $this->getTemplates();

	 	foreach($template_array as $templ_item)
	 	{
		 	if (isset($templ_item['name']) && $templ_item['name'] == $name)
						return $templ_item;


		}
		return false;
	}

 	/* Function to find all defined templates in plugin */
 	public function getLocal()
 	{
		$path =  MI()->get_plugin_path() . 'templates/';
		$url = MI()->get_plugin_url() . 'templates/';
		return $this->findTemplates($path, $url);
	}


	public function findTemplates($path, $url)
	{
 		$this->templates_paths[] = $path;
		$dir_iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
		$iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

		$template_array = array();

		foreach ($iterator as $fileinfo) { // get template maps
			if ($fileinfo->isDir() )
			{
				$template_name = $fileinfo->getFilename();

				$tpath = trailingslashit($path . $template_name);
				$turl = trailingslashit($url . $template_name);

				$readme = $tpath . "/" . $template_name . ".txt";

				$tdata = $this->getTemplateInfo($readme);

				$preview_image = '';

				if (file_exists($tpath . $template_name . '.png'))
					$preview_image = $turl . $template_name . '.png';
				elseif (file_exists($tpath . $template_name . '.jpg'))
					$preview_image = $turl . $template_name . '.jpg';
				elseif (file_exists($tpath . 'preview.png'))
					$preview_image = $turl. 'preview.png';
				elseif (file_exists($tpath . 'preview.jpg'))
					$preview_image = $turl . 'preview.jpg';


				if (file_exists($tpath  . $template_name . '.tpl'))
				{
					$template_array[] = array("name" => $template_name,
										 "description" => (isset($tdata["description"])) ? $tdata["description"] : '',
										 "author" => (isset($tdata["author"])) ? $tdata["author"] : '',
										 "nicename" => (isset($tdata["name"])) ? $tdata["name"] : ucfirst($template_name),
										 "url" => $turl,
										 "path" => $tpath,
										 "type" => (isset($tdata["type"])) ? $tdata['type'] : 'page',
										 "preview_image" => $preview_image,

										);
				}
			}
		}
		sort($template_array);


 		return $template_array;
 	}

 	public function getTemplateInfo($readme_file)
 	{
 		$template_info = array();
 		if (! file_exists($readme_file))
 			return $template_info;

 		$template_headers = array(
			'name' => 'Name',
			'author' => 'Author',
			'description' => 'Description',
			'textdomain' => 'Textdomain',
			'template' => 'Template',
		);

		$template_info = get_file_data($readme_file, $template_headers);
		return $template_info;
 	}

	/** Update the Template
	*	This function hooks in to the WordPress Post Flow
	*
	**/
	public function update($post_id)
	{

	  	// yes OMG wordpress - from update postmeta
	   if ( $the_post = wp_is_post_revision($post_id) )
      	  $post_id = $the_post;


		$template_name = sanitize_text_field($_POST['template']);
		update_post_meta($post_id, "_maxinbound_template", $template_name);

		// POST
		$template = $this->load($post_id);

		$dataArray = array();


		foreach ( MI()->editors()->all() as $editor )
		{
			$data = $editor->getPostdata($_POST);
			$dataArray = array_merge($dataArray, $data);

		}

        update_post_meta($post_id, "_maxinbound_data", $dataArray);

	}

	/** Load a template
	*
	*	Loads a template by post_id, sets all data.
	* 	@param $post_id integer
	*
	*/
	public function load($post_id, $args = array() )
	{

		$template = new miTemplate($post_id);
		if (! $template->check() )
		{
			MI()->errors()->add( new \Exception (" Template $post_id didn't pass check ") );
			return false;
		}
		$this->current_template = $template;
		$template->load($args);
		return $template;

	}

	/** Save post template
	*	This function is called via the WordPress save post flow
	*
	*	@param int $post_id The post ID of the template
	*
	**/
	public function save($post_id)
	{
		$post_type = MI()->ask('system/post_type');

		if (! isset($_POST) || count($_POST) == 0) return $post_id;

		if (! isset($_POST["post_type"]) || $post_type != $_POST['post_type'] )
		 return $post_id; // not for us

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		if (defined('DOING_AJAX'))
			return $post_id; // quick edit

		// Check the user's permissions.
		if ( $post_type == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		$this->update($post_id);
		MI()->tell('editor/save-options', $_POST);

	} // save_post

	/** Delete a template
	*
	* 	Deletion of the template itself is done by WP. Delegate to any modules deletion on custom tables like statistics and emails. Check before giving the sign if the post type is correct.
	*/
	public function delete($post_id)
	{
		global $post_type;
		$system_type = MI()->ask('system/post_type');
		if ($system_type === $post_type)
		{
			MI()->tell('template/delete', $post_id);

		}
	}


	/** Get the current active template
	*
	*	In Editor, get the template being currently edited.
	*/
	public function get()
	{
		if (is_null($this->current_template))
			throw new \Exception('No templates loaded');

		return $this->current_template;

	}

} // class
