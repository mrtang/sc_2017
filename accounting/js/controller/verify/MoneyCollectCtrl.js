'use strict';

//Verify Money Collect
angular.module('app').controller('MoneyCollectCtrl', ['$scope', 'CourierVerify',
 	function($scope, CourierVerify) {
    /*
        Config
     */
        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_start"){
                $scope.time_start_open = true;
            }else if(type == "time_end"){
                $scope.time_end_open = true;
            }
        };

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.totalItems           = 0;

        $scope.frm                  = {type : 'money_collect'};

        $scope.time_start           = new Date(date.getFullYear(), date.getMonth(), 1);
        $scope.time_end             = '';
        $scope.waiting              = true;

    /*
        End Config
     */

    /*
        Action
     */

        $scope.keys = function(obj){
            return obj? Object.keys(obj) : [];
        }

        $scope.refresh = function(){
            $scope.list_data        = {};
            $scope.list_user        = [];
        }

        $scope.setPage = function(page){
            $scope.currentPage = page;
            $scope.waiting   = true;

            $scope.frm.time_start   = '';
            $scope.frm.time_end     = '';

            if($scope.time_start != undefined && $scope.time_start != ''){
                $scope.frm.time_start       = +Date.parse($scope.time_start)/1000;
            }
            if($scope.time_end != undefined && $scope.time_end != ''){
                $scope.frm.time_end         = +Date.parse($scope.time_end)/1000 + 86399;
            }

            $scope.refresh();
            CourierVerify.load($scope.currentPage, $scope.frm).then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.list_user        = result.data.user;
                    $scope.totalItems       = result.data.total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting   = false;
                return;
            });
        }
        $scope.setPage(1);

    /*
        End Action
     */

    }
]);
