'use strict';
angular.module('app')
.controller('LoyCampaignDetailCtrl', ['$scope', '$state', '$filter', 'Loyalty',
function($scope, $state, $filter, Loyalty) {
    $scope.totalItems       = 0;
    $scope.frm              = {};
    $scope.time             = {};
    $scope.waiting_export   = false;

    $scope.refresh = function(cmd){
        if($scope.time.time_start != undefined && $scope.time.time_start != ''){
            $scope.frm.time_start   = +Date.parse($scope.time.time_start)/1000;
        }else{
            $scope.frm.time_start   = 0;
        }
        if($scope.time.time_end != undefined && $scope.time.time_end != ''){
            $scope.frm.time_end     = +Date.parse($scope.time.time_end)/1000 + 86399;
        }else{
            $scope.frm.time_end     = 0;
        }

        if(cmd != 'export'){
            $scope.list_data            = {};
            $scope.waiting              = true;
        }
    }

    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.refresh('');
        Loyalty.campaign_detail($scope.currentPage,$scope.frm).then(function (result) {
            if(!result.data.error){
                $scope.list_data        = result.data.data;
                $scope.totalItems       = result.data.total;
                $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
            }
            $scope.waiting  = false;
        });
        return;
    }

    $scope.exportExcel = function(){
        $scope.waiting_export   = true;
        $scope.refresh('export');
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
            "<th rowspan='2'>Phan thuong</th>" +
            "<th rowspan='2'>Thu Hang</th>" +
            "<th rowspan='2'>Gia tri</th>" +
            "<th rowspan='2'>So diem</th>" +
            "<th colspan='2'>Nhan thuong</th>" +
            "<th colspan='2'>Ma thuong</th>" +
            "<th colspan='2'>Thong bao</th>" +
            "</tr>" +
            "<tr style='font-size: 14px; background: #6b94b3'>" +
            "<td>Ho ten</td>" +
            "<td>Email</td>" +
            "<td>SDT</td>" +
            "<td>Nha cung cap</td>" +
            "<td>SDT</td>" +
            "<td>So the</td>" +
            "<td>Ma the</td>" +
            "<td>Email doi thuong</td>" +
            "<td>Email phan thuong</td>" +
            "<td>SMS phan thuong</td>" +
            "</tr>" +
            "</thead>" +
            "<tbody>";

        var i = 1;

        Loyalty.campaign_detail(1,$scope.frm, 'export').then(function (result) {
            if(!result.data.error){
                angular.forEach(result.data.data, function(value) {
                    html+= "<tr>" +
                        "<td>"+ i++ +"</td>" +
                        "<td>"+ ((value.get_user  != undefined) ? value.get_user.fullname : '') +"</td>" +
                        "<td>"+ ((value.get_user  != undefined) ? value.get_user.email : '') +"</td>" +
                        "<td>"+ ((value.get_user  != undefined) ? '_'+value.get_user.phone : '') +"</td>" +

                        "<td>"+ /*((value.get_campaign  != undefined) ? String(value.get_campaign.name) : '')*/"" +"</td>" +
                        "<td>"+ (($scope.sc_loyalty_level[value.level]  != undefined) ? $scope.sc_loyalty_level[value.level]['name'] : '') +"</td>" +
                        "<td>"+ ((value.get_campaign  != undefined) ? $filter('number')(value.get_campaign.value, 0)   : '') +"</td>" +
                        "<td>"+ ((value.get_campaign  != undefined) ? $filter('number')(value.get_campaign.point, 0)   : '') +"</td>" +

                        "<td>"+ (($scope.type_phone[value.phone_type]  != undefined) ? $scope.type_phone[value.phone_type] : '') +"</td>" +
                        "<td>"+ '_'+value.phone +"</td>" +
                        "<td>"+ '_'+value.code_number +"</td>" +
                        "<td>"+ '_'+value.code +"</td>" +
                        "<td>"+ ((value.notice  == 1) ? 'Da gui' : 'Chua gui') +"</td>" +
                        "<td>"+ ((value.return  == 1) ? 'Da gui' : 'Chua gui') +"</td>" +
                        "<td>"+ ((value.sms  == 1) ? 'Da gui' : 'Chua gui') +"</td>" +
                        "</tr>"
                    ;
                });
                html        +=  "</tbody></table>";
                var blob = new Blob([html], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
                });
                saveAs(blob, "danh_sach_doi_thuong.xls");
            }
        }).finally(function() {
            $scope.waiting_export   = false;
        });
        return;
    }

    $scope.changeCampaign   = function(item,value,field){
        if(!item.waiting){

        item.waiting  = true;
        var dataupdate = {id : item.id};
        dataupdate[field] = value;

        return Loyalty.change_campaign_detail(dataupdate).then(function (result) {
            if(result.data.error){
                return result.data.error_message;
            }
        }).finally(function() {
            item.waiting  = false;
        });
        }
    }

}]);

