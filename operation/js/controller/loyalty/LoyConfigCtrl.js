'use strict';
angular.module('app')
.controller('LoyConfigCtrl', ['$scope', 'Loyalty',
function($scope, Loyalty) {
    $scope.changeConfig   = function(item,value,field){
        item.waiting  = true;
        var dataupdate = {id : item.id};
        dataupdate[field] = value;

        return Loyalty.change_level(dataupdate).then(function (result) {
            if(result.data.error){
                return result.data.error_message;
            }
        }).finally(function() {
            item.waiting  = false;
        });
    }

    $scope.changeActive = function(item){
        return $scope.changeConfig(item,item.active,'active');
    }

}]);

