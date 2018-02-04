var _vueObj = {
    data: {
        isBusy: true,
        filter: {
            dateRange: {
                value: LocalDB.get('dashboard.filter.dateRange') || 'thisMonth',
                options: {
                    thisMonth: 'This Month',
                    lastMonth: 'Last Month',
                    last3Months: 'Last 3 Months',
                    last6Months: 'Last 6 Months',
                    last12Months: 'Last 12 Months',
                    thisYear: new Date().getUTCFullYear(),
                    lastYear: new Date().getUTCFullYear() - 1,
                },
            },
        },
        tasks: {},
        invoices: {},

        totalEarnings: 0,
        totalHours: 0,
    },
    mounted: function() {
        var vm = this;
        Load.chartjs(function() {
            vm.loadTasks();
        });
    },
    watch : {
        'filter.dateRange.value': function(newVal) {
            LocalDB.set('dashboard.filter.dateRange', newVal);
            this.loadTasks();
        },
    },
    methods: {

        /**
         * Loads a list of tasks
         */
        loadTasks: function() {
            var vm    = this;
            var dates = dateRangeFromKey(vm.filter.dateRange.value);

            vm.isBusy = true;

            $.post({
                url: '/api/task/list',
                data: {
                    start_time: {$gt: dates[0].toJSON(), $lt: dates[1].toJSON()}
                },
                dataType: 'json',
                success: function(result) {
                    vm.tasks = result.reduce(function(map, task) {
                        if (task.invoice_id === null)
                            return map;

                        if (!vm.invoices[task.invoice_id])
                            vm.invoices[task.invoice_id] = {};

                        map[task.task_id] = vm.formatTask(task);
                        return map;
                    }, {});

                    vm.loadInvoices();
                },
            });
        },

        /**
         * Loads the invoices
         */
        loadInvoices: function() {
            var vm = this;

            $.post({
                url: '/api/invoice/list',
                data: {
                    invoice_id: {$in: Object.keys(vm.invoices)}
                },
                dataType: 'json',
                success: function(result) {
                    result.map(function(invoice) {
                        vm.invoices[invoice.invoice_id] = invoice;
                    });
                    vm.render();
                },
            });
        },

        /**
         * Renders all report data
         */
        render: function() {
            this.renderTotalEarnings();
            this.renderBarChart();
            this.calculateAggregates();

            this.isBusy = false;
        },

        /**
         * Sums up text-based report data
         */
        calculateAggregates: function() {
            var vm = this;

            vm.totalEarnings = objValues(vm.tasks).reduce(function(total, t) {
                return total + vm.getTaskWorth(t);
            }, 0);

            vm.totalHours = objValues(vm.tasks).reduce(function(total, t) {
                return total + (t._elapsed_h || 0);
            }, 0);
        },

        /**
         * Renders the pie chart for last month's data
         */
        renderTotalEarnings: function() {
            var vm = this,
                $chart = $(vm.$el).find('#total-earnings-chart');

            if ($chart.data('chart')) {
                $chart.data('chart').destroy();
            }

            // calculate totals
            var Totals = objValues(vm.tasks).reduce(function(totals, t) {
                if (!t.start_time)
                    return totals;

                if (!totals[t.client])
                    totals[t.client] = 0;
                totals[t.client] += vm.getTaskWorth(t);

                return totals;
            }, {});

            var labels = objKeys(Totals),
                colors = labels.map(function(label) {
                    return '#' + md5(label).substr(4, 6);
                });

            // chart data object
            $chart.loadChartJS({
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: objValues(Totals),
                        backgroundColor: colors,
                    }],
                    labels: labels,
                },
                options: {
                    legend: {display: false},
                },
            });
        },

        /**
         * Renders a line chart in the "Trends" area
         */
        renderBarChart: function() {
            var vm = this,
                $chart = $(vm.$el).find('#trends-chart');

            if (!$chart.data('chart')) {
                $chart.loadChartJS({
                    type: 'bar',
                    data: {
                        datasets: [],
                        labels: [],
                    },
                    options: {
                        legend: {display: false},
                        responsive: true,
                        scales: {
                            xAxes: [{
                                stacked: true,
                            }],
                            yAxes: [{
                                stacked: true,
                            }]
                        }
                    },
                    tooltips: {
                        mode: 'index',
                        intersect: false
                    },
                });
            }

            var _formatDate,
                filterDates = dateRangeFromKey(vm.filter.dateRange.value);
            ;
            switch (vm.filter.dateRange.value) {
                case 'thisMonth':
                case 'lastMonth':
                    _formatDate = function(date) {
                        return date.toDateString().split(' ').splice(1,3).join(' ');
                    };

                    zeroData    = dateRange(filterDates[0], filterDates[1]).reduce(function(result, date) {
                        var d = _formatDate(date);
                        result[d] = 0;
                        return result;
                    }, {});
                    break;

                case 'last3Months':
                case 'last6Months':
                case 'last12Months':
                case 'thisYear':
                case 'lastYear':
                    _formatDate = function(date) {
                        return Months[date.getMonth()];
                    };
                    zeroData = dateRange(filterDates[0], filterDates[1], 'month').reduce(function(result, date) {
                        var d = _formatDate(date);
                        result[d] = 0;
                        return result;
                    }, {});
                    break;
            }

            // calculate totals
            var DataSets = objValues(vm.tasks).reduce(function(totals, t) {
                if (!t.start_time)
                    return totals;

                if (!totals[t.client]) {
                    totals[t.client] = {
                        label: t.client,
                        backgroundColor: strToColor(t.client),
                        data: clone(zeroData),
                    };
                }

                // Aggregate data points
                var task_date = _formatDate(t._start);

                if (!totals[t.client].data[task_date])
                    totals[t.client].data[task_date] = 0;

                totals[t.client].data[task_date] += vm.getTaskWorth(t);

                return totals;
            }, {});

            $chart.data('chart').data = {
                datasets: objValues(DataSets).map(function(set) {
                    set.data = objValues(set.data);
                    return set;
                }),
                labels: objKeys(zeroData),
            };
            $chart.data('chart').update();

        },

        /**
         * Mapper function for formatting task data
         * @param t
         * @returns {number}
         */
        formatTask: function(t) {
            var vm = this;

            if (!t._start && t.start_time)
                t._start = new Date(t.start_time);
            if (!t._end && t.end_time)
                t._end = new Date(t.end_time);
            if (!t._elapsed_h && t.end_time && t.start_time)
                t._elapsed_h = (t._end - t._start) / 3600000;

            return t;
        },

        /**
         * @param t
         * @return Number
         */
        getTaskWorth: function(t) {
            var vm = this;
            t = vm.formatTask(t);

            if (!t.start_time || !t.end_time || !t.invoice_id || !vm.invoices[t.invoice_id])
                return 0;

            var invoice = vm.invoices[t.invoice_id];

            if (!invoice.rate)
                return 0;
            else if (!invoice.rate.toFixed)
                invoice.rate = parseFloat(invoice.rate);

            if (!t._worth)
                t._worth = t._elapsed_h * invoice.rate;

            return t._worth;
        },

    },
};