

var maxInbound = function (jquery)
{
 	$ = jquery;
 	uploader = null;
 	fields = {};
	form_updates = false;
	submit_in_progress = false;

	liveEditors = null;
	liveData = null;

	eventFlood = false;

	colorpicker_current = null;
}

maxInbound.prototype = {

}; // MaxInbound

maxInbound.prototype.init = function () {

	// this seems unused ( and crashes the plugin if used )
//	this.fields= $.parseJSON($('#maxinbound_fields').text());

	// bind events

	/*	not doing this for now.
		$(document).on('click','.field', $.proxy(this.edit_field, this));  */

	$(document).on('click','#editorwindow-save', $.proxy( this.update_fields, this));

	 // prevent live anchors from firing external URL's
	$('.live-preview a').on('click', function (e) { if ($(e.target).hasClass("field")) return true; e.preventDefault(); return false; });

// init colorpicker
	$('.color-field').wpColorPicker(

	);

	// bind editors to their fields on event
	$(document).on('keyup', '.field.richtext textarea', $.proxy(this.update_text, this));
	$(document).on('keyup', '.field.text input[type=text]', $.proxy(this.update_text, this));
	$(document).on('keyup change', '.field.font input, .field.font select', $.proxy(this.update_font,this));
	$(document).on('click', '.mi-colorpicker-box span.remove', $.proxy(this.set_transparent, this));

	// Template Picker events
	$(document).on('click', '.template_picker .item', $.proxy(this.select_template, this));


	// panel for extra fields
	$(document).on('click', '.secondary-icon', $.proxy(this.openPanel, this));
	$(document).on('click', '.mi_secondary .close', $.proxy(this.closePanel,this));

	//$(document).on('click', '.mi-ajax-action', $.proxy(this.ajaxCall, this ));
	//$(document).on('change', '.mi-ajax-action-change', $.proxy(this.ajaxCall, this));

	// preview button
	$(document).on('click', '.preview_button',  function (e) { e.preventDefault(); $('#post-preview').trigger('click'); });

	// save check
	if ($('#poststuff').length > 0)
	{
		$(window).on('beforeunload', $.proxy(function (e) {  if (this.form_updated && ! this.submit_in_progress) { return postL10n.saveAlert; } }, this));
		$(document).on('submit', 'form[name="post"]', $.proxy(function(e) {  this.submit_in_progress = true; },this ));
		$(document).on('change keyup','#maxinbound input, #maxinbound select', $.proxy(function(e) {
			 this.form_updated = true; }, this)
		);
	}

	/*	Use this template function. Will create new post, with selected template */

	/* On Form save, keep the opened tab active */
	$(document).on('maxTabChange', $.proxy(this.tabChange, this) );

};


