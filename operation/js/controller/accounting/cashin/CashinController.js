'use strict';
angular.module('app')
.controller('CashinController', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox','FileUploader', '$timeout', '$q',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox,FileUploader, $timeout, $q) {
	//
    $scope.isShowing = false;
    $scope.currentPage = 1;
    $scope.item_page = 20;
    $scope.stateLoading = true;
    $scope.postData = {};

    $scope.statistic = function() {
        var from_date = '';
        var to_date = '';
        if($scope.from_date != undefined && $scope.from_date !='') {
            from_date = Date.parse($scope.from_date)/1000;
        }
        if($scope.to_date != undefined && $scope.to_date !='') {
            to_date = Date.parse($scope.to_date)/1000;
        }
        $http({
            url: ApiPath + 'cashin/report?from_date='+from_date+'&to_date='+to_date,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
            $scope.orderCreated = result.orderCreated;
            $scope.orderSuccess = result.orderSuccess;
            $scope.orderPaid = result.orderPaid;
            $scope.orderAmount = result.orderAmount;
            $scope.orderBuyAmount = result.orderBuyAmount;
        });
    }
    $scope.showStatistic = function() {
        $scope.statistic();
        $scope.isShowing = true;
    }
    $scope.showData = function() {
        $scope.isShowing = ($scope.isShowing) ? false : true;
    }

	$scope.setPage = function (currentPage, email, transaction_id, refer_code, status, from_date, to_date, type){
        $scope.stateLoading = true;
        $scope.listData = [];
        currentPage     = (currentPage)                 ? currentPage   : 1;
        email           = (email == undefined)          ? ''            : email;
        transaction_id  = (transaction_id == undefined) ? ''            : transaction_id;
        refer_code      = (refer_code == undefined)     ? ''            : refer_code;
        status          = (status == undefined)         ? ''            : status;
        type            = (type == undefined)           ? ''            : type;

        from_date       = (from_date == undefined)      ? ''            : Date.parse(from_date)/1000;
        to_date         = (to_date == undefined)        ? ''            : Date.parse(to_date)/1000;

        $http({
            url: ApiPath + 'cashin/listcashin?page=' + currentPage + '&email=' + email + '&transaction_id=' + transaction_id + '&refer_code=' + refer_code +'&status=' + status + '&from_date=' + from_date + '&to_date=' + to_date + '&type=' + type,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
            $scope.stateLoading = false;
            if(!result.error){
                $scope.listData = result.data;

                // Page navigation  
                $scope.totalItems = result.total;
                $scope.maxSize = 5;
                $scope.item_page = result.item_page;
                $scope.item_stt = $scope.item_page * (currentPage - 1);
            }        
            else{
                $scope.totalItems = 0;
            }
        });
    }

    $scope.checkStatus = function (status){
        switch(status){
            case 'WAITING': 
                return 'Chưa thanh toán';
            break;
            case 'PROCESSING': 
                return 'Đã thanh toán';
            break;
            case 'SUCCESS': 
                return 'Thành công';
            break;
            case 'CANCEL': 
                return 'Đã hủy';
            break;
        }
    }

    $scope.exportExcel = function (email, transaction_id, refer_code, status, from_date, to_date){
        email           = (email == undefined)          ? ''            : email;
        transaction_id  = (transaction_id == undefined) ? ''            : transaction_id;
        refer_code      = (refer_code == undefined)     ? ''            : refer_code;
        status          = (status == undefined)         ? ''            : status;
        from_date       = (from_date == undefined)      ? ''            : Date.parse(from_date)/1000;
        to_date         = (to_date == undefined)        ? ''            : Date.parse(to_date)/1000;

        return  ApiPath + 'cashin/exportexcelcashin?email=' + email + '&transaction_id=' + transaction_id + '&refer_code=' + refer_code +'&status=' + status + '&from_date=' + from_date + '&to_date=' + to_date;
    }

    
    $scope.createCashin = function (data){
        $http({
            url: ApiPath + 'cashin/createcashin',
            method: "POST",
            data: data,
            dataType: 'json',
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thêm thành công !');
                $state.go('delivery.accounting.cashin.show');
            }else {
                if(result.message == 'USER_NOT_FOUND'){
                    toaster.pop('error', 'Thông báo', 'Email người dùng không tồn tại !');

                }else if(angular.isObject(result.message)){
                    toaster.pop('error', 'Thông báo', 'Kiểm tra lại các trường !');
                }else {
                    if(result.message == 'DUPLICATE_TRANSACTION'){
                        toaster.pop('error', 'Thông báo', 'Mã giao dịch đã tồn tại !');
                    }
                    
                }
            }
        });
    }

    $scope.editCashin = function (item, data, field){
        var d = $q.defer();
        if(data == ""){
            toaster.pop('error', 'Thông báo', 'Dữ liệu không đựơc trống !');
            return;
        }
        

        $http({
            url: ApiPath+'cashin/updatecashinbyfield',
            method: "POST",
            data: {
                'id'      : item.id,
                'field'   : field,
                'data'    : data
            },
            dataType: 'json',
        }).success(function (result, status, headers, config) {

            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thêm thành công !');
                if(field == 'transaction_id'){
                    item.status = 'PROCESSING';
                }
                d.resolve()
            }else {
                /*toaster.pop('error', 'Thông báo', 'Lỗi !');*/
                if(result.message == 'DUPLICATE_TRANSACTION'){
                    d.reject('Mã giao dịch đã tồn tại !');
                }else {
                    d.reject('Lỗi server !');
                }
                
            }
        });
        return d.promise;
    }


    


    $scope.openModal = function (size, item) {
        $modal.open({
            templateUrl: 'EditCashinPopup.html',
            size:size,
            controller: 'CashinEditController',
            resolve: {
                item: function () {
                    return item;
                }
            }
        });
    };



    // DATE picker
    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1
    };
    
    // $scope.disabled = function(date, mode) {
    //     return ( mode === 'day' && ( date.getDay() === 0 || date.getDay() === 6 ) );
    // };

    $scope.open = function($event,type) {
        $event.preventDefault();
        $event.stopPropagation();
        if(type == "from_date"){
            $scope.from_date_open = true;
        }else if(type == "to_date"){
            $scope.to_date_open = true;
        }
    };

    //huy van don
    $scope.cancelCashin = function(id){
        $http({
            url: ApiPath+'cashin/cancelcashin',
            method: "POST",
            data: {'id':id},
            dataType: 'json',
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Bạn đã huỷ thành công !');
            }else {
                toaster.pop('error', 'Thông báo', 'Lỗi không cập nhật!');
            }
        });
    }


    $scope.setPage();
}]);

angular.module('app')
.controller('CashinEditController', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox','FileUploader', 'item',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox,FileUploader, item) {
    $scope.item = item;

    $scope.updateCashin = function (){

        $http({
            url: ApiPath+'cashin/updatecashin',
            method: "POST",
            data: item,
            dataType: 'json',
        }).success(function (result, status, headers, config) {
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Thêm thành công !');
                $state.go('shipchung.accounting.cashin.show');
            }else {
                if(result.message == 'USER_NOT_FOUND'){
                    toaster.pop('error', 'Thông báo', 'Email người dùng không tồn tại !');

                }else if(angular.isObject(result.message)){
                    toaster.pop('error', 'Thông báo', 'Kiểm tra lại các trường !');
                }else {
                    toaster.pop('error', 'Thông báo', 'Lỗi !');
                }
            }
        });
    }


}])

