'use strict';
angular.module('app').config(
  [          '$stateProvider', '$urlRouterProvider', '$controllerProvider', '$compileProvider', '$filterProvider', '$provide',
    function ($stateProvider,   $urlRouterProvider,   $controllerProvider,   $compileProvider,   $filterProvider,   $provide) {
        
        // lazy controller, directive and service
        app.controller = $controllerProvider.register;
        app.directive  = $compileProvider.directive;
        app.filter     = $filterProvider.register;
        app.factory    = $provide.factory;
        app.service    = $provide.service;
        app.constant   = $provide.constant;
        app.value      = $provide.value;

        $urlRouterProvider
            .otherwise('/access/signin');
        $stateProvider
            .state('app', {
                abstract: true,
                url: '/app',
                templateUrl: 'tpl/app.html'
            })
            .state('app.dashboard', {
                url: '/dashboard',
                templateUrl: 'tpl/app_dashboard.html'
            })
            .state('app.postman', {
                url: '/postman',
                templateUrl: 'tpl/postman/postman.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/postman/PostmanCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.detail-postman', {
                url: '/detail-postman/:postman_id',
                templateUrl: 'tpl/postman/detail.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/postman/PostmanCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.courier', {
                url: '/courier',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.courier.add', {
                url: '/add',
                templateUrl: 'tpl/courier/action.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load('toaster').then(
                            function(){
                                return $ocLazyLoad.load('js/controller/courier/CourierActionCtrl.js');
                            }
                        );
                    }]
                }
            })     
            .state('app.courier.edit', {
                url: '/edit/:id',
                templateUrl: 'tpl/courier/action.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load('toaster').then(
                            function(){
                                return $ocLazyLoad.load('js/controller/courier/CourierActionCtrl.js');
                            }
                        );
                    }]
                }
            })                
            .state('app.courier.list', {
                url: '/list',
                templateUrl: 'tpl/courier/courier.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/courier/CourierCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.courier.config', {
                url: '/courier-config/:courier_id',
                templateUrl: 'tpl/courier/courier-config.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/courier/CourierConfigCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.courier.promise', {
                url: '/promise/:courier_id',
                templateUrl: 'tpl/courier/courier-promise.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/courier/CourierPromiseCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.courier.promise_delivery', {
                url: '/promise_delivery/:courier_id',
                templateUrl: 'tpl/courier/courier-promise-delivery.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/courier/CourierPromiseDeCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.courier.comission', {
                url: '/comission',
                templateUrl: 'tpl/courier/comission.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/courier/CourierComissionCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.courier.area_location', {
                url: '/area-location/:courier_id',
                templateUrl: 'tpl/courier/area_location.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox','xeditable']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/courier/AreaLocationCtrl.js');
                            }
                        );
                    }]
                }
            })
            //Config location,stage,area
            .state('app.courier.config-info', {
                url: '/config-info',
                templateUrl: 'tpl/config_info/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox','xeditable']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/config_info/ConfigInfoCtrl.js');
                            }
                        );
                    }]
                }
            })
            // location  city - district - ward
            .state('app.location', {
                url: '/location',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.location.city', {
                url: '/city',
                templateUrl: 'tpl/location/city.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/location/CityCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.location.district', {
                url: '/district/{city_id:[0-9]{0,4}}',
                templateUrl: 'tpl/location/district.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/location/DistrictCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.location.ward', {
                url: '/ward/{city_id:[0-9]{0,4}}/{district_id:[0-9]{0,6}}',
                templateUrl: 'tpl/location/ward.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/location/WardCtrl.js');
                            }
                        );
                    }]
                }
            })
            
            .state('app.location.refuse', {
                url: '/refuse/{courier:[0-9]{0,4}}',
                templateUrl: 'tpl/location/refuse.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/location/RefuseCtrl.js');
                            }
                        );
                    }]
                }
            })

            .state('app.fee', {
                url: '/fee',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.fee.list', {
                url: '/list',
                templateUrl: 'tpl/courier/courier_fee.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/courier/CourierFeeCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.fee.add', {
                url: '/add/:courier_fee_id',
                templateUrl: 'tpl/courier/fee_add.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox','angularFileUpload']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/courier/CourierFeeActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.fee.vas', {
                url: '/fee_vas',
                templateUrl: 'tpl/courier/courier_fee_vas.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/courier/CourierFeeVasCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.fee.add_vas', {
                url: '/add_vas/:id',
                templateUrl: 'tpl/courier/fee_vas_add.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load('toaster').then(
                            function(){
                                return $ocLazyLoad.load('js/controller/courier/CourierFeeVasActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            // discount
            .state('app.discount', { 
                url: '/fee',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.discount.config', {
                url: '/discount',
                templateUrl: 'tpl/courier/courier_discount.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','toaster','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/courier/CourierDiscountCtrl.js');
                            }
                        );
                    }]
                }
            })
            // fee pickup
            .state('app.fee_pickup', { 
                url: '/fee_pickup',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.fee_pickup.list', {
                url: '/list',
                templateUrl: 'tpl/fee_pickup/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox','xeditable']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/fee_pickup/FeePickupCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.fee_pickup.add', {
                url: '/add',
                templateUrl: 'tpl/fee_pickup/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox','angularFileUpload']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/fee_pickup/FeePickupActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.fee_pickup.list_uploaded', {
                url: '/list_uploaded/:id',
                templateUrl: 'tpl/fee_pickup/list_uploaded.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['toaster','angular-bootbox','angularFileUpload']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/fee_pickup/ActionExcelCtrl.js');
                            }
                        );
                    }]
                }
            })
            //fee delivery
            .state('app.fee_delivery', { 
                url: '/fee_delivery',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            
            .state('app.fee_delivery.add', {
                url: '/add',
                templateUrl: 'tpl/fee_delivery/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/fee_delivery/FeeDeliveryActionCtrl.js');
                            }
                        );
                    }]
                }
            })

            .state('app.fee_delivery.list', {
                url: '/list',
                templateUrl: 'tpl/fee_delivery/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox','xeditable']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/fee_delivery/FeeDeliveryCtrl.js');
                            }
                        );
                    }]
                }
            })
            //post office
            .state('app.post_office', { 
                url: '/post_office',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })

            .state('app.post_office.list', {
                url: '/list',
                templateUrl: 'tpl/post_office/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/post_office/PostOfficeCtrl.js');
                            }
                        );
                    }]
                }
            })

            .state('app.post_office.add', {
                url: '/add',
                templateUrl: 'tpl/post_office/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/post_office/PostOfficeActionCtrl.js');
                            }
                        );
                    }]
                }
            })

            .state('app.post_office.edit', {
                url: '/edit/:id',
                templateUrl: 'tpl/post_office/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/post_office/PostOfficeActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            //status
            .state('app.status', { 
                url: '/status',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.status.list', {
                url: '/list',
                templateUrl: 'tpl/status/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/status/StatusCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.status.add', {
                url: '/add',
                templateUrl: 'tpl/status/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/status/StatusActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.status.edit', {
                url: '/edit/:id',
                templateUrl: 'tpl/status/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/status/StatusActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            //status accept
            .state('app.status_accept', { 
                url: '/status_accept',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.status_accept.list', {
                url: '/list',
                templateUrl: 'tpl/status_accept/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/status_accept/StatusAcceptCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.status_accept.add', {
                url: '/add',
                templateUrl: 'tpl/status_accept/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/status_accept/StatusAcceptActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.status_accept.edit', {
                url: '/edit/:id',
                templateUrl: 'tpl/status_accept/form.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/status_accept/StatusAcceptActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            //
            .state('app.estimate', { 
                url: '/estimate',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.estimate.list', {
                url: '/list',
                templateUrl: 'tpl/estimate/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/estimate/EstimateCtrl.js');
                            }
                        );
                    }]
                }
            })
            
            // others
            .state('lockme', {
                url: '/lockme',
                templateUrl: 'tpl/page_lockme.html'
            })
            .state('access', {
                url: '/access',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('access.signin', {
                url: '/signin',
                templateUrl: 'tpl/page_signin.html'
            })
            .state('access.signup', {
                url: '/signup',
                templateUrl: 'tpl/page_signup.html'
            })
            .state('access.forgotpwd', {
                url: '/forgotpwd',
                templateUrl: 'tpl/page_forgotpwd.html'
            })
            .state('access.404', {
                url: '/404',
                templateUrl: 'tpl/page_404.html'
            })
    }
  ]
);