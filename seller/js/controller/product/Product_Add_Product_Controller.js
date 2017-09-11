'use strict';
/**
 * Created by Giau on 9/2016.
 * controller danh sach mat hang 
 * version:02
 **/
//#################### CONTROLLER ####################

app.controller('AddProductController',
				['$q','$filter','$scope','$localStorage', '$rootScope', 'ProductsMetadata', 'ProductsRepository', 'toaster', '$stateParams', '$state', 'WarehouseRepository', '$random2', 'Analytics',
		function ($q,  $filter,  $scope,  $localStorage,   $rootScope,   ProductsMetadata,   ProductsRepository,   toaster,   $stateParams,   $state,   WarehouseRepository,   $random2,   Analytics) {
		if($stateParams.code)
			Analytics.trackPage('Product/Edit/'+$stateParams.code);
		else
			Analytics.trackPage('Product/Create/Simple');
		// build ui for category
		
		if(!$rootScope.userFulfillment){
			$state.go('product.thongbao');
		}
		var tran = $filter('translate');
		var imageDeferred   = $q.defer();
		$scope.imagePromise = imageDeferred.promise;
		var initScope = function () {
			if ($stateParams.code) {
					ProductsRepository.get($stateParams.code).then(function(product){
						$scope.min = product.Quantity;
						$scope.product = product.data;
						if ($stateParams.copy) {
							delete $scope.product.ProductId;
							delete $scope.product.SellerSKU;
						}
						if($localStorage['home_currency'].toString() != $localStorage['currency'].toString()){
							$scope.product.SalePrice_currency_2 = $scope.product.SalePrice ? $rootScope.convert_currency($scope.product.SalePrice).toFixed(2) :0;
							$scope.product.WholeSalePrice_currency_2  = $scope.product.WholeSalePrice ? $rootScope.convert_currency($scope.product.WholeSalePrice).toFixed(2) :0;
							$scope.product.BasePrice_currency_2  = $scope.product.BasePrice ? $rootScope.convert_currency($scope.product.BasePrice).toFixed(2) :0;
						}
						initProductFormDisplay($scope.product);
						imageDeferred.resolve(product.ProductImages);
					});
			} else {
				$scope.product = {};
				imageDeferred.resolve([]);
			}
			$scope.listCategory = [];
			$scope.listCateId = [];
			$scope.listProvider = [];
			$scope.listProviderId = [];
			$scope.listBrand = [];
			$scope.listBrandId = [];

			$scope.ProductsMetadata = ProductsMetadata;
			 // list attribute of product
			ProductsMetadata.getProductProperties().then(function (properties) {
				$scope.properties = properties;
				
			});
			ProductsMetadata.getProductProperties_en().then(function (properties) {
				$scope.properties_en = properties;
				
			});
			// list unit
			ProductsMetadata.getUnits().then(function (properties) {
				$scope.units = properties;
			});
			// list brand
			ProductsMetadata.getBrands().then(function (brands) {
				$scope.brands = brands;
			});
			// list weight unit
			ProductsMetadata.getWeights().then(function (weights) {
				$scope.weights = weights;
			});
			$scope.listWareHouse = [];
			// get warehoure
			WarehouseRepository.getListInventory().then(function (data) {
				$scope.listWareHouse =  []; //data.data._embedded.inventory;
				if(data.data._embedded.inventory){
					angular.forEach(data.data._embedded.inventory, function(item) {
						if(item.Code == "" || item.Code == null){
							$scope.listWareHouse.push(item);
						}
					});
				}
				$scope.product.InventoryId = $scope.product.InventoryId ? $scope.product.InventoryId : $scope.listWareHouse[0].InventoryId;
			});
		};
		$scope.suggestion = {};
		var initProductFormDisplay = function (product) {
			displayCategoryTag(product.CategoryName);
			//displayProviderTag(product.SupplierName);
			
			/** @namespace $scope.suggestion */
			if (product.ModelName && product.ModelName != ""){
				$scope.suggestion.model = product.ModelName;
				if (''!== product.ProductTags) {
					$scope.suggestion.tags =  product.ProductTags.split(',').map(function(tag){return {text:tag}});
				}
			}
		};
		var displayCategoryTag = function (categoryName) {
			$scope.listCateId = [categoryName];
		};
		var displayProviderTag = function (providerName) {
			//$scope.listProviderId = [providerName];
		};
		// check loi tieng viet sku
		 $scope.$watch('product.SellerSKU', function (Value, OldValue) {
             if (Value) {
            	 $scope.product.SellerSKU = $scope.convertTextToStringStandard($scope.product.SellerSKU);
            } 
         });
		// end check loi tieng viet
		var shouldPopulateSku = true;
		$scope.$watch(function () {
			return ($scope.product.CategoryId || '').toString() + ($scope.product.ModelId || '').toString();
		}, function (value) {
			if (shouldPopulateSku && value) {
				$scope.product.SellerSKU = $scope.product.SellerSKU ? $scope.product.SellerSKU : ($scope.setSkuByName($scope.product.Name)+'-'+$random2());
				$scope.product.ManufactureBarcode = $scope.product.ManufactureBarcode ? $scope.product.ManufactureBarcode : $scope.product.SellerSKU;
				shouldPopulateSku = false;
				//$scope.checkProductCodeExist();
			}
		});
		
		initScope();
		$scope.extractProductMetaByModel = function ($item) {
			$scope.listCategory = [];
			$scope.listCateId = [];
			$scope.listProvider = [];
			$scope.listProviderId = [];

			/** @namespace $item.model.name */
			$scope.product.Name = $item.model.name;
			$scope.product.CategoryId = $item.category.id;
			$scope.product.CategoryName = $item.category.name;
			displayCategoryTag($item.category.name);

			/** @namespace $item.manufacture */
			$scope.product.SupplierId = $item.manufacture.id;
			$scope.product.SupplierName = $item.manufacture.name;
			displayProviderTag($item.manufacture.name);

			$scope.product.ModelId = $item.model.id;
			$scope.product.ModelName = $item.model.name;
		};
		$scope.showSugest = function(){
			$scope.checkSugest = true;
			ProductsMetadata.getModels($scope.product.Name).then(function (data) {
				$scope.listProductsMetadata = data;
			});
		}
		$scope.checkSugest = false;
		$scope.seleteSugest = function(value){
			$scope.listCategory = [];
			$scope.listCateId = [];
			$scope.listProvider = [];
			$scope.listProviderId = [];

			/** @namespace $item.model.name */
			$scope.product.Name = value.model.name;
			$scope.product.CategoryId = value.category.id;
			$scope.product.CategoryName = value.category.name;
			displayCategoryTag(value.category.name);

			/** @namespace $item.manufacture */
			$scope.product.SupplierId = value.manufacture.id;
			$scope.product.SupplierName = value.manufacture.name;
			displayProviderTag(value.manufacture.name);

			$scope.product.ModelId = value.model.id;
			$scope.product.ModelName = value.model.name;
			$scope.checkSugest = false;
		}
		$scope.clearSync = function(){
			$scope.listCategory = [];
			$scope.listCateId = [];
			$scope.listProvider = [];
			$scope.listProviderId = [];
			$scope.product.ProductId = "";
			$scope.product.Name = "";
		}
		$scope.setSugest = function(){$scope.checkSugest = true;}
		$scope.resetSugest = function(){$scope.checkSugest = false;}

		// build item category
		$scope.buildCategory = function () {
			$scope.select2OptionsCategory = {
				formatSelectionTooBig:function(){
					//return 'Bạn chỉ có thể chọn 1 danh mục';
					return tran('PROA_Banchicothechonmotdanhmuc')
				},
				maximumSelectionSize: 1,
				multiple: true,
				simple_tags: true,
				query: function (q) {
					$scope.checkSugest = false;
					ProductsMetadata.getCategories(q.term).then(function(d)
					{
						$scope.listCategory = d;
						q.callback({results:d});
					});
				},
				formatResult: function (cate) {
					return cate.name;
				},
				formatSelection: function (cate) {
					if($scope.listCategory.length>0){
						for(var i= 0, length = $scope.listCategory.length; i < length; i++) {
							if (cate.id == $scope.listCategory[i].id) {
								$scope.product.CategoryId = $scope.listCategory[i].id;
								$scope.product.CategoryName = $scope.listCategory[i].name;
								return $scope.listCategory[i].name;
							}
						}
					}else{
						return cate.text;
					}

				},
				'tags': []  // Can be empty list.
			};
		};
		$scope.errro1 = "";
		$scope.check_change_sku_seller = function(){
			
			var check_suk =  $scope.product.ManufactureBarcode.split(' ');
			if(check_suk.length >= 2){
				$scope.errro1 = "errro";
				//toaster.pop('error', 'Bạn vui lòng kiểm tra lại mã SKU sản phẩm, mã SKU cần viết liền và không có khoảng cách');
				toaster.pop('error', tran('PROA_Banvuilongkiemtralaima'));
			}
		}
		$scope.errro2 = "";
		$scope.check_sku_code_sellers = function(prototype){
			var check_suk_seller =  prototype.split(' ');
			if(check_suk_seller.length >= 2){
				$scope.errro2 = "errro";
				//toaster.pop('error', 'Bạn vui lòng kiểm tra lại mã SKU sản phẩm, mã SKU cần viết liền và không có khoảng cách');
				toaster.pop('error', tran('PROA_Banvuilongkiemtralaima'));
			}
		}
		// list brand
		$scope.buidProvider = function () {
			// buid item product brand
			$scope.select2OptionsProvider = {
				formatSelectionTooBig:function(){
					//return 'Bạn chỉ có thể chọn 1 item!';
					return tran('PROA_Banchicothechonmotitem');
				},
				maximumSelectionSize: 1,
				multiple: true,
				simple_tags: true,
				tags:[],
				query: function (q) {

					ProductsMetadata.getProviders(q.term).then(function(d)
					{
						$scope.listProvider = d;
						q.callback({results:d});
					});
				},
				formatResult: function (cate) {

					return cate.name;
				},
				formatSelection: function (cate) {
					if($scope.listProvider.length > 0){
						for(var i= 0, length = $scope.listProvider.length; i < length; i++) {
							if (cate.id == $scope.listProvider[i].id) {
								$scope.product.SupplierId = $scope.listProvider[i].id;
								$scope.product.SupplierName = $scope.listProvider[i].name;
								return $scope.listProvider[i].name;
							}
						}
					}else{
						// case: set tag default
						return cate.text;
					}
				}
			};
		};
		// buid item product brand
		$scope.buildBrand = function(){
			$scope.select2OptionsBrand = {
				formatSelectionTooBig:function(){
					//return 'Bạn chỉ có thể chọn 1 item!';
					return tran('PROA_Banchicothechonmotitem');
				},
				maximumSelectionSize: 1,
				multiple: true,
				simple_tags: true,
				tags:[],
				query: function (q) {

					ProductsMetadata.getBrands(q.term).then(function(d)
					{
						$scope.listBrand = d;
						q.callback({results:d});
					});
				},
				formatResult: function (cate) {

					return cate.name;
				},
				formatSelection: function (cate) {
					if($scope.listBrand.length > 0){
						for(var i= 0, length = $scope.listBrand.length; i < length; i++) {
							if (cate.id == $scope.listBrand[i].id) {
								$scope.product.product_brand = $scope.listBrand[i];
								return $scope.listBrand[i].name;
							}
						}
					}else{
						// case: set tag default
						return cate.text;
					}
				}
			};
		};

		$scope.buildBrand();
		$scope.buildCategory();
		$scope.buidProvider();

		// check product code is exist
		//$scope.checkProductCodeExist = function () {
		//	if($scope.product.SellerSKU && $scope.product.SellerSKU.trim() !==''){
		//		ProductsRepository.find({sellersku:$scope.product.SellerSKU}).then(function (data) {
		//			$scope.formAddEditProduct.productcode.$dirty = true;
		//			$scope.formAddEditProduct.productcode.$setValidity('duplicated',data.totalItems()==0);
		//		});
		//	}
		//};

		// link create warehouse
		$scope.createWareHouse = function () {
			$state.go('setting.default.inventory');
		};

		// add property
		$scope.products_attrs = [{}];
		$scope.addProperty  = function () {
			var element = {};
			$scope.products_attrs.push(element);
		};
		// Delete property
		$scope.delProperty = function (attr) {
			$scope.products_attrs.splice($scope.products_attrs.indexOf(attr), 1);
		};
		// Remove property
		$scope.removeProperty = function (prototype) {
			$scope.prototypes.splice($scope.prototypes.indexOf(prototype), 1);
		};
		// generate attributes
		$scope.serializeAttrs = function (input, key, values) {
			var result = [];
			for (var i = 0; i < input.length; i++) {
				var tmp = input[i];
				for (var j = 0; j < values.length; j++) {
					var cloned = {};
					angular.copy(tmp, cloned);
					cloned[key] = values[j];
					result.push(cloned);
				}
			}
			return result;
		};

		$scope.prototypes = [];
		// generate property
		$scope.generateProperty = function () {
			$scope.product.ProtoTypes = [];
			var tmpPrototypes = [{}];
			var listAttr  = {};
			if($scope.products_attrs){
				for(var key in $scope.products_attrs ){
					if ($scope.products_attrs.hasOwnProperty(key)) {
						if($scope.products_attrs[key].name!= angular.undefined){
							listAttr[$scope.products_attrs[key].type] = $scope.products_attrs[key].name;
						}
					}
				}
			}
			
			$scope.product.ProductsAttrs = $scope.products_attrs
			
			for(key in listAttr ){
				if (listAttr.hasOwnProperty(key)) {
					tmpPrototypes =  $scope.serializeAttrs(tmpPrototypes, key, listAttr[key]);
				}
			}
		    
			for (var i = 0; i < tmpPrototypes.length; i++) {
				var cloned = {};
				cloned['sku_code'] = $scope.product.SellerSKU+'-'+$scope.mapKeySku(tmpPrototypes[i])+$random2();
				cloned['sku_code_seller'] = cloned['sku_code'];
				cloned['quantity'] = $scope.product.Quantity;
				cloned['price'] = $scope.product.BasePrice;
				cloned['whole_price'] = $scope.product.WholeSalePrice;
				cloned['cost'] = $scope.product.SalePrice;
				cloned['seller_product_name'] = $scope.product.Name + ' ' + $scope.mapKey(tmpPrototypes[i]);
				tmpPrototypes[i] = $.extend(tmpPrototypes[i],cloned);
			}
			$scope.prototypes = tmpPrototypes;
			$scope.product.ProtoTypes = tmpPrototypes;
		};

		// map name from attribute
		$scope.mapKey  = function (objects) {
			var str = '';
			for(var i in objects){
				if(objects.hasOwnProperty(i) && i!='$$hashKey' && $scope.properties.indexOf(i) >= 0){
					str += ' '+objects[i].text;
				}
			}
			return str;
		};
		
		$scope.convertTextToStringStandard = function (text){
			text = text.toLowerCase();
		    text = text.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a");
		    text = text.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ.+/g,"e");
		    text = text.replace(/ì|í|ị|ỉ|ĩ/g,"i");
		    text = text.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ.+/g,"o");
		    text = text.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u");
		    text = text.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y");
		    text = text.replace(/đ/g,"d");
		    text = text.toUpperCase();
		    return text
		}
		
		$scope.setSkuByName = function (value){
			if (value){
				var res = value.split(" ", 3);
				var text = "";
			    for(var i = 0; i < res.length;i++){
			    	text = text+(res[i].substr(0, 1).toLowerCase());
			    }	
			    text = $scope.convertTextToStringStandard(text);
			    return text
			}else return "BM"
			
			
		}
		
		// map name from attribute
		$scope.mapKeySku  = function (objects) {
			var str = '';
			
			for(var i in objects){
				if(objects.hasOwnProperty(i) && i!='$$hashKey' && $scope.properties.indexOf(i) >= 0){
					str += ''+objects[i].text.substr(0, 2);
				}
			}
			return $scope.convertTextToStringStandard(str);
		};

		// save Product
		$scope.saveProduct  = function () {
			$scope.formAddEditProduct.OnProccess = true;
			if ($scope.product.ProtoTypes == undefined){
				$scope.product.ProtoTypes = []
				var cloned = {}; 
				cloned['sku_code'] = $scope.product.SellerSKU;
				cloned['sku_code_seller'] = $scope.product.ManufactureBarcode;
				cloned['quantity'] = $scope.product.Quantity;
				cloned['price'] = $scope.product.BasePrice;
				cloned['whole_price'] = $scope.product.WholeSalePrice;
				cloned['cost'] = $scope.product.SalePrice;
				cloned['seller_product_name'] = $scope.product.Name;
				$scope.product.ProtoTypes.push(cloned);
			}
			else{
				$scope.product.ProtoTypes = $scope.prototypes;
			}
			if(!$scope.product.InventoryId){
				toaster.pop('error', tran('PROA_Banvuilongchonkhohang'));
				return;
			}
			if ($scope.product.ManufactureBarcode){
				var check_suk =  $scope.product.ManufactureBarcode.split(' ')
			}else{
				var check_suk = '';
			}
			if(check_suk.length >= 2){
				///toaster.pop('error', 'Bạn vui lòng kiểm tra lại mã SKU sản phẩm, mã SKU cần viết liền và không có khoảng cách');
				toaster.pop('error', tran('PROA_Banvuilongkiemtralaima'));
				$scope.formAddEditProduct.OnProccess = false;
				return;
			}
			if($scope.product.ProtoTypes[0].sku_code_seller){
				var check_suk_seller =  $scope.product.ProtoTypes[0].sku_code_seller.split(' ');
			}else{
				var check_suk_seller = '';
			}
			
			if(check_suk_seller.length >= 2){
				//toaster.pop('error', 'Bạn vui lòng kiểm tra lại mã SKU sản phẩm, mã SKU cần viết liền và không có khoảng cách');
				toaster.pop('error', tran('PROA_Banvuilongkiemtralaima'));
				$scope.formAddEditProduct.OnProccess = false;
				return;
			}
			if (!$scope.product.ProductImages) $scope.product.ProductImages = '';
			ProductsRepository.save($scope.product, true).then(function () {
					// Send a "Add Product" event to Mixpanel 
					// with a property "Accout Add Product" 
					mixpanel.identify($localStorage.id);
	                mixpanel.people.set({
	                	"$email": $localStorage.email ? $localStorage.email :"",     // only special properties need the $
	                    "$last_login": new Date(), 
	                    "$phone": $localStorage.phone ? $localStorage.phone : "",
	                    "$name": $localStorage.name ? $localStorage.name : "",
	                    "organization": $localStorage.organization ? $localStorage.organization : ""	
	                });
					mixpanel.track( 
						"Add Product", 
						{" Inventory Config ": $localStorage.email ? $localStorage.email :"",
						"Organization":	$localStorage.organization ? $localStorage.organization: ""} 
					); 
					// Intercom   
                    try {
                    	var metadata = {
 	                 		   user			: 	$rootScope.userInfo.email 		? $rootScope.userInfo.email 	: "",
 	                 		   name 		:   $rootScope.userInfo.fullname 	? $rootScope.userInfo.fullname 	: "",
 	                 		   time			: 	$filter('date')(new Date(),'yyyy-MM-dd HH:mm'),
                    		   type			:"Create one product",
                    		   links		:"product/product/add"
 	            		};
            			Intercom('trackEvent', 'Create product', metadata);
                    }catch(err) {
        			    console.log(err)
        			}
                    // Intercom
					///toaster.pop('success', 'Tạo mới sản phẩm thành công');
					toaster.pop('success', tran('PROA_Taosanphamthanhcong'));
					$state.go('product.list');
					$state.go('product.list',{type:"ENTERPRISE"});
				},
				function () {
					//toaster.pop('error', 'Có lỗi xảy ra');
					toaster.pop('error', tran('PROA_Coloixayra'));
				}).finally(function(){
					$scope.formAddEditProduct.OnProccess = false;
				});
		};

		$scope.removeImage = function (image) {
			$scope.product.ProductImages.splice($scope.product.ProductImages.indexOf(image), 1);
		};
		$scope.suggestTest = [];
		$scope.suggestTestProduct = "";
		$scope.changeSuggestTest = function(key,type){
			//defer.resolve(['Màu sắc', 'Kích thước', 'Hương vị', 'Dung lượng', 'Tốc độ', 'Chất liệu', 'Kiểu dáng']);
			//defer.resolve(['color', 'size', 'flavors', 'capacity', 'speed', 'fabric', 'style']);
			if (type == 'color' || type == 'Màu sắc'){
				//$scope.suggestTestProduct = ""+tran('PROA_VdXanhDoTrang')+"";
				$scope.suggestTest[key] = tran('PROA_VdXanhDoTrang'); //"VD: Xanh, Đỏ, Trắng...";
			}else if (type == 'size' || type == 'Kích thước'){
				//$scope.suggestTestProduct = tran('PROA_VdSMLXL');
				$scope.suggestTest[key] = tran('PROA_VdSMLXL'); //"VD: S, M, L, XL...";
			}else if (type == 'flavors' || type == 'Hương vị'){
				//$scope.suggestTestProduct = tran('PROA_VdNgotChuaCay');
				$scope.suggestTest[key] =  tran('PROA_VdNgotChuaCay') //"VD: Ngọt, Chua, Cay...";
			}else if (type == 'capacity' || type == 'Dung lượng'){
				//$scope.suggestTestProduct = tran('PROA_Vd16Gb32Gb64Gb');
				$scope.suggestTest[key] = tran('PROA_Vd16Gb32Gb64Gb') //"VD: 16G, 32G, 64G...";
			}else if (type == 'fabric' || type == 'Chất liệu'){
				//$scope.suggestTestProduct = tran('PROA_VdCottonJeanLua');
				$scope.suggestTest[key] = tran('PROA_VdCottonJeanLua') //"VD: Cotton, Jean, Lụa...";
			}else if (type == 'style' || type == 'Kiểu dáng'){
				//$scope.suggestTestProduct = tran('PROA_VdCodienHianDaiHippo');
				$scope.suggestTest[key] = tran('PROA_VdCodienHianDaiHippo') //"VD: Cổ điển, Hiện đại, Hippo...";
			}else if (type == 'speed' || type == 'Tốc độ'){
				//$scope.suggestTestProduct = tran('PROA_VdMsMhMph');
				$scope.suggestTest[key] = tran('PROA_VdMsMhMph')//"VD: m/s, m/h, mph...";
			}
		};
	}])
	
	
