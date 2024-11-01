<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');


class wmpro_license extends miModule
{
	static $name = 'license';

	protected $product_id = 'WelcomeMat PRO';
	protected $license_key = '';
	protected $license_active = false;
	protected $license_checked = false;
	protected $license_expires = false;
	protected $api_url = 'https://welcomemat.io';


	public function __construct()
	{
		parent::__construct();

		$this->load_license();

		if (! $this->is_valid() )
		{
			// add notices.
			add_action('admin_notices', array($this, 'admin_notice') );
			add_action( 'in_plugin_update_message-' . plugin_basename(WMPRO_ROOT_FILE), array($this, 'license_update_nag'), 10,2 );

		}

//		MI()->offer('system/settings-page', array($this, 'settings_page') );
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		MI()->tell('settings/settings/title', __('Settings','maxinbound') );
		MI()->tell('settings/settings/icon', 'admin-settings');
		MI()->tell('system/pro_version', WMPRO_VERSION_NUM);

	//	MI()->listen('system/ajax/deactivate_license', array($this, 'ajax_deactivate_license') );
	//MI()->listen('system/ajax/activate_license', array($this, 'ajax_activate_license') );

	//	$this->add_transient('license_lastcheck');

	}

	public function is_valid()
	{
		return true; // $this->license_active;
	}

	public function load_license()
	{
		$settings = $this->get_settings();

		$this->license_key = isset($settings['license_key']) ? $settings['license_key'] : false;
		$this->license_active = isset($settings['license_active']) ? $settings['license_active'] : false;
		//$this->license_checked = isset($settings['license_checked']) ? $settings['license_checked'] : false;
		$this->license_expires = isset($settings['license_expires']) ? $settings['license_expires'] : false;

		if ( $this->license_active == false && $this->status_message == false )
		{

			$this->status = 'red';
			$this->status_message = __('License is not active. Please enter a valid license and press active license button.', 'wmpro');

			if ($this->license_expires && $this->license_expires > 0)
			{
				$exp = new \DateTime($this->license_expires);
				$now = new \DateTime();

				$expire_date = $exp->format(get_option( 'date_format' ));

				$this->status_message .=  sprintf(__(' Your license expired at %s ','wmpro'), $expire_date);
			}
		}
	}

	public function update_check()
	{
		$version = MI()->ask('system/pro_version');


		$license_key = false;

		if ($this->is_valid())
			$license_key = $this->license_key;

		$edd_updater = new EDD_SL_Plugin_Updater( $this->api_url, WMPRO_ROOT_FILE, array(
		'version' 	=> $version, 		// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name'     => $this->product_id, 	// name of this plugin
		'author' 	=> 'Max Foundry',  // author of this plugin
		'url'           => home_url(),
		) );
	}

	public function check_module_update($args)
	{
		$default_args = array(
				'root_file' => '',
				'version' => '1.0',
				'product_name' => '',
		);

		$args = wp_parse_args($args, $default_args);

		if ($this->is_valid())
			$license_key = $this->license_key;
		else
			return false;


		$edd_updater = new EDD_SL_Plugin_Updater( $this->api_url, $args['root_file'], array(
		'version' 	=> $args['version'], 		// current version number
		'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
		'item_name'     => $args['product_name'], 	// name of this plugin
		'author' 	=> 'Max Foundry',  // author of this plugin
		'url'           => home_url(),
		) );


	}


	public function license_update_nag($file, $plugin )
	{

		echo " <span style='color: #ff0000;' class='error'>" . __('Missing license','wmpro') . '</span>';

	}

