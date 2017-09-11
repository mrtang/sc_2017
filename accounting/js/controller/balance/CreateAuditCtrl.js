'use strict';

angular.module('app').controller('CreateAuditCtrl', ['$scope', '$http', 'toaster', 'PhpJs', 'Api_Path',
 	function($scope, $http, toaster, PhpJs, Api_Path) {
    // config

        $scope.list_respond = [];
        $scope.run          = false;
        $scope.time         = {time_end : ''};
        $scope.frm          = {time_end : ''};
        

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time"){
                $scope.time = true;
            }
        };

        // action
        $scope.refresh = function(){
            if($scope.time.time_end != undefined && $scope.time.time_end != ''){
                $scope.frm.time_end   = +PhpJs.date_format('d', $scope.time.time_end)+'-'+PhpJs.date_format('m', $scope.time.time_end)+'-'+PhpJs.date_format('Y', $scope.time.time_end);
            }else{
                $scope.frm.time_end   = '';
            }
        }

        $scope.Action = function(){
            if($scope.run && $scope.frm.time_end != ''){
                $http({
                    url: Api_Path.Acc+'merchant/handling?time_start='+$scope.frm.time_end,
                    method: "GET",
                    dataType: 'json',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).success(function (result, status, headers, config) {
                    if($scope.list_respond.length > 20){
                        $scope.list_respond = [];
                    }

                    $scope.list_respond.push(result.data);

                    if(!result.error){
                        $scope.Action();
                    }
                    else{
                        $scope.run  = !$scope.run;
                        toaster.pop('warning', 'Thông báo', result.message);
                    }
                }).error(function (data, status, headers, config) {
                    $scope.Action();
                    toaster.pop('error', 'Thông báo', 'Lỗi kết nối dữ liệu, hãy thử lại!');
                });
            }
        }

        $scope.ActionRun = function(){
            $scope.refresh();

            if($scope.frm.time_end != ''){
                $scope.run  = !$scope.run;
                if($scope.run){
                    $scope.Action();
                }
            }
        }
    }
]);
