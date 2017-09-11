'use strict';
var list_discount  = [];
var search_courier = 0;
//Discount Fee
angular.module('app').controller('CourierDiscountCtrl', ['$scope','$modal','$http','$state','$window','toaster','bootbox', 
 	function($scope,$modal,$http,$state,$window,toaster,bootbox) {
    
    // config
 	$scope.currentPage          = 1;
    $scope.item_page            = 20;
    $scope.list_discount        = list_discount;
    $scope.list_courier         = [];
    
    //Load Courier
    $http.get(ApiPath+'courier?limit=all').success(function(result){$scope.list_courier = result.data;});
    
    // List Courier Fee
    $scope.GetCourierDiscount = function(){
        if($scope.search_courier > 0){
        	$http({
                url: ApiPath+'discount-config?page='+$scope.currentPage+'&courier_id='+$scope.search_courier,
                method: "GET",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.list_discount    = result.data;
                $scope.totalItems       = result.total;
                $scope.maxSize          = 5;
                $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                if(parseInt(result.total) == 0){
                    toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
                }
                
                search_courier          = $scope.search_courier;
                list_discount           = $scope.list_discount
            }        
            else{
                toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
            }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }
        return;
    };
    
    // Cập nhật
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'discount-config/edit/'+id,
            method: "POST",
            data:myData,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Thất bại!');
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
    //Xoa
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'discount-config/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.list_discount.splice(item, 1); 
        				toaster.pop('success', 'Thông báo', 'Thành công!');
        			}	       
        			else{
        				toaster.pop('error', 'Thông báo', 'Thất bại!');
        			}
                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
                });
                return;
            }
        });
        return;
    }
    
    $scope.$on('update',function(){
        $scope.list_discount = list_discount;
    })
    
    /**
    *   Popup
    **/

    // Popup Add service
    $scope.open_popup = function(size,item,index){
        $modal.open({
            templateUrl : 'ModalDiscountConfig.html',
            controller  : 'ModalDiscountConfigCtrl',
            size:size,
            resolve: {
                items: function () {
                  var data = {'id':item,'index':index};
                  return data;
                }
            }
        });
    };
    
    // Popup Discount Type
    $scope.open_popup_type = function(size){
        $modal.open({
            templateUrl : 'ModalDiscountType.html',
            controller  : 'ModalDiscountTypeCtrl',
            size:size
        });
    };
    
}]);

// Modal Discount Type
angular.module('app').controller('ModalDiscountTypeCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox',
function($scope, $modalInstance, $http, toaster, bootbox) {
    // config
    $scope.data    = {};
    
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    //List
    $scope.discount_type  = function(){
        $http({
            url: ApiPath+'discount-config/type',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_discount_type = result.data;
        }        
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.discount_type();
    
    //Save
    $scope.add = function(){
        if($scope.data.name){
            $http({
            url: ApiPath+'discount-config/createtype',
            method: "POST",
            data:{'name' : $scope.data.name},
            dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    toaster.pop('success', 'Thông báo', 'Thành công!');
                    $scope.list_discount_type.unshift({'id' : result.id, 'name' : $scope.data.name});
                }          
                else{
                    toaster.pop('warning', 'Thông báo', result.message);
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }else{
            toaster.pop('warning', 'Thông báo', 'Discount Type Empty !');
        }
        
        return;
    }
    
    //Update
    $scope.change = function(id,data,field) {
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier-type/edit/'+id,
            method: "POST",
            data:myData,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('warning', 'Thông báo', 'Thất bại!');
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    //XOA
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
                $http({
                url: ApiPath+'courier-type/destroy/'+id,
                method: "get",
                dataType: 'json',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        $scope.list_courier_type.splice(item, 1);
                        toaster.pop('success', 'Thông báo', 'Thành Công!');
                    }          
                    else{
                        toaster.pop('warning', 'Thông báo', 'Thất bại!');
                    }
                }).error(function (data, status, headers, config) {
                    toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
                });
                return; 
            }
        });
        return;
    }
}]);

