var profilerBarCharts = function (fetchUrl) {

    var that = this;

    var margin = {top: 10, right: 10, bottom: 55, left: 60};
    height = 400 - margin.top - margin.bottom;
    width = $('.chart-container').width() - margin.left - margin.right;

    var color = d3.scale.category20().range();

    var svg = {'db': {}, 'time': {}, 'memory': {}};
    var axes = {'db': {}, 'time': {}, 'memory': {}};
    var zooms = {'db': {}, 'time': {}, 'memory': {}};
    var options = {
        'db': {
            'yDomain': function (data) {
                return [0, d3.max(data, function(d) { return d.time; })];
            },
            'preProcessDataForGraph': function (data) {
                data.forEach(function(d) {
                    var y0 = 0;
                    var colorIndex = 0;
                    d.queries.forEach(function(q) {
                        q.y0 = y0;
                        q.y1 = y0 += q.elapsed;
                        q.color = color[(colorIndex += 1) % 20];
                    });
                });
            },
            'getProfileDataValue': function(d) { return d.queries; },
            'getProfileDataBinder': function(d) {return d.start;},
            'profileClickHandler': function(d) {
                var container = $('#legend-db');
                $("#type", container).html(d.type);
                $("#parameters", container).html(JSON.stringify(d.parameters));
                $("#sql", container).html(d.sql);
                $("#duration", container).html(d.y1 - d.y0);
            }
        },
        'time': {
            'yDomain': function (data) {
                return [0, d3.max(data, function(d) {
                    var sum = 0;
                    for (var e in d.events) {
                        sum += d.events[e].elapsed;
                    }
                    return sum;
                })];
            },
            'preProcessDataForGraph': function (data) {
                data.forEach(function(d) {
                    var y0 = 0;
                    var colorIndex = 0;
                    d.events.forEach(function(q) {
                        q.y0 = y0;
                        q.y1 = y0 += q.elapsed;
                        q.color = color[(colorIndex += 1) % 20];
                    });
                });
            },
            'getProfileDataValue': function(d) { return d.events; },
            'getProfileDataBinder': function(d) {return d.name + d.target + d.file + d.line + d.time;},
            'profileClickHandler': function(d) {
                var container = $('#legend-time');
                $("#name", container).html(d.name);
                $("#target", container).html(d.target);
                $("#file", container).html(d.file + ':' + d.line);
                $("#elapsed", container).html(d.elapsed);
            }
        },
        'memory': {
            'yDomain': function (data) {
                return [0, d3.max(data, function(d) { return d.events[d.events.length - 1].memory;})];
            },
            'preProcessDataForGraph': function (data) {
                data.forEach(function(d) {
                    var y0 = 0;
                    var colorIndex = 0;
                    d.events.forEach(function(q) {
                        q.y0 = y0;
                        q.y1 = y0 += q.difference;
                        q.color = color[(colorIndex += 1) % 20];
                    });
                });
            },
            'getProfileDataValue': function(d) { return d.events; },
            'getProfileDataBinder': function(d) {return d.name + d.target + d.file + d.line + d.memory;},
            'profileClickHandler': function(d) {
                var container = $('#legend-memory');
                $("#name", container).html(d.name);
                $("#target", container).html(d.target);
                $("#file", container).html(d.file + ':' + d.line);
                $("#difference", container).html(d.difference);
            }
        },
    };

    var updateRequestLegend = function (d) {
        var report = that.data.report;
        for (k in report) {
            if (report[k].run === d.run) {
                var container = $('#legend-request');
                var date = new Date(report[k].run * 1000);
                $("#ip", container).html(report[k].ip);
                $("#method", container).html(report[k].method);
                $("#time", container).html(report[k].time.date + report[k].time.timezone);
                $("#uri", container).html(report[k].uri);
                $("#run", container).html(date.toISOString());
                break;
            }
        }
        var request = that.data.request;
        for (k in request) {
            if (request[k].run === d.run) {
                var container = $('#legend-controller');
                $("#controller", container).html(request[k].controller);
                $("#route", container).html(request[k].route);
                $("#templates", container).html(JSON.stringify(request[k].templates));
                break;
            }
        }
    };

    var zoomed = function (graph) {
        svg[graph].select(".y.axis").call(axes[graph].yAxis);
        svg[graph].selectAll(".chart rect").attr("transform", "translate(0, " + d3.event.translate[1] + ")scale(1, " + d3.event.scale + ")");
    }

    this.initAxes = function() {
        for (var k in axes) {
            axes[k].x     = d3.scale.ordinal().rangeRoundBands([0, width], .1);
            axes[k].y     = d3.scale.linear().range([height, 0]);
            axes[k].xAxis = d3.svg.axis().scale(axes[k].x).orient("bottom").tickFormat(function(d) {var date = new Date(d * 1000); return date.toLocaleTimeString()});
            axes[k].yAxis = d3.svg.axis().scale(axes[k].y).orient("left");
        }
    };

    this.initChart = function() {
        d3.selectAll(".chart")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        for (var k in svg) {
            svg[k] = d3.select('#chart-' + k + ' > g');
        }

        this.initAxes();
        this.fetchData(fetchUrl);
        this.appendAxes();
    };

    this.fetchData = function(fetchUrl) {
        d3.json(fetchUrl, function(error, data) {
            $('#result-count').html(data.count);
            if (data.count === 0) {
                return false;
            }
            that.data = data;
            for (graph in svg) {
                that.updateGraph(graph, data[graph]);
            }
        });
    };

    this.updateGraph = function (graph, data) {
        axes[graph].x.domain(data.map(function(d) { return d.run; }));
        axes[graph].y.domain(options[graph].yDomain(data));

        // Y axis zoom
        zooms[graph] = d3.behavior.zoom()
        .y(axes[graph].y)
        .scaleExtent([1, 32])
        .on("zoom", (function(graph) { return function() {return zoomed(graph)}})(graph));

        if (typeof options[graph].preProcessDataForGraph !== 'undefined') {
            options[graph].preProcessDataForGraph(data);
        }

        // Runs container
        var run = svg[graph].selectAll(".run").data(data, function(d) {return d.run;});

        run.enter()
        .append("g")
        .call(zooms[graph])
        .on("click", updateRequestLegend)
        .transition().duration(300).ease("linear")
        .attr("class", "run")
        .attr("transform", function(d) { return "translate(" + axes[graph].x(d.run) + ",0)"; });

        run.append("svg:title").text(function(d) {return d.run;});

        // run profiles
        var profile = run.selectAll("rect")
        .data(options[graph].getProfileDataValue, options[graph].getProfileDataBinder);

        profile.enter().append("rect")
        .attr("width", axes[graph].x.rangeBand())
        .attr("y", function(d) { return axes[graph].y(d.y1); })
        .attr("height", function(d) { return axes[graph].y(d.y0) - axes[graph].y(d.y1); })
        .style("fill", function(d) { return d.color; })
        .on("click", options[graph].profileClickHandler);

        run.exit()
        .transition().duration(300).ease("linear")
        .style("opacity", 0)
        .remove();
        profile.exit().remove();

        svg[graph].select(".y.axis")
        .transition().duration(1500).ease("sin-in-out")
        .call(axes[graph].yAxis);

        svg[graph].select(".x.axis")
        .transition().duration(500).ease("sin-in-out")
        .call(axes[graph].xAxis);

        svg[graph]
        .selectAll(".x.axis text")
        .style("text-anchor", "end")
        .attr("dx", "-.8em")
        .attr("dy", ".15em")
        .attr("transform", "rotate(-45)")
    };

    this.appendAxes = function() {
        for (var k in svg) {
            svg[k].append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + height + ")")
            .call(axes[k].xAxis);
        }

        svg['db'].append("g")
        .attr("class", "y axis")
        .call(axes.db.yAxis)
        .append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 6)
        .attr("dy", ".71em")
        .style("text-anchor", "end")
        .text("Elapsed Time");

        svg['time'].append("g")
        .attr("class", "y axis")
        .call(axes.time.yAxis)
        .append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 6)
        .attr("dy", ".71em")
        .style("text-anchor", "end")
        .text("Elapsed Time");

        svg['memory'].append("g")
        .attr("class", "y axis")
        .call(axes.memory.yAxis)
        .append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 6)
        .attr("dy", ".71em")
        .style("text-anchor", "end")
        .text("Memory usage");
    };

    this.initChart();

};
