<?php
/*********************************************************************
    dashboard.php

    Staff's Dashboard - basic stats...etc.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');
$nav->setTabActive('dashboard');
require(STAFFINC_DIR.'header.inc.php');
//require(STAFFINC_DIR.$page);
?>

<script type="text/javascript" src="js/raphael-min.js"></script>
<script type="text/javascript" src="js/g.raphael.js"></script>
<script type="text/javascript" src="js/g.line-min.js"></script>
<script type="text/javascript" src="js/g.dot-min.js"></script>
<script type="text/javascript" src="js/bootstrap-tab.js"></script>

<link rel="stylesheet" type="text/css" href="css/bootstrap.css"/>

<style type="text/css">
#line-chart-here {
  padding: 0.4em;
  margin-bottom: 1em;
  border-radius: 0.3em;
  border: 0.2em solid #ccc;
background: rgb(246,248,249); /* Old browsers */
background: -moz-linear-gradient(top, rgba(246,248,249,1) 0%,
rgba(229,235,238,1) 50%, rgba(215,222,227,1) 51%, rgba(245,247,249,1) 100%);
/* FF3.6+ */
background: -webkit-gradient(linear, left top, left bottom,
color-stop(0%,rgba(246,248,249,1)), color-stop(50%,rgba(229,235,238,1)),
color-stop(51%,rgba(215,222,227,1)), color-stop(100%,rgba(245,247,249,1)));
/* Chrome,Safari4+ */
background: -webkit-linear-gradient(top, rgba(246,248,249,1)
0%,rgba(229,235,238,1) 50%,rgba(215,222,227,1) 51%,rgba(245,247,249,1)
100%); /* Chrome10+,Safari5.1+ */
background: -o-linear-gradient(top, rgba(246,248,249,1)
0%,rgba(229,235,238,1) 50%,rgba(215,222,227,1) 51%,rgba(245,247,249,1)
100%); /* Opera 11.10+ */
background: -ms-linear-gradient(top, rgba(246,248,249,1)
0%,rgba(229,235,238,1) 50%,rgba(215,222,227,1) 51%,rgba(245,247,249,1)
100%); /* IE10+ */
background: linear-gradient(top, rgba(246,248,249,1) 0%,rgba(229,235,238,1)
50%,rgba(215,222,227,1) 51%,rgba(245,247,249,1) 100%); /* W3C */
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f6f8f9',
endColorstr='#f5f7f9',GradientType=0 ); /* IE6-9 */
}
#line-chart-here tspan {
    font-family: Monaco;
    font-size: 8pt;
}
#line-chart-legend {
    margin: 0.6em;
    line-height: 140%;
}
span.label.disabled {
    opacity: 0.5;
    background-color: #555 !important;
}
span.label {
    cursor: pointer;
}
</style>

<form class="well form-inline" id="timeframe-form">
    <label>
        Report timeframe:
        <input type="text" class="dp input-medium search-query"
            name="start" placeholder="Last month"/>
    </label>
    <button class="btn" type="submit">Refresh</button>
</form>

<!-- Create a graph and fetch some data to create pretty dashboard -->

<div style="position:relative">
    <div id="line-chart-here" style="height:300px"></div>
    <div style="position:absolute;right:0;top:0" id="line-chart-legend"></div>
</div>