maxInbound.prototype.tabChange = function (e, tab)
{
	var form_url = $('form').attr('action');
	if (typeof form_url == 'undefined')
		return;

	new_url = form_url.replace(/#[a-z]*[A-Z]*/i, '');
	$('form').attr('action', new_url + '#' + tab);
}

/* Get the standard AJAX vars for this plugin */
maxInbound.prototype.ajaxInit = function()
{
	data = {
		action: mib.ajax_action,
		nonce:  mib.nonce,
	}

	return data;
}

/* Ajax call functionality for modules etc. */
/*maxInbound.prototype.ajaxCall = function (e)
{

	e.preventDefault();
	var target = e.target;

	var param = false;
	var plugin_action = $(target).data('action');
	var check_param = $(target).data('param');
	var param_input = $(target).data('param-input');

	if (typeof check_param !== 'undefined')
		param = check_param;
	if (typeof param_input !== 'undefined')
		param = $(param_input).val();

	data = this.ajaxInit();

	data['plugin_action'] = plugin_action;
	data['param'] = param;
	data['post'] = $('form').serialize(); // send it all

	this.showSpinner(target);

	this.ajaxPost(data);
}

maxInbound.prototype.showSpinner = function(target)
{
	var spinner = '<div class="ajax-load-spinner"></div>';
	$('.ajax-load-spinner').remove();
	$(target).after(spinner);
	//return spinner;
}

maxInbound.prototype.ajaxPost = function(data, successHandler, errorHandler)
{

	if (typeof successHandler == 'undefined')
	{

		var action = data['plugin_action'];

		var successHandler = function(data)
		{

			$(document).trigger('mib_ajax_success',[ action, data ]);
			$(document).trigger('mib_ajax_success_' + action, data);
		};
	}

	if (typeof errorHandler == 'undefined')
	{
		var errorHandler = function (jq,status,error)
		{
				$(document).trigger('mib_ajax_error_' + action, jq, status, error);
				console.log(jq);
				console.log(status);
				console.log(error);
		};
	}


	$.ajax({
		type: "POST",
		url: mib.ajax,
		data: data,
		success: successHandler,
		error: errorHandler,
		});


} */

maxInbound.prototype.update_fields = function (ev) {

	ev.stopPropagation();
	ev.preventDefault();

 	var value;

 	var editor = [];
 	var field = [];
 	var values = [];


 	for ( var i in this.liveEditors)
 	{
 		var current_editor = this.liveEditors[i];

 		editor.push(this.liveEditors[i]);
 		field.push(this.liveData[current_editor].field);


 		if (this.liveEditors[i] == 'richtext')
 		{
 			values.push(tinymce.activeEditor.getContent());

 		}
 		else
 		{
 			values.push($('#editorcontent input[id="' + field + '"]').val());
 		}

 		$('input[id="' + field + '"]').val(value);

 		// Ajax callback to populate field
 		var domtype = '';
 		var cb_field = this.liveData[current_editor].cb_field;

 		if (typeof this.liveData[current_editor].cb_field !== 'undefined')
 		{
 			domtype = this.liveData[current_editor].cb_domtype;
 		}

 	}

 		var dom = $('.field-' + cb_field)[0].outerHTML; //JSON.stringify($('.field-' + field));
 		  $(dom).serialize();

 		data = { field: cb_field,
 				 domtype: domtype,
 				 domObj: dom,
 				 value: values,
 				 editor: editor,
 				 action: 'populate_field'
 				};

 		$.post(ajaxurl, data, $.proxy(this.populate_preview, this, cb_field) );
};

maxInbound.prototype.update_font = function(event)
{

	var etarget = event.target;
	var field = $(etarget).parent('.field').data('field');
 	var value = $(etarget).val();
	var target = $(etarget).data('target');

	if (target == 'font-size')
		value = value + 'px';

	$('.field-' + field).css(target, value);


};

 // var custom_uploader;
//maxInbound
maxInbound.prototype.set_transparent = function(e)
{
	var target = $(e.target).parent().attr('id');
	$('input[name="' + target + '"]').val('none');

	$('#' + target + ' span.color').removeAttr('style');
}


maxInbound.prototype.update_text = function (event)
{

	var etarget = event.target;
	var value = $(etarget).val();
	var id = $(etarget).attr('id');

	var defs = id.split("_");
	var target = $(etarget).data('target');

	switch(target)
	{
		case 'css':
			var unit = $(etarget).data('unit');
			if (typeof unit !== 'undefined')
				value = value + unit;

			$('.field-' + defs[0]).css(defs[1], value);

		break;
		default:
			$('.field-' + defs[0]).text(value);
		break;
	}

	$('.field-' + id).text(value);
}


maxInbound.prototype.select_template = function (e)
{
	var target = $(e.target);
	if (typeof $(target).data('template') === 'undefined')
	{
		target = $(e.target).parents('.item');
	}

	var template = $(target).data('template');

	$('.template_picker .item').removeClass('selected');
	$('.template_picker .item .usebar').remove();
	$(target).addClass('selected');

  var url = mib.use_template_url;
	var link = url + '&action=use-template&template=' + template
	$(target).append("<span class='usebar'><a href='" + link + "'><span class='use'>Use <br> Template</span><span class='use_arrow dashicons dashicons-yes'></span></a></span>");
}

maxInbound.prototype.openPanel = function (e)
{
	var target = $(e.target);
	var editor_id = $(target).parents('[data-editor]').data('editor');
	$('#panel-' + editor_id).show();

}

maxInbound.prototype.closePanel = function (e)
{
	$(e.target).parents('.mi_secondary').hide();

}

jQuery(document).ready(function($) {

// inits

if (typeof window.maxFoundry === 'undefined')
	window.maxFoundry = {} ;

window.maxFoundry.maxInbound = new maxInbound($);

window.maxFoundry.maxInbound.init();

// init module space
window.maxFoundry.maxInbound.modules = {};

}); /* END OF JQUERY */