	public function admin_notice()
	{
		$class = 'notice notice-error';
		$message = __( 'Welcome Mat PRO doesn\'t have a valid license. Without license the plugin will not work!', 'wmpro' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}


	public function save_settings($post)
	{
		$settings = $this->get_settings();

		$old_key = isset($settings['license_key']) ? $settings['license_key'] : false;
		$key = isset($post['license_key']) ? sanitize_text_field($post['license_key']) : $this->license_key;

		$settings = array(
			'license_key' => $key,
			'license_active' => $this->license_active,
			'license_expires' => $this->license_expires,
		);

		$this->update_settings($settings);
		$this->license_key = $key;

	}

	public function set_status()
	{
		return; // no request params needed
	}

	public function settings_page()
	{
		$this->load_license();
		$this->set_status();

		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl';
		$tpl = new \stdClass;
		$tpl->title = 'License';
		$tpl->name = 'license';


		if ($this->status)
		{

			$tpl->status_message = $this->status_message;
			$tpl->status = $this->status;
		}
		else
		{
			if ($this->is_valid())
			{
				if ($this->license_expires == 'lifetime')
				{
					$tpl->status = 'green';
					$tpl->status_message = __('License is active','maxinbound');

				}
				else
				{
					$exp = new \DateTime($this->license_expires);
					$now = new \DateTime();
					$days_left = $exp->diff($now);
					$days_left = $days_left->format('%a');
					$expire_date = $exp->format(get_option( 'date_format' ));

					$tpl->status = 'green';
					$tpl->status_message = sprintf(__('License is active. Expire date: %s ( %s days left )','maxinbound'),
											 $expire_date, $days_left);
				}
			}
		}

		$disabled = ''; // disable license key after activation
		if (isset($this->license_key) && $this->license_key !== '' && $this->is_valid() )
			$disabled = " disabled ";


		ob_start();

		include('_license-settings.php');

		$tpl->content = ob_get_contents();

		ob_end_clean();

		$output = simpleTemplate::parse($template, $tpl);
		return array('page' => 'settings',
					 'content' => $output
					);
	}

	public function ajax_deactivate_license($data)
	{

		if (isset($data['param']))
		{
			$key = sanitize_text_field($data['param']);
			$result = $this->edd_action($key , 'deactivate_license');

			if ($result['status'] == 'success')
			{

				// status/ message
				echo json_encode ( array(
					'partial_target' => '.section.license',
					'reload' => true,
					'reload_url' => 'self',
		//			'partial_data' => $this->settings_page(),
				));
			}
			else
			{
				echo json_encode ( array (
					'partial_refresh' => true,
					'partial_target' => '.section.license',
					'partial_data' => $this->settings_page(),
				));

			}
		}
		exit();
	}

	public function ajax_activate_license($data)
	{

		if (isset($data['param']))
		{
			$key = sanitize_text_field($data['param']);
			$result = $this->edd_action($key , 'activate_license');

			$this->license_key = $key;

			if ($result['status'] == 'success')
			{
				// status/ message
				echo json_encode ( array(
					'partial_target' => '.section.license',
					'reload' => true,
					'reload_url' => 'self',
		//			'partial_data' => $this->settings_page(),
				));
			}
			else
			{
				echo json_encode ( array (
					'partial_refresh' => true,
					'partial_target' => '.section.license',
					'partial_data' => $this->settings_page(),
				));

			}

		}
		exit();
	}

	/** Send activate or deactive action to EDD server **/
	protected function edd_action($license_key, $action)
	{

		$args = array(
				"edd_action" => $action,
				"item_name" => urlencode($this->product_id),
				"license" => $license_key,
				"url" => home_url(),
		);

		$api_url = add_query_arg($args, $this->api_url);
		$result = wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $args ) );

		if ( is_wp_error( $result ) || 200 !== wp_remote_retrieve_response_code( $result ) )
		{
			$error = $result->get_error_message();
			$message =  ( is_wp_error( $result ) && ! empty( $error ) ) ? $error : __( 'An connection error occurred, please try again.', 'wmpro');
			$result = array('status' => 'error',
							'error' => $message
							);
			$this->status = 'red';
			$this->status_message = $message;
			MI()->errors()->add( new \Exception('HTTP Error in retrieving remote license : ' . $message ) );
			return $result;
		}

		$data = json_decode( wp_remote_retrieve_body( $result ) );

