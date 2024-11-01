<?php namespace MaxInbound; ?> 

<ul class='option-row'>
		<?php
			$activate = MI()->editors()->getNewfield('ga_active', 'checkbox'); 
			$activate->set('inputvalue','1'); 
			$activate->set('label', __('Activate','maxinbound') ); 
			$activate->set('value', $ga_active);
			$activate->setTemplate('switch.tpl', 'core'); 
			echo $activate->admin();
?>
</ul>
<ul class='option-row'>	
<?php	
			$load_ga = MI()->editors()->getNewfield('ga_loadcode' ,'checkbox'); 
			$load_ga->set('inputvalue','1'); 
			$load_ga->set('label', __('Setup Google Analytics script', 'maxinbound') );
			$load_ga->setTemplate('switch.tpl', 'core'); 
			$load_ga->set('value', $ga_loadcode);
			echo $load_ga->admin(); 
		
		?>
		<li class='note'><?php _e('If you are already loading Google Analytics in another way it\'s not needed to activate','maxinbound'); ?></li>
</ul>
	
<ul class='option-row'> 
	<li>
		<label>
			<span><?php _e('Google Analytics ID','maxinbound'); ?></span>
			<input type="text" name="ga_code" value="<?php echo $ga_code ?>" placeholder="UA-XXXXXXXX-X" /> 
		</label>
	</li>
</ul>
