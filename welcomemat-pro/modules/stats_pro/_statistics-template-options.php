<?php
namespace MaxInbound;

	if (! $this->api_key): ?>
		<p class='error'>
			<?php _e('Google API Key not set. Please do so in the settings','maxinbound') ?>
		</p> 

<?php endif; ?>		

<ul class='option-row'>
<?php
		$activate = MI()->editors()->getNewfield('ga_active', 'checkbox'); 
		$activate->set('icon', 'dashicons-yes'); 
	//	$activate->set('inputclass','check_button');
		$activate->set('inputvalue','1'); 
		$activate->set('label', __('Activate Google Analytics','maxinbound') ); 
		$activate->set('value', $active);
		$activate->setTemplate('switch.tpl', 'core'); 
		
?>
</ul>
