<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ .'\moduleRedirect', 'init'));

class moduleRedirect extends miModule
{
	static $name = 'basic_redirect';
	protected $redirect_url = '';

	public static function init($modules)
	{
		$modules->register(self::$name, get_called_class() );
	}

	public function __construct()
	{
		parent::__construct();

	 //	MI()->listen('template/process-end', array($this, 'redirect') );
	 	//MI()->offer('editor/module-options', array($this, 'options') );
		MI()->listen('editor/save-options', array($this, 'save_options') );
	 	MI()->listen('editor/metaboxes', array($this, 'box') );
		//MI()->tell('mi-core-options', array($this, 'options') );

		MI()->listen('template/scripts', array($this, 'redirect_template'));

	}

	public function redirect_template()
	{
		$options = $this->get_options();
		$sysslug = MI()->ask('system/slug');

		$delay = isset($options['redirect_delay']) ? intval($options['redirect_delay']) : false;
		$redirect = isset($options['redirect']) ? $options['redirect'] : false;

		if (! $redirect)
			return;

		$local_options = array();
		switch($redirect)
		{
			case "home";
				$local_options['redirect'] = get_home_url();
			break;
			case "posts" :
				$local_options['redirect'] = get_permalink( get_option( 'page_for_posts' ) );
			break;
			case "custom":
				$local_options['redirect'] = $options["custom_url"];
			break;

		}

		if ($delay)
			$local_options['delay'] = $delay;

		wp_localize_script($sysslug . '-front', $sysslug . '_redirect', $local_options);

	}


	public function redirect()
	{
		$options = $this->get_options();

		$redirect = $options["redirect"];

		switch($redirect)
		{
			case "home";
				$url = get_home_url();
			break;
			case "posts" :
				$url = get_permalink( get_option( 'page_for_posts' ) );
			break;
			case "custom":
				$url = $options["custom_url"];
			break;


		}
 		$this->redirect_url($url);

	}

	public function redirect_url($url)
	{
		wp_redirect($url);
		exit();
	}


	public function box()
	{
		$post_type = MI()->ask('system/post_type');

		// ID - TITLE - CALLBACK - SCREEN - CONTEXT - PRIORITY - ARGS
		add_meta_box('mod-redirect', __("After Signup Options","maxinbound"), array($this, 'options'), $post_type,
					 'side');

	}

	public function save_options($post)
	{
		$options = array();

		$options["redirect"] = isset($post["redirect"]) ? sanitize_text_field($post["redirect"]) : 'home';
		$options["custom_url"] = isset($post["custom_url"]) ? sanitize_text_field($post['custom_url']) : '';
		$options["redirect_delay"] = isset($post["redirect_delay"]) ? intval($post['redirect_delay']) : 2000;

		$this->update_options($options);

	}

	public function options ()
	{
		$options = $this->get_options();
		$redirect_value = isset($options["redirect"]) ? $options["redirect"] : '';
		$custom_url_value = isset($options["custom_url"]) ? $options["custom_url"] : '';
		$delay_value = isset($options['redirect_delay']) ? $options['redirect_delay'] : 0;
		if ($redirect_value == '')
			$redirect_value = 'none'; // default

		$metabox = MI()->editors()->getNew('metabox', 'metabox_stats');

		$redirect_none= MI()->editors()->getNewField('redirect', 'checkbox');
		$redirect_none->set('type', 'radio');
		$redirect_none->set('inputvalue', 'none');
		$redirect_none->set('label', __('No Redirect', 'maxinbound'));
	//	$redirect_none->set('id', 'redirect_none');
		$redirect_none->set('value', $redirect_value);

		$metabox->addField('redirect_none', $redirect_none);


		 $redirect_home = MI()->editors()->getNewField('redirect', 'checkbox');
		 $redirect_home->set('type', 'radio');
		 $redirect_home->set('inputvalue', 'home');
		 $redirect_home->set('label', __('To Homepage', 'maxinbound'));
	//	 $redirect_home->set('id', 'redirect_home');
		 $redirect_home->set('value', $redirect_value);

		 $metabox->addField('redirect_home', $redirect_home);

		 $redirect_posts = MI()->editors()->getNewField('redirect', 'checkbox');
		 $redirect_posts->set('type', 'radio');
		 $redirect_posts->set('inputvalue', 'posts');
		 $redirect_posts->set('label', __('To Posts Page', 'maxinbound'));
	//	 $redirect_posts->set('id', 'redirect_posts');
		 $redirect_posts->set('value', $redirect_value);

		 $metabox->addField('redirect_posts', $redirect_posts);

		 $redirect_custom = MI()->editors()->getNewField('redirect', 'checkbox');
		 $redirect_custom->set('inputvalue', 'custom');
		 $redirect_custom->set('type', 'radio');
		 $redirect_custom->set('label', __('To Custom URL','maxinbound'));
	//	 $redirect_custom->set('id', 'redirect_custom');
		 $redirect_custom->set('value', $redirect_value);

		 $metabox->addField('redirect_custom', $redirect_custom);

		 $custom_url = MI()->editors()->getNewField('custom_url', 'url');
		 $custom_url->set('placeholder', __('Custom URL', 'maxinbound'));
		 $custom_url->set('inputclass','full');
		 $custom_url->set('value', $custom_url_value);

		 $metabox->addField('custom_url', $custom_url);

		 $delay = MI()->editors()->getNewField('redirect_delay', 'text');
		 $delay->set('type', 'number');
		 $delay->set('inputclass', 'small');
		 $delay->set('title', __('Close delay in ms', 'maxinbound'));
		 $delay->set('value', $delay_value);

		 $metabox->addField('redirect_delay', $delay);

		 MI()->tell('editor/meta-box/redirect', $metabox);
 		$output = $metabox->admin();
 		echo $output;

	}


} //  class
