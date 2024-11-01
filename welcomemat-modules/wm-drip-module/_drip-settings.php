
<ul class='option-row'>
	<li>
		<label><span><?php _e('Drip API Key', 'wmdrip'); ?> </span>
			<input type='text' name='drip_api_key' value='<?php echo $this->api_key ?>'> </p>
			
			<?php if (!$this->verified): ?> 
	<input type='button' data-action='verify_drip' class='button mi-ajax-action' name='drip_verify' 
				value="<?php _e('Verify account','wmpro'); ?>" data-param-input='input[name="drip_api_key"]' data-loader='.section.drip'>
		</label>
			<?php endif; ?>
	</li>

</ul>


<?php if ($this->verified && $accounts > 0): ?>
<ul class='option-row'>
	<li><label><span><?php _e('Drip Account', 'wmdrip'); ?></span>
		<select name="drip_account_id">
			<?php foreach ($accounts as $account): ?> 
				<option value='' ><?php _e('Select Account', 'mbdrip'); ?></option>
				<option value="<?php echo $account['id'] ?>" <?php selected($this->account_id, $account['id']) ?> >
					<?php echo $account['name'] ?></option>
			<?php endforeach; ?>
		</select>
		</label> 
	</li>
</ul>	
	
	
	
<?php endif; ?> 	

