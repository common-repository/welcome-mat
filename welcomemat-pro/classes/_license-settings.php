<?php namespace MaxInbound; ?> 


<ul class='option-row'>
	<li>
		<label><span><?php _e('License Key','maxinbound'); ?></span>
			<input type='text' name='license_key' value='<?php echo $this->license_key ?>' <?php echo $disabled ?>>
		</label>
		<?php if ($this->is_valid() ) : ?>
		<button type='button' class='button mi-ajax-action' name='deactivate_license' 
			data-action='deactivate_license' data-param="<?php echo $this->license_key ?>" data-loader='.section.license' >
			<?php _e('Deactivate License', 'maxinbound'); ?>
		</button>
		<?php else: ?>

		<button type='button' class='button-primary mi-ajax-action ' name='activate_license' 
			data-action='activate_license' data-param-input='input[name="license_key"]' data-loader='.section.license' >
			<?php _e('Activate License', 'maxinbound'); ?>
		</button>
			
		<?php endif; ?> 
	</li>
</ul>


