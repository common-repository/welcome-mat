
var displayPro = function() 
{
	
}

displayPro.prototype = {
	 busy: false,
	 limit: 10, // internal linking doesn't implement limit. :/
};

displayPro.prototype.init = function () 
{
	// display
	$(document).on('change', 'input[name="display_noautoshow"]', $.proxy(this.checkAutoShow, this) );

	// search
	$(document).on('change keyup','input[name="search_post_text"]', $.proxy(_.debounce(this.doPostSearch, 500), this ) ); 

	$(document).on('click', '.select_posts li label', $.proxy(this.selectPost, this) ); 
	$(document).on('click', '.selected_posts li label', $.proxy(this.deselectPost, this) ); 
	this.checkAutoShow();
}

displayPro.prototype.checkAutoShow = function() 
{
	if ( $('input[name="display_noautoshow"]').attr('checked')  )
	{
		$('.search_post_text').show();
		$('.custom_searchpost').show(); 
		$('.custom_selectedpost').show();
		this.doPostSearch();
	}
	else
	{
		$('.search_post_text').hide();
		$('.custom_searchpost').hide(); 
		$('.custom_selectedpost').hide();
	}
}

displayPro.prototype.doPostSearch = function () 
{
	if (this.busy) 
		return; 
		
	this.busy = true;
	var action = 'wp-link-ajax'; 
	var url = mib.ajax; 
	
	var search = $('input[name="search_post_text"]').val();
	
	var data = {
		action: action, 
		_ajax_linking_nonce: $('input[name="_ajax_linking_nonce"]').val(),
	}; 
	
	if (search.length > 0)
		data['search'] = search;
	
	$.post({
		url: url,
		data: data,
		success: $.proxy(this.postSearchResult, this),
	});

}

displayPro.prototype.postSearchResult = function(data) 
{
	var data = $.parseJSON(data);
	$('.select_posts li').remove();
	
	var i = 0; 
	var self = this; 
	
	$.each(data, function (index, el) 
	{
		if (i >= self.limit) 
			return false;

		var id = el.ID; 
		var title = el.title;
		if (title == '') 
			title = miDisplayPro.no_title; 
			
		var info = el.info;
		var newel = '<li><label><i class="icon"></i><input type="checkbox" name="display_post_select[]" value="' + id + '">' + title + 
					'<span class="detail">' + info + '</span></label></li>'; 
		$('.select_posts').append(newel);

		i++;	
	}); 
	this.busy = false;
}

displayPro.prototype.selectPost = function(e) 
{
	e.preventDefault(); 
	var target = e.target; 
	
	var li = $(target).parents('li').first();
	li.appendTo('.selected_posts');
	li.find('input[type="checkbox"]').prop('checked', true); 
	li.find('i').addClass('dashicons dashicons-dismiss');	

	this.checkSelection();
	 
}

displayPro.prototype.checkSelection = function () 
{
	if ( $('.selected_posts li').length <= 1)
		$('.selected_posts .no-selection').show(); 
	else
		$('.selected_posts .no-selection').hide(); 


		
}

displayPro.prototype.deselectPost = function (e)
{
	e.preventDefault(); 
	var target = e.target; 
	$(target).parents('li').first().remove(); 
	this.checkSelection();
	this.doPostSearch(); 
	
}


jQuery(document).ready(function($) {	

window.maxFoundry.maxInbound.modules.displayPro = new displayPro(); 
window.maxFoundry.maxInbound.modules.displayPro.init(); 

});
