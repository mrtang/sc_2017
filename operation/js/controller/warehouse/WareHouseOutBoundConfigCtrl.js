'use strict';

angular.module('app').controller('WareHouseOutBoundConfigCtrl', ['$scope', '$localStorage', '$filter', 'Boxme_Status',
 	function($scope, $localStorage, $filter, Boxme_Status) {
        $scope.currentPage      = 1;
        $scope.item_page        = 20;
        $scope.maxSize          = 5;
        $scope.waiting_export   = false;

        $scope.list_color           = Boxme_Status.status_color;
    }
]);