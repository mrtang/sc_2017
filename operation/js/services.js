'use strict';
/* Services */

/* Services */
angular.module('app.services', [])
.service('HAL', function () {

    // Represents a HAL Resource
    var convertResource = function (data) {

        var halFields = ['_links', 'data'];

        data.embedded = function () {
            return data['data'];
        };

        data.links   = function () {
            return data['_links'];
        };

        data.linkOf = function (entity) {
            entity = entity || 'self';
            return data['_links'][entity];
        };

        data.get = function (field, defaultValue) {
            if (halFields.indexOf(field) >= 0) {
                return defaultValue;
            }

            return data[field] || defaultValue;
        };

        return data;
    };

    // Represents a HAL Collection Resource
    var convertCollection = function (data) {

        var baseResource = convertResource(data);
        
        baseResource.totalPage = function () {
            return baseResource['page'];
        };

        baseResource.totalItems = function () {
            return baseResource['total'];
        };

        baseResource.pageSize = function () {
            return baseResource['item_page'];
        };

        baseResource.listOf = function (field) {
            return baseResource.embedded()[field];
        };
        
        return baseResource;
    };

    return {
        Collection: convertCollection,
        Resource: convertResource
    }
})
.service('Search',['$http','Api_Path', function($http,Api_Path) {
    this.search = function(data) {
        return $http.get(Api_Path.Base + 'search/search', {params : data });
    }
    this.takeUser = function(user_id, data) {
        return $http.post(Api_Path.Base + 'seller/take-user/'+user_id, data);
    }
    this.listStatus = function() {
        return $http.get(Api_Path.Base + 'search/list-status');
    }
}])

.service('Seller',['$http','Api_Path', function($http,Api_Path) {
    this.customer = function(data) {
        return $http.get(Api_Path.Base + 'seller/customer', {params : data });
    }
    this.remove = function(id,data) {
        return $http.get(Api_Path.Base + 'seller/remove/'+id, {params: data});
    };
    this.history = function(id) {
        return $http.get(Api_Path.Base + 'seller/history/' + id);
    };
    this.historyCS = function(id) {
        return $http.get(Api_Path.Base + 'seller/history-cs/'+ id);
    };
}])
        
