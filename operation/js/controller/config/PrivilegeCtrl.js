"use strict";

angular.module('app')
.controller('PrivilegeCtrl', ['$scope','Privilege','toaster','$timeout', '$modal',   function ($scope, Privilege, toaster, $timeout, $modal){

	$scope.page 		= 1;
	$scope.item_page 	= 20;
	$scope.max_size     = 5;
	$scope.list_data    = [];
	$scope.load_process = false;
	$scope.privilege_selected = "";
	$scope.load_submit = false;
	$scope.load_remove = false;

	$scope.load = function (page){
		if(!page){
			page = 1;
		}
		$scope.load_process = true;
		Privilege.getPrivilege(null, page, $scope.item_page).then(function (resp){
			$scope.load_process  = false;
			if(resp.data.error){
				$scope.list_data = [];
			}else {
				$scope.totalItems = resp.data.total;
				$scope.list_data  = resp.data.data;
			}
		})
	}
	
	
	$scope.action_edit = function (item){
		$scope._selectedItem = item;
		$scope.selectedItem = angular.copy(item);
	}

	$scope.reset_form = function (){
		$scope.selectedItem = {
			group_privilege : {},
			active: 1
		}
		$scope.privilege_selected = "";
		$scope._selectedItem  = {};
	}

	$scope.change_privilege = function (privilege_selected){
		if(!$scope.selectedItem['group_privilege'][privilege_selected]){
			$scope.selectedItem['group_privilege'][privilege_selected] = {};
		}
		
	}

	$scope.save = function (){
		$scope.load_submit = true;
		Privilege.savePrivilege($scope.selectedItem.id || null, $scope.selectedItem)
		.then(function (resp){
			$timeout(function (){
				$scope.load_submit = false;
			})
			
			if(!resp.data.error){
				if(!$scope.selectedItem.id){
					$scope.list_data.push(resp.data.data);
					toaster.pop('success', 'Thông báo', 'Thêm mới thành công !');
				}else {
					$scope.list_data[$scope.list_data.indexOf($scope._selectedItem)] = $scope.selectedItem;
					toaster.pop('success', 'Thông báo', 'Cập nhật thành công !');
				}
				$scope.reset_form();
			}else {
				toaster.pop('warning', 'Thông báo', 'Lỗi kết nối máy chủ !');
			}
		})
	}

	$scope.remove = function (item, index){
		if(!confirm('Bạn muốn xóa quyền này ?')){
			return ;
		}

		$scope.load_remove = true;
		Privilege.removePrivilege(item.id)
			.then(function(resp){
				$scope.load_remove = false;
				if(!resp.data.error){
					
					$scope.list_data.splice(index, 1);
					toaster.pop('success', 'Thông báo', 'Xóa thành công quyền ' + item.description);
				}else {
					toaster.pop('warning', 'Thông báo', 'Lỗi kết nối máy chủ !');
				}
				return;
			})
	}



	// start 
	$scope.load();
	$scope.reset_form();
}]);

