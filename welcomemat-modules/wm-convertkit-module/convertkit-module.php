<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');


class convertkitModule extends miModule
{
	static $name = 'convertkit';

	protected $verified = false;
	protected $access_token = '';
	protected $api_key ;


	public static function init($modules)
	{
		$modules->register(self::$name, get_called_class() );
	}

	public function __construct()
	{
		parent::__construct();
		$settings = $this->get_settings();
		$this->title = __('Convertkit', 'wmdrip');

		$this->api_key = (isset($settings['api_key'])) ? $settings['api_key'] : false;
		$this->verified = (isset($settings['verified'])) ? $settings['verified'] : false;


		$this->add_transient('convertkit_forms');

		MI()->offer('system/settings-page', array($this, 'settings_page') );
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		MI()->listen('system/ajax/post-form', array($this, 'check_email'));
		MI()->listen('system/ajax/verify_convertkit', array($this, 'verify_api') );

		MI()->listen('editor/save-options', array($this, 'save_options') );
		MI()->offer('editor/module-options', array($this, 'template_options') );
	}


	public function save_settings($post)
	{
		$old_api_key = $this->api_key;

		$api_key = isset($post['convertkit_api_key']) ? $post['convertkit_api_key'] : false;

		if ($old_api_key != $api_key)
		{
			$data = array('param' => $api_key);
			$verified = $this->verify_api($data);
		}
		else
			$verified = $this->verified;

		$settings = array(
					'api_key' => $api_key,
					'verified' => $verified,
		);

		$this->update_settings($settings);

	}

	public function settings_page()
	{
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';

		$tpl = new \stdClass;
		$tpl->title = __('Convertkit','wmpro');
		$tpl->name = 'convertkit';

		// settings
		//$account_id = $this->account_id;
/*
		if ($this->api_key)
			$lists = $this->get_chimp_lists();
		else
			$lists = array();
*/

		ob_start();

		if (! $this->verified)
		{
			$status = 'red';
			$reason = __('Convertkit API key not verified / incorrect' , 'wmpro');
		}
		elseif ($this->verified)
		{
			$status = 'green';
			$reason = __('Convertkit API key verified', 'wmpro');
			$tpl->help = __('To activate for your Welcome Mat, active in template options and pick a form', 'mbconvert');
		}

		include('_convertkit-settings.php');


		if (isset($status))
		{
			$tpl->status = $status;
			$tpl->status_message = $reason;
		}


		$tpl->content = ob_get_contents();

		ob_end_clean();

		$output = simpleTemplate::parse($template, $tpl);
		return array('page' => 'email',
					 'priority' => 50,
					 'content' => $output,
					);
	}

	public function get_convertkit_forms()
	{

		$api = $this->getApi();

		$prefix = MI()->ask('system/option-prefix');

		$forms = get_transient($prefix . 'convertkit_forms');

		if ($forms) // check cache
			return $forms;

		$forms = $api->get_all();

		if (count($forms) == 0)
			return false;


		if ($forms && count($forms) > 0)
			set_transient($prefix . 'convertkit_forms', $forms);

		return $forms;

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
		$form_id = isset($options['form_id']) ? $options['form_id'] : '';

		$api = $this->getApi();

		$forms = $this->get_convertkit_forms();


		$current_status = 'inactive';
		if ($active == 1)
		{
			$current_status = 'active';
			if (! $form_id || $form_id == '')
				$current_status = 'missing_form';
			if (! $this->api_key)
				$current_status = 'missing_key';
			if (! $this->verified)
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
			case 'missing_form':
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

		$tpl->title = __('Convertkit','wmconvert');
		$tpl->name = 'convertkit';

		ob_start();

		include('_convertkit-template-options.php');

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

		$active = isset($post['convertkit_active']) ? intval($post['convertkit_active']) : 0;
		$form_id = isset($post['convertkit_form_id'])  ? sanitize_text_field($post['convertkit_form_id']) : '';
		$status = isset($post['convertkit_status']) ? sanitize_text_field($post['convertkit_status']) : false;
		$options = array('active' => $active,
						 'form_id' => $form_id,
						 'status' => $status,
					);


		$this->update_options($options);
	}

/*	public function save_settings($post)
	{
		$settings = $this->get_settings();

		$api_key = isset($post['drip_api_key']) ? $post['drip_api_key'] : false;

		$settings['api_key'] = $api_key;

		$old_api_key = $this->api_key;
		if ($api_key !== $old_api_key || ! $this->verified)
		{
			if ($api_key)
			{
				$verified = true;
				try {
					$api = $this->get_api($api_key);
					$api_verified = $api->validate_token($api_key);  // double but hey
				}
				catch (\Exception $e)
				{
					MI()->errors()->add($e);
					$api_verified = false;
				}
			}
			$this->flush_transients(); // reset cache
			$settings['verified'] = $verified;

			$this->api_key = $api_key;
			$this->update_settings($settings);
		}

	} */

	public function verify_api($data)
	{
		if (isset($data['param']))
		{
			$api_key = sanitize_text_field($data['param']);
			$api = $this->getApi($api_key);

			try {
				$this->api_key = $api_key;
				$forms = $this->get_convertkit_forms();

				if ($forms)
					$api_verified = true;
				else
					$api_verified = false;
			}
			catch (\Exception $e)
			{
				MI()->errors()->add($e);
				$api_verified = false;
			}

			$settings['api_key'] = $api_key;
			$settings['verified'] = $api_verified;

			$this->update_settings($settings);

			$this->api_key = $api_key;
			$this->verified = $api_verified;

				echo json_encode ( array (
					'partial_refresh' => true,
					'partial_target' => '.section.convertkit',
					'partial_data' => $this->settings_page(),
				));


		}
		exit();

	}

	public function getApi($api_key = false)
	{
		if (! $api_key)
			$api_key= $this->api_key;

		 //$logger = MI()->ask('system/logger');

		 $api = new convertkit\forms($api_key);
		 return $api;
	}

	/** Is passing email address from post form feature **/
	public function check_email($post)
	{
		$email = isset($post['email']) ? $post['email'] : false;
		$logger = MI()->ask('system/logger');

		if (! $email)
		{
			$logger->error('Convertkit module - no email supplied', $post);
			return;
		}


		$name = isset($post["fname"]) ? $post["fname"] : false;
		$post_id = MI()->ask('system/ajax/post-id');

		$options = $this->get_options($post_id);

	 	if (! isset($options['active']) || $options['active'] == 0)
	 	{
			$logger->debug('Convertkit module - Option not active',  array($options));
			return false;
		}

		if (! isset($options['form_id']))
		{
			$logger->error('Convertkit module - Active but no form selected', array($options) );
	 		return false;
	 	}

		$is_preview = isset($post['is_preview']) ? $post['is_preview']: false;

		if ($is_preview)
			return false;

		$form_id = $options['form_id'];


		$props = array();
		$props['email'] = $email;
		if ($name)
			$props['first_name'] = $name;

		/*$props->template = $template_title;
		$props->source = 'Welcome Mat';
		$props->ip  = MI()->ask('visitor/ip');
		*/

		//if ($post_title)
		//	$props->page = $post_title;

		$api = $this->getApi();

		/*$options = array('events' => array(
					array(
					'email' => $email,
					'action' => $action,
					'properties' => $props,
					)
		) );
		*/
		$logger->info('Converkit options', array($form_id, $props) );

		$response = $api->add($form_id,  $props);

		$logger->info('Convertkit Form Email Sent', array($response) );

	}

}

convertKitModule::init($modules);
