'use strict';

angular.module('app').controller('SearchCtrl', ['$scope', '$state', '$stateParams', 'Order', 'Base', 'Config_Status', 'Config_Accounting',
 	function($scope, $state, $stateParams, Order, Base, Config_Status, Config_Accounting) {
        return 1;
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;

        $scope.total_all            = 0;
        $scope.frm                  = {create_start : +Date.parse(new Date(date.getFullYear(), date.getMonth() - 3, 1))/1000};
        $scope.list_data            = {};
        $scope.user                 = [];
        $scope.object               = '';
        $scope.tab_status           = '';

        $scope.bank                 = Config_Accounting.vimo;

        $scope.list_to_address      = {};
        $scope.list_from_address    = {};
        $scope.list_district        = {};
        $scope.list_ward            = {};
        $scope.list_color           = Config_Status.order_color;
        $scope.seller               = 0;
        $scope.list_user            = {};

        $scope.waiting              = true;
        $scope.list_waiting         = true;

        if($stateParams.search != undefined && $stateParams.search != ''){
            if($stateParams.search.match(/^O\d+$/g)){
                $state.go('boxme.search',{keyword:$stateParams.search});
            }

            $scope.refresh = function(cmd){
                if(cmd != 'export'){
                    $scope.list_data            = {};
                    $scope.list_to_address      = {};
                    $scope.list_district        = {};
                    $scope.list_ward            = {};
                    $scope.list_from_address    = {};
                    $scope.list_waiting         = true;
                }
            }

            $scope.SearchUser   = function(search){
                Base.Search(search).then(function (result) {
                    if(!result.data.error){
                        $scope.user             = result.data.user;
                        $scope.object           = result.data.object;
                        if($scope.object == 'order'){

                        }else if($scope.object == 'seller'){
                            if($scope.user.id > 0){
                                $scope.getMerchant(1);
                            }
                        }else{
                            if($scope.user.id != undefined && $scope.user.id > 0){
                                $scope.frm.from_user  = $scope.user.id;
                                $scope.setPage(1);
                                $scope.setCountGroup();
                            }else{
                                $scope.list_waiting         = false;
                            }
                        }
                    }
                });
            }

            $scope.setPage = function(page){
                $scope.currentPage = page;
                $scope.refresh('');
                Order.ListOrder($scope.currentPage,$scope.frm, $scope.tab_status, '').then(function (result) {
                    if(!result.data.error){
                        $scope.list_data        = result.data.data;
                        $scope.totalItems       = result.data.total;
                        $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                        $scope.list_to_address  = result.data.list_to_address;
                        $scope.list_district    = result.data.list_district;
                        $scope.list_ward        = result.data.list_ward;
                        $scope.list_from_address    = result.data.list_from_address;

                        if($scope.object == 'order'){
                            $scope.SearchUser($scope.list_data[0]['from_user_id']);
                        }else if($scope.object == 'receiver'){
                            if($scope.list_data[0] != undefined){
                                $scope.user = {
                                    fullname        : $scope.list_data[0]['to_name'],
                                    email           : $scope.list_data[0]['to_email'],
                                    phone           : $scope.list_data[0]['to_phone']
                                };
                            }
                        }
                    }
                    $scope.list_waiting  = false;
                });
                return;
            }

            $scope.setCountGroup    = function(){
                $scope.total_all    = 0;
                $scope.total_group  = [];
                Order.CountGroup($scope.frm, $scope.tab_status, 'status').then(function (result) {
                    if(!result.data.error){
                        $scope.total_all    = result.data.total;
                        angular.forEach(result.data.data, function(value, key) {
                            if($scope.total_group[$scope.status_group[+key]] == undefined){
                                $scope.total_group[$scope.status_group[+key]]   = 0;
                            }
                            $scope.total_group[$scope.status_group[+key]]   += value;
                        });
                    }
                });
            }

            //Get merchant
            $scope.getMerchant = function(page){
                $scope.currentPage  = page;
                $scope.list_waiting  = true;
                $scope.list_user     = {};
                $scope.list_data     = {};
                Base.Merchant($scope.currentPage,{'seller' : $scope.user.id}, '').then(function (result) {
                    if(!result.data.error){
                        $scope.list_data        = result.data.data;
                        $scope.totalItems       = result.data.total;
                        $scope.list_user        = result.data.user;
                        $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    }
                    $scope.list_waiting  = false;
                });
                return;
            }

            $scope.ChangeTab    = function(tab){

                if(tab != 'ALL'){
                    if($scope.group_order_status[tab] != undefined){
                        $scope.tab_status   = $scope.group_order_status[tab].toString();
                    }else{
                        $scope.tab_status   = Config_Status.group_status[tab].toString();
                    }
                }
                $scope.setPage(1);
            }


            if($stateParams.search.match(/^SC\d+$/g)){
                $scope.frm.tracking_code    = $stateParams.search;
                $scope.setPage(1);
                $scope.object = 'order';
            }else if($stateParams.search.match(/^@/g)){
                $scope.frm.to_user    = $stateParams.search.substr(1);
                $scope.object = 'receiver';
                $scope.setPage(1);
                $scope.setCountGroup();

            }else{
                $scope.SearchUser($stateParams.search);
            }




            $scope.exportExcel = function(){
                $scope.refresh('export');
                return Accounting.report(1,$scope.frm,'export');
            }
        }else{
            $scope.waiting              = false;
        }
    }
]);
