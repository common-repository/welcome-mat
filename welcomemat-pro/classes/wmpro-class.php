<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

class wmpro
{
	protected static $instance = null;
	protected static $license = null;

	public static function getInstance()
	{
		if (is_null(self::$instance))
			self::$instance = new wmpro();


		return self::$instance;
	}

	/** Constructor. Runs always, even without license. */
	public function __construct()
	{

		MI()->tell('system/support/link', 'https://welcomemat.io/account/?part=support');
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('maxinbound_register_screen', array($this, 'remove_pronag'), 99);

	}

	public function setLicense($license)
	{
		self::$license = $license;
	}

	public function getLicense()
	{
		if (! is_null(self::$license))
			return self::$license;
		else
			return false;
	}

	/** Constructor function when license is found valid */
	public function license_construct()
	{
		$this->hook_modules();
		$this->hook_templates();
		$this->hook_editors();

		add_action('maxinbound_register_module', array($this, 'register_pro_module') );
	}

	public function enqueue_scripts($hook)
	{
		if (! MI()->is_our_admin() )
		return;

		$plugin_url = self::get_plugin_url();

		$sysslug = MI()->ask('system/slug');
		$version = MI()->ask('system/version');

		wp_register_script($sysslug . '-wmpro-admin', $this->get_plugin_url() . 'js/wmpro-admin.js', array('jquery', $sysslug . '-admin-js'), $version, true );

		wp_enqueue_script($sysslug . '-wmpro-admin');
	}

	/** Hook the modules function in the correct offer. This is a PHP 5.3 fix ( instead of anon function ) */
	public function hook_modules()
	{
		if (self::$license->is_valid() )
		{
			MI()->offer('system/modules_paths', array($this, 'modules'));
		}
	}

	public function hook_editors()
	{
		if (self::$license->is_valid() )
		{
			MI()->offer('system/editors_paths', array($this, 'editors'));
		}

	}

	public function hook_templates()
	{
		if (self::$license->is_valid() )
		{
			MI()->offer('system/templates', array($this, 'load_templates'));
		}
	}

	public function load_templates()
	{
		$path = $this->get_plugin_path() . 'templates/';
		$url = $this->get_plugin_url() . 'templates/';
		return MI()->templates()->findTemplates($path, $url);
	}

	/** Offer an extra action for registering modules for pro functionality **/
	public function register_pro_module($modules)
	{

		do_action('maxinbound_register_pro_module', $modules);
	}

	public function modules()
	{
		$path = $this->get_plugin_path() . 'modules/';
		return $path;
	}

	/** Paths for PRO editors and fields. **/
	public function editors()
	{
		$path = array($this->get_plugin_path() . 'editors/',
					  $this->get_plugin_path() . 'fields/');
		return $path;
	}

	/** Returns the path of the plugin
	*
	*	@return string full plugin root path
	*/
	public function get_plugin_path()
	{
		return trailingslashit(plugin_dir_path(WMPRO_ROOT_FILE));
	}

	/** Returns the url of the plugin
	*
	*	@return string full url of plugin root
	*/
	public function get_plugin_url()
	{
		return trailingslashit(plugin_dir_url(WMPRO_ROOT_FILE));
	}

	public function remove_pronag($modules)
	{
 		$modules->unRegisterScreen('gopro');

	}


}  // class
