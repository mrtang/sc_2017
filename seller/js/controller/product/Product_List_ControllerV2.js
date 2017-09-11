'use strict';
/**
 * Created by Giau on 9/2016.
 * controller danh sach mat hang 
 * version:02
 **/
//#################### CONTROLLER ####################

app.controller('ItemOderWattingV2', [
			'$scope','items','$modalInstance','ProductsRepository', 
    function($scope , items,$modalInstance ,   ProductsRepository) {
				$scope.search = {
					orderwatting: {},
					
					proceed_order_watting: function (items) {
						ProductsRepository.find_sku(items).then(function(result){
							$scope.items = result.data;
						});
					}
				};
				if (items)
					$scope.search.proceed_order_watting(items.wmsSKU);
				if(items.wmsSKU && items.InventoryId){
					$scope.search.orderwatting.sku=items.wmsSKU;
					$scope.search.orderwatting.wms=items.InventoryId;
					$scope.productname=items.Name;
					$scope.productsku=items.SellerSKU;
					$scope.productwms=items.InventoryName;
					//$scope.search.proceed_order_watting(items.wmsSKU);	
				}
				$scope.cancel = function(){
					$modalInstance.dismiss('cancel');
				}
	}])              	
app.controller('ListProductsV2Controller', [
			'$scope','$stateParams','WarehouseRepository','ProductsRepository','GetProductsV2','$rootScope','$state','Analytics','$filter','$modal', 'toaster','$alertModal','$confirmModal',
	function(  $scope,  $stateParams,  WarehouseRepository,  ProductsRepository,  GetProductsV2 ,  $rootScope,  $state, Analytics,  $filter,  $modal,   toaster,  $alertModal,  $confirmModal){
				var tran = $filter('translate');
				var item_temp = {};
				if(!$rootScope.userFulfillment){
					
					$state.go('product.thongbao');
				}
				//var accessToken = $tokenManager.accessToken();
				$scope.exportUrl1 = BOXME_API+'export_seller_product_V2';// + '?access_token=' + accessToken ;
				//get kho
				$scope.listWarehouseBomxe = [];
				$scope.listWarehouseENTERPRISE = [];
				$scope.listStore = [{'Name':tran('PROV2_select_status_all'),'InventoryId':'BOXME'}];
				var bindToListStore = function(result) {
					//$scope.listStore = result.listOf('inventory');
					angular.forEach(result.listOf('inventory'), function(item) {
						$scope.listStore.push(item);
					});
				};
				$scope.listEnterprise = function() {
					WarehouseRepository.getListInventory().then(function(result) {
						$scope.listWarehouseENTERPRISE =  [] //result.data._embedded.inventory ? result.data._embedded.inventory:[];
						if(result.data._embedded.inventory){
							angular.forEach(result.data._embedded.inventory, function(item) {
								if(item.Code == "" || item.Code == null){
									$scope.listWarehouseENTERPRISE.push(item);
								}
							});
						}
						$scope.listWarehouseBomxe = []
						if(result.data._embedded.boxme){
							angular.forEach(result.data._embedded.boxme, function(item) {
								
								if(item.Checked == 1){
									item.InventoryId =  item.IdInevent;
									$scope.listWarehouseBomxe.push(item);
								}
							});
						}
					})
				};
				$scope.listEnterprise();
				$scope.changeWarehouse = function(item){
					var checkWh = false
					angular.forEach($scope.listWarehouseBomxe, function(jtem) {
						if(jtem.InventoryId.toString() == item.toString()){
							checkWh = true;
						}
					});
					if(checkWh == false && item.toString() != 'BOXME'){
						$scope.disableCheckbox = true; //KHO RIENG KHONG CHON DC CAC CHECKBOX
						$scope.search.condition.limit_alert = 0;
						$scope.search.condition.not_enough = 0;
						$scope.search.condition.inventory_check = 0;						
					}else{
						$scope.disableCheckbox = false;
					}
				}
				//end get kho
				//check 
				$scope.checkbox_inventory_check = function(){
					if($scope.inventory_check == true){
						$scope.inventory_check = false;
					}else{
						$scope.inventory_check = true;
					}
				}
				//
				//tim kiem input
				$scope.suggestionProduct = function ($viewValue) {
					return GetProductsV2.find_product_by_key({key:$viewValue,page_size:10}).then(function (data) {
						if (data.data._embedded.product)
							return data.data.listOf('product');
						return []
					});
				};
				$scope.loadProductByName = function ($item) {
					$scope.search.condition.key =  $item.Name;
					$scope.search.condition.page =  1;
					$scope.search.proceed();
				};
				//end tim input
				//show san pham dc chon
				$scope.showproduct_selected = function(){
					if($scope.show_product_selected == false || $scope.show_product_selected == undefined){
						$scope.show_product_selected =true;
					}else{
						$scope.show_product_selected = false;
					}
				}
				//$scope.setActive = function(GetProductsV2)
				$scope.setInactive=  function (collection) {
					var listProductId = collection.map(function(product){
						return product.ProductId;
					});
					GetProductsV2.active({active:0},listProductId).then($scope.search.proceed);
				}
				$scope.setActive=  function (collection) {
					var listProductId = collection.map(function(product){
						return product.ProductId;
					});
					GetProductsV2.active({active:1},listProductId).then($scope.search.proceed);
				}
				//end show san pham dc chon
				//checkbox
				$scope.sp_sellected= []
				var modellingProducts = function(raw) {
					if($scope.search.condition.type=='BOXME')
						Analytics.trackPage('Product/Listing/BoxMe');
					else if($scope.search.condition.type=='ENTERPRISE')
						Analytics.trackPage('Product/Listing/Seller');
					$scope.products = raw;
					$scope.products.selectedAll = false;
					angular.forEach($scope.products._embedded.product, function(product) {
						 angular.forEach($scope.sp_sellected, function(sp) {
							 if(sp.wmsSKU == product.wmsSKU){
								 product.selected = true;
							 }
						})
						 /*if(product.inventory_limit && product.maybe_outbound){
							 if(product.maybe_outbound < product.inventory_limit){
								 product.alert_waring = "alert-danger"
							 }
						 }*/
						})
				}
				$scope.checkboxAll = function() {
//					if($scope.condition.apge==1){
//						$scope.sp_sellected = [];
//					}
					if($scope.products.seleectalll==false || $scope.products.seleectalll==undefined){
						$scope.sp_sellected = [];
					}
					$scope.soluong = 0
		        	$scope.checktotal = 0;
					$scope.products.selectedAll = !$scope.products.selectedAll;
					angular.forEach($scope.products._embedded.product, function(product, key) {
						product.selected = $scope.products.selectedAll;
						var check_sp = false;
						if(product.selected == true){
							if($scope.sp_sellected.length == 0){
								check_sp = false;
							} 
							angular.forEach($scope.sp_sellected, function(sp) {
								if(sp.wmsSKU == product.wmsSKU){
									check_sp = true;
				                }
							});
							 if(check_sp == false){
								 $scope.sp_sellected.push(product);
							 }
		                }
					});
					$scope.soluong = $scope.sp_sellected.length;
				};
				$scope.checkboxOne = function (item) {
					//$scope.sp_sellected = [];
					$scope.checktotal = 0;
					var temp_array = []
					var check_sp = false;
					if(item.selected == true){
						if($scope.sp_sellected.length == 0){
							check_sp = false;
						} 
						 angular.forEach($scope.sp_sellected, function(sp) {
								if(sp.wmsSKU == item.wmsSKU){
									check_sp = true;
				                }
							});
						 if(check_sp == false){
							 $scope.sp_sellected.push(item);
						 }
					 }
					if(item.selected == false){
						 angular.forEach($scope.sp_sellected, function(sp) {
								if(sp.wmsSKU != item.wmsSKU){
									temp_array.push(sp);
				                }
							});
						 $scope.sp_sellected=temp_array;
					 }
					$scope.soluong = $scope.sp_sellected.length;
				};
				
				//end check box chon san pham
				//check not check_not_enough
				$scope.check_not_enough = function(){
					if($scope.search.condition.not_enough == 1){
						$scope.search.condition.limit_alert = 0;
						$scope.search.condition.inventory_check = 0;
					}
					$scope.search.proceed();
				}
				$scope.check_limit_alert = function(){
					if($scope.search.condition.limit_alert == 1){
						$scope.search.condition.not_enough = 0;
					}
					$scope.search.proceed();
				}
				$scope.check_inventory_check = function(){
					if($scope.search.condition.inventory_check == 1){
						$scope.search.condition.not_enough = 0;
					}
					$scope.search.proceed();
				}
				//end check_not_enough
				// dem Chờ xuất kho
				$scope.open = function (item) {
					$modal.open({
						templateUrl	: 'item_order.html',
						controller	: 'ItemOderWattingV2',
						size: 'md',
						resolve: {
							items: function() {return item;}
						}
					});
				};
				// end Chờ xuất kho
				//proceess
				$scope.search = {
						// Default search condition
						condition: {
							active: 1,
							inventory_id: $stateParams.type ? $stateParams.type: 'BOXME',
							page: 1,
							page_size: 50,
							limit_alert:0
						},
						condition_update:{},
						// Reset to the default search condition
						resetCondition: function () {
							$scope.search.condition = {
								active: 1,
								page: 1,
								page_size: 50,
								limit_alert:0
							}
						},
						proceed: function () {
							$scope.search.searching = true;
							if ($stateParams.type)
								$scope.search.condition.inventory_check = 0;
							GetProductsV2.find($scope.search.condition).then(function(result) {
									$scope.search.searching = false;
									if (result.data)
										return result.data;
								}).then(modellingProducts);
						},
						proceed_update: function () {
							ProductsRepository.update_prices_product($scope.search.condition_update).then(function(result) {
								$scope.data_update = result;
								if($scope.data_update.type==1){
									//toaster.pop('success', 'Thành công', 'Cập nhật số lượng tồn tối thiểu thành công');
									toaster.pop('success',tran('toaster_ss_nitifi'), tran('PRO_C_toaster_update_limmit'));
								}else{
									//toaster.pop('success', 'Thành công', 'Cập nhật giá sản phẩm thành công');
									toaster.pop('success',tran('toaster_ss_nitifi'), tran('PRO_C_toaster_update_price'));
								}
								
								$scope.search.proceed();
								},function(response) {
									if(response.data.status == 2){
										toaster.pop('error', 'Lổi','Sản phẩm này không thuộc tổ chức của bạn' )
										//toaster.pop('success',tran('toaster_ss_nitifi'), tran('PRO_C_toaster_update_price'));
										}
								 })
						}
					};
				
				//end process
				//update price
				$scope.updateProductSale = function(product,data,type) {
					$scope.search.condition_update.price =  type == 1 ? data: $scope.convert_currency_to_home_currency(data);
					$scope.search.condition_update.alert = -1;
					$scope.search.condition_update.ProductId = product.ProductId;
					$scope.search.proceed_update();
				}
				//end update price
				//
				//
				//update so luong ton kho toi thieu
				$scope.updateProductlimit_alert =function(product,data){
					product.loading1 = true
					$scope.search.condition_update.price = -1;
					$scope.search.condition_update.alert = data;
					$scope.search.condition_update.ProductId = product.ProductId;
					$scope.search.proceed_update();
					product.loading1 = false;
				}
				//end update so luong ton kho toi thieu
				
				//hanh dong khi chon danh sach san pham
				$scope.filterSelected = function () {
						return $scope.sp_sellected;
					};
				$scope.transfer = {
						doTransferToInventory: function(collection, toInventoryType) {
							$rootScope.products         = collection;
							$rootScope.toInventoryType  = toInventoryType;

							$state.go('product.shipment-create');
						},

						toBoxme: function(collection) {
							for(var i = 0, length = collection.length; i < length; i++)
							{
								var tmp = collection[i];
								if (collection[0].InventoryId != tmp.InventoryId)
								{
									//$alertModal.show('Chuyển kho không hợp lệ', 'Bạn vui lòng chọn các sản phẩm cùng trong 1 kho hàng');
									$alertModal.show(tran('PRO_C_aler_err_ship'), tran('PRO_C_aler_err_shipDetail'));
									return;
								}
								if(tmp.QuantityFree == 0)
								{
									//toaster.pop('error', 'Sản phẩm trong kho rỗng');
									toaster.pop('error',tran('toaster_ss_nitifi'), tran('PRO_C_aler_err_inventNull'));
									return;
								}
							}
							//$confirmModal.ask('Xác nhận chuyển kho', 'Bạn có muốn chuyển các sản phẩm này về kho '+$scope.app.name+' hay không?')
							$confirmModal.ask(tran('PRO_aler_ask'), tran('PRO_aller_askDetail'))
								.then(function() {
									$scope.transfer.doTransferToInventory(collection, 'BOXME');
								});
						},
						makeOrder : function (collection) {
							for(var i=0, length = collection.length; i < length; i++ ) {
								if (collection[0].InventoryId != collection[i].InventoryId) {
									//$alertModal.show('Tạo đơn hàng không hợp lệ','Bạn vui lòng chọn các sản phẩm cùng trong 1 kho hàng');
									$alertModal.show(tran('PRO_aler_order_err'), tran('PRO_C_aler_err_shipDetail'));
									return;
								}
							}
							$rootScope.orderItemsSelected = collection;
							$state.go('orderv2.create');
						},
					};
				//end hanh dong khi chon danh sach san pham
	}])
	 app.service('GetProductsV2', ['$http','toaster','$filter',function($http,toaster,$filter) {
		 var tran = $filter('translate');
		 var sanityFilterProductCondition = function(condition) {
				if (""===condition.active) {
					delete condition.active;
				}
				if (""===condition.key) {
					delete condition.key;
				}
				if (""===condition.page_size) {
					delete condition.page_size;
				}
				if (""===condition.page) {
					delete condition.page;
				}
				if (""===condition.type) {
					delete condition.type;
				}
				if (""===condition.inventory_id) {
					delete condition.inventory_id;
				}
			};
			return {
				find_product_by_key : function (condition) {
					sanityFilterProductCondition(condition);
					return $http({
	                    url: BOXME_API+'get_list_products_by_key_V2',
	                    method: "GET",
	                    "params": condition
	                }).success(function (result) {
	                	return result
	                    /*if(result.error) {
	                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
	                    }*/
	                }).error(function (data) {
	                    if(status == 422){
	                        //Storage.remove();
	                    }else{
	                    	toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_ketnoidulieuthatbai'));
	                        //toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
	                    }
	                })
				},
				find: function(condition) {
					return $http({
	                    url: BOXME_API+'get_list_products_V3',
	                    method: "GET",
	                    "params": condition
	                }).success(function (result) {
	                	return result
	                    /*if(result.error) {
	                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
	                    }*/
	                }).error(function (data) {
	                    if(status == 422){
	                        //Storage.remove();
	                    	
	                    }else{
	                    	toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_ketnoidulieuthatbai'));
	                       // toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
	                    }
	                })
				},
				active: function(condition,ids) {
					return $http({
	                    url: BOXME_API+'product-active'+'/'+ids,
	                    method: "POST",
	                    data: condition
	                }).success(function (result) {
	                	return result
	                    /*if(result.error) {
	                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
	                    }*/
	                }).error(function (data) {
	                    if(data && data.validation_messages){
	                        //Storage.remove();
	                    	toaster.pop('warning',tran('toaster_ss_nitifi'), data.validation_messages);
	                    }else{
	                        //toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại!');
	                        toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_ketnoidulieuthatbai'));
	                    }
	                })
				},
			};
		}])
	
		