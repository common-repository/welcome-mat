<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_module', array('MaxInbound\moduleEmail', 'init'));


class moduleEmail extends miModule
{
	static $name = 'email';
	static $table_name = '';

	public static function init($modules)
	{
		$modules->register(self::$name, get_called_class() );

	}

	public function __construct()
	{

		global $wpdb;
		$prefix = $wpdb->prefix;
		static::$table_name = $prefix . 'mi_contacts';

		parent::__construct();

		$this->title = __('Basic Email','maxinbound');

		MI()->offer('database/install', array($this, 'database'));

		MI()->offer('screens', array($this, 'attach'), 'basic_email');

		// check if new posted email should be saved
		MI()->listen('system/ajax/post-form', array($this, 'check_email'));

		// remove single email hit.
		MI()->listen('system/ajax/remove-single-email', array($this, 'ajax_remove_email'));

		MI()->offer('statistics', array($this, 'get_stats'));
		MI()->offer('template/decide-load', array($this, 'check_visitor_email'));

		MI()->offer('landing-page-columns', function () { return
				array('email' => __('Emails','maxinbound'),
				); }  );

		MI()->ask('landing-page-column-email', array($this,  'printEmailCount'));

		MI()->listen('template/delete', array($this, 'delete_emails') );
	}

	public function check_email($post)
	{
		// should be dynamified probably
		if (! isset($post["email"]))
			return false;

		// optional
		$name = isset($post["fname"]) ? $post["fname"] : '';

		$post_id = MI()->ask('system/ajax/post-id');
		$email = $post["email"];
		$hash = MI()->ask('visitor/hash'); // unique visitor id.

		$is_preview = isset($post['is_preview']) ? $post['is_preview']: false;

		if ($is_preview)
			return false;

		if ($this->email_exists($email, $post_id))
		{
		 	global $wpdb;  // if email exists, update visitor hash, so WM is not displayed again right after.
		 	$result = $wpdb->update(static::$table_name,
		 				 array('hash' => $hash),  // what
		 				 array('post_id' => $post_id, 'email' => $email),  // where
		 				 array('%s'),  // format what
		 				 array('%d', '%s')
		 				);


			return false;

		}

		$insert = array('post_id' => $post_id,
						'email' => $email,
						'name' => $name,
						'hash' => $hash,
						);
		global $wpdb;
		$result = $wpdb->insert(static::$table_name, $insert);

		return $result; // returning to nowhere.

	}

