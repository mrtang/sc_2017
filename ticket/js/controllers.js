'use strict';

var timeout;
var time_call;

/* Controllers */

angular.module('app.controllers', ['pascalprecht.translate', 'ngCookies'])
    .controller('AppCtrl', ['$scope', '$translate', '$localStorage', '$window', '$modal', '$timeout', '$interval', '$rootScope', 'loginService', 'Notify', '$http','hotkeys', 'PhpJs',
        function($scope, $translate, $localStorage, $window, $modal, $timeout, $interval,  $rootScope, loginService, Notify, $http, hotkeys, PhpJs) {
            // add 'ie' classes to html
            var isIE = !!navigator.userAgent.match(/MSIE/i);
            isIE && angular.element($window.document.body).addClass('ie');
            isSmartDevice($window) && angular.element($window.document.body).addClass('smart');

            $scope.frm_call = {
              phoneNumbersInput : ""
            };

            $scope.loading = {
              call_history: true
            };
            
            $scope._call_history = [];

            $scope.copyClipbroad = function (value){
              window.prompt("Copy to clipboard: Ctrl+C, Enter", value);
            }


            $scope.getCallHistory = function (phone){
                $scope.loading.call_history = true;
                $http.get(ApiPath + 'call-center/cdr?phone=' + phone + '&item_page=10').success(function (resp){
                  $timeout(function (){
                    $scope.loading.call_history = false;
                    if(!resp.error){
                        $scope._call_history = resp.data;
                    }
                  }, 0);
                })
            };


            $scope.openCallCenterPopup = function (){
              var info  = $scope.islogged();
              if(info){
                $timeout(function (){
                  $scope.toggleCallcenter = !$scope.toggleCallcenter;
                  if($scope.toggleCallcenter){
                    $scope.getCallHistory(info.sip_account);
                  }
                }, 0);
              }
            };





            $scope.islogged = function (){
                return loginService.islogged();
            };


            $rootScope.timeAgo = function (time){
                moment.lang('vi');
                return moment(time).fromNow();
            };





            var loadUserInfo = function (phone){
                $http.get(ApiPath + 'user/user-by-phone?phone=' + phone).success(function (resp){
                    $scope.call_info.user = resp.data;
                });
            };

            $scope.show_info = function (size,user) {
                $modal.open({
                    templateUrl: 'tpl/ticket/modal.caller_info.html',
                    controller: function ($rootScope, $scope, user, Order, $modalInstance){

                        $scope.loading = {
                            newest_ticket: true,
                            call_history: true
                        };
                        //$scope.call_phonenumber = CALLMAN.display_name;
                        $scope.open_create_ticket = function() {
                            $rootScope.$broadcast('open_popup_ticket', [user.email]);
                        };

                        $scope.loadUserInfo = function (email){
                            $scope.loading.user_info = true;
                            $http.get(ApiOms + 'user/statistic/'+ email).success(function (resp){
                                $scope.loading.user_info = false;
                                if(!resp.error){
                                    $scope.user = resp.user;
                                }
                            })
                        };
                        $scope.getCallHistory = function (){
                            
                            $scope.loading.call_history = true;
                            $http.get(ApiPath + 'call-center/cdr?phone=' + user.phone + '&item_page=10').success(function (resp){
                                $scope.loading.call_history = false;
                                if(!resp.error){
                                    $scope.call_history = resp.data;
                                }
                            })
                        };
                        $scope.getNewestTicket = function (user_id){
                            $scope.loading.newest_ticket = true;
                            $http.get(ApiOms + 'user/newest-ticket?seller=' + user_id + '&item_page=10').success(function (resp){
                                $scope.loading.newest_ticket = false;
                                if(!resp.error){
                                    $scope.newest_ticket = resp.data;
                                }
                            })
                        };
                        $scope.loadOrderRecent = function (userID){
                            Order.Recent(userID)
                            .success(function(response) {
                                if(response.status) {
                                    $scope.listOrder = response.data;
                                } else {
                                    $scope.message = response.message;
                                }
                            });
                        };
                        $scope.cancel = function (){
                            $modalInstance.close();
                        }

                        $scope.loadUserInfo(user.email);
                        $scope.getCallHistory();
                        $scope.loadOrderRecent(user.id);
                        $scope.getNewestTicket(user.id);
                    },
                    size:size,
                    resolve: {
                        user: function(){
                            return user;
                        }
                    }
                });
            };


            $scope.modal_add_phone2 = function (size,phone) {
                $modal.open({
                    templateUrl: 'tpl/ticket/modal.add_phone2.html',
                    controller: function ($rootScope, $scope, $http, phone, Order, User, $modalInstance, toaster){
                        
                        $scope.phone    = phone;

                        // get list user
                        $scope.getUser  = function (val, is_code){
                            var isTrackingCode = false;
                            val                = val.toUpperCase();
                            $scope.loadingUses = true;
                            if(val.match(/^SC\d+$/g)){
                                isTrackingCode = true;
                            }


                            return User.load(val).then(function (result) {
                                $scope.loadingUses = false;
                                if(result){

                                    if(is_code && result.data.data.length > 0){
                                        $scope.user_id = result.data.data[0].id;
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

                        $scope.abc = function (item){
                            if(item){
                                $scope.user_id = item.id
                            }
                        }

                        $scope.cancel = function (){
                            $modalInstance.close();
                        }
                        $scope.saveLoading = false;
                        $scope.save = function (){
                            if (!$scope.user_id || !phone) {return false;};
                            $scope.saveLoading = true;
                            $http.post(ApiPath + 'user/update-phone2', {user_id: $scope.user_id, phone: phone}).success(function (resp){
                                $scope.saveLoading = false;
                                if(resp.error){
                                    toaster.pop('warning', 'Thông báo', resp.error_message);
                                }else {
                                    toaster.pop('success', 'Thông báo', resp.error_message);
                                    $modalInstance.close();
                                }
                            });
                        }
                    },
                    size:size,
                    resolve: {
                        phone: function(){
                            return phone;
                        }
                    }
                });
            };

            

            

            if (Notification.permission !== 'denied') {
                Notification.requestPermission(function (permission) {
                });
            }

            function sendNoti  (avt, title, content, callback){
                if (Notification.permission !== 'denied') {
                    Notification.requestPermission(function (permission) {
                      // If the user accepts, let's create a notification
                        if (permission === "granted") {
                            var notification = new window.Notification(title, {icon: avt, "body": content});
                            setTimeout(function (){
                                if(notification){
                                    notification.close();
                                }
                            }, 15000);
                            callback(notification);
                        }
                    });
                }
            }
            
     
            function getIdentifier (identifier){
                if(identifier){
                    return "(" + identifier + ")";
                }
                return "";
            }
            
            window._socketIO.on('incoming:call:3cx', function (data){
                console.log('new call', data)
                if (data.direction !== 'incoming') {
                	return false;
                };
                if(data.dest == $scope.islogged().sip_account){
                    var phone_number = data.source;
                    $http.get(ApiPath + 'user/user-by-phone?phone=' + phone_number).success(function (resp){
                        $timeout(function (){
                            var user = resp.data;

                            if(user){
                                sendNoti("http://www.gravatar.com/avatar/" + PhpJs.md5(user.email) +  "?s=80&r=g", "Có cuộc gọi đến",  getIdentifier(user.identifier) + ' '+ user.fullname +'\n' + user.phone + '\n' + user.email , function (notification){
                                    notification.onclick = function(){
                                        window.focus();
                                        $scope.show_info('lg', user);
                                    };
                                    
                                })
                            }else {
                                sendNoti("icon.jpg", "Có cuộc gọi đến", 'Khách hàng: Ẩn danh (' + phone_number +')', function (notification){
                                    notification.onclick = function(){
                                        window.focus();
                                        $scope.modal_add_phone2('lg', phone_number)
                                    };
                                    
                                })
                                
                            }
                            
                        }, 0)
                        
                    });
                }
            });
    
            window._socketIO.on('ticket:change:priority', function (data){
                var msg = data.user_info.fullname + " đã thay đổi mức độ quan trọng của yêu cầu #" + data.ticket_id + " - " + data.title + " thành " + data.priority_name.name;
                sendNoti("icon.jpg", "Thay đổi độ ưu tiên ticket",  msg , function (notification){
                    notification.onclick = function(){

                        window.open('/#/ticket/request/management/30/' + data.ticket_id);
                        notification.close();
                    };
                })
            })

            window._socketIO.on('ticket:reminder', function (data){

                var modalInstance = $modal.open({
                    templateUrl: 'tpl/ticket/modal.ticket.reminder.html',
                    controller: function ($scope, data){
                        $scope.data = data;
                    },
                    size: 'md',
                    resolve: {
                        data: function() {
                            return data;
                        }
                    }
                });
                modalInstance.result.then(function (){
                    window.title = "Shipchung | Tickets";
                })

                window.title = "(1) Shipchung | Tickets";

                var msg = "Nhắc nhở: " + data.name + ", mã yêu cầu liên quan #" + data.ticket_id;
                sendNoti("reminder_clock.png", "Nhắc nhở",  msg , function (notification){

                    notification.onclick = function(){
                        //window.open('/#/ticket/request/management/30/' + data.ticket_id);
                        window.title = "Shipchung | Tickets";
                        window.focus();
                        notification.close();
                    };
                })
            })


            window._socketIO.on('list:connection', function (connections){
                //console.log('abc', connections);
            })


            var intervalCheckLogin = setInterval(function (){
                if($scope.islogged()){
                    window._socketIO.emit("agent:poll", $scope.islogged().id);
                    /*clearInterval(intervalCheckLogin);*/
                }else {
                }
            }, 3000);

            


            // config
            $scope.app = {
                name: 'Boxme',
                version: '1.2.0',
                // for chart colors
                color: {
                    primary: '#7266ba',
                    info: '#23b7e5',
                    success: '#27c24c',
                    warning: '#fad733',
                    danger: '#f05050',
                    light: '#e8eff0',
                    dark: '#3a3f51',
                    black: '#1c2b36'
                },
                settings: {
                    themeID: 1,
                    navbarHeaderColor: 'bg-info',
                    navbarCollapseColor: 'bg-info dk',
                    asideColor: 'bg-black',
                    headerFixed: false,
                    asideFixed: false,
                    asideFolded: false,
                    asideDock: false,
                    container: false
                }
            }
            $scope.list_case = {};
            $scope.time_start = '7';
            $scope.link_storage = ApiStorage;
            $scope.link_export = ApiPath;
            $scope.order_code = '';
            $scope.list_notify = {};
            $scope.waiting_notify = true;

            $scope.list_time = {
                'overtime': 'quá hạn',
                'over1day': 'sắp hết hạn trong 1 ngày',
                'over2day': 'sắp hết hạn trong 2 ngày',
                'lastday': 'hôm qua',
                'now': 'hôm nay',
                '7': ' 7 ngày qua',
                '14': ' 14 ngày qua',
                '30': ' 1 tháng trước',
                '90': ' 3 tháng trước'
            }

            $scope.link_hvc = function(courier, code, sc_code) {
                var url = '#';
                switch (courier) {
                    case 1:
                        url = 'http://vtp.vn/theo-doi/id/' + code;
                        break;
                    case 2:
                        url = 'http://www.vnpost.vn/TrackandTrace/tabid/130/n/' + code + '/t/0/s/1/Default.aspx';
                        break;
                    case 3:
                        break;
                    case 4:
                        url = 'http://123giao.com/sc/status/' + code;
                        break;
                    case 5:
                        url = 'http://netco.vn/thong-tin-van-chuyen.aspx?bill=' + code;
                        break;
                    case 6:
                        url = 'http://khachhang.giaohangtietkiem.vn/khach-hang/tracking/order/' + sc_code;
                        break;
                    case 7:
                        break;
                    case 8:
                        url = 'http://www.vnpost.vn/TrackandTrace/tabid/130/n/' + code + '/t/0/s/1/Default.aspx';
                        break;
                    case 9:
                        url = 'http://goldtimes.vn/web/goldtimes/tim-kiem?searchtype=2&q=' + code;
                        break;
                    case 11:
                        url = 'http://kerryexpress.com.vn/index.php?mod=tracking&view=tracking&Idmenu=555&getVanDonId='+sc_code+'%20&captcha=2NmW';
                    default:
                        break;
                }
                return url;
            }


            $scope.check_privilege = function(code, action) {
                if ($rootScope == undefined || $rootScope.userInfo == undefined) {
                    return false;
                }

                if ($rootScope.userInfo != undefined && ($rootScope.userInfo.privilege == 2 || ($rootScope.userInfo.group_privilege[code] && $rootScope.userInfo.group_privilege[code][action] == 1))) {
                    return true;
                } else {
                    return false;
                }
            }

            $scope.$on('CaseTicket', function(event, data) {
                $scope.list_case = data;
            });

            $scope.open_create_ticket = function(code) {
                $scope.$broadcast('open_popup_ticket', [code]);
            }

            $scope.search_code = function(code) {
                if (code != undefined && code != '') {
                    var modalInstance = $modal.open({
                        templateUrl: 'ModalSearchCtrl.html',
                        controller: 'ModalSearchCtrl',
                        size: 'lg',
                        resolve: {
                            code: function() {
                                return code;
                            }
                        }
                    });

                    modalInstance.result.then(function(code) {
                        $scope.open_create_ticket(code);
                    });
                }
            };

            function isSmartDevice($window) {
                // Adapted from http://www.detectmobilebrowsers.com
                var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
                // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
                return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
            }

            $scope.logout = function() {
                loginService.logout();
            }

            /*var retime_notify = function(){
                time_call = $timeout(function(){
                    $scope.count_notify();
                    retime_notify();
                },180000);
            }*/

            $scope.$watch('userInfo', function(Value, OldValue) {
                if (Value != undefined && Value.id) {
                    //retime_notify();
                    //$scope.count_notify();
                } else {
                    $timeout.cancel(time_call);
                    $timeout.cancel(timeout);
                }
            });

            $scope.count_notify = function() {
                Notify.count().then(function(result) {
                    if (!result.data.error) {
                        $scope.notify = result.data.data;
                    }
                });
            };


        }
    ])

// bootstrap controller
// signin controller
.controller('CourierNoteCtrl', ['$scope', '$state', 'bootbox', '$modal', function($scope, $state, bootbox , $modal) {

        $scope.CourierCreateNote = function (order_id, courier_id, callback){
            $modal.open({
                templateUrl: 'tpl/courier/partials/modal_create_note.html',
                controller: function ($rootScope, $scope, $modalInstance, order_id, courier_id, $http, toaster){

                    $scope.data = {
                        courier_id  : courier_id,
                        order_id    : order_id
                    };

                    $scope.saveProcess = false;
                    $scope.loadLogs    = true;
                    $scope.logs = [];

                    $scope.showLog = function (order_id){
                        $scope.loadLogs    = true;
                        $http.get(ApiPath + 'ticket-dashbroad/show-note?order_id=' + order_id, function (resp){
                            $scope.loadLogs    = false;
                            $scope.logs = resp.data;
                        })
                    };


                    $scope.save = function (data){
                        $scope.saveProcess = true;
                        $http.post(ApiPath + 'ticket-dashbroad/create-note', data).success( function (resp){
                            console.log('resp', resp);
                            $scope.saveProcess = false;
                            if(resp.error){
                                toaster.pop('warning', 'Thông báo', resp.error_message);
                                return;
                            }
                            toaster.pop('success', 'Thông báo', resp.error_message);
                            $modalInstance.close();
                        })
                    };


                    $scope.showLog(order_id);

                    $scope.close = function (){
                        $modalInstance.close();
                    };
                },
                size: 'md',
                resolve: {
                    order_id: function (){
                        return order_id;
                    },
                    courier_id: function (){
                        return courier_id;
                    }
                }
            });
        }
    }])

    .controller('SigninFormController', ['$scope', '$state', 'loginService', function($scope, $state, loginService) {
        $scope.user = {};

        //loginService.loginfb($scope);
        $scope.authError = null;

        $scope.login_fb = function() {
            $scope.authError = null;
            loginService.loginfb($scope, $state);
        }



        $scope.login = function(data) {
            $scope.authError = null;
            // Try to login
            $scope.onProgress = true;
            loginService.login(data, $scope, $state); //call login service
        };
    }])

// signup controller
.controller('SignupFormController', ['$scope', '$state', 'loginService', function($scope, $state, loginService) {
        $scope.user = {};
        $scope.authError = null;
        $scope.signup = function(data) {
            $scope.authError = null;
            // Try to create
            $scope.onProgress = true;
            loginService.register(data, $scope, $state);
        };
    }])
    .controller('ModalSearchCtrl', ['$scope', '$http', '$modalInstance', '$rootScope', 'toaster', 'Ticket', 'code', function($scope, $http, $modalInstance, $rootScope, toaster, Ticket, code) {
        $scope.order_code = code;
        $scope.waiting = true;
        $scope.list_data = {};
        $scope.User = {};

        $scope.cancel = function() {
            $modalInstance.dismiss('cancel');
        };

        if ($scope.order_code != undefined && $scope.order_code != '') {
            $scope.list_data = {};
            Ticket.SearchRefer($scope.order_code).then(function(result) {
                if (result.data.data) {
                    $scope.list_data = result.data.data;

                    if (result.data.user) {
                        angular.forEach(result.data.user, function(value) {
                            $scope.User[value.id] = {};
                            $scope.User[value.id]['fullname'] = value.fullname;
                            $scope.User[value.id]['phone'] = value.phone;
                            $scope.User[value.id]['email'] = value.email;
                        });
                    }
                }
                $scope.waiting = false;
            });
        }

        $scope.save = function(item) {
            var data = {};
            data['ticket_id'] = item.id;
            data['assign_id'] = $rootScope.userInfo.id;
            data['active'] = 1;

            $http({
                url: ApiPath + 'ticket-assign/create',
                method: "POST",
                data: data,
                dataType: 'json',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }).success(function(result, status, headers, config) {
                if (!result.error) {
                    toaster.pop('success', 'Thông báo', 'Thành công !');
                    item.action = 0;

                    if (item.assign == undefined) {
                        item.assign = [];
                    }

                    item.assign.unshift({
                        assign_id: $rootScope.userInfo.id
                    });
                    if (!$scope.User[$rootScope.userInfo.id]) {
                        $scope.User[$rootScope.userInfo.id] = {
                            'fullname': $rootScope.userInfo.fullname,
                            'phone': $rootScope.userInfo.phone,
                            'email': $rootScope.userInfo.email
                        };
                    }
                } else {
                    toaster.pop('warning', 'Thông báo', 'Cập nhật lỗi !');
                }
            })
        }

        $scope.create = function() {
            $modalInstance.close($scope.order_code);
        }
    }]);
angular.module('app').controller('ModalAddJourneyCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'bootbox',  'items', 'pipe_status', 'step',  'type', 'group',
    function($scope, $modalInstance, $http, toaster, bootbox, items, pipe_status, step,  type, group) {

        $scope.frm_submit       = false;
        $scope.item             = items;
        $scope.pipe_status      = [];
        $scope.type             = type;
        $scope.data             = {'tracking_code' : items.id, 'group' : group, 'pipe_status' : 0, 'note' : '', 'type' : 1};



        $scope.loadListPipe = function (group){
               $http({
                url      : ApiPath + 'pipe-status/pipebygroup?group='+group+'&type='+1,
                method   : "GET",
                dataType : 'json'
            }).success(function (result, status, headers, config) {
                if(result.error){
                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                }
                $scope.pipe_status = result.data;
            })
        };



        if(type == 2){
            $scope.data.tracking_code = items.user_id;
        }

        $scope.step             = step > 0 ? step : 0;

        $scope.close = function(){
            $modalInstance.close($scope.frm_submit);
        };


        $scope.save = function(data, callback){
            $scope.frm_submit = true;

            if(angular.isArray(items)){
                async.eachSeries(items, function (id, callback){
                    console.log(id);
                    $scope.data.tracking_code = id;
                    callHttp($scope.data, callback);
                }, function (){
                    $modalInstance.close({multiple: true, data: data});
                    toaster.pop('success', 'Thông báo', 'Cập nhật tất cả thành công');
                })
            }else {
                callHttp(data, function (){
                    $scope.frm_submit = false;
                    $modalInstance.close({multiple: false, data: data});
                })
            }

        };

        var callHttp = function (data, callback){
            $http({
                url      : ApiPath + 'pipe-journey/create',
                method   : "POST",
                data     : data,
                dataType : 'json'
            }).success(function (result, status, headers, config) {
                callback(null, true);
                if(result.error){

                    toaster.pop('warning', 'Thông báo', 'Tải dữ liệu lỗi !');
                    return;
                }
                toaster.pop('success', 'Thông báo', 'Cập nhật thành công');
                //$modalInstance.close($scope.data);
            })
        };


        $scope.loadListPipe(group);

    }
])




