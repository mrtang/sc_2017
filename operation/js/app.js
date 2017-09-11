'use strict';

// Declare app level module which depends on filters, and services
var app = angular.module('app', [
    'ngAnimate',
    'ngCookies',
    'ngStorage',
    'ui.router',
    'ui.bootstrap',
    'ui.load',
    'ui.jq',
    'ui.validate',
    //'ui.codemirror',
    'oc.lazyLoad',
    'pascalprecht.translate',
    'app.filters',
    'app.services',
    'app.directives',
    'app.controllers',
    'ui.bootstrap.datetimepicker',
    'ngTagsInput',
    'toaster'
  ])
.factory('sessionService', ['$http', '$window', '$localStorage', function($http, $window, $localStorage){
    return{
        set:function(data){
            var newDate = new Date();
            $localStorage['login']          = data;
            $localStorage['time_login']     = Date.parse(new Date());
        },
        destroy:function(){
            $localStorage.$reset();
            return $http.get(ApiPath+'app/checkout');
        }
    };
}])
.factory('loginService',function($rootScope, $http, $location, sessionService, $localStorage, $window){
    return{
        login:function(data,scope,state){
            $http({
                url: ApiPath+'app/checkin',
                method: "POST",
                data: data,
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
                if(result.code == 'success'){
                    if(result['data']['privilege'] < 1){
                        scope.authError = 'Tài khoản không được quyền truy cập !';
                    }else{
                        sessionService.set(result['data']);
                        $http.defaults.headers.common['Authorization']  = result['data']['token'];
                        scope.setCountry(result['data']['country_id']);
                        state.go('delivery.dashboard');
                    }
                }
                else{
                    scope.authError = 'Email hoặc mật khẩu không đúng !';
                }
                scope.onProgress = false;
            }).error(function (data, status, headers, config) {
                scope.authError = 'Lỗi hệ thống, hãy thử lại';
                scope.onProgress = false;
            });

        },
        logout:function(){
            sessionService.destroy();
            $location.path('/access/signin');
        },
        islogged:function(scope){
            return $localStorage['login'];
        },
        gettimelogged:function(scope){
            return $localStorage['time_login'];
        },
        check_privilege:function(code, action){
            if($rootScope == undefined || $rootScope.userInfo == undefined){
                return false;
            }

            if($rootScope.userInfo != undefined && ($rootScope.userInfo.privilege == 2 || ($rootScope.userInfo.group_privilege[code] && $rootScope.userInfo.group_privilege[code][action] == 1))){
                return true;
            }else{
                return false;
            }
        }
    }

})
.run(
  [ '$rootScope', '$state', '$http', '$stateParams', '$location', 'loginService', 'sessionService', 'Config',
  function ($rootScope, $state, $http, $stateParams, $location, loginService, sessionService, Config) {
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;
        $rootScope.userInfo     = loginService.islogged();

        var routespermission = ['/access/signin','/access/signup'];  //route that require login
        var router_privilege = Config.router_privilege;

        $rootScope.$on('$stateChangeStart', function(evt,toState){
            $rootScope.userInfo     = loginService.islogged();

            if($rootScope.userInfo != undefined){
                $http.defaults.headers.common['Authorization']      = $rootScope.userInfo.token;
                $http.defaults.headers.common['ULocation']           = $rootScope.userInfo.country_id;
            }

            if (!toState.name.match(/^access/g))
            {
                if( $rootScope.userInfo < 1 || !loginService.gettimelogged() || (+Date.parse(new Date()) - loginService.gettimelogged() > 43200000)){
                    evt.preventDefault();
                    sessionService.destroy();
                    $state.go('access.signin');
                }else if(!toState.name.match(/^dashboard/g))
                {
                    if((router_privilege[toState['name']] != undefined) && (!loginService.check_privilege(router_privilege[toState['name']],'view'))){
                      evt.preventDefault();
                      $state.go('delivery.dashboard');
                    }
                }
            }else{
                if(loginService.gettimelogged() && (+Date.parse(new Date()) - loginService.gettimelogged() < 43200000)){
                    evt.preventDefault();
                    $state.go('delivery.dashboard');
                }
            }
        });    
    }]
)
// translate config
.config(['$translateProvider', function($translateProvider){

  // Register a loader for the static files
  // So, the module will search missing translation tables under the specified urls.
  // Those urls are [prefix][langKey][suffix].
  $translateProvider.useStaticFilesLoader({
    prefix: 'l10n/',
    suffix: '.json'
  });

  // Tell the module what language to use by default
  $translateProvider.preferredLanguage('en');

  // Tell the module to store the language in the local storage
  $translateProvider.useLocalStorage();

}])
/**
 * jQuery plugin config use ui-jq directive , config the js and css files that required
 * key: function name of the jQuery plugin
 * value: array of the css js file located
 */
