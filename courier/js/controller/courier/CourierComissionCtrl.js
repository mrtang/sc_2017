'use strict';
//List
angular.module('app')
.controller('CourierCommissionCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster', 'bootbox',
 	function($scope, $modal, $http, $state, $window, toaster, bootbox) {
 		$scope.currentPage = 1;
	    $scope.item_page = 20;
	    // List Postman
	    $scope.setPage = function(){
	    	$http({
	            url: ApiPath+'courier-comission?page='+$scope.currentPage,
	            method: "GET",
	            dataType: 'json',
	            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
	        }).success(function (result, status, headers, config) {
		        if(!result.error){
		            $scope.listData = result.data;
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
	    // editable
	    $scope.updateCommission = function(data,field,id) {
	        var myData = {};
	        myData[field] = data;
	        $http({
	            url: ApiPath+'courier-comission/edit/'+id,
	            method: "POST",
	            data:myData,
	            dataType: 'json'
	        }).success(function (result, status, headers, config) {
	            if(!result.error){
	                toaster.pop('success', 'Thông báo', 'Thành công!');
	                window.location.reload();
	            }          
	            else{
	                toaster.pop('error', 'Thông báo', 'Không thể cập nhật dữ liệu!');
	            }
	        });
	    };
	    //Xoa postman
	    $scope.delCommission = function(id,item){
	    	bootbox.confirm("Bạn chắc chắn muốn xóa ?", function (result) {
	    		if(result){
			    	$http({
			            url: ApiPath+'courier-comission/destroy/'+id,
			            method: "get",
			            dataType: 'json',
			            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			        }).success(function (result, status, headers, config) {
						if(!result.error){
			  				$scope.listData.splice(item, 1); 
							toaster.pop('success', 'Thông báo', 'Thành công!');
						}	       
						else{
							toaster.pop('error', 'Thông báo', 'Không thể xoá dữ liệu!');
						}
			        });
			    }
			});
	    };
	    //update status
	    $scope.updateStatus = function(data,field,id) {
	        var myData = {};
	        myData[field] = data;
	        $http({
	            url: ApiPath+'courier-comission/edit/'+id,
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
	        return;
	    };
	}
]);

//modal add
angular.module('app').controller('ModalCreateCommission', ['$scope', '$modal', function($scope, $modal) {
    $scope.open = function (size) {
        $modal.open({
            templateUrl: 'ModalCommission.html',
            controller: 'CourierPopUpCommissionCtrl',
            size:size,
            resolve: {
                items: function () {
                    return $scope.listCourier;
                }
            }
        });
    };
}]);
//

angular.module('app').controller('CourierPopUpCommissionCtrl', ['$scope','$http','$state','$window','toaster', 
 	function($scope,$http,$state,$window,toaster) {
    //Listcourier
    $http({
        url: ApiPath+'courier',
        method: "GET",
        dataType: 'json'
    }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listCourier = result.data;
        }          
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu!');
        }
    })
                 	  
	//Them moi 
    $scope.saveCommissionBtn = function (data) {
        $http({
            url: ApiPath+'courier-comission/create',
            method: "POST",
            data:data,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thành công!');
            }          
            else{
                toaster.pop('error', 'Thông báo', 'Bạn thêm mới thành công!');
            }
        })
    };
    
    $scope.commission = {};
    $scope.commission.from_date = new Date();
    $scope.commission.to_date   = new Date();
}]);

angular.module('app').controller('DatepickerCtrl', ['$scope', function($scope) {
    $scope.open = function($event) {
      $event.preventDefault();
      $event.stopPropagation();
      $scope.opened = true;
    };
    $scope.format = 'dd/MM/yyyy';
}]);