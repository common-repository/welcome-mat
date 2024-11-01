jQuery(document).ready(function($) {


var miGeneral = function()
{

}

miGeneral.prototype = {

}

miGeneral.prototype.init = function()
{

	/* Submit via AJAX due to module ordering issues on regular saves. Keep it as fallback though */
	$(document).on('submit','#settingsform', $.proxy(this.saveSettings, this ) );
	$(document).on('maxajax_success_flushtransients', $.proxy(this.successHandlerEvent, this ) );
	$(document).on('maxajax_success_activate_license', $.proxy(this.successHandlerEvent, this ) );
	$(document).on('maxajax_success_deactivate_license', $.proxy(this.successHandlerEvent, this ) );


	// add custom post status to post edit screen
	if ($('form#post').length > 0)
	{
		// check if we are in our post edit screen
		var post_type = $('input[name="post_type"]').val();
		if ( mimodulegeneral.post_type == post_type)
		{
			this.addArchiveStatus();
		}

		$(document).on('click','.save-post-status',$.proxy(this.checkArchivedStatus, this) );
	}
	// check for post overview post of current post type
	else if (typeof $('input[name="post_type_page"]') !== 'undefined' && $('input[name="post_type_page"]').val() == post_type )
	{
	   $('select[name="_status"]' ).append( '<option value="archive">' + mimodulegeneral.labels.status_label_dropdown + '</option>' );

	}
}


miGeneral.prototype.saveSettings = function (e)
{
	e.preventDefault();
	var form = $('#settingsform').serialize();

	var ajaxObj = new window.maxFoundry.wm.maxAjax;

	var data = ajaxObj.ajaxInit();
	data['plugin_action'] = 'save-settings';
	data['formdata'] = form;

	ajaxObj.ajaxPost(data, $.proxy(this.successHandler, this) );

}

/* If the success is an event ( via the ajaxCall implementation in the main class). */
miGeneral.prototype.successHandlerEvent = function (e, data)
{
	e.preventDefault();
	this.successHandler(data);
}

miGeneral.prototype.successHandler = function(data)
{

	if (typeof data !== 'undefined' && data !== '')
	{

		response = JSON.parse(data);
		if (typeof response.dialog !== 'undefined')
		{
			var dialog = response.dialog;

			var modal = new maxModal();
			modal.init();

			modal.newModal('modal');
			modal.setTitle(dialog.title);
			modal.setContent(dialog.content);

			if (dialog.type == 'ok')
			{
				modal.addControl('ok', '', eval(dialog.action));
			}
			modal.setControls();
			modal.show();
		}
		else if (typeof response.reload !== 'undefined' && response.reload)
		{
			if (response.reload_url == 'self')
			{
				window.location.reload(true);
			}
			else
			{
				var href = decodeURIComponent(response.reload_url);
				window.location.href = href;
			}
		}
		else if (typeof response.partial_refresh !== 'undefined' && response.partial_refresh)
		{

			var data = response.partial_data.content;
			var target = response.partial_target;
			$(target).replaceWith(data);

		}


	}
	return false;

}

// Add the archived status to the post editor
miGeneral.prototype.addArchiveStatus = function ()
{
	var status_label_dropdown = mimodulegeneral.labels.status_label_dropdown;
	var status_label = mimodulegeneral.labels.status_label;

	 $("select#post_status").append('<option value="archive">' + status_label_dropdown + '</option>');

	 this.checkArchivedStatus(false);
}


miGeneral.prototype.checkArchivedStatus = function(e)
{
	var post_status = $('input[name="original_post_status"]').val();

	// ! e -> only check this on load of the screen, not when user changes status.
	 if (post_status == 'archive' && ! e)
	 {
	 	$('#post-status-display').text(mimodulegeneral.labels.status_label);
	 	$('select#post_status').val('archive');
	 }

	 if ( $('select#post_status').val() == 'archive')
	 {
	 	$('#save-post').val(mimodulegeneral.labels.minor_button);
	 }

}

window.maxFoundry.maxInbound.modules.general = new miGeneral();
window.maxFoundry.maxInbound.modules.general.init();

});
