'use strict';

angular.module('app').controller('WareHouseProblemConfigCtrl', ['$scope', '$localStorage', 'BMBase', 'Base', 'Config_Status',
 	function($scope, $localStorage, BMBase, Base, Config_Status) {
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.list_color           = Config_Status.order_color;

        $scope.area_location    = [
            { code : 1      , content : 'Nội thành'},
            { code : 2      , content : 'Ngoại thành'},
            { code : 3      , content : 'Liên tỉnh'}
        ];

        $scope.calculate_item = function(item){
            var quantity = 0;
            angular.forEach(item.order_item, function(value) {
                angular.forEach(item.order_item, function(value) {
                    quantity    += 1;
                });
            });

            return quantity;
        }
        
    }
]);