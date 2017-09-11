'use strict';

/* Services */
angular.module('app.services', [])
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
            md5                     : md5,
            array_merge_recursive   : array_merge_recursive,
            ScenarioTime            : ScenarioTime,
            convertString           : convertString,
            date_format             : date_format,
            utf8_encode             : utf8_encode
        }
    })
    .service('MerchantVerify', ['$http', '$q', 'Api_Path', 'toaster', 'Storage', function ($http, $q, Api_Path, toaster, Storage) {
        return{
            verify_detail : function(page, id, search, time_start){
                if(id > 0){
                    var url = Api_Path.Acc+'verify/verify-detail/'+id+'?page='+page;

                    if(time_start != undefined && time_start != ''){
                        url += '&time_start='+time_start;
                    }

                    if(search != '' && search != undefined){
                        url += '&search='+search;
                    }

                    return $http({
                        url: url,
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
                            toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                        }
                    })
                }
            },
            Verify: function (id) {
                if (id != '' && id != undefined) {
                    return $http({
                        url: Api_Path.Acc+'verify/verify-request/' + id ,
                        method: "GET",
                        dataType: 'json'
                    }).success(function (result, status, headers, config) {
                        return result
                    }).error(function (data, status, headers, config) {
                        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                        return;
                    })
                }
                return;
            },
            Freeze: function (page, id, data) {
                return $http({
                    url: Api_Path.Acc+'verify/show-freeze/'+id+'?page=' + page ,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    return result
                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                    return;
                })
                return;
            }
        }
    }])

    // Base service
    .service('Base', ['$http', 'Api_Path', 'toaster', function ($http, Api_Path, toaster) {
        return {
            GroupStatus: function () {
                return $http({
                    url: Api_Path.Base + 'status-group',
                    method: "GET",
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
            Courier: function () {
                return $http({
                    url: Api_Path.Base + 'courier',
                    method: "GET",
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
            UserVip : function(){
                return $http({
                    url: Api_Path.Base + 'loyalty-user',
                    method: "GET",
                    dataType: 'json'
                })

                return;
            },
            City: function () {
                return $http({
                    url: Api_Path.Base + 'city',
                    method: "GET",
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
            District: function ($city) {
                return $http({
                    url: Api_Path.Base + 'district?city='+$city,
                    method: "GET",
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
            AllDistrict: function () {
                return $http({
                    url: Api_Path.Base + 'all-district',
                    method: "GET",
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
            Status: function () {
                return $http({
                    url: Api_Path.Base + 'status',
                    method: "GET",
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
            Service: function () {
                return $http({
                    url: Api_Path.Base + 'service',
                    method: "GET",
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
            loyalty_level: function () {
                return $http({
                    url: Api_Path.Base+'loyalty-level',
                    method: "GET",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                })
            },
            WareHouse: function () {
                return $http({
                    url: Api_Path.Base + 'ware-house-boxme',
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
        }
    }])
    //Courier Verify
    .service('CourierVerify', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', 'PhpJs', function ($http, $window, Api_Path, toaster, Storage, PhpJs) {
        return {
            load : function(page, frm){
                return $http({
                    url: Api_Path.Acc+'courier-verify/list-import?page='+page,
                    method: "GET",
                    params: frm,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', 'Lỗi ' + PhpJs.convertString(result.message));
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })
                return;
            },
            load_excel: function (page, frm, cmd) {
                if (page > 0 && frm.id != '' && frm.id != undefined) {
                    var url = Api_Path.Acc+'courier-verify/list-excel/' + frm.id + '?page=' + page + '&tab=' + frm.tab+'&type='+frm.type;;

                    if(cmd != undefined && cmd != ''){
                        url += '&cmd='+cmd;
                    }

                    return $http({
                        url: url,
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
                            toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                        }
                    })
                }
                return;
            },
            money_collect: function (id) {
                if (id != '' && id != undefined) {
                    return $http({
                        url: Api_Path.Acc+'courier-verify/verify-money-collect/' + id ,
                        method: "GET",
                        dataType: 'json'
                    }).success(function (result, status, headers, config) {
                        return result
                    }).error(function (data, status, headers, config) {
                        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                        return;
                    })
                }
                return;
            },
            fee: function (id) {
                if (id != '' && id != undefined) {
                    return $http({
                        url: Api_Path.Acc+'courier-verify/verify-fee/' + id ,
                        method: "GET",
                        dataType: 'json'
                    }).success(function (result, status, headers, config) {
                        return result
                    }).error(function (data, status, headers, config) {
                        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                        return;
                    })
                }
                return;
            },
            service: function (id) {
                if (id != '' && id != undefined) {
                    return $http({
                        url: Api_Path.Acc+'courier-verify/verify-service/' + id ,
                        method: "GET",
                        dataType: 'json'
                    }).success(function (result, status, headers, config) {
                        return result
                    }).error(function (data, status, headers, config) {
                        toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                        return;
                    })
                }
                return;
            }
        }
    }])

    //Get Order
    .service('Order', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            ListOrder: function (page, data, cmd) {

                var from_city   = '';
                if(data.from_city != undefined){
                    from_city = data.from_city.split("-");
                    if(from_city[1] != undefined){
                        from_city   = parseInt(from_city[1]);
                    }else{
                        from_city   = '';
                    }
                    delete data.from_city;
                }

                var to_city   = '';
                if(data.to_city != undefined){
                    to_city = data.to_city.split("-");
                    if(to_city[1] != undefined){
                        to_city   = parseInt(to_city[1]);
                    }else{
                        to_city   = '';
                    }
                    delete data.to_city;
                }

                var from_district   = '';
                if(data.from_district != undefined){
                    var from_district = data.from_district.split("-");
                    if(from_district[1] != undefined){
                        from_district   = parseInt(from_district[1]);
                    }else{
                        from_district   = '';
                    }
                    delete data.from_district;
                }

                var to_district = '';
                if(data.to_district != undefined){
                    var to_district = data.to_district.split("-");
                    if(to_district[1] != undefined){
                        to_district   = parseInt(to_district[1]);
                    }else{
                        to_district   = '';
                    }
                    delete data.to_district;
                }


                var url = Api_Path.Acc + 'order?page=' + page;

                if (from_city != undefined && from_city > 0) {
                    url += '&from_city=' + from_city;
                }

                if (from_district != undefined && from_district > 0) {
                    url += '&from_district=' + from_district;
                }

                if (to_city != undefined && to_city > 0) {
                    url += '&to_city=' + to_city;
                }

                if (to_district != undefined && to_district > 0) {
                    url += '&to_district=' + to_district;
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
            CountGroup: function (data, type) {

                var from_city   = '';
                if(data.from_city != undefined){
                    from_city = data.from_city.split("-");
                    if(from_city[1] != undefined){
                        from_city   = parseInt(from_city[1]);
                    }else{
                        from_city   = '';
                    }
                }
                delete data.from_city;

                var to_city   = '';
                if(data.to_city != undefined){
                    to_city = data.to_city.split("-");
                    if(to_city[1] != undefined){
                        to_city   = parseInt(to_city[1]);
                    }else{
                        to_city   = '';
                    }
                }
                delete data.to_city;

                var from_district   = '';
                if(data.from_district != undefined){
                    var from_district = data.from_district.split("-");
                    if(from_district[1] != undefined){
                        from_district   = parseInt(from_district[1]);
                    }else{
                        from_district   = '';
                    }
                }
                delete data.from_district;

                var to_district = '';
                if(data.to_district != undefined){
                    var to_district = data.to_district.split("-");
                    if(to_district[1] != undefined){
                        to_district   = parseInt(to_district[1]);
                    }else{
                        to_district   = '';
                    }
                }
                delete data.to_district;

                if(type == 'status'){
                    var url = Api_Path.Acc + 'order/count-group-status?page=1';
                }else{
                    var url = Api_Path.Acc + 'order/count-group?page=1';
                }

                if (from_city != undefined && from_city > 0) {
                    url += '&from_city=' + from_city;
                }

                if (from_district != undefined && from_district > 0) {
                    url += '&from_district=' + from_district;
                }

                if (to_city != undefined && to_city > 0) {
                    url += '&to_city=' + to_city;
                }

                if (to_district != undefined && to_district > 0) {
                    url += '&to_district=' + data.to_district;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
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
            OrderAccounting : function(page,data, cmd){
                var url    = Api_Path.OrderAcc+'/providemerchant?';

                if(page > 0){
                    url += 'page='+page+'&';
                }

                if(data.merchant != undefined && data.merchant != ''){
                    url += 'merchant='+data.merchant+'&';
                }

                if(data.time_create_start != undefined && data.time_create_start != ''){
                    url += 'time_create_start='+data.time_create_start+'&';
                }

                if(data.time_create_end != undefined && data.time_create_end != ''){
                    url += 'time_create_end='+data.time_create_end+'&';
                }

                if(data.time_accept_start != undefined && data.time_accept_start != ''){
                    url += 'time_accept_start='+data.time_accept_start+'&';
                }

                if(data.time_accept_end != undefined && data.time_accept_end != ''){
                    url += 'time_accept_end='+data.time_accept_end+'&';
                }

                if(data.time_success_start != undefined && data.time_success_start != ''){
                    url += 'time_success_start='+data.time_success_start+'&';
                }

                if(data.time_success_end != undefined && data.time_success_end != ''){
                    url += 'time_success_end='+data.time_success_end+'&';
                }

                if(cmd != undefined && cmd != ''){
                    url += 'cmd='+cmd+'&';
                    $window.open(PhpJs.rtrim(url,'&'), '_blank');
                    return '';
                }

                return $http({
                    url: PhpJs.rtrim(url,'&'),
                    method: "GET",
                    data: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                });
                return;
            }
        }
    }])
    // CashIn
    .service('CashIn', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            load : function(page, frm, cmd){
                return $http({
                    url: Api_Path.Acc+'cash-in?page='+page,
                    method: "GET",
                    params: frm,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            }
        }
    }])
    // Recover
    .service('Recover', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            load_excel: function (page, frm, cmd) {
                var url = Api_Path.Acc + 'cash-out/list-excel/' + frm.id + '?page=' + page + '&tab=' + frm.tab + '&type=' + frm.type;

                if (cmd != undefined && cmd != '') {
                    url += '&cmd=' + cmd;
                    $window.open(PhpJs.rtrim(url, '&'), '_blank');
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
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })
                return;
            },
            verify: function (id) {
                return $http({
                    url: Api_Path.Acc + 'cash-out/recover/' + id,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    return result
                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                    return;
                })
                return;
            }
        }
    }])
    // Refund
    .service('Refund', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            load_excel: function (page, frm, cmd) {
                var url = Api_Path.Acc + 'cash-out/list-excel/' + frm.id + '?page=' + page + '&tab=' + frm.tab + '&type=' + frm.type;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                    $window.open(PhpJs.rtrim(url,'&'), '_blank');
                    return '';
                }

                return $http({
                    url: url,
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
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })
                return;
            },
            verify: function (id) {
                return $http({
                    url: Api_Path.Acc + 'cash-out/refund/' + id ,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                    return;
                })
                return;
            }
        }
    }])
    //Cash Out
    .service('CashOut', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            load : function(page, frm, cmd){
                return $http({
                    url: Api_Path.Acc+'cash-out?page='+page,
                    method: "GET",
                    params: frm,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            },
            load_excel: function (page, frm, cmd) {
                var url = Api_Path.Acc + 'cash-out/list-excel/' + frm.id + '?page=' + page + '&tab=' + frm.tab + '&type=' + frm.type;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                    $window.open(PhpJs.rtrim(url,'&'), '_blank');
                    return '';
                }

                return $http({
                    url: url,
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
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })
                return;
            },
            verify: function (id) {
                return $http({
                    url: Api_Path.Acc + 'cash-out/cash-out/' + id ,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống, thử lại!');
                    return;
                })
                return;
            }
        }
    }])
    // Audit
    .service('Audit', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            load : function(page, frm, cmd){
                var url = Api_Path.Acc+'merchant/audit?page='+page;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    params: frm,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('success', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })

            }
        }
    }])

    // Merchant
    .service('Merchant', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            load: function (page, data, cmd) {
                var url = Api_Path.Acc + 'merchant?page=' + page;

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
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                });

            },
            edit  : function(id,data){
                return $http({
                    url: Api_Path.Acc+'merchant/edit/'+id,
                    method: "POST",
                    data:data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        toaster.pop('success', 'Thông báo', result.error_message);
                    }else{
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
            }
        }
    }])
    // Report
    .service('Report', ['$http', '$window', 'Api_Path', 'toaster', 'Storage', function ($http, $window, Api_Path, toaster, Storage) {
        return {
            merchant : function(page, data, cmd){
                var url = Api_Path.Acc+'report?page='+page;

                if(data.from_day != undefined && data.from_day != ''){
                    url += '&from_day='+data.from_day;
                }
                if(data.to_day != undefined && data.to_day != ''){
                    url += '&to_day='+data.to_day;
                }
                if(data.month != undefined && data.month != ''){
                    url += '&month='+data.month;
                }
                if(data.search != undefined && data.search != ''){
                    url += '&search='+data.search;
                }

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                    $window.open(url, '_blank');
                    return '';
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            },
            order : function(page, data, cmd){
                var url = Api_Path.Acc+'report/report-order?page='+page;
                if(data.month != undefined && data.month != ''){
                    url += '&month='+data.month;
                }
                if(data.search != undefined && data.search != ''){
                    url += '&search='+data.search;
                }

                if(data.sort_date != undefined && data.sort_date != ''){
                    url += '&sort_date='+data.sort_date;
                }

                if(data.sort_value != undefined && data.sort_value != ''){
                    url += '&sort_value='+data.sort_value;
                }

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                    $window.open(url, '_blank');
                    return '';
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            },
            statistic : function (user_id, month) {
                var url = Api_Path.Acc+'report/report?page=1';
                if(user_id != undefined && user_id > 0){
                    url += '&user_id='+user_id;
                }

                if(month != undefined && month != ''){
                    url += '&time='+month;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })
                return;
            },
            location: function (user_id, month) {
                var url = Api_Path.Acc+'report/report-location?page=1';
                if(user_id != undefined && user_id > 0){
                    url += '&user_id='+user_id;
                }

                if(month != undefined && month != ''){
                    url += '&time='+month;
                }

                return $http({
                    url: url ,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            }
        }
    }])
    // Invoice
    .service('Invoice', ['$http', '$window', 'Api_Path', 'toaster', 'Storage','$rootScope', function ($http, $window, Api_Path, toaster, Storage,$rootScope) {
        return {
            load : function(page, data, cmd){
                var url = Api_Path.Acc +'invoice?page='+page;

                if(data.merchant != undefined && data.merchant != ''){
                    url += '&merchant='+data.merchant;
                }

                if(data.time_start != undefined && data.time_start != ''){
                    url += '&time_start='+data.time_start;
                }

                if(data.time_end != undefined && data.time_end != ''){
                    url += '&time_end='+data.time_end;
                }

                if(data.first_shipment_start != undefined && data.first_shipment_start != ''){
                    url += '&first_shipment_start='+data.first_shipment_start;
                }

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                    url += '&access_token='+$rootScope.userInfo.token;
                    $window.open(url, '_blank');
                    return '';
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
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
    // Transaction service
    .service('Transaction', ['$http', '$window', 'Api_Path', 'toaster', 'Storage','$rootScope', function ($http, $window, Api_Path, toaster, Storage,$rootScope) {
        return {
            load : function(page, data, cmd){
                var url = Api_Path.Acc+'transaction?page='+page;

                if(data.time_start != undefined && data.time_start > 0){
                    url += '&time_start='+data.time_start;
                }
                if(data.time_end != undefined && data.time_end > 0){
                    url += '&time_end='+data.time_end;
                }
                if(data.first_shipment_start != undefined && data.first_shipment_start > 0){
                    url += '&first_shipment_start='+data.first_shipment_start;
                }
                if(data.refer_code != undefined && data.refer_code != ''){
                    url += '&refer_code='+data.refer_code;
                }
                if(data.search != undefined && data.search != ''){
                    url += '&search='+data.search;
                }
                if(data.type != undefined && data.type != ''){
                    url += '&type='+data.type;
                }

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                    url += '&access_token='+$rootScope.userInfo.token;
                    $window.open(url, '_blank');
                    return '';
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            }
        }
    }])
    // Payment service
    .service('Payment', ['$http', 'Api_Path', 'toaster', 'Storage','$rootScope', function ($http, Api_Path, toaster, Storage,$rootScope) {
        return {
            load : function(page, data, cmd){
                var url = Api_Path.Acc+'verify?page='+page;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                    url += '&access_token='+$rootScope.userInfo.token;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params: data
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            }
        }
    }])
    .service('Warehouse', ['$http', 'Api_Path', 'toaster', 'Storage','$rootScope', function ($http, Api_Path, toaster, Storage,$rootScope) {
        return {
            wmstype : function(page, data, cmd){
                var url = Api_Path.Acc+'wmstype?page='+page;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params: data
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            },verify : function(page, data, cmd){
                var url = Api_Path.Acc+'warehouse-verify?page='+page;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params: data
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            },
            temporary : function(page, data, cmd){
                var url = Api_Path.Acc+'warehouse-verify/warehouse-fee?page='+page;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params: data
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            },
            partner_verify : function(page, data, cmd){
                var url = Api_Path.Acc+'partner-verify?page='+page;

                if(cmd != undefined && cmd != ''){
                    url += '&cmd='+cmd;
                }

                return $http({
                    url: url,
                    method: "GET",
                    dataType: 'json',
                    params: data
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', result.error_message);
                    }
                }).error(function (data, status, headers, config) {
                    if(status == 440){
                        Storage.remove();
                    }else{
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
                    }
                })

            },
        }
    }]);