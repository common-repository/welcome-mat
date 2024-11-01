<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ .'\moduleAweber', 'init'));

class moduleAweber extends miModule
{
	static $name = 'aweber'; 
	protected $app_id; 

	protected $application_id = '818d593a';
	protected $auth_code = null; 
	
	protected $auth_url = 'https://auth.aweber.com/1.0/oauth/authorize_app/'; 
	
	protected $access_key; 
	protected $access_secret; 
	protected $access_token;

	protected $application = null;
	protected $account = null;
	
	
	
	public static function init($modules) 
	{
		$modules->register(self::$name, get_called_class() );
	}
	
	public function __construct() 
	{
		parent::__construct(); 
		$this->title = __('Aweber','maxinbound'); 
	
		
		MI()->listen('editor/save-options', array($this, 'save_options') ); 
		MI()->offer('editor/module-options', array($this, 'template_options') );
				
		MI()->offer('system/settings-page', array($this, 'settings_page') );
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		MI()->tell('settings/email/title', __('Email','maxinbound') );
		
		MI()->listen('system/ajax/post-form', array($this, 'check_email'));
		
		// register transient for management 
		$this->add_transient('aweber_lists');
		
		require_once('aweber_api/_aweber_api.php');
	}
	
	public function save_settings($post) 
	{
	
		$settings_stored = $this->get_settings(); 
		
		$auth_verified = isset($settings_stored['auth_verified']) ? $settings_stored['auth_verified'] : false;
		$prev_authcode = isset($settings_stored['auth_code']) ? $settings_stored['auth_code'] : false;
		
		$app_id = isset($post['aweber_app_id']) ? sanitize_text_field($post['aweber_app_id']) : false;
		$auth_code = isset($post['aweber_authcode']) ? sanitize_text_field($post['aweber_authcode']) : false; 
		
		if ($prev_authcode != $auth_code || $prev_authcode == false)
			$auth_verified = false; 
		
		if ($auth_code && ! $auth_verified) 
		{
			$this->generateKeys($auth_code); 
			
		}

	}
	
	public function settings_page() 
	{
		$settings = $this->get_settings(); 
		$auth_url = $this->getAuthURL();

		$verified = isset($settings['auth_verified']) ? $settings['auth_verified'] : false; 
 
 	
		$version = MI()->ask('system/version');
		$sysslug = MI()->ask('system/slug'); 
		 
		wp_enqueue_script('mi-module-aweber', plugin_dir_url(__FILE__) . 'js/aweber.js', array('jquery',$sysslug . '-admin-js'), $version, true); 
		wp_localize_script('mi-module-aweber', 'mi_aweber', array(
				'authorize_url' => $auth_url, 
				
		));

		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl'; 
		
		$tpl = new \stdClass;
		$tpl->title = __('Aweber','maxinbound'); 
		$tpl->name = 'aweber'; 

		if ($verified) 
		{
			$tpl->status = 'green'; 
			$tpl->status_message =  __('Aweber API verified. You can edit list settings in the Template settings', 'maxinbound') .  
			'<span><a class="do_update_authcode" >' . __('Change or Update your Authorization Code','maxinbound') . '</a></span>';		
		
		}
		

		ob_start(); 
		include('_aweber-settings.php');
		
		?>
		<style> 
			.aweber .status { margin-left: 0 !important; } 
			.do_update_authcode { color: #888; cursor: pointer; display: block; text-align: center; font-weight: 700; } 
		</style>
		<?php
		$tpl->content = ob_get_contents(); 
		
		ob_end_clean(); 
			
		$output = simpleTemplate::parse($template, $tpl);
		return array('page' => 'email', 
					 'content' => $output, 
					);
	}
	

	public function save_options($post)
	{
		
		$active = isset($post['aweber_active']) ? intval($post['aweber_active']) : 0; 
		$list_id = isset($post['aweber_list_id']) ? intval($post['aweber_list_id']) : 0;
		$status = isset($post['aweber_status']) ? sanitize_text_field($post['aweber_status']) : 0; 
		
		$options = array(
			'active' => $active, 
			'list_id' => $list_id, 
			'status' => $status
		);
		
		$this->update_options($options);
		
	}
	
	
	public function template_options() 
	{
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl'; 
			
		$options = $this->get_options(); 
		$settings = $this->get_settings(); 

		$active = isset($options['active']) ? $options['active'] : 0; 
		$list_id = isset($options['list_id']) ? $options['list_id'] : ''; 
		$verified = isset($settings['auth_verified']) ? $settings['auth_verified'] : false; 
		
		$current_status = 'inactive'; 
		if ($active == 1)
		{
			$current_status = 'active'; 
			if (! $list_id || $list_id == '')
				$current_status = 'missing_list'; 
			if (! $verified) 
				$current_status = 'missing_key'; 

		} 
		$show_status = true; 

		$lists = array(); 
		if ($active)
			$lists = $this->getLists(); 
			
			
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
				$reason = __('Everything is good to go','maxinbound'); 
			break;
			
		}
	
		
		$tpl = new \stdClass; 
		
		$tpl->title = __('Aweber', 'maxinbound'); 
		$tpl->name = 'aweber'; 
		$tpl->status = $code; 
		$tpl->status_message = $reason;
		
		ob_start(); 
	
		
		include('_aweber-template-options.php');
		$content = ob_get_contents(); 
		
		ob_end_clean(); 
		$tpl->content = $content; 
		
		$output = simpleTemplate::parse($template, $tpl) ;
		return $output;
		
	}
	
