'use strict';

//List Order
angular.module('app').controller('OrderCtrl', 
		     ['$rootScope','$scope','$filter','toaster','$modal','$http','$state','$window','$stateParams','Order','Inventory','Config_Status','PhpJs','$anchorScroll','$location','loginService',
    function ($rootScope,  $scope,   $filter,  toaster,  $modal,   $http,  $state, $window,   $stateParams,  Order, Inventory,   Config_Status,  PhpJs,  $anchorScroll,  $location,  loginService) {
        // config
		 var tran = $filter('translate');   	 
        $scope.list_status = {};
        $scope.status_group = [];
        $scope.tab_status = {};
        $scope.list_group = [];

        $scope.orders = {};
        $scope.check_box = [];
        $scope.list_inventory = [];
        $scope.service = {
            1: 'Chuyển phát tiết kiệm',
            2: 'Chuyển phát nhanh'
        };
        $scope.service_en = {
                1: 'Economy delivery service',
                2: 'Express delivery service'
        };
        $scope.list_service = [
            {'id': 1, 'name': 'Chuyển phát tiết kiệm'},
            {'id': 2, 'name': 'Chuyển phát nhanh'}
        ];
		$scope.list_service_en = [
            {'id': 1, 'name': 'Economy delivery service'},
            {'id': 2, 'name': 'Express delivery service'}
        ];

        $scope.checking = {
            1: 'Cho khách xem hàng khi nhận',
            2: 'Không cho khách xem hàng khi nhận'
        };

        $scope.list_checking = [
            {'id': 1, 'name': 'Cho khách xem hàng khi nhận'},
            {'id': 2, 'name': 'Không cho khách xem hàng khi nhận'}
        ];

        $scope.list_courier = [];
        $scope.list_color = Config_Status.order_color;
        $scope.CourierPrefix = Config_Status.CourierPrefix;


        $scope.total = 0;
        $scope.Page = 1;
        $scope.item_page = 10;
        $scope.maxSize = 5;
        $scope.status = 'ALL';
        $scope.keyword = '';
        $scope.report_pickup = {};

        $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        $scope.waiting = true;
        $scope.wating_status = false;

        $scope.show_list = true;
        $scope.show_detail = true;


       // loginService.checkOrderProcess();

        
        

        $scope.gotoDetail = function (order) {
            if ($rootScope.windowWith < 768) {
                $scope.show_detail = true;
                $scope.show_list = false;
            }
            $state.go('order.list.detail', {code: order.tracking_code});
        }

        $scope.backToList = function () {
            if ($rootScope.windowWith < 768) {
                $scope.show_detail = false;
                $scope.show_list = true;
            }
        }
        $rootScope.$watch('windowWith', function (newVal, oldVal) {
            if (newVal > 767) {
                $scope.show_detail = true;
                $scope.show_list = true;
            }
        })

        $scope.processTimer = function (time) {
            var currentDate = new Date();
            var expireDate = moment(time.time_update * 1000).add(24, 'hours');
            var isExpired = expireDate.isBefore(currentDate);
            return {
                isExpired: isExpired,
                diff: expireDate.diff(currentDate, 'hours')
            }
        }

        // get list lading
        var getOrder_Status = function(){
        	Order.ListStatus().then(function (result) {
                var tab_status = []
                if (result.data.list_group) {
                    angular.forEach(result.data.list_group, function (value) {
                        $scope.list_group[+value.id] = value.name;
                        tab_status.push({id: +value.id, name: value.name});
                        if (value.group_order_status) {
                            angular.forEach(value.group_order_status, function (v) {
                                $scope.list_status[+v.order_status_code] = v.group_status;
                            });
                        }
                    });
                    $scope.tab_status = tab_status;
                }
            });
        }
        getOrder_Status();
        $scope.$watch('keyLang', function (Value, OldValue) {
        	getOrder_Status();
        });
        // inventory
        Inventory.load().then(function (result) {
            if (result.data.data) {

                angular.forEach(result.data.data, function (value, key) {
                    $scope.list_inventory[value.id] = value;
                });
            }
        });

        /* ListService
         Order.ListService().then(function (result) {
         if(result.data.data){
         angular.forEach(result.data.data, function(value, key) {
         $scope.list_service[value.id] = value;
         });
         }
         });*/

        // ListCourier
        Order.ListCourier().then(function (result) {
            if (result.data.data) {
                angular.forEach(result.data.data, function (value, key) {
                    $scope.list_courier[value.id] = value;
                });
            }
        });

        //Report Pickup
        Order.ReportPickup({'time_start' : 7}).then(function (result) {
            if(!result.data.error){
                $scope.report_pickup    = result.data.data;
            }
        });

        $scope.refresh_data = function () {
            $scope.orders = {};
            $scope.status_group = {};
            $scope.total = 0;
            //$state.go('order.list.detail',{code:''});
        }

        $scope.change_tab = function (status, keyword, page) {
            $scope.Page = page;
            $scope.waiting = true;
            $scope.refresh_data();
            Order.ListData(page, status, $scope.item_page, keyword, $stateParams.time_start).then(function (result) {
                $scope.status = status;
                if (result.data.data) {
                    $scope.orders = result.data.data;
                    angular.forEach($scope.orders, function (value, key) {
                    	if($rootScope.keyLang == "vi"){
                    		$scope.orders[key]['time_create_before'] = PhpJs.ScenarioTime(Date.parse(new Date()) / 1000 - value.time_create);
                    	}else{
                    		$scope.orders[key]['time_create_before'] = PhpJs.ScenarioTime_en(Date.parse(new Date()) / 1000 - value.time_create);
                    	}
                        
                    });

                    $scope.totalItems = result.data.total;
                    $scope.status_group = result.data.total_group;
                    $scope.total = result.data.total_all;
                    $scope.toggleSelectionAll(1);

                    if ($state.current.name == 'order.list.detail' || $state.current.name == 'order.list') {
                        if ($rootScope.windowWith > 767) {
                            if ($scope.orders[0]) {
                                $state.go('order.list.detail', {code: $scope.orders[0]['tracking_code']});
                            } else {
                                $state.go('order.list.detail', {code: ''});
                            }
                        } else {
                            $scope.show_detail = false;
                            $scope.show_list = true;

                        }

                    }

                }

                $scope.waiting = false;
            });
            return;
        }

        $scope.change_tab('ALL', '', 1);

        $scope.check_list = function (code) {
            var data = angular.copy($scope.check_box);
            var idx = +data.indexOf(code);
            if (idx > -1) {
                return true;
            }
            else {
                return false;
            }
        }

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

        $scope.toggleSelectionAll = function (check) {
            var check_box = $scope.check_box;
            if (check == 0) {
                $scope.check_box = [];
            } else {
                $scope.check_box = [];
                angular.forEach($scope.orders, function (value, key) {
                    $scope.check_box.push(value.tracking_code);
                });
            }
        }

        $scope.action = function (status) {
            if ($scope.check_box.length > 0) {
                var dataupdate = {};
                // Update status

                dataupdate['tracking_code'] = $scope.check_box[0];
                dataupdate['status'] = status;
                
                Order.Edit(dataupdate).then(function (result) {
                    if (result.data.error) {
                        $scope.wating_status = false;
                        if (status == 21) {

                            if(result.data.message == 'NOT_ENOUGH_MONEY'){
                                var modalInstance = $modal.open({
                                    templateUrl: 'ModalError.html',
                                    controller: 'ModalErrorCtrl',
                                    resolve: {
                                        items: function () {
                                            return result.data;
                                        }
                                    }
                                });

                                modalInstance.result.then(function () {
                                    $scope.cash_in('');
                                });
                            }
                        }
                    } else {
                        
                        if (result.data.message == "OUT_OF_STOCK"){
                            toaster.pop('warning',tran('toaster_ss_nitifi'), result.data.error_message)
                        }
                        $scope.check_box.splice(0, 1);
                        $scope.action(status);
                    }
                });
            } else {
                $scope.wating_status = false;
                $scope.currentPage = 1;
                $scope.change_tab(12, $scope.keyword, 1);
            }
            return;
        };

        $scope.action_multi = function (status) {
            if ($scope.check_box.length > 0 && [12,14,15].indexOf($scope.status) != -1 && !$scope.wating_status) {
                var msg = '';
                if (status == [21]) {
                    msg = tran('ORDERC_BanCoMuonDuyetNhungDonNay');//'Bạn chắc chắn muốn duyệt những đơn hàng này ?';
					//msg 	= 'Bạn chắc chắn muốn duyệt những đơn hàng này ?';
                } else {
                    msg = tran('ORDERC_BanCoMuonHuyNhungDonNay'); //'Bạn chắc chắn muốn hủy những đơn hàng này ?';
                }
				$scope.list_status = ($rootScope.keyLang == 'en') ? $scope.list_status_en  : $scope.list_status_vi;
				$scope.$watch('keyLang', function (Value, OldValue) {
					$scope.list_status = ($rootScope.keyLang == 'en') ? $scope.list_status_en  : $scope.list_status_vi;
				});
                bootbox.confirm(msg, function (result) {
                    if (result) {
                        $scope.wating_status = true;
                        if($rootScope.fulfillment && $rootScope.fulfillment == 1 && status == 22){
                        	status = 28;
                        }
                        $scope.action(status);
                        return;
                    }
                });
            }
            return;
        }
    }]);
