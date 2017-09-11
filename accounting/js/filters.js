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
    .filter('phone', function () {
        return function (phone) {
            if (phone) {
                phone.match(/.{1,3}/g).join('-');
            } else {
                return '';
            }
        }
    });