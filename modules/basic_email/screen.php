<?php
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

add_action('maxinbound_register_screen', array(__NAMESPACE__ . '\miEmailsScreen', 'init'));

class miEmailsScreen
{
	protected static $screen_id = 'basic_email';

	public static function init($module)
	{
		$obj = new self;
		$module->registerScreen(static::$screen_id, __("Emails","maxinbound"), array($obj, 'show') );

		MI()->listen('setup/enqueue-scripts', array($obj, 'styles') );
		MI()->listen('system/ajax/exportcsv', array($obj, 'ajaxcsv') );
	}

	public function styles()
	{
		if (! MI()->modules()->is_screen_active(self::$screen_id))
			return false;

		$slug = MI()->ask('system/slug');
		$version = MI()->ask('system/version');

		$plugin_url = MI()->get_plugin_url();
		wp_enqueue_style('mi_basic_email', $plugin_url . 'modules/basic_email/css/email.css');

		wp_enqueue_script($slug . '-basic_email', $plugin_url . 'modules/basic_email/js/email.js', array('jquery', $slug .'-maxajax'), $version, true);
	}

	public function output_csv($post_id)
	{

		$csv = MI()->modules()->getAttachedScreen(static::$screen_id)->generate_csv($post_id);

		$time = str_replace(' ','', date_i18n('Ymd', time()  ) );
		$name = strtolower(str_replace(' ','', MI()->ask('system/nice_name')));
		$filename =  $name . '-' . $time . '.csv';

	 	header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);
    	header("Content-Type: application/force-download");
   		header("Content-Type: application/octet-stream");
    	header("Content-Type: application/download");

		echo $csv;

		exit($post_id);
	}

	public function ajaxcsv($post)
	{

		$post_id = intval($post["post_id"]);
		if ($post_id > 0)
		{
			MI()->collect('screens'); // manually attach screens, since this is normally done when adding menupages.
			$this->output_csv($post_id);
		}
	}

	public function show()
	{
		if (isset($_GET["export_csv"]))
		{
			$this->output_csv( intval($_GET["export_csv"]) );
		}
		else
		{
			$this->screen();
		}
	}

	public function screen()
	{

		$view = MI()->modules()->getAttachedScreen(static::$screen_id)->getView();

		$args = array(
				"title" => __("Emails","maxinbound")
		);
		MI()->modules()->header($args);
?>

	<div class='actions'>

	</div>

	<div><?php // _e( sprintf(" %d emails collected ",$result_count), 'maxinbound'); ?></div>


	<div class='basic_email overview'>
		<h2><?php _e("Projects","maxinbound"); ?></h2>
		<div class='row heading'>
			<span><?php _e("Page", 'maxinbound'); ?></span>
			<span><?php _e('Emails', 'maxinbound'); ?></span>
			<span></span>
		</div>

		<?php foreach($view->per_pages as $item):
			$email_count = intval($item->emails);
			$post_title = $item->title;
			if ($post_title == '')
				$post_title = __('[Untitled]','maxinbound');
			?>
			<div class='row item'>
				<span><?php echo esc_attr($post_title) ?></span>
				<span><?php echo ($email_count > 0) ? $email_count : __('No Emails yet', 'maxinbound'); ?></span>
				<?php if ($email_count > 0) :
					$url = admin_url('edit.php?post_type=' . $_GET['post_type'] . '&page=basic_email&export_csv=' . $item->post_id );
					$param = json_encode( array('post_id' => $item->post_id));
					$ajax_action = MI()->ask('system/ajax_action');
					?>
					<p>
					<form method="POST" action="<?php echo admin_url('admin-ajax.php'); ?>">
					<input type="hidden" name="plugin_action" value="exportcsv">
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce($ajax_action) ?>">
					<input type="hidden" name="action" value="<?php echo $ajax_action ?>">
					<input type="hidden" name="post_id" value='<?php echo $item->post_id ?>'>
					<button type="submit" class='export-button button' ><?php _e("Export as CSV",'maxinbound'); ?></button>
					</form>
					</p>
				<?php else: ?>
					<span>&nbsp;</span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

	</div>

	<div class='clear'></div>
<?php if (count($view->last_emails) > 0): ?>
	<h2><?php _e("Last incoming emails","maxinbound"); ?></h2>

	<div class='basic_email overview'>
		<div class='row heading'>
			<span>&nbsp;</span>
			<span>&nbsp;</span>
			<span><?php _e("Project","maxinbound"); ?></span>
			<span><?php _e("Email","maxinbound"); ?></span>
			<span><?php _e("Date","maxinbound"); ?></span>
		</div>
		<?php

		foreach($view->last_emails as $item)
		{
			$post_title = $item->post_title;

			if ($post_title == '')
				$post_title = __('[Untitled]','maxinbound');

			echo "<div class='row item item-$item->id'>
				 		<span class='dashicons dashicons-no remove mi-ajax-action' data-action='remove-single-email' data-param='" . $item->id . "'>&nbsp;</span>
						<span class='remove-spinner'>  </span>
				  <span>" . $post_title . "</span>";

			echo "<span>" . $item->email  . "</span>
				  <span>" . date_i18n( get_option( 'date_format' ), strtotime($item->date) ) . " - " . date_i18n( get_option( 'time_format' ), strtotime($item->date)) . " </span>
				  </div>";
		}
		?>

	</div>
<?php endif; ?>



<?php
	MI()->modules()->footer();
	} // show

} // class
