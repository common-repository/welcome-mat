
jQuery(document).ready(function($) {	

	if(typeof ga === 'undefined') 
	{	console.log('Google Analytics is -not- loaded [WMPRO]'); 
		return;
	}
	
	var ga_submit_event = false; 
	
	if (typeof mibfront.template_nicename !== 'undefined') 
		template_nicename = mibfront.template_nicename;
	
	ga('send','pageview');

	$(document).on('mi_form_init', 'body', function () {
		ga('send', 'event', { eventCategory: 'Welcomemat', eventAction: 'Load', eventLabel: template_nicename});
	});

	$(document).on('mi_form_close', 'body', function () { 
		if (ga_submit_event)  // don't log close when an email was submitted. 
			return false; 
		ga('send', 'event', { eventCategory: 'Welcomemat', eventAction: 'Close', eventLabel: template_nicename});
	
	}); 
	
	$(document).on('mi_form_send', 'body', function () {
		ga_submit_event = true;
		ga('send', 'event', { eventCategory: 'Welcomemat', eventAction: 'Signup', eventLabel: template_nicename});
		
	});

	
}); 
