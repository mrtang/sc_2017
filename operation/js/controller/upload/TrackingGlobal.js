'use strict';

//Verify Upload Money Collect
angular.module('app').controller('UploadTrackingCtrl', 
			['$scope', '$http','$state','$stateParams','Import_Tracking','$rootScope', 'FileUploader', 'toaster','Upload',
 	function($scope,   $http,   $state,  $stateParams,  Import_Tracking,  $rootScope,   FileUploader,   toaster,  Upload) {
        $scope.waiting              = true;
        $scope.waiting_upload       = false;
        // upload  excel
        var uploader = $scope.uploader = new FileUploader({
            url                 : BOXME_API+'upload_tracking_global',
            headers             : {Authorization : $rootScope.userInfo.token
            	},
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
            item.url = BOXME_API+'upload_tracking_global';
        };

        uploader.onSuccessItem = function(item, result, status, headers){
            $scope.waiting_upload  = false;
            if(!result.error){
                toaster.pop('success', 'Thông báo', 'Upload Thành công!');
                //shipchung.upload.upload_tracking({id:''})
                $state.go('delivery.upload.upload_tracking',{id:result.ID});
            }
            else{
                toaster.pop('warning', 'Thông báo', 'Upload Thất bại!');
            }
        };
        uploader.onErrorItem  = function(item, result, status, headers){
            $scope.waiting_upload  = false;
            toaster.pop('error', 'Thông báo!', "Kết nối dữ liệu thất bại.");
        };
        $scope.$watch($stateParams,function(){
            if($stateParams.id != '' && $stateParams.id != undefined){
                $scope.refresh_data();
                $scope.load(1);
            }

        });

        $scope.load = function(page){
            $scope.currentPage = page;
            Import_Tracking.preview($stateParams.id,{page:1}).then(function (result) {
                if(result){
                    $scope.list_data        = result._embedded.data;
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
        	Import_Tracking.approve($stateParams.id,{page:1}).then(function (result) {
        		 if(result){
                     $scope.list_data        = result._embedded.data;
                 }
                if (!result.error) {
                    toaster.pop('success', 'Thông báo', 'Thành công !');
                }else {
                    toaster.pop('warning', 'Thông báo', $scope.status_verify[result.data.message].text);
                }
            });
        }

        $scope.ChangeTab = function(cou){
            $scope.refresh_data();
            $scope.frm.tab  = cou;
            $scope.load(1);
        }

    /*
        End Action
     */

    }
]);
angular.module('app').controller('TrackingCtrl', 
		['$scope', 		'Import_Tracking','$rootScope', 'toaster',
	function($scope, 	 Import_Tracking,  $rootScope,  toaster) {
    $scope.waiting              = true;
    $scope.search_param ={page:1,page_size:25}
    $scope.time              = {create_start : new Date(date.getFullYear(), date.getMonth(), 1), create_end : ''};
    
    $scope.search = function(){
    	if($scope.time.create_start != undefined && $scope.time.create_start != ''){
            $scope.search_param.create_start     = +Date.parse($scope.time.create_start)/1000;
        }else{
            $scope.search_param.create_start     = '';
        }

        if($scope.time.create_end != undefined && $scope.time.create_end != ''){
            $scope.search_param.create_end       = +Date.parse($scope.time.create_end)/1000 + 86399;
        }else{
            $scope.search_param.create_end     = '';
        }
    	 Import_Tracking.getLog($scope.search_param).then(function (result) {
             if(result){
                 $scope.list_data        = result._embedded.data;
             }
             $scope.waiting      = false;
             return;
         });
    }
    $scope.load = function(page){
        Import_Tracking.getLog({page:1}).then(function (result) {
        	console.log(result)
            if(result){
                $scope.list_data        = result._embedded.data;
            }
            $scope.waiting      = false;
            return;
        });
    }
    $scope.load();
}
]);
angular.module('app').directive('fileSelected', function() {
	return {
		require:'ngModel',
		link: function(scope, el, attrs, ngModel){
			el.bind('change', function() {
				scope.$apply(function() {
					ngModel.$setViewValue(el.val());
					ngModel.$render();
				});
			});
		}
	}
});
angular.module('app').service('Import_Tracking', function($http, HAL) {
		return {
			preview: function(id,condition) {
				return $http({
					url: BOXME_API+'get_mongo_tracking_global' + '/' + id,
					//url: BOXME_API+'update_shipment_v2' + '/' + shipment.RequestCode,
					method: 'GET',
					params: condition
				}).then(function(response) {
					return HAL.Collection(response.data);
				});
			},
			approve: function(id, condition) {
				return $http({
					url: BOXME_API+'appove_mongo_tracking_global' + '/' + id,
					method: 'POST',
					data: condition
				}).then(function(response) {
					return HAL.Resource(response.data);
				});
			},
			getLog:	function(condition) {
				return $http({
					url: BOXME_API+'get_log_mongo_tracking_global',
					method: 'GET',
					params: condition
				}).then(function(response) {
					return HAL.Resource(response.data);
				});
			}
		}
});