<script type="text/javascript">
    var r, previous_data;

    function refresh() {
        $('#line-chart-here').empty();
        $('#line-chart-legend').empty();
        var r = new Raphael('line-chart-here');
        var width = $('#line-chart-here').width()
        var height = $('#line-chart-here').height()
        $.ajax({
            method:     'GET',
            url:        'ajax.php/report/overview/graph',
            data:       ((this.start && this.start.value) ? {'start': this.start.value} : {}),
            dataType:   'json',
            success:    function(json) {
                var previous_data = json,
                    times = [],
                    smtimes = Array.prototype.concat.apply([], json.times),
                    plots = [],
                    max = 0,
                    steps = 0,
                    primes = [2,3,5,7,9];

                // Convert the timestamp to number of whole days after the
                // unix epoch, and try and find an exact multiple of the
                // number of days across the query that is less than 13 for
                // the number of dates to place across the bottom.
                for (key in smtimes) {
                    smtimes[key] = Math.floor(smtimes[key] / 86400);
                }
                steps = smtimes[smtimes.length-1] - smtimes[0] + 1;
                while (steps > 12) {
                    for (var i in primes) {
                        if (steps % primes[i] === 0) {
                            steps /= primes[i];
                            break;
                        }
                    }
                }
                for (key in json.events) {
                    e = json.events[key];
                    if (json.plots[e] === undefined) continue;
                    $('<span>').append(e)
                        .attr({class:'label','style':'margin-left:0.5em'})
                        .appendTo($('#line-chart-legend'));
                    $('<br>').appendTo('#line-chart-legend');
                    times.push(smtimes);
                    plots.push(json.plots[e]);
                    max = Math.max(max, Math.max.apply(Math, json.plots[e]));
                }
                console.log(json.times, smtimes, steps, max);
                m = r.linechart(10, 10, width - 80, height - 20,
                    times, plots, { 
                    gutter: 10,
                    width: 1.6,
                    nostroke: false, 
                    shade: false,
                    axis: "0 0 1 1",
                    axisxstep: steps,
                    axisystep: max,
                    symbol: "circle",
                    smooth: false
                }).hoverColumn(function () {
                    this.tags = r.set();
                    var slots = [];

                    for (var i = 0, ii = this.y.length; i < ii; i++) {
                        if (this.values[i] === 0) continue;
                        if (this.symbols[i].node.style.display == "none") continue;
                        var angle = 160;
                        for (var j = 0, jj = slots.length; j < jj; j++) {
                            if (slots[j][0] == this.x && slots[j][1] == this.y[i]) {
                                angle = 20;
                                break;
                            }
                        }
                        slots.push([this.x, this.y[i]]);
                        this.tags.push(r.tag(this.x, this.y[i],
                            this.values[i], angle,
                            10).insertBefore(this).attr([
                                { fill: '#eee' },
                                { fill: this.symbols[i].attr('fill') }]));
                    }
                }, function () {
                    this.tags && this.tags.remove();
                });
                // Change axis labels from Unix epoch
                $('tspan', $('#line-chart-here')).each(function(e) {
                    var text = this.firstChild.textContent;
                    if (parseInt(text) > 10000)
                        this.firstChild.textContent =
                            $.datepicker.formatDate('mm-dd-yy',
                            new Date(parseInt(text) * 86400000));
                });
                // Dear aspiring API writers, please consider making [easy]
                // things simpler than this...
                $('span.label').each(function(i, e) {
                    e = $(e);
                    e.click(function() {
                        e.toggleClass('disabled');
                        if (e.hasClass('disabled')) {
                            m.symbols[i].hide();
                            m.lines[i].hide();
                        } else {
                            m.symbols[i].show();
                            m.lines[i].show();
                        }
                    });
                });
                $('span.label', '#line-chart-legend').css(
                    'background-color', function(i) {
                        return Raphael.color(m.symbols[i][0].attr('fill')).hex; 
                });
            }
        });
        return false;
    }
    $(refresh);
    $('#timeframe-form').submit(refresh);

    function refresh_dots() {
        var width = $('#line-chart-here').width()
        $.ajax({
            method:     'GET',
            url:        'ajax.php/report/overview/graph',
            data:       {'start': "May-01-2012"},
            dataType:   'json',
            success:    function(json) {
                var xs = [], ys = [], data = [], axisx = [], axisy = [], y = 0;
                for (var key in json.plots) {
                    y++;
                    axisy.push(key);
                    for (var x in json.plots[key]) {
                        xs.push(parseInt(x));
                        ys.push(y);
                        data.push(json.plots[key][x]);
                    }
                }
                width = Math.min(json.times.length * 75 + 50, width - 50);
                r.dotchart(10, 10 , width, 200, xs, ys, data, {
                    symbol: "o", max: 10, heat: true, axis: "0 0 1 1",
                    axisxstep: json.times.length-1,
                    axisystep: axisy.length-1,
                    axisxlabels: json.times, axisxtype: " ",
                    axisytype: " ", axisylabels: axisy
                }).hover(
                    function () {
                        this.marker = this.marker 
                                || r.tag(this.x, this.y, this.value, 0, this.r + 2)
                            .insertBefore(this);
                        this.marker.show();
                    },
                    function () {
                        this.marker && this.marker.hide();
                });
            }
        });
    }
    //$(refresh_dots);

