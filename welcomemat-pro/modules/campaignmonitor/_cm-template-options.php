<?php
namespace MaxInbound;

	if (! $this->api_key): ?>
		<ul class='error'>
			<li>
				<?php _e('Campaign Monitor API Key not set. Please do so in the settings','maxinbound') ?>
			</li>
		</ul> 
<?php elseif (! $client_id ) : ?> 
		<ul class='error'> 
			<li>
				<?php _e('No Client selected. Please do so in the settings', 'maxinbound'); ?> 
			</li>
		</ul>
<?php endif; ?>		

<ul class='option-row'>	
<?php		

		$activate = MI()->editors()->getNewfield('cm_active', 'checkbox'); 
		$activate->set('icon', 'dashicons-yes'); 
	//	$activate->set('inputclass','check_button');
		$activate->set('inputclass','mi-ajax-action-change');
		$activate->set('inputvalue','1'); 
		$activate->set('label', __('Activate Campaign Monitor','maxinbound') ); 
		$activate->set('value', $active);
		$activate->setTemplate('switch.tpl', 'core'); 
		
		$input = $activate->admin(); 
		$pos = strpos( $input, 'type=');
		$input = substr_replace($input, ' data-action="save-options-campaign_monitor" ', $pos, 0);
		echo $input;
?>
</ul>

<?php if ($active == 1): ?> 
<ul class='option-row'  >
<?php
?>
		<li>
			<label><span><?php _e('Pick a list','maxinbound'); ?></span>
		<select name='cm_list_id' class='mi-ajax-action-change' data-action='save-options-campaign_monitor' >
			<option value=''   <?php checked('', $list_id) ?> ><?php _e('Choose a list', 'maxinbound') ?></option>
<?php
		if (isset($lists) && is_array($lists) ) 
		{
			foreach($lists as $id => $name) 
			{
				$selected = ($id == $list_id) ? 'selected' : ''; 
				echo "<option value='$id' $selected> " . $name . '</option>'; 
			}
		}
?>
		</select>
			</label>
		</li>
</ul>
<?php endif; ?> 
