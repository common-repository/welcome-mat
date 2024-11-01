
var wmpro = function(jquery)
{

}

wmpro.prototype = {
	maxInbound : null,
}

wmpro.prototype.init = function () {

	// init for images
	this.maxInbound = window.maxFoundry.maxInbound;
	var self = this;

	$(document).on('click','.media-upload', $.proxy( this.image_uploader, this) );
	$(document).on('click','.media-remove', {action:'remove'},  $.proxy( this.change_image, this ) );
	$(document).on('click', '.media-default', {action: 'default'},  $.proxy ( this.change_image, this) );


	// ajax success goes by default to general success handler
	/*$(document).on('maxajax_success', function (e, action, data) {
		// hardening
		if (typeof action === 'undefined')
		{
			console.log('mib ajax success error - action is undefined ');
			return;
		}

		if ( action.indexOf('save-options-') === false)
			return;

		 e.preventDefault();
		 self.maxInbound.modules.general.successHandler(data);
	} ); */

};

wmpro.prototype.image_uploader = function(e)
{
        e.preventDefault();
        var target = $(e.target);
        var field = $(target).data('field');
 		var value = $('input[name="' + field + '"]').val();
 		var self = this;

        //If the uploader object has already been created, reopen the dialog
        if (this.uploader) {
            this.uploader.open();
            return;
        }

 		title = 'Choose Image';

        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: title,
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });

        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', $.proxy( function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();

 			 $('input[name="' + field + '"]').val(attachment.id);
 			 $('#' + field).find('img').attr('src', attachment.url);

			 this.checkImage(field);

        }, this) );

 		this.uploader = custom_uploader;
        //Open the uploader dialog
        custom_uploader.open();
}

wmpro.prototype.change_image = function(e)
{
	if (! e.data.action)
		return false;

	var field = $(e.target).data('field');
	var $field = $('#' + field); // the actual field

	var action = e.data.action;

	if (action == 'default')
	{
		var def_img = $(e.target).data('default');
		$field.find('img').attr('src', def_img);
 		$('input[name="' + field + '"]').val(def_img);

	}

	if (action == 'remove')
	{
		$field.find('img').attr('src', '');
 		$('input[name="' + field + '"]').val('none');
	}

	this.checkImage(field);
}

wmpro.prototype.checkImage = function(field)
{
	var $field = $('#' + field);

	if ( $field.find('img').attr('src') !== '')
	{
		$field.find('.placeholder').addClass('has-image');
	}
	else
		$field.find('.placeholder').removeClass('has-image');

}

jQuery(document).ready(function($) {

	if (typeof window.maxFoundry === 'undefined')
		window.maxFoundry = {} ;

	window.maxFoundry.wmpro = new wmpro($);

	window.maxFoundry.wmpro.init();


});
