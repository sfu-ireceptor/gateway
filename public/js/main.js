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
        nonSelectedText: 'Any',
        numberDisplayed: 1,
    });

	// help text for form fields
	$('[data-toggle="popover_form_field"]').popover({
  		html: true
	});

	// jstree: rest services -> labs -> projects tree
	$('#rest_service_list').on('ready.jstree', function (e, data) {
			// hack to make links work
			// (otherwise the links default behaviour is overrided by jstree)
	   		$('.jstree-anchor').addClass('jstree-anchor-simple').removeClass('jstree-anchor');
  	}).jstree();
	
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

	$('form.standard_sequence_search').submit(function(event){
		var nb_fields = 0;

		// get number of not empty text fields
		nb_fields = $('input[type=text], select', $(this)).filter(function () {
    		return $.trim($(this).val()).length !== 0
		}).length;

		if(nb_fields > 1) {
			if (! confirm('This may take a while (up to 2 min) and return incomplete data (but you will be notified) because using multiple sequence filters on a significant amount of data can be computationally expensive and time out on some of the remote repositories.')) {
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

	// update table column visibility using state of corresponding column selector checkboxe
	function update_column_visibility(column_checkbox) {
		var columnId = column_checkbox.val();

		// show/hide corresponding column
        if(column_checkbox.is(":checked")) {
        	$('table .' + columnId).removeClass('hidden');
        }
        else {
        	$('table .' + columnId).addClass('hidden');
        }
	}

	// change column visiblity
    $('.column_selector input').change(function() {
    	update_column_visibility($(this));

        // save ids of currently displayed columns in hidden form field
        var columns = [];
        $('.column_selector input:checked').each(function() {
        	var columnId = $(this).val().replace(/^col_/, '');
        	columns.push(columnId);
        });
        $('input[name=cols]').val(columns.join(','));
    });

	// update all table columns visiblity using column selector checkboxes
	function show_hide_columns() {
		$('.column_selector input[type=checkbox]').each(function() {
			update_column_visibility($(this));
		});
	}

	// on page load, update table columns visibity 
	show_hide_columns();

	// send current columns when sorting table
	$('a.sort_column').click(function() {
        var old_url = this.href,
        	cols = $('input[name=cols]').val();
            new_url      = old_url + '&cols=' + cols;
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
});
