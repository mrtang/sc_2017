'use strict';
angular.module('app').config(
    ['$stateProvider', '$urlRouterProvider', '$controllerProvider', '$compileProvider', '$filterProvider', '$provide',
        function ($stateProvider, $urlRouterProvider, $controllerProvider, $compileProvider, $filterProvider, $provide) {

            // lazy controller, directive and service
            app.controller = $controllerProvider.register;
            app.directive = $compileProvider.directive;
            app.filter = $filterProvider.register;
            app.factory = $provide.factory;
            app.service = $provide.service;
            app.constant = $provide.constant;
            app.value = $provide.value;


            $urlRouterProvider
                .otherwise('/ticket/request/management/7/');
            $stateProvider
            /**
             * Ticket
             **/
                .state('ticket', {
                    abstract: true,
                    url: '/ticket',
                    templateUrl: 'tpl/app_ticket.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['angularFileUpload', 'xeditable', 'angular-bootbox']).then(
                                    function () {
                                        return $ocLazyLoad.load([
                                            'js/controller/ticket/TicketCreateCtrl.js',
                                            'js/controller/ticket/ReplyTemplateCtrl.js',
                                        ]);
                                    }
                                );
                            }]
                    }
                })
                .state('ticket.request', {
                    url: '/request',
                    templateUrl: 'tpl/ticket/setting_layout.html'
                })
                .state('ticket.request.management', {
                    url: '/management/{time_start}/{id}',
                    templateUrl: 'tpl/ticket/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('angularFileUpload').then(
                                    function () {
                                        return $ocLazyLoad.load('js/controller/ticket/TicketCtrl.js');
                                    }
                                );
                            }]
                    }
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

                //report
                .state('report', {
                    abstract: true,
                    url: '/report',
                    templateUrl: 'tpl/app_report.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'js/controller/ticket/TicketCreateCtrl.js',
                                    'js/controller/ticket/ReplyTemplateCtrl.js'
                                ]);
                            }]
                    }
                })

                .state('courier', {
                    abstract: true,
                    url: '/courier',
                    templateUrl: 'tpl/app_courier.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'js/controller/ticket/TicketCreateCtrl.js',
                                    'js/controller/ticket/ReplyTemplateCtrl.js'
                                ]);
                            }]
                    }
                })


                .state('courier.dashbroad', {
                    /*abstract: true,*/
                    url: '/dashbroad',
                    templateUrl: 'tpl/courier/dashbroad.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/courier/DashbroadCtrl.js'
                                ]);
                            }]
                    }
                })

                .state('courier.redelivery', {
                    /*abstract: true,*/
                    url: '/redelivery/:city/:district',
                    templateUrl: 'tpl/courier/redelivery.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/courier/RedeliveryCtrl.js'
                                ]);
                            }]
                    }
                })
                .state('courier.slowdelivery', {
                    /*abstract: true,*/
                    url: '/slowdelivery/:city/:district',
                    templateUrl: 'tpl/courier/slowdelivery.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/courier/SlowDeliveryCtrl.js'
                                ]);
                            }]
                    }
                })
                .state('courier.slowpickup', {
                    /*abstract: true,*/
                    url: '/slowpickup/:city/:district',
                    templateUrl: 'tpl/courier/slowpickup.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/courier/SlowPickupCtrl.js'
                                ]);
                            }]
                    }
                })
                .state('courier.return', {
                    /*abstract: true,*/
                    url: '/return/:city/:district',
                    templateUrl: 'tpl/courier/return.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/courier/ReturnCtrl.js'
                                ]);
                            }]
                    }
                })


                .state('manage', {
                    abstract: true,
                    url: '/manage',
                    templateUrl: 'tpl/app_manage.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'js/controller/ticket/TicketCreateCtrl.js',
                                    'js/controller/ticket/ReplyTemplateCtrl.js'
                                ]);
                            }]
                    }
                })

                .state('manage.user-dashbroad', {
                    /*abstract: true,*/
                    url: '/ban-lam-viec',
                    templateUrl: 'tpl/manage/user-dashbroad.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/manage/UserDashbroadCtrl.js'
                                ]);
                            }]
                    }
                })

                .state('manage.request-extend', {
                    /*abstract: true,*/
                    url: '/request-extend',
                    templateUrl: 'tpl/manage/request-extend-time.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/manage/RequestExtendTimeCtrl.js'
                                ]);
                            }]
                    }
                })

                .state('manage.ticket-rating', {
                    /*abstract: true,*/
                    url: '/ticket-rating',
                    templateUrl: 'tpl/manage/ticket-rating.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angularFileUpload',
                                    'xeditable',
                                    'angular-bootbox',
                                    'js/controller/manage/TicketRatingCtrl.js'
                                ]);
                            }]
                    }
                })


                .state('report.insight', {
                    url: '/insight',
                    templateUrl: 'tpl/report/insight.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('js/controller/report/ReportInsightCtrl.js');
                            }]
                    }
                })

                .state('report.employee', {
                    url: '/employee',
                    templateUrl: 'tpl/report/employee.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('js/controller/report/EmployeesCtrl.js');
                            }]
                    }
                })

                .state('report.create-ticket', {
                    url: '/create-ticket',
                    templateUrl: 'tpl/report/create.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('js/controller/report/ReportCreateTicketCtrl.js');
                            }]
                    }
                })

                .state('report.cases', {
                    url: '/cases',
                    templateUrl: 'tpl/report/cases.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('js/controller/report/ReportCasesCtrl.js');
                            }]
                    }
                })

                .state('report.caseType', {
                    url: '/case-type',
                    templateUrl: 'tpl/report/case-type.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('js/controller/report/ReportCaseTypeCtrl.js');
                            }]
                    }
                })

                .state('upload', {
                    abstract: true,
                    url: '/upload',
                    templateUrl: 'tpl/courier/upload/app.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'angular-bootbox',
                                    'angularFileUpload',
                                    'js/controller/ticket/TicketCreateCtrl.js',
                                    'js/controller/ticket/ReplyTemplateCtrl.js'

                                ]);
                            }]
                    }
                })
                .state('upload.process', {
                    url: '/process',
                    templateUrl: 'tpl/courier/upload/process/index.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('js/controller/courier/upload/ProcessCtrl.js');
                            }]
                    }
                })

                .state('upload.upload_process', {
                    url: '/upload_process?id',
                    templateUrl: 'tpl/courier/upload/process/upload.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('angularFileUpload').then(
                                    function () {
                                        return $ocLazyLoad.load('js/controller/courier/upload/UploadProcessCtrl.js');
                                    }
                                );
                            }
                        ]
                    }
                })


        }
    ]
);