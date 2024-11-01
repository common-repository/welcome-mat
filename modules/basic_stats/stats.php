<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array(__NAMESPACE__ . '\moduleStats', 'init'));

class moduleStats extends miModule
{
	static $name = 'basic_stats';
	static $table_name = '';
	static $table_email = '';

	protected $filters = array();
	protected $period = array('start' => 0,
							  'end' => 0,
							  'interval' => '1D');

	protected $dataset = null;
	protected $email_dataset = null;

	public static function init($modules)
	{
		$modules->register(self::$name, get_called_class() );
	}

	public function __construct()
	{
		global $wpdb;
		$prefix = $wpdb->prefix;
		static::$table_name = $prefix . 'mi_stats';
		static::$table_email = $prefix . 'mi_contacts';

		parent::__construct();

		$this->title = __('Basic Statistics','maxinbound');

		MI()->listen('template/output-start', array($this, 'visitor'));


		MI()->offer('screens', array($this, 'attach'), 'basic_statistics');
		MI()->offer('database/install', array($this, 'database'));

		MI()->offer('landing-page-columns', function () { return
				array('unique' => __('Unique visitors','maxinbound'),
					  'visitors' => __("Views",'maxinbound'),
				); }  );

		MI()->ask('landing-page-column-unique', array($this,  'printUniqueCount'));
		MI()->ask('landing-page-column-visitors', array($this, 'printVisitCount'));

		MI()->listen('editor/metaboxes', array($this, 'box') );
	 	MI()->listen('editor/save-options', array($this, 'save_options') );

	 	MI()->listen('visitor/lastshow', array($this, 'check_lastshow_hash'), 'ask' );

		// A template gets removed.
		MI()->listen('template/delete', array($this, 'delete_stats') );

		$this->set_period();
	}

	public function visitor()
	{
		$options = $this->get_options();

		if (is_preview())
			return false;  // not a real visitor;

		if (isset($options["nocount_user"]) && $options["nocount_user"] == 1 && is_user_logged_in() )
			return false; // don't count logged in users

		if (isset($options["nocount_admin"]) && $options["nocount_admin"] == 1 && current_user_can( 'manage_options' ))
			return false; // don't count administrator

		global $wpdb;

		$ip = MI()->ask('visitor/ip');
		$hash = MI()->ask('visitor/hash');
		$referer = MI()->ask('page/referer');
		$agent = MI()->ask('visitor/agent');

		$args = array(
					  'post_id' => MI()->ask('template/post-id') ,
					  'ip' => $ip,
					  'hash' => $hash,
					  'agent' => $agent,
					  'referer' => $referer,
					  );

		$result = $wpdb->insert(static::$table_name, $args);

	}

