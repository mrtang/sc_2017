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
            .state('app.template', {
                url: '/template',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.template.add', {
                url: '/add',
                templateUrl: 'tpl/template/action.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/template/TemplateActionCtrl.js');
                            }
                        );
                    }]
                }
            })     
            .state('app.template.edit', {
                url: '/edit/:id',
                templateUrl: 'tpl/template/action.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/template/TemplateActionCtrl.js');
                            }
                        );
                    }]
                }
            })                
            .state('app.template.list', {
                url: '/list',
                templateUrl: 'tpl/template/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/template/TemplateCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.scenario', {
                url: '/scenario',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
            })
            .state('app.scenario.add', {
                url: '/add',
                templateUrl: 'tpl/scenario/action.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/scenario/ScenarioActionCtrl.js');
                            }
                        );
                    }]
                }
            })     
            .state('app.scenario.edit', {
                url: '/edit/:id',
                templateUrl: 'tpl/scenario/action.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                                return $ocLazyLoad.load('js/controller/scenario/ScenarioActionCtrl.js');
                            }
                        );
                    }]
                }
            })                
            .state('app.scenario.list', {
                url: '/list',
                templateUrl: 'tpl/scenario/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/scenario/ScenarioCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.scenario.config', {
                url: '/config',
                templateUrl: 'tpl/scenario/config.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/scenario/ScenarioTemplateCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.scenario.config_add', {
                url: '/config_add/:id',
                templateUrl: 'tpl/scenario/config_add.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/scenario/ScenarioTemplateActionCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.system-config', {
                url: '/system-config/:id',
                templateUrl: 'tpl/system/sys_config.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/system/SystemScenarioConfigCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.system', {
                url: '/system',
                templateUrl: 'tpl/system/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/system/SystemScenarioCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.user', {
                url: '/user',
                templateUrl: 'tpl/user/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/user/UserCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.user-transport', {
                url: '/user-transport/:id',
                templateUrl: 'tpl/user/transport.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/user/UserTransportCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.user-scenario', {
                url: '/user-scenario/:id',
                templateUrl: 'tpl/user/scenario.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/user/UserScenarioCtrl.js');
                            }
                        );
                    }]
                }
            })
            .state('app.queue', {
                url: '/queue',
                templateUrl: 'tpl/queue/index.html',
                resolve: {
                    deps: ['$ocLazyLoad',
                      function( $ocLazyLoad){
                        return $ocLazyLoad.load(['toaster','xeditable','angular-bootbox']).then(
                            function(){
                               return $ocLazyLoad.load('js/controller/queue/QueueCtrl.js');
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