'use strict';

//Verify Upload Money Collect
angular.module('app').controller('UploadServiceCtrl', ['$scope', '$state', '$stateParams', '$rootScope', '$filter', 'PhpJs', 'FileUploader', 'toaster', 'Api_Path', 'CourierVerify', 'Config_Status',
 	function($scope, $state, $stateParams, $rootScope, $filter, PhpJs, FileUploader, toaster, Api_Path, CourierVerify, Config_Status) {
    /*
        Config
    */
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.dynamic              = 0;
        $scope.totalItems           = 0;
        $scope.NewTotal             = 0;
        $scope.courier_id           = '';
        $scope.frm                  = {id : $stateParams.code, tab : 'ALL', type : 'service'};
        $scope.waiting              = true;
        $scope.waiting_export       = false;

        $scope.list_status          = Config_Status.StatusVerify;

        // upload  excel
        var uploader = $scope.uploader = new FileUploader({
            url                 : Api_Path.Acc+'courier-verify/upload/service',
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

        uploader.onSuccessItem = function(item, result, status, headers){
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Upload Thành công!');
                $state.go('app.verify.upload_service',{code:result.id});
            }
            else{
                toaster.pop('warning', 'Thông báo', result.data.message_error);
            }
        };

        uploader.onBeforeUploadItem = function(item) {
            item.url = Api_Path.Acc+'courier-verify/upload/service?courier_id='+$scope.courier_id
        };

        uploader.onErrorItem  = function(item, result, status, headers){
            toaster.pop('warning', 'Thông báo', "Lỗi kết nối dữ liệu, hãy thử lại !");
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
                }
                $scope.waiting              = false;
            });
        }

        $scope.refresh_data    = function(){
            $scope.list_data    = {};
            $scope.item_stt     = 0;
            $scope.totalItems   = 0;
        }

        // Verify
        $scope.Verify = function(){
            CourierVerify.service($stateParams.code).then(function (result) {
                if($scope.status_error.indexOf(result.status) != -1){
                    $scope.Verify();
                }else{
                    if (!result.data.error) {
                        toaster.pop('success', 'Thông báo', result.data.message_error);
                    } else {
                        toaster.pop('warning', 'Thông báo', result.data.message_error);
                    }

                    if(result.data.total > 0){

                        $scope.dynamic = (($scope.totalItems - result.data.total + 2)*100/$scope.totalItems).toFixed(2);
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
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sach doi soat dich vu hang van chuyen</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Ma don hang</th>" +
                "<th rowspan='2'>Ma HVC</th>" +
                "<th rowspan='2'>Hang van chuyen</th>" +
                "<th rowspan='2'>Dich vu SC</th>" +
                "<th colspan='2'>Dich vu</th>" +
                "<th colspan='2'>Tien thu ho</th>" +
                "<th colspan='2'>Khoi luong</th>" +
                "<th colspan='2'>Tinh/thanh gui</th>" +
                "<th colspan='2'>Tinh/thanh nhan</th>" +
                "<th rowspan='2'>Quan/Huyen nhan</th>" +
                "<th rowspan='2'>Khu vuc</th>" +
                "<th rowspan='2'>Trang thai</th>" +
                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Shipchung</td>" +
                "<td>Hvc</td>" +
                "<td>Shipchung</td>" +
                "<td>Hvc</td>" +
                "<td>Shipchung</td>" +
                "<td>Hvc</td>" +
                "<td>Shipchung</td>" +
                "<td>Hvc</td>" +
                "<td>Shipchung</td>" +
                "<td>Hvc</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";

            var i = 1;

            CourierVerify.load_excel(1,$scope.frm,'SERVICE').then(function (result) {
                if(!result.data.error) {
                    var location = '';
                    var service = '';
                    angular.forEach(result.data.data, function (value,key) {
                            location = '';

                            if (value.location != undefined) {
                                switch (value.location) {
                                    case 0:
                                        location = '';
                                        break;
                                    case 1:
                                        location = 'Nội Thành';
                                        break;
                                    case 2:
                                        location = 'Ngoại thành';
                                        break;
                                    default:
                                        location = 'Huyện xã';
                                }
                            }

                            if (value.service != undefined) {
                                if (value.service == 1) {
                                    service = 'CPTK';
                                }else if((value.service == 2)) {
                                    service = 'CPN';
                                }else if((value.service == 2)) {
                                    service = 'CPN';
                                }
                                else if((value.service == 5)) {
                                    service = 'VVT';
                                }else if((value.service == 6)) {
                                    service = 'XTK';
                                }else{
                                    service = 'XXX';
                                }
                            }

                            html += "<tr>" +
                                "<td>" + i++ + "</td>" +
                                "<td>" + value.tracking_code + "</td>" +
                                "<td>" + value.courier_track_code + "</td>" +
                                "<td>" + (($scope.list_courier[1 * value.courier_id]) ? $scope.list_courier[1 * value.courier_id]['name'] : '') + "</td>" +
                                "<td>" + service + "</td>" +
                                "<td>" + value.sc_service + "</td>" +
                                "<td>" + value.courier_service + "</td>" +

                                "<td>" + $filter('number')(value.sc_money_collect, 0) + "</td>" +
                                "<td>" + $filter('number')(value.courier_money_collect, 0) + "</td>" +

                                "<td>" + $filter('number')(value.sc_weight, 0) + "</td>" +
                                "<td>" + $filter('number')(value.courier_weight, 0) + "</td>" +

                                "<td>" + value.sc_from_city + "</td>" +
                                "<td>" + value.courier_from_city + "</td>" +
                                "<td>" + value.sc_to_city + "</td>" +
                                "<td>" + value.courier_to_city + "</td>" +

                                "<td>" + ((value.sc_to_district != undefined && $scope.district[value.sc_to_district] != undefined) ? $scope.district[value.sc_to_district] : "") + "</td>" +
                                "<td>" + location + "</td>" +
                                "<td>" + value.status + "</td></tr>";

                    })}

                    html += "</tbody></table>";
                
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8", Encoding: 'utf-8'
                    });
                    saveAs(blob, "Danh_sach_doi_soat_dich_vu.xls");
            }).finally(function() {
                $scope.waiting_export   = false;
            });
        }
    /*
        End Action
     */

    }
]);
