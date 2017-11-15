// Process the various chart elements if they exist
$(document).ready(function()
{
    if ($('#landing_charts').length > 0 ) doLandingCharts();
    if ($('#sequence_charts').length > 0 ) doSequenceCharts();
    if ($('#sample_charts').length > 0 ) doSampleCharts();
}
);

// Function to process the Landing Page charts. Data (graphData,
// graphFields, and graphNames) is provided by the appropriate 
// blades that contain the relevant DIV (in this case landing_charts)
function doLandingCharts()
{
    showData(graphData, graphFields, graphNames, graphDIV, graphInternalLabels, false);
}
// Function to process the Landing Page charts. Data (graphData,
// graphFields, and graphNames) is provided by the appropriate 
// blades that contain the relevant DIV (in this case sample_charts)
function doSampleCharts()
{
    showData(graphData, graphFields, graphNames, graphDIV, graphInternalLabels, false);
}

// Function to process the Landing Page charts. Data (graphData,
// graphFields, and graphNames) is provided by the appropriate 
// blades that contain the relevant DIV (in this case sequence_charts)
function doSequenceCharts()
{
    // Get the JSON and process it. We currently have no mechanism to get the 
    // filtered data to the visualization - working on it...
    showData(graphData, graphFields, graphNames, graphDIV, graphInternalLabels, true);
}

/**********************************************************
* Functions
**********************************************************/

// showData aggregates thee json data provided over the fields given
// in the graphFields variable (an array of field names). It renders
// a graph for each aggregated field into the HTML container ID provided
// by the htmlBase variable with an integer index appended to the ID name. 
// Each graph is given the title as provided in the graphNames array.
// 
// Preconditions:
// - this function assumes that the graphFields and graphNames arrays are the
// same size.
// - this funtion assumes there is a valid html container ID for each graph
// with a container ID given by the string htmlBase with a suffix of the index
// of the graph (starting at 1). For example, if there are N graphs and htmlBase
// is "foo" then there should be N valid html containers with the IDs "foo1",
// "foo2" up to "fooN".
function showData(json, graphFields, graphNames, htmlBase, internalLabels, sequenceAPIData)
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
        // Get the aggregated data for this field.
        aggregateData = irAggregateData(graphFields[index], json, sequenceAPIData, aggregateBySequence);
        // Build the chart data structure.
        chart = irBuildPieChart(graphNames[index], aggregateData, 3, internalLabels);
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
function irBuildPieChart(fieldTitle, data, level, internalLabels)
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
        seriesData[level] = {name:'Other',y:otherSequences,drilldown:'OtherDetails'};

    // Set up the label display
    if (internalLabels) labelDistance = -10;
    else labelDistance = 3;

    Highcharts.setOptions({
        colors: ['#f3ece7', '#e9ccb1', '#d3c4be', '#e4dac2', '#f4eee1', '#c4bdac', '#ebcfc4', '#e8e6d9', '#999999']
    })

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
                text: fieldTitle, 
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
                        // Color of the labels.
                        color: "#888888", 
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
                            //var newname = this.point.name.substring(0,20);
                            //var newname = this.point.name.replace(/ /,"</br>");
                            var newname = this.point.name;

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
                        //useHTML: true
                        
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


