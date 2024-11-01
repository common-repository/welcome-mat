<?php namespace MaxInbound; 
defined('ABSPATH') or die('No direct access permitted');

?>

<ul class='option-row'>	
	<p class='warning error'><?php _e('Warning! Will delete ALL plugin data. This can\'t be undone. Check this *only* if you are absolutely sure','maxinbound'); ?></p>
		<?php
			$remove = MI()->editors()->getNewfield('plugin_remove_data', 'checkbox'); 
			$remove->set('inputvalue',1); 
			$remove->set('label', __('On plugin remove, delete ALL data','maxinbound') ); 
			$remove->set('value', $do_remove);
			$remove->setTemplate('switch.tpl', 'core'); 
			echo $remove->admin();
?>
</ul>
