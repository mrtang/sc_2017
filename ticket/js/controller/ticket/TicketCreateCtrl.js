'use strict';

// Button create ticket
angular.module('app').controller('TicketCreateCtrl', ['$scope', '$modal',
 	function($scope, $modal) {
        /**
         *  Action
         **/

        $scope.open_popup = function (size,code) {
            $modal.open({
                templateUrl: 'PopupCreate.html',
                controller: 'ModalCreateCtrl',
                size:size,
                resolve: {
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
angular.module('app').controller('ModalCreateCtrl', ['$scope', '$modalInstance', '$http', '$rootScope', 'FileUploader', 'toaster', 'Api_Path', 'User','code', 'Ticket', 'Base','bootbox', '$timeout',
function($scope, $modalInstance, $http, $rootScope, FileUploader, toaster, Api_Path, User, code, Ticket, Base, bootbox , $timeout) {
    // Config
       $scope.frm                   = {};
       $scope.frm._datacase              = [];
       $scope.data                  = {};
       
       $scope.contact               = code;
       
       $scope.refer                 = [];
       
       $scope.data                  = {};
       $scope.frm_submit            = false;
       $scope.show_respond          = true;
       $scope.list_user             = {};
       $scope.list_type             = {};
       
       $scope.listOrderDelivery     = []
       $scope.show_confirm_delivery = false;
       $scope.confirm_delivered     = false;
       $scope.loadingUses = false;
       $scope.creater_id = 0;

       code                         = (code && typeof code !== 'string' ) ? (code[0] ? code[0] : code) : code;

        
    var list_suggest_type   = [];

    // File Upload
    var uploaderPopup = $scope.uploaderPopup = new FileUploader({
        url                 : Api_Path.Upload+'ticket/',
        alias               : 'TicketFile',
        headers             : {Authorization : $rootScope.userInfo.token},
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

    $scope.abc = function (item){
        if(item){
            $scope.creater_id = item.id
        }
    }
    // get list user
    $scope.getUser  = function(val, is_code){
        var isTrackingCode = false;
        val                = val.toUpperCase();
        $scope.loadingUses = true;
        if(val.match(/^SC\d+$/g)){
            $scope.onAddedTags(val);
            isTrackingCode = true;
        }


        return User.load(val).then(function (result) {
            $scope.loadingUses = false;
            if(result){
                if(is_code && result.data.data.length > 0){
                    $scope.creater_id = result.data.data[0].id;
                }
                return result.data.data.map(function(item){
                    item['data_search'] = '';

                    if(isTrackingCode && item.tracking_code){
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

    $scope.confirm_delivery_loading = false;
        

        $scope.confirm_delivery = function (item){
            bootbox.prompt({
                message: "<p>Nhập ghi chú cho yêu cầu này để Shipchung hỗ trợ bạn một cách tốt nhất !</p>",
                placeholder: "Thông tin địa chỉ, số điện thoại người nhận trong trường hợp có thay đổi",
                title: "Bạn chắc chắn muốn yêu cầu giao lại đơn hàng này ?",
                inputType:"textarea",
                callback: function (result) {
                    if(result !== null && result !== ""){
                        var statusCompare = 707;
                        statusCompare  = item.group_status.group_status == 18 ? 707: 903; 
                        $scope.confirm_delivery_loading = true;
                        $http.post(ApiPath + 'pipe-journey/create', {
                            'tracking_code' : item.id,
                            'type'          : 1,
                            'pipe_status'   : statusCompare,
                            'note'          : result,
                            'group'         : statusCompare == 707 ? 29 : 31
                        }).success(function (resp){
                            $scope.confirm_delivery_loading = false;
                            if(resp.error){
                                toaster.pop('warning', 'Thông báo', 'Lỗi không thể giao lại đơn hàng này vui lòng liên hệ bộ phận CSKH ');
                            }else {
                                $scope.confirm_delivered = true;
                                toaster.pop('success', 'Thông báo', 'Thành công');
                            }
                        })


                        
                    }else {
                        if (result == "") {
                            $timeout(function (){
                                toaster.pop('warning', 'Thông báo', 'Vui lòng nhập nội dung yêu cầu !');
                            })
                            
                        };
                    }
                 }
            });
        }


    $scope.onAddedTags = function (newTag){
        $scope.ids              = [];

        if(typeof newTag == 'string'){
            $scope.ids.push(newTag)
        }

        angular.forEach($scope.refer, function (value, key){
            $scope.ids.push(value.text);
        })

        if(!$scope.toggleCountinue){
            $scope.referLoading = !$scope.referLoading;

            Ticket.ListReferTicket($scope.ids.join(','), function (err, resp){
                $scope.loadingUses = false;
                if(!err){
                    $scope.listTicketRefer   = resp.data;
                    $scope.listOrderDelivery = resp.order;
                    if(resp.order && resp.order.length > 0){
                        $scope.show_confirm_delivery = true;
                    }else {
                        $scope.show_confirm_delivery = false;
                    }
                }
                $scope.referLoading = !$scope.referLoading;
            });
        }
    }

    $scope.switchShowConfirm = function (){
        $scope.show_confirm_delivery =!$scope.show_confirm_delivery;        
    }

    Base.list_type_case().then(function (result) {
        $scope.list_type = result.data.data;
    });

    if(code != undefined && code != ''){
        $scope.getUser(code, true);
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
    $scope.save = function(cotact,frm){
        var _datacase = frm._datacase;
        if($scope.creater_id > 0){
            var refer = [];

            if($scope.refer){
                refer = angular.copy($scope.refer);
            }

            if(cotact['tracking_code']){
                refer.push({'text' : cotact['tracking_code']});
            }

            var datafrm = {'customer_id':$scope.creater_id,'refer': refer};
            
            if(_datacase.id > 0){
                datafrm.data        = {'title' : _datacase.type_name, 'content' : $scope.data.content};
                datafrm.type_id     = _datacase.id;
            }else{
                datafrm.data        = {'title' : _datacase, 'content' : $scope.data.content};
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
