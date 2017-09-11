angular.module('app')
	.service('Merchant', ['$http', function ($http){
		var Merchant = {};

		Merchant.getList = function (filter, page, cmd){
			var url = ApiPath + 'oms/merchant/show?page='+page;
			var dateFields  = ['time_update_start', 'time_update_end', 'first_order_end', 'first_order_start', 'last_order_end', 'last_order_start', 'time_create_end', 'time_create_start'];
			if(filter.group_process){
				url	+= '&group_process=' + filter.group_process; 
			}
			if(filter.group_type){
				url	+= '&group_type=' + filter.group_type; 
			}

			if(filter.pipe_status){
				url	+= '&pipe_status=' + filter.pipe_status; 
			}
			if(filter.email){
				url	+= '&email=' + filter.email; 
			}

			if(filter.search_type){
				url	+= '&search_type=' + filter.search_type; 
			}
			if(filter.search){
				url	+= '&search=' + filter.search; 
			}
			if(filter.limit){
				url	+= '&item_page=' + filter.limit; 
			}

			if(filter.place_city){
				url	+= '&place_city=' + filter.place_city; 
			}

			if(filter.place_district){
				url	+= '&place_district=' + filter.place_district; 
			}

			if(filter.business_model){
				url	+= '&business_model=' + filter.business_model; 
			}

			if(filter.avg_lading_start){
				url	+= '&avg_lading_start=' + filter.avg_lading_start; 
			}

			if(filter.avg_lading_end){
				url	+= '&avg_lading_end=' + filter.avg_lading_end; 
			}
			angular.forEach(dateFields, function (value, key){
				if(filter[value] != undefined && filter[value] != ''){
					url += '&' + value + '=' + filter[value];
				}
			})
			if(cmd == 'export'){
				return window.location = url + '&cmd=export';
			}
			
			return $http.get(url);
		}


		Merchant.getListVip = function (filter, page, limit){
			var url = ApiPath + 'oms/merchant/vip?page='+page;
			if(filter.group_process){
				url	+= '&group_process=' + filter.group_process; 
			}
			if(filter.group_type){
				url	+= '&group_type=' + filter.group_type; 
			}
			if(filter.email){
				url	+= '&email=' + filter.email; 
			}

			if(filter.search_type){
				url	+= '&search_type=' + filter.search_type; 
			}
			if(filter.search){
				url	+= '&search=' + filter.search; 
			}

			return $http.get(url);
		}

		Merchant.updateStatus = function (data){
			var url = ApiPath + 'oms/merchant/update-status';
			return $http.post(url, data);
		}
		return Merchant;
	}]);
