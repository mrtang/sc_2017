"use strict";

angular.module('ShipChungApp')
	.factory('Payment', ['$http', function ($http){

		return {
			// Lấy danh sách thành phố
			getCity: function (callback){
				$http.get(appConfig.apiUrl.City, {params: {limit: 'all'}}).success(function(city) {
				if(city.error){
						callback(city.message, null);
					}else {
						callback(null, city.data);
					}
					return false;
				});
			},

			getCheckoutV1: function (token, callback){

				$http.get(appConfig.apiUrl.getCheckoutV1, {params: {token: "token"}}).success(function(merchant) {
					var newMerchant = {
						"Order" : {},
						"Item"	: []
					};

					var orderName = [];
					for(var i = 0 ; i < merchant.items.length ;i ++){
						newMerchant.Item.push({
							"Image" 	: merchant.items[i].item_image,
							"Link"		: "",
							"Name"		: merchant.items[i].item_name,
							"Price"		: merchant.items[i].item_amount,
							"Quantity"	: merchant.items[i].item_quantity
						});
						orderName.push(merchant.items[i].item_name);

					}
					newMerchant['Order'] = {
						"Amount"		: merchant.total_amount,
						"ProductName" 	: orderName.join(' ,'),
						"Quantity"		: merchant.total_item,
						"Weight"		: merchant.weight
					}


					callback(null, newMerchant);
					return false;
				});
			},

			// Lấy thông tin checkout
			getCheckout: function (checkoutId, callback){

				$http.get(appConfig.apiUrl.getCheckout, {params: {checkoutId: checkoutId}}).success(function(merchant) {
					if(merchant.error){
						callback(merchant.message, null);
					}else {
						callback(null, merchant.data)
					}
					return false;

				});
			},
			// Lấy thông tin quận /huyện trong thanh phố
			getDistrictByCity: function (cityCode, callback){
				$http.get(appConfig.apiUrl.District, {params: {city_id:cityCode, limit: 'all'}}).success(function(dist) {
					if(dist.error){
						callback(dist.message, null);
					}else {
						callback(null, dist.data)
					}
					return false;
				});
			},

			// Tính chi phí merchant 
			MerchantCalculate: function (calculateParams, callback){
				$http.post(appConfig.apiUrl.MerchantCalculate, calculateParams, {
					headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    transformRequest: function (data) {
                        return $.param(data);
                    }
				}).success(function(data) {
					if(!data.error && data.code == 'SUCCESS'){
						callback(null, data);
					}else {
						callback(true, {});
					}
				}).error(function (err){

					callback(true, {});
				});
			},
			createOrder: function (orderInfo, callback){

				$http.post(appConfig.apiUrl.createOrder, orderInfo, {
					headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    transformRequest: function (data) {
                        return $.param(data);
                    }
				})
				.success(function(data) {
					if(data.error == true){
						return callback(true, {});
					}
					callback(null, data);
				}).error(function (err){
					callback(true, {});
				});
			},
			createOrderWithNL: function (orderInfo, token, callback){
				var url = appConfig.apiUrl.createOrderWithNL + '/'+ token;

				$http.post(url, orderInfo, {
					headers : { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    transformRequest: function (data) {
                        return $.param(data);
                    }
				})
				.success(function(data) {
					callback(null, data);
					
				}).error(function (err){
					callback(true, {});
				});	
			},
			
			getOrderTrackInfo: function (trackCode, token, callback){

				$http.get(appConfig.apiUrl.getTrackInfo + '?TrackingCode='+ trackCode + '&MerchantKey='+token)
				.success(function(data) {
					if(data.error == false){
						callback(null, data);
					}else {
						callback(true, {});
					}
					
					
				}).error(function (err){
					callback(err, {});
				});	
			}
		}
	}]);

