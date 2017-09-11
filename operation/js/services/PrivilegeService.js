angular.module('app')
	.service('Privilege', ['$http', function ($http){
		var obj = {};

		obj.getGroupUser = function (id, page, item_page){
			var url = ApiPath + 'group-user/show';
			page 		= (page) ? page : 1;
			item_page 	= (item_page) ? item_page : 20;
			if(id){
				url+= '/' + id;
			}
			url += '?page=' + page + '&item_page='+item_page

			return $http.get(url);
		}

		
		obj.saveGroupUser = function (id, data){
			var url = ApiPath + 'group-user/save';
			if(id){
				url+= '/' + id;
			}
			return $http.post(url, data);
		}

		obj.removeGroupUser = function (id){
			var url = ApiPath + 'group-user/remove' + '/' + id;
			return $http.post(url, {});
		}

		obj.getPrivilege = function (id, page, item_page){
			var url = ApiPath + 'privilege/show';
			page 		= (page) ? page : 1;
			item_page 	= (item_page) ? item_page : 20;
			if(id){
				url+= '/' + id;
			}
			url += '?page=' + page + '&item_page='+item_page

			return $http.get(url);	
		}

		obj.savePrivilege = function (id, data){
			var url = ApiPath + 'privilege/save';
			if(id){
				url+= '/' + id;
			}
			return $http.post(url, data);
		}

		obj.removePrivilege = function (id){
			var url = ApiPath + 'privilege/remove' + '/' + id;
			return $http.post(url, {});
		}
		
		return obj;
	}]);
