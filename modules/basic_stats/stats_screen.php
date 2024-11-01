<?php
namespace MaxInbound;

defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_screen', array(__NAMESPACE__ . '\miStatsScreen', 'init'));

class miStatsScreen
{
	protected static $screen_id = 'basic_statistics';

	protected $module = null;

	public static function init($module)
	{
		$obj = new self;
		$module->registerScreen(static::$screen_id, __("Statistics","maxinbound"), array($obj, 'show') );

		MI()->listen('setup/enqueue-scripts', array($obj, 'styles') );
	}

	public function __construct()
	{
	 	// ajax data
	 	MI()->listen('system/ajax/get_chart', array($this,'get_data'));
	}


	public function get_data($post)
	{
		$filters = array();
		if (isset($post['filters']))
			parse_str($post['filters'], $filters); 	

		$mod = $this->getModule();
		$mod->setFilters($filters);

		$graph = $mod->data_points();
		$figures = $mod->get_figures();

		// this needs to be dynamifyd. Now using only this filter.

		// structure of response.
		$data = array(
				'filters' => array('primary' => array(),
								   'secondary' => array()
				),
				'figures' => $figures,
				'graph' => array('x' => array() ),
		);

		$primary = $mod->getFilterOptions('primary', $filters);
		$secondary = $mod->getFilterOptions('secondary', $filters);

		$data['filters']['primary'] = $primary;
		$data['filters']['secondary'] = $secondary;

		$labels = array();
		if (isset($graph['labels']))
		{
			$labels = $graph['labels'];
			unset($graph['labels']);
		}
		foreach($graph as $xval => $data_ar)
		{
			$data['graph']['x'][] = (string) "$xval";
			foreach($data_ar as $line => $val)
			{
				if (! isset($data['graph'][$line]))
					$data['graph'][$line] = array($val);
				else
					$data['graph'][$line][] = $val;
			}
		}
		$data['graph']['labels'] = $labels;
		$period = $mod->get_period();
		$wp_dateformat = get_option( 'date_format' );

		$data['period']['start'] = date_i18n($wp_dateformat, $period['start']->getTimeStamp() );
		$data['period']['end'] = date_i18n($wp_dateformat, $period['end']->getTimeStamp() );

		echo json_encode($data);
		exit();
	}

    protected function getModule()
    {
    	if (is_null($this->module))
		{
			MI()->collect('screens'); // screens hook is not loading with ajax.
		    $this->module = MI()->modules()->getAttachedScreen(static::$screen_id);
		}
		return $this->module;
    }

	public function styles()
	{
		if (! MI()->modules()->is_screen_active(self::$screen_id))
			return false;

		$plugin_url = MI()->get_plugin_url();
		$slug = MI()->ask('system/slug');
		$version = MI()->ask('system/version');

		wp_enqueue_style('mi_basic_stats', $plugin_url . 'modules/basic_stats/css/basic_stats.css');
		wp_enqueue_style($slug . '-c3-style', $plugin_url . 'assets/libraries/c3/c3.min.css');
		wp_enqueue_script($slug . '-d3-script', $plugin_url . 'assets/libraries/c3/d3.min.js', array('jquery'), $version, true);
		wp_enqueue_script($slug . '-c3-script', $plugin_url . 'assets/libraries/c3/c3.min.js', array('jquery', $slug . '-d3-script'), $version, true);
		wp_enqueue_script($slug . '-basic_stats', $plugin_url . 'modules/basic_stats/js/stats.js', array('jquery', $slug . '-c3-script'), $version, true);

	}

	public function show()
	{

	$mod = MI()->modules()->getAttachedScreen(static::$screen_id);
	$mod->buildDataSet();

	$args = array(
			"title" => __("Statistics","maxinbound"),
			'mainclass' => 'stats',
	);
	MI()->modules()->header($args);

	$post_type = MI()->ask('system/post_type');
	$page = sanitize_text_field($_GET["page"]);


?>


<form id='filter_form' action="<?php echo admin_url('edit.php'); ?> ">
	<?php wp_nonce_field( MI()->ask('system/ajax_action') ,'chart_nonce'); ?>

	<h2><?php _e('Showing:','maxinbound'); ?>
	</h2>
 	<div class='filters primary'></div>
	<button class='button' name="filter_stats" type='button'><?php _e('Show', 'maxinbound'); ?></button>


<div class='filters secondary'>

</div>

</form>


<div class='load_overlay'>
	<img src="/wp-admin/images/loading.gif">

</div>
<div class='basic_stats dashboard'>


</div>

<div class='period_date'>
	<span class='start'></span>
	<span class='end'></span>
</div>


<div id='chart' class='basic_stats chart'>	<span class='loading'></span> </div>


<?php if (isset($view->visits)) : ?>
<div class='basic_stats overview'>
	<h2><?php _e('Last Visits','maxinbound'); ?></h2>
	<div class='row heading'>
		<span><?php _e('Project','maxinbound'); ?></span>
		<span><?php _e('IP','maxinbound'); ?></span>
		<span><?php _e('User Agent','maxinbound'); ?></span>
		<span><?php _e('Date','maxinbound'); ?></span>
	</div>

	<?php foreach($view->visits as $item): ?>
		<div class='row item item-<?php echo $item->id ?>'>
			<span><?php echo $item->post_title ?></span>
			<span><?php echo $item->ip ?></span>
			<span><?php echo $item->agent ?></span>
			<span><?php echo date_i18n( get_option( 'date_format' ), strtotime($item->date));?>,
			<?php echo date_i18n( get_option( 'time_format' ), strtotime($item->date)); ?>

			 </span>

		</div>
	<?php endforeach; ?>
</div>
<?php endif; ?>


<?php } // show

} // class
