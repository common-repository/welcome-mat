var aweber = function() 
{
	
}

aweber.prototype = {
 	ajax_url: mib.ajax,
 	
};

aweber.prototype.init = function() 
{
 	$('.do_update_authcode').on('click', $.proxy(this.showAuthCode,this) ); 

}

aweber.prototype.showAuthCode = function (e) 
{
	e.preventDefault(); 
	$('.authcode_option').removeClass('hidden');

}


jQuery(document).ready(function($) {	

window.maxFoundry.maxInbound.modules.aweber = new aweber(); 
window.maxFoundry.maxInbound.modules.aweber.init(); 

});
