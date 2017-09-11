'use strict';

//Verify Upload Money Collect
angular.module('app').controller('CSUploadCtrl', ['$scope', '$state', '$rootScope', '$filter', 'FileUploader', 'toaster', 'Api_Path',
 	function($scope, $state, $rootScope, $filter, FileUploader, toaster, Api_Path) {
        $scope.waiting              = true;
        $scope.list_data            = {};
        $scope.time_create          = new Date(date.getFullYear(), date.getMonth(), 1);

        $scope.__get_time = function(){
            if($scope.time_create != undefined && $scope.time_create != ''){
                return +Date.parse($scope.time_create)/1000;
            }else{
                return '';
            }
        }

        // upload  excel
        var uploader = $scope.uploader = new FileUploader({
            url                 : Api_Path.Upload+'upload?type=kpi_cs&time='+$scope.__get_time(),
            headers             : {Authorization : $rootScope.userInfo.token,Location : $rootScope.userInfo.country_id},
            removeAfterUpload   : true
        });

        // FILTERS
        uploader.filters.push({
            name: 'excelFilter',
            fn: function(item /*{File|FileLikeObject}*/, options) {
                if($scope.time_create == undefined || $scope.time_create == ''){
                    return false;
                }

                var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
                return '|vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet|'.indexOf(type) !== -1;
            }
        });

        uploader.onBeforeUploadItem = function(item) {
            item.url = Api_Path.Upload+'upload?type=kpi_cs&time='+$scope.__get_time();
        };

        uploader.onSuccessItem = function(item, result, status, headers){
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Upload Thành công!');
            }
            else{
                toaster.pop('warning', 'Thông báo', result.data.message);
            }
            $scope.waiting              = false;
        };

        uploader.onErrorItem  = function(item, result, status, headers){
            toaster.pop('error', 'Thông báo!', "Lỗi kết nối dữ liệu, hãy thử lại !");
        };
    /*
        End Config
     */

    }
]);
