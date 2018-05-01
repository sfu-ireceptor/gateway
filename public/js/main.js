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
        includeSelectAllOption: false,
        buttonWidth: '100%',
        enableHTML: true,
        nonSelectedText: '&nbsp;',
        numberDisplayed: 1,
    });

	// help text for form fields
	$('[data-toggle="popover_form_field"]').popover({
  		html: true
	});

	// update number of selected samples
	function update_sample_selection_text(){
		var n = $('table.sample_list tbody input[type=checkbox]:checked').length;
		$('span.nb_selected_samples').text(n);

		// enable/disable "browse seq data" button
		if($('table.sample_list tbody input[type=checkbox]:checked').length > 0) {
			$('.browse-seq-data-button').removeAttr('disabled');
		}
		else {
			$('.browse-seq-data-button').attr('disabled','disabled');
		}
	}

	update_sample_selection_text();

	$('table.sample_list tbody input[type=checkbox]').change(function(){
		update_sample_selection_text();
	});

	// make table sortable
	$('table.sample_list').DataTable({
    	paging: false,
    	searching: false,
    	info: false,
    	order: [[ 5, 'asc' ]],
    	columnDefs: [
						{
							'orderable': false,
							'targets': 0
						}
					],
	});

	// jstree: rest services -> labs -> projects tree
	$('#rest_service_list').on('ready.jstree', function (e, data) {
			// hack to make links work
			// (otherwise the links default behaviour is overrided by jstree)
	   		$('.jstree-anchor').addClass('jstree-anchor-simple').removeClass('jstree-anchor');
  	}).jstree();

	// table select/unselect all rows
	$('a.select_all_samples').click(function(){
		$('input:checkbox', $('table.sample_list tbody')).prop('checked', true);
		$(this).hide();
		$('a.unselect_all_samples').show();
		update_sample_selection_text();
		return false;
	});

	$('a.unselect_all_samples').click(function(){
		$('input:checkbox', $('table.sample_list tbody')).prop('checked', false);
		$(this).hide();
		$('a.select_all_samples').show();
		update_sample_selection_text();
		return false;
	});
	
	// save filters panels state when submitting form
	$('form.sample_search, form.sequence_search').submit(function(){
		var filters_form = $(this);

		$('.panel-collapse', $(this)).each(function(i){
			if($(this).hasClass('in')) {
				var input = $('<input>').attr('type', 'hidden').attr('name', 'open_filter_panel_list[]').val(i);
		        filters_form.append($(input));
			}
		});

		return true;
	});

	/**********************************************************
	* Sequences
	**********************************************************/

	$('form.sequence_search').submit(function(event){
		var nb_fields = 0;

		// get number of not empty text fields
		nb_fields = $('input[type=text]', $(this)).filter(function () {
    		return $.trim($(this).val()).length !== 0
		}).length;

		if(nb_fields > 1) {
			if (! confirm('Multi-fields queries are currently very slow and might fail. Do you want to proceed?')) {
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

	// column selection
    $('.sequence_column_selector input').change(function() {
        var columnId = $(this).val();
        var columns = [];

        // show or hide corresponding column
        if($(this).is(":checked")) {
        	$('table .' + columnId).removeClass('hidden');
        }
        else {
        	$('table .' + columnId).addClass('hidden');
        }

        // save ids of currently displayed columns in hidden form field
        $('.sequence_column_selector input:checked').each(function() {
        	var columnId = $(this).val().replace(/^seq_col_/, '');
        	columns.push(columnId);
        });
        $('input[name=cols]').val(columns.join('_'));
    });

    function updateFiltersOrderField() {
    	var filtersList = [];
    	$('div.filter_list > .col-md-2').each(function() {
    		var fieldName = $('input', $(this)).attr('name');
    		var fieldId = $('input[type=checkbox].' + fieldName).data('id');
	    	filtersList.push(fieldId);
	    	$('input[name=filters_order]').val(filtersList.join('_'));
    	});
    }

    $('button.add_field').click(function() {
    	var select = $('select.add_field');
    	var fieldName = select.val();
    	var fieldTitle = $("select.add_field option:selected").text();

    	// init field template with current select option
    	$('#field_template label').attr('for', fieldName);
		$('#field_template label').text(fieldTitle);
    	$('#field_template input').attr('id', fieldName);
   		$('#field_template input').attr('name', fieldName);

		$('#field_template > div').clone(true).appendTo($('div.filter_list')); 
		$('select.add_field option:selected').remove();

		updateFiltersOrderField();
    });

    $('button.remove_field').click(function() {
    	var div = $(this).parents('.form-group');
    	var fieldName = $('input', div).attr('name');
    	var fieldTitle = $('label', div).text();
    	var select = $('select.add_field');

    	// remove filter
    	div.remove();

    	// update "add new filter" select
    	select.append('<option value="' + fieldName + '">' + fieldTitle + '</option>');
    	updateFiltersOrderField();
    });

    // tooltips
    $('[data-toggle="tooltip"]').tooltip();


    function show_loading_message() {
    	$('.loading_contents').addClass('disabled');
		$('.loading_message').show();    	
    }

    function hide_loading_message() {
    	$('.loading_contents').removeClass('disabled');
    	$('.loading_message').hide();  	
    }

	$('form.show_loading_message').submit(function(){
		show_loading_message();
	});

    $(window).bind("pageshow", function(event) {
		hide_loading_message();
	});

	$('.loading_message a.cancel').click(function(){
		window.stop();
		hide_loading_message();
	});

	// tsv download
	$('a.download_sequences').click(function() {
		var input = $('<input>').attr('type', 'hidden').attr('name', 'tsv').val('Download as TSV');
        $('form.sequence_search').append($(input));
		$('form.sequence_search').submit();
		return false;
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
						document.title = data['agave_status'];

						if (data['status'] >= 2) {
							// if job is done, stop timer and reload page
							clearInterval(timer);
							location.reload();
						}
					}
				});			
			}

			var timer = setInterval(refresh, 5000);
		}
	});	

	// jow view - display images
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

		var timer = setInterval(refreshJobList, 5000);
	});	

	/**********************************************************
	* Databases
	**********************************************************/

	// on checkbox change
	$('.rs_list input[type=checkbox]').change(function() {
		var checkbox = $(this);
		var rs_id = checkbox.val();
		var enabled = checkbox.is(":checked");

		$.post("/admin/update-database", {id: rs_id, enabled: enabled});
	});

});