//Modal DiscountConfig
angular.module('app').controller('ModalDiscountConfigCtrl', ['$scope', '$filter', '$modalInstance', '$http', 'toaster', 'bootbox', 'items',
function($scope, $filter, $modalInstance, $http, toaster, bootbox, items) {
    // config 
    $scope.id_discount      = items.id;
    $scope.discount_config  = {};
    $scope.list_type        = [];
    $scope.list_courier     = [];
    $scope.list_discount    = list_discount;
    $scope.list_value_type  = [{code:'percent', name:'percent'}, {code:'money', name:'money'}, {code:'relation', name:'relation'}];
    
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1
    };
    
    $scope.disabled = function(date, mode) {
        return ( mode === 'day' && ( date.getDay() === 0 || date.getDay() === 6 ) );
    };
    
    //Load Courier
    $http.get(ApiPath+'courier?limit=all').success(function(result){$scope.list_courier = result.data;});
    
      $scope.open = function($event,type) {
        $event.preventDefault();
        $event.stopPropagation();
        if(type == "from_date"){
            $scope.from_date_open = true;
        }else if(type == "to_date"){
            $scope.to_date_open = true;
        }
        
      };
            
    //List
    $scope.get_discount_config  = function(){
        $http({
            url: ApiPath+'discount-config/show/'+$scope.id_discount,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            result.data.from_date   = new Date(result.data.from_date*1000);
            result.data.to_date     = new Date(result.data.to_date*1000);
            $scope.discount_config  = result.data;
        }        
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    
    if($scope.id_discount > 0){
        $scope.get_discount_config();
    }else{
        $scope.discount_config['active']    = 1;
    }
    
    //List Type
    $scope.get_list_type  = function(){
        $http({
            url: ApiPath+'discount-config/type',
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.list_type = result.data;
        }        
        else{
            toaster.pop('warning', 'Thông báo', 'Không có dữ liệu!');
        }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    $scope.get_list_type();
    
    // get  discount type
    $scope.get_type = function(id_type){
        var selected = [];
        if(id_type > 0) {
          selected = $filter('filter')($scope.list_type,id_type);
        }
        return selected.length ? selected[0].name : 'Not set';
    }    
    
    //Save
    $scope.add = function(){
        var data_discount       = $scope.discount_config;
        data_discount.from_date = Date.parse(data_discount.from_date)/1000;
        data_discount.to_date   = Date.parse(data_discount.to_date)/1000;
        var link_url = '';
       
        if($scope.id_discount > 0){
            link_url = ApiPath+'discount-config/edit/'+$scope.id_discount;
        }else{
            link_url = ApiPath+'discount-config/create';
        }
        
        $http({
            url: link_url,
            method: "POST",
            data:data_discount,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                if((list_discount.length < 20) && (data_discount.courier_id == search_courier)){
                    var data_update = {'courier_id' : data_discount.courier_id, 'from_date' : data_discount.from_date, 'to_date' : data_discount.to_date, 'type_id':data_discount.type_id, discount_type:{'name':$scope.get_type(data_discount.type_id)}, 'code':data_discount.code, 'value_type':data_discount.value_type, 'value':data_discount.value, 'use_number':data_discount.use_number, 'active':data_discount.active };
                    
                    if($scope.id_discount > 0){
                        data_update['id']   = $scope.id_discount;
                        list_discount[items.index]  = data_update;
                    }else{
                        data_update['id']   = result.id;
                        list_discount.push(data_update);
                    }
                    
                    $scope.$emit('update');  // call to update to  CityCtrl
                }
                toaster.pop('success', 'Thông báo', 'Thành công!');
                $modalInstance.dismiss('cancel');
            }          
            else{
                toaster.pop('warning', 'Thông báo', result.message);
            }
        }).error(function (data, status, headers, config) {
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    }
    
}]);