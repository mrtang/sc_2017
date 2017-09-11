'use strict';
angular.module('app')
.controller('LoyCampaignCtrl', ['$scope', '$state', 'Loyalty',
function($scope, $state, Loyalty) {
    $scope.totalItems   = 0;
    $scope.frm          = {};
    $scope.time         = {};

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

        $scope.list_data            = {};
        $scope.waiting              = true;

    }

    $scope.setPage = function(page){
        $scope.currentPage  = page;
        $scope.refresh('');
        Loyalty.campaign($scope.currentPage,$scope.frm).then(function (result) {
            if(!result.data.error){
                $scope.list_data        = result.data.data;
                $scope.totalItems       = result.data.total;
                $scope.item_stt         = $scope.item_page * ($scope.currentPage - 1);
            }
            $scope.waiting  = false;
        });
        return;
    }
    $scope.setPage(1);

    $scope.createCampaign   = function(item){
        var data = angular.copy(item);console.log(data);

        if(data.value != undefined){
            data.value = data.value.toString().replace(/,/gi, "");
        }

        if(data.point != undefined){
            data.point = data.point.toString().replace(/,/gi, "");
        }

        if(data.total != undefined){
            data.total = data.total.toString().replace(/,/gi, "");
        }

        if(data.time_start != undefined && data.time_start != ''){
            data.time_start   = +Date.parse(data.time_start)/1000;
        }else{
            data.time_start   = 0;
        }
        if(data.time_end != undefined && data.time_end != ''){
            data.time_end     = +Date.parse(data.time_end)/1000 ;
        }else{
            data.time_end     = 0;
        }

        $scope.waiting  = true;
        Loyalty.create_campaign(data).then(function (result) {
            if(!result.data.error){
                $state.go('shipchung.loyalty.campaign');
            }else{
                return result.data.error_message;
            }
        }).finally(function() {
            $scope.waiting  = false;
        });
    }

    $scope.date_time = function(time){
        if(time == 0){
            return new Date();
        }else{
            return new Date(time*1000);
        }
    }

    $scope.changeCampaign   = function(item,value,field){
        item.waiting  = true;
        var dataupdate = {id : item.id};

        if(field == 'time_start'){
            dataupdate[field] = +Date.parse(value)/1000;
        }else if(field == 'time_end'){
            dataupdate[field] = +Date.parse(value)/1000 + 86399;
        }else{
            dataupdate[field] = value;
        }


        return Loyalty.change_campaign(dataupdate).then(function (result) {
            if(result.data.error){
                return result.data.error_message;
            }else{
                if(field == 'time_start'){
                    item.time_start = dataupdate[field];
                }else if(field == 'time_end'){
                    item.time_end = dataupdate[field];
                }
            }
        }).finally(function() {
            item.waiting  = false;
        });
    }

    $scope.changeActive = function(item){
        return $scope.changeCampaign(item,item.active,'active');
    }

}]);