.service('Storage', ['$localStorage', '$state', '$timeout', 'toaster', function ($localStorage, $state, $timeout, toaster) {
    return {
        remove: function(){
            delete $localStorage['login'];
            delete $localStorage['time_login'];
            toaster.pop('error', 'Thông báo', 'Bạn chưa đăng nhập tài khoản!');
            var yourTimer = $timeout(function() {
                $state.go('access.signin');
            }, 1500);
        }
    }
}])
.service('PhpJs', function () {
    var rtrim = function (str, charlist) {
        charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\\$1');
        var re = new RegExp('[' + charlist + ']+$', 'g');
        return (str + '').replace(re, '');
    };

    var addslashes = function(str){
        return (str + '')
            .replace(/[\\"']/g, '\\$&')
            .replace(/\u0000/g, '\\0');
    };

    var array_merge_recursive = function(arr1, arr2){
        var idx = '';
        if (arr1 && Object.prototype.toString.call(arr1) === '[object Array]' &&
            arr2 && Object.prototype.toString.call(arr2) === '[object Array]') {
            for (idx in arr2) {
                arr1.push(arr2[idx]);
            }
        } else if ((arr1 && (arr1 instanceof Object)) && (arr2 && (arr2 instanceof Object))) {
            for (idx in arr2) {
                if (idx in arr1) {
                    if (typeof arr1[idx] === 'object' && typeof arr2 === 'object') {
                        arr1[idx] = this.array_merge(arr1[idx], arr2[idx]);
                    } else {
                        arr1[idx] = arr2[idx];
                    }
                } else {
                    arr1[idx] = arr2[idx];
                }
            }
        }

        return arr1;
    };

    var array_unique    =    function(inputArr) {
        //  discuss at: http://phpjs.org/functions/array_unique/
        // original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
        //    input by: duncan
        //    input by: Brett Zamir (http://brett-zamir.me)
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // bugfixed by: Nate
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // bugfixed by: Brett Zamir (http://brett-zamir.me)
        // improved by: Michael Grier
        //        note: The second argument, sort_flags is not implemented;
        //        note: also should be sorted (asort?) first according to docs
        //   example 1: array_unique(['Kevin','Kevin','van','Zonneveld','Kevin']);
        //   returns 1: {0: 'Kevin', 2: 'van', 3: 'Zonneveld'}
        //   example 2: array_unique({'a': 'green', 0: 'red', 'b': 'green', 1: 'blue', 2: 'red'});
        //   returns 2: {a: 'green', 0: 'red', 1: 'blue'}

        var key = '',
            tmp_arr2 = {},
            val = '';

        var __array_search = function(needle, haystack) {
            var fkey = '';
            for (fkey in haystack) {
                if (haystack.hasOwnProperty(fkey)) {
                    if ((haystack[fkey] + '') === (needle + '')) {
                        return fkey;
                    }
                }
            }
            return false;
        };

        for (key in inputArr) {
            if (inputArr.hasOwnProperty(key)) {
                val = inputArr[key];
                if (false === __array_search(val, tmp_arr2)) {
                    tmp_arr2[key] = val;
                }
            }
        }

        return tmp_arr2;
    };

    var utf8_encode = function(string){
        string = (string+'').replace(/\r\n/g, "\n").replace(/\r/g, "\n");

        var utftext = "";
        var start, end;
        var stringl = 0;

        start = end = 0;
        stringl = string.length;
        for (var n = 0; n < stringl; n++) {
            var c1 = string.charCodeAt(n);
            var enc = null;

            if (c1 < 128) {
                end++;
            } else if((c1 > 127) && (c1 < 2048)) {
                enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
            } else {
                enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
            }
            if (enc != null) {
                if (end > start) {
                    utftext += string.substring(start, end);
                }
                utftext += enc;
                start = end = n+1;
            }
        }

        if (end > start) {
            utftext += string.substring(start, string.length);
        }

        return utftext;
    }

    var md5 = function(str){
        var xl;

        var rotateLeft = function(lValue, iShiftBits) {
            return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
        };

        var addUnsigned = function(lX, lY) {
            var lX4, lY4, lX8, lY8, lResult;
            lX8 = (lX & 0x80000000);
            lY8 = (lY & 0x80000000);
            lX4 = (lX & 0x40000000);
            lY4 = (lY & 0x40000000);
            lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
            if (lX4 & lY4) {
                return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
            }
            if (lX4 | lY4) {
                if (lResult & 0x40000000) {
                    return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                } else {
                    return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                }
            } else {
                return (lResult ^ lX8 ^ lY8);
            }
        };

        var _F = function(x, y, z) {
            return (x & y) | ((~x) & z);
        };
        var _G = function(x, y, z) {
            return (x & z) | (y & (~z));
        };
        var _H = function(x, y, z) {
            return (x ^ y ^ z);
        };
        var _I = function(x, y, z) {
            return (y ^ (x | (~z)));
        };

        var _FF = function(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_F(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };

        var _GG = function(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_G(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };

        var _HH = function(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_H(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };

        var _II = function(a, b, c, d, x, s, ac) {
            a = addUnsigned(a, addUnsigned(addUnsigned(_I(b, c, d), x), ac));
            return addUnsigned(rotateLeft(a, s), b);
        };

        var convertToWordArray = function(str) {
            var lWordCount;
            var lMessageLength = str.length;
            var lNumberOfWords_temp1 = lMessageLength + 8;
            var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
            var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
            var lWordArray = new Array(lNumberOfWords - 1);
            var lBytePosition = 0;
            var lByteCount = 0;
            while (lByteCount < lMessageLength) {
                lWordCount = (lByteCount - (lByteCount % 4)) / 4;
                lBytePosition = (lByteCount % 4) * 8;
                lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount) << lBytePosition));
                lByteCount++;
            }
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
            lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
            lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
            return lWordArray;
        };

        var wordToHex = function(lValue) {
            var wordToHexValue = '',
                wordToHexValue_temp = '',
                lByte, lCount;
            for (lCount = 0; lCount <= 3; lCount++) {
                lByte = (lValue >>> (lCount * 8)) & 255;
                wordToHexValue_temp = '0' + lByte.toString(16);
                wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
            }
            return wordToHexValue;
        };

        var x = [],
            k, AA, BB, CC, DD, a, b, c, d, S11 = 7,
            S12 = 12,
            S13 = 17,
            S14 = 22,
            S21 = 5,
            S22 = 9,
            S23 = 14,
            S24 = 20,
            S31 = 4,
            S32 = 11,
            S33 = 16,
            S34 = 23,
            S41 = 6,
            S42 = 10,
            S43 = 15,
            S44 = 21;

        str = utf8_encode(str);
        x = convertToWordArray(str);
        a = 0x67452301;
        b = 0xEFCDAB89;
        c = 0x98BADCFE;
        d = 0x10325476;

        xl = x.length;
        for (k = 0; k < xl; k += 16) {
            AA = a;
            BB = b;
            CC = c;
            DD = d;
            a = _FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
            d = _FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
            c = _FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
            b = _FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
            a = _FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
            d = _FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
            c = _FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
            b = _FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
            a = _FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
            d = _FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
            c = _FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
            b = _FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
            a = _FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
            d = _FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
            c = _FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
            b = _FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
            a = _GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
            d = _GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
            c = _GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
            b = _GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
            a = _GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
            d = _GG(d, a, b, c, x[k + 10], S22, 0x2441453);
            c = _GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
            b = _GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
            a = _GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
            d = _GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
            c = _GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
            b = _GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
            a = _GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
            d = _GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
            c = _GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
            b = _GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
            a = _HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
            d = _HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
            c = _HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
            b = _HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
            a = _HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
            d = _HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
            c = _HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
            b = _HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
            a = _HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
            d = _HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
            c = _HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
            b = _HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
            a = _HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
            d = _HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
            c = _HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
            b = _HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
            a = _II(a, b, c, d, x[k + 0], S41, 0xF4292244);
            d = _II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
            c = _II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
            b = _II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
            a = _II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
            d = _II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
            c = _II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
            b = _II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
            a = _II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
            d = _II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
            c = _II(c, d, a, b, x[k + 6], S43, 0xA3014314);
            b = _II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
            a = _II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
            d = _II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
            c = _II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
            b = _II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
            a = addUnsigned(a, AA);
            b = addUnsigned(b, BB);
            c = addUnsigned(c, CC);
            d = addUnsigned(d, DD);
        }

        var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);

        return temp.toLowerCase();
    };

    var ScenarioTime    = function(time_str){
        var str = '';
        if(time_str > 0){
            var hours   = Math.floor(time_str/60);

            if(hours > 518400){
                str   = Math.floor(hours/518400)+' năm';
            }
            else if(hours > 43200){ // 30 ngày
                str   = Math.floor(hours/43200)+' tháng';
            }else if(hours > 1440){ // 1 ngày
                str   = Math.floor(hours/1440)+' ngày';
            }else if(hours > 60){// 1 hours
                str   = Math.floor(hours/60)+' giờ';
            }else if(hours > 0){
                str   = hours+' phút';
            }else{
                str   = '1 phút';
            }

        }
        return str;
    };

    var convertString = function (obj){
        if(obj !== null && typeof obj === 'object'){
            var keys = Object.keys(obj);
            var val  = '';
            for (var i = 0; i < keys.length; i++) {
                val += obj[keys[i]];
                // use val
            }
            return val;console.log(val);
        }else{
            return obj;
        }
    }

    var date_format = function date(format, timestamp) {
        //  discuss at: http://phpjs.org/functions/date/
        // original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
        // original by: gettimeofday
        //    parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: MeEtc (http://yass.meetcweb.com)
        // improved by: Brad Touesnard
        // improved by: Tim Wiel
        // improved by: Bryan Elliott
        // improved by: David Randall
        // improved by: Theriault
        // improved by: Theriault
        // improved by: Brett Zamir (http://brett-zamir.me)
        // improved by: Theriault
        // improved by: Thomas Beaucourt (http://www.webapp.fr)
        // improved by: JT
        // improved by: Theriault
        // improved by: Rafał Kukawski (http://blog.kukawski.pl)
        // improved by: Theriault
        //    input by: Brett Zamir (http://brett-zamir.me)
        //    input by: majak
        //    input by: Alex
        //    input by: Martin
        //    input by: Alex Wilson
        //    input by: Haravikk
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // bugfixed by: majak
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // bugfixed by: Brett Zamir (http://brett-zamir.me)
        // bugfixed by: omid (http://phpjs.org/functions/380:380#comment_137122)
        // bugfixed by: Chris (http://www.devotis.nl/)
        //        note: Uses global: php_js to store the default timezone
        //        note: Although the function potentially allows timezone info (see notes), it currently does not set
        //        note: per a timezone specified by date_default_timezone_set(). Implementers might use
        //        note: this.php_js.currentTimezoneOffset and this.php_js.currentTimezoneDST set by that function
        //        note: in order to adjust the dates in this function (or our other date functions!) accordingly
        //   example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400);
        //   returns 1: '09:09:40 m is month'
        //   example 2: date('F j, Y, g:i a', 1062462400);
        //   returns 2: 'September 2, 2003, 2:26 am'
        //   example 3: date('Y W o', 1062462400);
        //   returns 3: '2003 36 2003'
        //   example 4: x = date('Y m d', (new Date()).getTime()/1000);
        //   example 4: (x+'').length == 10 // 2009 01 09
        //   returns 4: true
        //   example 5: date('W', 1104534000);
        //   returns 5: '53'
        //   example 6: date('B t', 1104534000);
        //   returns 6: '999 31'
        //   example 7: date('W U', 1293750000.82); // 2010-12-31
        //   returns 7: '52 1293750000'
        //   example 8: date('W', 1293836400); // 2011-01-01
        //   returns 8: '52'
        //   example 9: date('W Y-m-d', 1293974054); // 2011-01-02
        //   returns 9: '52 2011-01-02'

        var that = this;
        var jsdate, f;
        // Keep this here (works, but for code commented-out below for file size reasons)
        // var tal= [];
        var txt_words = [
            'Sun', 'Mon', 'Tues', 'Wednes', 'Thurs', 'Fri', 'Satur',
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        // trailing backslash -> (dropped)
        // a backslash followed by any character (including backslash) -> the character
        // empty string -> empty string
        var formatChr = /\\?(.?)/gi;
        var formatChrCb = function(t, s) {
            return f[t] ? f[t]() : s;
        };
        var _pad = function(n, c) {
            n = String(n);
            while (n.length < c) {
                n = '0' + n;
            }
            return n;
        };
        f = {
            // Day
            d: function() { // Day of month w/leading 0; 01..31
                return _pad(f.j(), 2);
            },
            D: function() { // Shorthand day name; Mon...Sun
                return f.l()
                    .slice(0, 3);
            },
            j: function() { // Day of month; 1..31
                return jsdate.getDate();
            },
            l: function() { // Full day name; Monday...Sunday
                return txt_words[f.w()] + 'day';
            },
            N: function() { // ISO-8601 day of week; 1[Mon]..7[Sun]
                return f.w() || 7;
            },
            S: function() { // Ordinal suffix for day of month; st, nd, rd, th
                var j = f.j();
                var i = j % 10;
                if (i <= 3 && parseInt((j % 100) / 10, 10) == 1) {
                    i = 0;
                }
                return ['st', 'nd', 'rd'][i - 1] || 'th';
            },
            w: function() { // Day of week; 0[Sun]..6[Sat]
                return jsdate.getDay();
            },
            z: function() { // Day of year; 0..365
                var a = new Date(f.Y(), f.n() - 1, f.j());
                var b = new Date(f.Y(), 0, 1);
                return Math.round((a - b) / 864e5);
            },

            // Week
            W: function() { // ISO-8601 week number
                var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3);
                var b = new Date(a.getFullYear(), 0, 4);
                return _pad(1 + Math.round((a - b) / 864e5 / 7), 2);
            },

            // Month
            F: function() { // Full month name; January...December
                return txt_words[6 + f.n()];
            },
            m: function() { // Month w/leading 0; 01...12
                return _pad(f.n(), 2);
            },
            M: function() { // Shorthand month name; Jan...Dec
                return f.F()
                    .slice(0, 3);
            },
            n: function() { // Month; 1...12
                return jsdate.getMonth() + 1;
            },
            t: function() { // Days in month; 28...31
                return (new Date(f.Y(), f.n(), 0))
                    .getDate();
            },

            // Year
            L: function() { // Is leap year?; 0 or 1
                var j = f.Y();
                return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0;
            },
            o: function() { // ISO-8601 year
                var n = f.n();
                var W = f.W();
                var Y = f.Y();
                return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0);
            },
            Y: function() { // Full year; e.g. 1980...2010
                return jsdate.getFullYear();
            },
            y: function() { // Last two digits of year; 00...99
                return f.Y()
                    .toString()
                    .slice(-2);
            },

            // Time
            a: function() { // am or pm
                return jsdate.getHours() > 11 ? 'pm' : 'am';
            },
            A: function() { // AM or PM
                return f.a()
                    .toUpperCase();
            },
            B: function() { // Swatch Internet time; 000..999
                var H = jsdate.getUTCHours() * 36e2;
                // Hours
                var i = jsdate.getUTCMinutes() * 60;
                // Minutes
                var s = jsdate.getUTCSeconds(); // Seconds
                return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
            },
            g: function() { // 12-Hours; 1..12
                return f.G() % 12 || 12;
            },
            G: function() { // 24-Hours; 0..23
                return jsdate.getHours();
            },
            h: function() { // 12-Hours w/leading 0; 01..12
                return _pad(f.g(), 2);
            },
            H: function() { // 24-Hours w/leading 0; 00..23
                return _pad(f.G(), 2);
            },
            i: function() { // Minutes w/leading 0; 00..59
                return _pad(jsdate.getMinutes(), 2);
            },
            s: function() { // Seconds w/leading 0; 00..59
                return _pad(jsdate.getSeconds(), 2);
            },
            u: function() { // Microseconds; 000000-999000
                return _pad(jsdate.getMilliseconds() * 1000, 6);
            },

            // Timezone
            e: function() { // Timezone identifier; e.g. Atlantic/Azores, ...
                // The following works, but requires inclusion of the very large
                // timezone_abbreviations_list() function.
                /*              return that.date_default_timezone_get();
                 */
                throw 'Not supported (see source code of date() for timezone on how to add support)';
            },
            I: function() { // DST observed?; 0 or 1
                // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
                // If they are not equal, then DST is observed.
                var a = new Date(f.Y(), 0);
                // Jan 1
                var c = Date.UTC(f.Y(), 0);
                // Jan 1 UTC
                var b = new Date(f.Y(), 6);
                // Jul 1
                var d = Date.UTC(f.Y(), 6); // Jul 1 UTC
                return ((a - c) !== (b - d)) ? 1 : 0;
            },
            O: function() { // Difference to GMT in hour format; e.g. +0200
                var tzo = jsdate.getTimezoneOffset();
                var a = Math.abs(tzo);
                return (tzo > 0 ? '-' : '+') + _pad(Math.floor(a / 60) * 100 + a % 60, 4);
            },
            P: function() { // Difference to GMT w/colon; e.g. +02:00
                var O = f.O();
                return (O.substr(0, 3) + ':' + O.substr(3, 2));
            },
            T: function() { // Timezone abbreviation; e.g. EST, MDT, ...
                // The following works, but requires inclusion of the very
                // large timezone_abbreviations_list() function.
                /*              var abbr, i, os, _default;
                 if (!tal.length) {
                 tal = that.timezone_abbreviations_list();
                 }
                 if (that.php_js && that.php_js.default_timezone) {
                 _default = that.php_js.default_timezone;
                 for (abbr in tal) {
                 for (i = 0; i < tal[abbr].length; i++) {
                 if (tal[abbr][i].timezone_id === _default) {
                 return abbr.toUpperCase();
                 }
                 }
                 }
                 }
                 for (abbr in tal) {
                 for (i = 0; i < tal[abbr].length; i++) {
                 os = -jsdate.getTimezoneOffset() * 60;
                 if (tal[abbr][i].offset === os) {
                 return abbr.toUpperCase();
                 }
                 }
                 }
                 */
                return 'UTC';
            },
            Z: function() { // Timezone offset in seconds (-43200...50400)
                return -jsdate.getTimezoneOffset() * 60;
            },

            // Full Date/Time
            c: function() { // ISO-8601 date.
                return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb);
            },
            r: function() { // RFC 2822
                return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb);
            },
            U: function() { // Seconds since UNIX epoch
                return jsdate / 1000 | 0;
            }
        };
        this.date = function(format, timestamp) {
            that = this;
            jsdate = (timestamp === undefined ? new Date() : // Not provided
                (timestamp instanceof Date) ? new Date(timestamp) : // JS Date()
                    new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
            );
            return format.replace(formatChr, formatChrCb);
        };
        return this.date(format, timestamp);
    }

    return {
        rtrim                   : rtrim,
        addslashes              : addslashes,
        utf8_encode             : utf8_encode,
        md5                     : md5,
        array_merge_recursive   : array_merge_recursive,
        ScenarioTime            : ScenarioTime,
        convertString           : convertString,
        date_format             : date_format,
        array_unique            : array_unique
    }
})
.service('Ticket', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', function ($http, $q, Api_Path, Storage, toaster) {
    return{
        // verify
        ListCase : function(){
            return $http({
                url: Api_Path.Base + 'ticket-case',
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(result.error && !result.accessDenny) {
                    toaster.pop('danger', 'Thông báo', 'Tải dữ liệu lỗi!');
                }
            }).error(function (data, status, headers, config) {
                if(status == 404){
                    Storage.remove();
                }else{
                    toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                }
            })
            return;
        },
        ListCaseType : function(){
            return $http({
                url: Api_Path.Base + 'ticket-case-type',
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(result.error){
                    toaster.pop('danger', 'Thông báo', 'Tải dữ liệu lỗi!');
                }
            }).error(function (data, status, headers, config) {
                if(status == 404){
                    Storage.remove();
                }else{
                    toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                }
            })
            return;
        },
        ListFeedback : function(id){
            return $http({
                url: Api_Path.Base + 'ticket-feedback/byticket/'+id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {

            })
            return;
        },
        SearchRefer : function(code){
            return $http({
                url: Api_Path.Base + 'ticket-refer/refer?code='+code,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 404){
                    Storage.remove();
                }else{
                    toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                }
            });
        }
    }
}])
.service('User', ['$http', '$q', 'Api_Path', 'HAL', function ($http, $q, Api_Path, HAL) {
        return{
            profile : function(id) {
                return $http.get(Api_Path.Base + 'user/view/'+id);
            },
            load : function(val){ 
            return $http({
                url: Api_Path.list_user+'&search='+val,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                return HAL.Collection(result).listOf('result');
            }).error(function (data, status, headers, config) {
                return;
            })
            
            },
            vip: function(data) {
                return $http.get(Api_Path.Base + 'user/vip',{params: data});
            },
            admin: function() {
                return $http.get(Api_Path.Base + 'user-info/useradmin');
            }
        }
}])
.service('Order', ['$http', '$q', '$window', 'Api_Path', 'PhpJs', 'toaster', 'Storage', '$rootScope', function ($http, $q, $window, Api_Path, PhpJs, toaster, Storage, $rootScope) {
        return {
            EventDashBoard: function () {
                return $http({
                    url: Api_Path.Ops + 'order/event-dashboard',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            CreateProcess: function (data, callback) {
                $http({
                    url: ApiPath + 'order-process/create',
                    method: "POST",
                    data: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        //toaster.pop('warning', 'Thông báo', 'Tạo yêu cầu lỗi !', PhpJs.convertString(result.message));
                        callback(true, null);
                    } else {
                        callback(null, result);
                        //toaster.pop('success', 'Thông báo', 'Tạo yêu cầu thành công !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        callback(true, null);
                        //toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            },
            ListStatus: function (group) {
                return $http({
                    url: Api_Path.OrderStatus + '/statusgroup?group=' + group,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            StatusAccept: function (group) { // status accept
                return $http({
                    url: Api_Path.Ops + 'order/statusaccept/' + group,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            SuggestCourier: function (data) { // status accept
                return $http({
                    url: Api_Path.Ops + 'order/suggest-courier',
                    method: "GET",
                    dataType: 'json',
                    params: data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', result.message);
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            ChangeCourier: function (data) { // status accept
                return $http({
                    url: Api_Path.Ops + 'order/change-courier',
                    method: "POST",
                    dataType: 'json',
                    params: data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', result.message);
                    }else{
                        toaster.pop('success', 'Thông báo', result.message);
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            ChangeStatus: function (data) {
                return $http({
                    url: '/api/public/trigger/journey/acceptstatus',
                    method: "POST",
                    data: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error == 'success') {
                        toaster.pop('success', 'Thông báo', result.error_message);
                    } else {
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            },
            ChangeTag: function (data) {
                return $http({
                    url: Api_Path.Ops + 'order/changetag',
                    method: "POST",
                    data: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (!result.error) {
                        toaster.pop('success', 'Thông báo', 'Cập nhật thành công!');
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Cập nhật lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                });

                return;
            },
            GetRefer: function (tracking_code) {
                return $http({
                    url: Api_Path.Ops + 'order/referticket?tracking_code='+tracking_code,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                });

                return;
            },

            GetPostman: function (postman_id) {
                return $http({
                    url: Api_Path.Ops + 'order/postman?postman='+postman_id,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                });

                return;
            },

            Statistic: function (list_status) {
                return $http({
                    url: Api_Path.Ops + 'order/statistic?list_status=' + list_status,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            Sale: function () {
                return $http({
                    url: Api_Path.Ops + 'order/statistic-sale',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            PickupSlow: function (page, data, list_status, cmd) {
                var url = Api_Path.Ops + 'order/pickup-slow?page='+page+'&list_status=' + list_status;
                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            DeliverySlow: function (page, data, list_status, cmd) {
                var url = Api_Path.Ops + 'order/delivery-slow?page=' + page + '&list_status=' + list_status;
                //delete $http.defaults.headers.common['Authorization'];
                //var url = 'http://dev.shipchung.vn/api/public/ops/order/delivery-slow?page=' + page + '&list_status=' + list_status;

                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            ReturnSlow: function (page, data, list_status, cmd) {
                var url = Api_Path.Ops + 'order/return-slow?page=' + page + '&list_status=' + list_status;
                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            OrderProblem: function (page, data, list_status, cmd) {
                var url = Api_Path.Ops + 'order?page=' + page;
                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                }

                if (list_status != undefined && list_status != '') {
                    url += '&list_status=' + list_status;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            ListOrder: function (page, data, list_status, cmd) {
                var url = Api_Path.Ops + 'order?page=' + page;

                if (data.global != undefined && data.global != 0) {
                    url += '&global=' + data.global;
                }
                if (data.tracking_code != undefined && data.tracking_code != '') {
                    url += '&tracking_code=' + data.tracking_code;
                }
                if (data.courier_tracking_code != undefined && data.courier_tracking_code != '') {
                    url += '&courier_tracking_code=' + data.courier_tracking_code;
                }

                if (data.to_user != undefined && data.to_user != '') { // TÌm kiếm tổng quan
                    url += '&to_user=' + data.to_user;
                }

                if (data.domain != undefined && data.domain != '') {
                    url += '&domain=' + data.domain;
                }

                if (data.slow != undefined) {
                    url += '&slow=' + data.slow;
                }
                if (data.delivery_slow != undefined) {
                    url += '&delivery_slow=' + data.delivery_slow;
                }

                if (data.type_noithanh != undefined) {
                    url += '&type_noithanh=' + data.type_noithanh;
                }

                if (data.slow_delivery_nt != undefined) {
                    url += '&slow_delivery_nt=' + data.slow_delivery_nt;
                }

                if (data.return_slow != undefined) {
                    url += '&return_slow=' + data.return_slow;
                }

                if (data.last_update != undefined && data.last_update > 0) {
                    url += '&last_update=' + data.last_update;
                }

                if (data.amount != undefined && data.amount > 0) {
                    url += '&amount=' + data.amount;
                }

                if (data.over_weight != undefined && data.over_weight > 0) {
                    url += '&over_weight=' + data.over_weight;
                }

                if (data.weight != undefined && data.weight > 0) {
                    url += '&weight=' + data.weight;
                }

                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        url += '&to_user=' + data.keyword.substr(1);
                    }else{
                        url += '&keyword=' + data.keyword;
                    }
                }

                if (data.from_user != undefined && data.from_user > 0) {
                    url += '&from_user=' + data.from_user;
                }

                if (data.group != undefined && data.group != '') {
                    url += '&group=' + data.group;
                }

                if (data.service != undefined && data.service != '') {
                    url += '&service=' + data.service;
                }

                if (data.courier != undefined && data.courier != '') {
                    url += '&courier=' + data.courier;
                }

                if (data.vip != undefined && data.vip > 0) {
                    url += '&vip=' + data.vip;
                }

                if (data.from_city != undefined && data.from_city > 0) {
                    url += '&from_city=' + data.from_city;
                }

                if (data.from_district != undefined && data.from_district > 0) {
                    url += '&from_district=' + data.from_district;
                }

                if (data.to_city != undefined && data.to_city > 0) {
                    url += '&to_city=' + data.to_city;
                }

                if (data.to_district != undefined && data.to_district > 0) {
                    url += '&to_district=' + data.to_district;
                }

                if (data.create_start != undefined && data.create_start != '') {
                    url += '&create_start=' + data.create_start;
                }

                if (data.new_customer_from != undefined && data.new_customer_from != '') {
                    url += '&new_customer_from=' + data.new_customer_from;
                }


                if (data.create_end != undefined && data.create_end != '') {
                    url += '&create_end=' + data.create_end;
                }

                if (data.accept_start != undefined && data.accept_start != '') {
                    url += '&accept_start=' + data.accept_start;
                }

                if (data.accept_end != undefined && data.accept_end != '') {
                    url += '&accept_end=' + data.accept_end;
                }

                if (data.pickup_start != undefined && data.pickup_start != '') {
                    url += '&pickup_start=' + data.pickup_start;
                }

                if (data.pickup_end != undefined && data.pickup_end != '') {
                    url += '&pickup_end=' + data.pickup_end;
                }

                if (data.success_start != undefined && data.success_start != '') {
                    url += '&success_start=' + data.success_start;
                }

                if (data.success_end != undefined && data.success_end != '') {
                    url += '&success_end=' + data.success_end;
                }

                if (data.package_start != undefined && data.package_start != '') {
                    url += '&package_start=' + data.package_start;
                }

                if (data.package_end != undefined && data.package_end != '') {
                    url += '&package_end=' + data.package_end;
                }

                if (data.accept_return_start != undefined && data.accept_return_start != '') {
                    url += '&accept_return_start=' + data.accept_return_start;
                }

                if (data.accept_return_end != undefined && data.accept_return_end != '') {
                    url += '&accept_return_end=' + data.accept_return_end;
                }

                if (data.tag != undefined && data.tag != '') {
                    url += '&tag=' + data.tag;
                }

                if (data.list_status != undefined && data.list_status != '') {
                    url += '&pipe_status=' + data.list_status;
                }

                if (list_status != undefined && list_status != '') {
                    url += '&list_status=' + list_status;
                }

                if (data.type_process != undefined && data.type_process != '') {
                    url += '&type-process=' + data.type_process;
                }

                if(data.location != undefined && data.location != 0){
                    url += '&location=' + data.location;
                }

                if(data.loyalty != undefined && data.loyalty != ''){
                    url += '&loyalty=' + data.loyalty;
                }

                if(data.post_office_id != undefined && data.post_office_id == "ALL"){
                    url += '&post_office_id=' + data.post_office_id;
                }

                if(data.warehouse != undefined && data.warehouse != ""){
                    url += '&warehouse=' + data.warehouse;
                }

                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                    url += '&access_token=' +  $rootScope.userInfo.token;
                    $window.open(url, '_blank');
                    return '';
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            GetOrder: function (page, data, cmd) {
                var url = Api_Path.Ops + 'order/order?page=' + page;
                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        data.to_user    = data.keyword.substr(1);
                        delete data.keyword;
                    }
                }

                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            CountGroupOrder: function (data, type) {
                if (type == 'status') {
                    var url = Api_Path.Ops + 'order/countgroupstatus?page=1';
                } else if(type == 'warehouse'){
                    var url = Api_Path.Ops + 'order/countgroupwarehouse?page=1';
                }else{
                    var url = Api_Path.Ops + 'order/countgroup?page=1';
                }

                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        data.to_user    = data.keyword.substr(1);
                        delete data.keyword;
                    }
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            CountGroup: function (data, list_status, type) {
                if (type == 'status') {
                    var url = Api_Path.Ops + 'order/countgroupstatus?page=1';
                } else if(type == 'warehouse'){
                    var url = Api_Path.Ops + 'order/countgroupwarehouse?page=1';
                }else{
                    var url = Api_Path.Ops + 'order/countgroup?page=1';
                }

                if (data.global != undefined && data.global != 0) {
                    url += '&global=' + data.global;
                }

                if (data.tracking_code != undefined && data.tracking_code != '') {
                    url += '&tracking_code=' + data.tracking_code;
                }

                if (data.to_user != undefined && data.to_user != '') { // TÌm kiếm tổng quan
                    url += '&to_user=' + data.to_user;
                }

                if (data.domain != undefined && data.domain != '') {
                    url += '&domain=' + data.domain;
                }

                if (data.slow != undefined) {
                    url += '&slow=' + data.slow;
                }
                if (data.delivery_slow != undefined) {
                    url += '&delivery_slow=' + data.delivery_slow;
                }

                if (data.type_noithanh != undefined) {
                    url += '&type_noithanh=' + data.type_noithanh;
                }

                if (data.slow_delivery_nt != undefined) {
                    url += '&slow_delivery_nt=' + data.slow_delivery_nt;
                }

                if (data.return_slow != undefined) {
                    url += '&return_slow=' + data.return_slow;
                }

                if (data.last_update != undefined && data.last_update > 0) {
                    url += '&last_update=' + data.last_update;
                }

                if (data.amount != undefined && data.amount > 0) {
                    url += '&amount=' + data.amount;
                }

                if (data.over_weight != undefined && data.over_weight > 0) {
                    url += '&over_weight=' + data.over_weight;
                }

                if (data.weight != undefined && data.weight > 0) {
                    url += '&weight=' + data.weight;
                }

                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        url += '&to_user=' + data.keyword.substr(1);
                    }else{
                        url += '&keyword=' + data.keyword;
                    }
                }

                if (data.service != undefined && data.service != '') {
                    url += '&service=' + data.service;
                }

                if (data.courier != undefined && data.courier != '') {
                    url += '&courier=' + data.courier;
                }

                if (data.from_user != undefined && data.from_user > 0) {
                    url += '&from_user=' + data.from_user;
                }

                if (data.vip != undefined && data.vip > 0) {
                    url += '&vip=' + data.vip;
                }

                if (data.from_city != undefined && data.from_city > 0) {
                    url += '&from_city=' + data.from_city;
                }

                if (data.from_district != undefined && data.from_district > 0) {
                    url += '&from_district=' + data.from_district;
                }

                if (data.to_city != undefined && data.to_city > 0) {
                    url += '&to_city=' + data.to_city;
                }

                if (data.to_district != undefined && data.to_district > 0) {
                    url += '&to_district=' + data.to_district;
                }

                if (data.tag != undefined && data.tag != '') {
                    url += '&tag=' + data.tag;
                }

                if (data.new_customer_from != undefined && data.new_customer_from != '') {
                    url += '&new_customer_from=' + data.new_customer_from;
                }

                if (data.create_start != undefined && data.create_start != '') {
                    url += '&create_start=' + data.create_start;
                }

                if (data.accept_start != undefined && data.accept_start != '') {
                    url += '&accept_start=' + data.accept_start;
                }

                if (data.create_end != undefined && data.create_end != '') {
                    url += '&create_end=' + data.create_end;
                }

                if (data.accept_end != undefined && data.accept_end != '') {
                    url += '&accept_end=' + data.accept_end;
                }

                if (data.pickup_start != undefined && data.pickup_start != '') {
                    url += '&pickup_start=' + data.pickup_start;
                }

                if (data.pickup_end != undefined && data.pickup_end != '') {
                    url += '&pickup_end=' + data.pickup_end;
                }

                if (data.success_start != undefined && data.success_start != '') {
                    url += '&success_start=' + data.success_start;
                }

                if (data.success_end != undefined && data.success_end != '') {
                    url += '&success_end=' + data.success_end;
                }

                if (data.accept_return_start != undefined && data.accept_return_start != '') {
                    url += '&accept_return_start=' + data.accept_return_start;
                }

                if (data.accept_return_end != undefined && data.accept_return_end != '') {
                    url += '&accept_return_end=' + data.accept_return_end;
                }

                if (data.group != undefined && data.group != '') {
                    url += '&group=' + data.group;
                }

                if (data.list_status != undefined && data.list_status != '') {
                    url += '&pipe_status=' + data.list_status;
                }

                if (list_status != undefined && list_status != '') {
                    url += '&list_status=' + list_status;
                }

                if(data.location != undefined && data.location != 0){
                    url += '&location=' + data.location;
                }

                if(data.post_office_id != undefined && data.post_office_id == "ALL"){
                    url += '&post_office_id=' + data.post_office_id;
                }

                if(data.loyalty != undefined && data.loyalty != ''){
                    url += '&loyalty=' + data.loyalty;
                }

                if(data.warehouse != undefined && data.warehouse == ""){
                    url += '&warehouse=' + data.warehouse;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            ListReportReplay: function (page, data, list_status, cmd) {
                var url = Api_Path.Ops + 'order/report-replay?page=' + page;

                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        url += '&to_user=' + data.keyword.substr(1);
                    }else{
                        url += '&keyword=' + data.keyword;
                    }
                }

                if (list_status != undefined && list_status != '') {
                    url += '&list_status=' + list_status;
                }

                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params: data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            CountReportReplay: function (data, list_status, type) {

                var url = Api_Path.Ops + 'order/count-report-replay?page=1';

                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        url += '&to_user=' + data.keyword.substr(1);
                    }else{
                        url += '&keyword=' + data.keyword;
                    }
                }

                if (list_status != undefined && list_status != '') {
                    url += '&list_status=' + list_status;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params : data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            ListReportStore: function (page, data, list_status, cmd) {
                var url = Api_Path.Ops + 'order/report-store?page=' + page;

                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        url += '&to_user=' + data.keyword.substr(1);
                    }else{
                        url += '&keyword=' + data.keyword;
                    }
                }

                if (list_status != undefined && list_status != '') {
                    url += '&list_status=' + list_status;
                }

                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params: data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            CountReportStock: function (data, list_status, type) {

                var url = Api_Path.Ops + 'order/count-report-stock?page=1';

                if (data.keyword != undefined && data.keyword != '') {
                    if(data.keyword.match(/^@/gi)){
                        url += '&to_user=' + data.keyword.substr(1);
                    }else{
                        url += '&keyword=' + data.keyword;
                    }
                }

                if (list_status != undefined && list_status != '') {
                    url += '&list_status=' + list_status;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params : data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            Edit: function (data) {
                return $http({
                    url: Api_Path.ChangeOrder + '/edit',
                    method: "POST",
                    data: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (!result.error) {
                        toaster.pop('success', 'Thông báo', 'Cập nhật thành công!');
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Cập nhật lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            },
            StatusOrder: function (order_id, status) {
                return $http({
                    url: Api_Path.Ops + 'order/orderstatus/'+order_id,
                    method: "GET",
                    dataType: 'json',
                    params: {'status' : status},
                }).success(function (result, status, headers, config) {
                    if (!result.error) {

                    } else {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            },
            ListStatusOrderProcess: function () {
                return $http({
                    url: Api_Path.Base + 'order-status/statusgroup?statusId=18,20,15',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

                return;
            },
            ListOrderProcess: function (page, limit, search, list_status, time_create_start, time_create_end, time_accept_start, time_accept_end, courier, to_city, to_district, cmd, over_weight, byVip) {
                var url_location = Api_Path.Base + 'order/order-process?';

                if (limit > 0) {
                    url_location += 'limit=' + limit + '&';
                }
                if (byVip) {
                    url_location += 'by_vip=true&';
                }

                if (courier > 0) {
                    url_location += 'courier_id=' + courier + '&';
                }

                if (to_city > 0) {
                    url_location += 'to_city=' + to_city + '&';
                }

                if (to_district > 0) {
                    url_location += 'to_district=' + to_district + '&';
                }

                if (search.length > 0) {
                    url_location += 'search=' + search + '&';
                }

                if (list_status != undefined && list_status != []) {
                    url_location += 'list_status=' + list_status + '&';
                }

                if (time_create_start > 0) {
                    url_location += 'time_create_start=' + time_create_start + '&';
                }
                if (time_create_end > 0) {
                    url_location += 'time_create_end=' + time_create_end + '&';
                }
                if (time_accept_start > 0) {
                    url_location += 'time_accept_start=' + time_accept_start + '&';
                }
                if (time_accept_end > 0) {
                    url_location += 'time_accept_end=' + time_accept_end + '&';
                }

                if (over_weight && over_weight == true) {
                    url_location += 'over_weight=true&';
                }

                if (page > 0) {
                    url_location += 'page=' + page + '&';
                }

                if (cmd != undefined && cmd != '') {
                    url_location += 'cmd=' + cmd + '&';
                    $window.open(PhpJs.rtrim(url_location, '&'), '_blank');
                    return '';
                }

                return $http({
                    url: PhpJs.rtrim(url_location, '&'),
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            }
        }
}])
.service('Base', ['$http', 'Api_Path', 'toaster', 'Storage','$rootScope', function ($http, Api_Path, toaster, Storage,$rootScope) {
    return{
        /// Take Seller

        'ProductType': function (callback){
            return $http({
                url: Api_Path.Base + 'seller/product-type',
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })
        },

        'Warehouse': function (){
            return $http({
                url: Api_Path.Base + 'seller/warehouse',
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })
        },
        
        'BusinessModel': function (callback){
            return $http({
                url: Api_Path.Base + 'seller/business-model',
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })
        },

        Search: function (search) {
            return $http({
                url: Api_Path.User + '/statistic/' + search,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })
            return;
        },
        GroupStatus: function () {
            return $http({
                url: Api_Path._Base + 'status-group',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        Tag: function () {
            return $http({
                url: Api_Path._Base + 'tag',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        Courier : function(){
            return $http({
                url: Api_Path._Base + 'courier',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        Country : function(){
            return $http({
                url: Api_Path._Base + 'country',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        City : function(){
            return $http({
                url: Api_Path._Base + 'city',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        AllDistrict : function(){
            return $http({
                url: Api_Path._Base + 'all-district',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        Status : function(){
            return $http({
                url: Api_Path._Base + 'status',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        PipeStatus : function(group,type){
            return $http({
                url: Api_Path.Ops+'base/pipe-by-group',
                method: "GET",
                params: {group : group, type : type},
                dataType: 'json'
            })

            return;
        },
        getGroupProcess :function (group,type){
            return $http({
                url: Api_Path.Ops+'base/group-process',
                method: "GET",
                params: {group : group, type : type},
                dataType: 'json'
            })

            return;
        },
        UserVip : function(){
            return $http({
                url: Api_Path._Base + 'loyalty-user',
                method: "GET",
                dataType: 'json'
            })

            return;
        },
        Merchant: function (page, frm, cmd) { // get seller  oms
            var url = Api_Path.User + '/merchant?page=' + page;

            if (frm.seller != undefined && frm.seller > 0) {
                url += '&seller=' + frm.seller;
            }
            if (frm.keyword != undefined) {
                url += '&keyword=' + frm.keyword;
            }
            if (frm.first_time_pickup_start != undefined) {
                url += '&first_time_pickup_start=' + frm.first_time_pickup_start;
            }
            if (frm.first_time_pickup_end != undefined) {
                url += '&first_time_pickup_end=' + frm.first_time_pickup_end;
            }
            if (cmd != undefined) {
                url += '&cmd=' + cmd;
            }

            if(cmd == 'export'){
                url += '&access_token=' + $rootScope.userInfo.token;
                return window.location = url;
            }
            return $http({
                url: url,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        district : function(province_id,limit, remote){
            return $http({
                url: ApiPath+'district',
                method: "GET",
                params: {city_id : province_id, limit : limit, remote : remote},
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(result.error){
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })
            return;
        },
        ward : function(district_id,limit){
            return $http({
                url: ApiPath+'ward',
                method: "GET",
                params: {district_id : district_id, limit : limit},
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(result.error){
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })
            return;
        },
        user_admin: function () {
                return $http({
                    url: '/api/public/ticket/base/user-admin',
                    method: "GET",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!!');
                    }
                });
        },
        loyalty_level: function () {
            return $http({
                url: Api_Path._Base+'loyalty-level',
                method: "GET",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            })
        },
        loyalty_category: function () {
            return $http({
                url: Api_Path._Base+'loyalty-category',
                method: "GET",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            })
        },
        kpi_category_group: function (group) {
            return $http({
                url: Api_Path._Base+'kpi-group-category?group='+group,
                method: "GET",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!!');
                }
            });
        },
        kpi_group_config: function (data) { // Nhóm nhân viên
            return $http({
                url: Api_Path._Base+'kpi-group-employee',
                method: "GET",
                params: data,
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!!');
                }
            });
        }
    }
}])
.service('Pipe', ['$http', '$q', '$window', '$rootScope', 'Api_Path', 'PhpJs', 'toaster', 'Storage', function ($http, $q, $window, $rootScope, Api_Path, PhpJs, toaster, Storage) {
    return{
        Create : function(data){
            $http.defaults.headers.common['Authorization']  = $rootScope.userInfo.token;
            return $http({
                url: Api_Path.PipeJourney+'/create',
                method: "POST",
                data: data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(result.error){
                    toaster.pop('warning', 'Thông báo', result.error_message);
                }else{
                    toaster.pop('success', 'Thông báo', 'Cập nhật thành công !');
                }
            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        }
    }
}])
.service('Address', ['$http', '$q', '$window', 'Api_Path', 'PhpJs', 'toaster', 'Storage', function ($http, $q, $window, Api_Path, PhpJs, toaster, Storage) {
    return{
        Inventory : function(page, data, cmd){
            var url    = Api_Path.Inventory+'?page='+page;

            if(data.domain != undefined && data.domain != ''){
                url += '&domain='+data.domain;
            }

            if(data.tab != undefined && data.tab != ''){
                url += '&tab='+data.tab;
            }

            if(data.keyword != undefined && data.keyword != ''){
                url += '&keyword='+data.keyword;
            }

            if(data.create_start != undefined && data.create_start != ''){
                url += '&create_start='+data.create_start;
            }

            if(data.create_end != undefined && data.create_end != ''){
                url += '&create_end='+data.create_end;
            }

            if(data.city_id != undefined && data.city_id != ''){
                url += '&city_id='+data.city_id;
            }

            if(data.district_id != undefined && data.district_id != ''){
                url += '&district_id='+data.district_id;
            }

            if(data.group != undefined && data.group != ''){
                url += '&group='+data.group;
            }

            if (data.vip != undefined && data.vip > 0) {
                url += '&vip=' + data.vip;
            }

            if (cmd != undefined && cmd != '') {
                url += '&cmd=' + cmd;
                $window.open(url, '_blank');
                return '';
            }

            return $http({
                url: url,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        InDay : function(page, data, cmd, notpick){
            var url    = ApiOms+'inventory/address-in-day?page='+page;

            if(data.domain != undefined && data.domain != ''){
                url += '&domain='+data.domain;
            }

            if(data.tab != undefined && data.tab != ''){
                url += '&tab='+data.tab;
            }

            if(data.keyword != undefined && data.keyword != ''){
                url += '&keyword='+data.keyword;
            }

            if(data.create_start != undefined && data.create_start != ''){
                url += '&create_start='+data.create_start;
            }

            if(data.create_end != undefined && data.create_end != ''){
                url += '&create_end='+data.create_end;
            }

            if(data.city_id != undefined && data.city_id != ''){
                url += '&city_id='+data.city_id;
            }

            if(data.district_id != undefined && data.district_id != ''){
                url += '&district_id='+data.district_id;
            }

            if(data.group != undefined && data.group != ''){
                url += '&group='+data.group;
            }

            if (data.vip != undefined && data.vip > 0) {
                url += '&vip=' + data.vip;
            }

            if (notpick != undefined) {
                url += '&notpick=' + notpick;
            }

            if (cmd != undefined && cmd != '') {
                url += '&cmd=' + cmd;
                $window.open(url, '_blank');
                return '';
            }

            return $http({
                url: url,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        }
    }

}])
.service('Location', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', 'PhpJs', function ($http, $q, Api_Path, Storage, toaster, PhpJs) {
        return{
            
            province : function(limit){
                var url_location    = ApiPath+'city';
                if(limit.length > 0){
                    url_location    += '?limit='+limit;
                }
                
                return $http({
                    url: url_location,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            },
            
            district : function(province_id,limit, remote){
                var url_location    = ApiPath+'district?';
                if(province_id > 0){
                    url_location += 'city_id='+province_id+'&';
                }
                
                if(limit.length > 0){
                    url_location += 'limit='+limit+'&';
                }

                if(remote){
                    url_location += 'remote=true&';
                }

                return $http({
                    url: PhpJs.rtrim(url_location,'&'),
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            },
            ward : function(district_id,limit){
                var url_location    = ApiPath+'ward?';
                if(district_id > 0){
                    url_location += 'district_id='+district_id+'&';
                }
                
                if(limit.length > 0){
                    url_location += 'limit='+limit+'&';
                }

                return $http({
                    url: PhpJs.rtrim(url_location,'&'),
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            },
            SuggestAll  : function(val){
                if(val == '' || val == undefined){
                    return;    
                }

                return $http({
                    url: Api_Path.Search+PhpJs.addslashes(val)+'&size=10',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            }
        }
}])
.service('Merchant', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', 'PhpJs', function ($http, $q, Api_Path, Storage, toaster, PhpJs) {
    return{
        statistic : function(user_id){
            return $http({
                url: Api_Path.User+'?user_id='+user_id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(result.error){
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })
            return;
        }
    }
}])

.service('Coupons', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', 'PhpJs', '$modal', function ($http, $q, Api_Path, Storage, toaster, PhpJs, $modal) {
    return{
        openModalCreate: function (campaign, callback){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/accounting/coupons/modal.create.coupons.html',
                controller: 'CreateCouponsCtrl',
                size: 'lg',
                resolve: {
                    campaign : function (){
                        return campaign;
                    }
                }
            });

            modalInstance.result.then(function (resp) {
                callback(resp);
            }, function () {
                callback(null);
            });
        } 
    }
}])



.factory('TaskCategory', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', 'PhpJs', '$modal', '$rootScope',
    function ($http, $q, Api_Path, Storage, toaster, PhpJs, $modal, $rootScope) {
    this.categories = [];
    this.cat_obj    = {};
    var self = this;
    function load(){
        $http.get(ApiPath + 'tasks/task-category',{
            headers : {'Location': String($rootScope.userInfo.country_id), 'Authorization' : String($rootScope.userInfo.token)}
        }).success(function (resp){
            if(!resp.error){
                self.categories = resp.data;
                angular.forEach(resp.data, function (value, key){
                    self.cat_obj[value.id] = value;

                })
            }
        })
    }

    function getObj(){
        return self.cat_obj;
    }

    function get(){
        return self.categories;
    }

    return {
        'load'   : load,
        'get'    : get,
        'getObj' : getObj
    }
}])


.service('Tasks', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', 'PhpJs', '$modal', function ($http, $q, Api_Path, Storage, toaster, PhpJs, $modal) {
    return{
        openModal : function (refer, refer_item){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/modal.add.task.html',
                controller: 'ModalAddTaskCtrl',
                size: 'lg',
                resolve: {
                    refer: function () {
                        return refer;
                    },
                    refer_item: function (){
                        return refer_item || {}
                    }
                }
            });
            return modalInstance;
        },
        SuggestOrder: function (key, type){
            return $http.get(ApiPath + 'tasks/suggest?type=' + type + '&q='+ key)
            .success(function (){
                // DO SOMETHING
            })
            .error(function (){

            });
        }
    }
}])



.service('Upload', ['$http', '$q', '$window', 'Api_Path', 'Storage', 'toaster', 'PhpJs', function ($http, $q, $window, Api_Path, Storage, toaster, PhpJs) {
    return{
        ListImport : function(page, data){
            var url    = Api_Path.Upload+'listimport?page='+page;

            if(data.type != undefined && data.type != ''){
                url += '&type='+data.type;
            }

            if(data.create_start != undefined && data.create_start != ''){
                url += '&create_start='+data.create_start;
            }

            if(data.create_end != undefined && data.create_end != ''){
                url += '&create_end='+data.create_end;
            }

            return $http({
                url: url,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },

        ListUpload : function(id , page, data, cmd){
            var url    = Api_Path.Upload+'listupload/'+id+'?page='+page;

            if(data.item_page != undefined && data.item_page > 0){
                url += '&item_page='+data.item_page;
            }

            if(data.type != undefined && data.type != ''){
                url += '&type='+data.type;
            }

            if(data.tab != undefined && data.tab != ''){
                url += '&tab='+data.tab;
            }

            if (cmd != undefined && cmd != '') {
                url += '&cmd=' + cmd;
                $window.open(url, '_blank');
                return '';
            }

            return $http({
                url: url,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        ListUpload1 : function(id , page, data, cmd){
            var url    = Api_Path.Upload+'listupload/'+id+'?page='+page;

            if(data.item_page != undefined && data.item_page > 0){
                url += '&item_page='+data.item_page;
            }

            if(data.type != undefined && data.type != ''){
                url += '&type='+data.type;
            }

            if(data.tab != undefined && data.tab != ''){
                url += '&tab='+data.tab;
            }

            if (cmd != undefined && cmd != '') {
                url += '&cmd=' + cmd;
            }

            return $http({
                url: url,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        Journey : function(id){
            return $http({
                url: Api_Path.Upload+'journey/'+id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        Weight : function(id){
            return $http({
                url: Api_Path.Upload+'weight/'+id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        Process : function(id){
            return $http({
                url: Api_Path.Upload+'process/'+id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        Status : function(id){
            return $http({
                url             : Api_Path.Upload+'status-verify/'+id,
                method          : "GET",
                crossDomain     : true,
                dataType        : 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        Estimate : function(id){
            return $http({
                url: Api_Path.Upload+'estimate/'+id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        },
        EstimatePlus : function(id){
            return $http({
                url: Api_Path.Upload+'estimate-plus/'+id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {

            }).error(function (data, status, headers, config) {
                if(status == 440){
                    Storage.remove();
                }else{
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }
            })

            return;
        }
    }
}])
.service('Loyalty', ['$http', 'Api_Path', 'toaster', 'Storage', 'PhpJs', function ($http, Api_Path, toaster, Storage, PhpJs) {
    return{
        user: function (page, data, cmd) {
            var url = Api_Path.Loyalty+'user?page='+page;
            if(cmd != undefined){
                url +='&cmd=' + cmd;
            }

            return $http({
                url: url,
                method: "GET",
                params: data,
                dataType: 'jsonp'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
            })

            return;
        },
        history: function (page, data, cmd) {
            var url = Api_Path.Loyalty+'user/history?page='+page;
            if(cmd != undefined){
                url +='&cmd=' + cmd;
            }

            return $http({
                url: url,
                method: "GET",
                params: data,
                dataType: 'jsonp'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
            })

            return;
        },
        create_user: function (data) {
            var url = Api_Path.Loyalty+'user/create';
            return $http({
                url: url,
                method: "POST",
                data: data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', PhpJs.convertString(result.error_message));
                }else{
                    toaster.pop('success', 'Thông báo', result.error_message);
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                }
            })
            return;
        },
        change_level: function (data) {
            return $http({
                url: Api_Path.Loyalty+'config/edit-level',
                method: "POST",
                data: data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', PhpJs.convertString(result.error_message));
                }else{
                    toaster.pop('success', 'Thông báo', result.error_message);
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                }
            })
            return;
        },
        campaign: function (page, data, cmd) {
            var url = Api_Path.Loyalty+'campaign?page='+page;

            if(cmd != undefined){
                url +='&cmd=' + cmd;
            }

            return $http({
                url: url,
                method: "GET",
                params: data,
                dataType: 'jsonp'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
            })

            return;
        },
        campaign_detail: function (page, data, cmd) {
            var url = Api_Path.Loyalty+'campaign/detail?page='+page;

            if(cmd != undefined){
                url +='&cmd=' + cmd;
            }

            return $http({
                url: url,
                method: "GET",
                params: data,
                dataType: 'jsonp'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
            })

            return;
        },
        campaign_detail_id: function (page, data, cmd) {
            var url = Api_Path.Loyalty+'campaign/detail-id?page='+page;

            if(cmd != undefined){
                url +='&cmd=' + cmd;
            }

            return $http({
                url: url,
                method: "GET",
                params: data,
                dataType: 'jsonp'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
            })

            return;
        },
        create_campaign_detail: function (data) {
            var url = Api_Path.Loyalty+'campaign/add-detail';
            return $http({
                url: url,
                method: "POST",
                data: data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', PhpJs.convertString(result.error_message));
                }else{
                    toaster.pop('success', 'Thông báo', result.error_message);
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                }
            })
            return;
        },
        create_campaign: function (data) {
            var url = Api_Path.Loyalty+'campaign/create';
            return $http({
                url: url,
                method: "POST",
                data: data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', PhpJs.convertString(result.error_message));
                }else{
                    toaster.pop('success', 'Thông báo', result.error_message);
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                }
            })
            return;
        },
        change_campaign: function (data) {
            return $http({
                url: Api_Path.Loyalty+'campaign/edit-campaign',
                method: "POST",
                data: data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', PhpJs.convertString(result.error_message));
                }else{
                    toaster.pop('success', 'Thông báo', result.error_message);
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                }
            })
            return;
        },
        change_campaign_detail: function (data) {
            return $http({
                url: Api_Path.Loyalty+'campaign/edit-campaign-detail',
                method: "POST",
                data: data,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', PhpJs.convertString(result.error_message));
                }else{
                    toaster.pop('success', 'Thông báo', result.error_message);
                }
            }).error(function (data, status, headers, config) {
                if (status == 440) {
                    Storage.remove();
                } else {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                }
            })
            return;
        }
    }
}])
.service('Courier', ['$http', 'Api_Path', 'toaster', 'Storage', 'PhpJs', function ($http, Api_Path, toaster, Storage, PhpJs) {
    return{
        estimate: function (page, data, cmd) {
            var url = Api_Path.Ops+'courier/estimate-courier?page='+page;

            if(cmd != undefined){
                url +='&cmd=' + cmd;
            }

            return $http({
                url: url,
                method: "GET",
                params: data,
                dataType: 'jsonp'
            }).success(function (result, status, headers, config) {
                if (result.error) {
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
            })

            return;
        },
    };
}])

/*





                                                    BOP ME









 */
    .service('BMBase', ['$http', 'Api_Path', 'toaster', 'Storage', function ($http, Api_Path, toaster, Storage) {
        return{
            ItemStatus: function () {
                return $http({
                    url: Api_Path._Base + 'item-status-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            GroupStatus: function () {
                return $http({
                    url: Api_Path._Base + 'status-group?group=6',
                    method: "GET",
                    dataType: 'json'
                })

                return;
            },
            ShipmentStatus: function () {
                return $http({
                    url: Api_Path._Base + 'shipment-status-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            PickupItemStatus: function () {
                return $http({
                    url: Api_Path._Base + 'pickup-status-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            PackageStatus: function () {
                return $http({
                    url: Api_Path._Base + 'package-status-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            WareHouseStatus: function () {
                return $http({
                    url: Api_Path._Base + 'warehouse-status-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            WareHouse: function () {
                return $http({
                    url: Api_Path._Base + 'ware-house-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            DrStatus: function () {
                return $http({
                    url: Api_Path._Base + 'dr-status-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            PutAwayStatus: function () {
                return $http({
                    url: Api_Path._Base + 'putaway-status-boxme',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            Volume: function () {
                return $http({
                    url: Api_Path._Base + 'bm-seller-packing',
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            }
        }
    }])
    .service('Warehouse', ['$http', 'Api_Path', 'toaster', 'Storage', function ($http, Api_Path, toaster, Storage) {
        return{
            return_slow: function (page,data,cmd) {
                var url = Api_Path.Ops + 'warehouse-problem/return-slow?page='+page;

                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            return_slow_count_group: function (data) {
                var url = Api_Path.Ops + 'warehouse-problem/count-group-return-slow';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            package_slow: function (page,data,cmd) {
                var url = Api_Path.Ops + 'warehouse-problem/package-slow?page='+page;

                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            package_slow_count_group: function (data) {
                var url = Api_Path.Ops + 'warehouse-problem/count-group-package-slow';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            pickup_slow: function (page,data,cmd) {
                var url = Api_Path.Ops + 'warehouse-problem/pickup-slow?page='+page;

                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            pickup_slow_count_group: function (data) {
                var url = Api_Path.Ops + 'warehouse-problem/count-group-pickup-slow';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            shipment_missing: function (page,data,cmd) {
                var url = Api_Path.Ops + 'warehouse-problem/shipment-lost?page='+page;

                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            shipment_lost_count_group: function (data) {
                var url = Api_Path.Ops + 'warehouse-problem/count-group-shipment-lost';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            packed_error_size: function (page,data,cmd) {
                var url = Api_Path.Ops + 'warehouse-problem/error-size?page='+page;

                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            shipment: function (page, data, cmd) {
                var url = Api_Path.Ops + 'warehouse-shipment?page='+page;
                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            shipment_count_group: function (data) {
                var url = Api_Path.Ops + 'warehouse-shipment/count-group';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            search: function (data) {
                var url = Api_Path.Ops + 'warehouse-shipment/search';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            list_freeze: function (merchant) {
                delete $http.defaults.headers.common['Authorization'];
                var url = 'http://seller.boxme.vn/api/get_uid_stocked/'+merchant;

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'jsonp'
                }).success(function (result, status, headers, config) {
                    return result;
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                }).finally(function(result) {
                    return result;
                    $http.defaults.headers.common['Authorization']  = $rootScope.userInfo.token;
                });
            },
            edit  : function(id,data) {
                return $http({
                    url: Api_Path.Ops + 'warehouse-shipment/edit/'+id,
                    method: "POST",
                    data: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (!result.error) {
                        toaster.pop('success', 'Thông báo', result.error_message);
                    } else {
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            },
            package: function (page, data, cmd) {
                var url = Api_Path.Ops + 'warehouse-packed?page='+page;

                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            package_count_group: function (data) {
                var url = Api_Path.Ops + 'warehouse-packed/count-group';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            return: function (page, data, cmd) {
                var url = Api_Path.Ops + 'warehouse-return?page='+page;
                if(cmd != undefined){
                    url +='&cmd=' + cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            return_count_group: function (data) {
                var url = Api_Path.Ops + 'warehouse-return/count-group';
                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
        }
    }])
    .service('KPI', ['$http', 'Api_Path', 'toaster', 'Storage', function ($http, Api_Path, toaster, Storage) {
        return {
            'Category': function (data) {
                var url = Api_Path.Ops + 'kpi/category';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'Config': function (data) {
                var url = Api_Path.Ops + 'kpi/config';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'CreateGroup': function (item) {
                return $http({
                    url: Api_Path.Ops + 'kpi/create-group-category',
                    method: "POST",
                    data: item,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Lỗi, hãy thử lại');
                    }else{
                        toaster.pop('success', 'Thông báo', 'Thành công');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            },
            'CreateCategory': function (item) {
                return $http({
                    url: Api_Path.Ops + 'kpi/create-category',
                    method: "POST",
                    data: item,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Lỗi, hãy thử lại');
                    }else{
                        toaster.pop('success', 'Thông báo', 'Thành công');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            },
            'CreateConfig': function (item) {
                return $http({
                    url: Api_Path.Ops + 'kpi/create-config',
                    method: "POST",
                    data: item,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Lỗi, hãy thử lại');
                    }else{
                        toaster.pop('success', 'Thông báo', 'Thành công');
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            }
        }
    }])
    .service('Report', ['$http', 'Api_Path', 'toaster', 'Storage', function ($http, Api_Path, toaster, Storage) {
        return {
            'SaleEmloyee': function (data) {
                var url = Api_Path.Ops + 'report/sale-employee';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'SaleWonByOwner' :  function (data) {
                var url = Api_Path.Ops + 'kpi/opportunity';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'SaleOverView' :  function (data) {
                var url = Api_Path.Ops + 'report/sale-over-view';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'SaleRevenueEmployee' :  function (data) {
                var url = Api_Path.Ops + 'report/sale-revenue-employee';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'SaleCustomer' :  function (page, data) {
                var url = Api_Path.Ops + 'report/sale-customer?page='+page;

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'FollowUpCustomers' :  function (page, data) {
                var url = Api_Path.Ops + 'report/follow-up-customers?page='+page;

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'UpdateCustomer': function (data) {
                return $http({
                    url: Api_Path.Ops + 'report/update-customer',
                    method: "POST",
                    dataType: 'json',
                    params: data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', result.message_error);
                    }else{
                        toaster.pop('success', 'Thông báo', result.message_error);
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            },
            'StatisticKpi' :  function (data) {
                var url = Api_Path.Ops + 'report/statistic-kpi';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'StatisticKpiTeam' :  function (data) {
                var url = Api_Path.Ops + 'report/statistic-kpi-team';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'StatisticKpiByDate' :  function (data) {
                var url = Api_Path.Ops + 'report/statistic-kpi-by-date';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'StatisticKpiTeamByDate' :  function (data) {
                var url = Api_Path.Ops + 'report/statistic-kpi-team-by-date';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'StatisticReturnMerchant' :  function (data) {
                var url = Api_Path.Ops + 'report/statistic-return-merchant';

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'StatisticCustomer' :  function (page, data) {
                var url = Api_Path.Ops + 'report/statistic-employee?page='+page;

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            'UpdateKpi': function (data) {
                return $http({
                    url: Api_Path.Ops + 'report/update-kpi',
                    method: "POST",
                    dataType: 'json',
                    params: data,
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', result.message_error);
                    }else{
                        toaster.pop('success', 'Thông báo', result.message_error);
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            }
        }
    }]);