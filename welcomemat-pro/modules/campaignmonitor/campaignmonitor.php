<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ .'\moduleCampaignMonitor', 'init'));

class moduleCampaignMonitor extends miModule
{
	static $name = 'campaign_monitor'; 
	protected $api_key; 

	public static function init($modules) 
	{
		$modules->register(self::$name, get_called_class() );
	}
	
	public function __construct() 
	{
		parent::__construct(); 
		$this->title = __('Campaign Monitor', 'maxinbound'); 

		$settings = $this->get_settings(); 
		$this->api_key = isset($settings['api_key']) ? $settings['api_key'] : false;

		MI()->offer('system/settings-page', array($this, 'settings_page') );		
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		
		
		MI()->offer('editor/module-options', array($this, 'template_options') );
		MI()->listen('editor/save-options', array($this, 'save_options') ); 		

		MI()->listen('system/ajax/post-form', array($this, 'check_email'));
		
		$this->add_transient('cm_clients');
		$this->add_transient('cm_lists');
					
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
	
	public function add_email($email, $list_id, $name)
	{
		require_once('cm/csrest_subscribers.php'); 
		
		$wrap = new \CS_REST_Subscribers($list_id, $this->api_key);
		$result = $wrap->add(array(
			'EmailAddress' => $email,
			'Name' => $name,
			'Resubscribe' => true
		)); 
		
		
	}
		
	public function save_settings($post) 
	{
		$api_key = isset($post['cm_api_key']) ? sanitize_text_field($post['cm_api_key']) : false;
		$client_id = isset($post['cm_client_id']) ? sanitize_text_field($post['cm_client_id']) : false;
		$this->api_key = $api_key; 
		
		$settings = array(
					'api_key' => $api_key,
					'client_id' => $client_id,  
		);
		
		$this->update_settings($settings);
		
	}
	
	public function settings_page() 
	{
		$settings = $this->get_settings();

		$cm_api_key = isset($settings['api_key']) ? $settings['api_key'] : false;
		
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl'; 
		$tpl = new \stdClass;
		$tpl->title = __('Campaign Monitor','maxinbound'); 
		$tpl->name = 'campaign_monitor'; 
		$clients = $this->get_cm_clients(); 
		
		$current_status = ''; 
		$code = ''; 
		$reason = ''; 
		
		if ($cm_api_key == '') 
		{
			$current_status = 'not_active'; 
		}
		elseif ($clients === false || $clients == null) 
		{
			$current_status = 'wrong_key'; 
		}
		elseif (is_array($clients) && count($clients) == 0 )
		{
			$current_status = 'no_clients'; 
		}
		elseif (is_array($clients) && count($clients) > 0)
			$current_status = 'active'; 

		
		switch($current_status)
		{
			case 'wrong_key': 
				$code = 'red'; 
				$reason = __('API not set, or incorrect', 'maxinbound'); 
			break;
			case 'no_clients': 
				$code = 'orange'; 
				$reason = __('No Clients found, please check your campaign monitor account', 'maxinbound'); 
			break; 
			case 'active': 
				$code = 'green'; 
				$reason = __('Campaign Monitor API key verified','maxinbound'); 
			break;
			
		}


		ob_start(); 
		include('_cm-settings.php');

		$tpl->content = ob_get_contents(); 
		$tpl->status = $code; 
		$tpl->status_message = $reason;
		ob_end_clean(); 
			
		$output = simpleTemplate::parse($template, $tpl);		
		return array('page' => 'email', 
					 'content' => $output, 
					); 
	}
	
	public function template_options() 
	{
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl'; 
	
		$settings = $this->get_settings(); 
		$options = $this->get_options();

		$active = isset($options['active']) ? $options['active'] : 0; 
		$list_id = isset($options['list_id']) ? $options['list_id'] : ''; 

		$lists = $this->get_cm_lists(); 

				
		$current_status = 'inactive'; 
		if ($active == 1)
		{
			$current_status = 'active'; 
			if (! $list_id || $list_id == '')
				$current_status = 'missing_list'; 
			if (! $this->api_key) 
				$current_status = 'missing_key'; 

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
			case 'active': 
				$code = 'green'; 
				$reason = __('Active','maxinbound'); 
			break;
			
		}
				
		$client_id = isset($settings['client_id']) ? $settings['client_id'] : false;
		
		$tpl = new \stdClass; 
		
		$tpl->status = $code; 
		$tpl->status_message = $reason;

		$tpl->title = __('Campaign Monitor', 'maxinbound'); 
		$tpl->name = 'campaign_monitor'; 
		
		ob_start();
		
		include('_cm-template-options.php');
		
		$tpl->content = ob_get_contents(); 
		
		ob_end_clean();
		
		$output = simpleTemplate::parse($template, $tpl) ;
		return $output;
	}
	
	public function save_options($post) 
	{
		$options = $this->get_options(); 
		
		$active = isset($post['cm_active']) ? sanitize_text_field($post['cm_active']) : false;
		$list_id = isset($post['cm_list_id'])? sanitize_text_field($post['cm_list_id']) : false; 
		$status = isset($post['cm_status']) ? sanitize_text_field($post['cm_status']) : false; 
		
		$options = array(
				'active' => $active, 
				'list_id' => $list_id, 
				'status' => $status, 
			); 
			
		
		$this->update_options($options); 
		
	}
	
	public function get_cm_clients() 
	{
		require_once('cm/csrest_general.php'); 
		$prefix = MI()->ask('system/option-prefix'); 

		$clients = get_transient($prefix . 'cm_clients');
		if ($clients) 
			return $clients;
			
		if (! $this->api_key) 
			return false; 
		
		//$auth = array('api_key' => $this->api_key);
		$wrap = new \CS_REST_General($this->api_key);
		//$wrap = new CS_REST_Lists('List ID', $auth);
		try
		{
			$result = $wrap->get_clients();
		}
		catch (Exception $e) {
			$result = false; 
			MI()->errors()->add($e); 
		}
		
		if ($result && $result->was_successful() ) 
		{
			if (count($result->response) == 1) 
			{
				$client_id = $result->response[0]->ClientID; 
				$settings = $this->get_settings(); 
				$settings['client_id'] = $client_id; 
				$this->update_settings($settings);
			}
			
			$clients = array(); 
			foreach($result->response as $client) 
			{
				$clients[$client->ClientID] = $client->Name;
			}
			set_transient($prefix . 'cm_clients', $clients);

			return $clients;
		}	
		
		return null;
	}
	
	/** For some non-clear reason this is supposed to be per client */ 
	public function get_cm_lists() 
	{
		$prefix = MI()->ask('system/option-prefix'); 	
		$settings = $this->get_settings(); 
		$api_key = $this->api_key; 
		$client_id = isset($settings['client_id'])? $settings['client_id'] : false; 
 
		$lists = get_transient($prefix . 'cm_lists');
		if ($lists) 
			return $lists;
		
		if (! $api_key | ! $client_id ) 
			return false;
			
		require_once('cm/csrest_clients.php'); 
			
			
		$wrap = new \CS_REST_Clients($client_id, $api_key);
    
    	try {
    		$result = $wrap->get_lists() ; 
   	 	
		}
		catch (Exception $e) { 
			$result = false;
			MI()->errors->add($e);
		}
		
		if ($result && $result->was_successful() ) 
		{
			$response = $result->response; 
			$lists = array(); 
			foreach($response as $list)
			{
				$lists[$list->ListID] = $list->Name; 
			}
			set_transient($prefix . 'cm_lists', $lists);
			return $lists; 
			
		}
			
    
	}
	
	
	
}// class
