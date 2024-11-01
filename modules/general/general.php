<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ . '\moduleGeneral', 'init'));

/** Module for general options, and plugin maintenance options.  */
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\JsonFormatter;


class moduleGeneral extends miModule
{
	static $name = 'general_core';
	protected $logger;

	public static function init($modules)
	{
		$modules->register(self::$name, get_called_class() );
	}

	public function __construct()
	{
		parent::__construct();

		/* Sections for the Settings pages */
		MI()->offer('system/settings-page', array($this, 'settings_transients'), array() );
		MI()->offer('system/settings-page', array($this, 'support_page'), array()  );
		MI()->offer('system/settings-page', array($this, 'debug_page'), array() );

		MI()->offer('screens', array($this, 'attach'), 'gopro');

		/* Title for the Support tab */
		MI()->tell('settings/support/title', __('Support', 'maxinbound') );
		MI()->tell('settings/support/icon', 'phone');

		/* Title for the Settings tab */
		MI()->tell('settings/settings/title', __('Settings','maxinbound') );
		MI()->tell('settings/settings/icon', 'admin-settings');

		/* Save settings hook */
		MI()->listen('settings/save-settings', array($this, 'save_debug_settings') );

		/* Save Settings - ajax - This class handles the AJAX save for ALL modules globally. Needs only to be here */
		MI()->listen('system/ajax/save-settings', array($this, 'save_ajax_settings') );

		/* Template options */
		//MI()->offer('editor/module-options', array($this, 'template_options'), array(), 5 );
		//MI()->listen('editor/save-options', array($this, 'save_options') );

		/* Flush transients ajax action */
		MI()->listen('system/ajax/flushtransients', array($this, 'ajax_flush_transients') );

		/* The logging system. Check for logs, store logs. */
		MI()->listen('system/logger', array($this, 'getLogger'), 'ask');

		MI()->listen('setup/enqueue-scripts', array($this, 'script') );

		$post_type = MI()->ask('system/post_type');

		// Archive Post Status
		add_action('init', array($this, 'register_post_status')); // register post status
		add_filter('display_post_states', array($this, 'display_post_status') ); // display edit post screen
		add_filter('post_row_actions', array($this, 'post_row_actions'), 10, 2);  // actions in overview
		add_action('post_action_archive', array($this, 'post_action_archive'));   // what to do on action
		add_action('post_action_unarchive', array($this, 'post_action_archive'));

		add_filter('handle_bulk_actions-edit-' . $post_type, array($this, 'bulk_actions'),10,3);  // bulk edit handler
		add_filter('bulk_actions-edit-' . $post_type, array($this, 'register_bulk_actions') );  // register bulk editor
		add_action('admin_notices', array($this, 'bulk_messages'), 10);  // handle update messages

	}

	/** Ajax Save Settings function. Called to save all settings. */
	public function save_ajax_settings($post)
	{
		$post = stripslashes_deep( $post );
		$formpost = $post['formdata'];
		parse_str($formpost, $postdata);
		MI()->tell('settings/save-settings', $postdata);
		$collected = array_filter( MI()->collect('settings/status') );

		$response = array('reload' => true);

		// referer is hopefully always the source
		$url = $_SERVER['HTTP_REFERER'];

		$remove_array = array(); // remove any previous messages
		$modules = MI()->modules()->getModules();

		foreach($modules as $module)
		{
			$module_name = $module->getName();
			$remove_array[] = $module_name . '_status';
			$remove_array[] = $module_name . '_status_message';

		}
		$url = remove_query_arg($remove_array, $url);

		$messages = array();
		foreach($collected as $item)
		{

			if (is_array($item))
			{
				$messages = array_merge($messages, $item);
			}
		}

		$url = add_query_arg($messages, $url);
		$response['reload_url'] = $url;
		echo json_encode ( $response );
		exit();
	}

	/** Flush transients . At some point is should be possible with the param option to flush specific data ( per module button ) */
	public function ajax_flush_transients($stuff)
	{
		MI()->modules()->flush_transients();
		echo json_encode ( array(
				'status' => true,
				'dialog' => array('type' => 'ok',
								  'title' => __('Temporary data removed', 'maxinbound'),
								  'content' => __('<p>Data Removed. The window will now reload</p>','maxinbound'),
								  'action' => 'window.location.reload(true)',
								)
				), JSON_FORCE_OBJECT);

		exit();
	}


