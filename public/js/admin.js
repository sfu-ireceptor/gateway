$(document).ready(function() {

	$('table.rs_stats').each(function() {
		var nb_samples = $('tbody tr', $(this)).length,
			stat_list = ['v_gene_usage', 'd_gene_usage', 'j_gene_usage', 'junction_length_stats'];
			
			testStats(nb_samples, stat_list, 0);
	});

	// repertoire statistics JSON popup
	$('#statsModalJSON').on('show.bs.modal', function (event) {
		var modal = $(this),
			button = $(event.relatedTarget),
			stats_url = button.data('url');


			// clear previous data
			$('#stats_vgene', modal).html('<pre>Loading V-gene JSON...</pre>');
			$('#stats_dgene', modal).html('<pre>Loading D-gene JSON...</pre>');
			$('#stats_jgene', modal).html('<pre>Loading J-gene JSON...</pre>');
			$('#stats_junction_length', modal).html('<pre>Loading Junction Length JSON...</pre>');

			// load JSON
			$('ul.nav-tabs li a', modal).each(function() {
				var link = $(this),
					stat = link.data('stat'),
					anchor = link.attr('href'),
					target_id = anchor.substring(1);

					// clear div
					$(anchor).empty();

					// update modal contents
					$.get(stats_url + '/' +  stat, function(data){
						try {
							$('#' + target_id).html('<pre>' + JSON.stringify(data, null, 2) + '</pre>');
						}
						catch(err) {
							$('#' + target_id).html('<p>Sorry, an error occurred when trying to display the JSON code</p>');
						}
					}).fail(function() {
						$('#' + target_id).html('<p>Sorry, an error occurred while doing the AJAX query</p>');
					});
			});
	});
});

function testStats(nb_samples, stat_list, i) {
	setTimeout(function() {
		stat_list.forEach(function(stat, index) {
			var tr = $('tr:eq(' + i + ')', $('table.rs_stats tbody')),
				stats_url = tr.data('url');

			$.get(stats_url + '/' +  stat, function(data){
				$('.' + stat + ' span',tr).removeClass('label-default').addClass('label-warning').text('Running');

				try {
					let properties = airrvisualization.createProperties();

				    if(stat == 'v_gene_usage') {
					    properties.setDataType('VGeneUsage');
					    properties.setDataDrilldown(true);
				    }
				    else if(stat == 'd_gene_usage') {
					    properties.setDataType('DGeneUsage');
					    properties.setDataDrilldown(true);
				    }
				    else if(stat == 'j_gene_usage') {
					    properties.setDataType('JGeneUsage');
					    properties.setDataDrilldown(true);
				    }
				    else if(stat == 'junction_length_stats') {
						properties.setDataType('JunctionLength');
				    }					    

					properties.setId(null).setSort(true).setData(data.stats).setTitle(' ');
					let chart = airrvisualization.createChart(properties);

					$('.' + stat + ' span',tr).removeClass('label-warning').addClass('label-success').text('Success');
				}
				catch(err) {
					$('.' + stat + ' span',tr).removeClass('label-warning').addClass('label-danger').text('Failed');
				}				
			});
		});		

		i++;
		if(i < nb_samples) {
			testStats(nb_samples, stat_list, i);
		}
	}, 1000);
}