	/**  Find last visitors in database
	*
	* If the last visit cookie is not present, check by hook for the visitor hash in the table.
	*/
	public function check_lastshow_hash($hash)
	{
		global $wpdb;

		$sql = $wpdb->prepare('SELECT date FROM ' . static::$table_name . '
							   WHERE hash = %s ORDER BY date DESC ', $hash);
		$result = MI()->db($sql,'get_var');

		if ($result)
			return $result;

		return false;

	}

	public function box()
	{
		$post_type = MI()->ask('system/post_type');
		// ID - TITLE - CALLBACK - SCREEN - CONTEXT - PRIORITY - ARGS
		add_meta_box('mod-stats', __("Statistics","maxinbound"), array($this, 'options'), $post_type,
					 'side');

	}

	public function options()
	{
		$options = $this->get_options();

		$nocount_admin = isset($options["nocount_admin"]) ? $options["nocount_admin"] : 1;
		$nocount_user = isset($options["nocount_user"]) ? $options["nocount_user"] : 1;


		$metabox = MI()->editors()->getNew('metabox', 'metabox_stats');

		$nocount = MI()->editors()->getNewField('nocount_admin', 'checkbox');
		$nocount->set('label', __('Exclude Administrator','maxinbound'));
		$nocount->set('inputvalue',1);
		$nocount->set('value', $nocount_admin);

		$metabox->addField('nocount_admin', $nocount);

		$nocountuser = MI()->editors->getNewField('nocount_user', 'checkbox');
		$nocountuser->set('label', __('Exclude Logged In User', 'maxinbound'));
		$nocountuser->set('value', $nocount_user);
		$metabox->addField('nocount_user', $nocountuser);

		MI()->tell('editor/meta-box/stats', $metabox);
		$output = $metabox->admin();
		echo $output;

	}

	public function save_options($post)
	{

		$options = array();
		$options["nocount_admin"] = isset($post["nocount_admin"]) ? 1 : -1;
		$options["nocount_user"] = isset($post["nocount_user"]) ? 1 : -1;

		$this->update_options($options);
	}


	public function set_period($start = false, $end = false, $interval = '1D')
	{
		if (! $start)
			$start = new \DateTime('-1 month midnight');
		if (! $end)
			$end = new \DateTime('midnight tomorrow');

		$this->period['start'] = $start;
		$this->period['end'] = $end;
		$this->period['interval'] = $interval;
	}

	public function get_period()
	{
		return $this->period;
	}

	/** Build central dataset for (most) stats. */
	public function buildDataSet()
	{
		$filters = $this->filters;

		$sql = "select * from " .  static::$table_name;

		$sql = $this->filters($sql);
		$sql .= ' order by date ';
		$results = MI()->db($sql, 'query', ARRAY_A);

		$this->dataset = $results;

	}

	public function buildEmailSet()
	{
		$filters = $this->filters;

		$sql = "select * from " .  static::$table_email;

		$sql = $this->filters($sql);
		$sql .= ' order by date ';

		$results = MI()->db($sql, 'query', ARRAY_A);

		$this->email_dataset = $results;

	}

	/** Execute SQL filters to filter query by */
	protected function filters($sql)
	{
		$prepares = array();
		global $wpdb;

		$sql .= ' WHERE ';

		if (is_array($this->filters) && count($this->filters) > 0)
		{

			foreach ($this->filters as $filter)
			{
				$field = $filter['field'];
				$prepares[] = $filter["value"];
				$operator = isset($filter['operator']) ? $filter['operator'] : '=';
				$sql .= $field . ' ' . $operator . ' %s AND ';
			}

			$sql = $wpdb->prepare($sql, $prepares);
		}
		$sql = $this->period_filter($sql);
		$sql .= '1=1'; // finish ends

		return $sql ;
	}

	protected function period_filter($sql)
	{
		$period = $this->period;
		$start = $period['start'];
		$end = $period['end'];

		$sql_start = $start->format('Y-m-d H:i:s');
		$sql_end = $end->format('Y-m-d H:i:s');

		$sql .= " date between '$sql_start' and '$sql_end' AND ";
		return $sql;
	}

	/** Generate key statistics as a number used in the Dashboard view of Statistics **/
	public function get_figures()
	{
		global $wpdb;
		if (is_null($this->dataset))
			$this->buildDataSet();
		if (is_null($this->email_dataset))
			$this->buildEmailSet();

		$data = $this->dataset;

		$visits = count($data);
		$visitors = array('name'  => 'number_visitors',
					   'title' => __("Number of views",'maxinbound'),
					   'value' => $visits,
					   );

		$uniquedata = array();
		foreach($data as $record)
		{
			if (! in_array($record['hash'], $uniquedata))
				$uniquedata[] = $record['hash'];
		}

		$unique_count = count($uniquedata);

		$unique = array('name' => 'unique_visitors',
						'title' => __("Unique visitors", 'maxinbound'),
						'value' => $unique_count,
					   );

		$email_count = count($this->email_dataset);
		$email_conversion = 0;
		if ($unique_count > 0)
			$email_conversion = ($email_count / $unique_count) * 100;
		
		$emails = array('name' => 'emails',
										'title' => __('Emails Collects', 'maxinbound'),
										'value' => $email_count,
							);
		$conversion = array('name' => 'conversion',
												'title' => __('Conversion Rate', 'maxinbound'),
												'value' => $email_conversion . '%',
		);

		$stats = array();
		$stats['visitors'] = $visitors;
		$stats['unique'] = $unique;
		$stats['email'] = $emails;
		$stats['conversion'] = $conversion;

		return $stats;

	}

	public function get_post_select($filter_data)
	{
		$posts = $this->get_all_posts();

		$selected_id = isset($filter_data['select_post']) ? intval($filter_data['select_post']) : false;

		$stats_select = '<select name="select_post">';
		$stats_select .= '<option value="0">' . __('All','maxinbound') . '</option>';
		foreach($posts as $post)
		{
			$id = $post->ID;
			$title = $post->post_title;
			if ($title == '')
				$title = __('[Untitled]', 'maxinbound');
			$stats_select .= "<option value='$id' " . selected($id, $selected_id, false) . ">$title</option>";
		}
		$stats_select .= "</select>";

		return $stats_select;

	}

	public function data_points()
	{
		if (is_null($this->dataset))
			$this->buildDataSet();
		if (is_null($this->email_dataset))
			$this->buildEmailSet();

		$data = $this->dataset;
		$email_data = $this->email_dataset;

		$start = $this->period['start'];
		$end = $this->period['end'];
		$interval = $this->period['interval'];
		$di_interval = "P" . $interval;

		$dateper = new \DatePeriod($start, new \DateInterval($di_interval), $end );
		$periods = iterator_to_array($dateper);
		$period_count = count($periods);

		// check date is based on correct format of the date.
		$interval = $this->period['interval'];
		if (strpos($interval,'D') >= 0)
			$format_str = 'Ymd';
		elseif (strpos($interval, 'M') >= 0)
			$format_str = 'Ym';
		elseif (strpos($interval, 'H') >= 0)
			$format_str = 'YmdH';

		$unique = array();
		$graph = array();

		// init the whole period with empty values
		foreach($periods as $cur)
		{
			$formatted = $cur->format($format_str);
			if (! isset($graph[$formatted]))
			{
				$graph[$formatted] = array('visits' => 0, 'unique' => 0, 'emails' => 0);
				$unique[$formatted] = array();
			}
		}

		// Loop the visitors.
		foreach($data as $index => $result)
		{

			$row_date = new \DateTime($result['date']); // visitor date
			$row_ts = $row_date->getTimestamp(); // visitor ts
			$post_id = $result['post_id'];
			$hash = $result['hash'];
			$prev = 0;

			// The periods on the X-axis. Find the data item ( visitor ) within these jumps
			foreach($periods as $index => $cur)
			{
				$cur_ts = $cur->getTimestamp();

				$formatted = $cur->format($format_str);

				/* Check if current visitor timestamp after previous point, but before the current one - we found the range.
				   If it's the last period point, assume the SQL didn't select out-of-range values and treat it as last point.
				*/
				if ( ($cur_ts >= $row_ts && $prev <= $row_ts) || ( ($period_count-1) == $index )  )
				{
					$graph[$formatted]['visits']++; // record visit

					if (! isset($unique[$formatted]) || ! in_array($hash, $unique[$formatted]) )
					{
						$unique[$formatted][] = $hash;
						$graph[$formatted]['unique']++;
					}
					break; // data found, break out of period loop
				}
				$prev = $cur_ts;
			}
		}

		// loop emails. This is copy-paste, bad.
		foreach($email_data as $index => $result)
		{

			$row_date = new \DateTime($result['date']); // visitor date
			$row_ts = $row_date->getTimestamp(); // visitor ts
			$prev = 0;
			// The periods on the X-axis. Find the data item ( visitor ) within these jumps
			foreach($periods as $index => $cur)
			{
				$cur_ts = $cur->getTimestamp();
				$formatted = $cur->format($format_str);

				/* Check if current visitor timestamp after previous point, but before the current one - we found the range.
					 If it's the last period point, assume the SQL didn't select out-of-range values and treat it as last point.
				*/
				if ( ($cur_ts >= $row_ts && $prev <= $row_ts) || ( ($period_count-1) == $index )  )
				{
					$graph[$formatted]['emails']++; // record visit
					break; // data found, break out of period loop
				}
				$prev = $cur_ts;
			}
		}


		$labels = array('visits' => sprintf( __('Visitors','maxinbound') ),
						'unique' => sprintf( __('Unique Visitors', 'maxinbound') ),
						'emails' => sprintf( __('Emails', 'maxinbound')),
		);
		$graph['labels'] = $labels;
		//$graph['totals'] = array('visits' => '', 'unique' => '');

		return $graph;
	}


	/** Get Filter options for display in the stats screen */
	public function getFilterOptions($row = 'primary', $filter_data = array() )
	{
		$options = array();
		if ($row == 'primary')
		{
			$options[] = $this->get_post_select($filter_data);
		}
		return $options;
	}

	public function setFilters($filters)
	{
		if ( isset($filters['select_post']) )
		{
			$id = $filters['select_post'];
			if ($id > 0)
			{
				$this->filters[] = array('field' => 'post_id',
									  'value' => $id,
							 	);
			}
		}
	}

	/** Used by data points function to find boundaries of the date points */
	/* Currently not in use ?? */
	protected function generateInterval( )
	{
		$start = $this->period['start'];
		$end = $this->period['end'];
		$interval = $this->period['interval'];
		$di_interval = "P" . $interval;

		$dateArr = array();

		// check date is based on correct format of the date.
		if (strpos($interval,'D') >= 0)
			$format_str = 'Ymd';
		elseif (strpos($interval, 'M') >= 0)
			$format_str = 'Ym';
		elseif (strpos($interval, 'H') >= 0)
			$format_str = 'YmdH';

		$startday = $start->format($format_str);
		$endday = $end->format($format_str);

		$i =0 ;
		//$dateArr[$startday] = array('visits' => 0, 'unique' => 0);
		$dataArr[] = $startday;

		while($start->getTimestamp() < $end->getTimestamp() )
		{
			$f = $start->format($format_str);
			//$dateArr[$f] = array('visits' => 0, 'unique' => 0);
			$dateArr[] = $f;
			$start->add(new \DateInterval($di_interval));
			$i++;

		}
		$dataArr[] = $endday;
		//$dateArr[$endday] = array('visits' => 0, 'unique' => 0);
		return $dateArr;
	}

	/** Load the latests visits table data */
	public function get_visits($limit = 20, $offset = 0)
	{
		$post_cache = array();
	//	$post_args = array('post_type' =>

		$limit = ' LIMIT 20 ';

		$sql = 'SELECT * FROM ' . static::$table_name;
		$sql .= ' ORDER BY DATE desc ';
		$sql .= $limit;

		global $wpdb;
		$results = $wpdb->get_results($sql);

		foreach($results as $index => $result)
		{
			$post_id = $result->post_id;
			if (! isset($post_cache[$post_id]))
				$post_cache[$post_id] = get_post($post_id);

			if (is_null($post_cache[$post_id]))  // in case something wrong / deleted
				$results[$index]->post_title = __('Unknown Project','maxinbound');
			else
				$results[$index]->post_title = $post_cache[$post_id]->post_title;

		}
		return $results;
	}


	/** Print Visitor count
	*
	*	Function used in admin columns
	*/
	function printVisitCount($post_id)
	{
		$this->filters = array();
		$this->filters[] = array('field' => 'post_id',
							 'value' => $post_id,
							);
		$this->buildDataSet();

		$stats = $this->get_figures( );
		if (isset($stats["visitors"]) && $stats["visitors"]["value"] > 0)
			echo $stats['visitors']['value'];
		else
			echo "0";

	}

	/** Print Unique visitors
	*
	*	Function used in admin columns
	*/
	function printUniqueCount($post_id)
	{
		$this->filters = array();
		$this->filters[] = array('field' => 'post_id',
							 'value' => $post_id,
							);
		$this->buildDataSet();
		$stats = $this->get_figures();
		echo $stats['unique']['value'];
	}

	/** Database table SQL */
	public function database()
	{
		$sql = 'CREATE TABLE ' . static::$table_name . ' (
				id INT NOT NULL AUTO_INCREMENT,
				post_id INT NOT NULL,
				ip varchar(70),
				hash varchar(100),
				referer varchar(300),
				agent varchar(300),
				date timestamp,
				PRIMARY KEY  (id)
				);
				';
		return $sql;
	}



	/** A template gets fully removed from the system
	*
	*	When a template is removed so should the connected stats, otherwise totals and what not will present a false image.
	*
	*	@param $post_id The template post id
	*/
	public function delete_stats($post_id)
	{
		if ( is_int($post_id) && $post_id > 0)
		{
			global $wpdb;
			$sql = ' Delete from ' . static::$table_name . ' where post_id = %d';
			$sql = $wpdb->prepare($sql, $post_id);
			$wpdb->query($sql);

		}
	}

} // class
