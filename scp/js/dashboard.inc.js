(function ($) {
    $.drawPlots = function(json) {
        $('#line-chart-here').empty();
        $('#line-chart-legend').empty();
        var r = new Raphael('line-chart-here'),
            width = $('#line-chart-here').width(),
            height = $('#line-chart-here').height();
        var times = [],
            smtimes = Array.prototype.concat.apply([], json.times),
            plots = [],
            max = 0;

        // Convert the timestamp to number of whole days after the
        // unix epoch.
        for (key in smtimes) {
            smtimes[key] = Math.floor(smtimes[key] / 86400);
        }
        for (key in json.events) {
            e = json.events[key];
            if (json.plots[e] === undefined) continue;
            $('<span>').append(e)
                .attr({'class':'label','style':'margin-left:0.5em'})
                .appendTo($('#line-chart-legend'));
            $('<br>').appendTo('#line-chart-legend');
            times.push(smtimes);
            plots.push(json.plots[e]);
            // Keep track of max value from any plot
            max = Math.max(max, Math.max.apply(Math, json.plots[e]));
        }
        m = r.linechart(20, 0, width - 70, height,
            times, plots, {
            gutter: 20,
            width: 1.6,
            nostroke: false,
            shade: false,
            axis: "0 0 1 1",
            axisxstep: 8,
            axisystep: Math.min(12, max),
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
                    if (slots[j][0] == this.x
                            && Math.abs(slots[j][1] - this.y[i]) < 20) {
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
        var qq = setInterval(function() {
            if ($.datepicker === undefined)
                return;
            clearInterval(qq);
            $('tspan', $('#line-chart-here')).each(function(e) {
                var text = this.firstChild.textContent;
                if (parseInt(text) > 10000)
                    this.firstChild.textContent =
                        $.datepicker.formatDate('mm-dd-yy',
                        new Date(parseInt(text) * 86400000));
            });
        }, 50);
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
        // Dear aspiring API writers, please consider making [easy]
        // things simpler than this...
        $('span.label', '#line-chart-legend').css(
            'background-color', function(i) {
                return Raphael.color(m.symbols[i][0].attr('fill')).hex;
        });
    };
})(window.jQuery);
