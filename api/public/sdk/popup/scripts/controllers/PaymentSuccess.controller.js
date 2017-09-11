"use strict";

angular.module('ShipChungApp')

	.controller('PaymentSuccessCtrl', ['$scope', '$http', '$timeout', '$translate', 'Payment', '$location','$routeParams',
		function ($scope, $http, $timeout, $translate, Payment, $location, $routeParams){
            // Lấy token params trên url
            
            $scope.hasToken         = true;
            $scope.TrackingCode     = $routeParams.code;
            $scope.checkoutId       = $location.search().id;
            $scope.TrackingInfo     = {};
            $scope.parentDomain     = document.referrer;

            $scope.lang             = $translate.use();
            $scope.stateLoading     = true;
            $scope.isCOD            = true;
            $scope.isPaymentBanking = false;

            if($routeParams.nlToken){
                $scope.isCOD        = false;
            }
            if($location.search().payment_banking && $location.search().payment_banking == 1){
                $scope.isPaymentBanking = true;
                $scope.isCOD        = false;
            }

            $scope.bankName  = function (code){
                code = code.toUpperCase();
                return (appConfig.bank[code]) ? appConfig.bank[code] : '';
            }


            

            $scope.convertTime = function (unixTime){
                if(unixTime){
                    unixTime    = parseInt(unixTime);
                    var date    = new Date(unixTime*1000),
                        hours   = date.getHours(),
                        minutes = "0" + date.getMinutes(),
                        seconds = "0" + date.getSeconds(),
                        day     = date.getDate(), 
                        month   = date.getMonth() + 1,
                        year    = date.getFullYear();

                    var formattedTime = hours + ':' + minutes.substr(minutes.length - 2) + ':' + seconds.substr(seconds.length-2) + ' ' + day + '/' +"0"+ month + '/' + year;

                    return formattedTime;
                }
            }

            Payment.getOrderTrackInfo($scope.TrackingCode, $location.search().t, function (err, resp) {
                $scope.stateLoading     = false;
                if(!err){
                    $scope.TrackingInfo = resp.data[0];
                    $scope.bankingInfo = resp.banking_info;
                    
                    
                    var urls = 'http://services.shipchung.vn/popup/return-url' + '?tracking_code=' + $scope.TrackingCode + '&method=' +( ($scope.isCOD) ? 'cod' : 'payment')+'&id='+ $scope.checkoutId;
                    $http.get(urls).success(function (data){
                        if(!data.error && data.message == 'success'){
                            $http.get(data.data).success(function (resp){
                                
                            })
                        }
                    })
                }
                $timeout(function (){
                    if($('#scroller table tr').length > 2){
                        /*$('#scroller table tr').css('height', $('#bankingInfo table tr:first-child').height()+10 * 2);*/
                        $('#scroller').css('overflow-y', 'scroll');
                    }else if($('#scroller table tr').length == 1){
                        $('#scroller table tr').css('height', $('#bankingInfo table tr:first-child').height()+10);
                    }
                    
                }, 500);
                
            });

            $scope.changeLanguage   = function(key) {
                $scope.lang = key;
                //$translate.refresh();
                $translate.use(key);
            };

	}])