angular.module('app').service('ProductsMetadata',['$q', '$http','$filter',
	                      		function ($q, $http,$filter ) {
	var tran = $filter('translate');
		var getModels = function (keyword) {
			if (!keyword) return $q.reject('');
			return $http.get(BOXME_API+'matching-full-model', {params: {key: keyword}}).then(function (response) {
				return response.data;
			});
		};
		return {
			getModels : getModels,
			getCategories: function(keyword) {
				if (!keyword) return $q.reject('');
				return $http.get(BOXME_API+'matching-category', {params: {key: keyword}}).then(function (response) {
					var result = [];
					if (!response.data.length) return $q.reject('');
					angular.forEach(response.data, function (categoryFamily) {
						var labels = [];
						angular.forEach(categoryFamily, function (cate) { labels.push(cate.name); });
						result.push({
							id      :categoryFamily[categoryFamily.length-1].id,
							name    : labels.join(' » '),
							code    : categoryFamily[categoryFamily.length-1].id
						});
					});
					return result;
				})
			},

			getProviders: function(keyword) {
				if (!keyword) return $q.reject('');
				return $http.get(BOXME_API+'supplier-by-seller', {params: {key: keyword}}).then(function (response) {
					
					return response.data
				});
			},

			getProductProperties: function () {
				var defer = $q.defer();
				
				defer.resolve(['Màu sắc', 'Kích thước', 'Hương vị', 'Dung lượng', 'Tốc độ', 'Chất liệu', 'Kiểu dáng']);
				//defer.resolve(['color', 'size', 'flavors', 'capacity', 'speed', 'fabric', 'style']);
				return defer.promise;
			},
			getProductProperties_en: function () {
				var defer = $q.defer();
				
				defer.resolve(['color', 'size', 'flavors', 'capacity', 'speed', 'fabric', 'style']);

				return defer.promise;
			},
			
			getUnits: function () {
				var defer = $q.defer();

				defer.resolve([{'id':1,'name':'cái'},{'id':2,'name':'hộp'}]);

				return defer.promise;
			},

			getBrands: function () {
				var defer = $q.defer();

				defer.resolve([
					{id:1,code:1,name:'Apple iPhone'},
					{id:2,code:2,name:'Samsung galaxy'},
					{id:3,code:3,name:'Samsung galaxy tab'},
					{id:4,code:4,name:'Google'},
					{id:5,code:5,name:'Apple iMac'},
					{id:6,code:6,name:'Apple iPad'}
				]);

				return defer.promise;
			},

			getWeights: function () {
				var defer = $q.defer();

				defer.resolve([
						{id:1,name:'Gram'},
						{id:2,name:'Kg'},
						{id:3,name:'Tạ'},
						{id:4,name:'Tấn'}
				]);

				return defer.promise;
			}
		}
	}
])

	
angular.module('app').service('$random2', function () {
		return function () {
			return Math.round(Math.random() * 9999 + 10000).toString().split('').splice(1, 3).join('');
		}
})