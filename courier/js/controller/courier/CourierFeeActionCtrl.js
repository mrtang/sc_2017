'use strict';
var BoxmePath    = 'http://10.0.3.20/boxme/api/'; 
var list_service = [];
//Courier
 angular.module('app')
 .controller('CourierFeeActionCtrl', ['$scope','$modal','$http','$state','$stateParams', '$window','toaster','bootbox', 
 	function($scope,$modal,$http,$state,$stateParams,$window,toaster,bootbox) {
    // config
    $scope.services         = [];
    $scope.listDistrict     = [];
    $scope.listCourier      = {};
    $scope.listService      = {};
    $scope.listArea         = {};
    
    $scope.liststate        = [{'id':1,'name':'pickup'},{'id':2,'name':'delivery'},{'id':3,'name':'return'}];
    
    $scope.CourierFeeId     = parseInt($stateParams.courier_fee_id);
    $scope.link_download    = BoxmePath+'download/Template_Courier_Fee.xls';
    
    //Load Courier
    $http.get(ApiPath+'courier').success(function(result){$scope.listCourier = result.data;});
    
    // Load Service
    $http.get(ApiPath+'courier-service').success(function(result){$scope.listService = result.data;});
   
    // Load Area
    $http.get(ApiPath+'courier-area').success(function(result){$scope.listArea = result.data;});
    
    // Load District by Area
    $scope.DistrictByArea = function (area) {
       $http.get(ApiPath+'area-location/locationbyarea/'+area).success(function(result){$scope.listDistrict = result.data;});
    }
    
    $scope.change_courier = function (){
        $scope.listDistrict = [];
    }
    
    //Them moi - sua xoa
    $scope.updateCourierFee = function (data) {
        $http({
            url: ApiPath+'courier-fee/' + ($stateParams.courier_fee_id > 0 ? 'edit/'+$stateParams.courier_fee_id : 'create'),
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error && result.message == 'success'){
                toaster.pop('success', 'Alert!', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    };
    
    //Xoa
    $scope.del = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'courier-fee/destroy/'+id+'?type=detail',
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.form.fee_detail.splice(item, 1); 
        				toaster.pop('success', 'Thông báo', 'Thành công!');
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
    
    //Update
    $scope.change = function(id,data,field) {
        var filed_caculate = ['money','surcharge'];
        if(+filed_caculate.indexOf(field) > 0){
            $scope.total_amount[id] = +$scope.money[id] + +($scope.money[id]*$scope.form.vat)/100 + +$scope.surcharge[id];
        }
        
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'courier-fee/edit/'+id+'?type=detail',
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
    
    if(parseInt($stateParams.courier_fee_id) > 0){
        $http({
            url: ApiPath+'courier-fee/show/'+$stateParams.courier_fee_id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.form = result.data;
                $scope.DistrictByArea($scope.form.from_area_id);
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    }
    
    $scope.$on('update',function(){
        $http({
            url: ApiPath+'courier-fee/show/'+$stateParams.courier_fee_id,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                $scope.form = result.data;
                $scope.DistrictByArea($scope.form.from_area_id);
            }          
            else{
                toaster.pop('error', 'Error!', "Error Server.");
            }
        });
    })
    
    // watch 
    //$scope.$watch('form.vat + form.money + form.surcharge', function (newValues, oldValues, scope) {
      //  $scope.form.total_amount = +$scope.form.money + +($scope.form.vat*$scope.form.money)/100 + +$scope.form.surcharge;
   // });
     // Popup Edit config
    $scope.open_popup = function (size) {
        $modal.open({
            templateUrl: 'ModalUploadFeeCtrl.html',
            controller: 'ModalUploadFeeCtrl',
            size:size,
            resolve: {
                items: function () {
                  return $scope.CourierFeeId
                }
            }
        });
    };
}]);

//Modal courier
angular.module('app').controller('ModalUploadFeeCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox', 'FileUploader', 'items',
function($scope, $modalInstance, $http, toaster, bootbox, FileUploader, items) {
    // config
    $scope.fee_id       = items;
    $scope.list_data    = {};
    $scope.list_status  = {'NOT ACTIVE':'Chưa cập nhật', 'SUCCESS':'Thành công', 'ERROR':'Cập nhật lỗi'};
    $scope.form         = {};
    
    $scope.id           = '';
    
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    
    // upload  excel
    var uploader = $scope.uploader = new FileUploader({
        url: ApiPath+'courier-fee/upload/'+$scope.fee_id
        });
        
    uploader.onSuccessItem = function(item, result, status, headers){
        if(!result.error){
            toaster.pop('success', 'Thông báo', 'Upload Thành công!');
            $scope.list_data    = result.data;
            
            angular.forEach($scope.list_data, function(value, key) {
              $scope.form[key]  = 1;
            });
            
            $scope.id           = result.id;
        }          
        else{
            toaster.pop('warning', 'Thông báo', 'Upload Thất bại!');
        }
    };
    
    uploader.onErrorItem  = function(item, result, status, headers){
        toaster.pop('error', 'Error!', "Error Server.");
    };
    
    // Action
    
    $scope.runAccept    = function(data){
        var data_update = [];
        angular.forEach(data, function(value, key) {
            if(value == 1){
                data_update.push(key);
            }
        });
        
        if(data_update){
            $scope.Accept(data_update,0)
        }
        return;
    }
    
    // Accept List Data
    $scope.Accept = function(data,index){
        var id = data[index];
        
        if(id){
            $http({
                url: ApiPath+'courier-fee/acceptfee/'+id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    $scope.Accept(data,+index+1);
                    toaster.pop('success', 'Thông báo', 'Thành công!');
                }          
                else{
                    toaster.pop('warning', 'Thông báo', 'Thất bại!');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }else{
            $http({
                url: ApiPath+'courier-fee/listdata/'+$scope.id,
                method: "GET",
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    $scope.list_data    = result.data;
                    $scope.$emit('update');
                }          
                else{
                    toaster.pop('warning', 'Thông báo', 'Load dữ liệu bại!');
                }
            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
            });
        }
            return;
    }
    
}]);