	public function email_exists($email, $post_id)
	{
		global $wpdb;
		$sql = $wpdb->prepare('select id from ' . static::$table_name . ' where post_id = %d and
							   email = %s', $post_id, $email);
		$result = $wpdb->get_results($sql);
		if (count($result) > 0)
			return true;
		else
			return false;

	}

	/** Check if visitor already gave email
	*
	* Visitors which already signed-up shouldn't be bothered again.
	*
	*/
	public function check_visitor_email($my_args, $collect_args)
	{
		$post_id = isset($collect_args['post_id']) ? $collect_args['post_id'] : 0;
		if ($post_id <= 0)
			return;

		$hash = MI()->ask('visitor/hash');

		global $wpdb;
		$sql = $wpdb->prepare('SELECT id from ' . static::$table_name . ' where post_id = %d and hash = %s', $post_id,  $hash);

		$results = $wpdb->get_results($sql);

		if (count($results) > 0)
		{
			MI()->tell('template/display/reason', 'Email already collected');
			return false;

		}
	}

	/** Generates the data for the emails per project count and for the 'last received emails' function **/
	public function getView($args = array() )
	{
		$view = new \stdClass;

		global $wpdb;
		// is still not very good
		$sql = 'SELECT post_title as title, post_id, count(post_id) as emails FROM ' . $wpdb->posts . ' as p
				LEFT JOIN ' . static::$table_name . ' as e
				ON p.ID = e.post_id
				WHERE post_type = %s
				GROUP BY post_id LIMIT 20';

		$post_type = MI()->ask('system/post_type');
		$sql = $wpdb->prepare($sql, $post_type);

		$results= MI()->db($sql);
		$view->per_pages = $results;

		$sql = " SELECT * FROM " . static::$table_name . ' as mi
				 LEFT JOIN ' . $wpdb->posts . ' as wp on wp.ID = mi.post_id
				 ORDER BY date desc
				 LIMIT 20
				 ';
		$results = MI()->db($sql);

		$view->last_emails = $results;

		return $view;

	}

	public function generate_csv($post_id)
	{
		if (! is_numeric($post_id))
			return false;

		global $wpdb;

		// Retrieve the page name
		//$sql = $wpdb->prepare('SELECT post_title from '. $wpdb->posts . ' where ID = %d ', $post_id );
		$post_title = get_the_title($post_id);
		if ($post_title == '')
			$post_title = __('[Untitled]','maxinbound');



		// Retrieve the emails
		$sql = $wpdb->prepare('SELECT email, name, date from ' . static::$table_name . ' where post_id = %d ', $post_id);

		$results = MI()->db($sql,'query', ARRAY_A);

		$seperator = ';';

		$output = __('Project','maxinbound') . $seperator .
				  __('Email', 'maxinbound') . $seperator .
				  __('Name', 'maxinbound') . $seperator .
				  __('Date', 'maxinbound') . $seperator .
				  "\n";

		foreach($results as $post)
		{
			$output .= $post_title . $seperator .  implode(',', $post) . "\n";

		}
		return $output;

	}

	public function get_stats($args, $collector)
	{
		$post_id = isset($collector['post_id']) ? $collector['post_id'] : 0;

		$sql = 'SELECT COUNT(*) as value FROM ' . static::$table_name;
		if ($post_id > 0)
		{
			global $wpdb;
			$sql .= ' WHERE post_id = %d ';
			$sql = $wpdb->prepare($sql, $post_id);
		}
		$value = MI()->db($sql, 'get_var');


		$emails = array('name' => 'number_of_emails',
						'title' => __("Number of Emails", 'maxinbound'),
						'value' => $value,
						'filters' => array('post_id', 'date'),
						);
		return $emails;
		//MI()->offer('statistics', $emails);
	}

	public function database()
	{
		$sql = 'CREATE TABLE ' . static::$table_name . ' (
				id INT NOT NULL AUTO_INCREMENT,
				post_id INT NOT NULL,
				email varchar(100),
				name varchar(100),
				hash varchar(100),
				date timestamp,
				PRIMARY KEY  (id)
				);
				';
		return $sql;
	}

	public function printEmailCount($post_id)
	{
		global $wpdb;

		$sql = ' SELECT count(id) FROM ' . self::$table_name . ' WHERE POST_ID = %d ';
		$sql = $wpdb->prepare($sql, $post_id);

		$count = $wpdb->get_var($sql);
		echo $count;

	}


	/** A template gets fully removed from the system
	*
	*	When a template is removed so should the connected emails.
	*
	* @param $post_id The template post id
	*/
	public function delete_emails($post_id)
	{
		if ( is_int($post_id) && $post_id > 0)
		{
			global $wpdb;
			$sql = ' DELETE FROM ' . static::$table_name . ' where post_id = %d';
			$sql = $wpdb->prepare($sql, $post_id);
			$wpdb->query($sql);

		}
	}

	public function ajax_remove_email($post)
	{
			global $wpdb;

			$id = isset($post['param']) ? intval($post['param']) : false;

			$sql = ' DELETE FROM ' . static::$table_name . ' WHERE id = %d';
			$sql = $wpdb->prepare($sql, $id);

		  $wpdb->query($sql);

			$result = array('item_deleted' => $id);
			echo json_encode($result);
			exit();
	}

} // class