	public function check_email($post)
	{

		if (! isset($post["email"])) 
			return false;
			
		$name = isset($post["fname"]) ? $post["fname"] : ''; 
		$post_id = MI()->ask('system/ajax/post-id'); 
		$email = $post["email"]; 
		$hash = MI()->ask('visitor/hash'); // unique visitor id. 	
		$ip = MI()->ask('visitor/ip'); 

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
		
		$this->connectApi(); 
		$list_link = $this->account->data['lists_collection_link']; // api until /lists 

		$list_link .= '/' .$list_id; 
		
		$subscriber = array(
			'email' => $email, 
			'name' => $name, 
			'ip_address' => $ip); 
		
		try {	
			$the_list = $this->account->loadFromUrl($list_link);
			$newSubscriber = $the_list->subscribers->create($subscriber);
		}
		catch (\Exception $e) 
		{
			MI()->errors()->add($e); 
		}
	}
	
	protected function getLists() 
	{
		$prefix = MI()->ask('system/option-prefix'); 	
		$lists = get_transient($prefix . 'aweber_lists');
	
		if ($lists)
			return $lists;

		if (is_null($this->application) ) 
			$app = $this->connectAPI(); 
		if (is_null($this->account )) 
		{
			MI()->errors()->add( new \Exception ( __('Aweber API key not found or not valid. ','maxinbound' ) ) ); 
			$settings =  $this->get_settings(); 
			$settings['auth_verified'] = false;
			$this->update_settings($settings);
			return false;
			
		}
			
		$list_link = $this->account->data['lists_collection_link']; 
		
		$lists = $this->account->loadFromURL($list_link); 

		$list_array = array(); 
		while($lists->valid()) 
		{
			$list = $lists->current(); 
			$list_id = $list->data['id']; 
			$list_name = $list->data['name']; 
			
			$list_array[$list_id] = $list_name; 
			
			$lists->next(); 
		}	
	
		if (is_array($list_array)) 
		{
			set_transient($prefix . 'aweber_lists', $list_array); 
		}
	
		return $list_array; 
	}
	
	protected function generateKeys($authorization_code)
	{
		try {
		
		    $auth = \AWeberAPI::getDataFromAweberID($authorization_code);
		    list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = $auth;	
		    
		    $settings= $this->get_settings(); 
		    $settings['consumer_key'] = $consumerKey;
		    $settings['consumer_secret'] = $consumerSecret; 
		    $settings['access_key'] = $accessKey; 
		    $settings['access_secret'] = $accessSecret; 
		    $settings['auth_verified'] = true;
		    $this->update_settings($settings);
		    
			return true;    
		}
		catch ( \Exception $e) 
		{
			MI()->errors()->add(__('Aweber API failed to authenticate','maxinbound') ); 
//			error_log('Wmpro Aweber failed to authenticate'); 
		}
		return false;
		
	}

	protected function getAuthURL() 
	{
		$url = $this->auth_url . $this->application_id; 
		return $url;
	}
	
	/** Connect to the Aweber API with customer key and secret 
	
	*/ 
	protected function connectApi() 
	{
		$settings = $this->get_settings(); 
		if (! isset($settings['auth_verified']) || ! $settings['auth_verified'] ) 
			return false; 
			
		$consumer_key = isset($settings['consumer_key']) ? $settings['consumer_key'] : false; 
		$consumer_secret = isset($settings['consumer_secret']) ? $settings['consumer_secret'] : false; 
		$access_key = isset($settings['access_key']) ? $settings['access_key'] : false; 
		$access_secret = isset($settings['access_secret']) ? $settings['access_secret'] : false ; 
		
		if (! $consumer_key) 
			return false; // if this is not here, assume the thing was not filled out -> error
		
		try {
			$application = new \AWeberAPI($consumer_key, $consumer_secret);
			$this->application = $application; 
		
			$account = $application->getAccount($access_key, $access_secret); 
			$this->account = $account;
	
		}
		catch (\Exception $e) 
		{
			MI()->errors()->add($e); // needs handling
		}
		catch (\AWeberAPIException $e)
		{
			MI()->errors()->add($e);
		}		
		return false;
	}

	
	
} // class

