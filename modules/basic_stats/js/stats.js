
var miStats = function ()
{}

miStats.prototype = {
	data: null,
	columns: null,
	chart: null,
	//ajax_url: maxajax.ajax_url,
	 //$(data).find('[data-line]');
};

miStats.prototype.init = function() {
	this.loadData();

	$(document).on('stats-dofilter', $.proxy(this.doFilter, this ) );
	$(document).on('click', 'button[name="filter_stats"]', function (e) {
		e.preventDefault();
		$(this).trigger('stats-dofilter');
	} );
};

miStats.prototype.loadData = function(filters) {

	/*var data = {
		action: mib.ajax_action,
		plugin_action: 'get_chart',
		nonce: $('input[name="chart_nonce"]').val(),
	}; */

	var ajax = new window.maxFoundry.wm.maxAjax;
	var data = ajax.ajaxInit();

	data['plugin_action'] = 'get_chart';

	if (typeof filters != 'undefined' && filters.length > 0)
		data['filters'] = filters;

	this.toggleLoading(true);

	ajax.ajaxPost(data, $.proxy(this.processData, this) );

	/*$.ajax( {
		type: 'POST',
		url: this.ajax_url,
		data: data,
		success: $.proxy(this.processData, this)
	} ); */

}

miStats.prototype.doFilter = function () {
	var form = $('#filter_form').serialize();

	this.loadData(form);
}

miStats.prototype.processData = function(result)
{
	this.resetView();

	data = JSON.parse(result);

	var x =  $.makeArray(data.graph.x);

	this.drawGraph(data.graph);

 	for (var key in data.figures)
 	{
 		this.addFiguresBlock(data.figures[key]);
 	}

 	if (data.filters.primary.length > 0)
 	{
	 	for (var key in data.filters.primary)
	 	{
	 		this.addFilter(data.filters.primary[key], 'primary');
	 	}
	}
	if (data.filters.secondary.length > 0)
	{
		for (var key in data.filters.secondary)
		{
			this.addFilter(data.filters.secondary[key], 'secondary');
		}
	}

	this.setPeriod(data.period);

	this.toggleLoading(false);

 	// for now, not usuable.
	//this.addGraph('visits','hello', data.graph.visits);
	//this.addGraph('unique','hello', data.graph.unique);

	// this is because c3 is buggy
	//this.chart.unload ({ 'ids': ['temp'] });
}

miStats.prototype.toggleLoading = function(toggle)
{
	if (toggle)
		$('.stats .load_overlay').show();
	else
		$('.stats .load_overlay').hide();
}

miStats.prototype.addFilter = function (filter, location)
{
	$('.filters.' + location).append(filter);
}

miStats.prototype.addFiguresBlock = function (item)
{
	$("#maxinbound.stats .dashboard").append("<div class='item " + item.name + "'><span class='title'>" + item.title + "</span>" +
			"<span class='number'>" + item.value + "</span></div>");
}

miStats.prototype.resetView = function()
{
	$('#maxinbound.stats .dashboard').children().remove();
	$('#maxinbound.stats .filters.primary, #maxinbound.stats .filters.secondary').children().remove();
}

miStats.prototype.setPeriod = function(period)
{
	var start = period.start;
	var end = period.end;

	$('#maxinbound.stats .period_date .start').text(start);
	$('#maxinbound.stats .period_date .end').text(end);
}

miStats.prototype.addGraph = function (name, label, data)
{

	// add name of data to array
	data.unshift(name);

	this.chart.load ({
			columns: [
				data
			],
			names: {

			}
		});


	this.chart.data.names({ 'visits' : label });
}


miStats.prototype.drawGraph = function (graph)
{

	// this is because c3 is buggy
 	/*var temp = ['temp'];
 	for (i=0; i< data.length; i++)
 		temp.push(0);
 	*/

 	var columns = [];
 	var names = [];

	for (var index in graph)
	{
		if (index == 'labels')
		{
			for ( label_index in graph[index])
				names[label_index] = graph[index][label_index];
		}

		else
		{
			var obj = graph[index];
 			obj.unshift(index);
 			columns.push( obj);
 		}
	}

	this.chart = c3.generate({
		bindto: '#chart',
		data: {
		  x: 'x',
		  xFormat: '%Y%m%d',
		  columns:
		  	columns,
		  names: names,
		},

		axis: {
		    x: {
		        type: 'timeseries',
		        tick: {
		            format: '%Y-%m-%d',
		            outer: false,
		        }
		    },
		    y: {
			    min: 0,
		    	tick: {
		    		format: function (d) { if (d == Math.floor(d) ) return d; else return '';   },
					outer: false,
		    	},
		        padding: { bottom: 0},
		    },

		}
	});

};



// reload on filter
/*
$('#post-select').on('change', function () {
	$("#filter-form").submit();

});
*/

jQuery(document).ready(function($) {

window.maxFoundry.maxInbound.modules.stats = new miStats();
window.maxFoundry.maxInbound.modules.stats.init();

});
