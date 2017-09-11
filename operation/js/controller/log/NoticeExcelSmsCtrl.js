'use strict';
angular.module('app')
.controller('NoticeExcelSmsCtrl', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster','FileUploader','$rootScope',
function($scope, $modal, $http, $state, $window, $stateParams, toaster,FileUploader,$rootScope) {
	var uploader = $scope.uploader = new FileUploader({
        url                 : ApiPath+'log/upload',
        headers             : {Authorization : $rootScope.userInfo.token},
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
            toaster.pop('success', 'Thông báo', 'Thành công!');
        }          
        else{
            toaster.pop('warning', 'Thông báo', result.message,5000,'trustedHtml');
        }
    };
    
    uploader.onErrorItem  = function(item, result, status, headers){
        toaster.pop('error', 'Error!', "Error Server.");
    };
}]);