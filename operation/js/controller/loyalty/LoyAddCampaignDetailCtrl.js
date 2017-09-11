'use strict';
angular.module('app')
.controller('LoyAddCampaignDetailCtrl', ['$scope', '$state', '$stateParams', 'Loyalty',
function($scope, $state, $stateParams, Loyalty) {

    if($stateParams.id == undefined || !$stateParams.id.length){
        $state.go('shipchung.loyalty.campaign');
    }
    
    $scope.totalItems   = 0;
    $scope.frm          = {campaign_id : $stateParams.id, code : ''};
    $scope.waiting      = true;
    $scope.waiting_add  = false;

    $scope.setPage = function(page){
        $scope.currentPage  = page;
        Loyalty.campaign_detail_id($scope.currentPage,{campaign_id : $stateParams.id}).then(function (result) {
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

    $scope.AddDetail   = function(){
        $scope.waiting_add  = true;
        Loyalty.create_campaign_detail($scope.frm).then(function (result) {
            if(result.data.error){
                return result.data.error_message;
            }
        }).finally(function() {
            $scope.waiting_add  = false;
        });
    }

}]);

