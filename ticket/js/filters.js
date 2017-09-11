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
    .filter('badWords', function($http) {
        var badWords = [
            'dkm',
            'địt',
            'lồn',
            'con cặc',
            'tao',
            'chúng mày',
            'chung mày',
            'lũ chúng mày',
            'bọn mày',
            'chung may',
            'cụ mày',
            'bố mày',
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
            'dit',
            'địt cụ',
            'thằng cụ',
            'Mả cha',
            'mả cha',
            'tổ cha',
            'Tổ cha',
            'mẹ nhà',
            'mẹ nhà',
            'tổ cụ',
            'Tổ cụ'
        ];

        return function(input) {
            angular.forEach(badWords, function(word){
                var str = word.substring(0,1)+"\\s*";
                for (var i = 1; i < word.length - 1; i++) str = str + word.substring(i,i+1)+"\\s*";
                str = str + word.substring(word.length - 1,word.length);
                var regEx = new RegExp(str, "gi");
                input = input.replace(regEx, "---");
            });

            return input;
        };

    })
    .filter('time', function() {
        return function(secound) {
            var day = 0,hour = 0, minute = 0;
            if(secound>=86400) {
                day = Math.floor(secound/86400);
                secound = secound - day*86400;
            }
            if(secound>=3600) {
                hour = Math.floor(secound/3600);
                secound = secound - hour*3600;
            }
            if(secound>=60) {
                minute = Math.floor(secound/60);
            }
            return day + ' ngày ' + hour + 'h' + minute + '"';
        }
    });