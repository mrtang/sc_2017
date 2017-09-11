'use strict';

angular.module('app').controller('AddressInDayCtrl', ['$scope', '$rootScope', 'Address', 'Base',
 	function($scope, $rootScope, Address, Base) {

        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.group_order          = [];
        $scope.time                 = {create_start : new Date(date.getFullYear(), date.getMonth(), date.getDate()), create_end: null};
        $scope.frm                  = {group : 200, tab : 'ALL', vip : 0};
        $scope.list_data            = {};
        $scope.list_pipe_status     = {};
        $scope.pipe_status          = {};
        $scope.pipe_limit           = 0;
        $scope.pipe_priority        = {};
        $scope.check_box            = [];

        $scope.waiting              = true;

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_create_start"){
                $scope.time_create_start_open = true;
            }else if(type == "time_create_end"){
                $scope.time_create_end = true;
            }
        };

        $scope.toggleSelection = function(id) {
            var data = angular.copy($scope.check_box);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box.splice(idx, 1);
            }
            else {
                $scope.check_box.push(id);
            }
        };

        Base.PipeStatus(200, 3).then(function (result) {
            if(!result.data.error){
                $scope.list_pipe_status      = result.data.data;
                angular.forEach(result.data.data, function(value) {
                    if(value.priority > $scope.pipe_limit){
                        $scope.pipe_limit   = +value.priority;
                    }
                    $scope.pipe_status[value.status]    = value.name;
                    $scope.pipe_priority[value.status]  = value.priority;
                });
            }
        });

        $scope.refresh = function(cmd){
            
            if($scope.time.create_start != undefined && $scope.time.create_start != ''){
                $scope.frm.create_start   = +Date.parse($scope.time.create_start)/1000;
            }else{
                $scope.frm.create_start   = 0;
            }
            if($scope.time.create_end != undefined && $scope.time.create_end != ''){
                $scope.frm.create_end     = +Date.parse($scope.time.create_end)/1000 + 86399;
            }else{
                $scope.frm.create_end     = 0;
            }

            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.list_status      = $scope.check_box.toString();
            }else{
                $scope.frm.list_status  = '';
            }

            if(cmd != 'export'){
                $scope.list_data            = {};
                $scope.group_order          = {};
                $scope.waiting              = true;
            }

        }

        $scope.setPage = function(page){
            $scope.currentPage  = page;
            $scope.refresh('');
            Address.InDay($scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data            = result.data.data;
                    //$scope.group_order      = result.data.group;
                    $scope.total_group          = result.data.total_group;
                    $scope.total_group_picked   = result.data.total_group_picked;
                    $scope.total_group_pick_fail   = result.data.total_group_pick_fail;
                    $scope.totalItems           = result.data.total;
                    $scope.item_stt             = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }

        $scope.ChangeTab    = function(tab){
            $scope.frm.tab  = tab;
            $scope.setPage(1);
        }

        $scope.setPage(1);

        $scope.exportExcel = function(_export, notpickup){
            $scope.refresh(_export);
            return Address.InDay(1,$scope.frm, _export, notpickup);
        }
    }
]);
