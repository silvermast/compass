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

function getDateTime(timestamp) {
    if (!timestamp || timestamp < 2000)
        return '';

    var d = new Date(timestamp * 1000);
    return d.toLocaleString()
}

function numberFormat(num, precision) {
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

function getTimeString(date) {
    return [
        ('00' + date.getHours()).substr(-2),
        ('00' + date.getMinutes()).substr(-2)
    ].join(':');
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