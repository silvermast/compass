var _vueHtml, _vueObj;
if (!location.hash) location.href = "/#/";


var routes = {
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
var router = new Router(routes);
router.init();


/**
 * Globally loaded NavBar
 * @type {Vue}
 */
var NavBar = new Vue({
    el: '#vue-nav-bar',
    data: {
        modules: {
            invoice: {
                title: 'Invoices',
                href: '#/invoice/list',
            },
        }
    },
    methods: {
        isActive: function(module) {
            return location.hash.indexOf(module.href) === 0;
        },
    },
});

/**
 * Clears the page and puts in a 'not found' alert
 */
function pageNotFound() {
    $app.html("<div class='alert alert-danger'>404: Page not found.</div>");
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

        NavBar.$forceUpdate();
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
Vue.component('x-alerts', {template: '#x-alerts-template', props: {alerts: Object}});
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
                    LocalDB.set('user', result.user);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    vm.alerts.error = jqXHR.responseText;
                }
            });

        },
    }
});

var vue_container = new Vue({
    el: '#page-container',
});


/**
 * jQuery Events
 */

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


/**
 * Structs
 */
var InvoiceStatus = {
    default:     {name: 'Default', color: 'default'},
    ignore:      {name: 'Ignore', color: 'info'},
    watch:       {name: 'Watch', color: 'warning'},
    negotiating: {name: 'Negotiating', color: 'success'},
    owned:       {name: 'Owned', color: 'primary'},
};

var TaskStatus = {
    // queued:      {name: 'Queued', color: 'info'},
    // in_progress: {name: 'In Progress', color: 'warning'},
    // complete:    {name: 'Complete', color: 'success'},
    // failed:      {name: 'Failed', color: 'danger'},
};