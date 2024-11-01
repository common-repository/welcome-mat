<?php
namespace MaxInbound;
?>


<ul class='option-row'>	
<?php	

		$activate = MI()->editors()->getNewfield('mailchimp_active', 'checkbox'); 
		$activate->set('id', 'mailchimp_active'); 
		$activate->set('icon', 'dashicons-yes'); 
		$activate->set('inputclass','mi-ajax-action-change');
		$activate->set('inputvalue','1'); 
		$activate->set('label', __('Activate MailChimp','maxinbound') ); 
		$activate->set('value', $active);
		$activate->setTemplate('switch.tpl', 'core'); 
		
		$input =  $activate->admin(); 
		$pos = strpos( $input, 'type=');
		$input = substr_replace($input, ' data-action="save-options-mailchimp" ', $pos, 0);
		echo $input;
		//$newstr = substr_replace($oldstr, $str_to_insert, $pos, 0);
?>
</ul>
<?php if ($active == 1): ?> 
<ul class='option-row'>

		<li><label><span><?php _e('Pick a list','maxinbound'); ?></span>
			<select name='mailchimp_list_id' class='mi-ajax-action-change' data-action='save-options-mailchimp' >
				<option value='' <?php checked('', $list_id); ?> ><?php _e('Choose a list', 'maxinbound') ?></option>
		
	<?php
			if (isset($list['lists'])) 
			{
				foreach($list['lists'] as $list_item) 
				{
					$id = $list_item['id'];
					$name = $list_item['name'];
					$selected = ($id == $list_id) ? 'selected' : ''; 
				
					echo "<option value='$id' $selected> " . $list_item['name'] . '</option>'; 
			
				}
			}
	?>
			</select>
		</label>
		
		</li> 
	
</ul>
<?php endif; ?> 
