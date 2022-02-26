$(document).ready(function() {
    var graphDIV = "chart";
    var graphLabelLength = 20;

    $('#charts').each(function() {
        showData(graphData, graphFields, graphNames, graphCountField, graphDIV, graphInternalLabels, graphLabelLength);
    });
});


/**********************************************************
* Functions
**********************************************************/

// showData aggregates the json data over the fields of graphFields (array of field names)
// renders a graph for each aggregated field into the HTML container provided
// by the htmlBase variable with an integer index appended to the ID name. 
// Each graph is given the title provided in the graphNames array.
// 
// Requisites:
// - arrays graphFields and graphNames have the same size.
// - valid html container for each graph with ID given by the string htmlBase with a suffix of the index
// of the graph (starting at 1). For example, if there are N graphs and htmlBase
// is "foo" then there should be N valid html containers with the IDs "foo1",
// "foo2" up to "fooN".
function showData(json, graphFields, graphNames, countField, htmlBase, internalLabels, truncateLabels=10)
{
    // Initial variables. These should be provided by the gateway, but they are constants for now.
    // sequenceAPIData - Whether or not the data came from the sequence_summary API or not.
    // The summary JSON data from the /v2/samples and /v2/sequences APIs are slightly different.
    var aggregateBySequence = true;

    // Generate the  charts for the  types of aggregated data provided. For each chart, we
    // get the aggregated data for the field of interest, convert that aggregated data
    // into a data structure that is appropriate for HighChart to make a chart out of,
    // and then finally render the chart (using HighChart) in the HTML container of 
    // choice.
    var aggregateData; // Variable for the generated aggregate data
    var chart; // Variable for he generated chart data
    var containerID = ""; // Generated sting for the container ID for each graph
    var containerNumber = 1; // The numeric suffix, containers expected to start at 1
    for (index in graphFields)
    {
	// Each graph field is an array of two field names, the first is for counting, 
	// the second is for labels.
	fieldPair = graphFields[index];
	aggrField = fieldPair[0];
	nameField = fieldPair[1];
        // valeus count for this field.
        //valuesCount = irAggregateData(graphFields[index], json, aggregateBySequence, countField);
        valuesCount = irAggregateData(aggrField, nameField, json, aggregateBySequence, countField);

        // transform aggregate data structure for chart
        var aggregateData = [];
        var i = 0;
        for (field in valuesCount) {
            if(valuesCount[field] > 0) {
                aggregateData[i] = {name:field, count:valuesCount[field]};   
                i++;                
            }
        }

        // Build the chart data structure.
        chart = irBuildPieChart(graphNames[index], aggregateData, 3, internalLabels, truncateLabels);
        //chart = irBuildBarChart(graphNames[index], aggregateData, 4, internalLabels);
        // Generate the container ID to use, we expect containers numberd at 1.
        containerID = htmlBase + String(containerNumber);
        // Render the chart in the container using the HighChart graph API.
        if( $('#'+containerID).length > 0 )
        {
            Highcharts.chart(containerID, chart);
        }
        // Increment the container number
        containerNumber++;  
    }
}

function bubbleSort(a, b)
{
    var swapped;
    do {
        swapped = false;
        for (var i=0; i < a.length-1; i++) {
            if (a[i] < a[i+1]) {
                var temp = a[i];
                var btemp = b[i];
                a[i] = a[i+1];
                a[i+1] = temp;
                b[i] = b[i+1];
                b[i+1] = btemp;
                swapped = true;
            }
        }
    } while (swapped);
}

