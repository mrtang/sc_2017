'use strict';
angular.module('app')
.controller('VimoVerifyController', ['$scope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox','FileUploader', '$timeout', '$q','Config_Accounting', '$filter','$rootScope',
function($scope, $modal, $http, $state, $window, $stateParams, toaster, bootbox,FileUploader, $timeout, $q, Config_Accounting, $filter,$rootScope) {
    $scope.ApiStorage      = ApiStorage;
    $scope.vimo            = Config_Accounting.vimo;
    
    $scope.currentPage     = 1;
    $scope.item_page       = 20;
    $scope.stateLoading    = true;
    $scope.verifyLoading   = false;
    $scope.unverifyLoading = false;
    $scope.logs            = {}
    $scope.users           = {}

    $scope.load = function (page, email, active, from_date, to_date, deleted, cmd){
        page      = page      || 1;
        email     = email     || "";
        active    = active    || "";
        deleted   = deleted   || "";
        from_date = Date.parse(from_date)/1000 || 0;
        to_date   = Date.parse(to_date)/1000 || 0;
        
        if(cmd == 'export'){
            return window.location = ApiPath + 'vimo-config/oms-verify?page='+ page +'&email='+email+'&active='+active+'&from_date='+from_date+'&to_date='+to_date+'&deleted='+deleted+'&cmd=export'+'&access_token='+$rootScope.userInfo.token;
        }
        $scope.stateLoading = true;
        $http({
            url: ApiPath + 'vimo-config/oms-verify?page='+ page +'&email='+email+'&active='+active+'&from_date='+from_date+'&to_date='+to_date + '&deleted='+ deleted,
            method: "GET",
            dataType: 'json',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (result, status, headers, config) {
            $scope.stateLoading = false;
            if(!result.error){
                $scope.list_data  = result.data;
                $scope.totalItems = result.total;
                $scope.maxSize    = 5;
                $scope.item_page  = result.item_page;
            }        
            else{
                $scope.totalItems = 0;
            }
        });
    }

    $scope.verify = function (item){
        $scope.verifyLoading = true;
        if(!confirm('Bạn chắc chắn muốn xác thực thông tin này')){
            return false;
        }
        $http.post(ApiPath + 'vimo-config/accept', {id: item.id}).success(function (resp){
            $scope.verifyLoading = false;
            if(resp.error){
                alert(resp.error_message);
            }else {
                alert("Xác thực thành công");
                item.active      = 1;
                item.time_accept = Date.now() / 1000;
            }
        })
    }

    var genAction = function (action){
        if(action == 'DELETE'){
            return 'xóa';
        }
        if(action == 'UNACCEPT'){
            return 'hủy xác nhận';
        }
        return 'xác nhận'
    }
    var logHTML = function (id){
        var html = '';
        if($scope.logs[id]){
            angular.forEach($scope.logs[id], function(value, key){
                html += '<p>' + $filter('date')(value.time * 1000, 'dd/MM/yy HH:mm') + ' : <strong>' + $scope.users[id][value.user_accept] + '</strong> '+genAction(value.action)+' - '+value.note || ''+'</p>'
            })
        }
        return html;
    }
    $scope.logHTML = "";
    $scope.getLogs = function (id){
        $scope.loadingLog = true;
        $scope.logHTML = "";
        if($scope.logs[id]){
            $scope.logHTML = logHTML(id);
            $scope.loadingLog = false;
            return;
        }
        $http.get(ApiPath + 'vimo-config/logs/'+id).success(function (resp){
            $scope.loadingLog = false;
            if(!resp.error){
                if(resp.data.length == 0){
                    $scope.logs[id] = [];
                    $scope.users[id] = {}
                }else {
                    $scope.logs[id] = resp.data;
                    $scope.users[id] = resp.user;
                    $scope.logHTML = logHTML(id);
                }
            }
        })   
    }
    

    $scope.unverify = function (item){
        bootbox.prompt({
            message: "<p>Nhập ghi chú cho thông tin này </p>",
            placeholder: "",
            title: "Ghi chú",
            inputType:"textarea",
            callback: function (result) {
                $scope.unverifyLoading = true;

                $http.post(ApiPath + 'vimo-config/unaccept', {id: item.id, note: result}).success(function (resp){
                    $scope.unverifyLoading = false;
                    if(resp.error){
                        alert(resp.error_message);
                    }else {
                        alert("Hủy xác thực thành công");
                        item.active = 0;
                        item.time_accept = 0;
                    }
                })
             }
        });

        
    }
    $scope.deleteVimo = function (id, note, callback){
        $scope.unverifyLoading = true;
        $http.post(ApiPath + 'vimo-config/delete', {id: id, note: note}).success(function (resp){
            $scope.unverifyLoading = false;
            if(resp.error){
                callback(resp.error_message);
            }else {
                callback();
                item.active = 0;
                item.time_accept = 0;
            }
        })
    }

    $scope.openDeleteVimo = function (item){
        bootbox.prompt({
            message: "<p>Nhập ghi chú cho thông tin này </p>",
            placeholder: "",
            title: "Ghi chú",
            inputType:"textarea",
            callback: function (result) {
                $scope.deleteVimo(item.id, result, function (error, resp){
                    if(error){
                        toaster.pop('warning', 'Thông báo', error);
                    }else {
                        $scope.list_data.splice($scope.list_data.indexOf(item), 1);
                        toaster.pop('success', 'Thông báo', 'Xóa thành công');
                    }
                })        
             }
        });
    }

    $scope.createNote = function (id ,note, callback){
        var url = ApiPath + 'vimo-config/create-note';
        $http.post(url, {'id': id, 'note': note}).success(function (resp){
            if(resp.error){
                callback(resp.error_message, null);
            }else {
                callback(null, resp.data);
            }
        })
    }

    $scope.openCreateNote = function (item){
        bootbox.prompt({
            message: "<p>Nhập ghi chú cho thông tin này</p>",
            placeholder: "",
            title: "Ghi chú",
            inputType:"textarea",
            callback: function (result) {
                if(result !== null && result !== ""){
                    $scope.createNote(item.id, result, function (error, resp){
                        if(error){
                            toaster.pop('warning', 'Thông báo', error);
                        }else {
                            item.note = result;
                            toaster.pop('success', 'Thông báo', 'Thêm ghi chú thành công');
                        }
                    })        
                }
             }
        });
    }
    $scope.load();
}]);


angular.module('app')
.controller('VimoCreateController', ['$scope', '$rootScope', '$modal', '$http', '$state', '$window', '$stateParams', 'toaster', 'bootbox','FileUploader', '$timeout', '$q','Config_Accounting', '$filter',
function($scope, $rootScope, $modal, $http, $state, $window, $stateParams, toaster, bootbox,FileUploader, $timeout, $q, Config_Accounting, $filter) {
    console.log('xin chao', 'hello');

    $scope.ApiStorage           = ApiStorage;
    $scope.list_bank            = Config_Accounting.vimo_bank;

    $scope.vimo   = {};
    $scope.loading = {
        cmnd_before: false,   
        cmnd_after: false,
        atm: false,
     }
     $scope.processLoading = false;
     /**
      * get data
      **/
    

    // File ATM 
    var uploaderPopup = $scope.uploaderPopup = new FileUploader({
        url                 : ApiPath+'vimo-config/upload-scan-img',
        headers             : {Authorization : $rootScope.userInfo.token},
        queueLimit          : 5
    });


    uploaderPopup.filters.push({
        name: 'FileFilter',
        fn: function(item, options) {
            var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
            return '|jpeg|pdf|png|'.indexOf(type) !== -1 && item.size < 3000000;
        }
    });

    uploaderPopup.onSuccessItem = function(item, result, status, headers){
        if(!result.error){
            $scope.loading.atm      = false;
            $scope.vimo.atm_image   = result.data;
            toaster.pop('success', 'Thông báo', 'Tải lên thành công');
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Upload thất bại !');
        }
    };

    uploaderPopup.onAfterAddingFile = function (item){
        $scope.loading.atm = true;
        uploaderPopup.uploadAll();
    }

    uploaderPopup.onWhenAddingFileFailed = function (item, filter, options){
        if(item.size > 2000000){
            alert('Dung lượng file vượt quá giới hạn 2MB');
        }
    }

    uploaderPopup.onErrorItem  = function(item, result, status, headers){
        toaster.pop('error', 'Error!', "Upload file lỗi, hãy thử lại.");
    };


    // Upload CMND Before

    var uploaderCMNDBefore = $scope.uploaderCMNDBefore = new FileUploader({
        url                 : ApiPath+'vimo-config/upload-scan-img',
        headers             : {Authorization : $rootScope.userInfo.token},
        queueLimit          : 5
    });


    uploaderCMNDBefore.filters.push({
        name: 'FileFilter',
        fn: function(item, options) {
            var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
            return '|jpeg|pdf|png|jpg'.indexOf(type) !== -1 && item.size < 2000000;
        }
    });

    uploaderCMNDBefore.onWhenAddingFileFailed = function (item, filter, options){
        if(item.size > 2000000){
            alert('Dung lượng file vượt quá giới hạn 2MB');
        }
    }
    uploaderCMNDBefore.onSuccessItem = function(item, result, status, headers){
        if(!result.error){
            $scope.loading.cmnd_before = false;
            $scope.vimo.cmnd_before_image =  result.data;
            toaster.pop('success', 'Thông báo', 'Tải lên thành công');
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Upload thất bại !');
        }
    };

    uploaderCMNDBefore.onAfterAddingFile = function (item){
        console.log(item);
        $scope.loading.cmnd_before = true;
        uploaderCMNDBefore.uploadAll();
    }
    uploaderCMNDBefore.onErrorItem  = function(item, result, status, headers){
        toaster.pop('error', 'Error!', "Upload file lỗi, hãy thử lại.");
    };


    // Upload CMND After

    var uploaderCMNDAfter = $scope.uploaderCMNDAfter = new FileUploader({
        url                 : ApiPath+'vimo-config/upload-scan-img',
        headers             : {Authorization : $rootScope.userInfo.token},
        queueLimit          : 5
    });


    uploaderCMNDAfter.filters.push({
        name: 'FileFilter',
        fn: function(item, options) {
            var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
            return '|jpeg|pdf|png|'.indexOf(type) !== -1 && item.size < 2000000;
        }
    });

    uploaderCMNDAfter.onSuccessItem = function(item, result, status, headers){
        if(!result.error){
            $scope.loading.cmnd_after = false;
            $scope.vimo.cmnd_after_image =  result.data;
            toaster.pop('success', 'Thông báo', 'Tải lên thành công');
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Upload thất bại !');
        }
    };

    uploaderCMNDAfter.onAfterAddingFile = function (item){
        $scope.loading.cmnd_after = true;
        uploaderCMNDAfter.uploadAll();
    }

    uploaderCMNDAfter.onWhenAddingFileFailed = function (item, filter, options){
        if(item.size > 2000000){
            alert('Dung lượng file vượt quá giới hạn 2MB');
        }
    }
    
    uploaderCMNDAfter.onErrorItem  = function(item, result, status, headers){
        toaster.pop('error', 'Error!', "Upload file lỗi, hãy thử lại.");
    };


    $scope.save    = function(data,type){

        var url;
        var data_post = {};

        url         = 'vimo-config/create';
        data_post   = data;

        $scope.processLoading = true;
        $http({
            url: ApiPath + url,
            method: "POST",
            data: data_post,
            dataType: 'json'
        }).success(function (result, status, headers, config) {
            $scope.processLoading = false;
            if(!result.error){
                $scope.savedVimo = true;
                $scope.vimo   = {};
                return toaster.pop('error', 'Thông báo', result.message);
            }          
            else{
                return toaster.pop('error', 'Thông báo', result.message);
            }
        }).error(function (data, status, headers, config) {
            if(status == 440){
                Storage.remove();
            }else{
                toaster.pop('error', 'Thông báo', 'Lỗi kết nối dữ liệu, hãy thử lại!');
            }
        });
    }

}])