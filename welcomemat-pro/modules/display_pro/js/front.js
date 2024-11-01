jQuery(document).ready(function($) {	

	

		mib_checkDelay($); 
		$(document).on('mib-ajax-hasloaded', function () {  mib_checkDelay($) } ); 

});

function mib_checkDelay($) {

	if ($('.template-delay').length > 0) 
	{
		
		var delay = parseInt( $('[data-delay]').data('delay') );
		if (! isNaN(delay)) 
		{
			var ms = delay * 1000;
			setTimeout( function () { 
			$('.template-delay').removeClass('template-delay');
			$('.mib form').trigger('visible');		
		
			}, ms);
		}	
	}
}