// Build a chart for the iReceptor aggregation data using HighCharts.
function irBuildPieChart(fieldTitle, data, level, internalLabels, truncateLabels=10)
{
    // Debug level for when developing...
    var debugLevel = 0;

    // Sort the data
    var keys = [];
    var values = [];
    for(var d in data) 
    {
        if (debugLevel > 0)
        {
            console.log("++" + "name = " + data[d].name + "count = " + data[d].count + "++" + "\n");
        }
        keys.push(data[d].name);
        values.push(data[d].count);
    }
    bubbleSort(values, keys);

    // Catch the special case where we want N levels and we have N+1.
    // In this case, the "Other" category would hold only 1 value, and
    // it is pointless. Thus in this case we accept N+1 levels in our
    // graph and avoid having an Other slice with 1 data value.
    if (keys.length == level+1) level++;

    // Convert iReceptor aggregate data into a form for HighChart.
    var seriesData = [];
    var otherSequences = 0;
    var otherData = [];
    var otherCount = 0;
    for (var i = 0; i<keys.length; i++)
    {
        if (i < level)
        {
            if (debugLevel > 0)
            {
                console.log("--" + "count = " + i + "--" + "\n");
                console.log("--" + keys[i] + " = " + values[i] + "--" + "\n");
            }
            seriesData[i] = {name:keys[i],y:values[i],drilldown:null};
        }
        else
        {
            otherSequences += values[i];
            otherData[otherCount] = [keys[i],values[i]];
            if (debugLevel > 0)
            {
                console.log("--" + "count = " + i + "--" + "\n");
                console.log("--" + "Other" + " = " + otherSequences + "--" + "\n");
                console.log("--" + keys[i] + " = " + values[i] + "--" + "\n");
            }
            otherCount++
        }
    }
    if (level < keys.length)
        seriesData[level] = {name:'Other', y:otherSequences, drilldown:'OtherDetails'};

    // Set up the label display
    if (internalLabels) labelDistance = -10;
    else labelDistance = 3;

    // default settings
    var colors = ['#7cb5ec', '#f4a45a', '#6bc287', '#9e7bc4', '#c47b87', '#fb9f89', '#e6e0a1', '#ebcfc4', '#e8e6d9', '#999999'];
    var class_name = 'chart_label';
    var n = data.length;

    // make sure that the smaller slice is black
    var black_level = Math.min(level, (n-1));
    colors[black_level] = '#4a4a4a';

    // if no values, don't display a chart
    if(data.length == 1 && data[0]['name'] == 'None') {
        colors = ['transparent'];
        truncateLabels = 15;
        labelDistance = -58;
        class_name = 'chart_label_nodata';
        n = 0;
    }
    // if only 1 value, use a muted color for the chart
    else if(data.length == 1) {
        colors = ['#e4e4e4'];
        truncateLabels = 15;
        labelDistance = -58;
    }

    Highcharts.setOptions({
        colors: colors
    })

    // display number of items
    var fieldTitleWithNumber = n + ' ' + pluralize(fieldTitle.toUpperCase(), n);

    // Generate the chart data structure for HighChart.
    var chartData;

        chartData = {
            lang: {
                noData: "No results found</br>Try removing a filter"
            },
            noData:
            {
                useHTML: true
            },
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                backgroundColor: 'transparent',
                type: "pie"
            },
            title: {
                text: fieldTitleWithNumber, 
                floating: false,
                margin: 0,
                style: {"font-size": "12px","font-weight":"bold"}
            },
            tooltip: {
                pointFormat: '<b>{point.y:.0f} ({point.percentage:.1f}%)</b>'
            },
            plotOptions: {
                pie: {
                    center: ["50%","50%"],
                    // We want a start angle for the pie chart to be 90 deg
                    // such that internal labelling is more likely to have
                    // text that does not overlap.
                    startAngle: 90,
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        className: class_name,
                        // Color of the labels.
                        color: "#666666",
                        // Do we want to display labels.
                        enabled: true,
                        /*overflow: "justify",*/
                        // Hide data labels that are outside the plot area. From the 
                        // manual - "To display data labels outside the plot area, set
                        // crop to false and overflow to 'none'""
                        crop: false,
                        overflow: "justify",
                        /* inside: true, */
                        // Format of the data label. We want to use a formatter
                        // so that we can shorten up all the labels that are used
                        // within the graph proper (we don't want to display really
                        // long names as it makes the graph ugly).
                        formatter: function()
                        {
                            var newname = this.point.name.substring(0,truncateLabels);
                            //var newname = this.point.name.replace(/ /,"</br>");
                            //var newname = this.point.name;

                            return(newname);
                        },
                        // Distance says how far values are from pie chart,
                        // negative numbers mean values are inside the pie.
                        // -10 is a good value for labels within the pie, as
                        // it minimizes text overlap and truncation...
                        // 5 is a good value for labels external to the pie
                        // as it minimizes the length of the line joining the
                        // label to the pie.
                        distance: labelDistance,
                        // We want to use HTML in case we want a line break in a label.
                        //useHTML: true,
                        style:{
                            fontWeight: null,
                            textOutline: '1px contrast', // "glow" around text
                        },
                        
                    }
                    //showInLegend: false
                },
                series: {
                    animation: false,
                }
            },
            legend: {
                enabled: false,
                maxHeight: 75,
                floating: false,
                itemStyle: {"font-weight":"normal", "font-size": "10px"}
            },
            series: [{
                name: fieldTitle,
                colorByPoint: true,
                data: seriesData
            }],
            drilldown: {
                drillUpButton:{
                    position: {y:-10, x:10},
                    relativeTo: 'spacingBox'
                },
                series: 
                [{
                    name: 'Other',
                    id: 'OtherDetails',
                    data: otherData
                }]
            },
            exporting: {
                enabled: false
            }
        };

    return chartData;
}

