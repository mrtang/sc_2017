"use strict";

angular.module('app')
.controller('GroupUserCtrl', ['$scope','Privilege','toaster','$timeout', '$modal',   function ($scope, Privilege, toaster, $timeout, $modal){

	$scope.page 		= 1;
	$scope.item_page 	= 20;
	$scope.max_size     = 5;
	$scope.list_data    = [];
	$scope._load_process = true;
	$scope.privilege_selected = "";
	$scope.load_submit = false;
	$scope.load_remove = false;

	$scope.load = function (page){
		if(!page){
			page = 1;
		}
		$scope._load_process = true;
		Privilege.getGroupUser(null, page, $scope.item_page).then(function (resp){
			$scope._load_process  = false;
			if(resp.data.error){
				$scope.list_data = [];
			}else {
				$scope.totalItems = resp.data.total;
				$scope.list_data  = resp.data.data;
			}
		})
	}
	
	$scope.load_privilege = function (){
		Privilege.getPrivilege('','',100)
		.then(function (resp){
			if(resp.data.error){
				$scope.list_privilege = [];
			}else {

				$scope.list_privilege = resp.data.data;
			}
		})

	}

	$scope.action_edit = function (item){
		$scope._selectedItem = item;
		$scope.selectedItem  = angular.copy(item);
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

	$scope.change_status = function (item){
		Privilege.saveGroupUser(item.id, {active: item.active})
		.then(function (resp){
			if(!resp.data.error){
				toaster.pop('success', 'Thông báo', 'Cập nhật thành công !');
			}else {
				toaster.pop('warning', 'Thông báo', 'Lỗi kết nối máy chủ !');
			}
		})
	}
	$scope.save = function (){
		$scope.load_submit = true;
		Privilege.saveGroupUser($scope.selectedItem.id || null, $scope.selectedItem)
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
		if(!confirm('Bạn muốn xóa nhóm thành viên này ?')){
			return ;
		}
		$scope.load_remove = true;
		Privilege.removeGroupUser(item.id)
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

	$scope.openSaveModal = function (item){
		if(!item){
			item = {
				group_privilege: {}
			}
		};
        var modalInstance = $modal.open({
            templateUrl: 'GroupUserSave.html',
            controller: 'GroupSaveCtrl',
            size: 'lg',
            resolve: {
                item : function (){
                    return item;
                },
                list_privilege: function(){
                	return $scope.list_privilege;
                }
            }
        });

        modalInstance.result.then(function (result) {
        	if(result.action == 'add'){
        		$scope.list_data.push(result.data);
        	}else if(result.action == 'update'){
    			$scope.list_data[$scope.list_data.indexOf(item)] = result.data;
        	}
        }, function () {
        });
    }

	

	// start 
	$scope.load();
	$scope.load_privilege();
	$scope.reset_form();
}]);

angular.module('app').controller('GroupSaveCtrl', ['$scope', '$modalInstance', 'item', 'Privilege', 'toaster','list_privilege','$timeout',   function ($scope, $modalInstance, item, Privilege, toaster, list_privilege, $timeout){
    $scope.selectedItem = angular.copy(item);
    
    $scope.data = {};
    $scope.list_privilege = list_privilege;


    $scope.save = function (){
		$scope.load_submit = true;
		Privilege.saveGroupUser($scope.selectedItem.id || null, $scope.selectedItem)
		.then(function (resp){
			$timeout(function (){
				$scope.load_submit = false;
			})
			
			if(!resp.data.error){
				if(!$scope.selectedItem.id){

					$modalInstance.close({
						'action' : 'add',
						'data'	 : resp.data.data
					});

					toaster.pop('success', 'Thông báo', 'Thêm mới thành công !');
				}else {
					$modalInstance.close({
						'action' : 'update',
						'data'	 : $scope.selectedItem
					});
					//$scope.list_data[$scope.list_data.indexOf($scope._selectedItem)] = $scope.selectedItem;
					toaster.pop('success', 'Thông báo', 'Cập nhật thành công !');
				}
				
			}else {
				toaster.pop('warning', 'Thông báo', 'Lỗi kết nối máy chủ !');
			}
		})
	}


    $scope.close = function (){
        $modalInstance.dismiss();
    }
}])

;

