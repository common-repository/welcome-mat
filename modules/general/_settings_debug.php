<?php namespace MaxInbound; 
defined('ABSPATH') or die('No direct access permitted');

?>

<ul class='option-row'>	
		<?php
			$log = MI()->editors()->getNewfield('log_active', 'checkbox'); 
			$log->set('inputvalue',1); 
			$log->set('label', __('Log Events for Debugging','maxinbound') ); 
			$log->set('value', $log_active);
			$log->setTemplate('switch.tpl', 'core'); 
			echo $log->admin();
		?>
</ul>
<ul class='option-row'>
		<li>
			<h3><?php _e('Latest log entries','maxinbound'); ?> </h3>
		<?php
		if (isset($log_array)) 
		{
			foreach($log_array as $line) 
			{
				echo  "<div class='log-entry'>" . $line . "</div>"; 
			}
		
		}
?>
		</li>
</ul>

