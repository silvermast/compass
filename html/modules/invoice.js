var _vueObj = {
    data: {
        isBusy: {},
        nav: {},

        query: '',
        index: {
            in_progress: [],
            sent: [],
            // paid: [],
        },
        invoice: {},
        tasks: [],
    },
    created: function() {
        var vm = this;
        vm.loadIndex();
        vm.loadInvoice();
        vm.loadTasks();

        // update the default date every minute for unfinished tasks
        setInterval(function() {
            if (vm.tasks && vm.tasks.length && $('.td-input input:focus', vm.$el).length === 0)
                vm.tasks = vm.tasks.map(vm.formatTask);

        }, 10000);
    },
    watch: {
        "params.slug": function() {
            this.loadInvoice();
        },
    },
    computed: {
        total_worth: function() {
            return this.total_hours * this.invoice.rate;
        },
        total_hours: function() {
            var vm = this;
            return vm.tasks.reduce(function(total, task) {
                return total + (task._elapsed_h ? task._elapsed_h : 0);
            }, 0);
        },
    },
    methods: {

        loadIndex: function() {
            var vm = this;
            $.get({
                dataType: 'json',
                url: '/api/invoice/list',
                data: {status: {$in: objKeys(vm.index)}},
                success: function(result) {

                    var index = {
                        in_progress: [],
                        sent: [],
                        paid: [],
                    };

                    $.map(result, function(item) {
                        if (!index[item.status])
                            index[item.status] = [];

                        index[item.status].push(item);
                    });

                    vm.index = index;
                },
            });
        },
        loadInvoice: function() {
            var vm = this;
            if (!vm.params.slug) {
                vm.invoice = {status: 'in_progress'};
                vm.tasks   = [];
                return;
            }

            $.get({
                dataType: 'json',
                url: '/api/invoice/get?slug=' + vm.params.slug,
                success: function(result) {
                    vm.alerts   = {};
                    result.rate = parseInt(result.rate);
                    vm.invoice  = result;
                    vm.invoice._is_locked = vm.invoice.status !== 'in_progress';

                    vm.loadTasks();
                },
            });
        },

        /**
         * Loads a list of tasks
         */
        loadTasks: function() {
            var vm = this;
            if (!vm.invoice.invoice_id) {
                vm.tasks = [];
                return;
            }

            $.get({
                dataType: 'json',
                url: '/api/task/list?invoice_id=' + vm.invoice.invoice_id,
                success: function(result) {
                    vm.alerts = {};
                    vm.tasks  = $.map(result, vm.formatTask);

                    if (!vm.tasks[0] || vm.tasks[0]._complete)
                        vm.addTask();
                },
            });
        },

        addTask: function() {
            if (this.invoice._is_locked)
                return false;

            this.tasks.unshift(this.formatTask({}));
        },

        /**
         * Generates a Date object rounded to the nearest 15m
         * @returns {Date}
         */
        getDefaultDate: function() {
            var m = moment();
            m.second(0);
            m.minute(Math.round(m.minute() / 15) * 15);
            console.log(m);
            return m;
        },

        /**
         *
         * @param t
         * @returns {*}
         */
        formatTask: function(t) {
            var vm        = this;
            var StartTime = moment(t.start_time || '0000-00-00');
            var EndTime   = moment(t.end_time || '0000-00-00');

            console.log(StartTime.format(), EndTime.format());

            t._time_start_valid = StartTime.isValid();
            t._time_end_valid   = EndTime.isValid();

            if (!StartTime.isValid()) { // if start_time not set, assume we're setting to now
                console.log(StartTime);
                StartTime   = vm.getDefaultDate();
                t._complete = false;
            } else if (!EndTime.isValid()) { // if end_time not set, assume we're setting to now
                EndTime        = vm.getDefaultDate();
                t._complete    = false;
                t._elapsed_est = timeFormat(Math.floor((EndTime - StartTime) / 1000));
            } else { // calculate elapsed time
                t._elapsed   = timeFormat(Math.floor((EndTime - StartTime) / 1000));
                t._elapsed_h = (EndTime - StartTime) / 3600000;
                t._complete  = true;
            }

            if (!t.task_id)
                t._complete = false;

            t._date = StartTime.format('YYYY-MM-DD');
            t._time_start = StartTime.format('HH:mm');

            if (EndTime.isValid())
                t._time_end = EndTime.format('HH:mm');

            return t;
        },

        /**
         * Saves the Invoice, but not the tasks.
         */
        saveInvoice: function() {
            var vm = this;

            $.post({
                dataType: 'json',
                url: '/api/invoice/save',
                data: clone(vm.invoice),
                success: function(result) {
                    vm.loadIndex();

                    if (result.slug === vm.params.slug) {
                        vm.loadInvoice();
                    } else {
                        location.href = '#/invoice/v/' + result.slug;
                    }

                    Alerts.success("Successfully saved invoice '" + vm.invoice.title + "'");
                },
            });
        },

        /**
         * Saves a single task
         * @param task_i array key of task
         */
        saveTask: function(task_i) {
            var vm = this;
            if (!vm.tasks[task_i])
                return;

            var tData = clone(vm.tasks[task_i]);

            tData.start_time = tData._time_start ? (tData._date + 'T' + tData._time_start + ':00') : null;
            tData.end_time   = tData._time_end ? tData._date + 'T' + tData._time_end + ':00' : null;

            tData.invoice_id    = vm.invoice.invoice_id;
            tData.invoice_title = vm.invoice.invoice_title;
            tData.client        = vm.invoice.client;

            $.post({
                dataType: 'json',
                url: '/api/task/save',
                data: tData,
                success: function(result) {
                    var newTask = vm.formatTask(result);
                    vm.$set(vm.tasks, task_i, newTask);

                    if (!vm.tasks[0] || vm.tasks[0]._complete)
                        vm.addTask();

                    Alerts.success("Successfully saved the task '" + newTask.title + "'");
                },
            });
        },

    },
};