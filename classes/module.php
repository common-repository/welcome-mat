<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class miModule
{
	protected $modules = array();
	protected $post_id = null;
	protected $title = '';
	protected $option_prefix = '';
	protected $transients = array();

	protected $status = false;
	protected $status_message = false;


	public static function init($modules)
	{
		$modules->register(static::$name, get_called_class() );

	}

	public function __construct()
	{
		MI()->ask('template/post-id', array($this, 'set_postid'));
		$this->option_prefix = MI()->ask('system/option-prefix');

		// check for custom database tables
		if (isset(static::$table_name) && static::$table_name != '')
			MI()->offer('database/check', array($this, 'check_database'));

		MI()->offer('settings/status', array($this, 'get_status'));

		MI()->listen('system/ajax/save-options-' . static::$name, array($this, 'ajax_save_options') );
	}

	public function getTitle()
	{
		if (isset($this->title))
			return $this->title;
		else
			return ucfirst(static::$name);
	}

	public function getName()
	{
		return static::$name;
	}

	/** For template options, save options via ajax (and do partial refresh ) when key setting changes */
	public function ajax_save_options()
	{

		$post = isset($_POST['post']) ? $_POST['post'] : false;

		$post_array = array();
		parse_str($post, $post_array);
		ksort($post_array);
		$post_array = array_filter($post_array);

		$post_id = isset($post_array['post_ID']) ? intval($post_array['post_ID']) : null;

		$this->set_postid($post_id);
		MI()->tell('template/post-id', $post_id);

		$this->save_options($post_array);

		$page = $this->template_options();

		echo json_encode ( array(
			'partial_target' => '.section.' . static::$name,
			'partial_refresh' => true,
			'partial_data' => array('content' => $page),  // JS Script like it like this
		));
		exit();
	}

	/** Save module options of a template
	*	@param Array $options Save this options to the current module data.
	*/
	public function update_options($options)
	{
		if (! $this->post_id)
			return false;

		$option_name = $this->option_prefix . static::$name;

		if (count ($options) == 0)
		{
			// remove if empty.
			delete_post_meta($this->post_id, $option_name);
			return;
		}
		update_post_meta($this->post_id, $option_name, $options);
	}


	/** Get module options from a template
	*	@param int $post_id Post ID of the template, if not set will take the current post id ( within the loop
	**/
	public function get_options($post_id = null)
	{

		if (is_null($this->post_id) && is_null($post_id) )
			return false; // no post id set
		elseif (! is_null($this->post_id) && is_null($post_id) )
			$post_id = $this->post_id;

		$option_name = $this->option_prefix . static::$name;

		$options = get_post_meta($post_id, $option_name, true);
		return $options;
	}

	/** Update Module settings from the settings section */
	public function update_settings($settings)
	{

		$setting_name = $this->option_prefix . static::$name;
		$settings = array_filter($settings);
		if (count ($settings) == 0)
		{
			// remove if empty.
			delete_option($setting_name);
			return;
		}
		update_option($setting_name, $settings);
	}

	/** Return the status + message if any is set for passing on */
	public function get_status()
	{
		if ($this->status)
		{
			return array(static::$name . '_status' => $this->status,
						 static::$name . '_status_message' => $this->status_message,
						);
		}
	}

	/** Check if there is a status to set from the Request params **/
	public function set_status()
	{
		if (isset($_REQUEST[static::$name . '_status']))
		{
			$status = sanitize_text_field($_REQUEST[static::$name . '_status']);
			$message = isset($_REQUEST[static::$name . '_status_message']) ? sanitize_text_field($_REQUEST[static::$name . '_status_message']) : false;
			$this->status = $status;
			$this->status_message = $message;
		}
	}

	/** Get global settings ( wp_options ) for a module */
	public function get_settings()
	{
		$settings_name = $this->option_prefix . static::$name;
		$settings = get_option($settings_name);

		return $settings;
	}

	/** Sets the post id currently in use ( preview or live ) */
	public function set_postid($post_id)
	{
		$this->post_id = $post_id;
	}

	/** Attach an interface ( screen ) to this module */
	public function attach($screen_name)
	{
		MI()->modules()->attachScreen($this, $screen_name);

	}

	/** Get all the Welcome Mats as WP Posts */
	public function get_all_posts($args = array() )
	{
		$defaults = array(
			'post_status' => 'any',
		);
		$args = wp_parse_args($args, $defaults);

		$post_type = MI()->ask('system/post_type');

		$args = array(
			'posts_per_page' => -1,
			'post_type' => $post_type,
			'post_status' => $args['post_status'],
		);

		$posts = get_posts($args);

		return $posts;
	}

	public function add_transient($name)
	{
		$this->transients[] = $this->option_prefix . $name;
	}

	public function get_transients()
	{
		return $this->transients;
	}

	public function flush_transients()
	{
		foreach($this->transients as $name)
		{
			delete_transient($name);
			MI()->log("Removed Transient $name upon request");
		}
	}


	public function getView($args = array() )
	{
		return new \stdClass;
	}

	/** Check if Database table exists */
	public function check_database()
	{
		if (! isset(static::$table_name) || static::$table_name == '')
			return;  // nothing

		$sql = 'SHOW TABLES LIKE \'' . static::$table_name . '\'';
		$result = MI()->db($sql, 'get_var');

		if ($result !== static::$table_name)
			return false;
		else
			return true;
	}



}
