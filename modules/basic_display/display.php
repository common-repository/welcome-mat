<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ .'\moduleDisplay', 'init'));

class moduleDisplay extends miModule
{
	static $name = 'basic_display';

	public static function init($modules)
	{
		$modules->register(self::$name, get_called_class() );
	}

	public function __construct()
	{
		parent::__construct();

	 	//MI()->offer('editor/module-options', array($this, 'options') );
	 	MI()->listen('editor/save-options', array($this, 'save_options') );
		MI()->listen('editor/metaboxes', array($this, 'box') );

		// listen for asks here
		MI()->listen('template/check_load', array($this, 'check_load'), 'ask' );
	//	MI()->tell('mi-core-options', array($this, 'options') );

	}

	public function box()
	{
		$post_type = MI()->ask('system/post_type');
		// ID - TITLE - CALLBACK - SCREEN - CONTEXT - PRIORITY - ARGS
		add_meta_box('mod-display', __("Display","maxinbound"), array($this, 'options'), $post_type,
					 'side');
	}

	/** Check if page shoud load
	*
	*	On basis of situation, decide to load -or not- this page and thus override requested template.
	*/
	public function check_load()
	{
		global $pagenow;

		$args = array(
			'post_type' => MI()->ask('system/post_type') ,
			'post_status' => 'publish',
		);

		if ($pagenow == 'wp-login.php' || $pagenow == 'wp-register.php')
		{
			MI()->tell('template/display/reason', 'Login / Registration page detected - Not loading');
			return false;
		}

		if (is_admin() && ! MI()->ask('system/front_caching')  )
		{
			MI()->tell('template/display/reason', ' Is admin true - Not loading');
			return false;
		}

		if (is_customize_preview() )
		{
			MI()->tell('template/display/reason', 'Theme Customizer Page detected - Not loading');
			return false;
		}
		// WP Preview Screen
		if (isset($_GET['preview']))
		{
			if (isset($_GET['p']) && intval($_GET['p']) > 0)
				$post_id = intval($_GET['p']);
			elseif(isset($_GET["preview_id"]) && intval($_GET["preview_id"]) > 0)
				$post_id = intval($_GET['preview_id']);
			else {
				global $post;
				global $wp_query;
				$post_id = $post->ID;
			}

			if (! isset($post_id) )
			{
				MI()->tell('template/display/reason', 'Preview but no post ID');
				return false;
			}
			$post = get_post($post_id);
			if ($post->post_type !== $args['post_type'] )
			{
				MI()->tell('template/display/reason', 'Preview but not of our Post Type');
				return false;
			}

			MI()->tell('template/post-id', $post_id);
			$template_name = get_post_meta($post_id,'_maxinbound_template',true);
			MI()->tell('template/template-name', $template_name);
			MI()->tell('template/display/reason', 'Preview Detected');
			return true;
		} // Preview

		$posts = get_posts($args);



		$loadable = array();
		foreach($posts as $post)
		{

			$checked = $this->decide_load($post);
			MI()->log('Decide Load on :' . $post->ID , array(
				MI()->ask('template/display/reason'),
				var_export($checked, true),
				)
			);

			if ($checked)
				$loadable[] = $post;
		}


		MI()->tell('template/display/loadable', $loadable);
		$loadable = MI()->ask('template/display/loadable');

		if (count($loadable) > 0)
		{
			$number = count($loadable);
			$random = rand(0, ($number-1) ); // if only one, rand is 0,0 -> 0
			$post  = $loadable[$random];

			MI()->tell('template/post-id', $post->ID);
			$template_name = get_post_meta($post->ID,'_maxinbound_template',true);
			MI()->tell('template/template-name', $template_name);

			return true;
		}

		MI()->tell('template/display/reason', 'No Posts found');
		return false;
	}

	protected function decide_load($post)
	{
		$post_id = $post->ID;

		$options = $this->get_options($post_id);

		$opinions = MI()->collect('template/decide-load', array('post_id' => $post_id) );

		foreach($opinions as $op)
		{
			if ($op === false)
			{
				return false;  // somebody else smarter.
			}
		}

		if (! isset($options["show_times"]))
			return false;

		$show_times = $options["show_times"];
		$hide_mobile = isset($options['hide_mobile']) ? intval($options['hide_mobile']) : false;
		$last_show =  MI()->ask('visitor/last-show');
		$last = isset($last_show[$post->ID]) ? intval($last_show[$post_id]) : false;

		if ($hide_mobile == 1)
		{
			$is_mob = MI()->ask('visitor/is_mobile');
			$is_tab = MI()->ask('visitor/is_tablet');

			if ($is_mob == true || $is_tab  == true)
			{
				MI()->tell('template/display/reason', 'Is Mobile or Tablet; Mobile devices not displaying');
				return false;
			}

		}

		if ($last > 0)
		{
			$did_show = true;
		}
		else
		{
			$did_show = false;
		}

		switch($show_times)
		{
			case 'once':
				if (! $did_show)
				{
					MI()->tell('template/display/reason', 'Show Once - Did not Show');
					return true;
				}
				else
				{
					MI()->tell('template/display/reason', 'Show Once - Did show');
					return false;
				}
			break;
			case 'visit':
				$session = MI()->data()->checkSessionCookie($post_id);
				if ($session === true)
				{
					MI()->tell('template/display/reason', 'Show visit - Did Show');
					return false;
				}
				if ($session === false)
				{
					MI()->tell('template/display/reason', 'Show visit - Did not Show ');
					return true;
				}

			break;
			case 'schedule':
				return $this->checkSchedule($last, $options);
			break;
		}

	}

