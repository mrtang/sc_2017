angular.module('app')
	.service('PipeJourney', ['$http', function ($http){
		var obj = {};

		obj.getGroupProcessAddress = function (id, page, item_page){
			var url = ApiPath + 'pipe-status/group-process-address';
			page 		= (page) ? page : 1;
			item_page 	= (item_page) ? item_page : 20;
			if(id){
				url+= '/' + id;
			}
			url += '?page=' + page + '&item_page='+item_page

			return $http.get(url);
		}
		obj.getGroupProcessSeller = function (id, page, item_page, type){
			var url = ApiPath + 'group-process-seller/show';
			page 		= (page) ? page : 1;
			item_page 	= (item_page) ? item_page : 20;
			if(id){
				url+= '/' + id;
			}
			url += '?page=' + page + '&item_page='+item_page+'&type=' + type

			return $http.get(url);
		}

		
		
		obj.getPipeStatus = function (id, filter, page, item_page){
			var url = ApiPath + 'pipe-status/show';
			page 		= (page) ? page : 1;
			item_page 	= (item_page) ? item_page : 20;
			if(id){
				url+= '/' + id;
			}

			url += '?page=' + page + '&item_page='+item_page

			if(filter.type){
				url += '&type=' + filter.type
			}

			return $http.get(url);
		}

		obj.savePipeStatus = function (id, data){
			var url = ApiPath + 'pipe-status/save';
			if(id){
				url+= '/' + id;
			}
			return $http.post(url, data);
		}

		obj.removePipeStatus = function (id){
			var url = ApiPath + 'pipe-status/remove' + '/' + id;
			return $http.post(url, {});
		}


		/*obj.getPipeStatusSeller = function (id, page, item_page){
			var url = ApiPath + 'pipe-status-seller/show';
			page 		= (page) ? page : 1;
			item_page 	= (item_page) ? item_page : 20;
			if(id){
				url+= '/' + id;
			}
			url += '?page=' + page + '&item_page='+item_page

			return $http.get(url);
		}

		obj.savePipeStatusSeller = function (id, data){
			var url = ApiPath + 'pipe-status-seller/save';
			if(id){
				url+= '/' + id;
			}
			return $http.post(url, data);
		}

		obj.removePipeStatusSeller = function (id){
			var url = ApiPath + 'pipe-status-seller/remove' + '/' + id;
			return $http.post(url, {});
		}*/

		obj.createGroupUser = function (data){
			var url = ApiPath + 'pipe-status/create-group-user';
			return $http.post(url, data);
		}




		return obj;
	}]);
