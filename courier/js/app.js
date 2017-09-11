'use strict';
//var ApiPath = 'http://localhost:8000/api/v1/';
//var ApiPath = 'http://localhost/boxme/api/public/api/v1/';
var ApiRest = '/api/public/api/rest/';
var ApiPath = '/api/public/api/v1/';
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
    'oc.lazyLoad',
    'pascalprecht.translate',
    'app.filters',
    'app.services',
    'app.directives',
    'app.controllers'
  ])
.factory('sessionService', ['$http', function($http){
	return{
		set:function(key,value){
			return sessionStorage.setItem(key,value);
		},
		get:function(key){
			return sessionStorage.getItem(key);
		},
		destroy:function(key){
			$http.get(ApiPath+'app/checkout');
			return sessionStorage.removeItem(key);
		}
	};
}])
.factory('loginService',function($http, $location, sessionService){
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
                    $http.defaults.headers.common['Authorization']  = result.data.token;
					sessionService.set('mrkien',JSON.stringify(result.data));
                    state.go('app.dashboard');
				}	       
				else{
					scope.authError = 'Email or Password not right';
				}
            }).error(function (data, status, headers, config) {
                scope.authError = 'Server Error ' + status;
            });
		},
		logout:function(){
			sessionService.destroy('mrkien');
			$location.path('/access/signin');
		},
		islogged:function(scope){
            return $http.jsonp(ApiPath+'app/checkexist?callback=JSON_CALLBACK');
		}
	}

})
.run(
  [ '$rootScope', '$state', '$stateParams', '$location', 'loginService','$http','sessionService',
  function ($rootScope, $state, $stateParams, $location, loginService, $http, sessionService) {
		$rootScope.userInfo = sessionService.get('mrkien');
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;
        var routespermission = ['/access/signin','/access/signup'];  //route that require login
        //alert(routespermission.indexOf($location.path()));
    	$rootScope.$on('$locationChangeStart', function(){
            $rootScope.userInfo = JSON.parse(sessionService.get('mrkien'));
            
            $http.defaults.headers.common['Authorization']  = $rootScope.userInfo.token;
    		if(routespermission.indexOf($location.path()) == -1)
    		{
                /*var connected=loginService.islogged();
    			connected.then(function(api){
    			     if(api){
                        var result = api.data;
                        if(result.code == 'success'){
                            $rootScope.userInfo = result.session;
                            if($location.path() == '/access/signup' || $location.path() == '/access/signin')
                                $state.go('app.dashboard');
                        }
                        else{
                            if($location.path() != '/access/signup'){
                                $location.path('/access/signin');
                            }
                        }
                    }
                    else{
                        $location.path('/access/signin');
                    }
    			}, 
                function(x) {
                    $location.path('/access/signin');
                });*/
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
        debug: false,
        events: true,
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
}]);