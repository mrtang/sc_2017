'use strict';

//Verify Upload Money Collect
angular.module('app').controller('UploadMoneyCollectCtrl', ['$scope', '$state', '$stateParams', '$rootScope', '$filter', 'FileUploader', 'toaster', 'Api_Path', 'CourierVerify', 'Config_Status',
 	function($scope, $state, $stateParams, $rootScope, $filter, FileUploader, toaster, Api_Path, CourierVerify, Config_Status) {
    /*
        Config
    */
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.dynamic              = 0;
        $scope.totalItems           = 0;
        $scope.NewTotal             = 0;
        $scope.frm                  = {id : $stateParams.code, tab : 'ALL', type : 'money_collect'};
        $scope.courier_id           = '';
        $scope.status_map           = {};
        $scope.waiting              = true;
        $scope.waiting_export       = false;

        $scope.status               = Config_Status.StatusVerify;
        $scope.keys = function(obj){
            return obj? Object.keys(obj) : [];
        }

        // upload  excel
        var uploader = $scope.uploader = new FileUploader({
            url                 : Api_Path.Acc+'courier-verify/upload/money_collect',
            alias               : 'AccFile',
            headers             : {Authorization : $rootScope.userInfo.token,Location : $rootScope.userInfo.country_id},
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

        uploader.onBeforeUploadItem = function(item) {
            item.url = Api_Path.Acc+'courier-verify/upload/money_collect?courier_id='+$scope.courier_id
        };

        uploader.onSuccessItem = function(item, result, status, headers){
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Upload Thành công!');
                $state.go('app.verify.upload_money_collect',{code:result.id});
            }
            else{
                toaster.pop('warning', 'Thông báo', result.data.message_error);
            }
        };

        uploader.onErrorItem  = function(item, result, status, headers){
            toaster.pop('error', 'Thông báo!', "Lỗi kết nối dữ liệu, hãy thử lại !");
        };
    /*
        End Config
     */

    /*
        Action
     */
        $scope.$watch($stateParams,function(){
            if($stateParams.code != '' && $stateParams.code != undefined){
                $scope.refresh_data();
                $scope.load(1);
            }

        });

        $scope.load = function(page){
            $scope.currentPage      = page;
            $scope.waiting          = true;
            $scope.list_data        = {};
            CourierVerify.load_excel($scope.currentPage, $scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.NewTotal         = result.data.new_total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                    $scope.status_map       = result.data.status_map;
                }
                $scope.waiting   = false;
                return;
            });
        }

        $scope.refresh_data    = function(){
            $scope.list_data    = [];
            $scope.item_stt     = 0;
            $scope.totalItems   = 0;
        }

        // Verify
        $scope.Verify = function(){
            CourierVerify.money_collect($stateParams.code).then(function (result) {
                if($scope.status_error.indexOf(result.status) != -1){
                    $scope.Verify();
                }else{
                    if (!result.data.error) {
                        toaster.pop('success', 'Thông báo', result.data.message_error);
                    } else {
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
            },function(reason){
                $scope.Verify();
            });
        }

        $scope.ChangeTab = function(cou){
            $scope.refresh_data();
            $scope.frm.tab  = cou;
            $scope.load(1);
        }


        $scope.exportExcel = function(){
            $scope.waiting_export   = true;

            var html =
                "<table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Doi soat tien thu ho</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Ma đon hang</th>" +
                "<th rowspan='2'>Hang van chuyen</th>" +
                "<th rowspan='2'>Ma HVC</th>" +
                "<th colspan='2'>Trang thai</th>" +
                "<th colspan='2'>Thu ho</th>" +
                "<th rowspan='2'>Trang thai</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>ShipChung</td>" +
                "<td>HVC</td>" +
                "<td>ShipChung</td>" +
                "<td>HVC</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;
            CourierVerify.load_excel(1, $scope.frm, 'MONEY_COLLECT').then(function (result) {
                if(!result.data.error){
                    var status_courier = result.data.status_courier;
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+  i++ +"</td>" +
                            "<td>"+  value.tracking_code +"</td>" +
                            "<td>"+  (($scope.list_courier[1*value.courier_id]) ? $scope.list_courier[1*value.courier_id]['name'] : '') +"</td>" +
                            "<td>"+  value.courier_track_code +"</td>" +
                            "<td>"+  (($scope.list_status[1*value.sc_status]) ? $scope.list_status[1*value.sc_status] : '') +"</td>" +
                            "<td>"+  ((status_courier[1*value.courier_status] && $scope.list_status[1*status_courier[1*value.courier_status]]) ? $scope.list_status[1*status_courier[1*value.courier_status]] : '') +"</td>" +

                            "<td>"+  $filter('number')(value.sc_money_collect, 0) +"</td>"  +
                            "<td>"+  $filter('number')(value.money_collect, 0) +"</td>"+
                            "<td>"+  value.status +"</td>" +
                            "</tr>"
                    });

                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "Doi_soat_tien_thu_ho.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }

    /*
        End Action
     */

    }
]);