// Example of data returned from the API - an array of data objects like the following:
// {
// "subject_code":"Pooled mice control SI PBS","subject_id":13,"subject_gender":null,"subject_species":"Mouse","subject_ethnicity":"",
// "project_id":1,"project_name":"Mouse single immunization","project_parent_id":-1,"sra_accession":null,
// "lab_id":1,"lab_name":"Jamie Scott Lab",//
// "disease_state_id":null,"disease_state_name":null,"case_control_id":1,"case_control_name":"Case",
// "sample_id":1,"project_sample_id":1,"sequence_count":28619,//
// "project_sample_note":"Controls using PBS immunization","sra_run_id":null,"sample_name":"3-PC-B",
// "subject_age":null,"sample_subject_id":13,"dna_id":1,"dna_type":"cDNA",
// "sample_source_id":2,"sample_source_name":"Blood","lab_cell_subset_name":"Plasma Cell","ireceptor_cell_subset_name":"Plasma Cell",
// "marker_1":"CD138+","marker_2":null,"marker_3":null,"marker_4":null,"marker_5":null,"marker_6":null
// }
//
// Do an aggregation count across the JSON data, aggregating on the series
// name provided in "seriesName" and aggregating the counts in "countField".
//
// seriesName: String that represents the series of interest that we are aggregating on (e.g. subject_species).
//
// jsonData: This is JSON data from the iReceptor API, in either the format 
// provided by the /v2/sequence_summary API call or the /v1 and /v2 samples API call.
// 
// aggregationSummary: A boolean flag that denotes whether jsonData came from
// the /v2/sequence_summary API or not. If not, we assume the data came from the
// /v1/samples API.
function irAggregateData(seriesName, jsonData, sequenceSummaryAPI=true, aggregateBySequence=true)
{
    // Debug level so we can debug the code...
    var debugLevel = 0;
    // Arrays to hold the aggregated value names and aggregated counts
    // e.g. an aggregateName might be "Mature T Cell" and the count might be 1,000,000 sequences
    var aggregateName = [];
    var aggregateCount = [];
    var aggregationData;
    var countField;
    
    // Debug: tell us the series name we are looking for.
    if (debugLevel > 0) 
    {
        console.log("irAggregateData: Series name = " + seriesName + "\n");
        console.log("irAggregateData: sequenceSummaryAPI = " + sequenceSummaryAPI + "\n");

        if (debugLevel > 1)
            console.log("irAggregateData: json Data = " + JSON.stringify(jsonData) + "\n");
    }
    
    aggregationList = jsonData;
    if (sequenceSummaryAPI)
        countField = "ir_filtered_sequence_count";
    else
        countField = "ir_sequence_count";
    if (debugLevel > 1)
        console.log("irAggregateData: aggregation list has " + JSON.stringify(aggregationList) + "\n");
    
    // Process each element in the data from iReceptor. 
    var count = 0
    for (element in aggregationList)
    {
        // Get the element.
        if (sequenceSummaryAPI)
        {
            if (debugLevel > 0)
                console.log("Element: " + element + "\n");
            elementData = aggregationList[element];
            if (debugLevel > 0)
                console.log("ElementData: " + elementData + "\n");           
        }
        else
        {
            elementData = aggregationList[element];
        }
        if (debugLevel > 1)
            console.log("Element: series = " + seriesName + ", element = " + JSON.stringify(elementData) + "\n");        
        // Get the value of the field we are aggregating on for this element.
        var fieldValue;
        var fieldCount = 0;
        fieldValue = elementData[seriesName];

        if (fieldValue == null) 
        {
            // If it doesn't exist in this element, then keep track of the count 
            // of the data that doesn't have this field. This should be rare, but
            // it can happen if the data models are different and are missing data.
            fieldValue = "NODATA";
            if (aggregateBySequence)
            {
                // If the element is found, and there is a count, get it,
                // otherwise there are no sequences for this element.
                if (!isNaN(elementData[countField]))
                    fieldCount = elementData[countField];
                else
                    fieldCount = 0;
            }
            else fieldCount = 1;
        }
        else
        {
            // If the element is found, extract the count.
            if (aggregateBySequence)
            {
                // If the element is found, and there is a count, get it,
                // otherwise there are no sequences for this element.
                if (!isNaN(elementData[countField]))
                    fieldCount = elementData[countField];
                else
                    fieldCount = 0;
            }
            else fieldCount = 1;
        }

        // Do the aggregation step if we have a count for the field.
        if (fieldCount > 0)
        {
            // If we haven't seen this one before, do an initial assignment
            // otherwise increment the value.
            if (aggregateName[fieldValue] == null)
            {
                // If we haven't seen this field before (it doesn't exist in our 
                // aggregator data structure) then initialize the cound for this
                // field.
                aggregateCount[fieldValue] = fieldCount;
                aggregateName[fieldValue] = fieldValue;
            }
            else
            {
                // If we have seen this field before, increment the count.
                aggregateCount[fieldValue] += fieldCount;
            }
        }

        // Do some debug output if required.
        if (debugLevel > 1)
        {
            var jsonString1 = JSON.stringify(fieldValue);
            var jsonString2 = JSON.stringify(fieldCount);
            console.log("irAggregateData: Found " + aggregateName[fieldValue] + " = " + aggregateCount[fieldValue] + "\n");
        }
        count = count + 1;
    }
    
    // Once we have the fully aggregated data, iterate over the unique
    // aggregate elements and generate the series data with a name and
    // value pair
    count = 0;
    var seriesData = [];
    for (element in aggregateCount)
    {
         if (debugLevel > 0)
             console.log("irAggregateData: Generating series data - " + element + " = " + aggregateCount[element] + "\n");
         seriesData[count] = {name:element,count:aggregateCount[element]};
         count = count + 1;
    }
    if (debugLevel > 0)
        console.log("\n");
    
    // Return the aggregate name/value list.
    return seriesData;
}