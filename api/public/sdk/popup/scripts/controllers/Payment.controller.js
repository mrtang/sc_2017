"use strict";

angular.module('ShipChungApp')

	.controller('PaymentCtrl', ['$scope', '$rootScope',  '$http', '$timeout', '$translate', 'Payment', '$location','$routeParams', '$route', 'User', '$window', 
		function ($scope, $rootScope, $http, $timeout, $translate, Payment, $location, $routeParams, $route, User, $window){

            // Lấy token params trên url
            var checkoutId      = $location.search().id;
            $scope.hasToken     = true;


            if(checkoutId){
                
            }else{
                $scope.hasToken = false;
                return;
            }
            
            $scope.lang                = $translate.use(); 
            $scope.MerchantInfo        = {}; 
            $scope.myCity              = {};
            $scope.myDistrict          = {};
            
            $scope.courierInfo         = {};
            $scope.user                = {}; // use for form 
            $scope.totalFee            = 0;
            $scope.totalAmount         = 0;
            $scope.totalVas            = 0;
            $scope.discount            = 0;
            
            $scope.MerchantInventory   = 0;
            $scope.saveOrderProcessing = false;
            $scope.districtSelecting   = false;
            $scope.stateLoading        = true;
            $scope.hasAccountNL        = true;
            $scope._service            = 2;
            
            var getMerchanFunction     = null,
                getMerchantCallback    = null;



            if($route.current.$$route.originalPath === '/checkoutv1'){
                getMerchanFunction = Payment.getCheckoutV1;
            } else {
                getMerchanFunction = Payment.getCheckout;
            }

            getMerchantCallback =  function (err, merchant){

                if(!err){
                    $scope.MerchantInfo     = merchant;
                    $scope.listItems        = merchant.Item;
                    $scope.totalAmount      = merchant.Order.Amount;

                    $timeout(function(){ // Taọ scroller
                        var maxItem = 3;
                        var count   = parseInt($('.bar-right-top ul.media-list li.media').length);
                        if(count >= maxItem)
                        {
                            $('#scroller').css('height',($('.bar-right .bar-right-top ul.media-list li.media:first-child').height()+10)*maxItem);
                            $('#scroller').scroller({
                                settings_scrollbyhover:'off'
                            });
                        }
                        else{
                            $('#scroller').css('height',($('.bar-right .bar-right-top ul.media-list li.media:first-child').height() + 10) * count);
                        }
                        $timeout(function (){
                            init();
                        })
                        
                        
                    }, 200);
                }else {
                    $scope.hasToken = false;
                }
            }
            

            getMerchanFunction.apply(this, [checkoutId, getMerchantCallback]);


            function init(){
                $scope.user                     = User.getUser();
                $scope.myCity.CitySelected      = $scope.user.city      || 0;
                $scope.myDistrict.DistSelected  = $scope.user.province  || 0;
                $scope.user.ol_payment          = '0';
                $scope.user.ol_payment_banking  = '0';
                $scope.user.cod_payment         = '1';
                $scope.hasAccountNL             = ~~!!$scope.MerchantInfo.ReceiverEmail;
                $scope.hasBanking               = ~~!!$scope.MerchantInfo.hasBanking;
                $scope.IntegrateConfig          = ($scope.MerchantInfo.IntegrateConfig) ? $scope.MerchantInfo.IntegrateConfig : false;

                // Lấy danh sách các thành phố

                $scope.stateLoading = !($scope.myCity.CitySelected == 0 || $scope.myDistrict.DistSelected == 0);
                Payment.getCity(function (err, resp){
                    if(!err){

                        $scope.myCity.options = resp;
                        if($scope.myDistrict.DistSelected > 0){

                            // Láy danh sạch quận huyện theo thành phố 
                            Payment.getDistrictByCity($scope.myCity.CitySelected, function (err, dist){
                                $scope.myDistrict.options   = dist;
                                $scope.stateLoading         = false;

                                // Tính chi phí giao hàng
                                $scope.myCity.ChangeProvinceFrom(); 
                            });
                        }
                    }else {
                        swal("Lỗi", "Lỗi kết nối dữ liệu");
                    }
                });
                
            }
                
            $scope.changeLanguage = function (key) {
                $scope.lang = key;
                $translate.use(key);
            };


            $scope.togglePayment = function (){                    
                
                /*if($scope.user.ol_payment_banking == '1'){
                    if($scope.user.ol_payment == '1'){

                        $scope.user.ol_payment == '0';
                    }
                }

                if($scope.user.ol_payment == '1'){
                    if($scope.user.ol_payment_banking == '1'){
                        $scope.user.ol_payment_banking == '0';
                    }
                }*/

                
            }
            // Thực thi khi người dùng submit form 
            $scope.submitForm = function (isValid, user) {
                // check to make sure the form is completely valid
                var OrderInfo  = $scope.MerchantInfo.Order;

                if (isValid) {
                    if(!$scope.MerchantInventory) {
                        swal({
                            title: "Lỗi !",
                            text: "Tài khoản chưa cấu hình kho hàng"
                        })
                        return 
                    };
                    
                    
                    var orderData = {
                            "MerchantKey"       : $scope.MerchantInfo.MerchantKey,
                            "From" :{
                                "Stock"      : $scope.MerchantInventory, 
                            },
                            "To" : {
                               "City"       : $scope.myCity.CitySelected,
                               "Province"   : $scope.myDistrict.DistSelected,
                               "Name"       : user.fullname,
                               "Phone"      : user.phone,
                               "Address"    : user.address,
                               "Email"      : user.email,
                            },
                            "Order" : function(){
                                if(parseInt($scope.user.ol_payment) == 1 || parseInt($scope.user.ol_payment_banking) == 1){
                                        OrderInfo['Collect'] = 0;
                                }

                                var orderItem    = $scope.MerchantInfo.Item;
                                var _productName = [];
                                angular.forEach(orderItem, function (value, key){
                                    _productName.push(value.Name + ' (x' + value.Quantity + ')');
                                });
                                OrderInfo['ProductName'] = _productName.join();

                                return OrderInfo;
                            }(),
                            "Items" : $scope.MerchantInfo.Item,
                            "Config": {
                                "Service"   : $scope._service,
                                "CoD"       : function (){
                                    return (parseInt($scope.user.ol_payment) == 1) ? 2 : ((parseInt($scope.user.ol_payment_banking) == 1) ? 2 : 1)
                                }(),
                                "Protected" : 2,
                                "Payment"   : ($scope.MerchantInfo.hasOwnProperty('Config')) ? $scope.MerchantInfo.Config.Payment : (parseInt($scope.user.ol_payment) == 1) ? 1 : ((parseInt($scope.user.ol_payment_banking) == 1) ? 1 : 2),
                                "Checking"  : (typeof $scope.IntegrateConfig == 'object' && $scope.IntegrateConfig.checking > 0) ? 1 : 2,
                                "Fragile"   : 2
                            },
                            "Type"          : 'popup',
                            "Courier": $scope.courierInfo.courier_id,
                            "Domain" : document.referrer.split('/')[2] || $scope.MerchantInfo.Domain
                    };

                    
                    User.setUser({
                       "city"       : $scope.myCity.CitySelected,
                       "province"   : $scope.myDistrict.DistSelected,
                       "fullname"   : user.fullname,
                       "phone"      : user.phone,
                       "address"    : user.address,
                       "email"      : user.email
                    });

                    if(user.ol_payment == 1){
                        
                        $scope.saveOrderProcessing = true;
                        
                        Payment.createOrderWithNL(orderData, $scope.MerchantInfo._id.$id, function (err, resp){
                            $scope.saveOrderProcessing = false;
                            if(!err){
                                if(resp.error == 'success'){
                                    window.top.postMessage({name: 'redirectUrl', data: resp.LinkCheckout}, '*');
                                }else {
                                    $scope.saveOrderProcessing = false;
                                }
                            }else {
                                $scope.saveOrderProcessing = false;
                                // TODO when error
                            }
                        })

                    }else {
                        // Create order without NL
                        $scope.saveOrderProcessing = true;

                        Payment.createOrder(orderData, function (err, resp){
                            
                            if(!err){
                                $timeout(function (){
                                    $scope.saveOrderProcessing = false;

                                    $rootScope.OrderInfo = {
                                        "Order" : $scope.MerchantInfo.Order,
                                        "TotalFee": $scope.totalFee
                                    };
                                    if($scope.user.ol_payment_banking == 1){
                                        $location.search().payment_banking = 1;
                                    }
                                    $location.search().t = $scope.MerchantInfo.MerchantKey;
                                    $location.path('/result/' + resp.data.TrackingCode);
                                }, 1000);

                            }else {
                                $scope.saveOrderProcessing = false;

                                swal({
                                    title: "Lỗi !",
                                    text: "Có lỗi xảy ra trong qua trình xử lý , vui long thử lại sau !"
                                }, function (){
                                    
                                })
                            }
                        });
                    }
                }
            };

            // Bắt sự kiện khi chọn city
            $scope.myCity.ChangeCityFrom = function(){  

                if($scope.myCity.CitySelected > 0)
                {   
                    // Lấy danh sách quận huyện theo thành phố

                    $scope.totalAmount = 0;
                    $scope.totalFee = 0;
                    $scope.totalVas = 0;
                    $scope.discount = 0;

                    $scope.districtSelecting = true;

                    Payment.getDistrictByCity($scope.myCity.CitySelected, function (err, dist){
                        $scope.districtSelecting = false;
                        $scope.myDistrict = {};
                        $scope.myDistrict.DistSelected  = '';
                        $scope.myDistrict.options       = dist;
                    });
                }
            }


            $scope.myCity.ChangeProvinceFrom = function (service){
                if (service) {
                    $scope._service  = service;
                };
                
                $scope.totalAmount = 0;
                $scope.totalFee = 0;
                $scope.totalVas = 0;      
                $scope.discount = 0;              
                if($scope.myDistrict.DistSelected){
                    
                    $scope.districtSelecting = true;
                    Payment.MerchantCalculate({
                        "To": {
                           "City"       : $scope.myCity.CitySelected,
                           "Province"   : $scope.myDistrict.DistSelected
                        },
                        "Order": {
                            "Amount"    : parseInt($scope.MerchantInfo.Order.Amount),
                            "Quantity"  : parseInt($scope.MerchantInfo.Order.Quantity),
                            "Weight"    : $scope.MerchantInfo.Order.Weight
                        },
                        "Config": {
                            "Service"   : $scope._service,
                            "CoD"       : function (){
                                return (parseInt($scope.user.ol_payment) == 1) ? 2 : ((parseInt($scope.user.ol_payment_banking) == 1) ? 2 : 1);
                            }(),
                            "Protect"   : 2,
                            "Payment"   : ($scope.MerchantInfo.hasOwnProperty('Config')) ? $scope.MerchantInfo.Config.Payment : (parseInt($scope.user.ol_payment) == 1) ? 1 : ((parseInt($scope.user.ol_payment_banking) == 1) ? 1 : 2),
                            "Checking"  : (typeof $scope.IntegrateConfig == 'object' && $scope.IntegrateConfig.checking > 0) ? 1 : 2,
                            "Fragile"   : 2
                        },
                        "Type"          : 'popup',
                        "MerchantKey"   : $scope.MerchantInfo.MerchantKey,
                        "Domain"        : (window.location != window.parent.location) ? document.referrer: document.location.href

                    }
                    ,
                    function (err, data){
                        $scope.districtSelecting = false;

                        if(!err){
                            
                            if(data.data.courier.hasOwnProperty('me')){
                                $scope.courierInfo = data.data.courier.me[0];
                            }else if(data.data.courier.hasOwnProperty('system')){
                                $scope.courierInfo = data.data.courier.system[0];
                            }else {
                                return swal({
                                    title: "Lỗi !",
                                    text: "[#101] Có lỗi xảy ra trong qua trình xử lý , vui long thử lại sau !"
                                })
                            }
                            $scope.MerchantInventory = data.stock;
                            $scope.pvc               = parseInt(data.data.pvc);
                            $scope.pcod              = parseInt(data.data.vas.cod);
                            $scope.feePickup         = $scope.courierInfo.money_pickup || 0;
                            $scope.discount          = parseInt(data.data.seller.discount);


                            if(parseInt($scope.user.ol_payment) == 1 ||  parseInt($scope.user.ol_payment_banking) == 1){
                                $scope.totalAmount  = parseInt($scope.MerchantInfo.Order.Amount) + parseInt(data.data.collect) - $scope.pcod;
                            }else {
                                $scope.totalAmount  =  parseInt(data.data.collect);
                            }
                            $scope.totalAmount += $scope.feePickup;
                        }else {
                            
                            swal({
                                title: "Lỗi !",
                                text: "[#102] Có lỗi xảy ra trong qua trình xử lý , vui long thử lại sau !"
                            }, function (){
                            })
                        }
                        
                    });
                }else {
                    $scope.totalAmount      =  parseInt($scope.MerchantInfo.Order.Amount);
                    $scope.totalFee         =  0;
                    $scope.discount         =  0;
                }
            }
	}])