	public function settings_transients()
	{
		if (! $this->check_for_transients() )
			return false;

//		$settings = $this->get_settings();
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';

		$tpl = new \stdClass;
		$tpl->title = __('Flush temporary data','maxinbound');
		$tpl->name = 'flush_data';

		$do_remove = ( isset($settings['do_remove']) && $settings['do_remove'] === 1)  ? true : false;


		ob_start();
		include('_transient_settings.php');

		$tpl->content = ob_get_contents();

		ob_end_clean();

		$output = simpleTemplate::parse($template, $tpl);
		return array('page' => 'settings',
					 'priority' => 5,
					 'content' => $output,
		);

	}

	public function support_page ()
	{
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';

		$tpl = new \stdClass;
		$tpl->title = __('Support','maxinbound');
		$tpl->name = 'support';


		ob_start();
		$support_data = $this->collect_support_data();

		$view = 'support';
		include('_support_settings.php');
		$support_content = ob_get_contents();
		ob_end_clean();
		ob_start();

		$view = 'systeminfo';
		include('_support_settings.php');
		$sysinfo_content = ob_get_contents();
		ob_end_clean();

		$tpl->content = $support_content;
		$output = simpleTemplate::parse($template, $tpl);
		$tpl->title = __('System Information', 'maxinbound');
		$tpl->content = $sysinfo_content;
		$output .= simpleTemplate::parse($template, $tpl);

//		$output = simpleTemplate::parse($template, $tpl);
		return array('page' => 'support',
					 'priority' => 100,
					 'content' => $output,
		);
	}

	public function debug_page()
	{
		$settings = $this->get_settings();
		$log_active = isset($settings['log_active']) ? $settings['log_active'] : false;

		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';

		if ($log_active)
		{
			$upload = wp_upload_dir();
			$log_location = trailingslashit($upload['basedir']) . 'welcomemat.log';
			if (file_exists($log_location) )
			{
				$log_file = \file_get_contents ( $log_location );
				$log_array = explode(PHP_EOL, $log_file);
				$log_array = array_reverse($log_array);
			}
		}

		$tpl = new \stdClass;
		$tpl->title = __('Debug','maxinbound');
		$tpl->name = 'debug';

		ob_start();
		include('_settings_debug.php');
		$tpl->content = ob_get_contents();
		ob_end_clean();

		$output = simpleTemplate::parse($template, $tpl);
		return array('page' => 'advanced',
					 'content' => $output,
		);

	}

	public function initLog()
	{
		$settings = $this->get_settings();

		$log_active = (isset($settings['log_active']) && $settings['log_active'] == 1) ? true : false;

		if ($log_active)
			$log_level = LOGGER::DEBUG;
		else
			$log_level = LOGGER::EMERGENCY;


			$upload = wp_upload_dir() ;
			$this->logger = new \Monolog\Logger('whistle');
			$this->logger->pushHandler(new StreamHandler( trailingslashit($upload['basedir']) . 'welcomemat.log'), $log_level);

			$this->add_transient('debug_cleanup');
			$prefix = MI()->ask('system/option-prefix');
			$clean = get_transient($prefix . 'debug_cleanup');

			if (! $clean )
			{
				//$filemode = 'w';
				set_transient($prefix . 'debug_cleanup', true, HOUR_IN_SECONDS );
			}

			MI()->tell('system/whistle/log_active', true);

	}

	public function getLogger()
	{
		if ( is_null($this->logger))
			$this->initLog();

		return $this->logger;
	}



	public function save_debug_settings($post)
	{
		$settings = $this->get_settings();

		$log_active = isset($post['log_active']) ? intval($post['log_active']) : false;

		$settings['log_active'] = $log_active;

		$this->update_settings($settings);
	}

