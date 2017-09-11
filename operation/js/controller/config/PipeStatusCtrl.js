"use strict";

angular.module('app')
.controller('PipeStatusCtrl', ['$scope','Privilege', 'PipeJourney', 'toaster','$timeout', '$modal', '$http',    function ($scope, Privilege, PipeJourney, toaster, $timeout, $modal, $http){

	$scope.page 		= 1;
	$scope.item_page 	= 10;
	$scope.max_size     = 5;
	$scope.list_data    = [];
	
	$scope._load_process = true;
	$scope.load_submit 	= false;
	
	 $scope.current_tab_type = 1;

	$scope.load = function (type){
		$scope._load_process = true;
		$scope.current_tab_type = type;

        if(type == 1){
            $scope.list_data = $scope.group_status;
        }else if(type == 2){
            if($scope.list_process_2 != undefined){
                $scope.list_data = $scope.list_process_2;
            }else{
                $scope.list_data = {};
            }
        }else if(type == 3){
            if($scope.list_process_3 != undefined){
                $scope.list_data = $scope.list_process_3;
            }else{
                $scope.list_data = {};
            }
        }else if(type == 4){
            if($scope.list_process_4 != undefined){
                $scope.list_data = $scope.list_process_4;
            }else{
                $scope.list_data = {};
            }
        }else if(type == 5){
            if($scope.list_process_5 != undefined){
                $scope.list_data = $scope.list_process_5;
            }else{
                $scope.list_data = {};
            }
        }else if(type == 10){
			if($scope.list_process_10 != undefined){
				$scope.list_data = $scope.list_process_10;
			}else{
				$scope.list_data = {};
			}
		}else if(type == 11){
			if($scope.list_process_11 != undefined){
				$scope.list_data = $scope.list_process_11;
			}else{
				$scope.list_data = {};
			}
		}else if(type == 12){
			if($scope.list_process_12 != undefined){
				$scope.list_data = $scope.list_process_12;
			}else{
				$scope.list_data = {};
			}
		}else if(type == 13){
			if($scope.list_process_13 != undefined){
				$scope.list_data = $scope.list_process_13;
			}else{
				$scope.list_data = {};
			}
		}


        $scope._load_process = false;
	}
	
	$scope.change_tab = function (type){
		$scope.list_data    = [];
		$scope.load(type);
	}

	$scope.action_edit = function (item){
		$scope._selectedItem = item;
		$scope.selectedItem = angular.copy(item);
		$scope.group_status_selected	=  $scope.selectedItem.group_status.id;
	}


	$scope.reset_form = function (){
		$scope.selectedItem = {
		};

		$scope.group_status_selected = "";
		$scope._selectedItem  = {};
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
		$scope.selectedItem.group_status = $scope.group_status_selected;

		PipeJourney.savePipeStatus($scope.selectedItem.id || null, $scope.selectedItem)
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

	$scope.openSaveModal = function (item, value){
		if(item == undefined || !item){
			return;
		};
        var data = [];
        if($scope.current_tab_type == 1){
            data    = $scope.list_process_order[item];
        }else if($scope.current_tab_type == 2){
            data    = $scope.list_merchant[item];
        }else if($scope.current_tab_type == 3){
            data    = $scope.list_merchant_vip[item];
        }else if($scope.current_tab_type == 4){
            data    = $scope.list_address[item];
        }else if($scope.current_tab_type == 5){
            data    = $scope.list_problem_order[item];
        }else if($scope.current_tab_type == 10){
			data    = $scope.list_boxme_order[item];
		}else if($scope.current_tab_type == 11){
			data    = $scope.list_boxme_uid[item];
		}else if($scope.current_tab_type == 12){
			data    = $scope.list_boxme_shipment[item];
		}else if($scope.current_tab_type == 13){
			data    = $scope.list_boxme_problem[item];
		}

        var modalInstance = $modal.open({
            templateUrl: 'PipeStatusSave.html',
            controller: 'PipeStatusSaveCtrl',
            size: 'lg',
            resolve: {
                item: function (){
            		return item;
            	},
            	type: function (){
            		return $scope.current_tab_type;
            	},
                name: function (){
                    return   value;
                },
                list_process : function(){
                    return data;
                }
            }
        });

        modalInstance.result.then(function (result) {
           return;
        });
    }


    $scope.openCreateGroupProcessUser  = function (){
    	var modalInstance = $modal.open({
            templateUrl: 'CreateGroupProcessUserModal.html',
            controller: function ($scope, $http, $modalInstance, type, data){
            	$scope.selectedItem = {};
            	$scope.add_process  = false;
                $scope.data         = data;

            	$scope.close = function (){
            		$modalInstance.close();
            	}

            	$scope.create  = function (item){
        			var url = ApiPath + 'pipe-status/save-process';
        			item.type = type;
					$http.post(url, item).success(function (resp){
						if(!resp.error){
                            item.id = resp.data;
                            $scope.data[item.code]  = item.name;
							toaster.pop('success', 'Thông báo', "Thêm thành công");
						}else {
							toaster.pop('warning', 'Thông báo', resp.error_message);
						}
					});
            	}
            },
            size: 'md',
            resolve: {
            	type: function (){
            		return $scope.current_tab_type;
            	},
                data : function(){
                    return $scope.list_data;
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
	$scope.load(1);
	$scope.reset_form();

}]);

angular.module('app').controller('PipeStatusSaveCtrl',['$scope','item', 'name', 'type', 'list_process', 'PipeJourney', '$q', 'toaster', '$modalInstance', '$filter',
    function ($scope, item, name, type, list_process, PipeJourney, $q, toaster, $modalInstance, $filter){
        if(list_process){
            $scope.list_proccess    = list_process;
        }else{
            $scope.list_proccess    = [];
        }

        $scope.name             = name;

        $scope.ItemAdds = [];
        $scope.add_process  = false;
        $scope.estimates = [

        ];
        for (var i = 1; i < 97; i++) {
            $scope.estimates.push({value: i, text: i+' tiếng'});
        };
		action_add
	$scope.showEstimate = function (pipe){
		var selected = $filter('filter')($scope.estimates, {value: pipe.estimate_time});
   		return (pipe.estimate_time && selected.length) ? selected[0].text : 'Chưa cấu hình';
	}

	$scope.action_add = function (item, index){
		$scope.add_process  = true;
		PipeJourney.savePipeStatus(null, item)
		.then(function (resp){
			$scope.add_process  = false;
			if(resp.data.error){
				toaster.pop('warning', 'Thông báo', resp.data.error_message);
			}else {
                item.id = 1*resp.data.data;
				toaster.pop('success', 'Thông báo', "Thêm thành công");
			}
		})
	}

	$scope.savePipe = function (item, fields, value){
		if(!value){
            if(fields == 'active'){
                toaster.pop('warning', 'Thông báo', "Dữ liệu không được để trống !");
            }
            return 'Dữ liệu không được để trống !';
		}
		var data = {};
		data[fields] = value;
		var defer  = $q.defer();

		PipeJourney.savePipeStatus(item.id, data).then(function (resp){
			if(resp.data.error){
                if(fields == 'active'){
                    toaster.pop('warning', 'Thông báo', resp.data.error_message);
                }
				defer.resolve(resp.data.error_message);
			}else {
				defer.resolve();
				toaster.pop('success', 'Thông báo', "Cập nhật thành công !");
			}
		});
		return defer.promise;
	}

	$scope.close = function (){
		$modalInstance.close('');
	}


	
}])