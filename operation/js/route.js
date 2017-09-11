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
            .state('delivery', {
                abstract: true,
                url: '/van-chuyen',
                templateUrl: 'tpl/layout.html'
            })

            .state('delivery.dashboard', {
                url: '/dashboard',
                templateUrl: 'tpl/dashboard.html'
            })

            .state('delivery.app', {
                abstract: true,
                url: '/app',
                templateUrl: 'tpl/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/config/ConfigCtrl.js');
                        }]
                }
            })

            .state('delivery.tasks', {
                abstract: true,
                url: '/tasks',
                templateUrl: 'tpl/tasks/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','angular-bootbox', 'angularFileUpload']);
                        }]
                }
            })

            .state('delivery.tasks.list', {
                url: '/list',
                templateUrl: 'tpl/tasks/lists.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','angular-bootbox', 'angularFileUpload', 'js/controller/tasks/TasksController.js']);
                        }]
                }
            })

            .state('delivery.tasks.list.detail', {
                url: '/:state/:id?category',
                templateUrl: 'tpl/tasks/list_detail.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','angular-bootbox', 'angularFileUpload', 'js/controller/tasks/TasksController.js']);
                        }]
                }
            })



            .state('delivery.app.config', {
                abstract: true,
                url: '/cau-hinh-he-thong',
                template: '<div ui-view class="fade-in-big smooth"></div>',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','angular-bootbox', 'angularFileUpload']);
                        }]
                }
            })
            .state('delivery.app.config.group_user', {
                url: '/nhom-nhan-vien',
                templateUrl: 'tpl/config/group_user.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['js/services/PrivilegeService.js', 'js/controller/config/GroupUserCtrl.js']);
                        }]
                }
            })
            .state('delivery.app.config.privileges', {
                url: '/quyen-nhan-vien',
                templateUrl: 'tpl/config/privileges.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['js/services/PrivilegeService.js', 'js/controller/config/PrivilegeCtrl.js']);
                        }]
                }
            })

            .state('delivery.app.config.pipe_status', {
                url: '/trang-thai-xu-ly',
                templateUrl: 'tpl/config/pipe-status.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                ['js/services/PipeService.js',
                                'js/services/PrivilegeService.js',
                                 'js/controller/config/PipeStatusCtrl.js'
                                ]);
                        }]
                }
            })
            .state('delivery.app.config.kpi_category', {
                url: '/cau-hinh-kpi',
                templateUrl: 'tpl/config/kpi_category.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['js/services/PrivilegeService.js','js/controller/config/KpiCtrl.js']);
                        }]
                }
            })
            .state('delivery.app.config.kpi_employee', {
                url: '/cau-hinh-kpi-nhan-vien',
                templateUrl: 'tpl/config/kpi_employee.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['js/services/PrivilegeService.js','js/controller/config/KpiEmployeeCtrl.js']);
                        }]
                }
            })

            
            .state('delivery.log', {
                abstract: true,
                url: '/log',
                templateUrl: 'tpl/log/app.html',
                resolve: {
                    'CanAccess': function ($q ,$rootScope, toaster){
                        var deferred = $q.defer();
                        if($rootScope.userInfo.privilege == 3 && $rootScope.userInfo.group == 3){
                            toaster.pop('warning', 'Thông báo', 'Bạn không có quyền truy cập !!');
                            deferred.reject();
                        }else {
                            deferred.resolve();
                        }
                        
                        return deferred.promise;

                    }
                }
            })
            .state('delivery.log.lading', {
                url: '/lading',
                templateUrl: 'tpl/log/lading.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/log/LogCreateLadingCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.change-lading', {
                url: '/change-lading',
                templateUrl: 'tpl/log/change-lading.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/log/LogChangeLadingCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.journey', {
                url: '/journey',
                templateUrl: 'tpl/log/journey.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/log/LogJourneyCtrl.js'
                                ]
                            );
                        }]
                }
            })

            .state('delivery.log.change-payment', {
                url: '/change-payment',
                templateUrl: 'tpl/log/change-payment.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/log/LogChangePayment.js'
                                ]
                            );
                        }]
                }
            })
            
            .state('delivery.log.sms', {
                url: '/sms',
                templateUrl: 'tpl/log/sms.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/log/LogSentSmsCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.over_weight', {
                url: '/over_weight',
                templateUrl: 'tpl/log/over_weight.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/LogOverWeightCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.accept_return', {
                url: '/duyet-hoan',
                templateUrl: 'tpl/log/accept_return.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/LogAcceptReturnCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.report_replay', {
                url: '/phat-lai',
                templateUrl: 'tpl/log/report_replay.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/LogReportReplayCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.report_cancel', {
                url: '/bao-huy',
                templateUrl: 'tpl/log/report_cancel.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/LogReportCancelCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.statistic', {
                url: '/statistic?type',
                templateUrl: 'tpl/log/statistic.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/LogStatisticCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.statisticlist', {
                url: '/statisticlist/:id?from_date&to_date',
                templateUrl: 'tpl/log/statisticlist.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/LogStatisticListCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.createsms', {
                url: '/createsms',
                templateUrl: 'tpl/log/notice_sms.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/NoticeSmsCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.excelsms', {
                url: '/excelsms',
                templateUrl: 'tpl/log/excel_sms.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'angularFileUpload','js/controller/log/NoticeExcelSmsCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.log.noticeapp', {
                url: '/noticeapp',
                templateUrl: 'tpl/log/notice_app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                    'js/controller/log/NoticeAppCtrl.js'
                                ]
                            );
                        }]
                }
            })

            .state('delivery.search', {
                url: '/search?search',
                templateUrl: 'tpl/search.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','js/SearchCtrl.js']);
                        }]
                }
            })

            .state('delivery._search', {
                url: '/_search?search',
                templateUrl: 'tpl/_search.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable', 'js/_SearchCtrl.js']);
                        }]
                }
            })


            .state('delivery.user_dashboard', {
                url: '/viec-can-lam',
                templateUrl: 'tpl/user_dashboard.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/UserDashboardCtrl.js');
                        }]
                }
            })

            // Pickup
            .state('delivery.accounting', {
                abstract: true,
                url: '/accounting',
                templateUrl: 'tpl/accounting/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','angular-bootbox', 'angularFileUpload', 'js/controller/accounting/coupons/CreateCouponCtrl.js']);
                        }]
                }
            })

            .state('delivery.accounting.coupons', {
                url: '/coupons',
                templateUrl: 'tpl/accounting/coupons/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/accounting/coupons/CouponsCtrl.js'
                                ]
                            );
                        }]
                }
            })

            .state('delivery.accounting.coupons-list', {
                url: '/coupons/:id',
                templateUrl: 'tpl/accounting/coupons/coupon-list.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/accounting/coupons/CouponsListCtrl.js'
                                ]
                            );
                        }]
                }
            })
            .state('delivery.accounting.refer', {
                url: '/refer',
                templateUrl: 'tpl/accounting/coupons/index_refer.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(
                                [
                                 'js/controller/accounting/coupons/CouponsReferListCtrl.js'
                                ]
                            );
                        }]
                }
            })

            .state('delivery.accounting.cashin', {
                abstract: true,
                url: '/cashin',
                template: '<div ui-view class="fade-in-big smooth"></div>'
            })

            .state('delivery.accounting.vimo-verify', {
                url: '/vimo-verify',
                templateUrl: 'tpl/accounting/vimo/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/accounting/vimo/VimoVerifyController.js');
                        }]
                }
            })

            .state('delivery.accounting.vimo-create', {
                url: '/vimo-create',
                templateUrl: 'tpl/accounting/vimo/create.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/accounting/vimo/VimoVerifyController.js');
                        }]
                }
            })




            .state('delivery.accounting.cashin.show', {
                url: '',
                templateUrl: 'tpl/accounting/cashin/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/accounting/cashin/CashinController.js');
                        }]
                }
            })

            .state('delivery.accounting.cashin.add', {
                url: '/add',
                templateUrl: 'tpl/accounting/cashin/add.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/accounting/cashin/CashinController.js');
                            }
                        );
                    }]
                }
            })

            .state('delivery.accounting.cashin.add-excel', {
                url: '/add-excel',
                templateUrl: 'tpl/accounting/cashin/add-excel.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/accounting/cashin/CashinAddExcelController.js');
                            }
                        );
                    }]
                }
            })

            .state('delivery.accounting.cashin.add-excel-list', {
                url: '/add-excel-list/:id',
                templateUrl: 'tpl/accounting/cashin/add-excel.list.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['xeditable','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/accounting/cashin/CashinAddExcelController.js');
                            }
                        );
                    }]
                }
            })

            // Order
            .state('delivery.order', {
                abstract: true,
                url: '/can-xu-ly',
                templateUrl: 'tpl/order/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angular-bootbox');
                        }]
                }
            })
            .state('delivery.order.update_slow', {
                url: '/cap-nhat-cham?tracking_code',
                templateUrl: 'tpl/order/update_slow/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/UpdateSlowCtrl.js');
                        }]
                }
            })
            .state('delivery.order.amount', {
                url: '/gia-tri-cao?tracking_code',
                templateUrl: 'tpl/order/amount/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/AmountCtrl.js');
                        }]
                }
            })
            .state('delivery.order.over_weight', {
                url: '/vuot-can?tracking_code',
                templateUrl: 'tpl/order/over_weight/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/OverWeightCtrl.js');
                        }]
                }
            })
            .state('delivery.order.big_weight', {
                url: '/khoi-luong-lon?tracking_code',
                templateUrl: 'tpl/order/big_weight/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/BigWeightCtrl.js');
                        }]
                }
            })
            .state('delivery.order.delivery_slow_nt', {
                url: '/giao-cham-noi-thanh?tracking_code',
                templateUrl: 'tpl/order/delivery_slow_nt/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/DeliverySlowNTCtrl.js');
                        }]
                }
            })

            .state('delivery.order.delivery_slow', {
                url: '/giao-cham?tracking_code',
                templateUrl: 'tpl/order/delivery_slow/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/DeliverySlowCtrl.js');
                        }]
                }
            })
            .state('delivery.order.pickup_slow', {
                url: '/lay-cham?tracking_code',
                templateUrl: 'tpl/order/pickup_slow/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/PickupSlowCtrl.js');
                        }]
                }
            })

            .state('delivery.order.report_replay', { // Yêu cầu phát lại
                url: '/yeu-cau-phat-lai?tracking_code',
                templateUrl: 'tpl/order/report_replay/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/ReportReplayCtrl.js');
                        }]
                }
            })
            .state('delivery.order.return_slow', {
                url: '/hoan-cham?tracking_code',
                templateUrl: 'tpl/order/return_slow/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/ReturnSlowCtrl.js');
                        }]
                }
            })
            .state('delivery.order.courier_note', {
                url: '/ghi-chu-hvc?tracking_code',
                templateUrl: 'tpl/order/courier_note/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/CourierNoteCtrl.js');
                        }]
                }
            })
            .state('delivery.order.recover', {
                url: '/bao-lay-lai',
                templateUrl: 'tpl/order/recover/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/RecoverCtrl.js');
                        }]
                }
            })
            .state('delivery.order.report_store', { // Yêu cầu lưu kho
                url: '/yeu-cau-luu-kho?tracking_code',
                templateUrl: 'tpl/order/report_store/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/order/ReportStoreCtrl.js');
                        }]
                }
            })

            // Pickup
            .state('delivery.pickup', {
                abstract: true,
                url: '/pickup',
                templateUrl: 'tpl/pickup/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angular-bootbox');
                        }]
                }
            })
            .state('delivery.pickup.event', {
                url: '/event',
                templateUrl: 'tpl/pickup/event/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/EventCtrl.js');
                        }]
                }
            })
            .state('delivery.pickup.request', {
                url: '/request?tracking_code',
                templateUrl: 'tpl/pickup/request/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/RequestCtrl.js');
                        }]
                }
            })
            .state('delivery.pickup.accept', {
                url: '/accept?tracking_code',
                templateUrl: 'tpl/pickup/accept/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/AcceptCtrl.js');
                        }]
                }
            })
            .state('delivery.pickup.stocking', {
                url: '/stocking?tracking_code',
                templateUrl: 'tpl/pickup/stocking/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/StockingCtrl.js');
                        }]
                }
            })
            .state('delivery.pickup.stocking_', {
                url: '/stocking_?tracking_code',
                templateUrl: 'tpl/pickup/stocking_/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/StockingCtrl_.js');
                        }]
                }
            })
            .state('delivery.pickup.problem', {
                url: '/problem?tracking_code',
                templateUrl: 'tpl/pickup/problem/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/ProblemCtrl.js');
                        }]
                }
            })
            .state('delivery.pickup.problem_', {
                url: '/problem_?tracking_code',
                templateUrl: 'tpl/pickup/problem_/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/ProblemCtrl_.js');
                        }]
                }
            })
            .state('delivery.pickup.stocked', {
                url: '/stocked?tracking_code',
                templateUrl: 'tpl/pickup/stocked/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/StockedCtrl.js');
                        }]
                }
            })
            .state('delivery.pickup.cancel', {
                url: '/cancel?tracking_code',
                templateUrl: 'tpl/pickup/cancel/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/CancelCtrl.js');
                            return $ocLazyLoad.load('js/controller/pickup/CancelCtrl.js');
                        }]
                }
            })
            .state('delivery.pickup.address', {
                url: '/address?tracking_code',
                templateUrl: 'tpl/pickup/address/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/AddressCtrl.js');
                        }]
                }
            })

            .state('delivery.pickup.addressInDay', {
                url: '/address-in-day?tracking_code',
                templateUrl: 'tpl/pickup/address-in-day/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/pickup/AddressInDayCtrl.js');
                        }]
                }
            })


            // Delivery
            .state('delivery.delivery', {
                abstract: true,
                url: '/delivery',
                templateUrl: 'tpl/delivery/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angular-bootbox');
                        }]
                }
            })
            .state('delivery.delivery.in_transit', {
                url: '/in_transit?tracking_code',
                templateUrl: 'tpl/delivery/in_transit/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/delivery/InTransitCtrl.js');
                        }]
                }
            })
            .state('delivery.delivery.delivering', {
                url: '/delivering?tracking_code',
                templateUrl: 'tpl/delivery/delivering/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/delivery/DeliveringCtrl.js');
                        }]
                }
            })
            .state('delivery.delivery.problem', {
                url: '/problem?tracking_code',
                templateUrl: 'tpl/delivery/problem/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/delivery/DProblemCtrl.js');
                        }]
                }
            })
            .state('delivery.delivery.cancel', {
                url: '/cancel?tracking_code',
                templateUrl: 'tpl/delivery/cancel/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/delivery/DCancelCtrl.js');
                        }]
                }
            })
            .state('delivery.delivery.status_problem', {
                url: '/status_problem?tracking_code',
                templateUrl: 'tpl/delivery/status_problem/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/delivery/StatusProblemCtrl.js');
                        }]
                }
            })
            .state('delivery.delivery.delivered', {
                url: '/delivered?tracking_code',
                templateUrl: 'tpl/delivery/delivered/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/delivery/DeliveredCtrl.js');
                        }]
                }
            })

            /**
             * Loyalty
             */
            .state('delivery.loyalty', {
                abstract: true,
                url: '/khach-hang-than-thiet',
                templateUrl: 'tpl/loyalty/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/loyalty/ConfigCtrl.js');
                        }]
                }
            })
            .state('delivery.loyalty.user', {
                url: '/danh-sach_khach_hang',
                templateUrl: 'tpl/loyalty/tpl/user.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/loyalty/LoyUserCtrl.js');
                        }]
                }
            })
            .state('delivery.loyalty.add_user', {
                url: '/them-moi-khach-hang',
                templateUrl: 'tpl/loyalty/tpl/user_add.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angular-bootbox']).then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/loyalty/LoyUserCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('delivery.loyalty.config', {
                url: '/cau-hinh',
                templateUrl: 'tpl/loyalty/tpl/config.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable']).then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/loyalty/LoyConfigCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('delivery.loyalty.campaign', {
                url: '/doi-thuong',
                templateUrl: 'tpl/loyalty/tpl/campaign.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable']).then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/loyalty/LoyCampaignCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('delivery.loyalty.add_campaign', {
                url: '/them-moi-doi-thuong',
                templateUrl: 'tpl/loyalty/tpl/campaign_add.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/loyalty/LoyCampaignCtrl.js');
                        }]
                }
            })
            .state('delivery.loyalty.campaign_detail', {
                url: '/lich-su-doi-thuong',
                templateUrl: 'tpl/loyalty/tpl/campaign_detail.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable']).then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/loyalty/LoyCampaignDetailCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('delivery.loyalty.add_campaign_detail', {
                url: '/danh-sach-doi-thuong-chi-tiet?id',
                templateUrl: 'tpl/loyalty/tpl/add_campaign_detail.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/loyalty/LoyAddCampaignDetailCtrl.js');
                        }]
                }
            })
            .state('delivery.loyalty.history', {
                url: '/lich-su-thang-hang',
                templateUrl: 'tpl/loyalty/tpl/history.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/loyalty/LoyHistoryCtrl.js');
                        }]
                }
            })

            //Complain
            .state('delivery.complain', {
                abstract: true,
                url: '/complain',
                templateUrl: 'tpl/complain/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angular-bootbox');
                        }]
                }
            })
            .state('delivery.complain.indemnify', {
                url: '/indemnify',
                templateUrl: 'tpl/complain/indemnify/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/complain/IndemnifyCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('delivery.complain.indemnifylist', {
                url: '/indemnifylist?id',
                templateUrl: 'tpl/complain/indemnify/list.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/complain/IndemnifyActionCtrl.js');
                        }]
                }
            })
            .state('delivery.complain.indemnifyprocess', {
                url: '/indemnifyprocess?id',
                templateUrl: 'tpl/complain/indemnify/process.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/complain/IndemnifyProcessCtrl.js');
                        }]
                }
            })

            //marketing
            .state('delivery.marketing', {
                abstract: true,
                url: '/marketing',
                templateUrl: 'tpl/marketing/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angular-bootbox');
                        }]
                }
            })
            .state('delivery.marketing.user', {
                url: '/user',
                templateUrl: 'tpl/marketing/user/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/marketing/UserOrderCtrl.js');
                        }]
                }
            })
            .state('delivery.marketing.statistic', {
                url: '/statistic',
                templateUrl: 'tpl/marketing/user/statistic.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/marketing/StatisticCtrl.js');
                        }]
                }
            })


            // Return
            .state('delivery.return', {
                abstract: true,
                url: '/return',
                templateUrl: 'tpl/return/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angular-bootbox');
                        }]
                }
            })
            .state('delivery.return.waiting', {
                url: '/waiting?tracking_code',
                templateUrl: 'tpl/return/waiting/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/return/WaitingCtrl.js');
                        }]
                }
            })
            .state('delivery.return.returning', {
                url: '/returning?tracking_code',
                templateUrl: 'tpl/return/returning/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/return/ReturningCtrl.js');
                        }]
                }
            })
            .state('delivery.return.return', {
                url: '/return?tracking_code',
                templateUrl: 'tpl/return/return/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/return/ReturnCtrl.js');
                        }]
                }
            })

            // upload
            .state('delivery.upload', {
                abstract: true,
                url: '/upload',
                templateUrl: 'tpl/upload/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angular-bootbox');
                        }]
                }
            })
            .state('delivery.upload.journey', {
                url: '/journey',
                templateUrl: 'tpl/upload/journey/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/upload/JourneyCtrl.js');
                        }]
                }
            })
            .state('delivery.upload.upload_journey', {
                url: '/upload_journey?id',
                templateUrl: 'tpl/upload/journey/upload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/upload/UploadJourneyCtrl.js');
                                }
                            );
                        }
                    ]
                }
            })
            .state('delivery.upload.weight', {
                url: '/weight',
                templateUrl: 'tpl/upload/weight/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/upload/WeightCtrl.js');
                        }]
                }
            })
            .state('delivery.upload.upload_weight', {
                url: '/upload_weight?id',
                templateUrl: 'tpl/upload/weight/upload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/upload/UploadWeightCtrl.js');
                                }
                            );
                        }
                    ]
                }
            })
            .state('delivery.upload.process', {
                url: '/process',
                templateUrl: 'tpl/upload/process/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/upload/ProcessCtrl.js');
                        }]
                }
            })
            /// UPLOAD TRACKING CHO DON QUOC TE
            .state('delivery.upload.tracking', {
                url: '/tracking_global',
                templateUrl: 'tpl/upload/tracking/global.html',
                resolve: {
                	deps: ['$ocLazyLoad',
                           function( $ocLazyLoad){
                               return $ocLazyLoad.load('angularFileUpload').then(
                                   function(){
                                	   return $ocLazyLoad.load('js/controller/upload/TrackingGlobal.js');
                                   }
                               );
                           }
                       ]
                }
            })
            .state('delivery.upload.upload_tracking', {
                url: '/upload_tracking_global?id',
                templateUrl: 'tpl/upload/tracking/upload.html',
                resolve: {
                	deps: ['$ocLazyLoad',
                           function( $ocLazyLoad){
                               return $ocLazyLoad.load('angularFileUpload').then(
                                   function(){
                                	   return $ocLazyLoad.load('js/controller/upload/TrackingGlobal.js');
                                   }
                               );
                           }
                       ]
                    
                }
            })
            /// END UPLOAD TRACKING CHO DON QUOC TE
            .state('delivery.upload.upload_process', {
                url: '/upload_process?id',
                templateUrl: 'tpl/upload/process/upload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/upload/UploadProcessCtrl.js');
                                }
                            );
                        }
                    ]
                }
            })
            .state('delivery.upload.verify', {
                url: '/verify',
                templateUrl: 'tpl/upload/verify/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/upload/VerifyCtrl.js');
                        }]
                }
            })
            .state('delivery.upload.upload_verify', {
                url: '/upload_verify?id',
                templateUrl: 'tpl/upload/verify/upload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/upload/UploadVerifyCtrl.js');
                                }
                            );
                        }
                    ]
                }
            })
            .state('delivery.upload.estimate', {
                url: '/estimate',
                templateUrl: 'tpl/upload/estimate/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/upload/EstimateCtrl.js');
                        }]
                }
            })
            .state('delivery.upload.upload_estimate', {
                url: '/upload_estimate?id',
                templateUrl: 'tpl/upload/estimate/upload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/upload/UploadEstimateCtrl.js');
                                }
                            );
                        }
                    ]
                }
            })
            .state('delivery.upload.estimate_plus', {
                url: '/estimate_plus',
                templateUrl: 'tpl/upload/estimate_plus/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/upload/EstimatePlusCtrl.js');
                        }]
                }
            })
            .state('delivery.upload.upload_estimate_plus', {
                url: '/upload_estimate_plus?id',
                templateUrl: 'tpl/upload/estimate_plus/upload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/upload/UploadPlusEstimateCtrl.js');
                                }
                            );
                        }
                    ]
                }
            })
            //Courier
            // Pickup
            .state('delivery.courier', {
                //abstract: true,
                url: '/hang-van-chuyen',
                templateUrl: 'tpl/courier/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/courier/ConfigCtrl.js');
                        }]
                }
            })
            .state('delivery.courier.estimate', {
                //abstract: true,
                url: '/cam-ket-giao-hang',
                templateUrl: 'tpl/courier/estimate/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/courier/CourierEstimateCtrl.js');
                        }]
                }
            })
            //Merchant
            .state('delivery.merchant', {
                abstract: true,
                url: '/_merchant',
                templateUrl: 'tpl/merchant/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angular-bootbox','js/controller/config/ConfigCtrl.js']);
                        }]
                }
            })
            .state('delivery.merchant.list', {
                url: '/list?group_code',
                templateUrl: 'tpl/merchant/list/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load([

                                'js/services/MerchantService.js',
                                'js/controller/merchant/ListCtrl.js'
                            ]);
                        }]
                }
            })
            .state('delivery.merchant.vip_process', {
                url: '/vip-process',
                templateUrl: 'tpl/merchant/vip-process/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load([
                                'js/controller/merchant/VipProcessCtrl.js',
                                'js/controller/ticket/TicketCreateCtrl.js',
                                'angular-bootbox',
                                'angularFileUpload'
                            ]);
                        }]
                }
            })

            .state('delivery.merchant.vip_list', {
                url: '/vip-list',
                templateUrl: 'tpl/merchant/vip-list/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load([
                                'js/controller/merchant/ListVipCtrl.js',
                                'angular-bootbox',
                                'angularFileUpload',
                            ]);
                        }]
                }
            })

            .state('delivery.merchant.vip', {
                url: '/vip/:groupId',
                templateUrl: 'tpl/merchant/vip/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load([
                                'js/controller/merchant/VipCtrl.js',
                                'js/services/MerchantService.js',
                                'angular-bootbox',
                                'angularFileUpload',
                            ]);
                        }]
                }
            })

            /*
                Report
             */
            .state('report', {
                abstract: true,
                url: '/report',
                templateUrl: 'tpl/layout.html'
            })
            .state('report.sale', {
                //abstract: true,
                url: '/sale',
                templateUrl: 'tpl/report/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['js/controller/report/sale/ReportConfigCtrl.js']);
                        }]
                }
            })
            .state('report.sale.overview', {
                //abstract: true,
                url: '/overview',
                templateUrl: 'tpl/report/sale/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/report/sale/ReportOverViewCtrl.js');
                        }]
                }
            })
            .state('report.sale.employee', {
                //abstract: true,
                url: '/employee',
                templateUrl: 'tpl/report/sale/employee.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/report/sale/ReportEmployeeCtrl.js');
                        }]
                }
            })
            .state('report.sale.statistic', {
                //abstract: true1,
                url: '/statistic',
                templateUrl: 'tpl/report/sale/statistic.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','js/controller/report/sale/ReportStatisticCtrl.js']);
                        }]
                }
            })
            .state('report.sale.revenue_employee', {
                //abstract: true,
                url: '/employee-revenue',
                templateUrl: 'tpl/report/sale/revenue_employee.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/report/sale/ReportRevenueEmployeeCtrl.js');
                        }]
                }
            })
            .state('report.sale.customer_by_employee', {
                //abstract: true,
                url: '/customer-by-employee?email',
                templateUrl: 'tpl/report/sale/customer_by_employee.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/report/sale/ReportCustomerByEmployeeCtrl.js');
                        }]
                }
            })
            .state('report.sale.follow_up_customers', {
                //abstract: true,
                url: '/follow-up-customers',
                templateUrl: 'tpl/report/sale/follow_up_customers.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angular-bootbox','js/controller/report/sale/FollowUpCustomersCtrl.js']);
                        }]
                }
            })
            .state('report.cs', {
                //abstract: true,
                url: '/cs',
                templateUrl: 'tpl/report/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['js/controller/report/cs/ReportConfigCtrl.js']);
                        }]
                }
            })
            .state('report.cs.statistic', {
                //abstract: true1,
                url: '/statistic',
                templateUrl: 'tpl/report/cs/statistic.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable','js/controller/report/cs/ReportStatisticCtrl.js']);
                        }]
                }
            })
            .state('report.cs.upload', {
                //abstract: true,
                url: '/upload',
                templateUrl: 'tpl/report/cs/upload.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angularFileUpload','js/controller/report/cs/CSUploadCtrl.js']);
                        }]
                }
            })

            /*
                Boxme
             */
            .state('warehouse', {
                abstract: true,
                url: '/kho-hang',
                templateUrl: 'tpl/layout.html'
            })
            .state('warehouse.search', {
                //abstract: true,
                url: '/tim-kiem?keyword',
                templateUrl: 'tpl/warehouse_search.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable', 'js/WareHouseSearchCtrl.js']);
                        }]
                }
            })
            .state('warehouse.inbound', {
                //abstract: true,
                url: '/nhap-kho',
                templateUrl: 'tpl/warehouse_inbound/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHouseInBoundConfigCtrl.js');
                        }]
                }
            })
            .state('warehouse.inbound.shipment', {
                //abstract: true,
                url: '/danh-sach-shipment',
                templateUrl: 'tpl/warehouse_inbound/shipment.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHouseShipmentCtrl.js');
                        }]
                }
            })
            .state('warehouse.outbound', {
                //abstract: true,
                url: '/xuat-kho',
                templateUrl: 'tpl/warehouse_outbound/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHouseOutBoundConfigCtrl.js');
                        }]
                }
            })
            .state('warehouse.outbound.packed', {
                //abstract: true,
                url: '/danh-sach-dong-goi',
                templateUrl: 'tpl/warehouse_outbound/packed.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHousePackedCtrl.js');
                        }]
                }
            })
            .state('warehouse.outbound.returned', {
                //abstract: true,
                url: '/danh-sach-hoan-hang',
                templateUrl: 'tpl/warehouse_outbound/return.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHouseReturnCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem', {
                //abstract: true,
                url: '/can-xu-ly',
                templateUrl: 'tpl/warehouse_problem/app.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHouseProblemConfigCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem.return', {
                //abstract: true,
                url: '/hoan-cham',
                templateUrl: 'tpl/warehouse_problem/return/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHouseReturnSlowCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem.package', {
                //abstract: true,
                url: '/dong-goi-cham',
                templateUrl: 'tpl/warehouse_problem/package/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHousePackageCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem.package_nt', {
                //abstract: true,
                url: '/dong-goi-cham-noi-thanh',
                templateUrl: 'tpl/warehouse_problem/package/package_nt.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHousePackageNTCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem.package_error', {
                //abstract: true,
                url: '/dong-goi-sai-kich-thuoc',
                templateUrl: 'tpl/warehouse_problem/package/error_size.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHousePackageErrorCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem.pickup', {
                //abstract: true,
                url: '/lay-hang-cham',
                templateUrl: 'tpl/warehouse_problem/pickup/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHousePickupCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem.pickup_nt', {
                //abstract: true,
                url: '/lay-hang-cham-noi-thanh',
                templateUrl: 'tpl/warehouse_problem/pickup/pickup_nt.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHousePickupNTCtrl.js');
                        }]
                }
            })
            .state('warehouse.problem.shipmment_lost', {
                //abstract: true,
                url: '/nhap-kho-thieu',
                templateUrl: 'tpl/warehouse_problem/shipment/lost.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WareHouseShipmentLostCtrl.js');
                        }]
                }
            })
            // Delivery
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
            .state('access.404', {
                url: '/404',
                templateUrl: 'tpl/page_404.html'
            })

    }
  ]
);