angular.module('app').controller('OrderDetail', 
			['$scope','$rootScope','$http','$state','$window','$stateParams','$modal','bootbox','Print','Order','$location','toaster','$filter',
    function ($scope, $rootScope,   $http,  $state,  $window,  $stateParams,  $modal,  bootbox,  Print,  Order, $location,   toaster,  $filter) {
        var tran = $filter('translate');
		$scope.code = $stateParams.code;
        $scope.data = [];
        $scope.City = [];
        $scope.Province = [];
        $scope.Country = [];
        $scope.Ward = [];
        $scope.waiting = true;
        $scope.can_request_delivery = true;
        $scope.post_office = {};

        $scope.thongbao_noithanh = false;
        $scope.courier_2 = [];

        var _count_req_delivery = 0;
        // load order
        if ($scope.code.length > 0) {
            Print.Multi($scope.code).then(function (result) {

                /*if($(window).width() < 768){
                 $location.hash('detail_order');
                 }*/

                $scope.data = result.data.data[0];
                $scope.courier_2 = result.data.courier_tracking;
                if($scope.data.order_tracking){
                	var courier_data = $scope.courier_2[''+$scope.data.order_tracking.courier_tracking_id+''];
                	$scope.data.order_tracking.url = courier_data.tracking_url;
                	$scope.data.order_tracking.name = courier_data.name;
                }
                
                
                var currentDate = new Date();
                
                if($scope.data && $scope.data.status == 20 && $scope.data.from_city_id == $scope.data.to_order_address.city_id && $scope.data.service_id == 2 && currentDate.getHours() > 10){
                    angular.forEach($rootScope.list_district_by_location, function (value){
                        
                        if(value == $scope.data.to_order_address.province_id){
                            $scope.thongbao_noithanh = true;
                        }
                    })
                }

                _count_req_delivery = 0;
                angular.forEach($scope.data.order_status, function (value) {
                    if (value.status == 67) {
                        _count_req_delivery++;
                    }
                });

                $scope.City = result.data.city;
                $scope.District = result.data.district;
                $scope.Ward = result.data.ward;
                $scope.post_office = result.data.postoffice;
                $scope.waiting = false;
                if(result.data.country){
                	$scope.Country = result.data.country;
                }
            });
        } else {
            $scope.waiting = false;
        }


        var sendProcessAction = function (order_id, action, note) {
            Order.CreateProcess({
                'order_id': order_id,
                'action': action,
                'note': note
            }, function (err, resp) {

            })
        }


        $scope.confirm_return = function () {ORDERC_BanMunChacChanXNChuyenHoan
            //bootbox.confirm("Bạn chắc chắn muốn chuyển hoàn đơn hàng này ?", function (result) {
			bootbox.confirm(tran('ORDERC_BanMunChacChanXNChuyenHoan'), function (result) {
                if (result) {
                    $scope.change(60, 61, 'status');
                }
            });
            return;
        }


        $scope.confirm_delivery = function () {
            if (_count_req_delivery >= 2) {
                bootbox.alert(tran('ORERC_DonHangCuaBanDaGuiYcGiaoLai2Lan'), function () {
                });
                return;
            }
            ;
            bootbox.prompt({
                message: tran('ORERC_NhapGhiChuChoyeuCauNay'),
                placeholder: tran('ORERC_ThongTinDiaChiSDT'),
                title: tran('ORERC_BanChacChanMuonYCGiaoLai'),
                inputType: "textarea",
                callback: function (result) {

                    if (result) {
                        $scope.change($scope.data.status, 67, 'status', result, function (err, resp) {
                            if (!err) {
                                sendProcessAction($scope.data.id, 1, result);
                            }
                        });
                    }
                }
            });
            return;
        }

        $scope.confirm_pickup = function () {
            bootbox.prompt({
                message: tran('ORERC_NhapGhiChuChoyeuCauNay'),
                placeholder: tran('ORERC_ThongTinDiaChiSDT'),
                title: tran('ORDERC_BanCoMuonHuyNhungDonNay'),//"Bạn chắc chắn muốn yêu cầu lấy lại đơn hàng này ?",
                inputType: "textarea",
                callback: function (result) {
                    if (result) {
                        $scope.change($scope.data.status, 38, 'status', result, function (err, resp) {
                            if (!err) {
                                sendProcessAction($scope.data.id, 4, result);
                            }
                        });
                    }
                }
            });
        }

        $scope.confirm_cancel = function () {
            //bootbox.confirm("Bạn chắc chắn muốn hủy đơn hàng này ?", function (result) {
        	bootbox.confirm(tran('ORDERC_BanCoMuonHuyNhungDonNay') , function (result) {
        	if (result) {
                    $scope.change(20, 22, 'status');
                		var metadata = {
                     		   user	: 		$rootScope.userInfo.email 	? $rootScope.userInfo.email 	: "",
                     		   order_number : 	$scope.data.tracking_code ? $scope.data.tracking_code : "",   
                     		   type			:	1,
         	                   links		:   "order/list/",
             			};
                		Intercom('trackEvent', 'Cancel order', metadata);
                	// Intercom
                }
            });
            return;
        }

        $scope.confirm_report_cancel = function (status) {
            bootbox.confirm(tran('ORERC_BanChacChanMuonBaoHuy')  , function (result) {
                if (result) {
                    $scope.change(status, 28, 'status');
                 // Intercom
            		var metadata = {
                 		   create_by	: 	$rootScope.userInfo.email 	? $rootScope.userInfo.email 	: "",
                 		   order_number : 	$scope.data.tracking_code ? $scope.data.tracking_code : "",   
     	                   type		:	2,
     	                   links		:   "order/list/",
         			};
            		Intercom('trackEvent', 'Cancel order', metadata);
            	// Intercom
                    
                }
            });
            return;
        }

        $scope.re_approve_order = function (order){
            
        }


        $scope.copyProcessing = false;
        $scope.copyOrder = function (order) {
            console.log('hello ', order);
            $scope.copyProcessing = true;
            
            var newOrder = {
                "Domain": order.domain,
                "From": {
                    "City"      : ($scope.list_inventory[order.from_address_id]) ? $scope.list_inventory[order.from_address_id].city_id : order.from_city_id,
                    "Province"  : ($scope.list_inventory[order.from_address_id]) ? $scope.list_inventory[order.from_address_id].province_id : order.from_district_id,
                    "Ward"      : ($scope.list_inventory[order.from_address_id]) ? ($scope.list_inventory[order.from_address_id].ward_id) : order.from_ward_id,
                    "Address"   : ($scope.list_inventory[order.from_address_id]) ? $scope.list_inventory[order.from_address_id].address : order.from_address,
                    "Phone"     : ($scope.list_inventory[order.from_address_id]) ? $scope.list_inventory[order.from_address_id].phone : $rootScope.userInfo.phone,
                    "Name"      : ($scope.list_inventory[order.from_address_id]) ? $scope.list_inventory[order.from_address_id].user_name : $rootScope.userInfo.fullname,
                    "Stock"     : $scope.list_inventory[order.from_address_id] ? $scope.list_inventory[order.from_address_id].id : "" 
                },
                "To": {
                    "City": order.to_order_address.city_id,
                    "Province": order.to_order_address.province_id,
                    "Name": order.to_name,
                    "Phone": order.to_phone,
                    "Address": order.to_order_address.address
                },
                "Order": {
                    "Weight": order.total_weight,
                    "Amount": order.total_amount,
                    "Quantity": order.total_quantity,
                    "ProductName": order.product_name,
                    "Collect": order.order_detail.money_collect
                },
                "Config": {
                    "Service": order.service_id,
                    "Protected": (order.order_detail.sc_pbh > 0) ? 1 : 2,
                    "CoD": (order.order_detail.sc_cod > 0) ? 1 : 2,
                    "Payment": (order.order_detail.seller_discount > 0) ? 1 : 2,
                    "Fragile": order.fragile,
                    "Checking": order.checking
                },
                //"Discount"      :order.order_detail.seller_discount || null,
                "Courier": order.courier_id
            }

            Order.Create(newOrder).then(function (result) {
                $scope.copyProcessing = false;
                if (result.data.error == 'success') {

                    $scope.Order = {
                        'TrackingCode': result.data.data.TrackingCode,
                        'CourierId': result.data.data.CourierId,
                        'MoneyCollect': result.data.data.MoneyCollect,
                        'status': 20
                    };

                    $scope.change_tab('ALL', '', 1);

                    var modalInstance = $modal.open({
                        templateUrl: 'ModalCopy.html',
                        resolve: {
                            order: function () {
                                return $scope.Order;
                            }
                        },
                        controller: function (order, $scope, Order, $modal, $modalInstance) {
                            $scope.run = function () {
                                $modalInstance.close();
                            };
                            $scope.accept_order = function (tracking_code) {
                                if (tracking_code != undefined && tracking_code != '') {
                                    var dataupdate = {};
                                    $scope.waiting_status = true;
                                    dataupdate['tracking_code'] = tracking_code;
                                    dataupdate['status'] = 21;

                                    Order.Edit(dataupdate).then(function (result) {
                                        if (result.data.error) {
                                            $scope.waiting_status = false;

                                            if (result.data.message == 'NOT_ENOUGH_MONEY') {
                                                var modalInstance = $modal.open({
                                                    templateUrl: 'ModalError.html',
                                                    controller: 'ModalErrorCtrl',
                                                    resolve: {
                                                        items: function () {
                                                            return result.data;
                                                        }
                                                    }
                                                });

                                                modalInstance.result.then(function () {
                                                    $scope.cash_in('');
                                                });
                                            }

                                        } else {
                                            if (result.data.message == "OUT_OF_STOCK"){
                                                toaster.pop('warning', tran('toaster_ss_nitifi'), result.data.error_message)
                                            }
                                            $scope.Order.status = 21;
                                        }
                                    });
                                }

                                return;
                            };

                            $scope.Order = order;
                        }
                    });
                }
                $scope.frm_submit = false;
                return;
            });
        }

        /**
         *   Edit order
         */
        $scope.buyProtect = function (old_value, new_value, field) {
            if (field == 'protect') {
            	//tran('ORDERC_BanCoMuonHuyNhungDonNay')  
                bootbox.confirm(tran('ORERC_BanChacMuaBaoHiem'), function (result) {
                    if (result) {
                        $scope.change(old_value, new_value, field);
                    }
                });
            }
        }


        $scope.acceptStatus = function (status, sc_code, city, note, courier, callback) {
            var data = {};
            if (status && sc_code && courier && city && note) {
                data['status'] = status;
                data['sc_code'] = sc_code;
                data['courier'] = courier;
                data['city'] = city;
                data['note'] = note;

                Order.AcceptStatus(data, function (err, resp) {
                    if (!err) {
                        $scope.data.status = new_value;
                        (callback && typeof callback == 'function') ? callback(null, true) : null;
                    } else {
                        (callback && typeof callback == 'function') ? callback(true, null) : null;
                    }
                })
            }

        }


        $scope.change = function (old_value, new_value, field, note, coupon_code, callback) {
            var dataupdate = {};
            if (new_value != undefined && new_value !== '' && old_value != new_value && $scope.data.id > 0) {

                // Update status
                $scope.waiting_status = true;
                if (field == 'status') {

                    if (new_value == 61) {
                        $scope.acceptStatus(new_value, $scope.data.tracking_code, 'SC', tran('ORERC_KhachHangBaoChuyenHoan'), $scope.CourierPrefix[$scope.data.courier_id], function (err) {
                            if (!error)
                                $scope.data.status = new_value;
                        });
                        return;
                    } else if (new_value == 67) {
                        if (!note) {
                            return;
                        }
                        $scope.acceptStatus(new_value, $scope.data.tracking_code, 'SC', note, $scope.CourierPrefix[$scope.data.courier_id], function (err) {
                            if (!error)
                                $scope.data.status = new_value;
                        });
                        return;
                    }

                }
                dataupdate['id'] = $scope.data.id;
                dataupdate[field] = new_value;
                dataupdate['note'] = note || "";

                if (coupon_code != undefined && coupon_code != '') {
                    dataupdate['coupon_code'] = coupon_code;
                }

                return Order.Edit(dataupdate).then(function (result) {
                    $scope.waiting_status = false;
                    if (result.data.error) {
                        if (field == 'status' && new_value == 21 && result.data.message == 'NOT_ENOUGH_MONEY') {
                            var modalInstance = $modal.open({
                                templateUrl: 'ModalError.html',
                                controller: 'ModalErrorCtrl',
                                resolve: {
                                    items: function () {
                                        return result.data;
                                    }
                                }
                            });

                            modalInstance.result.then(function () {
                                $scope.cash_in('');
                            });
                        }

                        (callback && typeof callback == 'function') ? callback(true, null) : null;
                        return 'Cập nhật lỗi';
                    } else {
                        if (result.data.fee) {
                            // Sc Pvc
                            if (result.data.fee.pvc != undefined && result.data.fee.pvc != '') {
                                $scope.data.order_detail.sc_pvc = result.data.fee.pvc;
                            }

                            if (result.data.fee.discount.pvc != undefined && result.data.fee.discount.pvc != '') {
                                $scope.data.order_detail.sc_discount_pvc = result.data.fee.discount.pvc;
                            }

                            // Sc PBh
                            if (result.data.fee.vas.protected != undefined && result.data.fee.vas.protected != '') {
                                $scope.data.order_detail.sc_pbh = result.data.fee.vas.protected;
                            }

                            // Sc CoD
                            if (result.data.fee.vas.cod != undefined && result.data.fee.vas.cod != '') {
                                $scope.data.order_detail.sc_cod = result.data.fee.vas.cod;
                            }

                            // time_estimate_delivery
                            if (result.data.estimate_delivery != undefined && result.data.estimate_delivery != '') {
                                $scope.data.estimate_delivery = +result.data.estimate_delivery;
                            }

                        }

                        // Status
                        
                        if (field == 'status') {

                            if (result.data.message == "OUT_OF_STOCK"){
                                toaster.pop('warning', tran('toaster_ss_nitifi'), result.data.error_message)
                            }
                            $scope.data.status = new_value;
                            (callback && typeof callback == 'function') ? callback(null, true) : null;
                        }
                        // Intercom   
                        try {
                    		var metadata = {
                    			action			: 	 "Account Accepted Order",
                    			time			:	new Date,
                    			user			: 	$rootScope.userInfo 	? $rootScope.userInfo.email 	: "",
                    			tracking_code	: ($scope.data && $scope.data.tracking_code) ?$scope.data.tracking_code :""
                 			}; 
                			Intercom('trackEvent', 'Accept Order', metadata);
                        }catch(err) {
            			    console.log(err)
            			}
                        // Intercom
                    }
                    return;
                });
            }
            return;
        };

        $scope.open_edit = function () {
            if ($scope.data.status == 20) {
                var data = {};
                data.to_address_id = $scope.data.to_order_address.id;
                data.to_city_id = $scope.data.to_order_address.city_id;
                data.to_province_id = $scope.data.to_order_address.province_id;
                data.to_ward_id = $scope.data.to_order_address.ward_id;
                data.to_address = $scope.data.to_order_address.address;
                data.id = $scope.data.id;

                var modalInstance = $modal.open({
                    templateUrl: 'ModalEdit.html',
                    controller: 'ModalEditCtrl',
                    resolve: {
                        items: function () {
                            return data;
                        }
                    }
                });

                modalInstance.result.then(function (frm_submit) {
                    if (frm_submit) {
                        $scope.waiting = true;
                        $scope.data = [];

                        Print.Multi($scope.code).then(function (result) {
                            $scope.data = result.data.data[0];
                            $scope.City = result.data.city;
                            $scope.District = result.data.district;
                            $scope.Ward = result.data.ward;
                            $scope.waiting = false;
                            if(result.data.country){
                            	$scope.Country = result.data.country;
                            }
                        });
                    }
                });
            }
            return;
        }
    }
]);
angular.module('app').controller('ModalErrorCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox', 'items',
    function ($scope, $modalInstance, $http, toaster, bootbox, items) {
        $scope.data = items;

        $scope.cash_in = function (size) {
            $modalInstance.close();
        };
    }
]);

angular.module('app').controller('ModalEditCtrl', 
		    ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox', 'items', 'Location', 'Order','$filter',
    function ($scope,   $modalInstance, $http,     toaster,   bootbox,    items,   Location,   Order,  $filter) {
        $scope.data = items;
        $scope.frm_submit = false;
        var tran = $filter('translate');
        Location.province('all').then(function (result) {
            if (result) {
                if (!result.data.error) {
                    $scope.list_city = result.data.data;
                } else {
                    ///toaster.pop('warning', 'Thông báo', 'Tải danh sách Tỉnh/Thành Phố lỗi !');
                    toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_TaiDSThanhPhOloi'));
                }
            } else {
                //toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!!');
                toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_ketnoidulieuthatbai'));
            }
        });

        $scope.save = function (data) {
            $scope.frm_submit = true;
            Order.Edit(data).then(function (result) {
                if (result.data.error) {
                    $scope.frm_submit = false;
                } else {
                    $modalInstance.close($scope.frm_submit);
                }
            });
        }

        $scope.close = function () {
            $modalInstance.close($scope.frm_submit);
        }


    }
]);