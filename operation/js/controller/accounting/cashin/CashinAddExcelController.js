'use strict';
angular.module('app')
.controller('CashinAddExcelController', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox','FileUploader', '$timeout', '$q',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox,FileUploader, $timeout, $q) {


    $scope.uploadProcessing = false;

    var uploader = $scope.uploader = new FileUploader({
        url                 : ApiPath+'cashin/upload',
        removeAfterUpload   : true,
    });
        
    // FILTERS

    uploader.filters.push({
        name: 'excelFilter',
        fn: function(item /*{File|FileLikeObject}*/, options) {
            var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
            return '|vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet|'.indexOf(type) !== -1;
        }
    });
    uploader.onProgressItem = function (){
        $scope.uploadProcessing = true;
    }

    uploader.onSuccessItem = function(item, result, status, headers){
        $scope.uploadProcessing = false;
        if(!result.error){
            toaster.pop('success', 'Thông báo', 'Tải lên Thành công !');
            $state.go('accounting.cashin.add-excel-list',{id:result.id});
        }          
        else{
            toaster.pop('warning', 'Thông báo', 'Tải lên thất bại !');
        }
    };
    
    uploader.onErrorItem  = function(item, result, status, headers){
        $scope.uploadProcessing = false;
        toaster.pop('error', 'Error!', "Lỗi server !.");
    };



}]);


angular.module('app')
.controller('CashinAddExcelActionController', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox','FileUploader', '$timeout', '$q',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox,FileUploader, $timeout, $q) {
    $scope.dynamic          = 0;
    $scope.listExcelLoading = true;




    $scope.setPage = function(){
        $http({
            url: ApiPath + 'cashin/listexcel/' + $stateParams.id,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {

        $scope.listExcelLoading = false;
        
        if(!result.error){
            $scope.list_data = [];
            
            for(var property in result.data){
                $scope.list_data.push(result.data[property]);
            }
            $scope.totalItems   = result.total;
        }
        else{
            toaster.pop('error', 'Thông báo', 'Không có dữ liệu trả ra!');
        }
        }).error(function (data, status, headers, config) {
            $scope.listExcelLoading = false;
            toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
        });
        return;
    };
    
    $scope.setPage();



    $scope.id = $stateParams.id;
    //
    $scope.action_update = function(data){
        

        if(Object.keys(data).length > 0){
            $scope.update_all   = true;
            $scope.update_multi(data ,0);
        }
        return;
    }


    $scope.status = function (status, item){
        switch(status){
            case "EMAIL_NOT_FOUND": 
                return 'Email người dùng không tồn tại';
            break;
            case "DUPLICATE_TRANSACTION" :
                return 'Mã giao dịch đã tồn tại';
            break;
            default: 
                return status;
            break;
        }
    }
    $scope.errors_item = [];
    $scope.update_multi = function(data, num){
        $scope.dynamic  = num;
        if(data[num] && Object.keys(data[num]).length > 0  ){//&& data[num].active !== 0
            if(data[num].active !== 0){

                $http({
                    url: ApiPath+'cashin/createcashin',
                    method: "POST",
                    data: {
                        "email"     : data[num].email,
                        "amount"    : data[num].amount,
                        "type"      : data[num].type,
                        "transaction_id" : data[num].transaction_id,
                        "refer_code" : data[num].refer_code
                    },
                    dataType: 'json',
                }).success(function (result, status, headers, config) {
                    if(!result.error){
                        $scope.list_data[num]['active'] = 3;
                    }else{
                        $scope.errors_item.push({index: num, transaction_id : data[num].transaction_id,  error: result.message})
                        $scope.list_data[num]['error'] = result.message;
                    }
                    $scope.update_multi(data,+ num + 1);
                });
            }else{
                $scope.update_multi(data, +num+1);
            }

        }else{
           $scope.update_all    = false;

           toaster.pop('success', 'Thông báo', 'Kết thúc !');


           $timeout(function (){
                var listStr = '';
                angular.forEach($scope.errors_item, function (value){
                    listStr += '<li >#' + value.index + ' - <strong>' + value.transaction_id  + '</strong> - '+ $scope.status(value.error) + '</li>';
                })
                bootbox.dialog({
                  title: "Thông báo",
                  message: '<h4 class="text-center">Thành công</h4> <p> Các giao dịch có lỗi khi tạo : </p><ul>'+listStr+'</ul>'
                });
          }, 1000);
           
        }
    }


}]);

