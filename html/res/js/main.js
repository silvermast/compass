var _vueHtml, _vueObj;
if (!location.hash) location.href = "/#/";


var routes = {
    '/logout': function() {
        $.get('/api/logout', function(result) {
            Alerts.success(result, {layout: 'topCenter', timeout: 1000});
            LocalDB.del('user');
            location.hash = '#/';
            if (App) {
                App.user = null;
                App.$forceUpdate();
            }
        });
    },
    '/dashboard': function() {
        loadModule('dashboard');
    },
    '/invoice/v/:invoice_id': function(slug) {
        loadModule('invoice', {slug: slug});
    },
    '/invoice/new': function() {
        loadModule('invoice');
    },
    '/': function() {
        location.hash = '#/dashboard';
    },
};

/**
 * Route the request
 */
var router = new Router(routes);
router.init();

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
            alerts: {},
            email: '',
            pass: '',
        };
    },
    methods: {
        loginAttempt: function (e) {
            e.preventDefault();

            var vm = this;

            vm.alerts = {};

            $.post({
                url: '/api/login',
                data: {
                    email: vm.email,
                    pass: vm.pass
                },
                dataType: 'json',
                success: function(result) {
                    LocalDB.set('user', result);
                    vm.$emit('input', result);
                    Alerts.success("Successfully logged in.", {layout: 'topRight', timeout: 1000});
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Alerts.error(jqXHR.responseText, {layout: 'topCenter'});
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
            user: LocalDB.get('user'),
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
    error: function(jqXHR, textStatus, errorThrown) {
        Alerts.error(jqXHR.responseText);
    },
    statusCode: {
        401: function() {
            Alerts.error("You have been logged out.", {layout: 'topCenter'});
            App.user = false;
            LocalDB.del('user');
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
