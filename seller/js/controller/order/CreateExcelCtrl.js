'use strict';

//List Order
angular.module('app').controller('CreateExcelCtrl',
    ['$scope', '$rootScope', '$filter', '$http', '$state', '$window', '$stateParams', 'toaster', 'Order', 'Location', 'OrderStatus', 'Inventory', 'Config_Status', '$timeout', 'Analytics', 'WarehouseRepository',
        function ($scope, $rootScope, $filter, $http, $state, $window, $stateParams, toaster, Order, Location, OrderStatus, Inventory, Config_Status, $timeout, Analytics, WarehouseRepository) {
            // Config
            var tran = $filter('translate');
            if (!$stateParams.id.length) {
                $state.go('app.dashboard');
            }

            Analytics.trackPage('/create_order/file/step3');

            $scope.list_data = {};
            $scope.list_city = {};
            $scope.list_inventory = {};  // kho hàng
            $scope.Inventory = {};
            $scope.list_district = {};
            $scope.check_box = [];
            $scope.list_tracking_code = [];

            //$scope.list_status = Config_Status.ExcelOrder;
//            $scope.service = $rootScope.keyLang=="en" ? OrderStatus.service_en: OrderStatus.service;
//            
//            $scope.list_service =  $rootScope.keyLang=="en" ? OrderStatus.list_service_en :OrderStatus.list_service;
//            $scope.pay_pvc = $rootScope.keyLang=="en" ? OrderStatus.pay_pvc_en :OrderStatus.pay_pvc;
//            $scope.list_pay_pvc = $rootScope.keyLang=="en" ? OrderStatus.list_pay_pvc_en :OrderStatus.list_pay_pvc;
//            $scope.$watch('keyLang', function (Value, OldValue) {
//            	$scope.service 		= (Value =="en") ? OrderStatus.service_en 	: OrderStatus.service;
//            	$scope.list_service =(Value =="en") ? OrderStatus.list_service_en 	: OrderStatus.list_service;
//            	$scope.pay_pvc =(Value =="en") ? OrderStatus.pay_pvc_en 	: OrderStatus.pay_pvc;
//            	$scope.list_pay_pvc =(Value =="en") ? OrderStatus.list_pay_pvc_en 	: OrderStatus.list_pay_pvc;
//            });
            $scope.$watch('keyLang', function (Value, OldValue) {
            	if(Value == 'vi'){
            		$scope.list_pay_pvc  = OrderStatus.list_pay_pvc;
            		$scope.service          = OrderStatus.service;
            		 $scope.pay_pvc			=OrderStatus.pay_pvc;
            		 $scope.list_service =OrderStatus.list_service;
            		 $scope.list_status = Config_Status.ExcelOrder;
            	}else if(Value == 'en'){
            		$scope.list_pay_pvc  = OrderStatus.pay_pvc_en;
            		$scope.service          = OrderStatus.service_en;
            		$scope.pay_pvc			=OrderStatus.pay_pvc_en;
           		 	$scope.list_service =OrderStatus.list_service_en;
           		 	$scope.list_status = Config_Status.ExcelOrder_en;
            	}else{
            		$scope.list_pay_pvc  = OrderStatus.list_pay_pvc_thailand;
            		$scope.service          = OrderStatus.service_thailand;
            		$scope.pay_pvc			=OrderStatus.pay_pvc_thailand;
           		 	$scope.list_service =OrderStatus.list_service_thailand;
           		 $scope.list_status = Config_Status.ExcelOrder_thailand;
            	}
            });
            $scope.create_all = false;
            $scope.total = '';
            $scope.max = 0;
            $scope.dynamic = 0;

            $scope.checkingALL = 1;
            $scope.total_disabled = 0;

            $scope.fee = {
                Inventory: {}
            };
            $scope.all_data = [];

            $scope.counter = {
                'own_inventory': 0,
                'boxme_inventory': 0
            };
            $scope.filter_inventory = false;
            

            $scope.change_tab = function (inventory_boxme) {
                $scope.list_data        = {};
                $scope.filter_inventory = inventory_boxme;
                $scope.loadInventory($rootScope.pos, null, function (currentInventory){
                    if(inventory_boxme && currentInventory){
                        $scope.processProducts(currentInventory.warehouse_code)
                    }
                    
                }); 
                angular.forEach($scope.all_data, function (value, key){
                    
                    if(value.boxme_inventory == inventory_boxme){
                        value.id = key;
                        $scope.list_data[key] = value;
                    }
                })
            }

            function arr_diff (a1, a2) {
                var a = [], diff = [];
                for (var i = 0; i < a1.length; i++) {
                    a[a1[i]] = true;
                }
                for (var i = 0; i < a2.length; i++) {
                    if (a[a2[i]]) {
                        delete a[a2[i]];
                    } else {
                        a[a2[i]] = true;
                    }
                }
                for (var k in a) {
                    diff.push(k);
                }
                return diff;
            }


            $scope.item_names = {};
            var checkOrderAvaliable = function (products, item){
                
                if(item.order_items && item.order_items.length > 0){
                    item.unavaliable_sku = [];
                    item.avaliable_sku   = [];
                    
                    item.items_sku     = [];
                    var _temp          = [];
                    item.sku_not_found     = [];

                    for(var i = 0; i < products.length; i++){
                        var productItem = products[i];
                        $scope.item_names[productItem.SellerBSIN] = productItem.ProductName;
                        for(var j = 0; j < item.order_items.length; j++){
                            if(item.items_sku.indexOf(item.order_items[j].sku) == -1){
                                item.items_sku.push(item.order_items[j].sku);
                            }
                            if(productItem.SellerBSIN == item.order_items[j].sku ){
                                if(productItem.SellerBSIN >= item.order_items[j].quantity){
                                    item.avaliable_sku.push(productItem.SellerBSIN);
                                }else {
                                    item.unavaliable_sku.push(productItem.SellerBSIN);
                                }
                                _temp.push(productItem.SellerBSIN);
                            }
                        }
                    }
                    //console.log(item, item.items_sku, _temp, arr_diff(item.items_sku, _temp))
                    item.sku_not_found = arr_diff(item.items_sku, _temp);
                }
            }


            $scope.generation_item_name = function (item){
                var name_arr      = [];
                item.has_sku_not_found = false;
                angular.forEach(item.order_items, function (value){
                    if($scope.item_names.hasOwnProperty(value.sku)){
                        var thieuhang = ""
                        if(item.unavaliable_sku.indexOf(value.sku) != -1){
                            thieuhang = " <label class='label label-warning'>Thiếu hàng</label>"
                        }
                        name_arr.push("<p>" + value.sku + " - "+ $scope.item_names[value.sku] + " (x" +value.quantity+ ")"+thieuhang+"</p>");
                    }else {
                        item.has_sku_not_found = true;
                        name_arr.push("<p class='text-danger'>" + value.sku + " <label class='label label-danger'>SKU không tồn tại</label></p>");
                    }
                })
                return name_arr.join('');
            }

            $scope.processProducts = function (warehouse_code){
                WarehouseRepository.GetProducts(warehouse_code, true).then(function (resp){
                    $scope.item_names = {};
                    
                    angular.forEach($scope.list_data, function (value, key){
                        checkOrderAvaliable(resp.embedded().product || [] , value);
                        value.name_str = $scope.generation_item_name(value);
                        
                    })
                });
            }

            // Get Data
            // List order
            $scope.get_location = function (item, time) {
                setTimeout(function () {
                    Order.Synonyms(item._id.$id).then(function (result) {
                        if (!result.data.error) {
                            item.to_city = result.data.city_id;
                            item.to_district = result.data.district_id;

                            if (item.to_district > 0) {
                                $scope.check_box.push(item._id.$id);
                                $scope.total_disabled -= 1;
                            }
                        }
                    });
                }, (time - 1) * 2000);
            };

            Order.ListExcel($stateParams.id, 1, 'ALL').then(function (result) {
                $scope.counter = {
                    'own_inventory': 0,
                    'boxme_inventory': 0
                };
                //$scope.list_data = result.data.data;
                $scope.total = result.data.total;
                $scope.all_data = result.data.data;

                $scope.change_tab($scope.filter_inventory);

                angular.forEach($scope.all_data, function (value, key) {
                    
                    if (value.boxme_inventory) {
                        $scope.counter.boxme_inventory += 1;
                    } else {
                        $scope.counter.own_inventory += 1;
                    }

                    if (value.to_city == 0 || value.to_district == 0) {
                        $scope.total_disabled += 1;
                        console.log(value);
                        $scope.get_location(value, $scope.total_disabled);
                    }
                });

                $scope.toggleSelectionAll(1);
            });

            // Checkbox
            $scope.toggleSelection = function (id) {
                var data = angular.copy($scope.check_box);
                var idx = +data.indexOf(id);

                if (idx > -1) {
                    $scope.check_box.splice(idx, 1);
                }
                else {
                    $scope.check_box.push(id);
                }
            };

            $scope.check_list = function (id, action) {
                var data = angular.copy($scope.check_box);
                var idx = +data.indexOf(id);

                if (idx > -1) {
                    if (action == 'delete') {
                        delete $scope.check_box[idx];
                    }
                    return true;
                }
                else {
                    return false;
                }

            }

            $scope.toggleSelectionAll = function (check) {
                var check_box = $scope.check_box;
                if (check == 0) {
                    $scope.check_box = [];
                } else {
                    $scope.check_box = [];
                    angular.forEach($scope.list_data, function (value, key) {
                        if (value.active == 0 && (value.to_city > 0) && (value.to_district > 0)) {
                            $scope.check_box.push(key);
                        }
                    });
                }


            }

            $scope.$watchCollection('check_box', function (newdata, olddata) {
                $scope.max = newdata.length;
            });


            var busy = false;
            // Action
            $scope.loadInventory = function (params, q, callback) {
                if ($stateParams.bc) {
                    params = angular.extend(params, { bc: $stateParams.bc });
                }
                if (busy && !params.lat) {
                    return false;
                };

                busy = true;

                if($scope.filter_inventory){
                    params.source = 'boxme';
                }else {
                    params.source = 'shipchung';
                }
                
                Inventory.loadWithPostOffice(params).then(function (result) {
                    busy = false;
                    if (result) {
                        $scope.list_inventory = result.data.data;

                        if ($stateParams.bc && $stateParams.bc !== "" && $stateParams.bc !== null && $stateParams.bc !== undefined) {
                            result.data.data.forEach(function (value) {
                                if (value.id == $stateParams.bc) {
                                    $scope.fee.Inventory = value;
                                };
                            })

                        } else {
                            $scope.fee.Inventory = $scope.list_inventory[0];
                        }

                        if(callback && typeof callback == 'function'){
                            callback($scope.fee.Inventory);
                        }

                    }
                });
            }

            $scope.$watch('fee.Inventory', function (inventory){
                if($scope.filter_inventory && inventory){
                    $scope.processProducts(inventory.warehouse_code)
                }
            })

            $rootScope.pos = {};

            function success(pos) {
                var crd = pos.coords;
                $rootScope.pos = {
                    lat: crd.latitude,
                    lng: crd.longitude
                };

                $scope.loadInventory($rootScope.pos, null);
            };

            function error(err) {
                $scope.loadInventory($rootScope.pos, null);
            };

            navigator.geolocation.getCurrentPosition(success, error, {});

            // get list city
            Location.province('all').then(function (result) {
                $scope.list_city = result.data.data;
                angular.forEach($scope.list_city, function (value) {
                    $scope.list_district[value.id] = {};
                });
            });

            // Action

            $scope.loadDistrict = function (city_id) {
                if (city_id > 0 && !$scope.list_district[city_id][0]) {
                    Location.district(city_id, 'all').then(function (result) {
                        if (result) {
                            if (!result.data.error) {
                                $scope.list_district[city_id] = result.data.data;
                            }
                        }
                        return;
                    });
                }
                return;
            }

            $scope.change_city = function (id, item) {
                item.to_city = id;
                $scope.loadDistrict(id);
            }

            $scope.save = function (data, item, key) {
                data.checking = item.checking;

                Order.ChangeExcel(data, key).then(function (result) {
                    $scope.list_data[key].city_name = result.data.city_name;
                    $scope.list_data[key].district_name = result.data.district_name;
                });
            }

            $scope.checkdata = function (data) {
                if (data == '' || data == undefined) {
                	if($rootScope.keyLang=="en"){
                		return "Data null";
                	}else{
                		return "Dữ liệu trống";
                	}
                    
                }
            };

            $scope.check_district = function (data, city_id) {
                if (data == '' || data == undefined || data == 0) {
                    return "Bạn chưa chọn quận huyện !";
                }

                if (!checkDistrictInCity(data, city_id)) {
                    return "Bạn chưa chọn quận huyện !";
                }
                return;
            }

            var checkDistrictInCity = function (district_id, city_id) {
                if ($scope.list_district[city_id]) {
                    var check = false;
                    angular.forEach($scope.list_district[city_id], function (value) {
                        if (value.id == district_id) {
                            check = true;
                            return;
                        }
                    });
                    return check;
                } else {
                    return true;
                }

            }

            $scope.remove = function (key) {
                Order.RemoveExcel(key).then(function (result) {
                    if (!result.data.error) {
                        delete $scope.list_data[key];
                    }
                });
            }

            $scope.toggleChecking = function (status) {
                $scope.checkingALL = status;
                angular.forEach($scope.list_data, function (value, key) {
                    value.checking = parseInt(status);
                })
            }

            $scope.create = function (key, accept) {
                var stock_id = $scope.fee.Inventory.id;
                var is_postoffice = false;
                if ($scope.fee.Inventory.hasOwnProperty('post_office') && $scope.fee.Inventory.post_office == true) {
                    is_postoffice = true;
                }
                console.log($scope.fee.Inventory);

                if (stock_id > 0) {
                    Order.CreateExcel(key, stock_id, null, is_postoffice, accept).then(function (result) {
                        if (!result.data.error) {
                            $scope.list_data[key]['trackingcode'] = result.data.data;
                            $scope.list_data[key]['active'] = 1;
                            $scope.list_data[key]['status'] = 'SUCCESS';
                            $scope.list_tracking_code.push(result.data.data);
                        } else {
                            if (result.data.code == 1) {
                                $scope.list_data[key]['active'] = 2;
                                $scope.list_data[key]['status'] = 'FAIL';
                            }
                        }

                        if (result.data.total) {
                            $scope.total = result.data.total;
                        }

                        $scope.check_list(key, 'delete');
                    });
                } else {
                    //toaster.pop('danger', 'Thông báo', 'Hãy chọn kho hàng !');
                    toaster.pop('warning', tran('toaster_ss_nitifi'), tran('Toaster_BanChuaChonKhoHang'));

                }
                return;
            }

            // create multi
            $scope.action_create = function (data, accept) {
                if (data.length > 0) {
                    $scope.create_all = true;
                    $scope.create_multi(data, 0, accept);
                }
                return;
            }

            $scope.create_multi = function (data, num, accept) {
                $scope.dynamic = num;
                var is_postoffice = false;
                if ($scope.fee.Inventory.hasOwnProperty('post_office') && $scope.fee.Inventory.post_office == true) {
                    is_postoffice = true;
                }

                var stock_id = $scope.fee.Inventory.id;
                if (data[num] && data[num].length > 0) {
                    Order.CreateExcel(data[num], stock_id, $scope.checkingALL, is_postoffice, accept).then(function (result) {
                        if (!result.data.error) {
                            $scope.list_data[data[num]]['trackingcode'] = result.data.data;
                            $scope.list_data[data[num]]['active'] = 1;
                            $scope.list_data[data[num]]['status'] = 'SUCCESS';
                            $scope.list_tracking_code.push(result.data.data);
                        } else {
                            $scope.list_data[data[num]]['active'] = 2;
                            $scope.list_data[data[num]]['status'] = 'FAIL';
                        }

                        if (result.data.total || result.data.total == 0) {
                            $scope.total = result.data.total;
                        }

                        $scope.create_multi(data, +num + 1, accept);
                    });
                } else {
                    $scope.create_all = false;
                    $scope.check_box = [];
                    // toaster.pop('success', 'Thông báo', 'Kết thúc !'); 
                    toaster.pop('warning', tran('toaster_ss_nitifi'), tran('Toaster_KetThuc'));
                }
            }

        }]);