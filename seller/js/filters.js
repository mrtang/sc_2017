'use strict';

/* Filters */
// need load the moment.js to use this filter. 
angular.module('app.filters', [])
    .filter('fromNow', function() {
        return function(date) {
          return moment(date).fromNow();
        }
    })
    .filter('vnNumber', function ($filter) {
        return function (number) {
            return $filter('number')(number).replace(/,/g, '.');
        }
    })
    .filter('usdNumber', function ($filter) {
        return function (number) {
            return $filter('number')(number).replace(/,/g, '');
        }
    })
    .filter('badWords', function($http) {
        var badWords = [
            'dkm',
            'địt',
            'lồn',
            'con cặc',
            'tao',
            'chúng mày',
            'chung mày',
            'chung may',
            'cụ mày',
            'cụ chúng mày',
            'im mồm', 
            'im mom',
            'câm mồm',
            'câm',
            'mẹ chúng',
            'con mẹ',
            'bố chúng',
            'thằng bố',
            'con chó',
            'chó chết',
            'du ma',
            'đụ má',
            'chết',
            'dmm',
            'dit con me',
            'con me',
            'dit'
        ];

        return function(input) {
            angular.forEach(badWords, function(word){
                var str = word.substring(0,1)+"\\s*";
                for (var i = 1; i < word.length - 1; i++) str = str + word.substring(i,i+1)+"\\s*";
                str = str + word.substring(word.length - 1,word.length);
                var regEx = new RegExp(str, "gi");
                input = input.replace(regEx, "***");
            });

            return input;
        };

    })
    .filter('phone', function () {
        return function (tel) {
            if (!tel) { return ''; }

            var value = tel.toString().trim().replace(/^\+/, '');


            value= value.split(/[\D]+/);
            var str = '';

            switch (value[0].length) {
                case 9: // +1PPP####### -> C (PPP) ###-####
                    str    += value[0].slice(0, 3);
                    str    += '-'+value[0].slice(3,6);
                    str    += '-'+value[0].slice(6);
                    break;

                case 10 : // +CPPP####### -> CCC (PP) ###-####
                case 11 :
                    str    += value[0].slice(0, 4);
                    str    += '-'+value[0].slice(4,7);
                    str    += '-'+value[0].slice(7);
                    break;

                default:
                    return tel;
            }

            if(value[1]){
                switch (value[1].length) {
                    case 9: // +1PPP####### -> C (PPP) ###-####
                        str    += ' , '+value[1].slice(0, 3);
                        str    += '-'+value[1].slice(3,6);
                        str    += '-'+value[1].slice(6);
                        break;

                    case 10 : // +CPPP####### -> CCC (PP) ###-####
                    case 11 :
                        str    += ' , '+value[1].slice(0, 4);
                        str    += '-'+value[1].slice(4,7);
                        str    += '-'+value[1].slice(7);
                        break;

                    default:
                        return tel;
                }
            }

            return str.trim();
        };
    })
    .filter('exists', function () {
        return function (arr) {
            var r = [];
            if (arr) {
                angular.forEach(arr, function(value, key) {
                    if(value){
                        r.push(value);
                    }
                });
                return r;
            }
            return [];
        }
    })
    .filter('orderObjectBy', function(){
    return function(input, attribute) {
        if (!angular.isObject(input)) return input;

        var array = [];
        for(var objectKey in input) {
            array.push(input[objectKey]);
        }

        array.sort(function(a, b){
            a = parseInt(a[attribute]);
            b = parseInt(b[attribute]);
            return a - b;
        });
        return array;
    }
    })
    .filter('productNameOrSellerSku', function () {
        return function (list, keyword) {
            var filtered = [];
            window.bodauTiengViet = function(str) {  
                if(!str){
                    return str;
                }
                str= str.toLowerCase();  
                str= str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a");  
                str= str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e");  
                str= str.replace(/ì|í|ị|ỉ|ĩ/g,"i");  
                str= str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o");  
                str= str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u");  
                str= str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y");  
                str= str.replace(/đ/g,"d");  
                return str;  
            }
            angular.forEach(list, function (product) {
                if (
                    (product.Name && window.bodauTiengViet(product.Name.toString()).indexOf(window.bodauTiengViet(keyword)) > -1) ||
                    (window.bodauTiengViet(product.SellerSKU.toString()).indexOf(window.bodauTiengViet(keyword)) > -1)
                )
                {
                    filtered.push(product)
                }
            });

            return filtered;
        }
    })
    ;