.constant('JQ_CONFIG', {
    easyPieChart:   ['js/jquery/charts/easypiechart/jquery.easy-pie-chart.js'],
    sparkline:      ['js/jquery/charts/sparkline/jquery.sparkline.min.js'],
    plot:           ['js/jquery/charts/flot/jquery.flot.min.js', 
                        'js/jquery/charts/flot/jquery.flot.resize.js',
                        'js/jquery/charts/flot/jquery.flot.tooltip.min.js',
                        'js/jquery/charts/flot/jquery.flot.spline.js',
                        'js/jquery/charts/flot/jquery.flot.orderBars.js',
                        'js/jquery/charts/flot/jquery.flot.pie.min.js'],
    slimScroll:     ['js/jquery/slimscroll/jquery.slimscroll.min.js'],
    sortable:       ['js/jquery/sortable/jquery.sortable.js'],
    nestable:       ['js/jquery/nestable/jquery.nestable.js',
                        'js/jquery/nestable/nestable.css'],
    filestyle:      ['js/jquery/file/bootstrap-filestyle.min.js'],
    slider:         ['js/jquery/slider/bootstrap-slider.js',
                        'js/jquery/slider/slider.css'],
    chosen:         ['js/jquery/chosen/chosen.jquery.min.js',
                        'js/jquery/chosen/chosen.css'],
    TouchSpin:      ['js/jquery/spinner/jquery.bootstrap-touchspin.min.js',
                        'js/jquery/spinner/jquery.bootstrap-touchspin.css'],
    wysiwyg:        ['js/jquery/wysiwyg/bootstrap-wysiwyg.js',
                        'js/jquery/wysiwyg/jquery.hotkeys.js'],
    dataTable:      ['js/jquery/datatables/jquery.dataTables.min.js',
                        'js/jquery/datatables/dataTables.bootstrap.js',
                        'js/jquery/datatables/dataTables.bootstrap.css'],
    vectorMap:      ['js/jquery/jvectormap/jquery-jvectormap.min.js', 
                        'js/jquery/jvectormap/jquery-jvectormap-world-mill-en.js',
                        'js/jquery/jvectormap/jquery-jvectormap-us-aea-en.js',
                        'js/jquery/jvectormap/jquery-jvectormap.css'],
    footable:       ['js/jquery/footable/footable.all.min.js',
                        'js/jquery/footable/footable.core.css']
    }
)

// modules config
.constant('MODULE_CONFIG', {
    select2:        ['js/jquery/select2/select2.css',
                        'js/jquery/select2/select2-bootstrap.css',
                        'js/jquery/select2/select2.min.js',
                        'js/modules/ui-select2.js']
    }
)

// oclazyload config
.config(['$ocLazyLoadProvider', function($ocLazyLoadProvider) {
    // We configure ocLazyLoad to use the lib script.js as the async loader
    $ocLazyLoadProvider.config({
        debug   : false,
        events  : true,
        cache   : false,
        timeout : 5000,
        modules: [
            {
                name: 'ngGrid',
                files: [
                    'js/modules/ng-grid/ng-grid.min.js',
                    'js/modules/ng-grid/ng-grid.css',
                    'js/modules/ng-grid/theme.css'
                ]
            },
            {
                name: 'xeditable',
                files: [                    
                    'js/jquery/editable/xeditable.js',
                    'js/jquery/editable/xeditable.css'
                ]
            },
            {
                name: 'angular-bootbox',
                files: [
                    'js/modules/bootstrap.js',
                    'js/modules/bootbox.js',
                    'js/modules/angular-bootbox.js'
                ]
            }
            ,
            {
                name: 'angularFileUpload',
                files: [
                    'js/modules/angular-file-upload.js'
                ]
            }
        ]
    });
}]);