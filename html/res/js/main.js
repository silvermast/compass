var _vueHtml, _vueObj;
if (!location.hash) location.href = "/#/";


var routes = {
    '/logout': function() {
        $.get('/api/logout', function(result) {
            Alerts.success("Successfully logged out", {layout: 'topCenter', timeout: 1000});
            if (App) {
                App.user = null;
            }
            location.hash = '#/';
        });
    },
    '/reports': function() {
        loadModule('reports');
    },
    '/clients': function() {
        loadModule('clients');
    },
    '/clients/new': function() {
        loadModule('clients');
    },
    '/clients/v/:invoice_id': function(slug) {
        loadModule('clients', {slug: slug});
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

        Timeout.clear();
        Interval.clear();

        // don't cache this selector. Creating a new Vue instance clones it and the ref becomes stale.
        $('#vue-app').html(_vueHtml);

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
            email: '',
            pass: '',
        };
    },
    methods: {
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
var authMixin = {
    data: function() {
        return {
            _hasLoaded: false,
            _authTimeout: null,
            _authXHR: null,
            user: null,
        }
    },
    watch: {
        user: function(newVal, oldVal) {
            if (this._hasLoaded && newVal && !oldVal) {
                this.checkAuth(this._callInit);
            }
        }
    },
    created: function() {
        var vm = this;
        vm.checkAuth(vm._callInit);
    },
    methods: {
        _callInit: function() {
            var vm = this;
            vm.$nextTick(function() {
                vm._hasLoaded = true;
                vm.user && vm.init && vm.init();
            });
        },

        /**
         * Checks the user's auth status
         */
        checkAuth: function(done) {
            var vm = this;

            Timeout.clear(vm._authTimeout);

            if (vm._authXHR) {
                vm._authXHR.abort();
                vm._authXHR = null;
            }

            vm._authXHR = $.getJSON({
                url: '/api/user/me',
                success: function(result) {
                    vm.user = result;
                    done && done();
                    vm._authTimeout = Timeout.set(vm.checkAuth, 10000);
                },
                error: function(jqXHR) {
                    vm.user = null;
                },
                done: function() {
                    vm._authXHR = null;
                },
            });
        },

    }
};

Vue.mixin({
    data: function() {
        return {
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
