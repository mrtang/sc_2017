'use strict';
moment.locale('vi');
/*var ApiPath     = '/api/public/api/v1/';*/
var ApiUrl      = '/#/';
var SellerUrl   = 'http://seller.shipchung.vn/#/';
var ApiPath     = '/api/public/api/v1/';
var ApiJourney     = '/api/public/trigger/journey/';
var ApiOms      = '/api/public/api/oms/';

var ApiBase     = '/api/public/';

/*var ApiJourney  = '/api/public/trigger/journey/';
*/
var ApiStorage  = 'http://cloud.shipchung.vn/';
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
    'markdown',
    'ngTagsInput',
    'toaster',
    'ngClipboard',
    'angularCharts',
    'mentio',
    'cfp.hotkeys',
    'ngBootstrap',
    'highcharts-ng',
    'ui.bootstrap.datetimepicker'
])
.factory('sessionService', ['$http', '$localStorage', '$window', function($http, $localStorage, $window){
	return{
        set:function(data){
            var newDate = new Date();
            $localStorage.login          = data;
            $localStorage.time_login     = Date.parse(new Date());
        },
		destroy:function(){
            $localStorage.$reset();
            return $http.get(ApiPath+'app/checkout');
		}
	};
}])
.factory('loginService',function($http, $location, sessionService, $localStorage, $window){
	return{
		login:function(data,scope,state){
			$http({
                url: ApiPath+'app/checkin',
                method: "POST",
                data: data,
                dataType: 'json'
/*                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
*/            }).success(function (result, status, headers, config) {
                if(result.code == 'success'){
                    if(result['data']['privilege'] < 1){
                        scope.authError = 'Tài khoản không được cấp quyền truy cập !';
                    }else{

                        if((new Date().getTime() / 1000) - result['data']['time_change_password'] > (86400* 30)){
                            scope.authError = 'Bạn đã khá lâu rồi không đổi mật khẩu, để đảm bảo an toàn khi sử dụng vui lòng đăng nhập seller.shipchung.vn để đổi mật khầu mới.';
                            //alert('Bạn đã khá lâu rồi không đổi mật khẩu, để đảm bảo an toàn khi sử dụng vui lòng đăng nhập seller.shipchung.vn để đổi mật khầu mới.');
                            setTimeout(function (){
                                window.location = 'http://seller.shipchung.vn/#/app/config/account';
                            }, 4000)
                            
                            return;
                        } 

                        sessionService.set(result['data']);
                        $http.defaults.headers.common['Authorization']  = result['data']['token'];


                        state.go('manage.user-dashbroad');
                    }
                }
                else{
                    scope.authError = 'Email hoặc Password không dúng !';
                }
                scope.onProgress = false;
            }).error(function (data, status, headers, config) {
                scope.authError = 'Lỗi hệ thống, hãy thử lại';
                scope.onProgress = false;
            });

		},
        register:function(data,scope,state){
            $http({
                url: ApiPath+'app/register',
                method: "POST",
                data: data,
                dataType: 'json'
/*                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
*/            }).success(function (result, status, headers, config) {
                if(result.code == 'success'){
                    sessionService.set(result['data']);
                    state.go('ticket.request.management',{time_start:'7day', id: ''});
                }
                else{
                    if('email' in  result.message){
                        scope.authError = 'Email đã tồn tại';
                    }else if('fullname' in result.message){
                        scope.authError = 'Hãy nhập họ tên đầy đủ !';
                    }else if('password' in result.message){
                        scope.authError = 'Hãy nhập mật khẩu !';
                    }else if('confirm_password' in result.message){
                        scope.authError = 'Hãy nhập mật khẩu xác nhận !';
                    }else if('insert' in result.message){
                        scope.authError = 'Lỗi , hãy thử lại !';
                    }
                }
                scope.onProgress = false;
            }).error(function (data, status, headers, config) {
                scope.authError = 'Lỗi hệ thống, hãy thử lại';
                scope.onProgress = false;
            });
        },
        loginfb : function(scope,state){
            $http({
                url: ApiPath+'app/loginfb',
                method: "GET",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
                if(result.data){
                    sessionService.set(result['data']);
                    state.go('ticket.request.management',{time_start:'7day', id: ''});
                }else{
                    $window.location.href = result.url;
                }
            }).error(function (data, status, headers, config) {
                scope.authError = 'Lỗi hệ thống, hãy thử lại';
            });
            return;
        },
		logout:function(){
			sessionService.destroy();
			$location.path('/access/signin');
		},
		islogged:function(scope){
            return $localStorage.login;
		},
        gettimelogged:function(scope){
            return $localStorage.time_login;
        }
	}

})
.run(
  [ '$rootScope', '$state', '$stateParams', '$location', 'loginService', 'sessionService', '$localStorage', '$http', '$filter', 
  function ($rootScope, $state, $stateParams, $location, loginService, sessionService, $localStorage, $http, $filter) {

		$state.userInfo = {'avatar':'img/a0.jpg'};
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;

        var routespermission = ['/access/signup','/access/signin'];  //route that require login
        $rootScope.$on('$stateChangeStart', function(evt,toState){
        $rootScope.userInfo                             = loginService.islogged();

        if($rootScope.userInfo != undefined){
            $http.defaults.headers.common['Authorization']  = $rootScope.userInfo.token;
        }
        
        /*if($localStorage.call_online == true &&  (typeof ua == 'undefined')){
            if($rootScope.userInfo && $rootScope.userInfo.sip_account && $rootScope.userInfo.sip_pwd){
                CALLMAN.login($rootScope.userInfo.sip_account, $rootScope.userInfo.sip_pwd);
            }
        }*/

        $rootScope.badWords = function (text){
            var newText = $filter('badWords')(text);
            return newText;
        }


          if (!toState.name.match(/^access/g))
          {
              if($rootScope.userInfo < 1 || !loginService.gettimelogged() || (+Date.parse(new Date()) - loginService.gettimelogged() > 7200000) ){
                  evt.preventDefault();
                  sessionService.destroy();
                  $state.go('access.signin');
              }
          }else{
              if(loginService.gettimelogged() && (+Date.parse(new Date()) - loginService.gettimelogged() < 7200000)){
                  evt.preventDefault();
                  $state.go('ticket.request.management',{time_start:'7day', id: ''});
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
                name: 'toaster',
                files: [
                    'js/modules/toaster/toaster.js',
                    'js/modules/toaster/toaster.css'
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
            },
            {
                name: 'angularFileUpload',
                files: [
                    'js/modules/angular-file-upload.js'
                ]
            }
        ]
    });
}]).config(function(markdownConfig){
    markdownConfig.sanitize = false;
    markdownConfig.outline  = true;
})
.config([
    '$compileProvider',
    function( $compileProvider )
    {
        $compileProvider.aHrefSanitizationWhitelist (/^\s*(https?|ftp|mailto|file|tel|chrome-extension):/);
        $compileProvider.imgSrcSanitizationWhitelist (/^\s*(https?|ftp|mailto|file|tel|chrome-extension):/);
        // Angular before v1.2 uses $compileProvider.urlSanitizationWhitelist(...)
    }
])
.config(['ngClipProvider', function(ngClipProvider) {
    ngClipProvider.setPath("js/modules/ng-clip/ZeroClipboard.swf");
}]);
