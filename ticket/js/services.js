'use strict';

/* Services */



// Demonstrate how to register services
angular.module('app.services', [])
    .service('User', ['$http', '$q', 'Api_Path', function ($http, $q, Api_Path) {
        return {
            load: function (val) {
                return $http({
                    url: Api_Path.list_user + '&search=' + val,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    return;
                })

            },
            identifier: function (id, data) {
                return $http.post(Api_Path.Base + 'user/identifier/' + id, data);
            }
        }
    }])
    .service('Storage', ['$localStorage', '$state', '$timeout', 'toaster', function ($localStorage, $state, $timeout, toaster) {
        return {
            remove: function () {
                delete $localStorage['login'];
                delete $localStorage['time_login'];
                toaster.pop('error', 'Thông báo', 'Bạn chưa đăng nhập!');
                var yourTimer = $timeout(function () {
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

        var addslashes = function (str) {
            return (str + '')
                .replace(/[\\"']/g, '\\$&')
                .replace(/\u0000/g, '\\0');
        };

        var utf8_encode = function (string) {
            string = (string + '').replace(/\r\n/g, "\n").replace(/\r/g, "\n");

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
                } else if ((c1 > 127) && (c1 < 2048)) {
                    enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
                } else {
                    enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
                }
                if (enc != null) {
                    if (end > start) {
                        utftext += string.substring(start, end);
                    }
                    utftext += enc;
                    start = end = n + 1;
                }
            }

            if (end > start) {
                utftext += string.substring(start, string.length);
            }

            return utftext;
        }

        var md5 = function (str) {
            var xl;

            var rotateLeft = function (lValue, iShiftBits) {
                return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
            };

            var addUnsigned = function (lX, lY) {
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

            var _F = function (x, y, z) {
                return (x & y) | ((~x) & z);
            };
            var _G = function (x, y, z) {
                return (x & z) | (y & (~z));
            };
            var _H = function (x, y, z) {
                return (x ^ y ^ z);
            };
            var _I = function (x, y, z) {
                return (y ^ (x | (~z)));
            };

            var _FF = function (a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(_F(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            };

            var _GG = function (a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(_G(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            };

            var _HH = function (a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(_H(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            };

            var _II = function (a, b, c, d, x, s, ac) {
                a = addUnsigned(a, addUnsigned(addUnsigned(_I(b, c, d), x), ac));
                return addUnsigned(rotateLeft(a, s), b);
            };

            var convertToWordArray = function (str) {
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

            var wordToHex = function (lValue) {
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

        var ScenarioTime = function (time_str) {
            var str = '';
            if (time_str > 0) {
                var hours = Math.floor(time_str / 60);

                if (hours > 518400) {
                    str = Math.floor(hours / 518400) + ' năm';
                }
                else if (hours > 43200) { // 30 ngày
                    str = Math.floor(hours / 43200) + ' tháng';
                } else if (hours > 1440) { // 1 ngày
                    str = Math.floor(hours / 1440) + ' ngày';
                } else if (hours > 60) {// 1 hours
                    str = Math.floor(hours / 60) + ' giờ';
                } else if (hours > 0) {
                    str = hours + ' phút';
                } else {
                    str = '1 phút';
                }

            }
            return str;
        };

        return {
            rtrim: rtrim,
            addslashes: addslashes,
            md5: md5,
            ScenarioTime: ScenarioTime
        }
    })
    .service('Ticket', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', function ($http, $q, Api_Path, Storage, toaster) {
        return {
            ListFeedback: function (id) {
                return $http({
                    url: Api_Path.Base + 'ticket-feedback/byticket/' + id,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {

                })
                return;
            },
            SearchRefer: function (code) {
                return $http({
                    url: Api_Path.Base + 'ticket-refer/refer?code=' + code,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                    }
                });
            },
            ListGroup: function () {
                return $http({
                    url: Api_Path.Base + 'ticket-assign/group',
                    method: "GET",
                    dataType: 'json'
                });
            },
            ListReferTicket: function (ids, callback) {
                $http({
                    url: Api_Path.Base + 'ticket-refer/referseller?close=true&code=' + ids,
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        callback(true, result.message);
                    } else {
                        callback(false, result);
                    }
                }).error(function (data, status, headers, config) {
                    callback(true, null);
                })
            }
        }
    }])
    .service('Notify', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', function ($http, $q, Api_Path, Storage, toaster) {
        return {
            // verify
            count: function () {
                return $http({
                    url: Api_Path.Base + 'queue/count',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                    }
                })
                return;
            },
            get: function () {
                return $http({
                    url: Api_Path.Base + 'user/notifybyuser',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                    }
                })
                return;
            },
            list_user_admin: function () {
                return $http({
                    url: ApiPath + 'user-info/useradmin',
                    method: "GET",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if (result.error) {
                        toaster.pop('warning', 'Thông báo', 'Tải danh sách thành viên lỗi !');
                    }
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
    .service('Order', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', 'PhpJs', function ($http, $q, Api_Path, Storage, toaster, PhpJs) {
        return {
            PipeStatus: function (group, type) {
                return $http({
                    url: ApiPath + 'pipe-status/pipebygroup?group=' + group + '&type=' + type,
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
            ListStatus: function (group) {
                var url = Api_Path.OrderStatus + '/statusgroup';
                if (group) {
                    url += '?group=' + group
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
            Status: function () {
                return $http({
                    url: Api_Path.Base + 'list_status',
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
            }
            ,
            Recent: function (userID) {
                return $http.get(Api_Path.Base + 'order/recent/' + userID);
            },
            AcceptStatus: function (data, callback) {
                $http({
                    url: ApiJourney + 'acceptstatus',
                    method: "POST",
                    data: data,
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if (result.error == 'success') {
                        toaster.pop('success', 'Thông báo', 'Cập nhật thành công!');
                    } else {
                        toaster.pop('warning', 'Thông báo', resp.error_message);
                    }
                    callback(result.error, result.data);
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    callback(true, data.message);
                });
            },
        }
    }])
    .service('Report', function ($http, Api_Path) {
        this.insight = function (data) {
            return $http.get(Api_Path.Base + 'statistic/insight', {params: data});
        }
        this.statistic = function (data) {
            return $http.get(Api_Path.Base + 'statistic/statistic', {params: data});
        }
        this.getCase = function () {
            return $http.get(Api_Path.Base + 'ticket-case');
        }
        this.getCaseType = function (case_id) {
            return $http.get(Api_Path.Base + 'ticket-case-type');
        }
        this.reportCaseType = function (data) {
            return $http.get(Api_Path.Base + 'statistic/report-case-type', {params: data});
        }
        this.reportCase = function (data) {
            return $http.get(Api_Path.Base + 'statistic/report-cases', {params: data});
        }
    })
    .service('ReplyTemplate', function ($http, Api_Path) {
        this.list = function (data) {
            return $http.get(Api_Path.Base + 'reply-template', {params: data});
        };
        this.save = function (id, data) {
            return $http.post(Api_Path.Base + 'reply-template/save/' + id, data);
        };
        this.load = function (id) {
            return $http.get(Api_Path.Base + 'reply-template/detail/' + id);
        };
    })
    .service('Location', ['$http', '$q', 'Api_Path', 'Storage', 'toaster', 'PhpJs', function ($http, $q, Api_Path, Storage, toaster, PhpJs) {
        return {

            province: function (limit) {
                var url_location = ApiPath + 'city';
                if (limit.length > 0) {
                    url_location += '?limit=' + limit;
                }

                return $http({
                    url: url_location,
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

            district: function (province_id, limit, remote) {
                var url_location = ApiPath + 'district?';
                if (province_id > 0) {
                    url_location += 'city_id=' + province_id + '&';
                }

                if (limit.length > 0) {
                    url_location += 'limit=' + limit + '&';
                }

                if (remote) {
                    url_location += 'remote=true&';
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
                return;
            },
            ward: function (district_id, limit) {
                var url_location = ApiPath + 'ward?';
                if (district_id > 0) {
                    url_location += 'district_id=' + district_id + '&';
                }

                if (limit.length > 0) {
                    url_location += 'limit=' + limit + '&';
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
                return;
            },
            SuggestAll: function (val) {
                if (val == '' || val == undefined) {
                    return;
                }

                return $http({
                    url: Api_Path.Search + PhpJs.addslashes(val) + '&size=10',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                    }
                })
                return;
            }
        }
    }])
    .service('Base', ['$http', '$q', '$window', 'Api_Path', 'PhpJs', 'toaster', 'Storage', function ($http, $q, $window, Api_Path, PhpJs, toaster, Storage) {
        return {
            // verify
            list_case: function () {
                return $http({
                    url: Api_Path._Base + 'api/base/case-ticket',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                    }
                })
                return;
            },
            list_type_case: function () {
                return $http({
                    url: Api_Path._Base + 'api/base/case-type',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    if (status == 440) {
                        Storage.remove();
                    } else {
                        toaster.pop('error', 'Thông báo', 'Kết nối dữ liệu thất bại, hãy thử lại!');
                    }
                })
                return;
            },
            status: function () {
                return $http({
                    url: Api_Path._Base + 'api/base/status',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            group_status: function () {
                return $http({
                    url: Api_Path._Base + 'api/base/status-group',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            PipeStatus: function (group, type) {
                return $http({
                    url: Api_Path.Pipe + '/pipebygroup?group=' + group + '&type=' + type,
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
            getGroupProcess: function (group, type) {
                return $http({
                    url: Api_Path.Pipe + '/group-process?group=' + group + '&type=' + type,
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
            assign_group: function () {
                return $http({
                    url: Api_Path._Base + 'ticket/base/group-assign',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            },
            user_admin: function () {
                return $http({
                    url: Api_Path._Base + 'ticket/base/user-admin',
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
            city: function () {
                return $http({
                    url: Api_Path._Base + 'api/base/city',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {

                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })

                return;
            }
        }
    }])
    .service('Upload', ['$http', '$q', '$window', 'Api_Path', 'Storage', 'toaster', 'PhpJs', function ($http, $q, $window, Api_Path, Storage, toaster, PhpJs) {
        return{
            ListImport : function(page, data){
                var url    = ApiOms+'upload/listimport?page='+page;

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
                var url    = ApiOms+'upload/listupload/'+id+'?page='+page;

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
            Journey : function(id){
                return $http({
                    url: ApiOms+'upload/journey/'+id,
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
                    url: ApiOms+'upload/weight/'+id,
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
                    url: ApiOms+'upload/process/'+id,
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
                    url: ApiOms+'upload/status-verify/'+id,
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
