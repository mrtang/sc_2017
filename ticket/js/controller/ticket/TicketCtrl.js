
//Ticket
angular.module('app').controller('TicketCtrl', ['$scope', '$modal', '$timeout', '$http', '$window', '$stateParams', '$rootScope', 'toaster', 'Storage', 'Config_Status', 'FileUploader', 'Api_Path', 'PhpJs', 'Ticket', 'Notify', 'Order', 'User','Location', 'bootbox', 'Base',
 	function($scope, $modal, $timeout, $http, $window, $stateParams, $rootScope, toaster, Storage, Config_Status, FileUploader, Api_Path, PhpJs, Ticket, Notify, Order, User, Location, bootbox, Base) {

        $scope.ticket = {
            assginer : []
        }
        $timeout(function (){
                $scope.toggleOrder   = false;
                $scope.city          = {};
                $scope.pipe_status   = {};
                $scope.status_group  = {};
                $scope.list_question = {};
                $scope.list_request  = [{id:3, content:'Hài lòng'},{id:2, content:'Tạm được'},{id:1, content:'Không tốt'}];
                $scope.time_start    = $stateParams.time_start;
                $scope.data_status   = Config_Status.ticket_btn;
                $scope.list_color    = Config_Status.order_color;
                $scope.list_type_process  = { 0 :'Chưa phân loại' , 1 :'Đã phân loại'};
                $scope.maxSize       = 4;
                $scope.CourierPrefix = Config_Status.CourierPrefix;
                $scope.listTicketID  = [];
                
                var UserInfo         = angular.copy($rootScope.userInfo);
                $scope.privilege     = UserInfo.privilege;
                $scope.group         = UserInfo.group;
                $scope.nav_status    = Config_Status.Nav_Ticket;
                
                if(UserInfo.privilege == 2){
                    $scope.list_status   = Config_Status.Ticket_Master;
                }else{
                    $scope.list_status   = Config_Status.Ticket;
                }
                
                $scope.list_priority     = Config_Status.priority;
                $scope.priority_case     = Config_Status.priority_case;
                $scope.priority          = 0;
                $scope.type_ticket       = 0;
                $scope.code_search       = '';
                $scope.User              = {};
                $scope.source            = 'web';
                $scope.show_reply        = false;
                
                $scope.log_view          = {};
                $scope.log               = {};
                $scope.list_order        = {};
                $scope.list_type         = {};
                
                $scope.list_status_order = {};
                $scope.list_group        = {};
                $scope.list_case         = {};
                $scope.isVip             = 0;
                $scope.list_ticket_group = [];
                $scope.type_process      = '';
                
                $scope.filter_assginer   = {
                
                };
                $scope.picked = {
                    datePicked : {

                    }
                };
                


            // List case  ticket
            Base.list_case().then(function (result) {
                $scope.list_case = result.data.data;
            });

            // type case
            Base.list_type_case().then(function (result) {
                $scope.list_type = result.data.data;
            });

            //get list status
            Base.status().then(function (result) {
                $scope.list_status_order    = result.data.data;
            });

            // group status
            Base.group_status().then(function (result) {
                $scope.status_group = {}
                if(result.data.list_group){
                    angular.forEach(result.data.list_group, function(value) {
                        $scope.list_group[+value.id] = value.name;
                        if(value.group_order_status){
                            angular.forEach(value.group_order_status, function(v) {
                                $scope.status_group[+v.order_status_code]    = v.group_status;
                            });
                        }
                    });
                }
            });

            Base.city().then(function (result) {
                $scope.list_city  = result.data.data;
                angular.forEach(result.data.data, function(value) {
                    $scope.city[value.id]   = value.city_name;
                });
            });

            Base.assign_group().then(function(result) {
                $scope.list_ticket_group = result.data.data;
            });

            Base.user_admin().then(function (result) {
                $timeout(function (){
                    $scope.listAdminRoot       = result.data.data;
                })
            });

            $scope.config_action    = {
              'type_process': 'Đã phân loại',
              'status'  : 'Đã thay đổi trạng thái',
              'case'    : {
                  0 : 'Đã bỏ một loại yêu cầu',
                  1 : 'Đã thêm loại yêu cầu'
              },
              'time_over' : 'Đã cập nhật thời gian xử lý',
              'assign'    : {
                  0 : 'Hủy giao cho',
                  1 : 'Giao cho'
              },
              'priority'    : 'Đã thay đổi yêu cầu'
            };

            $scope.tab_action   = {
                'web'   : {
                    'content' : 'Gửi phản hồi',
                    'bg'      : 'bg-info'
                },
                'sms'   : {
                    'content' : 'Nhắn SMS',
                    'bg'      : 'bg-success'
                },
                'note'  : {
                    'content' : 'Ghi chú',
                    'bg'      : 'bg-warning'
                }
            };

            $scope.vip_filter = {
                0   :   'Tất cả khách hàng',
                1   :   'Hiển thị theo khách VIP',
                7   :   'Hiển thị theo khách Loyalty',
                6   :   'Hiển thị theo khách mới',
                2   :   'Yêu cầu do tôi assign',
                3   :   'Yêu cầu assign cho tôi',
                4   :   "Yêu cầu chưa assign cho ai",
                5   :   "Đã giao cho HVC"
            };

            $scope.currentPage      = 1;
            $scope.item_page        = 20;

            $scope.data_respond     = {};
            $scope.list_time_process    = [{code:28800, content:'1 Ngày'},{code:86400, content:'3 Ngày'},{code:144000, content:'5 Ngày'},{code:201600, content:'1 Tuần'}, {code:288000, content:'10 Ngày'}, {code:403200, content:'2 Tuần'}];
            $scope.list_feedback    = {};
            $scope.list = {
                admin: []
            };

            /*$scope.list.admin        = [];*/

            $scope.show_wating      = true;
            $scope.listAdminRoot    = [];

            // File Upload
            var uploader = $scope.uploader = new FileUploader({
                url                 : Api_Path.Upload+'ticket/',
                alias               : 'TicketFile',
                queueLimit          : 5,
                headers             : {Authorization : $rootScope.userInfo.token},
                removeAfterUpload   : true,
                headers : {
                    'Authorization': UserInfo.token // X-CSRF-TOKEN is used for Ruby on Rails Tokens
                },

                formData: [
                    {
                        key: 'feedback'
                    }
                ]
            });

            uploader.filters.push({
                name: 'FileFilter',
                fn: function(item /*{File|FileLikeObject}*/, options) {
                    var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
                    return '|vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet|jpeg|pdf|png|'.indexOf(type) !== -1 && item.size < 3000000;
                }
            });

            uploader.onSuccessItem = function(item, result, status, headers){
                if(!result.error){

                    return;
                }
                else{
                    toaster.pop('warning', 'Thông báo', 'Upload Thất bại!');
                }
            };

            uploader.onErrorItem  = function(item, result, status, headers){
                toaster.pop('error', 'Error!', "Upload file lỗi, hãy thử lại.");
            };

            //ng-clip
            $scope.fallback = function(copy) {
                $window.prompt('Press cmd+c to copy the text below.', copy);
            };

            /**
             * get data
             **/
            
            $scope.AssginToCourierLoading = false;
            $scope.AssginToCourier = function (ticket){
                
                var listSCCode = [];
                angular.forEach(ticket.refer, function (value, key){
                    if(value.type == '1'){
                        listSCCode.push(value.code);
                    }
                })
                $scope.AssginToCourierLoading = true;
                $http.post(ApiPath + 'ticket-request/assgin-postoffice', {id: ticket.id, order: listSCCode.join(',')}).success(function (resp){
                    $scope.AssginToCourierLoading = false;
                    if(resp.error){
                        toaster.pop('warning', 'Thông báo', resp.error_message);
                        return;
                    }

                    angular.forEach(resp.list_id, function (value, key){
                        $scope.detail.assign.push({assign_id: value.id});
                    });

                    toaster.pop('success', 'Thông báo', "Giao thành công");
                })

            }

            $scope.isDuplicate = function (item){
                if(item.refer){
                    for (var i = item.refer.length - 1; i >= 0; i--) {
                        if(item.refer[i].type == 3){
                            return true;
                            break;
                        }
                    };
                }
                return false;
            }

            $scope.exportAdditionUrl = "";
            $scope.onDatepickedChange = function (date){
                
                if(date.startDate.getTime() / 1000 !== date.endDate.getTime() / 1000){
                    $scope.exportAdditionUrl = '&time_create_start=' + date.startDate.getTime()/ 1000  + '&time_create_end=' + date.endDate.getTime() / 1000;
                }else {
                    $scope.exportAdditionUrl = "";
                }
            }
            
            
            
            $scope.exportDefault = function (){
                var link =  $scope.link_export + 'ticket-request/ticketbyprivilege?cmd=export&status='+ $scope.status + '&time_start=' + $scope.time_start + '&search=' + ($scope.search || "") + '&isVip=' + $scope.isVip + '&priority=' + $scope.priority + '&type_ticket=' + $scope.type_ticket;
                if($scope.filter_assginer){
                    var _list = [];
                    angular.forEach($scope.filter_assginer, function (value, key){
                        if(value){
                            _list.push(key); 
                        }
                    });

                    link += "&by_assigner=" + _list.join(',');
                }
                link += '&access_token=' + $rootScope.userInfo.token 
                
                return link;
            }

            $scope.getReferOrder = function (id){
                
                var url = ApiPath + 'ticket-request/order-refer/' + id;
                $http.get(url).success(function(resp){
                    $scope.listOrderRef = resp.data;
                    $scope.listAddress = resp.address;
                    $scope.listDistrict = resp.district;
                }).error(function (){
                    toaster.pop('warning', 'Thông báo', 'Không thể tải thông tin đơn hàng liên quan.');
                });
            } 


            $scope.unRefer = function (ReferItem, ReferId){
                if(!window.confirm('Bạn muốn hủy liên kiết này ? ')){
                    return false;
                }
                
                $http.post(ApiPath + 'ticket-refer/remove-refer', {
                    'refer_id': ReferId
                }).success(function (resp){
                    if (!resp.error) {
                        $scope.detail.refer.splice($scope.detail.refer.indexOf(ReferItem), 1);
                        $scope.detail.link = [];
                        toaster.pop('success', 'Thông báo', 'Thành công');
                    };
                })
            }

            $scope.checkPipe = function (item){
                var statusCompare = 707;
                    statusCompare  = $scope.in_array(item.status, [60]) ? 903 : 707;
                if(!item.pipe_journey){
                    return false;
                }
                for (var i = item.pipe_journey.length - 1; i >= 0; i--) {

                    if(item.pipe_journey[i].pipe_status == statusCompare){
                        return true;
                    }
                };
            }
            $scope.displayConfirmDelivery = function (item){
                return $scope.in_array(item.status, [75,77, 56, 57, 59, 60]);
                
            }
            $scope.in_array = function (needle, haystack, argStrict) {
                var key = '',
                strict  = !! argStrict;

                if (strict) {
                for (key in haystack) {
                  if (haystack[key] === needle) {
                    return true;
                  }
                }
                } else {
                for (key in haystack) {
                  if (haystack[key] == needle) {
                    return true;
                  }
                }
                }

                return false;
            }

            $scope.confirm_delivery = function (item){
                if($scope.checkPipe(item)){
                   bootbox.alert('Bạn đã gửi yêu cầu giao lại đơn hàng này, không thể gửi thêm .');
                   return ; 
                }
                
                var msg = "Bạn chắc chắn muốn yêu cầu giao lại đơn hàng này ?";
                bootbox.prompt({
                    message: "<p>Nhập ghi chú cho yêu cầu này để Shipchung hỗ trợ bạn một cách tốt nhất !</p>",
                    placeholder: "Thông tin địa chỉ, số điện thoại người nhận trong trường hợp có thay đổi ",
                    title: msg,
                    inputType:"textarea",
                    callback: function (result) {
                        if(result !== null && result !== ""){
                            $scope.change(item.status, 67, 'status', item, result,  function (err, resp ){
                                $scope.waiting_status   = false;
                                if(!err){
                                    item.pipe_journey.push(resp);
                                    toaster.pop('success', 'Thông báo', 'Gửi yêu cầu giao lại thành công');
                                }
                            });
                        }else {
                            $timeout(function (){
                                if(result == ""){
                                    toaster.pop('warning', 'Thông báo', 'Vui lòng nhập nội dung');
                                }
                                
                            })
                            
                        }
                     }
                });
            }
            $scope.confirm_return = function (item){
                var msg = "Bạn chắc chắn muốn xác nhận chuyển hoàn đơn hàng này ?";
                bootbox.confirm( msg , function (result) {
                    if(result){
                        $scope.change(item.status, 61, 'status', item, "Người bán xác nhận chuyển hoàn",  function (err, resp ){
                            $scope.waiting_status   = false;
                            if(!err){
                                /*$scope.list_data.splice($scope.list_data.indexOf(item),1);*/
                                item.pipe_journey.push(resp);
                                //sendProcessAction(item.id, processType);
                            }
                        });
                    }
                });
            }


            $scope.acceptStatus = function (status, sc_code, city, note, courier, callback){
                var data = {};
                if(status && sc_code && courier && city && note){
                    data['status']  = status;
                    data['sc_code'] = sc_code;
                    data['courier'] = courier;
                    data['city']    = city;
                    data['note']    = note;

                    Order.AcceptStatus(data, function (err, resp){
                        if(!err){
                            (callback && typeof callback == 'function') ? callback(null, true) : null;
                        }else {
                            (callback && typeof callback == 'function') ? callback(true, null) : null;
                        }
                    })
                }
                
            }

            $scope.change   = function(old_value, new_value, field, item, note, callback){
                var dataupdate = {};

                if(new_value != undefined && new_value != ''&& old_value != new_value && item.id > 0 ){
                    // Update status
                    if(field == 'status'){
                        if(new_value == 61){
                            $scope.acceptStatus(new_value, item.tracking_code, 'SC', "Khách hàng báo chuyển hoàn", $scope.CourierPrefix[item.courier_id], function (err, result){
                                if(err){
                                    callback(true, true);
                                }else {
                                    callback(null, true);
                                }
                            });    
                        }else if(new_value == 67) {
                            var statusCompare = 707;
                            statusCompare     = $scope.in_array(item.status, [60]) ? 903 : 707;


                            $http.post(ApiPath + 'pipe-journey/create', {
                                'tracking_code' : item.id,
                                'type'          : 1,
                                'pipe_status'   : statusCompare,
                                'note'          : note,
                                'group'         : statusCompare == 707 ? 29 : 31
                            }).success(function (resp){
                                if(resp.error){
                                    callback(true, true);
                                }else {
                                    callback(null, {
                                        'tracking_code' : item.id,
                                        'type'          : 1,
                                        'pipe_status'   : statusCompare,
                                        'note'          : note,
                                        'group'         : statusCompare == 703 ? 29 : 31,
                                        time_create     : Date.now()
                                    });
                                }
                            })

                            
                        }else {
                            $scope.acceptStatus(new_value, item.tracking_code, 'SC', note, $scope.CourierPrefix[item.courier_id], function (err, result){
                                if(err){
                                    callback(true, true);
                                }else {
                                    callback(null, true);
                                }
                                
                            });
                        }
                        
                        return false;
                        $scope.waiting_status   = true;
                    }
                }
                return;
            };

            $scope.getGroupPipe = function (){
                return $http({
                    url: ApiPath + 'pipe-status/pipebygroup',
                    method: "GET",
                    dataType: 'json'
                }).success(function (result, status, headers, config) {
                    if(result.error){
                        toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    }
                    
                    $scope.list_pipe_status      = result.data;
                    angular.forEach(result.data, function(value) {
                        
                        $scope.pipe_status[value.status]    = value.name;
                    });
                    
                }).error(function (data, status, headers, config) {
                    toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại, vui lòng thử lại!');
                })
            }

            $scope.processTimer = function(time){
                var currentDate = new Date();
                var expireDate  = moment(time.time_update * 1000).add(24, 'hours');
                var isExpired = expireDate.isBefore(currentDate);
                return {
                    isExpired   : isExpired,
                    diff        : expireDate.diff(currentDate, 'hours')
                }
            }


            // get list request
            $scope.change_tab   = function(status,page,search,priority,isVip,type_process){
                if(page > 0){
                    $scope.priority             = priority;

                    if(type_process != undefined) $scope.type_process         = type_process;

                    $scope.currentPage          = page;
                    $scope.show_wating          = true;
                    $scope.list_data_request    = {};
                    $scope.detail               = {};
                    $scope.code_search          = '';
                    $scope.log_view             = {};
                    if(isVip != undefined) {
                        $scope.isVip = isVip;
                    }
                    if(status != ''){
                        $scope.status = status;
                        var url = ApiPath+'ticket-request/ticketbyprivilege?page='+page+'&status='+status+'&time_start='+$scope.time_start+'&priority='+$scope.priority;

                        if(search != ''){
                            if(search != undefined){
                                url += '&search='+search;
                            }
                        }else if($stateParams.id != undefined && $stateParams.id != ''){
                            url += '&search='+$stateParams.id;
                        }

                        if($scope.type_ticket != undefined && $scope.type_ticket !=  ''){
                            url += '&type_ticket='+$scope.type_ticket;
                        }
                        if(isVip != undefined) {
                            url += '&isVip='+isVip;
                        }

                        if($scope.type_process != undefined && $scope.type_process != ''){
                            url += '&type_process='+$scope.type_process;
                        }

                        if($scope.filter_assginer){
                            var _list = [];
                            angular.forEach($scope.filter_assginer, function (value, key){
                                if(value){
                                    _list.push(key); 
                                }
                            });

                            url += "&by_assigner=" + _list.join(',');
                        }

                        $http({
                            url: url,
                            method: "GET",
                            dataType: 'json',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                        }).success(function (result, status, headers, config) {
                            if(!result.error){
                                $scope.list_data_request        = result.data;
                                $scope.total                    = result.total;
                                $scope.total_group              = result.total_group;
                                $scope.totalItems               = $scope.total_group[$scope.status];

                                if(result.log_view){
                                    angular.forEach(result.log_view, function(value, key) {
                                        $scope.log_view[value.ticket_id]    = value.view;
                                    });
                                }

                                if($scope.list_data_request.length > 0){
                                   $scope.show_detail($scope.list_data_request[0]['id']);
                                }else{
                                    if(search){
                                        if(search.match(/^SC\d{6,10}$/g)){
                                            $scope.code_search  = search;
                                        }
                                    }
                                }
                            }
                            else{
                                toaster.pop('warning', 'Thông báo', result.message);
                            }

                            $scope.show_wating = false;
                        }).error(function (data, status, headers, config) {
                            if(status == 440){
                                Storage.remove();
                            }else{
                                toaster.pop('error', 'Thông báo', 'Lỗi kết nối dữ liệu, hãy thử lại!');
                            }
                        });
                    }
                }
                return;
            }
            $scope.change_tab('ALL',1,'',0);

            $scope.getGroupPipe();


            $scope.renderAssginerName = function (user_id){
                if($scope.list.admin_name.hasOwnProperty(user_id)){
                    return $scope.list.admin_name[user_id];
                }else if($scope.User.hasOwnProperty[user_id]) {
                    return $scope.User[user_id].fullname;
                }

                return "Chưa xác định"
                
            };

            //detail
            $scope.show_detail = function (id) {
                var list_assign                 = [];
                $scope.show_reply               = false;
                $scope.detail                   = {};
                $scope.source                   = 'web';
                $scope.list_feedback            = {};
                $scope.log                      = {};
                $scope.list_order               = {};
                $scope.data_respond.content     = '';
                
                $timeout(function (){
                    $scope.list.admin                = angular.copy($scope.listAdminRoot);    
                    $scope.list.admin_name = [];
                    angular.forEach($scope.list.admin, function (value, key){
                        if (value.user && value.user.fullname) {
                            $scope.list.admin_name[value.user_id] = value.user.fullname;
                        };
                        
                    })

                }, 0)

                $timeout.cancel(timeout);
                retime_feedback();
                if(id > 0){
                    $scope.show_wating          = true;
                    $http({
                        url: ApiPath+'ticket-request/show/'+id,
                        method: "GET",
                        dataType: 'json',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).success(function (result, status, headers, config) {
                        if(!result.error){
                            

                            if($scope.log_view[id] == undefined){
                                $scope.log_view[id]    = {};
                            }
                            $scope.log_view[id]       = 1;

                            if(result.data){
                                angular.forEach(result.data, function(value, key) {
                                    list_assign.push(value.assign_id);
                                });
                                removeListAdmin(list_assign);
                            }

                            if(result.list_order){
                                angular.forEach(result.list_order, function(value) {
                                    $scope.list_order[value.tracking_code]  = value;
                                });
                            }

                            if(result.user){
                                angular.forEach(result.user, function(value) {
                                    $scope.User[value.id]   = {};
                                    $scope.User[value.id]['md5_email']          = PhpJs.md5(value.email);
                                    $scope.User[value.id]['fullname']           = value.fullname;
                                    $scope.User[value.id]['identifier']         = value.identifier;
                                    $scope.User[value.id]['phone']              = value.phone;
                                    $scope.User[value.id]['email']              = value.email;
                                    $scope.User[value.id]['time_create']        = value.time_create;
                                    $scope.User[value.id]['time_last_login']    = value.time_last_login;
                                    $scope.User[value.id]['loyalty']    = value.loyalty;

                                    if($scope.User[value.id]['time_create'] &&  (Date.now() / 1000) - $scope.User[value.id]['time_create'] < 30 * 86400){
                                        $scope.User[value.id]['is_newbie']       = true;
                                    }

                                    if($scope.User[value.id]['time_last_login'] &&  (Date.now() / 1000) - $scope.User[value.id]['time_last_login'] > 30 * 86400){
                                        
                                        $scope.User[value.id]['is_return']       = true;
                                    }

                                    if(value.user_info != undefined && value.user_info.is_vip != undefined) {
                                        $scope.User[value.id]['is_vip']       = value.user_info.is_vip;
                                    }
                                });
                                
                            }

                            $scope.detail           = result.data;
                            $scope.list_feedback    = result.feedback;
                            $scope.log              = result.log;
                            $scope.listTicketID = result.ticket_refer;

                            $scope.getReferOrder(id);
                        }  
                        else{
                            toaster.pop('warning', 'Thông báo', 'Tải danh sách yêu cầu lỗi !');
                            $scope.detail       = {};
                        }
                        $scope.show_wating          = false;
                    }).error(function (data, status, headers, config) {
                        if(status == 440){
                            Storage.remove();
                        }else{
                            toaster.pop('error', 'Thông báo', 'Lỗi kết nối dữ liệu, hãy thử lại!');
                            $scope.detail       = {};
                        }
                    });
                }
                return;
            }

            $scope.saveIdentifier = function(userID, data) {
                User.identifier(userID,{identifier: data})
                    .success(function(response) {
                        if(response.error) {
                            toaster.pop('error', 'Thông báo', response.message);
                        } else {
                            toaster.pop('success', 'Thông báo', response.message);
                        }
                    })
            };

            $scope.setMessage = function(message) {
                $scope.data_respond.content = message;
            };

            $scope.get_feedback = function(id){
                Ticket.ListFeedback(id).then(function (result) {
                    if(result.data.data){
                        $scope.list_feedback    = result.data.data;
                    }
                });
            }

            $scope.save = function(type,value){
                if($scope.detail.id > 0 && type != undefined && type != '' && value !== undefined && value !== ''){
                    var data = {};
                    var url  = ApiPath;
                    var idx  = indexOfListRequest($scope.detail.id);
                    switch(type) {
                        case 'status':

                            if(value  == 'CLOSED' && $scope.detail.list_ticket_refer.length > 0 ){
                                var msg = 'Bạn muốn đóng yêu cầu này và những yêu cầu liên quan : ';
                                angular.forEach($scope.detail.link, function (value, key){
                                    msg +=  '#' + value.id + ', ';
                                })

                                var result = confirm(msg);
                                if(result){
                                    data['list_ticket_refer'] = $scope.detail.list_ticket_refer || "";
                                }else {
                                    return false;
                                }
                            }
                            data[type] = value;
                            url += 'ticket-request/edit/' + $scope.detail.id;
                            
                        break;

                        case 'feedback':
                            if($scope.data_respond.content != undefined && $scope.data_respond.content != ''){
                                $scope.show_reply   = true;
                                url += 'ticket-feedback/create/' + $scope.detail.id;
                                data['content']   = $scope.data_respond.content;
                                data['source']    = $scope.source;

                                if($rootScope.userInfo.courier_id > 0){
                                    data['source']    = 'note';
                                }

                                if(value == 'ASSIGNED' && (['web','sms'].indexOf(data['source']) != -1)){
                                    data['status']      = 'PENDING_FOR_CUSTOMER';
                                }
                                if(value == 'PROCESSED'){
                                    data['status']      = 'PROCESSED';
                                }
                            }
                            break;

                        case 'time_over':
                        case 'priority':
                        case 'type_process':
                            url += 'ticket-request/edit/' + $scope.detail.id;
                            data[type]    = value;

                            if(type == 'priority' && window._socketIO){
                                var __sent = {
                                    user_info       : {
                                        'id'          : $rootScope.userInfo.id,
                                        'fullname'  : $rootScope.userInfo.fullname,
                                    },
                                    ticket_id       : $scope.detail.id,
                                    title           : $scope.detail.title,
                                    priority        : value,
                                    priority_name   : $scope.list_priority[value],
                                    list_assgin     : $scope.detail.assign.map(function (value){
                                        return value.assign_id;
                                    })
                                };
                                window._socketIO.emit('ticket:change:priority', __sent)
                                
                                
                            }
                            break;
                        case 'user_assign':
                            try {
                                value = JSON.parse(value);
                            } catch(e) {
                            }
                            url += 'ticket-assign/create';
                            data['ticket_id']   = $scope.detail.id;
                            data['assign_id']   = value.user_id;
                            data['active']      = 1;
                            data['value']       =   value;
                            break;

                        case 'type':
                            value = JSON.parse(value);
                            url += 'ticket-case-ticket/create/'+$scope.detail.id;
                            data['type_id'] = value.id;
                            data['active']  = 1;
                            break;

                        case 'refer':
                            url += 'ticket-refer/create/'+$scope.detail.id;
                            data['refer'] = [
                                {
                                    text : value
                                }
                            ];
                        break;

                        default:
                            return;
                    }
                    $http({
                        url: url,
                        method: "POST",
                        data: data,
                        dataType: 'json',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).success(function (result, status, headers, config) {
                        if (!result.error) {
                            toaster.pop('success', 'Thông báo', 'Thành công !');

                            switch(type) {
                                case 'feedback':
                                    if(data.content){
                                        var res = data.content.match(/(@)[0-9]{1,}/g);
                                        var ticketID = [];
                                        angular.forEach(res, function(v,i) {
                                            if(ticketID.indexOf(v) == -1) {
                                                data.content = data.content.replace(v,'<a data-ng-click="show_detail(' + v.replace("@","") + ')">' + v + '</a>');
                                                ticketID.push(v);
                                            }
                                        });
                                        $scope.list_feedback.unshift({user_id:UserInfo.id, content:data.content, source : data['source']});
                                        if($scope.User[UserInfo.id] == undefined || !$scope.User[UserInfo.id]){
                                            $scope.User[UserInfo.id]   = {};
                                            $scope.User[UserInfo.id]['fullname']    = UserInfo.fullname;
                                            $scope.User[UserInfo.id]['email']       = UserInfo.email;
                                            $scope.User[UserInfo.id]['phone']       = UserInfo.phone;
                                            $scope.User[UserInfo.id]['email_md5']   = PhpJs.md5(UserInfo.email);
                                        }
                                        if($scope.list_data_request[idx].user_id != result.user_id && $scope.list_data_request[idx].status == 'CUSTOMER_REPLY' && $scope.source != 'note') {
                                            $scope.detail.status = 'PENDING_FOR_CUSTOMER';
                                            $scope.list_data_request[idx].status = 'PENDING_FOR_CUSTOMER';
                                        }

                                        if(result.id > 0){ // Upload file
                                            uploader.onBeforeUploadItem = function(item) {
                                                item.url = Api_Path.Upload+'ticket/'+result.id;
                                            };
                                            uploader.uploadAll();
                                        }
                                        $scope.data_respond.content = '';
                                    }


                                    if((value == 'ASSIGNED' && (['web','sms'].indexOf(data['source']) != -1)) || value == 'PROCESSED'){
                                        $scope.detail.status    = data['status'];
                                        $scope.log.unshift({user_id : UserInfo.id, type: 'status', time_create_str: '1 phút', new : {'status' : data['status']}});
                                        if($scope.list_data_request[idx]){
                                            $scope.list_data_request[idx].status = data['status'];
                                        }
                                    }
                                    $scope.show_reply   = false;

                                    break;
                                case 'status':
                                    if($scope.list_data_request[indexOfListRequest($scope.detail.id)]){
                                        $scope.list_data_request[indexOfListRequest($scope.detail.id)].status = data['status'];
                                    }
                                    $scope.log.unshift({user_id : UserInfo.id, type: 'status', time_create_str: '1 phút', new : {'status' : data['status']}});
                                    $scope.detail.status    = data['status'];
                                    break;

                                case 'user_assign':
                                    if(result.list_id.length > 0) {
                                        angular.forEach($scope.list.admin, function(value, key) {
                                            if(result.list_id.indexOf(value.user_id) != -1){
                                                if($scope.User[value.user_id] == undefined || !$scope.User[value.user_id]){
                                                    $scope.User[value.user_id]   = {};
                                                    $scope.User[value.user_id]['fullname']  = value.user.fullname;
                                                    $scope.User[value.user_id]['email']     = value.user.email;
                                                    $scope.User[value.user_id]['phone']     = value.user.phone;
                                                    $scope.User[value.user_id]['email_md5'] = PhpJs.md5(value.user.email);
                                                }
                                                removeListAdmin([value.user_id]);
                                                $scope.detail.assign.unshift({assign_id: value.user_id, time_create_str: 'Vừa cập nhật'});
                                                $scope.log.unshift({user_id : UserInfo.id, type: 'assign', time_create_str: '1 phút', new : {'assign_id' : value.user_id, 'active' : 1}});
                                            }
                                        });

                                        if($scope.detail.status == 'NEW_ISSUE'){
                                            if($scope.list_data_request[indexOfListRequest($scope.detail.id)]){
                                                $scope.list_data_request[indexOfListRequest($scope.detail.id)].status = 'ASSIGNED';
                                            }
                                            $scope.log.unshift({user_id : UserInfo.id, type: 'status', time_create_str: '1 phút', new : {'status' : 'ASSIGNED'}});
                                            $scope.detail.status    = 'ASSIGNED';
                                        }
                                    }
                                    break;

                                case 'type':
                                   $scope.detail.case_ticket.unshift({type_id : value, case_type: {type_name : value.type_name}});
                                    $scope.log.unshift({user_id : UserInfo.id, type: 'case', time_create_str: '1 phút', new : {'active' : 1}});
                                    break;

                                case 'priority':
                                    if($scope.list_data_request[indexOfListRequest($scope.detail.id)]){
                                        $scope.list_data_request[indexOfListRequest($scope.detail.id)].priority = data['priority'];
                                    }
                                    $scope.log.unshift({user_id : UserInfo.id, type: 'priority', time_create_str: '1 phút', new : {'priority' : value}});
                                    break;

                                case 'time_over':
                                    $scope.log.unshift({user_id : UserInfo.id, type: 'time_over', time_create_str: '1 phút'});

                                    if(result.time_over > 0){
                                        $scope.detail.time_over =  result.time_over;
                                    }

                                    if($scope.list_data_request[idx]){
                                        $scope.list_data_request[idx].time_over_before    = PhpJs.ScenarioTime($scope.detail.time_over - Date.parse(new Date())/1000);
                                    }

                                    break;

                                case 'refer':
                                    var val = { code : value};
                                    $scope.detail.refer.unshift(val);
                                    break;

                                default:
                                    break;
                            }

                            if($scope.list_data_request[idx]){
                                $scope.list_data_request[idx].time_update           = Date.parse(new Date())/1000;
                                $scope.list_data_request[idx].time_update_before    = '1 phút';
                            }
                        }
                        else {
                            toaster.pop('error', 'Thông báo', result.message);
                        }
                    }).error(function (data, status, headers, config) {
                        $scope.show_reply   = false;
                        if (status == 440) {
                            Storage.remove();
                        } else {
                            toaster.pop('error', 'Thông báo', 'Lỗi kết nối dữ liệu, hãy thử lại!');
                        }
                    });
                }
                return;
            }

            $scope.change_action = function(val){
                $scope.source   = val;
            }

            var indexOfListRequest = function(id) {
                var length = $scope.list_data_request.length;
                for(var i = 0; i< length; i++) {
                    if ( id === $scope.list_data_request[i].id) {
                        return i;
                    }
                }
                return -1;
            }

            var removeListAdmin = function(data) {
                var length = $scope.list.admin.length;
                for(var i = 0; i< length; i++) {
                    if($scope.list.admin[i] && data.indexOf($scope.list.admin[i].user_id) != -1){
                        $scope.list.admin.splice(i, 1);
                    }
                }
            }

            $scope.remove = function(type,value,index){
                if($scope.detail.id > 0 && type != undefined && type != '' && value != undefined && value != ''){
                    var data = {};
                    var url  = ApiPath;
                    var idx  = indexOfListRequest($scope.detail.id);

                    switch(type) {
                        case 'user_assign':
                            url += 'ticket-assign/create';
                            data['ticket_id']   = $scope.detail.id;
                            data['assign_id']   = value;
                            data['active']      = 0;
                            break;

                        case 'type':
                            url += 'ticket-case-ticket/create/'+$scope.detail.id;
                            data['type_id']             = value;
                            data['active']              = 0;
                            break

                        default:
                            return;
                    }

                    $http({
                        url: url,
                        method: "POST",
                        data: data,
                        dataType: 'json',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).success(function (result, status, headers, config) {
                        if (!result.error) {
                            toaster.pop('success', 'Thông báo', 'Thành công !');

                            switch(type) {

                                case 'user_assign':

                                    $scope.detail.assign.splice(index, 1);
                                    if(!$scope.detail.assign){
                                        $scope.save('status','NEW_ISSUE');
                                    }
                                    $scope.log.unshift({user_id : UserInfo.id, type: 'assign', time_create_str: '1 phút', new : {'assign_id' : value, 'active' : 0}});
                                    $scope.list.admin.push({user_id : value, user : { id: value, email : $scope.User[value].email, fullname : $scope.User[value].fullname }});
                                    break;

                                case 'type':
                                    $scope.detail.case_ticket.splice(index, 1);
                                    $scope.log.unshift({user_id : UserInfo.id, type: 'case', time_create_str: '1 phút', new : {'active' : 0}});
                                    break;

                                default:
                                    break;
                            }

                            if($scope.list_data_request[idx]){
                                $scope.list_data_request[idx].time_update           = Date.parse(new Date())/1000;
                                $scope.list_data_request[idx].time_update_before    = '1 phút';
                            }
                        }
                        else {
                            toaster.pop('error', 'Thông báo', 'Thất bại!');
                        }
                    }).error(function (data, status, headers, config) {
                        if (status == 440) {
                            Storage.remove();
                        } else {
                            toaster.pop('error', 'Thông báo', 'Lỗi kết nối dữ liệu, hãy thử lại!');
                        }
                    });
                }
                return;
            }

            var retime_feedback = function(){
                timeout = $timeout(function(){
                    $scope.get_feedback($scope.detail.id);
                    retime_feedback();
                },180000);
            }


            $scope.searchTicket = function(term) {
                var listTicketID = [];
                //return $scope.listTicketID;
                var i = 0;
                angular.forEach($scope.listTicketID,function(v) {
                    if(i < 10) {
                        //key check = v.id && v.referCode[offset].code
                        var filter = false;
                        var id = v.id + "";
                        if(id.indexOf(term) >= 0) {
                            filter = true;
                        }
                        angular.forEach(v.referCode,function(value) {
                            if(value.code.indexOf(term) >= 0) {
                                filter = true;
                            }
                        });
                        if(filter) {
                            listTicketID[i] = v;
                            ++i;
                        }
                    }
                });
                $scope.listTicketIDFiltered = listTicketID;
                return $scope.listTicketIDFiltered;
            }
            
            $scope.getTicketText = function(item) {
                return '@' + item.id;
                //      return '[<ticket><a href="#/ticket/detail/' + item.id + '">#' + (item.id) + '</a> ' + item.title + '</ticket>]';
            };


            $scope.openFilterAssginer = function (list_ticket_group, list_admin, assginer){
                var modalFilterAssginer =  $modal.open({
                    templateUrl: 'tpl/ticket/modal.filter_assginer.html',
                    controller: function($scope, $timeout, $modalInstance, Order, ticket_group, admins, assginer) {

                        $scope.ticket_group = ticket_group;
                        $scope.admins   = admins;
                        $scope.filter_user_selected = {
                            assginer: {},
                            assgin_group: []
                        };

                        $scope.select2Change = function (groups){
                            $scope.filter_user_selected.assginer = {};
                            $scope.filter_user_selected.assgin_group = groups;
                            
                            angular.forEach(groups, function (value, key){
                                var _item = JSON.parse(value);
                                angular.forEach(_item.user_assign, function (v, k){
                                    $timeout(function (){
                                        $scope.filter_user_selected.assginer[v.assign_id] = true;
                                    })
                                    
                                })
                            })
                        }

                        $scope.save = function (){
                            $modalInstance.dismiss($scope.filter_user_selected.assginer);
                        }

                        $scope.cancel = function() {
                            $modalInstance.dismiss('cancel');
                        };
                    },
                    size: 'lg',
                    resolve: {
                        ticket_group: function () {
                            return list_ticket_group;
                        },
                        admins : function (){
                            return list_admin;
                        },
                        assginer: function (){
                            return assginer;
                        }
                    }
                });

                modalFilterAssginer.result.then(function(resp) {
                }, function(resp) {
                    if(resp !== 'cancel' && typeof resp == 'object'){
                        $scope.filter_assginer = resp;
                        $scope.change_tab($scope.status,1,$scope.search,$scope.priority, $scope.isVip);
                    }
                })['finally'](function(){

                });
            }


            $scope.openModalListOrder = function (userID) {
                var modalInstance = $modal.open({
                    templateUrl: 'tpl/ticket/modal.listOrder.html',
                    controller: function($scope, $modalInstance, Order, userID) {
                        $scope.listOrder = [];
                        $scope.message = '';
                        Order.Recent(userID)
                            .success(function(response) {
                                if(response.status) {
                                    $scope.listOrder = response.data;
                                } else {
                                    $scope.message = response.message;
                                }
                            });

                        $scope.cancel = function() {
                            $modalInstance.dismiss('cancel');
                        };
                    },
                    size: 'lg',
                    resolve: {
                        userID: function () {
                            return userID;
                        }
                    }
                });
            };


            $scope.AddReminder = function (ticket) {
                var modalInstance = $modal.open({
                    templateUrl: 'tpl/ticket/modal.create_reminder.html',
                    controller: function ($scope, $modalInstance, ticket, toaster) {
                        var current_date = new Date();
                        $scope.frm = {
                            /*time_reminder: new Date(current_date.setHours(current_date.getHours() + 4))*/
                        };

                        $scope.displayDate = function(minutes){
                            var min   = minutes % 60;
                            var hours = 0;
                            
                            if(minutes < 60){
                                return minutes + ' phút nữa';
                            }

                            hours     = (minutes - min) / 60;

                            if(min == 0){
                                return  hours + ' tiếng nữa';
                            }

                            return  hours + ' tiếng '+ min + ' phút nữa';
                        }

                        $scope.close = function (){
                            $modalInstance.close();
                        }

                        $scope.loading = false;
                        $scope.add = function (frm){
                            var saveData = {};
                            if(frm.time_reminder_minutes && frm.time_reminder_minutes != "" && frm.time_reminder_minutes > 0){
                                var time = new Date();
                                time = time.setMinutes(time.getMinutes() + parseInt(frm.time_reminder_minutes));
                                saveData.time_reminder = time / 1000;
                            }else if(frm.time_reminder && frm.time_reminder != ""){
                                saveData.time_reminder = new Date(frm.time_reminder).getTime() / 1000;
                            }

                            saveData.name       = frm.name;
                            saveData.ticket_id  = ticket.id;
                            $scope.loading      = true;
                            $http.post(ApiPath + 'ticket-reminder/add-reminder', saveData).success(function (resp){
                                $scope.loading      = false;
                                if(resp.error){
                                    toaster.pop('warning', 'Thông báo', resp.error_message);
                                    return;
                                }
                                toaster.pop('success', 'Thông báo', "Thành công");
                                ticket.reminder = resp.data;
                                $modalInstance.close(resp.data);
                                
                            })


                        }

                    },
                    size: 'sm',
                    resolve: {
                        ticket: function () {
                            return ticket;
                        }
                    }
                });
            };
        })
    

        $scope.SendRefundConfirm  = function (ticket){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/ticket/modal.refund_confirm.html',
                controller: function($scope, $modalInstance, ticket, $http, list_order) {

                    $scope.ticket         = ticket;
                    $scope.submit_loading = false;

                    $scope.frm            = {
                        list_order: []
                    };

                    
                    ticket.refer.forEach(function (value){
                        if (value.type == 1 && list_order[value.code]) {
                            $scope.frm.list_order.push({
                                'tracking_code' : value.code,
                                'refund_type'   : 1,
                                'courier_id'    : list_order[value.code].courier_id,
                                'from_city_id'    : list_order[value.code].from_city_id,
                                'courier_name'  : list_order[value.code].courier.name
                            });
                        };
                    })

                    $scope.addItemToListOrder = function (){
                        $scope.frm.list_order.push({});
                    }

                    $scope.removeOrderItem = function (index){
                        $scope.frm.list_order.splice(index, 1);
                    }
                   
                    $scope.createRequest = function (frm){

                        $scope.submit_loading = true;
                        $scope.frm.ticket_id  = ticket.id;
                        $scope.frm.seller_id  = ticket.user_id;

                        $http.post(ApiPath + 'refund-confirm/create', frm).success(function (resp){
                            if (!resp.error) {
                                toaster.pop('success', 'Thông báo', 'Gửi yêu cầu bồi hoàn thành công, vui lòng đợi quản lý duyệt.');
                            };
                            

                            $scope.submit_loading = false;
                            $scope.cancel();
                        })
                    }

                    $scope.cancel = function() {
                        $modalInstance.dismiss('cancel');
                    };

                },
                size: 'md',
                resolve: {
                    ticket: function () {
                        return ticket;
                    },
                    list_order: function (){
                        return $scope.list_order;
                    }
                }
            });
        }

        $scope.openExtendTimeModal = function (ticket){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/ticket/modal.extend_time.html',
                controller: function($scope, $modalInstance, ticket, $http) {
                    $scope.ticket = ticket;
                    $scope.submit_loading = false;
                    $scope.range = function(min, max, step) {
                        // parameters validation for method overloading
                        if (max == undefined) {
                            max = min;
                            min = 0;
                        }
                        step = Math.abs(step) || 1;
                        if (min > max) {
                            step = -step;
                        }
                        // building the array
                        var output = [];
                        for (var value=min; value<max; value+=step) {
                            output.push(value);
                        }
                        // returning the generated array
                        return output;
                    };

                    $scope.createRequest = function (time, note){
                        $scope.submit_loading = true;
                        var data       = {};
                        data.ticket_id = $scope.ticket.id;
                        data.time      = time;
                        data.note      = note;
                        $http.post(ApiPath + 'ticket-extend-time/create-request', data).success(function (resp){
                            $scope.submit_loading = false;
                            toaster.pop('success', 'Thông báo', 'Gửi yêu cầu gia hạn thành công, vui lòng đợi quản lý duyệt');
                            $scope.cancel();
                        })
                    }

                    $scope.cancel = function() {
                        $modalInstance.dismiss('cancel');
                    };
                },
                size: 'md',
                resolve: {
                    ticket: function () {
                        return ticket;
                    }
                }
            });
        };
        

}]);