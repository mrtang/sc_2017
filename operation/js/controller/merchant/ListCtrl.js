'use strict';

angular.module('app').controller('MerchantListCtrl', ['$scope', '$rootScope',  '$http', '$state', '$stateParams', '$window', 'toaster', 'Merchant',  '$modal','Base', 'Config',
 	function($scope, $rootScope,  $http, $state, $stateParams,  $window, toaster, Merchant, $modal, Base, Config) {
        $scope.groupCode            = $stateParams.group_code;
        $scope.currentPage          = 1;
        $scope.item_page            = 20;
        $scope.maxSize              = 5;
        $scope.item_stt             = 0;
        $scope.time                 = {};
        $scope.frm                  = {};
        $scope.list_data            = {};
        $scope.waiting              = true;
        $scope.nextStep             = 0;

        $scope.pipe_status          = {};
        $scope.pipe_limit           = 0;
        $scope.check_box            = [];

        $scope.list_business_model  = Config.business_model;
        $scope.business_model = {};
        angular.forEach(Config.business_model, function (value, key){
            $scope.business_model[value.id] = value.name;
        })

        $scope.typeName = "";


        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.toggleSelection = function(id) {
            var data = angular.copy($scope.check_box);
            var idx = +data.indexOf(id);

            if (idx > -1) {
                $scope.check_box.splice(idx, 1);
            }
            else {
                $scope.check_box.push(id);
            }
        };
        

        $scope.list_city           = {};
        $scope.list_district       = {};

        Base.City().then(function (result) {
            if(!result.data.error){
                $scope.list_city        = result.data.data;
            }
        });

        $scope.$watch('frm.place_city', function (newVal){
            if(!newVal){
                return; 
            }
            $scope.loadDictricts(newVal);
        })

        $scope.loadDictricts = function (city){
             Base.district(city, 100).then(function (result){
                if(!result.data.error){
                    $scope.list_district        = result.data.data;
                }
             })
        }



        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "from_date"){
                $scope.from_date_open = true;
            }else if(type == "to_date"){
                $scope.to_date_open = true;
            }
        };

        $scope.refresh = function(cmd){
            if($scope.time.time_update_start != undefined && $scope.time.time_update_start != ''){
                $scope.frm.time_update_start    = +Date.parse($scope.time.time_update_start)/1000;
            }else{
                $scope.frm.time_update_start    = 0;
            }

            if($scope.time.time_update_end != undefined && $scope.time.time_update_end != ''){
                $scope.frm.time_update_end      = +Date.parse($scope.time.time_update_end)/1000 + 86399;
            }else{
                $scope.frm.time_update_end      = 0;
            }

            if($scope.time.first_order_start != undefined && $scope.time.first_order_start != ''){
                $scope.frm.first_order_start    = +Date.parse($scope.time.first_order_start)/1000;
            }else{
                $scope.frm.first_order_start    = 0;
            }

            if($scope.time.last_order_start != undefined && $scope.time.last_order_start != ''){
                $scope.frm.last_order_start      = +Date.parse($scope.time.last_order_start)/1000 + 86399;
            }else{
                $scope.frm.last_order_start      = 0;
            }

            if($scope.time.first_order_end != undefined && $scope.time.first_order_end != ''){
                $scope.frm.first_order_end    = +Date.parse($scope.time.first_order_end)/1000;
            }else{
                $scope.frm.first_order_end    = 0;
            }

            if($scope.time.last_order_end != undefined && $scope.time.last_order_end != ''){
                $scope.frm.last_order_end      = +Date.parse($scope.time.last_order_end)/1000 + 86399;
            }else{
                $scope.frm.last_order_end      = 0;
            }

            if(cmd != 'export'){
                $scope.list_data = {};
                $scope.waiting   = true;
            }

        }
        $scope.district = {};
        $scope.setPage = function(page, cmd){
            
            $scope.refresh(cmd);
            if($scope.check_box != undefined && $scope.check_box != []){
                $scope.frm.pipe_status      = $scope.check_box.toString();
            }else{
                $scope.frm.pipe_status  = '';
            }

            if(cmd && cmd == 'export'){
                Merchant.getList($scope.frm, $scope.currentPage, 'export');
                return false;
            }
            
            $scope.currentPage = page;
            $scope.item_page   = $scope.frm.limit || 20;
            Merchant.getList($scope.frm, $scope.currentPage).then(function (result) {
                if(!result.data.error){
                    $scope.list_data  = result.data.data;
                    $scope.district   = result.data.district;
                    $scope.totalItems = result.data.total;
                    $scope.item_stt   = $scope.item_page * ($scope.currentPage - 1);
                }
                $scope.waiting  = false;
            });
            return;
        }


        $scope.checkStepClass = function (item, status){
            for (var i = item.pipe_journey.length - 1; i >= 0; i--) {
                if(item.pipe_journey[i].pipe_status == status){
                    return true;
                    break;
                }
            };
            return false;
        }

        $scope.openActionPop = function (item, status){
            if(status.priority < item.pipe_status.priority ){
                alert('Sai quy trình, không thể cập nhật');
                return false;
            }
            var modalInstance = $modal.open({
                templateUrl: 'MerchantActionPop.html',
                controller: 'MerchantListActionCtrl',
                size: 'lg',
                resolve: {
                    item : function (){
                        return item;
                    },
                    status: function (){
                        return status;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
            }, function () {
            });
        }

        $scope.frm.group_process  = $scope.groupCode;
        $scope.currentPage  = 1;
        $scope.setPage(1);
    }
]);

angular.module('app')
    .controller('MerchantListActionCtrl', ['$scope', '$modalInstance', 'item', 'status', 'Merchant', 'toaster', 'Pipe',  function ($scope, $modalInstance, item, status, Merchant, toaster, Pipe){
        $scope.item = item;
        $scope.status = status;
        $scope.data = {};

        $scope.save = function (data){
            data.tracking_code  = item.user_id;
            data.pipe_status    = $scope.status.status;
            data.type           = 1;

            Pipe.Create(data).then(function (result){
                if(result.data.error){
                    $scope.frm_submit   = false;
                }else{
                    $scope.close();
                }
            })
        }
        $scope.close = function (){
            $modalInstance.dismiss();
        }
    }]);