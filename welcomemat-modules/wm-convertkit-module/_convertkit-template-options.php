<?php
namespace MaxInbound;
?>


<ul class='option-row'>	
<?php	

		$activate = MI()->editors()->getNewfield('convertkit_active', 'checkbox'); 
		$activate->set('id', 'convertkit_active'); 
		$activate->set('icon', 'dashicons-yes'); 
		$activate->set('inputclass','mi-ajax-action-change');
		$activate->set('inputvalue','1'); 
		$activate->set('label', __('Activate Convertkit','maxinbound') ); 
		$activate->set('value', $active);
		$activate->setTemplate('switch.tpl', 'core'); 
		
		$input =  $activate->admin(); 
		$pos = strpos( $input, 'type=');
		$input = substr_replace($input, ' data-action="save-options-convertkit" ', $pos, 0);
		echo $input;
		//$newstr = substr_replace($oldstr, $str_to_insert, $pos, 0);
?>
</ul>
<?php if ($active == 1): ?> 
<ul class='option-row'>

	<?php 		//echo "<PRE> ??? "; var_dump($forms); echo "</PRE>";  return; ?>
		<li><label><span><?php _e('Pick a list','wmconvert'); ?></span>
			<select name='convertkit_form_id' class='mi-ajax-action-change' data-action='save-options-convertkit' >
				<option value='' <?php checked('', $form_id); ?> ><?php _e('Choose a list', 'wmconvert') ?></option>
		
	<?php

		
			if (isset($forms->forms)) 
			{
				foreach($forms->forms as $form) 
				{
					$id = $form->id;
					$name = $form->name;
					$selected = ($id == $form_id ) ? 'selected' : ''; 
				
					echo "<option value='$id' $selected> " . $name . '</option>'; 
			
				}
			}
	?>
			</select>
		</label>
		
		</li> 
	
</ul>
<?php endif; ?> 
