<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class maxInbound
{
	static protected $instance = null;
	static protected $errors = null; // error handler

	var $editors = null; 	// editors
	var $templates = null; // templates
	var $modules = null;  // modules
	var $whistle = null; // communication


	/** Main constructor
	*
	* Hooks and loads all core functions
	*
	*/
	function __construct()
	{
		self::$instance = $this;

		$this->whistle = whistle::getInstance();
		$this->tell('init', '');

		maxPlugin::init(); // low level settings
		$maxplugin = maxPlugin::getInstance();
		self::$errors = new maxError();


		// The Template to load, init on false
		$this->tell('template/load', false);

		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_enqueue_scripts',  array($this, 'front_scripts') );
		add_action('wp_enqueue_scripts', array($this, 'front_ajax_scripts') );

		add_action('plugins_loaded', array($this, 'load_textdomain'));

		add_action('plugins_loaded', array($this, 'templates'));
		add_action('plugins_loaded', array($this, 'modules')); // load modules as early as possible - after construct
		add_action('plugins_loaded', function () { MI()->modules()->loadModules(); }, 998); // load them
		add_action('plugins_loaded', function () { miInstall::check_version(); }, 999); // Check the current version, and do needed updates on update. Load late due to module / custom table hooks


		add_action('init', array('MaxInbound\maxData', 'init'), 5) ; // collect some central variables like request envs. etc

		/** Admin specific action */
		if (is_admin() )
		{
			// load the templates class, init listeners and controls.
			$this->templates();

			// handle the correct post screens
			add_action('current_screen', array($this, 'redirect_new_post'));
			add_action('load_page', array($this, 'load_page'));

			// meta menu's and save functionality
			add_action('admin_menu', array($this, 'add_menu_pages'));
			add_action("edit_form_after_editor", array($this, "template_editor"));
			add_action('add_meta_boxes', array($this, 'meta_boxes' ), 10, 2); // for modules / settings

			// Not sure what these do
			add_filter('_wp_post_revision_fields', array($this, 'add_field_template_preview'));
			add_action( 'edit_form_after_title', array($this, 'add_input_template_preview' ));

		}

		// Add our landing 'templates' to WordPress.
		//add_filter("page_attributes_dropdown_pages_args", array($this,'get_landing_pages_as_templates'));

		// Add landing pages to pages list

		add_action('template_redirect', array($this, 'check_load_page'), 20 );
		$this->listen('system/ajax/check_load_page', array($this, 'check_load_page_ajax') );

		// columns in admin
		$post_type = $this->ask('system/post_type');
		add_action('manage_' . $post_type . '_posts_custom_column', array($this, 'custom_columns'),10,2 );
		add_filter('manage_edit-' . $post_type . '_columns', array($this,'edit_columns') );
		add_filter('get_media_item_args', array($this, 'force_send_on_media_item') );

		//add_filter('plugin_action_links', array($this,'plugin_action_links'), 10, 2);

		// listen for ajax actions
		$ajax_action = $this->ask('system/ajax_action');  // The name of the plugin ajax action.
		add_action('wp_ajax_' . $ajax_action, array($this, 'ajax'));
		add_action('wp_ajax_nopriv_' . $ajax_action, array($this, 'ajax') );


	}

	/** Singleton pattern

	*/
	public static function getInstance()
	{
		return self::$instance;
	}

	/** Returns the path of the plugin
	*
	*	@return string full plugin root path
	*/
	public function get_plugin_path()
	{
		return trailingslashit(plugin_dir_path(WM_ROOT_FILE));
	}

	/** Returns the url of the plugin
	*
	*	@return string full url of plugin root
	*/
	public function get_plugin_url()
	{
		return trailingslashit(plugin_dir_url(WM_ROOT_FILE));
	}

	/** Public access function for editors
	*
	* This function provides an interface to access the template editors
	*
	*	@return Object returns Editors object.
	*/
	public function editors()
	{
		if (is_null($this->editors))
			$this->editors = miEditors::getInstance();

		return $this->editors;
	}

	/** Public access function for templates
	*
	* Provides an interface for interacting with the template functions.
	*
	*	@return Object returns Templates object
	*/
	public function templates()
	{
		if (is_null($this->templates))
			$this->templates = miTemplates::getInstance();

		return $this->templates;
	}

	/* Public access function for modules

	Provides an interface for interacting with modules

		return Object Modules object
	*/
	public function modules()
	{
		if (is_null($this->modules))
			$this->modules = miModules::getInstance();

		return $this->modules;
	}

	/* Public access function for data

	Provides an interface for interacting with data class

	return Object MaxData object
	*/
	public function data()
	{
		return maxData::getInstance();
	}


	public function db($sql, $function = 'query', $format = OBJECT )
	{
		if ($function == 'query')
			$results = miDatabase::query($sql, $format);

		if ($function == 'get_var')
			$results = miDatabase::getvar($sql, $format);

		if ($function == 'get_col')
			$results = miDatabase::getcol($sql, $format);

		return $results;
	}

	/** Whistle ask function
	*
	* Provides the ability to ask certain information from the plugin when it becomes available.
	*
	* @param msg String the information requested
	* @param args Array extra options
	* @param respond Function Callback function
	*
	* @return Mixed Answer from the responsible part.
	*/
	public function ask($msg, $respond = null)
	{
		return $this->whistle->ask($msg, $respond);
	}

	public function tell($msg, $args = array(), $priority = 1)
	{
		return $this->whistle->tell($msg, $args, $priority);
	}

	/* Event listener

	Function can hook to an event occuring.

		@param $msg Event name
		@param $callback Callback to function on event
		@param $direction Listen to 'tell' events or 'ask' events.

	*/
	public function listen($msg, $callback, $direction = 'tell')
	{

		return $this->whistle->listen($msg, $callback, $direction);
	}

	public function offer($msg, $callback, $args = array(), $priority = 10)
	{
		$this->whistle->offer($msg, $callback, $args, $priority);
	}

	public function collect($msg, $args = array() )
	{
		return $this->whistle->collect($msg, $args);

	}

	public function log($msg, $args = array())
	{
		return $this->whistle->log($msg, $args);
	}

	public function errors()
	{
		return static::$errors;
	}

	/** Enqueue Core scripts
	*
	* Enqueues the wp-admin core plugin scripts and styles. WP Hook.
	*
	*/
	function enqueue_scripts($hook)
	{
		if (! $this->is_our_admin() )
			return;

		$plugin_url = self::get_plugin_url();

		$sysslug = MI()->ask('system/slug');
		$version = MI()->ask('system/version');
		$ajax_action = $this->ask('system/ajax_action');

		//wp_enqueue_style($sysslug . '-colorpicker-css', $plugin_url . 'assets/libraries/colpick/css/colpick.css');
		wp_enqueue_style($sysslug . '-admin-css', $plugin_url . 'assets/css/admin.css');

		wp_enqueue_media();

		wp_register_script($sysslug . '-admin-js', $plugin_url . 'js/maxinbound_admin.js', array('jquery', 'wp-color-picker'), $version, true);
		wp_enqueue_script($sysslug . '-maxtabs', $plugin_url . 'js/maxtabs.js', array('jquery',$sysslug . '-admin-js'), $version, true);

		wp_register_script($sysslug . '-maxajax', $plugin_url . 'js/maxajax.js', array('jquery'), $version, true);
		wp_localize_script($sysslug . '-maxajax', 'maxajax', array(
						'ajax_action' => $ajax_action,
						'nonce' => wp_create_nonce($ajax_action),
						'ajax_url' => admin_url('admin-ajax.php'),
					) );
		wp_enqueue_script($sysslug . '-maxajax');

		wp_register_script($sysslug . '-maxmodal', $plugin_url . 'js/maxmodal.js', array('jquery', $sysslug . '-admin-js'), $version, true);

		wp_register_style($sysslug . '-font-awesome', $plugin_url . 'assets/libraries/font-awesome/css/font-awesome.min.css', null, $version);

		$post_type = $this->ask('system/post_type');
		$use_template_url = admin_url('edit.php?post_type='. $post_type . '&page=maxinbound_new');

		wp_localize_script($sysslug . '-admin-js', 'mib',
				array(
				'ajax' => admin_url('admin-ajax.php'),
				'use_template_url' => $use_template_url,
		));

 		// translations of controls and other elements that can be used in maxmodal
 		$translations = array(
 				'yes' => __("Yes","maxinbound"),
 				'no' => __("No","maxinbound"),
 				'ok' => __("OK","maxinbound"),
 				'cancel' => __("Cancel","maxinbound"),
 		);
 		wp_localize_script($sysslug . '-maxmodal', 'modaltext', $translations);

		wp_enqueue_script($sysslug . '-admin-js');
		wp_enqueue_script($sysslug .  '-maxmodal');

		wp_enqueue_style('wp-color-picker');


		$this->tell('setup/enqueue-scripts');
	}

	/** Front Scripts Enqueue
	*
	* Hooks into WP's front script enqueue function
	*/
	public function front_scripts($override = false)
	{
		if (! MI()->ask('template/queued') && ! $override )
			return; // if nothing is in queue.

		$post_id = MI()->ask('template/post-id');

		if ( (! $post_id || $post_id <= 0) && ! $override )
			return; // something wrong

	 	$plugin_url = self::get_plugin_url();

		$sysslug = MI()->ask('system/slug');
		$version = MI()->ask('system/version');

		$template_name = MI()->ask('template/template-name');
		$template_obj = MI()->templates()->findTemplate($template_name);
		$template_nicename = '';
		if ($template_obj)
			$template_nicename = $template_obj['nicename'];

 		wp_enqueue_style($sysslug . '-front', $plugin_url . 'assets/css/front.css');

 		wp_enqueue_script('jquery-validate', $plugin_url . 'assets/libraries/jqvalidate/jquery.validate.min.js', array('jquery'), $version, true);

		wp_enqueue_script($sysslug . '-front', $plugin_url . 'js/front.js', array('jquery', 'jquery-validate'), $version, true);

  		// FA. Can be activated on demand.
 		wp_register_style($sysslug . '-font-awesome', $plugin_url . 'assets/libraries/font-awesome/css/font-awesome.min.css', null, $version);

  		wp_localize_script($sysslug . '-front', 'mibfront', array(
 		'ajaxurl' => admin_url('admin-ajax.php'),
 		'ajaxaction' => $this->ask('system/ajax_action'),
 		'template_nicename' => $template_nicename,
 		'plugin_slug' => $sysslug,

 		)	);
 		$this->tell('template/scripts');
	}

	public function front_ajax_scripts()
	{

		if (MI()->ask('system/front_caching') )
		{
			$this->front_scripts(true);

			$sysslug = MI()->ask('system/slug');
			$version = MI()->ask('system/version');
		 	$plugin_url = self::get_plugin_url();

			wp_register_script($sysslug . '-front-ajax', $plugin_url . 'js/front_ajax.js', array($sysslug . '-front'), $version, true);

			wp_enqueue_script($sysslug . '-front-ajax');
		}
	}

	/** Ajax Action handler
	*
	*	Entry point for all plugin ajax actions. Checks nonce and delegates event to caller. Should be used by modules also
	*/
	public function ajax()
	{
		$nonce = isset($_POST["nonce"]) ? $_POST["nonce"] : false;
		$nonce_required = isset($_POST['nonce-required']) ? sanitize_text_field($_POST['nonce-required']) : false;
		$ajax_action = $this->ask('system/ajax_action');

		if (! $nonce && $nonce_required)
		{
				exit(__("Nonce not set","maxinbound"));
		}
		elseif ($nonce_required)
		{
			if (! wp_verify_nonce($nonce, $ajax_action) )
				exit(__('Nonce not verified','maxinbound'));
		}

		$action = isset($_POST['plugin_action']) ? sanitize_text_field($_POST['plugin_action']) : '';
		$post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : false;
		$this->tell('system/ajax/post-id', $post_id);

		$this->tell('system/ajax/' . $action, $_POST ); // different name.

		exit();
		//exit( __('End of AJAX call, no response', 'maxinbound') );
	}

	/** Check if screen is part of plugin
	*
	* Checks if the current admin screen is indeed part of the plugin scope. Features should check this before loading
	* to prevent load on non-plugin parts of WordPress
	*
	* @return bool True if part of plugin, false if not
	*/
	public function is_our_admin()
	{
		$landing_page = false;
		$post_type = $this->ask('system/post_type');

		if ( isset($_GET["post_type"]) == $post_type)
			return true;

		if (! isset($_GET["post"]) || $_GET['post'] == '')
			return false;
		else
		{
			$post = get_post( intval($_GET["post"]) );
			if (isset($post->post_type) && $post->post_type == $post_type)
				return true;
		}

		return false;

	}
	/* Check if front item is landing page

	Checks if the current loaded page on the front end is part of the plugin

	@return bool True if plugin page, false if not.
	*/
	public function is_landing()
	{
		if (is_admin())
			return false;

		// flawed probably
		return $this->is_our_admin();

	}

	/* Load the plugin textdomain */
	public function load_textdomain()
	{
		// see: http://geertdedeckere.be/article/loading-wor=dpress-language-files-the-right-way
		$domain = 'maxinbound';
		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$locale = apply_filters('plugin_locale', get_locale(), $domain);

		load_textdomain($domain, WP_LANG_DIR.'/maxinbound/'.$domain.'-'.$locale.'.mo');
		load_plugin_textdomain('maxinbound', false, dirname(plugin_basename($this->get_plugin_path())) . '/languages/');
 	}



	public function redirect_new_post()
	{
		$screen = get_current_screen();
 		$post_type = $this->ask('system/post_type');

	 	if (! isset($_GET["post_type"]))
	 		return; // not our business.


		if ($screen->post_type == $post_type && $screen->action == 'add')
		{
			$url = admin_url('edit.php?post_type=' . $post_type . '&page=maxinbound_new');
			wp_redirect( $url );
			exit();
		}
		elseif (isset($_GET["action"]) && $_GET["action"] == 'use-template')
		{
			$template = $_GET["template"];

			$post_array = array("post_type" => $post_type
							);

			$post_id = wp_insert_post($post_array);

			update_post_meta($post_id, "_maxinbound_template", $template);

			$url = admin_url('post.php?post=' . $post_id . '&action=edit');
			wp_redirect($url);
			exit();
		}

	}

	function add_menu_pages() {
		$post_type = $this->ask('system/post_type');

		$parent_slug = 'edit.php?post_type=' . $post_type;
		MI()->tell('parent-menu-slug', $parent_slug);

		$page_title = __("Add new",'maxinbound');
		$sub_menu_title = __("Add new",'maxinbound');
		$capability = 'edit_pages';
		$menu_slug = 'maxinbound_new';
		$function = array($this, 'template_picker');
		add_submenu_page($parent_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $function);

		// check if there are any settings
		if ( $this->whistle->hasOffer('system/settings-page') )
		{
 			add_submenu_page($parent_slug, __( MI()->ask('system/nice_name') . ' : Settings','maxinbound'), __('Settings','maxinbound'),
 						'manage_options', 'maxinbound-settings', array($this, 'settings_page') );
 		}

 		remove_submenu_page($parent_slug, 'post-new.php?post_type='. $post_type);

	 	$this->collect('screens'); // please all your screens

	}

	/** Add the editor meta boxes. Asks the system via whistle to add meta boxes from modules and other sources */
	public function meta_boxes($post_type, $post)
	{
		$sys_type = MI()->ask('system/post_type');

		add_meta_box('mi-system', __("Template","maxinbound"), array($this, 'system_metabox'), $sys_type,
					 'side');

		$this->tell('template/post-id', $post->ID);
		$this->tell('editor/metaboxes');

	}

	public function system_metabox()
	{
		include($this->get_plugin_path() . 'admin/system_metabox.php');
	}

	/** Put page options in WP interfaces
	*
	* Currently not used .
	*/
	public function get_pages($pages)
	{
		$post_type = $this->ask('system/post_type');
		$landing_pages = new \WP_Query(array(
		'post_type' => $this->post_type,
		'post_status' => 'publish',
		'nopaging' => true,
		'order' => 'ASC',
		'orderby' => 'title'
	));

	// Probably this stuff should be moved to a module

	if (strpos($_SERVER['REQUEST_URI'], 'options-reading.php') !== false) {
		// If we're in the Settings > Reading section, check to see which
		// landing pages to include in the static front page dropdown list.
		foreach ($landing_pages->posts as $cpt) {
		//	if (get_post_meta($cpt->ID, 'maxinbound_frontpage_enabled', true) != 'no') {
				array_push($pages, $cpt);
		//	}
		}
	}
	else {
		// Otherwise, if we're anywhere else, check to see which
		// landing pages should be included in menus and navigation.
		foreach ($landing_pages->posts as $cpt) {
			//if (get_post_meta($cpt->ID, 'maxinbound_menu_enabled', true) != 'no') {
				array_push($pages, $cpt);
		//	}
		}
	}

		return $pages;
	}

	/*
		If a WM should be loaded and loads it to the hooks.
	*/
	public function check_load_page()
	{
		if (is_admin())
			return false; // don't check in the admin.

		// Load via ajax is caching is on; prevent hanging pages.
		if ( MI()->ask('system/front_caching') )
		{
			return false;
		}

		// check if we are loading the landing. Does cookie checks as well so before output.
		$load_template = $this->ask('template/check_load');

 		$this->log('Check Load',
 				array(
 					$this->ask('visitor/ip'),
 					$this->ask('visitor/hash'),
 					$this->ask('template/display/reason'),
 					$load_template,
		));


		if (! $load_template)
		{
			return false;
		}

		// add class to body for CSS control.
		add_filter('body_class', function ($classes)
		{
			$extra_classes = MI()->collect('template/body-class');  // is used for delays and others

			foreach($extra_classes as $class)
				$classes[] = $class;
			$classes[] = 'mi-template-body';
			return $classes;

		});

		ob_start();

		// get and save the output. The output will also set flags to load front scripts ( or not if template fails )
		$template = $this->get_plugin_path() . "includes/do_template.php";
 		include_once($template);
 		$output = ob_get_contents();
 		ob_end_clean();

		// load the actual output in the footer
		add_action('wp_footer', function() use ($output) {
			echo $output;
 		});
	}

	/*
		Ajax request to check a page load. Invoked when using blocking scripts like cache plugins.
	*/
	public function check_load_page_ajax($post)
	{
		$load_template = $this->ask('template/check_load');
		$status = false;

 		$this->log('Check Load',
 				array(
 					$this->ask('visitor/ip'),
 					$this->ask('visitor/hash'),
 					$this->ask('template/display/reason'),
 					$load_template,
		));


		if (! $load_template)
		{
			$page_load = array('status' => false);
		}
		else
		{
			$page_load = array(
							   'status' => true,
							   'template' => $load_template,
							   'body_class' =>  'mi-template-body',
							   'page_ouput' => '',
							  );

			ob_start();

			// get and save the output.
			$template = $this->get_plugin_path() . "includes/do_template.php";
	 		include_once($template);
	 		$output = ob_get_contents();
	 		ob_end_clean();


	 		$page_load['page_output'] = $output;
		}

 		echo json_encode($page_load);
 		exit();


	}

	public function settings_page()
	{
		include($this->get_plugin_path() . '/admin/settings.php');
	}


	function custom_columns($column, $post_id) {
		// The Title and Date columns are standard, so we don't have to explicitly provide output for them

		switch ($column) {
			case 'template':
				 $template_name = get_post_meta($post_id, '_maxinbound_template',true);
				 $template = MI()->templates()->findTemplate($template_name);

				if ($template)
					echo $template['nicename'];
				else
				{
					MI()->errors()->add( new \Exception ("Template $template_name not found!") );
					echo "<span class='error'>" . __('No Template Found!','maxinbound') . "</span>";
				}

			break;
			default:
				MI()->tell('landing-page-column-' . $column, $post_id);
			break;
		}
	}

	function edit_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Title','maxinbound') );

		$extra_columns = $this->collect('landing-page-columns');
		if (is_array($extra_columns))
		{
			foreach($extra_columns as $column)
				$columns = array_merge($columns, $column);

		}
		$columns['template'] = __('Template','maxinbound');
		$columns['date'] = __('Date','maxinbound');

		return $columns;
	}

	function force_send_on_media_item($args) {
		// This ensures we see the "Insert Into Post" button when the
		// media uploader is used. See the bottom of this forum thread:
		// http://wordpress.org/support/topic/insert-into-post-button-missing-for-some-picture

		$args['send'] = true;
		return $args;
	}

	public function plugin_action_links($links, $file) {
		return;
		/*$post_type = $this->ask('system/post_type');
		$this_path = self::get_plugin_path() . 'maxinbound.php';

		if ( strpos($this_path,$file) !== false) {
			$packs_link = '<a href="' . admin_url() . 'edit.php?post_type=' . $post_type . '">' . __("Landing Pages","maxinbound") . '</a>';
			array_unshift($links, $packs_link);
		}

		return $links;
		*/
	}


	// this will need to go to some admin script at some point.
	public function template_picker()
	{
		include(self::get_plugin_path()  . "admin/template_picker.php");
	}

	public function template_preview($post, $metabox)
	{
		$template = $metabox["args"]["template"];
		require_once( self::get_plugin_path() . 'admin/preview.php');
	}

	public function template_editor($post)
	{
		$post_type = $this->ask('system/post_type');
		if ($post->post_type !== $post_type)
			return;

		include(self::get_plugin_path()  . 'admin/editor.php');
	}


	public function add_input_template_preview() {
	   echo '<input type="hidden" name="debug_preview" value="template_preview">';
	}

	public function add_field_template_preview($fields){
	   $fields["debug_preview"] = "debug_preview";
	   return $fields;
	}


} // Class
