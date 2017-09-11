'use strict';

angular.module('app').controller('RefundCtrl', ['$scope', '$state', '$stateParams', '$rootScope', 'toaster', 'FileUploader', 'Refund', 'Api_Path', 'Config_Status',
 	function($scope, $state, $stateParams, $rootScope, toaster, FileUploader, Refund, Api_Path, Config_Status) {
        /*
         Config
         */
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.totalItems           = 0;

        $scope.dynamic              = 0;

        $scope.NewTotal             = 0;
        $scope.frm                  = {id : $stateParams.id, tab : 'ALL', type : 'refund'};
        $scope.data_sum             = 0;
        $scope.waiting              = true;

        $scope.list_status          = Config_Status.StatusVerify;
        $scope.keys = function(obj){
            return obj? Object.keys(obj) : [];
        }

        // upload  excel
        var uploader = $scope.uploader = new FileUploader({
            url                 : Api_Path.Acc+'cash-out/upload-refund',
            alias               : 'AccFile',
            headers             : {Authorization : $rootScope.userInfo.token},
            removeAfterUpload   : true
        });

        // FILTERS
        uploader.filters.push({
            name: 'excelFilter',
            fn: function(item /*{File|FileLikeObject}*/, options) {
                var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
                return '|vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet|'.indexOf(type) !== -1;
            }
        });

        uploader.onSuccessItem = function(item, result, status, headers){
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Upload Thành công!');
                $state.go('app.refund',{id:result.id});
            }
            else{
                toaster.pop('warning', 'Thông báo', 'Upload Thất bại!');
            }
        };

        uploader.onErrorItem  = function(item, result, status, headers){
            toaster.pop('error', 'Thông báo!', "Lỗi hãy thử lại.");
        };
        /*
         End Config
         */

        /*
         Action
         */
        $scope.$watch($stateParams,function(){
            if($stateParams.id != '' && $stateParams.id != undefined){
                $scope.refresh_data();
                $scope.load(1);
            }

        });

        $scope.load = function(page){
            $scope.currentPage = page;
            Refund.load_excel($scope.currentPage, $scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.NewTotal         = result.data.new_total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.data_sum         = result.data.data_sum;
                }
                $scope.waiting              = false;
                return;
            });
        }

        $scope.refresh_data    = function(){
            $scope.list_data    = {};
            $scope.item_stt     = 0;
            $scope.totalItems   = 0;
            $scope.data_sum     = 0;
            $scope.waiting      = true;
        }

        // Verify
        $scope.Verify = function(){
            Refund.verify($stateParams.id).then(function (result) {
                if($scope.status_error.indexOf(result.status) != -1){
                    $scope.Verify();
                }else{
                    if(!result.data.error){
                        toaster.pop('success', 'Thông báo', 'Thành công !');
                    }else{
                        toaster.pop('warning', 'Thông báo', result.data.message_error);
                    }

                    if(result.data.total > 0){

                        $scope.dynamic = (($scope.totalItems - result.data.total)*100/$scope.totalItems).toFixed(2);
                        $scope.Verify();
                    }else{
                        toaster.pop('success', 'Thông báo', 'Kết thúc !');
                        $scope.dynamic = 100;
                        $scope.refresh_data();
                        $scope.load(1);
                    }
                }
                return;
            });
        }

        $scope.ChangeTab = function(cou){
            $scope.refresh_data();
            $scope.frm.tab  = cou;
            $scope.load(1);
        }

        /*
         End Action
         */
    }
]);
