/** Do Ajax Load in case of issues and plugins **/ 


var miFrontAjax = function (jquery) 
{
	$ = jquery; 
}

miFrontAjax.prototype.init = function() 
{
	var ajaxurl = mibfront.ajaxurl;
	var ajaxaction = mibfront.ajaxaction;

		var data = { 
			'action': ajaxaction,
			'plugin_action': 'check_load_page',  
		}
		
		var self = this; 
		
		$.ajax({
			url: ajaxurl, 
			type: 'POST',
			dataType: "JSON",
			data: data,
			success: function(result)  { 
				self.renderAjaxPage(result);
			},
			error: function(data){
				console.log('error getting ajax data!');
				console.log(data.error());
			  }
      			
		});
			
}

miFrontAjax.prototype.renderAjaxPage = function (result) 
{
	
	if (result.status === false) 
		return; 
		
	
	var bodyClass = result.body_class; 
	
	$('body').addClass(bodyClass); 
	$('body').append(result.page_output); 
	

	window.maxFoundry.maxInbound.hook_events();
	window.maxFoundry.maxInbound.send_ajax_cookie(); 

	$(document).trigger('mib-ajax-hasloaded'); 	

}

jQuery(document).ready(function($) {	

// inits  
frontAjax = new miFrontAjax($); 
frontAjax.init(); 


if (typeof window.maxFoundry === 'undefined') 
	window.maxFoundry = {} ; 
	
window.maxFoundry.miFrontAjax = frontAjax;

 
}); /* END OF JQUERY */

