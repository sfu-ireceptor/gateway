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

	// jstree: rest services -> labs -> projects tree
	$('#rest_service_list').on('ready.jstree', function (e, data) {
	    //data.instance.get_node(data.selected[i]).text);
		$('span.sra').each(function(){
			var sra = $(this).text();
			var url = 'https://www.ncbi.nlm.nih.gov/sra/?term=' + sra;
			//$(this).replaceWith('<a href="' . url . '">SRA</a>');
			//console.log($(this).text());
		});
  	}).jstree({
  		"plugins" : ["checkbox"]
	});

	$('form.sample_search').submit(function(){
		var projectIdList = $('#rest_service_list').jstree("get_bottom_checked");
		var projectIdListStr = projectIdList.join(",");
		
		//console.log(projectIdList);
		$('input[name=project_id_list]', $(this)).val(projectIdListStr);
		return true;
	});

	// enable/disable "browse seq data" button
	$('table.sample_list input[type=checkbox]').change(function(){
		if($('table.sample_list input[type=checkbox]:checked').length > 0) {
			$('.browse-seq-data-button').removeAttr('disabled');
		}
		else {
			$('.browse-seq-data-button').attr('disabled','disabled');	
		}
	});
	
	/**********************************************************
	* Sequences
	**********************************************************/

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

					text.text('Bookmark');
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

    	select.append('<option value="' + fieldName + '">' + fieldTitle + '</option>');
    	div.parent().remove();

    	updateFiltersOrderField();
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