</script>

<ul class="nav nav-tabs" id="tabular-navigation">
</ul>

<div id="table-here"></div>

<script type="text/javascript">

    $(function() {
        $('tabular-navigation').tab();
    });

    // Add tabs for the tabular display
    $(function() {
        $.ajax({
            url:        'ajax.php/report/overview/table/groups',
            dataType:   'json',
            success:    function(json) {
                var first=true;
                for (key in json) {
                    $('#tabular-navigation')
                        .append($('<li>').attr((first) ? {class:"active"} : {})
                        .append($('<a>')
                            .click(build_table)
                            .attr({'table-group':key,'href':'#'})
                            .append(json[key])));
                    first=false;
                }
                build_table.apply($('#tabular-navigation li:first-child a'))
            }
        });
    });

    function build_table(e) {
        $('#table-here').empty();
        $(this).tab('show');
        var group = $(this).attr('table-group')
        $.ajax({
            method:     'GET',
            dataType:   'json',
            url:        'ajax.php/report/overview/table',
            data:       {group: group},
            success:    function(json) {
                var q = $('<table>').attr({class:'table table-condensed table-striped'});
                var h = $('<tr>').appendTo($('<thead>').appendTo(q));
                var pagesize = 25;
                for (var c in json.columns)
                    h.append($('<th>').append(json.columns[c]));
                for (var i in json.data) {
                    if (i % pagesize === 0)
                        b = $('<tbody>').attr({'page':i/pagesize+1}).appendTo(q);
                    row = json.data[i];
                    tr = $('<tr>').appendTo(b);
                    for (var j in row)
                        tr.append($('<td>').append(row[j]));
                }
                $('#table-here').append(q);

                // ----------------------> Pagination <---------------------
                function goabs(e) {
                    $('tbody', q).addClass('hide');
                    if (e.target) {
                        page = e.target.text;
                        $('tbody[page='+page+']', q).removeClass('hide');
                    } else {
                        e.removeClass('hide');
                        page = e.attr('page')
                    }
                    enable_next_prev(page);
                }
                function goprev() {
                    current = $('tbody:not(.hide)', q).attr('page');
                    page = Math.max(1, parseInt(current) - 1);
                    goabs($('tbody[page='+page+']', q));
                }
                function gonext() {
                    current = $('tbody:not(.hide)', q).attr('page');
                    page = Math.min(Math.floor(json.data.length / pagesize) + 1,
                        parseInt(current) + 1);
                    goabs($('tbody[page='+page+']', q));
                }
                function enable_next_prev(page) {
                    $('#table-here div.pagination li[page]').removeClass('active');
                    $('#table-here div.pagination li[page='+page+']').addClass('active');

                    if (page == 1)  $('#report-page-prev').addClass('disabled');
                    else            $('#report-page-prev').removeClass('disabled');

                    if (page == Math.floor(json.data.length / pagesize) + 1)
                                    $('#report-page-next').addClass('disabled');
                    else            $('#report-page-next').removeClass('disabled');
                }

                var p = $('<ul>')
                    .appendTo($('<div>').attr({'class':'pagination'})
                    .appendTo($('#table-here')));
                $('<a>').click(goprev).attr({'href':'#'})
                    .append('&laquo;').appendTo($('<li>').attr({'id':'report-page-prev'})
                    .appendTo(p));
                $('tbody', q).each(function() {
                    page = $(this).attr('page');
                    $('<a>').click(goabs).attr({'href':'#'}).append(page)
                        .appendTo($('<li>').attr({'page':page})
                        .appendTo(p));
                });
                $('<a>').click(gonext).attr({'href':'#'})
                    .append('&raquo;').appendTo($('<li>').attr({'id':'report-page-next'})
                    .appendTo(p));

                // ------------------------> Export <-----------------------
                $('<a>').attr({'href':'ajax.php/report/overview/table/export?group='
                        +group}).append('Export')
                    .appendTo($('<li>')
                    .appendTo(p));

                gonext();
            }
        });
        return false;
    }
</script>

<?
include(STAFFINC_DIR.'footer.inc.php');
?>
