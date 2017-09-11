"use strict";

angular.module('app')
.controller('PipeStatusSellerCtrl', ['$scope','Privilege', 'Pipe', 'toaster','$timeout', '$modal',   function ($scope, Privilege, Pipe, toaster, $timeout, $modal){

	$scope.page 		= 1;
	$scope.item_page 	= 10;
	$scope.max_size     = 5;
	$scope.list_data    = [];

	$scope.load_process = false;
	$scope.load_submit 	= false;
	$scope.load_remove 	= false;

	$scope.load = function (page){
		if(!page){
			page = 1;
		}
		$scope.load_process = true;
		Pipe.getPipeStatusSeller(null, page, $scope.item_page).then(function (resp){
			$scope.load_process  = false;
			if(resp.data.error){
				$scope.list_data = [];
			}else {
				$scope.totalItems = resp.data.total;
				$scope.list_data  = resp.data.data;
			}
		})
	}
	
	$scope.load_group_status = function (){
		Pipe.getGroupProcessSeller()
		.then(function (resp){
			if(resp.data.error){
				$scope.list_groupstatus = [];
			}else {
				$scope.list_groupstatus = resp.data.data;
			}
		})

	}
	$scope.action_edit = function (item){
		$scope._selectedItem = item;
		$scope.selectedItem = angular.copy(item);
		$scope.group_status_selected	=  $scope.selectedItem.group.id;
	}


	$scope.reset_form = function (){
		$scope.selectedItem = {
		};

		$scope.group_status_selected = "";
		$scope._selectedItem  = {};
	}

	$scope.save = function (){
		$scope.load_submit = true;
		$scope.selectedItem.group = $scope.group_status_selected;

		Pipe.savePipeStatusSeller($scope.selectedItem.id || null, $scope.selectedItem)
		.then(function (resp){
			$timeout(function (){
				$scope.load_submit = false;
			})
			
			if(!resp.data.error){
				if(!$scope.selectedItem.id){
					$scope.list_data.push(resp.data.data);
					toaster.pop('success', 'Thông báo', 'Thêm mới thành công !');
				}else {
					$scope.list_data[$scope.list_data.indexOf($scope._selectedItem)] = resp.data.data;
					toaster.pop('success', 'Thông báo', 'Cập nhật thành công !');
				}
				$scope.reset_form();
			}else {
				toaster.pop('warning', 'Thông báo', resp.data.error_message);
			}
		})
	}

	$scope.remove = function (item, index){
		if(!confirm('Bạn muốn xóa trạng thái này ?')){
			return ;
		}
		$scope.load_remove = true;

		Pipe.removePipeStatusSeller(item.id)
			.then(function(resp){
				$scope.load_remove = false;
				if(!resp.data.error){
					
					$scope.list_data.splice(index, 1);
					toaster.pop('success', 'Thông báo', 'Xóa thành công nhóm ' + item.name);
				}else {
					toaster.pop('warning', 'Thông báo', 'Lỗi kết nối máy chủ !');
				}
				return;
			})
	}


	// start 
	$scope.load();
	$scope.load_group_status();
	$scope.reset_form();
}]);

