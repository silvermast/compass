var _vueHtml, _vueObj;
if (!location.hash) location.href = "/#/";


var routes = {
    '/logout': function() {
        $.get('/api/logout', function(result) {
            Alerts.success("Successfully logged out", {layout: 'topCenter', timeout: 1000});
            location.hash = '#/';
            if (App) {
                App.user = null;
                App.$forceUpdate();
            }
        });
    },
    '/reports': function() {
        loadModule('reports');
    },
    '/invoice/v/:invoice_id': function(slug) {
        loadModule('invoice', {slug: slug});
    },
    '/invoice/new': function() {
        loadModule('invoice');
    },
    '/': function() {
        location.hash = '#/invoice/new';
    },
};

/**
 * Route the request
 */
var router = new Router(routes).configure({
    notfound: function() {
        console.log('Page Not Found');
        location.hash = '#/';
        loadModule('/');
    },
});
router.init();

/**
 * @type {{alert: Alerts.alert, success: Alerts.success, error: Alerts.error, warning: Alerts.warning, info: Alerts.info}}
 */
var Alerts = {
    alert: function(obj) {
        new Noty(obj).show();
    },
    success: function(msg, opts) {
        this.alert($.extend({}, {type: 'success', text: msg}, opts));
    },
    error: function(msg, opts) {
        this.alert($.extend({}, {type: 'error', text: msg}, opts));
    },
    warning: function(msg, opts) {
        this.alert($.extend({}, {type: 'warning', text: msg}, opts));
    },
    info: function(msg, opts) {
        this.alert($.extend({}, {type: 'info', text: msg}, opts));
    },
};

Noty.overrideDefaults({
    closeWith: ['click', 'button'],
    timeout: 5000,
    layout: 'bottomRight',
    theme: 'none',
});


/**
 * Clears the page and puts in a 'not found' alert
 */
function pageNotFound() {
    $('#vue-app').html("<div class='alert alert-danger'>404: Page not found.</div>");
}

/**
 * @param module
 * @param params
 */
function loadModule(module, params) {
    if (module[0] === '/')
        module = module.substr(1);

    if (module === window.module) {
        App.params = params || {};
        App.$forceUpdate();
        return;
    }

    window.module = module;
    var waiting = 2;

    function done() {
        if (waiting) return;

        // don't cache this selector. Creating a new Vue instance clones it and the ref becomes stale.
        $('#vue-app').html(_vueHtml);

        // console.log('loadModule', module, (params || {}));
        _vueObj.data.params = params || {};
        _vueObj.el = '#vue-app';

        if (window.App && window.App.$destroy)
            window.App.$destroy();

        window.App = new Vue(_vueObj);
    }

    $.get({
        url: '/modules/' + module + '.html',
        success: function(html) {
            waiting--;
            _vueHtml = html;
            done();
        },
        error: pageNotFound,
    });

    $.getScript('/modules/' + module + '.js', function(js) {
        waiting--;
        done();
    });
}

/**
 * Vue Templates
 */
Vue.component('x-loader', {template: '#x-loader-template'});
Vue.component('x-owner-nav', {template: '#x-owner-nav-template', props: ['selected']});
Vue.component('x-login-form', {
    template: '#x-login-form',
    data: function() {
        return {
            isBusy: true,
            email: '',
            pass: '',
        };
    },
    created: function() {
        this.checkAuth();
    },
    methods: {
        /**
         * Checks the user's auth status
         */
        checkAuth: function() {
            var vm = this;
            $.getJSON({
                url: '/api/user/me',
                success: function(result) {
                    vm.$emit('input', result);
                    Timeout.set(vm.checkAuth, 300000);
                    vm.isBusy = false;
                },
                error: function(jqXHR) {
                    vm.$emit('input', null);
                    vm.email = '';
                    vm.pass  = '';
                    vm.isBusy = false;
                }
            });
        },

        /**
         * Attempts to log the user in
         * @param e
         */
        loginAttempt: function (e) {
            e.preventDefault();

            var vm = this;

            $.post({
                url: '/api/login',
                data: {
                    email: vm.email,
                    pass: vm.pass
                },
                dataType: 'json',
                success: function(result) {
                    vm.$emit('input', result);
                    Alerts.success("Successfully logged in.", {layout: 'topRight', timeout: 1000});
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Alerts.error(json_decode(jqXHR.responseText) || jqXHR.responseText, {layout: 'topCenter'});
                    console.error(jqXHR.responseText);
                }
            });

        },
    }
});

/**
 * Default vue options & methods
 */
Vue.mixin({
    data: function() {
        return {
            user: null,
            params: {},
            alerts: {},
            nav: {
                left: [],
                right: [],
            },

            InvoiceStatusOptions: {
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
            }
        }
    },
    watch: {
        user: function(newVal) {
            var vm = this;
            vm.$nextTick(function() {
                newVal && vm.init && vm.init();
            });
        }
    },
    created: function() {
        var vm = this;
        vm.$nextTick(function() {
            vm.user && vm.init && vm.init();
        });
    },
});



/**
 * jQuery Events
 */
$.ajaxSetup({
    statusCode: {
        401: function() {
            App.user = null;
        },
    }
});

$(document).on('click', '[href]', function(e) {
    var href = $(this).attr('href');

    if (href === '#null') {
        e.preventDefault();

    } else if (href[0] === '#' && !$(this).is('a')) {
        location.href = href;

    }
});
$(document).on('click', '.btn', function() {
    $(this).blur();
});
