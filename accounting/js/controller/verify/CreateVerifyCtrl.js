'use strict';

//Verify Money Collect
angular.module('app')
	.controller('CreateVerifyCtrl', ['$scope', '$http', 'toaster','$timeout',
 	function($scope, $http, toaster, $timeout) {
 		$scope.runProcessing 	= false;
        $scope.waiting          = false;
        $scope.loyalty          = 0;
 		$scope.resultShow 		= [];

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };


        $scope.open = function($event,type) {
            $event.preventDefault();
            $event.stopPropagation();
            if(type == "time_start"){
                $scope.time_start_open = true;
            }else if(type == "time_end"){
                $scope.time_end_open = true;
            }
        };

        $scope.setPage = function (){
        	$http.get(ApiPath + 'accounting/verify/lastverify').success(function (resp){
        		if(resp.data.length == 0){
        			
        		}else {
                    $timeout(function (){
                        var _date = new Date(resp.data.time_create * 1000);
                        $scope.time_start = new Date(_date.getFullYear(), _date.getMonth(), 1);
                    })
					
        		}
        	})
        }

        $scope.keys = function(obj){
            return obj? Object.keys(obj) : [];
        }


        $scope.createVerify = function (){
        	if($scope.time_start  && $scope.time_end &&  !$scope.waiting){
                $scope.waiting  = true;
        		var time_start 	= $scope.time_start.getDate() + '/' + ($scope.time_start.getMonth() + 1) +'/'+$scope.time_start.getFullYear();
        		var time_end 	= $scope.time_end.getDate() + '/' +( $scope.time_end.getMonth() + 1 )+'/'+$scope.time_end.getFullYear();
        		var url = '/api/public/cronjob/convert/createverify/'+ $scope.loyalty +'?time_start='+ time_start +'&time_end='+ time_end;
        		$http.get(url).success(function (resp,status){
                    $scope.waiting  = false;
        			if(!resp.error){
        				if(resp.message == 'EMPTY'){
                            toaster.pop('success', 'Thông báo', 'Không có đơn hàng nào cần đối soát');
        				}else {
                            $scope.start()
        				}
        			}else {
                        toaster.pop('error', 'Thông báo', 'Something error : ' + resp.message);
        			}
        		})
        	}
        }

        $scope.start =  function (){
            if($scope.waiting){
                return;
            }

        	var url = '/api/public/cronjob/convert/verifyorder';
        	$http.get(url).success(function (resp,status){
                if(status == 408 || status == 504){
                    $scope.start();
                }

        		if(resp.message == 'CONTINUES'){
        			$scope.resultShow.unshift({time: new Date(), msg: 'Tạo thành công !'});
        			$scope.start();
        		}else if(resp.message == 'EMPTY'){
        			$scope.resultShow.unshift({time: new Date(), msg: 'Hoàn thành'});
                    toaster.pop('success', 'Thông báo', 'Hoàn thành');
        		}else {
        			$scope.resultShow.unshift({time: new Date(), msg: 'Lỗi : '+ resp.message});
        			$scope.start();
        		}
        	})
        }


        //$scope.setPage();
        
   }
]);
