'use strict';
angular.module('app')
.controller('LogChangeLadingCtrl', ['$scope', '$http',
function($scope, $http) {
	$scope.currentPage  = 1;
    $scope.item_page    = 20;
    $scope.sc_code      = '';
    $scope.totalItems   = 0;
    $scope.maxSize      = 5;
    $scope.listData     = [];
    $scope.order        = [];
    $scope.stateLoading = false;
    // List

    $scope.keys = function(obj){
        return obj? Object.keys(obj) : [];
    }

    $scope.type = {
        status              : 'Trạng thái',
        protect             : 'Bảo hiểm',
        description         : 'Mô tả',
        total_weight        : 'Khối lượng',
        service_id          : 'Dịch vụ',
        sc_pvk              : 'Phí vk',
        sc_pvc              : 'Phí vc',
        leatime_delivery    : 'Thời gian giao dự tính',
        courier_id          : 'Hãng vận chuyển',
        sc_cod              : 'Phí CoD',
        sc_pbh              : 'Phí bảo hiểm',
        money_collect       : 'Thu hộ',
        from_address_id     : 'Địa chỉ người gửi',
        to_address_id       : 'Địa chỉ người nhận',
        to_phone            : 'Điện thoại người nhận',
        to_name             : 'Tên người nhận'
    };


    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.listData     = [];
        $scope.order        = [];
        $scope.user         = {}
        $scope.stateLoading = true;

        var url = ApiOps+'log/change-order?page='+page;

        if($scope.sc_code != undefined && $scope.sc_code != ''){
            url += '&sc_code='+$scope.sc_code;
        }

    	$http({
            url: url,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData = result.data;
            $scope.order    = result.order;
            $scope.user     = result.user;
            //
		    $scope.totalItems = result.total;
			$scope.maxSize = 5;
            $scope.item_stt = $scope.item_page * (page - 1);
        }

        $scope.stateLoading = false;
        });
    };
}]);