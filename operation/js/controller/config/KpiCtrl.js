"use strict";

angular.module('app')
.controller('KpiCtrl', ['$scope','Base','toaster','$timeout', '$modal', 'Privilege',   function ($scope, Base, toaster, $timeout, $modal, Privilege){

	$scope.page 			= 1;
	$scope.item_page 		= 20;
	$scope.max_size     	= 5;
	$scope.frm				= {group : 1};
	$scope.waiting			= true;
	$scope.list_data    	= [];
	$scope.list_privilege 	= [];
	$scope.__privilege		= [];

	$scope._load_process = true;
	$scope.privilege_selected = "";
	$scope.load_submit = false;
	$scope.load_remove = false;

	$scope.setGroup = function(Key) {
		$scope.frm.group	= Key;
		$scope.load(Key);
	};

	Privilege.getGroupUser(null, 0, 100).then(function (resp){
		if(!resp.data.error){
			$scope.load(1);
			$scope.list_privilege = resp.data.data;
			angular.forEach(resp.data.data, function(value) {
				$scope.__privilege[value.id]	= value.name;
			});

		}
	})

	$scope.load = function (group){
		$scope.waiting		= true;
		$scope.list_data 	= [];

		Base.kpi_category_group(group).then(function (resp){
			$scope.waiting		= false;
			if(!resp.data.error){
				$scope.list_data  = resp.data.data;
			}
		})
	}

	$scope.openSaveModal = function (group){
		var modalInstance = $modal.open({
			templateUrl: 'FrmCreateGroupCategory.html',
			controller: 'FrmCreateGroupCategoryCtrl',
			resolve: {
				group : function(){
					return group;
				},
				__privilege : function(){
					return $scope.__privilege;
				}
			}
		});

		modalInstance.result.then(function (result) {
			$scope.load(group);
		}, function () {
		});
	}

	$scope.openEditModal = function (item){
		var modalInstance = $modal.open({
			templateUrl: 'KPICategory.html',
			controller: 'KPICategoryCtrl',
			size: 'lg',
			resolve: {
				item: function (){
					return item;
				}
			}
		});

		modalInstance.result.then(function (result) {
			return;
		});
	}
}]);

angular.module('app').controller('FrmCreateGroupCategoryCtrl', ['$scope', '$modalInstance', 'group', '__privilege', 'KPI',
	function ($scope, $modalInstance, group, __privilege, KPI){
    $scope.data 		= {};
	$scope.__privilege 	= __privilege;
	$scope.item			= {'name'	: '', 'group' : group};


    $scope.create = function (item){
		$scope.load_submit = true;
		KPI.CreateGroup(item).then(function (resp){
			if(!resp.data.error){
				$modalInstance.close({
					'item' : item
				});
			}else {
				$scope.load_submit = false;
			}
		})
	}


    $scope.close = function (){
        $modalInstance.dismiss();
    }
}]);
angular.module('app').controller('KPICategoryCtrl', ['$scope', '$modalInstance', '$q', 'item', 'KPI',
	function ($scope, $modalInstance, $q, item, KPI){
		$scope.list_category 		= [];
		$scope.item					= item;

		KPI.Category({group_category_id : item.id}).then(function (resp){
			if(!resp.data.error){
				$scope.list_category  = resp.data.data;
			}
		});

		$scope.btn_add = function (){
			$scope.list_category.push({group_category_id : item.id, work_name : '', percent : 0, weight : 0, active : 1});
		}

		$scope.action_add = function (item, index){
			$scope.add_process  = true;
			KPI.CreateCategory(item).then(function (resp){
				$scope.add_process  = false;
				if(!resp.data.error){
					item.id = 1*resp.data.data;
				}
			})
		}

		$scope.saveCategory = function (item, fields, value){
			if(!value){
				return 'Dữ liệu không được để trống !';
			}
			var data = {id : item.id};
			data[fields] = value;
			var defer  = $q.defer();
			KPI.CreateCategory(data).then(function (resp){
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
	}
]);

