'use strict';

// Button create ticket
angular.module('app').controller('TicketCreateCtrl', ['$scope', '$modal', 'Ticket', '$rootScope',
 	function($scope, $modal, Ticket, $rootScope) {
        /**
         *  Action
         **/

        $scope.list_case_type   = {};
        if($rootScope._list_case){
            $scope.list_case = $rootScope._list_case;
            $scope.$emit('CaseTicket', $scope.list_case);
        }else {
            // List case  ticket
            Ticket.ListCase().then(function (result) {
                if(result.data.data){
                    $scope.list_case = result.data.data;
                    $rootScope._list_case       = result.data.data;
                    $scope.$emit('CaseTicket', $scope.list_case);
                }
            });    
        }
        


        $scope.open_popup = function (size,code) {
            $modal.open({
                templateUrl: 'PopupCreate.html',
                controller: 'ModalCreateCtrl',
                size:size,
                resolve: {
                    list_case: function () {
                        return $scope.list_case;
                    },
                    code: function(){
                        return code;
                    }
                }
            });
        };

        $scope.$on('open_popup_ticket', function(event, data) {
            $scope.open_popup('',data);
        });

}]);

//Modal status
angular.module('app').controller('ModalCreateCtrl', ['$scope', '$modalInstance', '$http', 'FileUploader', 'toaster', 'Api_Path', 'User','list_case', 'code', 'Ticket','$timeout', 
function($scope, $modalInstance, $http, FileUploader, toaster, Api_Path, User, list_case, code, Ticket, $timeout) {
    // Config

    $scope.data             = {};
    $scope.datacase         = [];
    $scope.list_case        = list_case;
    $scope.contact          = code;
    $scope.refer            = [];

    $scope.data             = {};
    $scope.frm_submit       = false;
    $scope.show_respond     = true;
    $scope.list_user        = {};
    $scope.list_type        = {};
    var list_suggest_type   = [];
    var customer = {};

    // File Upload
    var uploaderPopup = $scope.uploaderPopup = new FileUploader({
        url                 : Api_Path.Upload+'ticket/',
        alias               : 'TicketFile',
        queueLimit          : 5,
        formData: [
            {
                key: 'request'
            }
        ]
    });

    uploaderPopup.filters.push({
        name: 'FileFilter',
        fn: function(item /*{File|FileLikeObject}*/, options) {
            var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
            return '|vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet|jpeg|pdf|png|'.indexOf(type) !== -1 && item.size < 3000000;
        }
    });

    uploaderPopup.onSuccessItem = function(item, result, status, headers){
        if(!result.error){
            return;
        }
        else{
            toaster.pop('warning', 'Thông báo', 'Upload Thất bại!');
        }
    };

    uploaderPopup.onErrorItem  = function(item, result, status, headers){
        toaster.pop('error', 'Error!', "Upload file lỗi, hãy thử lại.");
    };

    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };

    // get list user
    $scope.getUser  = function(val){
        return User.load(val).then(function (result) {
            if(result){
                return result.data.data.map(function(item){
                    item['data_search'] = '';
                    val = val.toUpperCase();
                    if(val.match(/^SC\d+$/g) && item.tracking_code){
                        item['data_search'] += item.tracking_code + ' - ';
                    }

                    item['data_search'] += item.fullname+' - '+item.email+' - '+item.phone;

                    return item;
                });
            }else{
                return;
            }
        });
    }

    

    
    Ticket.ListCaseType().then(function (result) {
        if(result.data.data){
            $scope.list_type = result.data.data;
        }
    });

    if(code != undefined && code != ''){
        User.load(code).then(function (result) {
            if(result){
                var item = result.data.data[0];
                var _code = code.toUpperCase();
                item['data_search'] = "";
                if(_code.match(/^SC\d+$/g) && item.tracking_code){
                    item['data_search'] += item.tracking_code + ' - ';
                }
                item['data_search'] += item.fullname+' - ' + item.email +' - '+ item.phone;
                $scope.refer.push({text: item.tracking_code});
                $scope.contact  = item['data_search'];
                customer = item;
            }
        })
        
    }

    $scope.getCase  = function(val){
        var data        = [];
        var check       = 0;
        var list_type   = angular.copy($scope.list_type);
        var re = new RegExp(val, 'gi');

        angular.forEach(list_type, function(value, key) {
            if(value.type_name.match(re)){
                check = 1;
                data.unshift(value);
            }else{
                data.push(value);
            }
        });

        if(check == 1){
            list_suggest_type = data;
        }else if(list_suggest_type.length > 0){
            data    = list_suggest_type;
        }

        return  data.map(function(item){
            return item;
        });
    }

    // Create
    $scope.save = function(cotact,datacase){
        if(cotact.id > 0 || customer.id){
            cotact = (cotact.id) ? cotact : customer;
            var refer = [];


            if($scope.refer){
                refer = angular.copy($scope.refer);
            }
            console.log()

            /*if(cotact['tracking_code']){
                refer.push({'text' : cotact['tracking_code']});
            }*/

            var datafrm = {'customer_id':cotact.id,'refer': refer};

            if(datacase.id > 0){
                datafrm.data        = {'title' : datacase.type_name, 'content' : $scope.data.content};
                datafrm.type_id     = datacase.id;
            }else{
                datafrm.data        = {'title' : datacase, 'content' : $scope.data.content};
            }

            $scope.frm_submit       = true;
            $http({
                url: ApiPath+'ticket-request/create',
                method: "POST",
                data:datafrm,
                dataType: 'json'
            }).success(function (result, status, headers, config) {
                if(!result.error){
                    toaster.pop('success', 'Thông báo', 'Thành công!');

                    if(result.id > 0){ // Upload file
                        uploaderPopup.onBeforeUploadItem = function(item) {
                            item.url = Api_Path.Upload+'ticket/'+result.id;
                        };
                        uploaderPopup.uploadAll();
                    }
                }
                else{
                    toaster.pop('error', 'Thông báo', 'Có lỗi , hãy thử lại');
                    $scope.frm_submit       = false;
                }

            }).error(function (data, status, headers, config) {
                toaster.pop('error', 'Thông báo', 'Lỗi hệ thống!');
                $scope.frm_submit       = false;
            });
            $scope.show_respond     = true;
        }

    }

    // toogle show  markdown
    $scope.toogle_show = function(){
        $scope.show_respond = !$scope.show_respond;
    }

}]);
