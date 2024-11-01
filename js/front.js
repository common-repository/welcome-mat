
var miFront = function miFront(jquery)
{
	$ = jquery;
	// defaults. For some reason, prototype don't pick those up
	this.close_event = this.form_close;
	this.signup_event = this.form_close;
	this.redirection = {};
	this.signup_delay = 2000;
}

miFront.prototype = {
	currentForm: null,
	nonce: '',
	post_id : 0,
	ajaxurl : '',
	ajaxaction: '',
	close_event: this.form_close,
	signup_event: this.form_close,
	redirection: null,
	signup_delay: 2000,
}

miFront.prototype.init = function()
{

	this.ajaxurl = mibfront.ajaxurl;
	this.ajaxaction = mibfront.ajaxaction;
	this.sysslug = mibfront.plugin_slug;

	this.currentForm = $('.mib form');

	// No form loaded
	if (this.currentForm.length == 0)
	{
		return;
	}

		if( this.currentForm.is(':visible') )
		this.form_visible();
	else
		$('.mib form').on('visible', $.proxy(this.form_visible, this ));

	this.hook_events();

}

miFront.prototype.hook_events = function()
{
	this.currentForm = $('.mib form');

	this.currentForm.validate({
		debug: true,
		invalidHandler: $.proxy(this.form_errors, this),
		submitHandler:  $.proxy(function (f, e) { e.preventDefault(); this.form_send(f, e); return false }, this),
		errorClass: 'mib-error',
	});

	try {
		this.redirection = eval(this.sysslug + '_redirect');
	}
	catch (error)
	{		// var not there, do nothing.
	}

	var redirkeys = Object.keys(this.redirection);
	if (redirkeys.length > 0)
	{

		if( redirkeys.indexOf('redirect') >= 0)
		{
				this.signup_event = this.form_redirection;
		}
		if (redirkeys.indexOf('delay') >= 0)
		{
				this.signup_delay = this.redirection.delay;
		}
	}

	$('.mib [data-action="close"]').on('click', $.proxy(this.close_event , this) );

}

miFront.prototype.send_ajax_cookie = function()
{
		this.nonce = $('input[name="nonce"]').val();
		this.post_id = $('input[name="post_id"]').val();

		var data = {
			'action': this.ajaxaction,
			'plugin_action': 'visit-cookie',
			'nonce': this.nonce,
			'post_id': this.post_id,
		}
		var url = this.ajaxurl;

		$.ajax({
			url: url,
			type: 'POST',
			dataType: "JSON",
			data: data,
			success: function()  {
				$('body').trigger('mi_form_init');
			},
			error: function(data){
				console.log('error setting cookie!');
				console.log(data.error());
			  }

		});

}


miFront.prototype.form_errors = function(e, v)
{
	var errors = v.numberOfInvalids();
		if (errors) {
			var invalidElements = v.invalidElements();
			$('#subscription').removeClass('shake animated').addClass('shake animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
				$(this).removeClass();
			});
		}
    return false;
}

miFront.prototype.form_send = function(form, event)
{
		event.preventDefault();

		// collect all form items
		$('body').trigger('mi_form_send');
		var elements = $(form).find(':input');
		var data = {};

		elements.each(function(index, item)
		{
				data[$(this).attr('name')] = $(this).val();
		});


		data['plugin_action'] = 'post-form';

		$.ajax({
			url: this.ajaxurl,
			type: 'POST',
			dataType: "JSON",
			data: data,
			success: $.proxy(this.form_thanks, this),
		});

		// be done with it
		return false;
}

miFront.prototype.form_visible = function ()
{
	$('body').addClass('template-active');
	this.send_ajax_cookie();

}

miFront.prototype.form_thanks = function ()
{
		$('.mib form').children().filter(":not(.after-submit,[class^=background], link, style)").remove();
		$('.mib .after-submit').removeClass('hidden');
		window.setTimeout($.proxy(this.signup_event,this), this.signup_delay);
}

miFront.prototype.form_redirection = function ()
{
	 var url = this.redirection.redirect;
	 this.form_close();
	 window.location.href = url;

}

miFront.prototype.form_close= function()
{
		$('body').trigger('mi_form_close');
		$('.mib').fadeOut(300).remove();
		$('body').removeClass('mi-template-body').removeClass('template-active');

		$('link[id^=' + this.sysslug + ']').remove();
}


jQuery(document).ready(function($) {

// inits
maxInboundJS = new miFront($);
maxInboundJS.init();


if (typeof window.maxFoundry === 'undefined')
	window.maxFoundry = {} ;

window.maxFoundry.maxInbound = maxInboundJS;


}); /* END OF JQUERY */