		if (isset($data->license) && $data->license == 'valid')
		{
			$result  = array("status" => 'success'); // clean output
			$this->license_active = true;
			$this->license_expires = $data->expires;
			$this->update_license_checked_time();

			$this->status = false;
			$this->status_message = false;

			$settings = array(
				'license_key' => $license_key,
				'license_active' => $this->license_active,
				'license_expires' => $this->license_expires,
			);
			set_site_transient( 'update_plugins', null );

		$this->update_settings($settings);

			return $result;
		}
		elseif (isset($data->license) && $data->license == 'deactivated')
		{
			$result = array('status' => 'success');
			$result['message'] = __('Your license has been deactived', 'wmpro');
			$this->license_active = false;
			$this->license_expires = 0;
			$this->update_license_checked_time();

			$settings = array( // not optimal
				'license_key' => $license_key,
				'license_active' => $this->license_active,
				'license_expires' => $this->license_expires,
			);

			$this->update_settings($settings);
			set_site_transient( 'update_plugins', null );

			return $result;
		}
		else
		{
			$this->license_active = false;
			$this->license_expires = 0;

			$new_result = array(); // clean output;

			$new_result["status"] = "error";
			$new_result["error"] = (isset($data->error)) ? $data->error : '';

			switch( $data->error ) {
					case 'expired' :
						$message = sprintf(
							__( 'Your license key expired on %s.', 'maxbuttons-pro' ),
							date_i18n( get_option( 'date_format' ), strtotime( $data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'revoked' :
						$message = __( 'Your license key has been disabled.','wmpro');
						break;
					case 'missing' :
						$message = __( 'Invalid license key', 'wmpro');
						break;
					case 'invalid' :
					case 'site_inactive' :
						$message = __( 'Your license is not active for this URL.', 'wmpro' );
						break;
					case 'item_name_mismatch':
						$message = __('The item name didn\'t match the server item. Please contact support', 'wmpro');
					break;
					case 'license_not_activable':
						$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'wmpro' ), 'Welcome Mat PRO' );
					break;
					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.','wmpro' );
						break;
					default :

						$message = __( 'An error occurred, please try again.' . $data->error, 'wmpro' );
						break;
			}

			$settings = array( // not optimal
				'license_key' => $license_key,
				'license_active' => $this->license_active,
				'license_expires' => $this->license_expires,
			);
			$this->update_settings($settings);


			// internal status update
			$this->status = 'red';
			$this->status_message = $message;

			$new_result["message"] = $message ;
			$result = $new_result;
			return $result;
		}

	}

	/** Check if the license is valid. Don't check more than given amount of days. */
	public function check_license()
	{
		if (! $this->license_active)
			return false; // not activated,  no further checks needed

		$prefix = MI()->ask('system/option-prefix');
		$license_lastcheck = get_transient($prefix . 'license_lastcheck');

		if ($license_lastcheck === false)
		{
			//$remote_result = $this->get_remote_license();
			return $this->license_active; // disable remote checking.
		}
		else
			return $this->license_active; // if transient exists, return status quo

		$this->load_license(); // reinit;

		$reason = (isset($remote_result)) ? $remote_result : '';

		if($this->license_active)
		{
			return true;
		}
		else
			return false;

	}

	public function update_license_checked_time($seconds = false)
	{
		 if (! $seconds)
		 {
		 	$seconds = WEEK_IN_SECONDS;
		 }

  		 $prefix = MI()->ask('system/option-prefix');
		 set_transient($prefix . 'license_lastcheck',true, $seconds );
	}

	public function get_remote_license()
	{
		$args = array(
				"edd_action" => "check_license",
				"license" => $this->license_key,
				"item_name" => $this->product_id,
				"url" => home_url(),
		);

		$request = wp_remote_post($this->api_url,  array( 'body' => $args, 'timeout' => 15, 'sslverify' => false ) );

		if(is_wp_error($request))
		{
			// failed - defer check three hours - prevent check license flood
			$this->update_license_checked_time( (3*HOUR_IN_SECONDS) );
			MI()->errors()->add( new \Exception("WMPRO - License server failed to respond"));
			return "Request failed";
		}

		$data = json_decode( wp_remote_retrieve_body( $request ) );

 		$settings = $this->get_settings();

		if (isset($data->license) && $data->license == 'valid')
		{
			$this->update_license_checked_time();
			$expires = $data->expires;
			$settings['license_expires'] = $expires;
			$this->update_settings  ( $settings );
			return true;
		}
		else
		{
			//update_option('maxbuttons_pro_license_activated', false, true);
			$settings['license_active'] = false;
			if (isset($data->expires))
				$settings['license_expires'] = $data->expires;

			$this->update_settings( $settings );
			$this->update_license_checked_time();
			return false;
		}
	}
}
