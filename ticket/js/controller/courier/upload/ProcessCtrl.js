'use strict';

//Verify Money Collect
angular.module('app').controller('ProcessCtrl', ['$scope', '$http', '$state', '$window', 'toaster', 'Upload',
 	function($scope, $http, $state, $window, toaster, Upload) {
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
            if(type == "create_start"){
                $scope.create_start_open = true;
            }else if(type == "create_end"){
                $scope.create_end_open = true;
            }
        };
        var date                    = new Date();
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), 1), create_end : ''};
        $scope.frm                  = {type : 'process'};
        $scope.waiting              = true;
        $scope.User                 = [];
        $scope.list_data             = {};

    /*
        End Config
     */

    /*
        Action
     */

        $scope.keys = function(obj){
            return obj? Object.keys(obj) : [];
        };

        $scope.refresh = function(){
            $scope.list_data        = {};
            $scope.User             = [];
        };

        $scope.setPage = function(page){
            $scope.currentPage = page;
            $scope.waiting   = true;
            if($scope.time.create_start != undefined && $scope.time.create_start != ''){
                $scope.frm.create_start     = +Date.parse($scope.time.create_start)/1000;
            }else{
                $scope.frm.create_start     = '';
            }

            if($scope.time.create_end != undefined && $scope.time.create_end != ''){
                $scope.frm.create_end       = +Date.parse($scope.time.create_end)/1000 + 86399;
            }else{
                $scope.frm.create_end     = '';
            }

            $scope.refresh();
            Upload.ListImport($scope.currentPage, $scope.frm).then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.User             = result.data.user;
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
