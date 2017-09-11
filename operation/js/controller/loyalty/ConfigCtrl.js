'use strict';

angular.module('app').controller('ConfigCtrl', ['$scope', '$localStorage', 'Base',
 	function($scope, $localStorage, Base) {
        $scope.currentPage      = 1;
        $scope.item_page        = 20;
        $scope.maxSize          = 5;
        $scope.sc_loyalty_category  = {};

        $scope.get_loyalty_category = function(){
            if($localStorage['sc_loyalty_category'] != undefined){
                $scope.sc_loyalty_category    = $localStorage['sc_loyalty_category'];
            }else{
                Base.loyalty_category().then(function (result) {
                    if(!result.data.error){
                        $localStorage['sc_loyalty_category']   = result.data.data;
                        $scope.sc_loyalty_category                = result.data.data;
                    }
                }).finally(function() {

                });
            }
        }

        $scope.type_phone = {
            1 : 'Viettel Post',
            2 : 'VinaPhone',
            3 : 'MobiPhone',
            4 : 'VietnamMobile'
        };

        $scope.get_loyalty_category();
    }
]);