
    "use strict";

	var app = angular.module("ShipChungApp", ['ngRoute','pascalprecht.translate', 'ngCookies', 'ngAnimate']);

	app.config(['$routeProvider','$translateProvider', '$locationProvider',
		function($routeProvider,$translateProvider,  $locationProvider) {
            

            $routeProvider
                .when('/process', {
                    templateUrl: 'views/loading_process.html?t=' + new Date().getTime(),

                    controller: function ($location, $timeout, $rootScope){
                        $rootScope.currentButtonId = $location.search().q;
                        
                        if($location.search().hasOwnProperty('version') && $location.search()['version'] == '1'){
                            $location.path('/checkoutv1');
                        }else {
                            $location.path('/checkout');
                        }
                    }
                })
                
                .when('/checkout', {
    				templateUrl: 'views/v2/popup.html?t=' + new Date().getTime(),
                    controller: 'PaymentCtrl',
    			})

                .when('/checkoutv1', {
                    templateUrl: 'views/v2/popup.html?t=' + new Date().getTime(),
                    controller: 'PaymentCtrl',
                })
                .when('/result/:code', {
                    templateUrl: 'views/popup_checkout_success.html?t=' + new Date().getTime(),
                    controller: 'PaymentSuccessCtrl',
                    
                })
                .when('/result/:code/:nlToken', {
                    templateUrl: 'views/popup_checkout_nl_success.html?t=' + new Date().getTime(),
                    controller: 'PaymentSuccessCtrl',
                    
                })
                .when('/errorPage', {
                    templateUrl: 'views/error.html'
                })

                .when('/checkout/:method', {
    				templateUrl: 'template/checkout.html',
    				controller: 'CheckoutOptionCtrl'
    			})
                
                .otherwise({
    				redirectTo: '/process'
    				/*templateUrl: 'views/popup.html',
    				controller: 'PaymentCtrl'*/
                });
               /*$locationProvider.html5Mode({
                 enabled: true,
                 requireBase: false
                });
                $locationProvider.hashPrefix('!');*/
            $translateProvider.useStaticFilesLoader({
                prefix: 'languages/',
                suffix: '.json'
            }).preferredLanguage(appConfig.lang).useCookieStorage();
		}
	]);

    app.config(function($httpProvider) {
        $httpProvider.defaults.useXDomain = true;
        delete $httpProvider.defaults.headers.common['X-Requested-With'];
    });

    app.factory('httpInterceptor', ['$q', '$rootScope',  function($q, $rootScope) {

        function success(response) {
            console.log('httpSuccess', response);
            return response;
        }

        function error(response) {
            var status = response.status;   
            console.log('httpError', response);
            if (status === 401) {
              $rootScope.$broadcast('event:loginRequired');
              return
            }
            // otherwise
            return $q.reject(response);
        }

        return function(promise) {
            return promise.then(success, error);
        }
    }]);

    app.run(['$rootScope', '$templateCache', '$routeParams', function ($rootScope, $templateCache, $routeParams){

        $rootScope.$on('$viewContentLoaded', function() {
            $templateCache.removeAll();
        });
        

        $rootScope.OrderInfo = {};
        $rootScope.currentButtonId = "";
        
        $rootScope.closePopup = function (){
            //window.top.postMessage('close-popup', '*');
             window.top.postMessage({name: 'closeAndRemovePopup', data: null}, '*');
        }

        $rootScope.closeAndRemovePopup = function (){
            window.top.postMessage({name: 'closeAndRemovePopup', data: $rootScope.currentButtonId}, '*');
        }

        $rootScope.redirectUrl = function (){
            var url = (window.location != window.parent.location) ? document.referrer : document.location;
            
            if(url.indexOf('mykingdom') !== -1){
                var tracking_code = $routeParams.code;
                window.top.postMessage({name: 'redirectUrl', data: 'http://www.mykingdom.com.vn/complete/' + tracking_code}, '*');
            }else {
                window.top.postMessage({name: 'closeAndRemovePopup', data: null}, '*');
            }
            
            
        }

        document.onkeydown = function(evt) {
            if(evt.keyCode == 27){
                // $rootScope.closePopup();
                // console.log('Escape button press ');
            }
        }

    }])
  
  
