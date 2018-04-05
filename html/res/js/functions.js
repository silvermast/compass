/**
 * Local Storage database
 * @type {{set: DB.set, get: DB.get, del: DB.del}}
 */
var LocalDB = {
    set: function(key, val) {
        localStorage.setItem(key, JSON.stringify(val));
    },
    get: function(key) {
        return JSON.parse(localStorage.getItem(key));
    },
    del: function(key) {
        localStorage.removeItem(key);
    },
};

/**
 * Wrapper class for setInterval.
 * @type {{_cache: {}, set: Interval.set, clear: Interval.clear}}
 */
var Interval = {
    _cache: {},

    set: function(fn, timeout) {
        var id = setInterval(fn, timeout);
        this._cache[id] = id;
    },

    clear: function(id) {
        if (id === undefined) {
            $.map(this._cache, clearInterval);
            this._cache = {}

        } else {
            clearInterval(id);
            delete this._cache[id];
        }
    }
};

/**
 * Wrapper class for setTimeout
 * @type {{_cache: {}, set: Timeout.set, clear: Timeout.clear}}
 */
var Timeout = {
    _cache: {},

    set: function(fn, timeout) {
        var id = setTimeout(fn, timeout);
        this._cache[id] = id;
    },

    clear: function(id) {
        if (id === undefined) {
            $.map(this._cache, clearTimeout);
            this._cache = {}

        } else {
            clearTimeout(id);
            delete this._cache[id];
        }
    }
};

/**
 * Loads external libraries
 * @type {{_js: {}, _css: {}, javascript: Load.javascript, css: Load.css}}
 */
var Load = {
    _js: {},
    _css: {},

    javascript: function(script, donefn) {
        if (this._js[script]) return donefn();
        this._js[script] = true;
        $.getScript(script, donefn);
    },

    css: function(script, donefn) {
        if (this._css[script]) return;
        this._css[script] = true;
        $('head').append('<link rel="stylesheet" href="' + script + '" />');
    },

    chartjs: function(donefn) {
        this.javascript('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.min.js', donefn);
    },
};

function getDateTime(timestamp) {
    if (!timestamp || timestamp < 2000)
        return '';

    var d = new Date(timestamp * 1000);
    return d.toLocaleString()
}

function numberFormat(num, precision) {
    num = isNumber(num) && !isNaN(num) ? num : 0;
    precision = precision === undefined ? 2 : precision;
    return parseFloat(num).toFixed(precision)
}
function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
function clone(obj) {
    return $.extend(true, {}, obj);
}

/**
 * @param obj
 * @returns {*}
 */
function count(obj) {
    return isObject(obj) ? Object.keys(obj).length : 0;
}

function isNumber(param) {
    return typeof param === "number";
}
function isObject(param) {
    return typeof param === "object";
}
function isString(param) {
    return typeof param === "string";
}

/**
 * exception-safe JSON.parse
 * @param param
 * @returns {null}
 */
function json_decode(param) {
    try {
        return JSON.parse(param);
    } catch (e) {
        console.error(e);
        return null
    }
}

/**
 * Exception-safe JSON.stringify
 * @param param
 * @returns {null}
 */
function json_encode(param) {
    try {
        return JSON.stringify(param);
    } catch (e) {
        console.error(e);
        return null
    }
}

/**
 *
 * @param obj
 * @returns {Array}
 */
function objKeys(obj) {
    return Object.keys(obj);
}

/**
 *
 * @param obj
 * @returns {Array}
 */
function objValues(obj) {
    if (Object.values) {
        return Object.values(obj);
    } else {
        var arr = [];
        for (var i in obj)
            arr.push(obj[i]);
        return arr;
    }
}

function timeFormat(seconds) {
    var res = '';
    if (!seconds)
        return 'N/A';

    if (seconds >= 86400) {
        var days = Math.floor(seconds / 86400);
        seconds -= 86400 * days;
        res += days + 'd';
    }

    if (seconds >= 3600) {
        var hours = Math.floor(seconds / 3600);
        seconds -= hours * 3600;
        res += ' ' + hours + 'h';
    }

    if (seconds >= 60) {
        var minutes = Math.floor(seconds / 60);
        seconds -= minutes * 60;
        res += ' ' + minutes + 'm';
    }

    if (seconds > 0)
        res += ' ' + seconds + 's';

    return res.trim() || '0s';
}

/**
 * Generates an array of dates based on a start and end date
 * @param start
 * @param end
 * @param step
 * @returns {Array}
 */
function dateRange(start, end, step) {
    range = [];
    while (start <= end) {
        range.push(moment(start));
        if (step === 'month') {
            start.add(1, 'month');
        } else {
            start.add(1, 'day');
        }
    }
    return range;
}

/**
 * generates two dates from a report date struct key
 * @param key
 * @returns {[Date,Date]}
 */
function dateRangeFromKey(key) {
    var sDate = moment();
    var eDate = moment();

    switch (key) {
        case 'thisMonth':
            sDate.startOf('month');
            eDate.endOf('month');
            break;

        case 'lastMonth':
            sDate.subtract(1, 'month').startOf('month');
            eDate.subtract(1, 'month').endOf('month');
            break;

        case 'last3Months':
            sDate.subtract(3, 'month').startOf('month');
            eDate.subtract(1, 'month').endOf('month');
            break;

        case 'last6Months':
            sDate.subtract(6, 'month').startOf('month');
            eDate.subtract(1, 'month').endOf('month');
            break;

        case 'last12Months':
            sDate.subtract(12, 'month').startOf('month');
            eDate.subtract(1, 'month').endOf('month');
            break;

        case 'thisYear':
            sDate.startOf('year');
            eDate.endOf('year');
            break;

        case 'lastYear':
            sDate.subtract(1, 'year').startOf('year');
            eDate.subtract(1, 'year').endOf('year');
            break;
    }

    return [sDate, eDate];
}

function strToColor(label) {
    return '#' + md5(label).substr(4, 6);
}

$(document).on('click', '[data-toggle=visible]', function(e) {
    $($(this).data('target')).toggle();
});

/**
 * Loads Charts
 * @param opts
 * @returns {$}
 */
$.fn.loadChartJS = function(opts) {
    $(this).each(function() {
        var chart = new Chart($(this).get(0), opts);
        $(this).data('chart', chart);
    });
    return this;
};