	protected function checkSchedule($last_show, $options)
	{
		// default these to 1 hour interval in case they are not set, to prevent spamming.
		$amount = isset($options['show_amount']) ? $options['show_amount'] : 1;
		$interval = isset($options['show_interval']) ? $options['show_interval'] : 'hours';

		if ( ! isset($amount) || $amount < 0 || ! $interval )
		{
			MI()->tell('template/display/reason', " Amount ($amount) or Interval ($interval) not set ");
			return true; // when serious error, allow for display.
		}
		switch($interval)
		{
			case 'days':
				$time = $amount * DAY_IN_SECONDS;
			break;
			case 'months':
				$time= $amount * MONTH_IN_SECONDS;
			break;
			case 'hours':
			default:
				$time = $amount * HOUR_IN_SECONDS;
			break;
		}

		$time_passed = time() - $last_show;

		if ($time_passed >= $time)
		{
			MI()->tell('template/display/reason', 'Schedule, last show: ' . $last_show . ' exceeded time passed ' .  $time_passed . ' of ' . $time );
			return true;
		}
		else
		{
			MI()->tell('template/display/reason', 'Schedule, exceeded time NOT passed '. $time_passed . ' of ' . $time );
			return false;
		}
	}

	public function save_options($post)
	{
		$options = array();

		$options['hide_mobile'] = isset($post['hide_mobile']) ? $post['hide_mobile'] : 0;
		$options["show_times"] = isset($post["show_times"]) ? $post["show_times"] : '';
		$options["show_amount"] = isset($post["show_amount"]) ? intval($post["show_amount"]) : ''; 
		$options["show_interval"] = isset($post["show_interval"]) ? $post["show_interval"] : '';

		$this->update_options($options);
	}

	public function options()
	{
		// enqueue on display.

		$plugin_url = MI()->get_plugin_url();
		wp_enqueue_style('mi_basic_display', $plugin_url . 'modules/basic_display/css/basic_display.css');

		$options = $this->get_options();

		$show_times = isset($options["show_times"]) ? $options["show_times"] : 'once';
		$show_amount = isset($options["show_amount"]) ? intval($options["show_amount"]) : 1;
		$show_interval = isset($options["show_interval"]) ? $options["show_interval"] : 'hours';
		$hide_mobile = isset($options['hide_mobile']) ? intval($options['hide_mobile']) : 1;

		$metabox = MI()->editors->getNew('metabox', 'metabox_display');

		$hide_mob = MI()->editors()->getNewField('hide_mobile', 'checkbox');
		$hide_mob->set('id', 'hide_mobile');
		$hide_mob->set('type','checkbox');
		$hide_mob->set('inputvalue', '1');
		$hide_mob->set('value', $hide_mobile);
		$hide_mob->set('label', __('Don\'t display on mobile devices', 'maxinbound'));

		$hide_mob->setTemplate('switch.tpl', 'core');

		$metabox->addField('hide_mobile', $hide_mob);

		$show_once =  MI()->editors()->getNewField('show_times', 'checkbox');
		$show_once->set('type','radio');
		$show_once->set('label',  __('Show only once','maxinbound'));
		$show_once->set('inputvalue', 'once');
		$show_once->set('value', $show_times);

		$metabox->addField('show_times_once', $show_once );

		$show_visit = MI()->editors()->getNewField('show_times', 'checkbox');
		$show_visit->set('type', 'radio');
		$show_visit->set('label', __('Show every visit', 'maxinbound'));
		$show_visit->set('inputvalue', 'visit');
		$show_visit->set('value', $show_times);

		$metabox->addField('show_times_visit', $show_visit);


		$show_sched =  MI()->editors()->getNewField('show_times', 'checkbox');
		$show_sched->set('type','radio');
		$show_sched->set('label', __('Show every','maxinbound'));
		$show_sched->set('inputvalue', 'schedule');
		$show_sched->set('value', $show_times);

		$metabox->addField('show_times_interval', $show_sched );

		$spacer = MI()->editors->getNewField('','spacer');
		$spacer->set('type', 'wrapper');
		$spacer->set('content','<div class="interval">');

		$metabox->addField('start_el',$spacer);

		$amount = MI()->editors()->getNewField('show_amount', 'text');
		$amount->set('label', __('Amount','maxinbound'));
		$amount->set('type', 'number');
		$amount->set('min', 0);
		$amount->set('value', $show_amount);

		$metabox->addField('show_amount', $amount);

		$interval = MI()->editors()->getNewField('show_interval', 'checkbox');
		$interval->set('type', 'radio');
		$interval->set('value', $show_interval);

		$interval_hours = clone $interval;
		$interval_days = clone $interval;
		$interval_months = clone $interval;

		$interval_hours->set('inputvalue','hours');
		$interval_hours->set('label', __('Hours', 'maxinbound'));

		$metabox->addField('interval_hours', $interval_hours);

		$interval_days->set('inputvalue','days');
		$interval_days->set('label', __('Days','maxinbound'));

		$metabox->addField('interval_days', $interval_days);

		$interval_months->set('inputvalue','months');
		$interval_months->set('label', __('Months','maxinbound') );

		$metabox->addField('interval_months', $interval_months);

		$spacer = MI()->editors->getNewField('','spacer');
		$spacer->set('type', 'wrapper');
		$spacer->set('content','</div>');

		$metabox->addField('end_el',$spacer);

		MI()->tell('editor/metabox/display', $metabox);
		$output = $metabox->admin();

		echo $output;

	}

} // class
