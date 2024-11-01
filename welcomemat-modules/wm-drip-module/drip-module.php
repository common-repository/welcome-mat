<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class dripModule extends miModule
{
	static $name = 'drip'; 

	protected $verified = false; 
	protected $access_token = '';
	protected $api_key ; 
	protected $account_id; 
	 

	public static function init($modules) 
	{
		$modules->register(self::$name, get_called_class() );
	}
	
	public function __construct() 
	{
		$settings = $this->get_settings();
		$this->title = __('Drip', 'wmdrip'); 
		
		$this->api_key = (isset($settings['api_key'])) ? $settings['api_key'] : false;
		$this->verified = (isset($settings['verified'])) ? $settings['verified'] : false; 
		$this->account_id = (isset($settings['account_id'])) ? $settings['account_id'] : false;
		  
		$this->add_transient('drip_accounts');
	
		MI()->offer('system/settings-page', array($this, 'settings_page') );
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		MI()->listen('system/ajax/post-form', array($this, 'check_email'));
		MI()->listen('system/ajax/verify_drip', array($this, 'verify_api') );	
	}
	
	
	public function save_settings($post) 
	{
		$old_api_key = $this->api_key; 
		
		$api_key = isset($post['drip_api_key']) ? $post['drip_api_key'] : false; 
		$account_id = isset($post['drip_account_id']) ? $post['drip_account_id'] : false; 
	
		if ($old_api_key != $api_key) 
			$verified = $this->verify_api($api_key);
		else
			$verified = $this->verified; 
	
		$settings = array(
					'api_key' => $api_key,
					'account_id' => $account_id,  
					'verified' => $verified,
		);
		
		$this->update_settings($settings);
	
	}
	
	public function settings_page()
	{
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl'; 
		
		$tpl = new \stdClass;
		$tpl->title = __('Drip','wmpro'); 
		$tpl->name = 'drip'; 
 
		// settings 		
		ob_start(); 

		if (! $this->verified) 
		{
			$status = 'red'; 
			$reason = __('Drip API key not verified / incorrect' , 'wmpro'); 
			$accounts = false;
		}
		elseif ($this->verified)
		{
			$status = 'green'; 
			$reason = __('Drip verified', 'wmpro'); 	
			$accounts = $this->get_drip_accounts(); 
			$tpl->help = __('Drip module will sent subscribed email addresses as events to specified Drip Account. Use Drip automation to assign them to your lists', 'wmdrip');
		} 

		include('_drip-settings.php');

			
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
	
	public function get_drip_accounts() 
	{
		$api = $this->getApi(); 
		

		$prefix = MI()->ask('system/option-prefix'); 

		$accounts = get_transient($prefix . 'drip_account');
		
		if ($accounts) // check cache
			return $accounts; 

		$accounts = $api->list_accounts(); 
			
		if (count($accounts) == 0) 
			return false; 
		
		if ( isset($accounts['accounts'])) 
			$accounts = $accounts['accounts']; 
		
		if ($accounts && count($accounts) > 0) 	
			set_transient($prefix . 'drip_account', $accounts);
	
		return $accounts; 
	
	}
	
	public function verify_api($data)
	{
		if (isset($data['param'])) 
		{	
			$api_key = sanitize_text_field($data['param']);
			
			$api = $this->getApi($api_key); 
			try { 
				$api_verified = $api->validate_token($api_key); 
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
					'partial_target' => '.section.drip', 
					'partial_data' => $this->settings_page(),
				));
			
	
		}
		exit();		
	
	}
	
	public function getApi($api_key = false)
	{
		if (! $api_key) 
			$api_key= $this->api_key; 
		 
		 $logger = MI()->ask('system/logger');
		 
		 $log_class = new \stdClass;  // workaround for library needs a class with log attribute (?) 
		 $log_class->log = $logger; 
				 
		 $api = new GetDrip_WP_API( array('api_token' => $api_key, 'logger' => $log_class) );
		 return $api; 		
	}
		
	/** Is passing email address from post form feature **/
	public function check_email($post) 
	{

		$email = isset($post['email']) ? $post['email'] : false; 
		if (! $email) 
			return; 

		$logger = MI()->ask('system/logger'); 

		$is_preview = isset($post['is_preview']) ? $post['is_preview']: false;

		if ($is_preview) 
			return false;
							
		$props = new \stdClass;
		
		$name = isset($post["fname"]) ? $post["fname"] : false; 
		$post_id = MI()->ask('system/ajax/post-id'); 
		$template_title = get_the_title($post_id); 
	
		$page_id = MI()->ask('page/post-id');
		$post_title = ($page_id) ? get_the_title($page_id) : false; 
	
		$props->template = $template_title; 
		$props->source = 'Welcome Mat'; 
		$props->ip  = MI()->ask('visitor/ip'); 
		
		if ($post_title) 
			$props->page = $post_title; 
					
		$action = __('Subscribed via Welcome Mat', 'wmdrip'); 
		
		$api = $this->getApi(); 
		
		$account_id = $this->account_id; 
		
		$options = array('events' => array( 
					array(
					'email' => $email, 
					'action' => $action, 
					'properties' => $props, 
					) 
		) ); 
		
		$logger->info('Drip options', $options);
	
		$response = $api->record_event($account_id,  $options); 
	
	

		$logger->info('Drip Event Sent', array($response) );
			
	}

	
}





dripModule::init($modules); 


