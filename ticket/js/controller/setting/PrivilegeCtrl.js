'use strict';
//
var listResult = [];
angular.module('app').controller('PrivilegeCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster','bootbox',
function($scope, $modal, $http, $state, $window, toaster,bootbox) {
	//
	$scope.currentPage = 1;
    $scope.item_page = 20;
    // List 
    $scope.setPage = function(){
    	$http({
            url: ApiPath+'user-privilege?page='+$scope.currentPage,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listResult = result.data;
            //
		    $scope.totalItems = result.total;
			$scope.maxSize = 5;
            $scope.item_page = result.item_page;
            $scope.item_stt = $scope.item_page * ($scope.currentPage - 1);
        }        
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
        });
    };
    $scope.setPage();
    //Xoa 
    $scope.delPrivilege = function(id,item){
        bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
            if(result){
            	$http({
                    url: ApiPath+'user-privilege/destroy/'+id,
                    method: "get",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
        			if(!result.error){
          				$scope.listResult.splice(item, 1); 
        				toaster.pop('success', 'Thông báo', 'Thành công!');
        			}	       
        			else{
        				toaster.pop('error', 'Thông báo', 'Bạn không thể xoá!');
        			}
                });
            }
        });
    };
    //update
    $scope.updateInfo = function(data,field,id){
        var myData = {};
        myData[field] = data;
        $http({
            url: ApiPath+'user-privilege/edit/'+id,
            method: "POST",
            data:myData,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Không thể cập nhật dữ liệu!');
            }
        });
    }

}]);
angular.module('app').controller('ModalCreatePrivilege', ['$scope', '$modal', function($scope, $modal) {
    $scope.open = function (size) {
        $modal.open({
            templateUrl: 'ModalPrivilege.html',
            controller: 'ActionPrivilegeCtrl',
            size:size
        });
    };
}]);
angular.module('app').controller('ActionPrivilegeCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster',
function($scope, $modal, $http, $state, $window, toaster) {
	//Them moi 
    $scope.saveBtn = function (data) {
        $http({
            url: ApiPath+'user-privilege/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
                listResult.unshift({'id' : result.id, 'privilege_name' : data.name,'privilege_code' : data.code});
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Bạn không thể tạo mới!');
            }
        });
    };

}]);