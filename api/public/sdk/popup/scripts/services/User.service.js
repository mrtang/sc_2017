"use strict";

angular.module('ShipChungApp')
	.factory('User', ['$http', '$cookieStore',  function ($http, $cookieStore){
		var _userPrefix  = "___sc_userinfo"
		return {
			getUser: function (){
				if(localStorage.getItem(_userPrefix)){
					return JSON.parse(localStorage.getItem(_userPrefix));
				}
				return  {};
			},
			setUser: function (user){
				user  = JSON.stringify(user);
				localStorage.setItem(_userPrefix, user);
			},
			clearUser: function (){
				localStorage.removeItem(_userPrefix);
			}

		}
			
	}]);

