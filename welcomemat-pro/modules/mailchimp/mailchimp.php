<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ .'\moduleMailchimp', 'init'));

class moduleMailchimp extends miModule
{
	static $name = 'mailchimp';
	protected $api_key;
	protected $api_verified;

	public static function init($modules)
	{
		$modules->register(self::$name, get_called_class() );
	}

	public function __construct()
	{
		parent::__construct();
		$this->title = __('Mailchimp', 'maxinbound');

		$settings = $this->get_settings();
		$this->api_key = isset($settings['api_key']) ? $settings['api_key'] : false;
		$this->api_verified = isset($settings['api_verified']) ? $settings['api_verified'] : false;

		MI()->listen('editor/save-options', array($this, 'save_options') );

		MI()->offer('system/settings-page', array($this, 'settings_page') );
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		MI()->tell('settings/email/title', __('Email','maxinbound') );
		MI()->tell('settings/email/icon', 'email');

		MI()->offer('editor/module-options', array($this, 'template_options') );
		MI()->listen('system/ajax/post-form', array($this, 'check_email'));

		$this->add_transient('mailchimp_lists');

		require_once('_mailchimp_api.php');
	}

	public function settings_page()
	{
		//$settings = $this->get_settings();

		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';

		$tpl = new \stdClass;
		$tpl->title = __('Mailchimp','wmpro');
		$tpl->name = 'mailchimp';

		// settings
		$mailchimp_api_key = $this->api_key;

		if ($this->api_key)
			$lists = $this->get_chimp_lists();
		else
			$lists = array();


		ob_start();
		include('_mailchimp-settings.php');

		if (! $this->api_verified)
		{
			$status = 'red';
			$reason = __('API Key not verified / incorrect' , 'wmpro');

		}
		elseif ($this->api_verified)
		{
			$status = 'green';
			$reason = __('Mailchimp API Key verified', 'wmpro');
		}

		if (isset($status))
		{
			$tpl->status = $status;
			$tpl->status_message = $reason;
		}

		$tpl->content = ob_get_contents();

		ob_end_clean();

		$output = simpleTemplate::parse($template, $tpl);
		return array('page' => 'email',
					 'priority' => 30,
					 'content' => $output,
					);
	}

	public function save_settings($post)
	{
		$settings = $this->get_settings();

		$api_key = isset($post['mailchimp_api_key']) ? $post['mailchimp_api_key'] : false;
		$api_verified = false; // default.

		$settings['api_key'] = $api_key;


		$old_api_key = $this->api_key;
		if ($api_key !== $old_api_key || ! $this->api_verified)
		{
			if ($api_key)
			{
				$api_verified = true;
				try {
					$api = new miMailChimp($api_key);
				}
				catch (\Exception $e)
				{
					MI()->errors()->add($e);
					$api_verified = false;
				}
			}
			$this->flush_transients(); // reset cache
			$settings['api_verified'] = $api_verified;

			$this->api_key = $api_key;
			$this->update_settings($settings);
		}

	}

	/** Load template options
	*	Template options are used on a per post basis
	*/
	public function template_options()
	{
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';

		$settings = $this->get_settings();
		$options = $this->get_options();


		$active = isset($options['active']) ? $options['active'] : 0;
		$list_id = isset($options['list_id']) ? $options['list_id'] : '';

		$list = $this->get_chimp_lists();


		$current_status = 'inactive';
		if ($active == 1)
		{
			$current_status = 'active';
			if (! $list_id || $list_id == '')
				$current_status = 'missing_list';
			if (! $this->api_key)
				$current_status = 'missing_key';
			if (! $this->api_verified)
				$current_status = 'api_error';
		}
		$show_status = true;
		switch($current_status)
		{
			case 'inactive':
				$code = 'orange';
				$reason = __('Not Active','maxinbound');
				$show_status = false;
			break;
			case 'missing_list':
				$code = 'red';
				$reason = __('Please select a list','maxinbound');
			break;
			case 'missing_key':
				$code = 'red';
				$reason = __('API not set, or incorrect', 'maxinbound');
			break;
			case 'api_error' :
				$code = 'red';
				$reason = __('API is not set or error in API Key, please check settings', 'maxinbound');
			break;
			case 'active':
				$code = 'green';
				$reason = __('Active','maxinbound');
			break;

		}


		$tpl = new \stdClass;

		$tpl->title = __('Mailchimp','maxinbound');
		$tpl->name = 'mailchimp';

		ob_start();

		include('_mailchimp-template-options.php');

		$content = ob_get_contents();
		ob_end_clean();

		$tpl->content = $content;
		$tpl->status = $code;
		$tpl->status_message = $reason;

		$output = simpleTemplate::parse($template, $tpl) ;
		return $output;

	}

	public function save_options($post)
	{

		$active = isset($post['mailchimp_active']) ? intval($post['mailchimp_active']) : 0;
		$list_id = isset($post['mailchimp_list_id'])  ? sanitize_text_field($post['mailchimp_list_id']) : '';
		$status = isset($post['mailchimp_status']) ? sanitize_text_field($post['mailchimp_status']) : false;
		$options = array('active' => $active,
						 'list_id' => $list_id,
						 'status' => $status,
					);


		$this->update_options($options);
	}


	public function get_chimp_lists()
	{
		// list the list from mailchimp
		$prefix = MI()->ask('system/option-prefix');
		$lists = get_transient($prefix . 'mailchimp_lists');
		if ($lists )
			return $lists;

		if (! $this->api_key)
			return array();

		$args = array(
			'fields' => 'lists.id,lists.name,lists.stats.member_count',
		);

		try {
			$chimp_api = new miMailChimp($this->api_key);
			$lists = $chimp_api->get('lists', $args);
		}
		catch (\Exception $e)
		{
			MI()->errors()->add($e);
		}



		if (is_array($lists))
		{
			set_transient($prefix . 'mailchimp_lists', $lists);
		}

		return $lists;
	}

	public function check_email($post)
	{

		if (! isset($post["email"]))
			return false;

		$name = isset($post["fname"]) ? $post["fname"] : '';
		$post_id = MI()->ask('system/ajax/post-id');
		$email = $post["email"];
		$hash = MI()->ask('visitor/hash'); // unique visitor id.

		$options = $this->get_options($post_id);

		$is_preview = isset($post['is_preview']) ? $post['is_preview']: false;

		if ($is_preview)
			return false;

		// get list id
		if (! isset($options['list_id']))
	 		return false;

	 	if (! isset($options['active']) || $options['active'] == 0)
	 		return false;

		$list_id = $options['list_id'];
		$this->add_email($email, $list_id, $name);
	}

	/** Add email address to mailchimp
	* @param string $email Email address
	* @param string $list_id The Mailchimp ID of the list
	*/
	public function add_email($email, $list_id, $name = '')
	{
	 	if (! $this->api_key)
	 		return false;

		$chimp_api = new miMailChimp($this->api_key);
		$chimp_args = array(
				        'email_address' => $email,
				        'status'        => 'subscribed',
				    );
		$result = $chimp_api->post("lists/$list_id/members",$chimp_args);
		return $result;
	}

} // class
