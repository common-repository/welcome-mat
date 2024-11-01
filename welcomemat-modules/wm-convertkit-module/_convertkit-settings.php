<ul class='option-row'>
	<li>
		<label><span><?php _e('Convertkit API Key', 'wmconvert'); ?> </span>
			<input type='text' name='convertkit_api_key' value='<?php echo $this->api_key ?>'> </p>
			
			<?php if (!$this->verified): ?> 
	<input type='button' data-action='verify_convertkit' class='button mi-ajax-action' name='convertkit_verify' 
				value="<?php _e('Verify account','wmconvert'); ?>" data-param-input='input[name="convertkit_api_key"]' data-loader='.section.convertkit'>
		</label>
			<?php endif; ?>
	</li>
</ul>

