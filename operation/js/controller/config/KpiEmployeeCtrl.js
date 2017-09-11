"use strict";

angular.module('app')
.controller('KpiEmployeeCtrl', ['$scope', '$localStorage', 'Base','$timeout', '$modal', 'Privilege',   function ($scope, $localStorage, Base, $timeout, $modal, Privilege){

	$scope.page 			= 1;
	$scope.item_page 		= 20;
	$scope.max_size     	= 5;
	$scope.frm				= {group : 1};
	$scope.waiting			= true;
	$scope.list_data    	= [];
	$scope.list_privilege 	= [];
	$scope.__privilege		= [];
	$scope.__list_user_admin = [];

	$scope._load_process = true;
	$scope.privilege_selected = "";
	$scope.load_submit = false;
	$scope.load_remove = false;

	$scope.setGroup = function(Key) {
		$scope.frm.group	= Key;
		$scope.list_data	= $scope.__list_user_admin[Key];
		return;
	};

	if($localStorage['user_admin'] == undefined){
		Base.user_admin().then(function (resp){
			if(!resp.data.error){
				$localStorage['user_admin'] = resp.data.data;
				angular.forEach(resp.data.data, function(value) {
					if($scope.__list_user_admin[value.group] == undefined){
						$scope.__list_user_admin[value.group] = [];
					}
					$scope.__list_user_admin[value.group].push(value);
				});
				$scope.setGroup($scope.frm.group);
			}
		})
	}else{
		angular.forEach($localStorage['user_admin'], function(value) {
			if($scope.__list_user_admin[value.group] == undefined){
				$scope.__list_user_admin[value.group] = [];
			}
			$scope.__list_user_admin[value.group].push(value);
		});
		$scope.setGroup($scope.frm.group);
	}

	if($localStorage['group_user'] == undefined){
		Privilege.getGroupUser(null, 0, 100).then(function (resp){
			if(!resp.data.error){
				$scope.list_privilege 		= resp.data.data;
				$localStorage['group_user'] = resp.data.data;
				angular.forEach(resp.data.data, function(value) {
					$scope.__privilege[value.id]	= value.name;
				});

			}
		})
	}else{
		$scope.list_privilege = $localStorage['group_user'];
		angular.forEach($localStorage['group_user'], function(value) {
			$scope.__privilege[value.id]	= value.name;
		});
	}


	$scope.openEditModal = function (item){
		var modalInstance = $modal.open({
			templateUrl: 'FrmEmployee.html',
			controller: 'FrmEmployeeCtrl',
			resolve: {
				item : function(){
					return item;
				}
			}
		});

		modalInstance.result.then(function (result) {

		}, function () {
		});
	}
}]);

angular.module('app').controller('FrmEmployeeCtrl', ['$scope', '$modalInstance', '$q', 'item', 'KPI',
	function ($scope, $modalInstance, $q, item, KPI){
		$scope.data 			= {};
		$scope.item				= item;
		$scope.list_config		= [];
		$scope.list_category 	= [];

		KPI.Config({'user_id' : item.user_id}).then(function (resp){
			if(!resp.data.error){
				angular.forEach(resp.data.data, function(value) {
					$scope.list_config[1*value.category_id] = 1*value.active;
				});
				$scope.__get_category();
			}
		});

		$scope.__get_category = function(){
			KPI.Category({group : item.group, active : 1}).then(function (resp){
				if(!resp.data.error){
					angular.forEach(resp.data.data, function(value) {
						if($scope.list_config[value.id] != undefined && $scope.list_config[value.id] == 1){
							value.active = 1;
						}else{
							value.active = 0;
						}
						$scope.list_category.push(value);
					});
				}
			});
		}

		$scope.saveCategory = function (it, fields, value){
		if(!value){
			return 'Dữ liệu không được để trống !';
		}
		var data = {category_id : it.id, user_id : item.user_id};
		data[fields] = value;
		var defer  = $q.defer();
		KPI.CreateConfig(data).then(function (resp){
			if(resp.data.error){
				defer.resolve(resp.data.error_message);
			}else {
				defer.resolve();
			}
		});
		return defer.promise;
	}


    $scope.close = function (){
        $modalInstance.dismiss();
    }
}]);

