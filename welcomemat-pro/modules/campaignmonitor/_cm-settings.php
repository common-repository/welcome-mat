
<ul class='option-row'>
	<li>
	<label><span><?php _e('Campaign Monitor API Key','maxinbound'); ?></span>
		<input type="text" name="cm_api_key" value="<?php echo $cm_api_key ?>"> 
	</label>
	</li>
</ul>

<?php

if (is_array($clients)) 
{
	$selected_client = isset($settings['client_id']) ? $settings['client_id'] : false;

?>
	<ul class='option-row'>
		<li><label><span><?php _e('Select a Client','maxinbound'); ?></span>
		
	<select name="cm_client_id">
	<option value='0' disabled><?php _e('Select a Client','maxinbound'); ?></option>
	
	<?php
	foreach($clients as $id => $name) 
	{
		
		?>
		<option value="<?php echo $id ?>" <?php echo selected($selected_client, $id); ?> > <?php echo $name ?> </option>	
		<?php
	}	
	?>
	</select>
		</label></li>
	</ul>
	<?php
}
?>
