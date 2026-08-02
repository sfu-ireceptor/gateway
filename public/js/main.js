String.prototype.endsWith = function(suffix) {
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
};

$(document).ready(function() {
	$('[data-toggle="tooltip"]').tooltip();

	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});

	/**********************************************************
	* Login
	**********************************************************/

	// autofocus on username field
	$('form input[name=username]').focus(function() {
		$(this).select();
	}).focus();

	/**********************************************************
	* Samples
	**********************************************************/

	// multiselect
	$('.multiselect-ui').multiselect({
        includeSelectAllOption: true,
        buttonWidth: '100%',
        enableHTML: true,
        nonSelectedText: 'Any',
        numberDisplayed: 1,
    });

	// help text for form fields
	$('[data-toggle="popover_form_field"]').popover({
  		html: true
	});

	// jstree: rest services -> labs -> projects tree
	$('#rest_service_list').jstree();

	// jstree: hack to make links work
	// (otherwise link default behaviour is overrided by jstree)
	$('#rest_service_list').on('after_open.jstree', function (e, data) {
		$('.jstree-anchor').removeClass('jstree-anchor').addClass('jstree-anchor-simple');
  	});

	// rest services modal
	$('.toggle_modal_rest_service_list_folded').click(function () {

		$('#rest_service_list .rs_node').each(function(){
			$('#rest_service_list').jstree('close_node', $(this));
		});

		$('#myModal').modal('toggle');
		return false;
	});

	$('.toggle_modal_rest_service_list_expanded').click(function () {
		$('#rest_service_list .rs_node').each(function(){
			$('#rest_service_list').jstree('open_node', $(this));
		});

		$('#myModal').modal('toggle');
		return false;
	});

	// jstree: rest services tree for clones -> labs -> projects tree
	$('#rest_service_list_clones').jstree();

	// jstree: hack to make links work
	// (otherwise link default behaviour is overrided by jstree)
	$('#rest_service_list_clones').on('after_open.jstree', function (e, data) {
		$('.jstree-anchor').removeClass('jstree-anchor').addClass('jstree-anchor-simple');
  	});

	// rest services modal
	$('.toggle_modal_rest_service_list_clones_folded').click(function () {

		$('#rest_service_list_clones .rs_node').each(function(){
			$('#rest_service_list_clones').jstree('close_node', $(this));
		});

		$('#myModalClones').modal('toggle');
		return false;
	});

	$('.toggle_modal_rest_service_list_clones_expanded').click(function () {
		$('#rest_service_list_clones .rs_node').each(function(){
			$('#rest_service_list_clones').jstree('open_node', $(this));
		});

		$('#myModalClones').modal('toggle');
		return false;
	});

	// jstree: rest services tree for cells -> labs -> projects tree
	$('#rest_service_list_cells').jstree();

	// jstree: hack to make links work
	// (otherwise link default behaviour is overrided by jstree)
	$('#rest_service_list_cells').on('after_open.jstree', function (e, data) {
		$('.jstree-anchor').removeClass('jstree-anchor').addClass('jstree-anchor-simple');
  	});

	// rest services modal
	$('.toggle_modal_rest_service_list_cells_folded').click(function () {

		$('#rest_service_list_cells .rs_node').each(function(){
			$('#rest_service_list_cells').jstree('close_node', $(this));
		});

		$('#myModalCells').modal('toggle');
		return false;
	});

	$('.toggle_modal_rest_service_list_cells_expanded').click(function () {
		$('#rest_service_list_cells .rs_node').each(function(){
			$('#rest_service_list_cells').jstree('open_node', $(this));
		});

		$('#myModalCells').modal('toggle');
		return false;
	});

	// save filters panels state when submitting form
	$('form.sample_search, form.sequence_search, form.clone_search, form.cell_search').submit(function(){
		var filters_form = $(this);

		$('.panel-collapse', $(this)).each(function(i){
			if($(this).hasClass('in')) {
				var input = $('<input>').attr('type', 'hidden').attr('name', 'open_filter_panel_list[]').val(i);
		        filters_form.append($(input));
			}
		});

		return true;
	});

	// add new field
	$('.add_field button').click(function() {
		var button = $(this);
		var field = $('.add_field select[name=extra_field]').val();
		var field_url = '/samples/field/' + field;

		// disable button
		button.prop('disabled', true);
		$('span.glyphicon', button).removeClass('glyphicon-plus');
		$('span.text', button).text('adding filter..');
		
		$.get(field_url, function(data) {
			// add form field
			$('.extra_fields_list').append($(data));
			$('.extra_fields').removeClass('hidden');

			// add corresponding table column
			$('.column_selector input.' + field).prop("checked", true);
			$('.column_selector input.' + field).change();

			// disable this option in the dropdown
			$('.add_field select[name=extra_field] option[value="' + field + '"]').attr('disabled','disabled');

			// change dropdown selection to first not-disabled option
			var first_not_disabled_option_value = $('.add_field select[name=extra_field] option:enabled:first').val();
			$('.add_field select[name=extra_field]').val(first_not_disabled_option_value);

			// re-enable button
			button.prop('disabled', false);
			$('span.glyphicon', button).addClass('glyphicon-plus');
			$('span.text', button).text('Add filter');

			// add help text for new form fields
			$('[data-toggle="popover_form_field"]', $('.extra_fields_list')).popover({
				html: true
			});
		})
		.fail(function(jqXHR, status, message) {
			console.log(status + ': ' + message);

			// re-enable button
			button.prop('disabled', false);
			$('span.glyphicon', button).addClass('glyphicon-plus');
			$('span.text', button).text('Add filter');
		});
	});


	// remove extra field
	$('.extra_fields').on('click', '.remove_field', function() {
		var field_group = $(this).closest('div.form-group');
		var field_name = $('label', field_group).attr('for');
		var field_data_url = '/samples/field-data/' + field_name;

		$.get(field_data_url, function(data) {
			var field = data['field'];
			var field_drowpdown_name = field['ir_subclass'] + ' | ' + field['ir_short'];

			// enable field in dropdown
			$('.add_field select[name=extra_field] option[value=' + field_name + ']').removeAttr('disabled');

			// remove field from form
			field_group.remove();
		})
		.fail(function(jqXHR, status, message) {
			console.log(status + ': ' + message);
		});
	});


	// repertoire statistics popup
	$('#statsModal').on('show.bs.modal', function (event) {
		var modal = $(this),
			button = $(event.relatedTarget),
			repertoire_name = button.data('repertoireName'),
			stats_url = button.data('url');

			// hide popover
			$('.stats_notification_container').remove();

			// notify server popover has been seen
			$.get('/samples/count-stats-popup-open');

			// un-highlight any highlighted table rows
			$('tr.info', button.parents('table')).removeClass('info');

			// highlight this table row
			button.parents('tr').addClass('info');

			// clear previous data
			$('.sample_stats_info', modal).html('<p class="loading">Loading repertoire metadata...</p>');
			$('#stats_vgene', modal).html('<p>Loading V-gene graph...</p>');
			$('#stats_dgene', modal).html('<p>Loading D-gene graph...</p>');
			$('#stats_jgene', modal).html('<p>Loading J-gene graph...</p>');
			$('#stats_junction_length', modal).html('<p>Loading Junction Length graph...</p>');

			// load repertoire info div
			$.get(stats_url, function(html){
				$('.sample_stats_info', modal).html(html);
			});

			// load graphs
			$('ul.nav-tabs li a', modal).each(function() {
				var link = $(this),
					stat = link.data('stat'),
					anchor = link.attr('href'),
					target_id = anchor.substring(1);

					// clear div
					$(anchor).empty();

					// update modal contents
					$.get(stats_url + '/' +  stat, function(data){

					    let properties = airrvisualization.createProperties();

					    if(stat == 'v_gene_usage') {
						    properties.setDataType('VGeneUsage');
						    properties.setDataDrilldown(true);
						    properties.setSubtitle(["Subgroup/Family", "Gene", "Allele"]);
						    properties.setSeriesColors(["rgb(124,181,226)"]);
					    }
					    else if(stat == 'd_gene_usage') {
						    properties.setDataType('DGeneUsage');
						    properties.setDataDrilldown(true);
						    properties.setSubtitle(["Subgroup/Family", "Gene", "Allele"]);
						    properties.setSeriesColors(["rgb(244,164,90)"]);
					    }
					    else if(stat == 'j_gene_usage') {
						    properties.setDataType('JGeneUsage');
						    properties.setDataDrilldown(true);
						    properties.setSubtitle(["Gene", "Allele"]);
						    properties.setSeriesColors(["rgb(107,194,135)"]);
					    }
					    else if(stat == 'junction_length_stats') {
							properties.setDataType('JunctionLength');
							properties.setPercentage(true);
						    properties.setSeriesColors(["rgb(158,123,196)"]);
					    }					    

						try {
							properties.setId(target_id).setSort(true).setData(data.stats).setTitle(' ');
							let chart = airrvisualization.createChart(properties);
							chart.plot();
						}
						catch(err) {
							$('#' + target_id).html('<p>Sorry, an error occurred while generating this graph.</p>');
						}
					}).fail(function() {
						$('#' + target_id).html('<p>Sorry, an error occurred while generating this graph.</p>');
					});
			});
		
		modal.find('.modal-title').text('Repertoire: ' + repertoire_name);
	});


	/**********************************************************
	* Sequences
	**********************************************************/

	$('form.standard_sequence_search').submit(function(event){
		var nb_fields = 0;

		// get number of not empty text fields
		nb_fields = $('input[type=text], select', $(this)).filter(function () {
    		return $.trim($(this).val()).length !== 0
		}).length;

		if(nb_fields > 1) {
			if (! confirm('This may take up to 3 min, and some repositories might not respond, because queries with multiple filters are computationally expensive.')) {
				event.stopImmediatePropagation();
				return false;
			}
		}

		return true;
	});

	// bookmarking
	$('a.bookmark').click(function(){
		var button = $('button', $(this));
		var span = $('button span.glyphicon', $(this));
		var text = $('button span.text', $(this));
		var url = $(this).data('uri');
		var bookmarkId = button.data('id');

		button.prop('disabled', true);
		if(span.hasClass('glyphicon-star-empty')) {
			// add bookmark
			$.post("/bookmarks/add", {url: url})
				.done(function(id) {
					button.data('id', id);

					span.removeClass('glyphicon-star-empty');
					span.addClass('glyphicon-star');

					button.removeClass('btn-default');
					button.addClass('btn-success');

					text.text('Bookmarked');
					button.prop('disabled', false);
				});
		}
		else {
			// remove bookmark
			$.post("/bookmarks/delete", {id: bookmarkId})
				.done(function() {
					span.removeClass('glyphicon-star');
					span.addClass('glyphicon-star-empty');

					button.removeClass('btn-success');
					button.addClass('btn-default');

					text.text('Bookmark this search');
					button.prop('disabled', false);
				});
		}

		return false;
	});

	// show/hide table column (using current state of corresponding column selector checkbox)
	function update_column_visibility(column_checkbox) {
		var column_id = column_checkbox.val();

		// show/hide corresponding column
        if(column_checkbox.is(":checked")) {
        	$('table .' + column_id).removeClass('hidden');
        }
        else {
        	$('table .' + column_id).addClass('hidden');
        }

        // update column group checkbox status
        var column_group = column_checkbox.parents('.column_group');
    	var nb_columns_in_group = 0;
    	var nb_columns_checked_in_group = 0;

    	$('input[name=table_columns]', column_group).each(function() {
    		nb_columns_in_group++;
    		if($(this).is(":checked")) {
    			nb_columns_checked_in_group++;
    		}
        });

    	var column_group_checkbox = $('input[name=table_columns_group]', column_group);

    	if(nb_columns_checked_in_group == nb_columns_in_group) {
    		column_group_checkbox.prop('checked', true);
			column_group_checkbox.prop('indeterminate', false);
    	}
    	else {
			column_group_checkbox.prop('checked', false);
			if(nb_columns_checked_in_group > 0) {
				column_group_checkbox.prop('indeterminate', true);
			}
			else {
				column_group_checkbox.prop('indeterminate', false);
			}
    	}
	}

	// update all table columns visiblity (using current column selector checkboxes state)
	function show_hide_columns() {
		$('.column_selector input[type=checkbox][name=table_columns]').each(function() {
			update_column_visibility($(this));
		});
	}

	// change column visiblity event
    $('.column_selector input[name=table_columns]').change(function() {
    	update_column_visibility($(this));

        // save ids of currently displayed columns in hidden form field
        var columns = [];
        $('.column_selector input[name=table_columns]:checked').each(function() {
        	var column_id = $(this).val().replace(/^col_/, '');
        	columns.push(column_id);
        });
        $('input[name=cols]').val(columns.join(','));
    });

    // change column group event
    $('.column_selector input[name=table_columns_group]').change(function() {
    	var new_state = $(this).is(":checked");
    	var column_group = $(this).parents('.column_group');
    	$('input[name=table_columns]', column_group).each(function() {
    		$(this).prop('checked', new_state).change();
        });
    });

	// on page load, update table columns visibity 
	show_hide_columns();

	// send current columns when sorting table
	$('a.sort_column').click(function() {
        var old_url = this.href,
        	cols = $('input[name=cols]').val();
            new_url = old_url + '&cols=' + cols;
        window.location = new_url;
        return false;
   	});

    // reloading message
    function show_reloading_message() {
    	$('.reloading_contents').after($('.reloading_message'));
    	$('.reloading_contents').hide();
		$('.reloading_message').show();    	
    }

    function hide_reloading_message() {
    	$('.reloading_contents').show();
    	$('form input[type=submit]').removeAttr('disabled');
    	$('.reloading_message').hide();  	
    }

	$('form.show_reloading_message').submit(function(){
		$('input[type=submit]', $(this)).attr('disabled','disabled');
		show_reloading_message();
	});

    $(window).bind("pageshow", function(event) {
		hide_reloading_message();
	});

	$('.reloading_message a.cancel').click(function(){
		window.stop();
		hide_reloading_message();
	});

    // loading overlay
    function show_loading_message() {
    	$('.loading_contents').addClass('disabled');
		$('.loading_message').show();
		$('.loading_message_background').show();
    }

    function hide_loading_message() {
    	$('.loading_contents').removeClass('disabled');
    	$('.loading_message').hide();
		$('.loading_message_background').hide();
    }

    function hide_finishing_loading_message() {
    	$('.reloading_contents').removeClass('hidden');
    	$('.finishing_loading_message').hide();
    }

	$('form.show_loading_message').submit(function(){
		show_loading_message();
	});

    $(window).bind("pageshow", function(event) {
		hide_loading_message();
	});

	jQuery(window).load(function () {
		hide_finishing_loading_message();
	});

	$('.loading_message a.cancel').click(function(){
		window.stop();
		hide_loading_message();
	});

	// automatically do AJAX download query on sequence download page
	$('a.download_sequences_direct').each(function(){
		var url = $(this).attr('href');
		console.log('url=' + url);

		$.get(url, function(data) {
			var file_path = data.file_path;
			var incomplete = data.incomplete;
			var file_stats = data.file_stats;
			var message = 'Your file is ready. If the download didn\'t start automatically, <a href="' + file_path + '">click here</a>.';
			var status = 'alert-success';

			if(incomplete) {
				message += '<br><br>' + 'Warning: some sequences seem to be missing:' + '<ul>';
				file_stats.forEach(function(t) {
					if(t.incomplete) {
						message += '<li>' + t.rest_service_name + ' (' + t.name + '): expected ' + t.expected_nb_sequences + ' sequences, received ' + t.nb_sequences + '</li>';						
					}
				});
				message += '</ul>';
				status = 'alert-warning';
			}

			$('.download_message').hide();
			$('.download_status').addClass(status).show().html(message);
			window.location.href = file_path;
		})
		.fail(function(jqXHR, status, message) {
			console.log(status + ': ' + message);
			$('.download_message').hide();
			var message = 'Sorry, there was a problem with the download. Try again later or contact us at <a href="mailto:support@ireceptor.org">support@ireceptor.org</a>.';
			$('.download_status').addClass('alert-danger').show().html(message);
		});
	});

	/**********************************************************
	* Systems
	**********************************************************/

	// on system change, do AJAX POST request and change row color 
	$('.system_list input[type=radio]').change(function() {
		var radio = $(this);
		$.post("/systems/select", {id: radio.val()})
			.done(function() {
				var public_key;

				$('.system_list tr').removeClass('selected');
				radio.parents('tr').addClass('selected');

				public_key = $('.system_list .selected td input.public-key').val();
				$('pre.ssh_key_how_to strong').text(public_key);
			});
	});

	// select public key when click on field
	$('input.public-key').click(function(){
		$(this).select();
	});

	/**********************************************************
	* Jobs
	**********************************************************/

	// job view - refresh
	$('.job_container').each(function(){
		var job_id = $(this).data('job-id');
		var job_status = $(this).data('job-status');

		if(job_status < 2) {
			function refresh() {
				$.ajax({
					url: '/jobs/job-data/' + job_id,
					dataType: 'json',
					success: function(data) {
						$('.job_view_progress').html(data['progress']);
						$('.job_steps').html(data['steps']);
						$('.submission_date_relative').html(data['submission_date_relative']);
						$('.run_time').html(data['run_time']);
						$('.job_url').html(data['job_url']);
						$('.summary').html(data['summary']);
						$('.job_summary').html(data['job_summary']);
						$('.job_control_button').html(data['job_control_button']);
						document.title = data['agave_status'];

						if (data['status'] >= 2) {
							// if job is done, stop timer and reload page
							clearInterval(timer);
							location.reload();
						}
					}
				});			
			}

			var timer = setInterval(refresh, 10000);
		}
	});	

	// job view - display images
	$('.result_files li a').each(function(){
		var href = $(this).attr('href');
		if (href.endsWith('.jpg')) {
			$(this).html('<img src="' + href + '"/>');
		}
	});

	// job list - status refresh
	$('.job_list_grouped_by_month').each(function(){
		
		function refreshJobList() {
			$.ajax({
				url: 'jobs/job-list-grouped-by-month',
				success: function(html) {
					$('.job_list_grouped_by_month').html(html);
				}
			});			
		}

		var timer = setInterval(refreshJobList, 10000);
	});	

	/**********************************************************
	* Downloads
	**********************************************************/

	// download list - refresh
	$('.page-refresh').each(function(){
		var refresh_interval = $(this).attr('data-page-refresh-interval');
		setTimeout(function(){
			window.location.reload(true);
		}, refresh_interval * 1000);
	});	
});