	protected function collect_support_data()
	{
		global $wpdb;

		$theme = wp_get_theme();

		$active_plugins = get_option('active_plugins', array());
		$installed_plugins = get_plugins();

		$plugins = array();

		foreach($installed_plugins as $plugin_path => $plugin)
		{
			if (in_array($plugin_path, $active_plugins))
				$plugins[] = $plugin['Name'] . ' ' . $plugin['Version'];
		}

		if (function_exists('curl_init') )
		{
			$curl = __('Yes','maxinbound');
			$curl_code = false;
		}
		else
		{
			$curl = __('No', 'maxinbound');
			$curl_code = 'red';
		}

		if (extension_loaded('simplexml'))
		{
			$simplexml = __('Yes', 'maxinbound');
			$simplexml_code = false;
		}
		else
		{
		 	$simplexml = __('No', 'maxinbound');
			$simplexml_code = 'red';
		}

		$memory_limit = ini_get('memory_limit');

		if (intval($memory_limit) < 64)
			$memory_limit_code = 'red';
		elseif(intval($memory_limit) < 90)
			$memory_limit_code = 'orange';
		else
			$memory_limit_code = false;



		$data =
		array(
		'wordpress_version' =>
			array(
				'title' => __("WordPress Version",'maxinbound'),
				'data' => get_bloginfo('version'),
			),
		'user_agent' =>
			array(
				'title' => __('User Agent','maxinbound'),
				'data' => $_SERVER['HTTP_USER_AGENT'],
			),
		'web_server' => array(
				'title' => __('Web Server', 'maxinbound'),
				'data' => $_SERVER['SERVER_SOFTWARE'],
			),
		'php_version' => array(
				'title' => __('PHP Version','maxinbound'),
				'data' => PHP_VERSION,
			),
		'mysql_version' => array(
				'title' => __('MySQL Version','maxinbound'),
				'data' =>  $wpdb->db_version(),
			),
		'plugin_version' => array(
				'title' => __('Plugin Version', 'maxinbound'),
				'data' => MI()->ask('system/version'),
			),
		'wordpress_url' => array(
				'title' => __('Wordpress URL','maxinbound'),
				'data' => get_bloginfo('url'),
				),
		'curl' => array(
				'title' => __('CURL Support','maxinbound'),
				'data' => $curl,
				'status' => $curl_code,
				),
		 'simplexml' => array(
		 		'title' => __('SimpleXML Support', 'maxinbound'),
		 		'data' => $simplexml,
		 		'status' => $simplexml_code,
		 		),
		 'memory_limit' => array(
		 		'title' => __('Memory Limit','maxinbound'),
		 		'data' => $memory_limit ,
		 		'status' =>  $memory_limit_code,
		 	),
		 'theme' => array(
		 		'title' => __('Theme','maxinbound'),
		 		'data' => $theme->get('Name') . ' ' . $theme->get('Version'),
		 	),

		 );

		$data['active_plugins'] = array('title' => __('Active Plugins','maxinbound'),
										'data' => $plugins);

		return $data;



	}

	/* Check if any modules has a setting that uses Transients ( Module Class - register transient function ) */
	public function check_for_transients()
	{
		$modules = MI()->modules->getModules();
		foreach($modules as $module)
		{
			$trans = $module->get_transients();
			if (is_array($trans) && count($trans) > 0)
				return true;
		}

		return false;
	}

	public function script()
	{
		$sysslug = MI()->ask('system/slug');
		$version = MI()->ask('system/version');
		wp_register_script('mi-module-general', plugin_dir_url(__FILE__) . 'js/general.js', array('jquery',$sysslug . '-admin-js'), $version, true); 
		wp_localize_script('mi-module-general', 'mimodulegeneral', array(
				'post_type' => MI()->ask("system/post_type"),
				'labels' => array('minor_button' => __('Save as Archived','maxinbound'),
					//		      'publish_button' => __('Archive', 'maxinbound'),
							      'status_label' => __('Archived', 'maxinbound'),
								   'status_label_dropdown' => __('Archive','maxinbound'),
								  ),

		) );

		wp_enqueue_script('mi-module-general');
	}

	/** Register custom post status for Archived templates */
	public function register_post_status()
	{
		register_post_status( 'archive', array(
			'label'                     => _x( 'Archived', 'template' ),

			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' ),
		) );

	}

	public function display_post_status($statuses)
	{
		global $post;
		if( get_query_var( 'post_status' ) != 'archive' ){
			if( $post->post_status == 'archive' ){
				return array(__('Archived','maxinbound') );
			}
		}
		return $statuses;

	}

