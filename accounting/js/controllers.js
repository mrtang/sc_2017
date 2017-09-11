'use strict';
var date                    = new Date();
/* Controllers */

angular.module('app.controllers', ['pascalprecht.translate', 'ngCookies'])
  .controller('AppCtrl', ['$scope', '$rootScope','$translate', '$localStorage', '$window', '$timeout', '$stateParams', 'loginService', 'Order', 'Config_Status', 'Base',
    function(              $scope,   $rootScope, $translate,   $localStorage,   $window , $timeout, $stateParams, loginService, Order, Config_Status, Base) {
      // add 'ie' classes to html
      var isIE = !!navigator.userAgent.match(/MSIE/i);
      isIE && angular.element($window.document.body).addClass('ie');
      isSmartDevice( $window ) && angular.element($window.document.body).addClass('smart');

        // config
          $scope.app = {
            name: 'ShipChung',
            version: '1.2.0',
            // for chart colors
            color: {
              primary: '#7266ba',
              info:    '#23b7e5',
              success: '#27c24c',
              warning: '#fad733',
              danger:  '#f05050',
              light:   '#e8eff0',
              dark:    '#3a3f51',
              black:   '#1c2b36'
            },
            settings: {
              themeID: 1,
              navbarHeaderColor: 'bg-info',
              navbarCollapseColor: 'bg-info dk',
              asideColor: 'bg-black',
              headerFixed: false,
              asideFixed: false,
              asideFolded: true,
              asideDock: false,
              container: false
            }
          }
      
          function isSmartDevice( $window )
          {
              // Adapted from http://www.detectmobilebrowsers.com
              var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
              // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
              return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
          }

          $scope.logout=function(){
            loginService.logout();
          }

        $scope.link_export      = ApiPath;
        $scope.link_storage     = ApiStorage;
        $scope.link_seller      = ApiSeller;

        $scope.list_courier         = {};
        $scope.courier              = {};
        $scope.list_city            = {};
        $scope.city                 = {};
        $scope.district             = {};
        $scope.list_status          = {};
        $scope.group_status         = {};
        $scope.status_group         = {};
        $scope.group_order_status   = {};
        $scope.sc_loyalty_level     = {};
        $scope.list_vip             = {};
        $scope.warehouse_warehouse  = {};
        $scope.list_country         = {
            101 :   'Indonesia',
            133 :   'Malaysia',
            174 :   'Philippines',
            237 :   'Vietnam'
        };

        

        $scope.setCountry = function(CountryKey) {
            if($rootScope.userInfo != undefined){
                $rootScope.userInfo.country_id   = CountryKey;

                $timeout(function(){
                    $window.location.reload();
                }, 3000);
            }
        };

        $scope.list_color       = Config_Status.order_color;
        // status respond
        $scope.status_error     = [408,504];

        // Check quyền hệ thống
        $scope.check_privilege  = function(code, action){
            if($rootScope == undefined || $rootScope.userInfo == undefined){
                return false;
            }

            if($rootScope.userInfo != undefined && ($rootScope.userInfo.privilege == 2 || ($rootScope.userInfo.group_privilege[code] && $rootScope.userInfo.group_privilege[code][action] == 1))){
                return true;
            }else{
                return false;
            }
        }

        $scope.__get_warehouse  = function(){
            if($localStorage['warehouse_warehouse'] == undefined){
                Base.WareHouse().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_warehouse']    = result.data.data;
                        $scope.warehouse_warehouse              = result.data.data;
                    }
                }).finally(function() {

                });
            }else{
                $scope.warehouse_warehouse = $localStorage['warehouse_warehouse'];
            }
        }

        $scope.get_sc_district    = function(){
            if($localStorage['district'] != undefined){
                $scope.district = $localStorage['district'];
                $scope.__get_warehouse();
            }else{
                Base.AllDistrict().then(function (result) {
                    if(!result.data.error){
                        angular.forEach(result.data.data, function(value, key) {
                            $scope.district[value.id]    = value.district_name;
                        });

                        $localStorage['district']       = $scope.district;
                    }
                }).finally(function() {
                    $scope.__get_warehouse();
                });
            }
        }

        $scope.get_sc_service    = function(){
            if($localStorage['list_service'] != undefined){
                $scope.list_service = $localStorage['list_service'];
                $scope.get_sc_district();
            }else{
                Base.Service().then(function (result) {
                    if(!result.data.error){
                        $scope.list_service             = result.data.data;
                        $localStorage['list_service']   = result.data.data;
                    }
                }).finally(function() {
                    $scope.get_sc_district();
                });
            }
        }

        $scope.get_sc_city    = function(){
            if($localStorage['list_city'] != undefined){
                $scope.list_city    = $localStorage['list_city'];
                $scope.city         = $localStorage['city'];
                $scope.get_sc_service();
            }else{
                Base.City().then(function (result) {
                    if(!result.data.error){
                        $scope.list_city        = result.data.data;
                        angular.forEach(result.data.data, function(value, key) {
                            if([18,19,6,1,3,14,12,7,10,5,4,17,16,15,11,2,23,22,25,24,8,28,20,26,31,30,27,32,29,35,34,,37,36,33].indexOf(value.id) != -1){
                                value.city_name += '(MB)';
                            }else{
                                value.city_name += '(MN)';
                            }
                            $scope.city[value.id]    = value.city_name;
                        });

                        $localStorage['list_city']  = result.data.data;
                        $localStorage['city']       = $scope.city;
                    }
                }).finally(function() {
                    $scope.get_sc_service();
                });
            }
        }

        $scope.get_sc_status    = function(){
            if($localStorage['list_status'] != undefined){
                $scope.list_status = $localStorage['list_status'];
                $scope.get_sc_city();
            }else{
                Base.Status().then(function (result) {
                    if(!result.data.error){
                        $scope.list_status             = result.data.data;
                        $localStorage['list_status']   = result.data.data;
                    }
                }).finally(function() {
                    $scope.get_sc_city();
                });
            }
        }

        $scope.get_sc_courier   = function(){
            if($localStorage['list_courier'] != undefined){
                $scope.list_courier = $localStorage['list_courier'];
                $scope.get_sc_status();
            }else{
                Base.Courier().then(function (result) {
                    if(!result.data.error){
                        $scope.list_courier             = result.data.data;
                        $localStorage['list_courier']   = result.data.data;
                    }
                }).finally(function() {
                    $scope.get_sc_status();
                });
            }
        }

        $scope.__get_user_vip   = function(){
            if($localStorage['sc_list_vip'] == undefined){
                Base.UserVip().then(function (result) {
                    if(!result.data.error){
                        $scope.list_vip                 = result.data.data;
                        $localStorage['sc_list_vip']    = result.data.data;
                    }
                }).finally(function() {
                    $scope.get_sc_courier();
                });
            }else{
                $scope.list_vip = $localStorage['sc_list_vip'];
                $scope.get_sc_courier();
            }
        }

        $scope.get_sc_loyalty_level = function(){
            if($localStorage['sc_loyalty_level'] != undefined){
                $scope.sc_loyalty_level    = $localStorage['sc_loyalty_level'];
                $scope.__get_user_vip();
            }else{
                Base.loyalty_level().then(function (result) {
                    if(!result.data.error){
                        $localStorage['sc_loyalty_level']   = result.data.data;
                        $scope.sc_loyalty_level                = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_user_vip();
                });
            }
        }

        if($localStorage['group_status'] != undefined) {
            $scope.group_status             = $localStorage['group_status'];
            $scope.group_order_status       = $localStorage['group_order_status'];
            $scope.status_group             = $localStorage['status_group'];
            $scope.get_sc_loyalty_level();
        }else{
            Base.GroupStatus().then(function (result) {
                if(!result.data.error){
                    $scope.group_order_status   = {};
                    angular.forEach(result.data.list_group, function(value) {
                        $scope.group_status[value.id]   = value.name;
                        if(value.group_order_status){
                            angular.forEach(value.group_order_status, function(v) {
                                $scope.status_group[+v.order_status_code]    = v.group_status;

                                if($scope.group_order_status[+v.group_status] == undefined){
                                    $scope.group_order_status[+v.group_status]  = [];
                                }
                                $scope.group_order_status[+v.group_status].push(+v.order_status_code);
                            });
                        }
                    });
                    $localStorage['group_status']               = $scope.group_status;
                    $localStorage['group_order_status']         = $scope.group_order_status;
                    $localStorage['status_group']               = $scope.status_group;
                }
            }).finally(function() {
                $scope.get_sc_loyalty_level();
            });
        }

        $scope.caculater_totalfee = function(data, status){
            if(data == undefined) return 0;
            var total = 1*data.sc_pvc + 1*data.sc_pvk + 1*data.sc_remote + 1*data.sc_clearance - 1*data.sc_discount_pvc;

            if(status == 66){
                total   += 1*data.sc_pch;
            }else if(status == 67){
                total   = total;
            }else if(status == 52){
                total   += 1*data.sc_pbh + 1*data.sc_cod - 1*data.sc_discount_cod;
            }else{
                total   += 1*data.sc_pbh + 1*data.sc_cod + 1*data.sc_pch - 1*data.sc_discount_cod;
            }

            return total;
        }

        $scope.calculate_discount_fee   = function(data, status){
            if(data == undefined) return 0;
            var total = 1*data.sc_discount_pvc;

            if([66,67].indexOf(1*status) == -1){
                total += 1*data.sc_discount_cod;
            }

            return total;
        }

        $scope.caculater_hvcfee = function(data, status){
            if(data == undefined) return 0;
            var total = 1*data.hvc_pvc;

            if(status == 66){
                total   += 1*data.hvc_pch;
            }else if(status == 67){
                total   = total;
            }else if(status == 52){
                total   += 1*data.hvc_pbh + 1*data.hvc_cod;
            }else{
                total   += 1*data.hvc_pch + 1*data.hvc_pbh + 1*data.hvc_cod;
            }

            return total;
        }

        $scope.caculater_warehouse_fee  = function(data){
            if(data == undefined) return 0;
            return 1*data.sc_plk + 1*data.sc_pdg + 1*data.sc_pxl;
        }

        $scope.caculater_discount_warehouse_fee  = function(data){
            if(data == undefined) return 0;
            return 1*data.sc_discount_plk + 1*data.sc_discount_pdg + 1*data.sc_discount_pxl;
        }

        $scope.caculater_warehouse_historical_fee  = function(data){
            if(data == undefined) return 0;
            return 1*data.historical_plk + 1*data.historical_pdg + 1*data.historical_pxl;
        }

        $scope.caculater_discount_warehouse_historical_fee  = function(data){
            if(data == undefined) return 0;
            return 1*data.historical_discount_plk + 1*data.historical_discount_pdg + 1*data.historical_discount_pxl;
        }

  }])
  // signin controller
  .controller('SigninFormController', ['$scope', '$http', '$state', 'loginService', function($scope, $http, $state, loginService) {
    $scope.user = {};

    $scope.authError = null;
    $scope.login = function(data) {
      $scope.authError = null;
      // Try to login
      $scope.onProgress = true;
	  loginService.login(data,$scope,$state); //call login service
    };
  }])
 ;