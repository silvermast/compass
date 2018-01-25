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

        StatusOptions: {
            types: {
                in_progress: 'In Progress',
                sent: 'Sent',
                paid: 'Paid',
            },
            colors: {
                in_progress: 'warning',
                sent: 'success',
                paid: 'default',
            },
        },
    },
    created: function() {
        this.loadIndex();
        this.loadInvoice();
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
            var d = new Date();
            d.setSeconds(0);
            d.setMinutes(Math.round(d.getMinutes() / 15) * 15);
            return d;
        },

        /**
         *
         * @param t
         * @returns {*}
         */
        formatTask: function(t) {
            if (!t.start_time) { // if start_time not set, assume we're setting to now
                t.start_time = this.getDefaultDate();
                t._complete  = false;
            } else if (!t.end_time) { // if end_time not set, assume we're setting to now
                t.start_time = new Date(t.start_time);
                t.end_time   = this.getDefaultDate();
                t._complete  = false;
            } else { // calculate elapsed time
                t.start_time = new Date(t.start_time);
                t.end_time   = new Date(t.end_time);
                t._elapsed   = timeFormat(Math.floor((t.end_time - t.start_time) / 1000));
                t._elapsed_h = (t.end_time - t.start_time) / 3600000;
                t._complete  = true;
            }

            if (!t.task_id)
                t._complete = false;

            t._date = t.start_time.toISOString().split('T')[0];
            t._time_start = getTimeString(t.start_time);

            if (t.end_time)
                t._time_end = getTimeString(t.end_time);

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
            tData.start_time = tData._date + 'T' + tData._time_start + ':00';

            if (tData._time_end)
                tData.end_time = tData._date + 'T' + tData._time_end + ':00';

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