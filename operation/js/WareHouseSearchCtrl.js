'use strict';

angular.module('app').controller('WareHouseSearchCtrl', ['$scope', '$filter', '$state', '$stateParams', '$localStorage', 'PhpJs', 'Warehouse', 'Config_Status', 'Order',
 	function($scope, $filter, $state, $stateParams, $localStorage, PhpJs, Warehouse, Config_Status, Order) {
        $scope.waiting                  = true;
        $scope.waiting_item             = true;
        $scope.maxSize                  = 5;

        $scope.user                     = [];
        $scope.total                    = 0;
        $scope.list_freeze              = {};
        $scope.list_group_freeze        = {};
        $scope.list_sc_color            = Config_Status.order_color;
        $scope.list_status_verify       = Config_Status.StatusVerify;
        $scope.list_status_verify.SUCCESS.text = "Đã thanh toán";

        $scope.item_stt             = 0;
        $scope.item_page            = 20;
        $scope.currentPage          = 1;
        $scope.totalItems           = 0;
        var create_start            = +Date.parse(new Date(date.getFullYear(), (date.getMonth() - 2), 1))/1000;

        $scope.frm                      = {};
        $scope.list_location        = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};

        if($stateParams.keyword != undefined && $stateParams.keyword != ''){
            $scope.calculate_item = function(item){
                var quantity = 0;
                angular.forEach(item.order_item, function(value) {
                    angular.forEach(item.order_item, function(value) {
                        quantity    += 1;
                    });
                });

                return quantity;
            }

            /*if($stateParams.keyword.match(/^SC\d+$/g)){
                $state.go('shipchung._search', {search : $stateParams.keyword});
            }else{*/
                $scope.get_freeze   = function(organization){
                    if(organization != undefined && organization > 0){
                        if($localStorage['list_freeze_'+ organization] != undefined){
                            $scope.list_freeze          = $localStorage['list_freeze_' + organization];
                            $scope.list_group_freeze    = $localStorage['list_group_freeze_' + organization];
                        }else{
                            Warehouse.list_freeze($scope.item.organization).then(function (result) {
                                if(result.data.total_items){
                                    $scope.list_freeze              = result.data;
                                    $localStorage['list_freeze_' + organization]    = result.data;

                                    // group sku freeze
                                    var list_group_freeze   = {};

                                    angular.forEach($scope.list_freeze._embedded.data, function(value) {
                                        if(list_group_freeze[value.Sku] == undefined){
                                            list_group_freeze[value.Sku]    = {
                                                'sku'           : value.Sku,
                                                'name'          : value.ProductName,
                                                'warehouse'     : value.Warehouse,
                                                'total_item'    : 0,
                                                'hours'         : 0,
                                                'total_hours'   : 0,
                                                'price'         : 0
                                            };
                                        }

                                        list_group_freeze[value.Sku]['total_item']      += 1;
                                        list_group_freeze[value.Sku]['hours']           += value.Hours;
                                        list_group_freeze[value.Sku]['price']           += value.Price;
                                        list_group_freeze[value.Sku]['total_hours']     += value.HoursStock;
                                    });

                                    $scope.list_group_freeze    = list_group_freeze;
                                    $localStorage['list_group_freeze_' + organization]  = list_group_freeze;
                                }
                            })
                        }
                    }
                }

                $scope.calculate_balance =  function(item){
                    return 1*item.balance + 1*item.quota + 1*item.provisional - 1*item.warehouse_freeze - 1*item.freeze;
                }

                $scope.getListOrder = function(page){
                    $scope.currentPage      = page;
                    $scope.list_location    = {'list_city': {},'list_district': {},'list_ward': {},'list_to_address': {},'list_from_address': {}};
                    $scope.waiting_item     = true;

                    Order.ListOrder($scope.currentPage,$scope.frm, '', '').then(function (result) {
                        $scope.item.list_order  = {};
                        if(!result.data.error){
                            $scope.item.list_order  = result.data.data;
                            $scope.totalItems       = result.data.total;
                            $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);

                            $scope.list_location.list_city              = result.data.list_city;
                            $scope.list_location.list_district          = result.data.list_district;
                            $scope.list_location.list_ward              = result.data.list_ward;

                            $scope.list_location.list_to_address        = result.data.list_to_address;
                            $scope.list_location.list_from_address      = result.data.list_from_address;
                            $scope.list_post_office                     = result.data.list_postoffice;
                        }
                        $scope.waiting_item  = false;
                    });
                    return;
                }

                $scope.setCountGroup    = function(){
                    $scope.total_group  = [];
                    Order.CountGroup($scope.frm, $scope.list_order_status, 'warehouse').then(function (result) {
                        if(!result.data.error){
                            $scope.total_group      = result.data.data;
                            $scope.total_group.ALL  = result.data.total;
                        }
                    });
                }

                $scope.ChangeTab    = function(warehouse){
                    $scope.frm.warehouse  = warehouse;
                    $scope.getListOrder(1);
                }

            Warehouse.search({keyword : $stateParams.keyword}).then(function (result) {
                    if(!result.data.error){
                        $scope.item             = result.data.data;
                        if($scope.item != undefined){
                            if($scope.item.user_id != undefined){
                                $scope.total    = 1;
                            }

                            if($scope.item.email != undefined){
                                $scope.item.avatar = PhpJs.md5($scope.item.email);
                            }

                            if($scope.item.list_order != undefined){

                                $scope.frm = {from_user : $scope.item.user_id, domain : 'boxme.vn', create_start : create_start};

                                if($scope.item.tracking_code != undefined){
                                    $scope.frm.tracking_code    = $scope.item.tracking_code;
                                }

                                $scope.item_stt         = 0;
                                $scope.totalItems       = 0;
                                $scope.getListOrder(1);
                                $scope.setCountGroup();
                            }
                        }
                    }
                    $scope.waiting  = false;
                }).finally(function() {
                    $scope.get_freeze($scope.item.organization);
                });


            $scope.change   = function(id, new_value, field){
                var dataupdate = {};
                dataupdate[field] = new_value;

                if(new_value != undefined &&  id > 0 ){
                    return  Warehouse.edit(id, dataupdate).then(function (result) {
                        if(result.data.error){
                            return 'Cập nhật lỗi';
                        }
                    }).finally(function() {

                    });
                }
            };

        }

    }
]);