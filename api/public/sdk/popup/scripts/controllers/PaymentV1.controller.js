"use strict";

angular.module('ShipChungApp')

	.controller('PaymentV1Ctrl', ['$scope', '$http', '$timeout', '$translate', 'Payment', '$location','$routeParams', 'User',
		function ($scope, $http, $timeout, $translate, Payment, $location, $routeParams, User){

            // Lấy token params trên url
            var tokenId         = $location.search().token; // Token property form query string 
            $scope.hasToken     = true; 

            if(!tokenId){
                $scope.hasToken = false;
                return;
            }

            $scope.lang                 = $translate.use(); 
            $scope.MerchantInfo         = {}; 
            $scope.myCity               = {};
            $scope.myDistrict           = {};

            $scope.courierInfo          = {};
            $scope.user                 = {}; // use for form 
            $scope.totalFee             = 0;
            $scope.totalAmount          = 0;
            $scope.saveOrderProcessing  = false;
            $scope.districtSelecting    = false;
            $scope.stateLoading         = true;



            Payment.getMerchantInfoV1(function (err, merchant){
                if(!err){
                    $scope.MerchantInfo = merchant;
                    $scope.listItems    = merchant.items;
                    $scope.totalAmount  = merchant.total_amount;
                    

                    $timeout(function(){ // Implement scroller
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
                            $('#scroller').css('height',($('.bar-right .bar-right-top ul.media-list li.media:first-child').height()+10)*count);
                        }
                    }, 200);

                    init();

                }else {
                    $scope.hasToken = false;
                }
            })

            
            function init(){
                $scope.user                     = User.getUser();
                $scope.myCity.CitySelected      = $scope.user.city || 0;
                $scope.myDistrict.DistSelected  = $scope.user.province || 0;
                $scope.user.ol_payment          = 0;

                // Lấy danh sách các thành phố
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
                    }
                });

            };
            
                    // Thay đổi ngôn ngữ 
            $scope.changeLanguage = function (key) {
                $scope.lang = key;
                //$translate.refresh();
                $translate.use(key);
            };
                

            // initial form input

            




            // Thực thi khi người dùng submit form 
            $scope.submitForm = function(isValid, user) {
                // check to make sure the form is completely valid
                if (isValid) {
                    $scope.saveOrderProcessing = true;

                    User.setUser({
                       "city"       : $scope.myCity.CitySelected,
                       "province"   : $scope.myDistrict.DistSelected,
                       "fullname"   : user.fullname,
                       "phone"      : user.phone,
                       "address"    : user.address,
                       "email"      : user.email
                    });

                    Payment.saveOrder({
                        "Token" : tokenId,
                        "To"    : {
                           "City"       : $scope.myCity.CitySelected,
                           "Province"   : $scope.myDistrict.DistSelected,
                           "Name"       : user.fullname,
                           "Phone"      : user.phone,
                           "Address"    : user.address,
                           "Email"      : user.email
                        }
                    }, function (err, resp){
                        if(!err){
                            
                            $timeout(function (){
                                $scope.saveOrderProcessing = false;
                                $location.search({}).path('/result/'+ 'code');
                            }, 1000);

                        }else {
                            
                        }
                    });

                }
            };


                
                

               
                


                // Bắt sự kiện khi chọn city
            $scope.myCity.ChangeCityFrom = function(){  
                if($scope.myCity.CitySelected > 0)
                {   
                    // Lấy danh sách quận huyện theo thành phố
                    $scope.totalAmount =  $scope.MerchantInfo.total_amount
                    $scope.totalFee = 0;

                    $scope.districtSelecting = true;

                    Payment.getDistrictByCity($scope.myCity.CitySelected, function (err, dist){
                        $scope.districtSelecting = false;
                        $scope.myDistrict = {};
                        $scope.myDistrict.DistSelected  = '';
                        $scope.myDistrict.options       = dist;
                    });
                }
            }


            $scope.myCity.ChangeProvinceFrom = function (){
                

                if($scope.myDistrict.DistSelected){
                    $scope.districtSelecting = true;
                    
                    Payment.MerchantCalculate({
                        "To": {
                           "City"       : $scope.myCity.CitySelected,
                           "Province"   : $scope.myDistrict.DistSelected
                        },
                        "Order": {
                            "Amount"    : $scope.MerchantInfo.total_amount,
                            "Quantity"  : $scope.MerchantInfo.total_item,
                            "Weight"    : $scope.MerchantInfo.weight
                        },
                        "Config": {
                            "Service"   : 2,
                            "CoD"       : 1,
                            "Protect"   : 1,
                            "Payment"   : 1,
                            "Checking"  : 1,
                            "Fragile"   : 1
                        },
                        "MerchantKey"   : "196889408d70273de871b578cdc37a73",
                        "Domain"        : $scope.MerchantInfo.return_url

                    }
                    ,
                    function (err, data){
                        $timeout(function (){
                            $scope.districtSelecting = false;
                        }, 500);
                        
                        $scope.courierInfo = data.data.courier.system;
                        $scope.totalFee = data.data.fee.total_fee + data.data.fee.total_vas;


                        $scope.totalAmount =  $scope.MerchantInfo.total_amount + $scope.totalFee;
                    });
                }else {
                    $scope.totalAmount =  $scope.MerchantInfo.total_amount
                    $scope.totalFee = 0;
                }
                
            }
            

	}])