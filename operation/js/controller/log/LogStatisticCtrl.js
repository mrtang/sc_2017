'use strict';
angular.module('app')
.controller('LogStatisticCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster',
function($scope, $modal, $http, $state, $window, $stateParams, toaster) {
    var typeC = $stateParams.type;
	$scope.time_start = new Date(date.getFullYear(), date.getMonth(), date.getDate()-1);
	$scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1
    };
    $scope.url = ApiBase;
	$scope.open = function($event,type) {
        $event.preventDefault();
        $event.stopPropagation();
        if(type == "time_accept_start_open"){
            $scope.time_accept_start_open = true;
        }else if(type == "time_accept_end_open"){
            $scope.time_accept_end = true;
        }
    };

	$scope.setPage = function(type,from_date,to_date){
        var fromDate = '';
        var toDate = '';
        if(from_date == undefined){
            from_date = '';
        }else{
            fromDate = Date.parse(from_date)/1000;
        }
        if(to_date == undefined){
            to_date = '';
        }else{
            toDate = Date.parse(to_date)/1000;
        }

        $scope.listData = [];
        $scope.stateLoading = true;

        var url = ApiPath+'log/statistic';
        if(type != undefined && type != ''){
            url += '?type='+type;
        }

        if(from_date != undefined && from_date != ''){
            url += '&from_date='+Date.parse(from_date)/1000;
        }
        if(to_date != undefined && to_date != ''){
            url += '&to_date='+Date.parse(to_date)/1000;
        }
        
    	$http({
            url: url,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
        if(!result.error){
            $scope.listData     = result.scenario;
            $scope.listCreated  = result.emailCreated;
            $scope.listSent     = result.emailSent;
            $scope.date      = result.date;
            $scope.fromDate = fromDate;
            $scope.toDate = toDate;
            $scope.dateGet      = result.dateGet;
            $scope.listCreatedByDate  = result.emailCreatedByDate;
            $scope.listSentByDate     = result.emailSentByDate;
            $scope.type = result.type;
        }
        $scope.stateLoading = false;
        });
    };
    $scope.setPage(typeC);

    $scope.changeTab = function(){
        alert(3);
    }

}]);