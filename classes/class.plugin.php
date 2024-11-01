<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

/** Class to define plugin environment variables. System specific data can be requested via whistle. **/
class maxPlugin
{
	protected static $instance;

	public static function init()
	{
		self::$instance = new maxPlugin();
	}

	public static function getInstance()
	{
		return self::$instance;
	}

	public function __construct()
	{
		MI()->tell('system/post_type', 'mf_mat');
		MI()->tell('system/slug', 'mf_welcomemat'); // system slug is for everything uniquely named in plugin
		MI()->tell('system/post_slug', 'mat');
		MI()->tell('system/option-prefix', 'wm_module_'); // prefix in wp_option / wp_postmeta for module options / settings
		MI()->tell('system/version', WM_VERSION_NUM);
		MI()->tell('system/post_type_labels', $this->labels() );
		MI()->tell('system/ajax_action', 'mib_ajax');
		MI()->tell('system/support/link', 'https://wordpress.org/support/plugin/welcome-mat');

		$wm_debug = false;
		if (defined('WM_DEBUG'))
			$wm_debug = WM_DEBUG;

		MI()->tell('system/debug', $wm_debug);

		MI()->offer('system/template_types', array($this, 'template_types') );

		//$this->template_types(); // controls the template types the system has.
		$this->titles(); // various titles

		// load various post type actions
		add_action('init', array($this, 'register_post_type'));
		add_action('post_row_actions', array($this, 'post_row_actions'), 10,2);
		add_filter('post_updated_messages', array($this, 'post_updated_messages') );

	}

	protected function labels()
	{
		$labels = array(
			'name' => __('Welcome Mat',"maxinbound"),
			'singular_name' => __('Welcome Mat',"maxinbound"),
			'add_new' => __('Add New Mat','maxinbound'),
			'add_new_item' => __('Add New Mat','maxinbound'),
			'edit_item' => __('Edit Mat','maxinbound'),
			'new_item' => __('New Mat','maxinbound'),
			'view_item' => __('View Mat','maxinbound'),
			'search_items' => __('Search Mats','maxinbound'),
			'not_found' => __('No Mats found','maxinbound'),
			'not_found_in_trash' => __('No Mats found in trash','maxinbound'),
			'parent_item_colon' => ''
		);
		MI()->tell('system/nice_name', $labels['name']) ;
		return $labels;
	}



	protected function titles()
	{
		MI()->tell('system/plugin_title', __('Welcome Mat', 'maxinbound') );

	}

	public function template_types()
	{
		return  array(
				'page' => __('Welcome Mats', 'maxinbound'),
			//	'popup' => __('Popup', 'maxinbound'),
				);


	}

	/** Register the Plugins Post Type */
	public function register_post_type() {

		$labels = MI()->ask('system/post_type_labels');
		$slug = MI()->ask('system/post_slug');

		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menu' => false,
			'exclude_from_search' => true,
			'query_var' => true,
			'menu_icon' => MI()->get_plugin_url() . '/images/menu_icon.png',
			'menu_position' => 20,
			'rewrite' => false,
			'has_archive' => false,
			'capability_type' => 'page',
			'hierarchical' => false,
			'show_in_rest' => false,
			'supports' => array('title'),
		);

		$post_type = MI()->ask('system/post_type');
		$args = apply_filters($slug . '_register_post_type', $args);
		register_post_type($post_type, $args);
	}

	/** Edit the actions on the plugins overview screen */
	public function post_row_actions($actions, $post)
	{
		$post_type = MI()->ask('system/post_type');
		if ($post_type !== $post->post_type)
			return $actions;

		$post_id = $post->ID;
		$title = $post->post_title;
		$preview_link = esc_url( get_preview_post_link( $post ) );
		$preview_link .= '&preview_id=' . $post_id;

		$preview = sprintf(
                    '<a href="%s" rel="permalink" aria-label="%s">%s</a>',
                    esc_url( $preview_link ),
                    esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ),
                    __( 'Preview' )
            );

		$actions['view'] = $preview;
		return $actions;
	}

	/** Update the update messages to fix the Preview URL */
	public function post_updated_messages($messages)
	{
		$post_type = MI()->ask('system/post_type');

		$messages[$post_type][1] = __('Template updated', 'maxinbound');
		$messages[$post_type][4] = __('Template updated', 'maxinbound');

		return $messages;
	}


} // class
