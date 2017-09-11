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
                url: '/bao-cao',
                templateUrl: 'tpl/app.html'
            })
            .state('app.report', {
                url: '/report?id&email',
                templateUrl: 'tpl/report/merchant.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/report/RMerchantCtrl.js');
                        }]
                }
            })
             // provider report
            .state('app.provider', {
                url: '/hang-van-chuyen',
                templateUrl: 'tpl/provider/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                                return $ocLazyLoad.load('js/controller/provider/ProviderReportCtrl.js');
                    }]
                }
            })
            // lading report
            .state('app.order', {
                url: '/van-don',
                templateUrl: 'tpl/order/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                                return $ocLazyLoad.load('js/controller/order/OrderReportCtrl.js');
                    }]
                }
            })
            // balance report
            .state('app.balance', {
                abstract        : true,
                url             : '/so-du',
                templateUrl     : 'tpl/balance/setting_layout.html'
            })
            .state('app.balance.report', {
                url: '/khach-hang',
                templateUrl: 'tpl/balance/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['xeditable']).then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/balance/BalanceReportCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('app.balance.audit', {
                url: '/lich-su',
                templateUrl: 'tpl/balance/audit.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/balance/AuditCtrl.js');
                        }]
                }
            })
            .state('app.balance.create_audit', {
                url: '/doi-soat-so-du',
                templateUrl: 'tpl/balance/create_audit.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/balance/CreateAuditCtrl.js');
                        }]
                }
            })
            .state('app.balance.cash_in', {
                url: '/nap-tien',
                templateUrl: 'tpl/balance/cash_in.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/balance/CashInCtrl.js');
                        }]
                }
            })
            .state('app.balance.cash_out', {
                url: '/rut-tien',
                templateUrl: 'tpl/balance/cash_out.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/balance/CashOutCtrl.js');
                        }]
                }
            })
            // merchant report
            .state('app.report_orders', {
                url: '/bao-cao-don-hang',
                templateUrl: 'tpl/report/order.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/report/ReportOrderCtrl.js');
                        }]
                }
            })
            // order report
            .state('app.report_merchants', {
                url: '/khach-hang',
                templateUrl: 'tpl/report/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/report/ReportCtrl.js');
                        }]
                }
            })
            .state('app.merchants', {
                url: '/merchants',
                templateUrl: 'tpl/merchants/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/merchants/MerchantsReportCtrl.js');
                        }]
                }
            })// Thanh toán chuyển tiền
            .state('app.cash_out', {
                url: '/chuyen-tien?id',
                templateUrl: 'tpl/cash_out/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angularFileUpload']).then(
                                function() {
                                    return $ocLazyLoad.load('js/controller/cash_out/CashOutCtrl.js');
                                }
                            );
                        }]
                }
            }).state('app.refund', {
                url: '/hoan-tien?id',
                templateUrl: 'tpl/refund/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angularFileUpload']).then(
                                function() {
                                    return $ocLazyLoad.load('js/controller/refund/RefundCtrl.js');
                                }
                            );
                        }]
                }
            }).state('app.recover', {
                url: '/thu-hoi?id',
                templateUrl: 'tpl/recover/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angularFileUpload']).then(
                                function() {
                                    return $ocLazyLoad.load('js/controller/recover/RecoverCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('app.transaction', { // transaction
                url: '/lich-su-giao-dich',
                templateUrl: 'tpl/transaction/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/transaction/TransactionCtrl.js');
                        }]
                }
            })
            .state('app.wmstype', { // wmstype
                url: '/lich-su-luu-kho',
                templateUrl: 'tpl/wmstype/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/wmstype/WmsTypeCtrl.js');
                        }]
                }
            })
            // bản kê thanh toán
            .state('app.payment', {
                url: '/bang-ke',
                templateUrl: 'tpl/payment/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/payment/PaymentCtrl.js');
                        }]
                }
            })
            // Hóa đơn
            .state('app.invoice', {
                url: '/hoa-don',
                templateUrl: 'tpl/invoice/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/invoice/InvoiceCtrl.js');
                        }]
                }
            })
            .state('app.payment_detail', {
                url: '/bang-ke-chi-tiet?id&time_start',
                templateUrl: 'tpl/payment/detail.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/payment/DetailCtrl.js');
                        }]
                }
            })
            .state('app.payment_freeze', {
                url: '/bang-ke-tam-giu?id',
                templateUrl: 'tpl/payment/freeze.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/payment/FreezeCtrl.js');
                        }]
                }
            })
            .state('app.payment_verify', {
                url: '/doi-soat?id',
                templateUrl: 'tpl/payment/verify.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load(['angularFileUpload']).then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/payment/VerifyCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('print', {
                url: '/in-hoa-don?id',
                templateUrl: 'tpl/invoice.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/invoice/PrintCtrl.js');
                        }]
                }
            })

            /**
             * Kho hàng
             */
            .state('app.warehouse', {
                abstract        : true,
                url             : '/kho-hang',
                templateUrl     : 'tpl/warehouse/setting_layout.html'
            })
            .state('app.warehouse.warehouse_fee', {
                url: '/khoang-ke-theo-ngay',
                templateUrl: 'tpl/warehouse/temporary.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/WHTemporaryCtrl.js');
                        }]
                }
            })
            .state('app.warehouse.verify_warehouse', {
                url: '/doi-soat-khoang-ke',
                templateUrl: 'tpl/warehouse/verify_warehouse.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/VerifyWareHouseCtrl.js');
                        }]
                }
            })
            .state('app.warehouse.verify_partner', {
                url: '/doi-soat-doi-tac',
                templateUrl: 'tpl/warehouse/partner_verify.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('js/controller/warehouse/PartnerVerifyCtrl.js');
                        }]
                }
            })

            /**
             * Đối soát
             */
            .state('app.verify', {
                abstract        : true,
                url             : '/doi-soat',
                templateUrl     : 'tpl/verify/setting_layout.html'
            })
            .state('app.verify.money_collect', {
                url: '/thu-ho',
                templateUrl: 'tpl/verify/money_collect.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/verify/MoneyCollectCtrl.js');
                        }]
                }
            })
            .state('app.verify.create_verify', {
                url: '/create_verify',
                templateUrl: 'tpl/verify/create_verify.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/verify/CreateVerifyCtrl.js');
                        }]
                }
            })
            .state('app.verify.upload_money_collect', {
                url: '/cap-nhat-thu-ho/{code}',
                templateUrl: 'tpl/verify/upload_money_collect.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/verify/UploadMoneyCollectCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('app.verify.fee', {
                url: '/phi',
                templateUrl: 'tpl/verify/fee.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/verify/FeeCtrl.js');
                        }]
                }
            })
            .state('app.verify.upload_fee', {
                url: '/cap-nhat-phi/{code}',
                templateUrl: 'tpl/verify/upload_fee.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/verify/UploadFeeCtrl.js');
                                }
                            );
                        }]
                }
            })
            .state('app.verify.service', {
                url: '/dich-vu',
                templateUrl: 'tpl/verify/service.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                                    return $ocLazyLoad.load('js/controller/verify/ServiceCtrl.js');
                        }]
                }
            })
            .state('app.verify.upload_service', {
                url: '/cap-nhat-dich-vu/{code}',
                templateUrl: 'tpl/verify/upload_service.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                        function( $ocLazyLoad){
                            return $ocLazyLoad.load('angularFileUpload').then(
                                function(){
                                    return $ocLazyLoad.load('js/controller/verify/UploadServiceCtrl.js');
                                }
                            );
                        }]
                }
            })
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