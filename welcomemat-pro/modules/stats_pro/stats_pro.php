<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ . '\moduleStatsPRO', 'init'));
remove_action('maxinbound_register_module', array(__NAMESPACE__ . '\moduleStats', 'init'));
//remove_action('maxinbound_register_screen', array(__NAMESPACE__ . '\miStatsScreen', 'init'));

class moduleStatsPRO extends moduleStats
{
	static $name = 'stats_pro'; 
	
	public function __construct() 
	{	

		parent::__construct(); 
		$this->title = __('Statistics PRO', 'maxinbound'); 

	//	MI()->offer('screens', array($this, 'attach'), 'basic_statistics');
		
		MI()->offer('system/settings-page', array($this, 'settings_page') );
		MI()->listen('settings/save-settings', array($this, 'save_settings') );
		MI()->tell('settings/statistics/title', __('Statistics','maxinbound') );
		MI()->tell('settings/statistics/icon', 'chart-line' ); 
		
		// GA Actions
		MI()->listen('template/load', array($this, 'load_template') ); 
	//	MI()->listen('setup/enqueue-scripts', array($this, 'styles') );
	}

	public function settings_page() 
	{
		$settings = $this->get_settings(); 
		$template = MI()->get_plugin_path() . 'admin/setting_template.tpl'; 
			
		$tpl = new \stdClass;
		$tpl->title = __('Google Analytics','maxinbound'); 
		$tpl->name = 'stats'; 
		
		$ga_code = isset($settings['ga_code']) ? $settings['ga_code'] : false;
		$ga_active = isset($settings['ga_active']) ? $settings['ga_active'] : false;
		$ga_loadcode = isset($settings['ga_loadcode']) ? $settings['ga_loadcode'] : false;
		
		ob_start(); 
		include('_statistics-settings.php');

		$tpl->content = ob_get_contents(); 
		
		ob_end_clean(); 
			
		$output = simpleTemplate::parse($template, $tpl);		
		return array('page' => 'statistics', 
					 'priority' => 20, 
					 'content' => $output
					);
	}
	
	public function save_settings($post)
	{
		$ga_code = isset($post['ga_code']) ? sanitize_text_field($post['ga_code']) : false;
		$ga_active = isset($post['ga_active']) ? intval($post['ga_active']) : false;
		$ga_loadcode = isset($post['ga_loadcode']) ? intval($post['ga_loadcode']) : false;
		
		$settings = array(
				'ga_code' => $ga_code,
				'ga_active' => $ga_active, 	
				'ga_loadcode' => $ga_loadcode, 
		); 
		
		$this->update_settings($settings);
	}
	
	
	public function getFilterOptions($row = 'primary', $filter_data = array() ) 
	{
		$options = parent::getFilterOptions($row, $filter_data);
		if ($row == 'primary') 
		{
			
		}
		if ($row == 'secondary') 
		{
			$options[] = $this->get_time_options($filter_data);
				
		}
		return $options;
	}
	
	/** Set Filters coming from the Ajax interface to the query */ 
	public function setFilters($filters)
	{
		parent::setFilters($filters); 
		
		if (isset($filters['period_filter'])) 
		{
			$period = $filters['period_filter']; 
			$start_date = new \DateTime('midnight'); 
			
			switch($period) 
			{
				case 'week': 
					$start_date->sub(new \DateInterval('P7D') );				 
					$this->set_period($start_date, 0, 'T1H');
					
				break; 
				case 'month': 
					$start_date->sub(new \DateInterval('P1M') );  
					$this->set_period($start_date);
				break;
				case 'year': 
					$start_date->sub(new \DateInterval('P1Y') );  
					$this->set_period($start_date, 0, '1M');
					
				break;		
			}
		}
	}
	
	public function get_time_options($filter_data) 
	{
		$period = isset($filter_data['period_filter']) ? sanitize_text_field($filter_data['period_filter']) : 'month';
	
		$option = '<div class="period_filter">'; 
		$option .= '<label><input type="radio" name="period_filter" value="week" ' . checked($period, 'week', false)  . '>' . __('Last Week', 'maxinbound') . '</label>'; 
		$option .= '<label><input type="radio" name="period_filter" value="month" ' . checked($period, 'month', false)  . '>' .__('Last Month', 'maxinbound') . '</label>'; 
		$option .= '<label><input type="radio" name="period_filter" value="year" ' . checked($period, 'year', false)  . '>'. __('Last Year', 'maxinbound') . '</label>'; 
		$option .= '</div>'; 
		return $option;
		
	}
	
	public function load_template() 
	{
		$settings= $this->get_settings(); 
		
		$q = MI()->ask('template/queued');
		// check if template is in queue, aka we are live.  
		if (is_null($q) || ! $q === true) 
			return; 
		
		$active = isset($settings['ga_active']) ? $settings['ga_active'] : false;
		$code = isset($settings['ga_code']) ? $settings['ga_code'] : false; 
		$loadcode = isset($settings['ga_loadcode']) ? $settings['ga_loadcode'] : false;
		
		$sysslug = MI()->ask('system/slug'); 
		$version = MI()->ask('system/version'); 
		$dir_url = trailingslashit(plugin_dir_url(__FILE__)); 
		$debug_mode = MI()->ask('system/debug'); 
		
		if (! $active) 
			return; // if GA not active, don't load script. 
				
		wp_enqueue_script($sysslug . '-statspro-front', $dir_url . 'statspro_front.js', array($sysslug . '-front'), $version, true );	
		wp_localize_script($sysslug .  '-statspro-front', 'statspro', 
			array('debug_mode' => $debug_mode ) 
		); 

		if (! $loadcode ) // if GA-code shold not be included return here.  
			return;
		
		$ga_url = 'https://www.google-analytics.com/analytics.js';
		if($debug_mode)
			$ga_url = 'https://www.google-analytics.com/analytics_debug.js';
				
		echo "<script type='text/javascript'>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','$ga_url','ga');

		  ga('create', '$code', 'auto');
		  //ga('send', 'pageview');

			</script>
		";
	
	}
	
} // class
