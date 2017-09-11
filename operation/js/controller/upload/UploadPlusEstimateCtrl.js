'use strict';

//Verify Upload Money Collect
angular.module('app').controller('UploadPlusEstimateCtrl', ['$scope', '$state', '$filter', '$stateParams', '$rootScope', 'FileUploader', 'toaster', 'Api_Path', 'Upload', 'Config_Status',
 	function($scope, $state, $filter, $stateParams, $rootScope,FileUploader, toaster, Api_Path, Upload, Config_Status) {
    /*
        Config
    */
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.dynamic              = 0;
        $scope.totalItems           = 0;
        $scope.NewTotal             = 0;
        $scope.tab                  = 'ALL';
        $scope.courier_id           = 0;
        $scope.service_id           = 0;
        $scope.frm                  = {tab : 'ALL'};
        $scope.waiting              = true;
        $scope.status_verify        = Config_Status.StatusVerify;
        $scope.list_color           = Config_Status.order_color;
        $scope.waiting_upload       = false;
        $scope.waiting_export       = false;

        // upload  excel
        var uploader = $scope.uploader = new FileUploader({
            url                 : Api_Path.Upload+'upload?type=estimate_plus',
            headers             : {Authorization : $rootScope.userInfo.token,Location : $rootScope.userInfo.country_id},
            removeAfterUpload   : true
        });

        // FILTERS
        uploader.filters.push({
            name: 'excelFilter',
            fn: function(item /*{File|FileLikeObject}*/, options) {
                $scope.waiting_upload  = false;
                var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
                return '|vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet|'.indexOf(type) !== -1;
            }
        });

        uploader.onProgressAll  = function(progress){
            $scope.waiting_upload  = true;
        }

        uploader.onBeforeUploadItem = function(item) {
            item.url = Api_Path.Upload+'upload?type=estimate_plus&courier_id='+$scope.courier_id+'&service_id='+$scope.service_id;
        };

        uploader.onSuccessItem = function(item, result, status, headers){
            $scope.waiting_upload  = false;
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Upload Thành công!');
                $state.go('delivery.upload.upload_estimate_plus',{id:result.id});
            }
            else{
                toaster.pop('warning', 'Thông báo', 'Upload Thất bại!');
            }
        };

        uploader.onErrorItem  = function(item, result, status, headers){
            $scope.waiting_upload  = false;
            toaster.pop('error', 'Thông báo!', "Kết nối dữ liệu thất bại.");
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
            Upload.ListUpload($stateParams.id, $scope.currentPage,$scope.frm, '').then(function (result) {
                if(!result.data.error){
                    $scope.list_data        = result.data.data;
                    $scope.totalItems       = result.data.total;
                    $scope.NewTotal         = result.data.new_total;
                    $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting      = false;
                return;
            });
        }

        $scope.refresh_data    = function(){
            $scope.list_data    = [];
            $scope.item_stt     = 0;
            $scope.totalItems   = 0;
            $scope.waiting      = true;
        }

        // Verify
        $scope.Verify = function(){
            Upload.EstimatePlus($stateParams.id).then(function (result) {
                if(result.data.total > 0) {
                    if (!result.data.error) {
                        toaster.pop('success', 'Thông báo', 'Thành công !');
                    } else {
                        toaster.pop('warning', 'Thông báo', $scope.status_verify[result.data.message].text);
                    }
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
            });
        }

        $scope.ChangeTab = function(cou){
            $scope.refresh_data();
            $scope.frm.tab  = cou;
            $scope.load(1);
        }

        $scope.export_excel = function(){
            $scope.waiting_export   = true;
            var html =
                "<meta http-equiv='content-type' content='application/vnd.ms-excel; charset=UTF-8'><table width='100%' border='1'>" +
                "<thead><tr>" +
                "<td style='border-style:none'></td>" +
                "<td style='border-style:none'></td>"+
                "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sách cập nhật thời gian cam kết</strong></td></tr>" +
                "<tr></tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<th rowspan='2'>STT</th>" +
                "<th rowspan='2'>Hãng vận chuyển</th>" +
                "<th rowspan='2'>Dịch vụ</th>" +
                "<th colspan='2'>HVC</th>" +
                "<th colspan='2'>ShipChung</th>" +
                "<th rowspan='2'>Thời gian cộng thêm</th>" +
                "<th rowspan='2'>Trạng thái</th>" +


                "</tr>" +
                "<tr style='font-size: 14px; background: #6b94b3'>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "<td>Tỉnh/Thành Phố</td>" +
                "<td>Quận/Huyện</td>" +
                "</tr>" +
                "</thead>" +
                "<tbody>";
            var i = 1;
            Upload.ListUpload1($stateParams.id, $scope.currentPage,$scope.frm, 'ESTIMATE_PLUS').then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.data, function(value) {
                        html+= "<tr>" +
                            "<td>"+ i++ +"</td>" +
                            "<td>"+ $scope.courier[value.courier_id] +"</td>" +
                            "<td>"+ $scope.service[value.service_id] +"</td>" +

                            "<td>"+ value.city +"</td>" +
                            "<td>"+ value.district +"</td>" +
                            "<td>"+  (($scope.city[1*value.sc_city])           ? $scope.city[1*value.sc_city]                 : '') +"</td>" +
                            "<td>"+  (($scope.district[1*value.sc_district])             ? $scope.district[1*value.sc_district]                 : '') +"</td>" +

                            "<td>"+ $filter('number')(value.estimate, 0) +"</td>" +
                            "<td>"+ value.status +"</td>" +
                            "</tr>"
                        ;
                    });
                    html        +=  "</tbody></table>";
                    var blob = new Blob([html], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                    });
                    saveAs(blob, "danh_sach_cap_nhat_thoi_gian_cam_ket_cong_them.xls");
                }
            }).finally(function() {
                $scope.waiting_export   = false;
            });
            return;
        }

    /*
        End Action
    */
    }
]);