// Build a chart for the iReceptor aggregation data using HighCharts.
function irBuildBarChart(fieldTitle, data, level)
{
    // Debug level for when developing...
    var debugLevel = 0;
    // Build a chart using the "HighCharts" chart, using the data provided.
    var seriesData = [];
    var count = 0;

    // Convert iReceptor aggregate data into a form for HighChart.
    for (d in data)
    {
        if (debugLevel > 0)
        {
            console.log("--" + data[d] + "--" + "\n");
            console.log("--" + data[d].name + " = " + data[d].count + "--" + "\n");
        }


        seriesData[count] = {name:data[d].name,data:[data[d].count]};
        count = count + 1;
    }

    // Generate the chart data structure for HighChart.
    var chartData;
        chartData = {
            chart: {
                type: 'column',
                marginLeft: 5,
                marginRight: 5
            },
            title: {
                text: fieldTitle,
                style: {"font-size": "12px"},
                floating: false,
                margin: 0
            },
            xAxis: {
                labels: {enabled:false},
                visible: false
            },
            yAxis: {
                visible: false,
                min: 0,
                title: {
                    text: '%'
                }
            },
            tooltip: {
                pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> ({point.percentage:.0f}%)<br/>',
                shared: true
            },
            plotOptions: {
                column: {
                    //stacking: 'percent',
                    stacking: 'normal',
                    borderWidth: 0
                }
            },
            legend: {
                enabled: false,
                maxHeight: 75,
                floating: false,
                itemStyle: {"font-weight":"normal", "font-size": "11px"}
            },
            series: seriesData
        };
    return chartData;
}


// Generate array of values count for a given field 
// 
// field: that field
// objList: list of objects
// aggregateBySequence: aggregate number of sequences count instead of
//  just incrementing value count for each object that field value
function irAggregateData(field, nameField, objList, aggregateBySequence = true, sequenceCountField = 'ir_sequence_count')
{        
    var valuesCount = [];
    var fieldMap = [];

    // Iterate over all the object in the list.
    for (i in objList) {
        var obj = objList[i];
	// If the object has the field we are counting, process it.
        if (obj.hasOwnProperty(field)) {
	    // Get the value of the field for this object, so we can aggregate
            var value = obj[field];
            if(value === null) {
                value = 'None';
            }
	    // Also keep track of the label field value for each unique count value
	    if (obj[nameField] === null)
		fieldMap[value] = 'None';
	    else
	        fieldMap[value] = obj[nameField];

	    // Do the aggregation on the appropriate field
            count = 1;
            if (aggregateBySequence)
            {
                if($.isNumeric(obj[sequenceCountField])) {
                    count = obj[sequenceCountField];
                }
                else {
                    // there is no sequence with that field value
                    count = 0;
                }
            }

	    // Keep track of the count for this value of the count field
            if( ! (value in valuesCount)) {
                valuesCount[value] = 0;
            }
            valuesCount[value]+= count; 
        }
    }

    // What we really want to return is actual the aggregation with the key
    // being the field we want to display, not the field we counted on. So
    // we use the field map to create a count array keyed by the display field.
    var finalCount = [];
    for (i in valuesCount) {
	finalCount[fieldMap[i]] = valuesCount[i]
    }

    return finalCount;
}
