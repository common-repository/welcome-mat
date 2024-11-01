
<ul class='option-row authcode_option <?php if ($verified) echo 'hidden' ?>'> 
	<li>
		<label><span><?php _e('Authorization Code','maxinbound'); ?></span>
			<textarea name='aweber_authcode' 
			placeholder="<?php _e('Copy and Paste your Authorization code here','maxinbound'); ?>"></textarea>
		</label>
	</li>
</ul>
<ul class='option-row  authcode_option <?php if ($verified) echo 'hidden' ?>'>
	<li>
	 <?php printf(__('You can get the Authorization code by %s clicking here %s','maxinbound'), '<a href="' .$auth_url .'" target="_blank">', 
					'</a>'); 
		?> 
	</li>
</ul>



 

