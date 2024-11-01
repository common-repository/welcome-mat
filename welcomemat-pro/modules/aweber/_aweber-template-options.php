<?php
namespace MaxInbound;

?>

<ul class='option-row'>	
<?php	

		$activate = MI()->editors()->getNewfield('aweber_active', 'checkbox'); 
		$activate->set('icon', 'dashicons-yes'); 
	//	$activate->set('inputclass','check_button');
		$activate->set('inputvalue','1'); 
		$activate->set('inputclass','mi-ajax-action-change');
		$activate->set('label', __('Activate Aweber','maxinbound') ); 
		$activate->set('value', $active);
		$activate->setTemplate('switch.tpl', 'core'); 
		
		// hacky
		$input = $activate->admin(); 
		$pos = strpos( $input, 'type=');
		$input = substr_replace($input, ' data-action="save-options-aweber" ', $pos, 0);
		echo $input;
		
?>
</ul>
<?php if ($active == 1): ?>
<ul class='option-row' class='mi-ajax-action-change' data-action='save-options-aweber' > 
		<li><label><span><?php _e('Pick a list','maxinbound'); ?></span>
		<select name='aweber_list_id' class='mi-ajax-action-change' data-action='save-options-aweber'>
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
