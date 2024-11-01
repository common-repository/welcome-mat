<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_screen', array(__NAMESPACE__ . '\miGoProScreen', 'init'));

class miGoProScreen 
{

	protected static $screen_id = 'gopro';
	
	public static function init($module) 
	{
		$obj = new self;
		$module->registerScreen(static::$screen_id, __("Get Pro","maxinbound"), array($obj, 'show') );
		
		MI()->listen('setup/enqueue-scripts', array($obj, 'styles') );
				

	}

	public function styles() 
	{
		if (! MI()->modules()->is_screen_active(self::$screen_id))
			return false; 
			
		$plugin_url = MI()->get_plugin_url();
	
		wp_enqueue_style('mi_gopro', $plugin_url . 'modules/general/css/gopro.css'); 	
	}	
	

	public function show() 
	{
		$view = MI()->modules()->getAttachedScreen(static::$screen_id)->getView(); 
	
		$args = array( 
				"title" => __("Go PRO","maxinbound")
		);
		MI()->modules()->header($args);
		
		$plugin_url = MI()->get_plugin_url();
		
		?>
		<div class='gopro-wrap'> 			
	


	<div class='content block'>

		<div class='image'> <img src='<?php echo $plugin_url ?>/images/gopro/graph_graphic.png' /></div>
		
				
		<h4 class='item'>Convert your visitors with proven high conversion welcome mats</h4>

		<p class='item'>Customize proven high-converting page templates that focus your visitors attention around you call-to action. Whether you’re building an email list or promoting your incredible product, Welcomat.io’s intuitive interface and powerful customization will help you capture your visitors attention.</p>
	</div>
			
			
	<div class='content block'>
		<div class='image'> <img src='<?php echo $plugin_url ?>/images/gopro/integration_graphic.png' /></div>
		
<p class='item'>Our Welcome Mat integrates with popular email marketing tools to seamlessly load captured emails into <strong>Mailchimp</strong>, <strong>Aweber</strong> or <strong>Campaign Monitor</strong>. Simply connect your email marketing account and we’ll make sure those valuable sign-up and subscription email addresses get added to your list.</p>

<h4 class='item'>Easily export captured emails</h4>


	
	</div>		
	
	<div class='content block'> 
		<h3>19+ Conversion Proven Templates</h3>
		<div class='image'> <img src="<?php echo $plugin_url ?>/images/gopro/template_thumbs.png" /></div>

	</div>
	
	<div class='content block center'> 		
<a class="maxbutton-start-converting" href="https://welcomemat.io/checkout/?edd_action=add_to_cart&amp;download_id=194&amp;edd_options[subscription_option]=single&amp;edd_options[price_id]=1"><span class="mb-text">Start Converting</span></a>
	
		<p>Or <a href="https://welcomemat.io" target="_blank">check our website</a></p>
	</div>
	
</div>	
		<?php	
		

		MI()->modules()->footer(); 
	}





} // gopro class
