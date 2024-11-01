<?php 
namespace MaxInbound;
defined('ABSPATH') or die('No direct access permitted');

$post_type = MI()->ask('system/post_type'); 
$action = '?post_type=' . $post_type . '&page=maxinbound-settings'; 

if (isset($_POST) && isset($_POST['nonce']))
{
	$post = stripslashes_deep( $_POST );
	$nonce = $_POST['nonce'];
	if (! wp_verify_nonce( $nonce, 'save-settings' ) )
	{
		exit('Invalid Nonce'); 
	}

	MI()->tell('settings/save-settings', $post); 
}

// request settings pages. 
$settings = array_filter(MI()->collect('system/settings-page'));
$tab_array = array(); 
$order_array = array();

// First run, and figure out all priority settings. Settings might not have priority set, but then this value should be found via other settings
$prio_array = array(); 

foreach($settings as $index => $setting) 
{
	if (isset($prio_array[$setting['page']]))
		continue; 
		
	if (isset($setting['priority'])) 
		$prio_array[$setting['page']] = $setting['priority'];
}


foreach($settings as $index => $setting) 
{
	$page = $setting['page']; 
	$priority = isset($prio_array[$page]) ? $prio_array[$page] : 10; 
	if (! isset($order_array[$priority])) 
		$order_array[$priority] = array(); 	
	
	$order_array[$priority][] = $page;
	
	if (isset($page))
	{
		if (! isset($tab_array[$setting['page']])) 
			$tab_array[$setting['page']] = ''; 

		$tab_array[ $setting['page'] ] .= $setting['content']; 
	}
	
	ksort($order_array);

}

?>
<div id="maxinbound" data-view='tabs'>
	<div class='wrap'>
		<h1 class='title'>
		
			<?php echo MI()->ask('system/plugin_title'); ?> :
			<?php _e("Settings","maxinbound"); ?>
		</h1>

		<div class="settings"> 
			<form method="post" action="<?php echo $action ?>" id='settingsform'>
				<?php wp_nonce_field('save-settings', 'nonce'); ?> 
				<?php wp_nonce_field($this->ask('system/ajax_action') , 'ajax_nonce'); ?>
			<?php
			$loaded = array(); 
			
			foreach($order_array as $prio => $tabs):
				foreach($tabs as $index => $tab_name):
					if (in_array($tab_name, $loaded ))  // pages can only load once. 
						continue;
					$loaded[] = $tab_name; 
					$output = $tab_array[$tab_name]; 
					$title = MI()->ask('settings/' . $tab_name . '/title'); 
					$icon = MI()->ask('settings/' . $tab_name . '/icon');
				?>
					<div class="mb_tab option-container <?php echo $tab_name ?> ">
							<div class="title">
								<span class="dashicons dashicons-<?php echo $icon ?>"></span> 
								<span class='title'><?php echo $title ?></span>
					 
							</div> 
							<div class="inside">	
								<?php echo $output ?>
							</div> <!-- inside -->
					</div> <!-- mb_tab -->
			<?php
				endforeach;
			endforeach;
			
			?>		
			<input type="submit" class='button-primary' name="save" value="<?php _e('Save All', 'maxinbound'); ?>"> 
			</form>
		</div> <!-- /settings -->
	</div> <!-- wrap -->
</div> <!-- mi -->
