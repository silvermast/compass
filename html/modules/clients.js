var _vueObj = {
    mixins: [authMixin],
    data: {
        isBusy: {},
        nav: {},

        query: '',
        index: [],
        client: {},
        editingNotes: false,
    },
    watch: {
        "params.slug": function() {
            var vm = this;
            vm.$nextTick(function() {
                vm.loadClient();
            });

            // mobile handling of sidebar display
            $toggleIndex = $('.toggle-index', vm.$el);
            if ($toggleIndex.is(':visible')) {
                $toggleIndex.trigger('click');
            }
        },
        client: function(newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                // this.loadInvoices();
            }
        },
    },
    methods: {
        init: function() {
            var vm = this;
            vm.loadIndex();
            vm.loadClient();
        },

        /**
         * Loads the sidebar index
         */
        loadIndex: function() {
            var vm = this;

            $.get({
                dataType: 'json',
                url: '/api/client/list',
                success: function(result) {
                    vm.index = result;
                },
            });
        },

        /**
         * Loads the invoice selected
         */
        loadClient: function() {
            var vm = this;
            if (!vm.params.slug) {
                vm.client = {};
                return;
            }

            $.get({
                dataType: 'json',
                url: '/api/client/get?slug=' + vm.params.slug,
                success: function(result) {
                    vm.client = result;
                },
            });
        },

        /**
         * Saves the Client
         */
        saveClient: function() {
            var vm = this;

            $.post({
                dataType: 'json',
                url: '/api/client/save',
                data: clone(vm.client),
                success: function(result) {
                    vm.loadIndex();

                    if (result.slug === vm.params.slug) {
                        vm.loadClient();
                    } else {
                        location.href = '#/clients/v/' + result.slug;
                    }

                    Alerts.success("Successfully saved client '" + vm.client.name + "'");
                },
            });
        },

    },
};