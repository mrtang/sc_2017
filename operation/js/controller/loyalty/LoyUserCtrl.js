'use strict';
angular.module('app')
.controller('LoyUserCtrl', ['$scope', '$state', '$filter', 'Loyalty','$modal','toaster',
function($scope, $state, $filter, Loyalty, $modal, toaster) {
    $scope.totalItems       = 0;
    $scope.frm              = {};
    $scope.waiting_export   = false;

    $scope.setPage = function(page){
        $scope.currentPage  = page;
        Loyalty.user($scope.currentPage,$scope.frm).then(function (result) {
            if(!result.data.error){
                $scope.list_data        = result.data.data;
                $scope.totalItems       = result.data.total;
                $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
            }
            $scope.waiting  = false;
        });
        return;
    }

    $scope.createCashin   = function(item){
        $scope.waiting  = true;
        Loyalty.create_user({email : item.email, level : item.level, current_point : item.current_point.toString().replace(/,/gi, ""), total_point : item.total_point.toString().replace(/,/gi, ""), active : item.active}).then(function (result) {
            if(!result.data.error){
                $state.go('delivery.loyalty.user');
            }else{
                return result.data.error_message;
            }
        }).finally(function() {
            $scope.waiting  = false;
        });
    }

    $scope.exportExcel = function(){
        $scope.waiting_export   = true;
        var html =
            "<table width='100%' border='1'>" +
            "<thead><tr>" +
            "<td style='border-style:none'></td>" +
            "<td style='border-style:none'></td>"+
            "<td colspan='3' style='font-size: 18px; border-style:none '><strong>Danh sach khach hang than thiet</strong></td></tr>" +
            "<tr></tr>" +
            "<tr style='font-size: 14px; background: #6b94b3'>" +
            "<th rowspan='2'>STT</th>" +
            "<th colspan='3'>Khach hang</th>" +
            "<th rowspan='2'>Thu hang</th>" +
            "<th rowspan='2'>Tong điem</th>" +
            "<th rowspan='2'>Tich luy thang</th>" +
            "<th rowspan='2'></th>" +
            "</tr>" +
            "<tr style='font-size: 14px; background: #6b94b3'>" +
            "<td>Ho ten</td>" +
            "<td>Email</td>" +
            "<td>SDT</td>" +
            "</tr>" +
            "</thead>" +
            "<tbody>";

        var i = 1;

        Loyalty.user(1,$scope.frm, 'export').then(function (result) {
            if(!result.data.error){
                angular.forEach(result.data.data, function(value) {
                    html+= "<tr>" +
                        "<td>"+ i++ +"</td>" +
                        "<td>"+ ((value.get_user  != undefined) ? value.get_user.fullname : '') +"</td>" +
                        "<td>"+ ((value.get_user  != undefined) ? value.get_user.email : '') +"</td>" +
                        "<td>"+ ((value.get_user  != undefined) ? value.get_user.phone : '') +"</td>" +

                        "<td>"+ (($scope.sc_loyalty_level[value.level]  != undefined) ? $scope.sc_loyalty_level[value.level]['name'] : '') +"</td>" +
                        "<td>"+  $filter('number')(value.total_point, 0)                   +"</td>" +
                        "<td>"+  $filter('number')(value.current_point, 0)                   +"</td>" +
                        "<td>"+ ((value.active  == 1) ? 'Chinh thuc' : 'Tiem Nang') +"</td>" +
                        "</tr>"
                    ;
                });
                html        +=  "</tbody></table>";
                var blob = new Blob([html], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                });
                saveAs(blob, "danh_sach_khach_hang_than_thiet.xls");
            }
        }).finally(function() {
            $scope.waiting_export   = false;
        });
        return;
    }
    //doi thu hang TV
    $scope.changeLevel = function(id){
        var modalInstance = $modal.open({
            templateUrl: 'tpl/loyalty/tpl/modal.change_level.html',
            controller: function($scope, $modalInstance, id, $http) {
                $scope.id         = id;
                $scope.submit_loading = false;
                
                $scope.acceptChange = function (frm){
                    $scope.submit_loading = true;
                    $scope.frm.id   = id;
                    $http.post(ApiLoyalty + 'user/changelevel', frm).success(function (resp){
                        $scope.submit_loading = false;
                        toaster.pop('success', 'Thông báo', resp.error_message);
                        $scope.cancel();
                    })
                }

                $scope.cancel = function() {
                    $modalInstance.dismiss('cancel');
                };
            },
            size: 'md',
            resolve: {
                id: function () {
                    return id; 
                }
            }
        });
    }

}]);

