$(document).ready(function() {
    $('.chart').each(function() {
        var chart = $(this),
            chartData = chart.data('chartData'),
            data = chartData.data,
            title = chartData.title,
            chartConfig = generateChartConfig(data, title);
        
        chart.highcharts(chartConfig);
    });
});


function generateChartConfig(data, fieldTitle)
{
    var level = 3;
    var internalLabels = true;
    var truncateLabels = 10;
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
    var fieldTitleWithNumber = n + ' ' + pluralize(fieldTitle, n);

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