	public function post_row_actions($actions, $post)
	{
		$post_type = MI()->ask('system/post_type');
		if ($post_type !== $post->post_type)
			return $actions; // not ours

		$new_actions = array();
		foreach ($actions as $key => $action)
		{
			if ($key === 'view')
			{
				if (isset($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'archive')
				{
					$title = __('Unarchive','maxinbound');
					$the_action = 'unarchive';
				}
				else
				{
					$title = __('Archive','maxinbound');
					$the_action = 'archive';
				}

				$url = wp_nonce_url(sprintf('post.php?post_type=%s&action=%s&post=%d',$post_type,$the_action, $post->ID), $the_action);
				$a_action = '<a href=' . $url . '>' . $title . '</a>';
				$new_actions['archive'] = $a_action;
			}

			$new_actions[$key] = $action;
		}

		return $new_actions;
	}

	public function post_action_archive ($post_id)
	{
		global $post;
		global $action;
		global $sendback;

		$post_type = MI()->ask('system/post_type');
		if ($post->post_type !== $post_type)
			return $post_id; // not ours

		if (isset($action) && $action == 'archive')
		{
				add_post_meta($post_id,'_wp_archive_meta_status', $post->post_status);
				$post->post_status = 'archive';
				wp_update_post($post);
				wp_redirect( add_query_arg('archived', 1, $sendback) );
				exit();
		}
		if (isset($action) && $action == 'unarchive')
		{
				$post_status = get_post_meta($post_id, '_wp_archive_meta_status', true);

				if (! $post_status || $post_status == '')
					$post_status = 'draft';

				$post->post_status = $post_status;

				wp_update_post($post);
				delete_post_meta($post_id, '_wp_archive_meta_status');
				wp_redirect( add_query_arg('unarchived', 1, $sendback) );
				exit();
		}

	}

	public function bulk_actions($sendback, $doaction, $post_ids)
	{
		if ($doaction == 'archive' || $doaction = 'unarchive' )
		{
			$post_type = MI()->ask('system/post_type');

			foreach($post_ids as $post_id)
			{
				$post = get_post($post_id);
				if ($post->post_type == $post_type)
				{
					if ($doaction == 'archive')
					{
						add_post_meta($post_id,'_wp_archive_meta_status', $post->post_status);
						$post->post_status = 'archive';
						wp_update_post($post);
					}
					if ($doaction == 'unarchive')
					{
						$post_status = get_post_meta($post_id, '_wp_archive_meta_status', true);

						if (! $post_status || $post_status == '')
							$post_status = 'draft';

						$post->post_status = $post_status;
						wp_update_post($post);
						delete_post_meta($post_id, '_wp_archive_meta_status');
					}
				}
			}

			$sendback = remove_query_arg( array('archived', 'unarchived'), $sendback );
			if ($doaction = 'archive')
				$sendback = add_query_arg('archived', count($post_ids), $sendback);
			if ($doaction = 'unarchive')
				$sendback = add_query_arg('unarchived', count($post_ids), $sendback);

			wp_redirect( $sendback );
			exit();
		}

	}

	/** This uses admin_notice since WP doesn't offer any native entry point here */
	public function bulk_messages()
	{
		if ( isset($_REQUEST['archived']))
		{
			$count = isset($_REQUEST['archived']) ? intval($_REQUEST['archived']) : 0;
			$message = sprintf( _n('%s template archived', '%s templates archived ', $count, 'maxinbound'), $count );

			echo  '<div id="message" class="updated fade"><p>' .
      				$message . '</p></div>';
		}
		if ( isset($_REQUEST['unarchived']))
		{
			$count = isset($_REQUEST['unarchived']) ? intval($_REQUEST['unarchived']) : 0;
			$message = sprintf( _n('%s template restored', '%s templates restored ', $count, 'maxinbound'), $count );

			echo  '<div id="message" class="updated fade"><p>' .
      				$message . '</p></div>';
		}
	}

	public function register_bulk_actions($bulk_actions)
	{
		$new_bulk = array();
		foreach($bulk_actions as $name => $action)
		{
			if ($name == 'trash' || $name == 'untrash')
			{
				if (isset($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'archive')
					$new_bulk['unarchive'] = __('Unarchive', 'maxinbound');
				else
					$new_bulk['archive'] = __("Archive",'maxinbound');
			}
			$new_bulk[$name] = $action;
		}
		return $new_bulk;
	}

	public function save_options($post)
	{
		$options = $this->get_options();

		$options['thank_text'] = isset($post['thank_text']) ? sanitize_text_field( $post['thank_text'] ) : '';

		$this->update_options($options);
	}

	public function template_options()
	{
		$options = $this->get_options();

		$thank_text = isset($options['thank_text']) ? $options['thank_text'] : __('Thank you!', 'maxinbound') ;


		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';

		$tpl = new \stdClass;
		$tpl->title = __('Template Options','maxinbound');
		// .option is usually set by editor for label layout
		$tpl->name = ' thank_text option ';

		$content = '<label>' . __('After signup display message','maxinbound')  . '</label>';

		$thank = MI()->editors()->getNewField('thank_text', 'text');
		$thank->set('id', 'thank_text');
//		$thank->set('name','thank_text');
//		$thank->set('title', __('After signup display message','maxinbound') );
		$thank->set('value', $thank_text);
	//	$thank->setTemplate('switch.tpl', 'core');

		$content .= $thank->admin();

		$tpl->content = $content;

		$output = simpleTemplate::parse($template, $tpl) ;
		return $output;



	}


}
