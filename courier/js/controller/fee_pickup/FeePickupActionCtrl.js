'use strict';
angular.module('app')
.controller('FeePickupActionCtrl', ['$scope', '$modal', '$http', '$state', '$window', 'toaster','FileUploader', 'bootbox',
function($scope, $modal, $http, $state, $window, toaster,FileUploader, bootbox) {
	
	var uploader = $scope.uploader = new FileUploader({
        url                 : ApiPath+'fee-pickup/upload',
        removeAfterUpload   : true,
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
            $state.go('app.fee_pickup.list_uploaded',{id:result.id});
        }          
        else{
            toaster.pop('warning', 'Thông báo', result.message,5000,'trustedHtml');
        }
    };
    
    uploader.onErrorItem  = function(item, result, status, headers){
        toaster.pop('error', 'Error!', "Error Server.");
    };
}]);