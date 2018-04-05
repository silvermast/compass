var _vueObj = {
    data: {
        isBusy: {},
        nav: {},

        query: '',
        index: {
            in_progress: [],
            sent: [],
            paid: [],
        },
        invoice: {},
        tasks: [],
    },
    created: function() {
        this.init();
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
        init: function() {
            var vm = this;
            vm.loadIndex();
            vm.loadInvoice();

            // update the default date every minute for unfinished tasks
            Interval.clear();
            Interval.set(function() {
                if (vm.tasks && vm.tasks.length && $('.td-input input:focus', vm.$el).length === 0)
                    vm.tasks = vm.tasks.map(vm.formatTask);

            }, 10000);
        },

        /**
         * Loads the sidebar index
         */
        loadIndex: function() {
            var vm = this;

            function sortInvoice(a, b) {
                return a.client + ' - ' + a.title > b.client + ' - ' + b.title;
            }

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

                    index.in_progress.sort(sortInvoice);
                    index.sent.sort(sortInvoice);
                    index.paid.sort(sortInvoice);

                    vm.index = index;
                },
            });
        },

        /**
         * Loads the invoice selected
         */
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

            t._time_start_valid = StartTime.isValid();
            t._time_end_valid   = EndTime.isValid();

            if (!StartTime.isValid()) { // if start_time not set, assume we're setting to now
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

        saveInvoiceDelayed: function() {
            Timeout.clear(this._saveInvoiceTimeout);
            this._saveInvoiceTimeout = Timeout.set(this.saveInvoice, 1000);
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


        /**
         * Deletes a single task
         * @param task_i array key of task
         */
        deleteTask: function(task_i) {
            var vm = this;
            if (!vm.tasks[task_i])
                return;

            if (!confirm('Are you sure you want to delete this task?'))
                return;

            var tData = clone(vm.tasks[task_i]);

            tData.start_time = tData._time_start ? (tData._date + 'T' + tData._time_start + ':00') : null;
            tData.end_time   = tData._time_end ? tData._date + 'T' + tData._time_end + ':00' : null;

            tData.invoice_id    = vm.invoice.invoice_id;
            tData.invoice_title = vm.invoice.invoice_title;
            tData.client        = vm.invoice.client;

            $.post({
                dataType: 'json',
                url: '/api/task/delete',
                data: tData,
                success: function(result) {
                    var newTask = vm.formatTask(result);
                    vm.$delete(vm.tasks, task_i);
                    Alerts.success("Successfully deleted the task '" + newTask.title + "'");
                },
            });
        },

    },
};