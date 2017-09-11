'use strict';
var date                    = new Date();
moment.locale('vi');
/* Controllers */

angular.module('app.controllers', ['pascalprecht.translate', 'ngCookies'])
  .controller('AppCtrl',
      [
          '$rootScope', '$scope', '$translate', '$localStorage', '$window', '$timeout', '$stateParams', '$modal', 'loginService',
          'Config', 'Config_Status', 'Base', 'BMBase', 'TaskCategory', 'PhpJs',
    function($rootScope, $scope,   $translate,   $localStorage,   $window , $timeout, $stateParams, $modal, loginService, Config,
             Config_Status, Base, BMBase, TaskCategory, PhpJs) {
      // add 'ie' classes to html
      var isIE = !!navigator.userAgent.match(/MSIE/i);
      isIE && angular.element($window.document.body).addClass('ie');
      isSmartDevice( $window ) && angular.element($window.document.body).addClass('smart');

      // config
      $scope.app = {
        name: 'ShipChung',
        version: '1.2.0',
        // for chart colors
        color: {
          primary: '#7266ba',
          info:    '#23b7e5',
          success: '#27c24c',
          warning: '#fad733',
          danger:  '#f05050',
          light:   '#e8eff0',
          dark:    '#3a3f51',
          black:   '#1c2b36'
        },
        settings: {
          themeID: 1,
          navbarHeaderColor: 'bg-info',
          navbarCollapseColor: 'bg-info dk',
          asideColor: 'bg-black',
          headerFixed: false,
          asideFixed: false,
          asideFolded: true,
          asideDock: false,
          container: false
        }
      }

        $scope.changeLanguage = function(key) {
            $translate.use(key);
            $rootScope.keyLang = key.toString();
             $http.defaults.headers.common['LANGUAGE']  = $rootScope.keyLang ? $rootScope.keyLang : "vi";
        };
        if(!$rootScope.keyLang){
            if ($window.localStorage.NG_TRANSLATE_LANG_KEY){
                $rootScope.keyLang = ""+$window.localStorage.NG_TRANSLATE_LANG_KEY.toString()+"";
            }
        }

      function isSmartDevice( $window )
      {
          // Adapted from http://www.detectmobilebrowsers.com
          var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
          // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
          return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
      }

      $scope.link_seller      = ApiSeller;

        // Check quyền hệ thống
        $scope.check_privilege  = function(code, action){
            if($rootScope == undefined || $rootScope.userInfo == undefined){
                return false;
            }

            if($rootScope.userInfo.privilege == 2){
                return true;
            }

            if(action == undefined || action == ''){
                switch(code) {
                    case 'marketing':
                        return true;
                        break;
                    case 'sale':
                        if($rootScope.userInfo.group == 10){
                            return true;
                        }else{
                            return false;
                        }
                        break;
                    case 'cs':
                        if($rootScope.userInfo.group == 15){
                            return true;
                        }else{
                            return false;
                        }
                        break;
                    default:
                        return false;
                }

            }else{
                if($rootScope.userInfo != undefined && ($rootScope.userInfo.group_privilege[code] && $rootScope.userInfo.group_privilege[code][action] == 1)){
                    return true;
                }else{
                    return false;
                }
            }

        }

        $scope.__get_time  = function(time){
            var str = '';
            if(time > 0){
                var date = Date.parse(new Date)/1000;
                var long = 0;
                if(date > time){
                    long    = (date - 1*time);
                }
                str = PhpJs.ScenarioTime(long);
            }
            return str;
        }

        $scope.list_courier     = {};
        $scope.courier          = {};
        $scope.service          = Config.service;
        $scope.list_country     = {};
        $scope.list_city        = {};
        $scope.list_district    = {};
        $scope.country          = {};
        $scope.district         = {};
        $scope.city             = {};
        $scope.list_vip         = {};
        $scope.color            = Config_Status.order_color;

        // Kho hàng
        $scope.dr_status                        = {};
        $scope.putaway_status                   = {};
        $scope.pickup_status                    = {};
        $scope.package_status                   = {};
        $scope.warehouse_status                 = {};
        $scope.shipment_status                  = {};
        $scope.warehouse_item_status            = {};
        $scope.warehouse_warehouse              = {};
        $scope.volume                           = {};
        $scope.warehouse_group_status           = {};
        $scope.warehouse_group_order_status     = {};
        $scope.warehouse_status_group           = {};
        $scope.list_pipe_status                 = {};
        $scope.warehousepipe_status             = {};
        $scope.pipe_priority                    = {};
        $scope.pipe_limit                       = 0;
        $scope.pipe_status                      = {};
        $scope.warehouse                        = {};

        // Delivery
        $scope.link_export      = ApiPath;
        $scope.link_oms         = ApiOms;
        $scope.link_storage     = ApiStorage;
        $scope.link_ticket      = 'http://ticket.shipchung.vn/#/ticket/request/management/90/';

        $scope.list_status                  = {};
        $scope.group_status                 = {};
        $scope.status_group                 = {};
        $scope.group_order_status           = {};
        $scope.status_accept                = {};
        $scope.list_tag                     = {};
        $scope.sc_loyalty_level             = {};
        $scope.list_router                  = Config.router;
        $scope.router_privilege             = Config.router_privilege;
        $scope.pipe_status                  = {};


        $scope.list_country__         = {
            101 :   'Indonesia',
            133 :   'Malaysia',
            174 :   'Philippines',
            237 :   'Vietnam'
        };

        $scope.type_sku             = {
            1   : 'S1',
            2   : 'S2',
            3   :'S3',
            4   :'S4',
            5   :'S5',
            6   :'S6'
        };

        $scope.folds = [
            {name: 'Tất cả', filter:'ALL'},
            {name: 'Chưa bắt đầu', filter:'NOT_STARTED'},
            {name: 'Đang làm', filter:'IN_PROCESS'},
            {name: 'Đã hoàn thành', filter:'SUCCESS'},
            {name: 'Tạm dừng', filter:'PAUSED'},
        ];

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.keys = function(obj){
            return obj? Object.keys(obj) : [];
        }

        $scope.task_state_info = {
            'NOT_STARTED': {
                icon: 'fa-stop',
                name: 'Chưa bắt đầu',
            },
            'IN_PROCESS': {
                icon: 'fa-stop',
                name: 'Đang làm',
            },
            'SUCCESS': {
                icon: 'fa-stop',
                name: 'Đã hoàn thành',
            },
            'PAUSED': {
                icon: 'fa-stop',
                name: 'Tạm dừng',
            }
        }

        $rootScope.link_hvc = function (courier,code,sc_code){
            var url = '#';
            switch(courier) {
                case 1:
                    url = 'http://vtp.vn/theo-doi/id/'+code;
                    break;
                case 2:
                    url = 'http://www.vnpost.vn/TrackandTrace/tabid/130/n/'+code+'/t/0/s/1/Default.aspx';
                    break;
                case 3:
                    break;
                case 4:
                    url = 'http://123giao.com/sc/status/'+code;
                    break;
                case 5:
                    url = 'http://netco.vn/thong-tin-van-chuyen.aspx?bill='+code;
                    break;
                case 6:
                    url = 'http://khachhang.giaohangtietkiem.vn/khach-hang/tracking/order/'+sc_code;
                    break;
                case 7:
                    break;
                case 8:
                    url = 'http://www.vnpost.vn/TrackandTrace/tabid/130/n/'+code+'/t/0/s/1/Default.aspx';
                    break;
                case 9:
                    url = 'http://goldtimes.vn/web/goldtimes/tim-kiem?searchtype=2&q='+code;
                    break;
                case 11:
                    url = 'http://kerryexpress.com.vn/index.php?mod=tracking&view=tracking&Idmenu=555&getVanDonId='+sc_code+'%20&captcha=2NmW';
                    break;
                case 14:
                    url = 'https://www.ninjavan.co/vn-vn?tracking_id='+code;
                    break;
                default:
                    break;
            }
            return url;
        }


        $scope.newCustomerFilter = [
            {
                label: "Hôm nay",
                value: moment().startOf('day').unix()
            },
            {
                label: "3 ngày trước",
                value: moment().subtract('days', 3).startOf('day').unix()
            },
            {
                label: "7 ngày trước",
                value: moment().subtract('days', 7).startOf('day').unix()
            },
            {
                label: "14 ngày trước",
                value: moment().subtract('days', 14).startOf('day').unix()
            },
            {
                label: "30 ngày trước",
                value: moment().subtract('days', 30).startOf('day').unix()
            }
        ];

        if($rootScope.userInfo != undefined && TaskCategory.get()){
            TaskCategory.load()
        }

        $scope.setCountry = function(CountryKey) {
            if($rootScope.userInfo != undefined){
                $rootScope.userInfo.country_id   = CountryKey;

                $timeout(function(){
                    $window.location.reload();
                }, 3000);
            }
        };

        $scope.get_time_stock = function(item){
            var long = 0;
            if(item.update_stocked != undefined && item.update_stocked != ''){
                var stocked = (new Date(item.update_stocked.replace(/-/g,"/")).getTime())/1000;
                var packed;
                if(item.update_packed > item.update_stocked){
                    packed = (new Date(item.update_packed.replace(/-/g,"/")).getTime())/1000;
                }else{
                    packed = Date.parse(new Date)/1000;
                }

                long  = (packed - stocked)/3600;
            }

            return long;
        }

        $scope.count_shipment   = function(shipment){
            var quantity = 0;
            angular.forEach(shipment, function(value) {
                quantity    += 1*value.quantity;
            });
            return quantity;
        }

        $scope.__get_list_pipe_status   = function(group, type){
            if($localStorage['list_pipe_status_'+group+'_'+type] == undefined){
                Base.PipeStatus(group, type).then(function (result) {
                    if(!result.data.error){
                        $scope.list_pipe_status      = result.data.data;
                        angular.forEach(result.data.data, function(value) {
                            if(value.priority > $scope.pipe_limit){
                                $scope.pipe_limit   = +value.priority;
                            }
                            $scope.pipe_status[value.status]    = value.name;
                            $scope.pipe_priority[value.status]  = value.priority;
                        });

                        $localStorage['list_pipe_status_'+group+'_'+type]           = $scope.list_pipe_status;
                        $localStorage['pipe_status_'+group+'_'+type]    = $scope.pipe_status;
                        $localStorage['pipe_priority_'+group+'_'+type]  = $scope.pipe_priority;
                        $localStorage['pipe_limit_'+group+'_'+type]           = $scope.pipe_limit;
                    }else{
                        $scope.list_pipe_status     = {};
                        $scope.pipe_status          = {};
                        $scope.pipe_priority        = {};
                        $scope.pipe_limit           = 0;
                    }
                }).finally(function() {

                });
            }else{
                $scope.list_pipe_status     = $localStorage['list_pipe_status_'+group+'_'+type];
                $scope.pipe_status          = $localStorage['pipe_status_'+group+'_'+type];
                $scope.pipe_priority        = $localStorage['pipe_priority_'+group+'_'+type];
                $scope.pipe_limit           = $localStorage['pipe_limit_'+group+'_'+type];
            }
        }

        // Cập nhật hành trình
        $scope.change_status   = function(item){
            var modalInstance = $modal.open({
                templateUrl: 'ModalChangeStatus.html',
                controller: 'ModalChangeStatusCtrl',
                resolve: {
                    tracking_code: function () {
                        return item.tracking_code;
                    },
                    group : function(){
                        return $scope.status_group[item.status];
                    },
                    status_accept   : function(){
                        return $scope.status_accept;
                    },
                    list_status     : function(){
                        return $scope.list_status;
                    },
                    group_order_status : function(){
                        return $scope.group_order_status;
                    },
                    items  : function(){
                        return item;
                    },
                    list_courier    : function(){
                        return $scope.list_courier;
                    }
                }
            });

            modalInstance.result.then(function (status) {
                if(status > 0){
                    item.status = status;
                }
            });
        }

        // Cập nhật hãng vận chuyển
        $scope.change_courier   = function(item){
            var modalInstance = $modal.open({
                templateUrl: 'ModalChangeCourier.html',
                controller: 'ModalChangeCourierCtrl',
                resolve: {
                    items: function () {
                        return item;
                    }
                }
            });

            modalInstance.result.then(function (courier_id) {
                if(courier_id > 0){
                    item.courier_id = courier_id;
                }
            });
        }

        // Add tag
        $scope.change_tag  = function(item, tagid, action){
            var arr_tag     = item['list_tag'];
            var id          = arr_tag.indexOf(tagid);
            var listtag     = '';


            if(action == 'add'){
                if(id !== -1){

                }else{
                    arr_tag.push(tagid);
                }
            }else{
                if(arr_tag.length > 0){
                    if(id !== -1){
                        arr_tag.splice(id, 1);
                    }
                }
            }

            if(arr_tag.length > 0){
                listtag = arr_tag.toString();
            }

            var data    = {
                order_id    : item.id,
                tag         : listtag
            };

            Order.ChangeTag(data).then(function (result) {
                if(!result.data.error){
                    item.list_tag   = arr_tag;
                }
            });
        };

        $scope.open_post_office = function (){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/modal_post_office.html',
                size: 'lg',
                controller: function ($scope, $http, $modalInstance){
                    var map, input, autocomplete, markers = [];
                    $scope.list_postoffice = [];
                    $scope.loading = false;

                    $scope.close = function(){
                        $modalInstance.close($scope.frm_submit);
                    }

                    var icons = {
                        '1': '/img/marker/location-viettel1.png',
                        '2': '/img/marker/location-vietnampost.png',
                        '11': '/img/marker/location-kerry1.png',
                        '8': '/img/marker/location-ems-1.png',
                    };

                    var infowindow = new google.maps.InfoWindow();

                    $scope.goCenter = function (value){
                        map.setCenter(new google.maps.LatLng(value.lat, value.lng));
                    };

                    $scope.getNearPostoffice = function (lat, lng){
                        $scope.list_postoffice = [];
                        $scope.loading         = true;
                        $http.get(ApiPath + 'post-office/find-around', {params: {
                            lat     : lat,
                            lng     : lng,
                            radius  : 5
                        }}).success(function (resp){
                            $scope.loading         = false;
                            $scope.list_postoffice = resp.data;
                            $scope.clearMarker();
                            angular.forEach($scope.list_postoffice, function (value){
                                var _marker = new google.maps.Marker({
                                    map: map
                                });

                                _marker.setIcon(({
                                    url: icons[value.courier_id] ? icons[value.courier_id] : '/img/marker/location-ems-1.png',
                                }));
                                _marker.setPosition(new google.maps.LatLng(value.lat, value.lng));
                                _marker.setVisible(true);

                                google.maps.event.addListener(_marker,'click', (function(marker,value,infowindow){
                                    return function() {
                                        map.setCenter(new google.maps.LatLng(value.lat, value.lng));
                                        infowindow.setContent("<h5>" + value.name + "</h5><p>SĐT: " + value.phone + "</p>");
                                        infowindow.open(map, marker);
                                    };
                                })(_marker, value, infowindow));


                                markers.push(_marker);
                            })
                        })
                    }

                    $scope.clearMarker = function (){
                        for (var i = markers.length - 1; i >= 0; i--) {
                            if(markers[i]){
                                markers[i].setMap(null);
                            }

                        };
                        markers = [];
                    }
                    function initMap() {
                        map = new google.maps.Map(document.getElementById('map'), {
                            center  : {lat: 21.0031180, lng: 105.8201410},
                            zoom    : 13
                        });
                        input = (document.getElementById('address-input'));

                        var marker = new google.maps.Marker({
                            map: map
                        });

                        autocomplete = new google.maps.places.Autocomplete(input);

                        autocomplete.bindTo('bounds', map);
                        autocomplete.addListener('place_changed', function() {

                            marker.setVisible(false);

                            var place = autocomplete.getPlace();
                            if(place.geometry){

                                map.panTo(place.geometry.location);
                                map.setCenter(place.geometry.location);
                                $scope.getNearPostoffice(place.geometry.location.lat(), place.geometry.location.lng());
                                marker.setIcon(({
                                    url: '/img/marker_.png'

                                }));

                                marker.setPosition(place.geometry.location);
                                marker.setVisible(true);

                            }

                        });


                    }
                    setTimeout(function (){
                        initMap();
                    }, 200)


                },
                resolve: {
                    items: function () {
                        return {};
                    }
                }
            });
        }

        $rootScope.openAddTask = function (refer, refer_item){
            Tasks.openModal(refer, refer_item).result.then(function (abc){
                console.log('abc', abc);
            })
        }

        $scope.caculater_totalfee = function(fee, fee_fulfillment, status){
            var total_fee = 1*fee.sc_pvc + 1*fee.sc_pvk - 1*fee.sc_discount_pvc + fee.sc_remote + fee.sc_clearance;

            if(fee_fulfillment != undefined){
                total_fee   += 1*fee_fulfillment.sc_plk + 1*fee_fulfillment.sc_pdg + 1*fee_fulfillment.sc_pxl -
                    1*fee_fulfillment.sc_discount_plk - 1*fee_fulfillment.sc_discount_pdg - 1*fee_fulfillment.sc_discount_pxl;
            }

            if(status == 66){
                return total_fee + 1*fee.sc_pch ;
            }else{
                return total_fee + 1*fee.sc_cod - 1*fee.sc_discount_cod + 1*fee.sc_pbh;
            }
        }

        //get refer ticket
        $scope.get_ticket   = function(item){
            if(item.refer_ticket == undefined || item.refer_ticket.length == 0){
                item.waiting_ticket = true;
                var refer = [];
                Order.GetRefer(item.tracking_code).then(function (result) {
                    if(!result.data.error){
                        item.refer_ticket   = result.data.data;
                    }
                    item.waiting_ticket = false;
                });
            }else{
                return;
            }
        }

        $scope.get_postman   = function(item){
            if((item.postman == undefined || item.postman.length == 0 ) && item.postman_id > 0){
                item.waiting_postman = true;

                Order.GetPostman(item.postman_id).then(function (result) {
                    if(!result.data.error){
                        item.postman   = result.data.data;
                    }
                    item.waiting_postman = false;
                });
            }else{
                return;
            }
        }

        //get list tag
        $scope.__get_tag    = function(){
            if($localStorage['sc_list_tag'] == undefined){
                Base.Tag().then(function (result) {
                    if(!result.data.error){
                        $scope.list_tag                 = result.data.data;
                        $localStorage['sc_list_tag']    = result.data.data;
                    }
                }).finally(function() {

                });
            }else{
                $scope.list_tag = $localStorage['sc_list_tag'];
            }
        }

        $scope.__get_loyalty_level  = function(){
            if($localStorage['sc_loyalty_level'] != undefined){
                $scope.sc_loyalty_level    = $localStorage['sc_loyalty_level'];
                $scope.__get_tag();

            }else{
                Base.loyalty_level().then(function (result) {
                    if(!result.data.error){
                        $localStorage['sc_loyalty_level']   = result.data.data;
                        $scope.sc_loyalty_level                = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_tag();
                });
            }
        }



        $scope.__get_group_status   = function(){
            if($localStorage['group_status'] != undefined) {
                $scope.group_status          = $localStorage['group_status'];
                $scope.group_order_status    = $localStorage['group_order_status'];
                $scope.status_group          = $localStorage['status_group'];
                $scope.__get_loyalty_level();
            }else{
                Base.GroupStatus().then(function (result) {
                    if(!result.data.error){
                        $scope.group_order_status   = {};
                        angular.forEach(result.data.list_group, function(value, key) {
                            $scope.group_status[value.id]   = value.name;
                            if(value.group_order_status){
                                angular.forEach(value.group_order_status, function(v) {
                                    $scope.status_group[+v.order_status_code]    = v.group_status;

                                    if($scope.group_order_status[+v.group_status] == undefined){
                                        $scope.group_order_status[+v.group_status]  = [];
                                    }
                                    $scope.group_order_status[+v.group_status].push(+v.order_status_code);
                                });
                            }
                        });
                    }
                }).finally(function() {
                    $scope.__get_loyalty_level();
                });
            }
        }

        $scope.__get_warehouse_group_status   = function(){
            if($localStorage['warehouse_group_status'] != undefined) {
                $scope.warehouse_group_status          = $localStorage['warehouse_group_status'];
                $scope.warehouse_group_order_status    = $localStorage['warehouse_group_order_status'];
                $scope.warehouse_status_group          = $localStorage['warehouse_status_group'];
                $scope.__get_group_status();
            }else{
                BMBase.GroupStatus().then(function (result) {
                    if(!result.data.error){
                        $scope.warehouse_group_order_status   = {};
                        angular.forEach(result.data.list_group, function(value) {
                            $scope.warehouse_group_status[value.id]   = value.name;
                            if(value.group_order_status){
                                angular.forEach(value.group_order_status, function(v) {
                                    $scope.warehouse_status_group[+v.order_status_code]    = v.group_status;

                                    if($scope.warehouse_group_order_status[+v.group_status] == undefined){
                                        $scope.warehouse_group_order_status[+v.group_status]  = [];
                                    }
                                    $scope.warehouse_group_order_status[+v.group_status].push(+v.order_status_code);
                                });
                            }
                        });
                        $localStorage['warehouse_group_status']            = $scope.warehouse_group_status;
                        $localStorage['warehouse_group_order_status']      = $scope.warehouse_group_order_status;
                        $localStorage['warehouse_status_group']            = $scope.warehouse_status_group;
                    }
                }).finally(function() {
                    $scope.__get_group_status();
                });
            }
        }

        $scope.__get_status  = function(){
            if($localStorage['status'] != undefined){
                $scope.list_status    = $localStorage['status'];
                $scope.__get_warehouse_group_status();
            }else{
                Base.Status().then(function (result) {
                    if(!result.data.error){
                        $scope.list_status               = result.data.data;
                        $localStorage['status']         = $scope.list_status;
                    }
                }).finally(function() {
                    $scope.__get_warehouse_group_status();
                });
            }
        }

        $scope.__get_volume = function(){
            if($localStorage['warehouse_volume'] == undefined){
                BMBase.Volume().then(function (result) {
                    if(!result.data.error){
                        var data = [];
                        angular.forEach(result.data.data, function(value) {
                            data.push({code : value.volume_limit, volume_limit : value.volume_limit});
                        });
                        $localStorage['warehouse_volume']   = data;
                        $scope.volume                = data;
                    }
                }).finally(function() {
                    $scope.__get_status();
                });
            }else{
                $scope.volume = $localStorage['warehouse_volume'];
                $scope.__get_status();
            }
        }

        $scope.__get_warehouse  = function(){
            if($localStorage['warehouse_warehouse'] == undefined){
                BMBase.WareHouse().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_warehouse']    = result.data.data;
                        $scope.warehouse_warehouse              = result.data.data;
                        $scope.warehouse                        = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_volume();
                });
            }else{
                $scope.warehouse_warehouse  = $localStorage['warehouse_warehouse'];
                $scope.warehouse            = $localStorage['warehouse_warehouse'];
                $scope.__get_volume();
            }
        }

        $scope.__get_item_status    = function(){
            if($localStorage['warehouse_item_status'] != undefined){
                $scope.warehouse_item_status = $localStorage['warehouse_item_status'];
                $scope.__get_warehouse();
            }else{
                BMBase.ItemStatus().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_item_status']     = result.data.data;
                        $scope.warehouse_item_status               = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_warehouse();
                });
            }
        }

        $scope.__get_shipment_status = function(){
            if($localStorage['warehouse_shipment_status'] != undefined){
                $scope.shipment_status    = $localStorage['warehouse_shipment_status'];
                $scope.__get_item_status();
            }else{
                BMBase.ShipmentStatus().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_shipment_status']  = result.data.data;
                        $scope.shipment_status                      = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_item_status();
                });
            }
        }

        $scope.__get_warehouse_status = function(){
            if($localStorage['warehouse_status'] != undefined){
                $scope.warehouse_status    = $localStorage['warehouse_status'];
                $scope.__get_shipment_status();
            }else{
                BMBase.WareHouseStatus().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_status']       = result.data.data;
                        $scope.warehouse_status                 = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_shipment_status();
                });
            }
        }

        $scope.__get_package_status = function(){
            if($localStorage['warehouse_package_status'] != undefined){
                $scope.package_status    = $localStorage['warehouse_package_status'];
                $scope.__get_warehouse_status();
            }else{
                BMBase.PackageStatus().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_package_status']   = result.data.data;
                        $scope.package_status                = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_warehouse_status();
                });
            }
        }

        $scope.__get_pickup_status = function(){
            if($localStorage['warehouse_pickup_status'] != undefined){
                $scope.pickup_status    = $localStorage['warehouse_pickup_status'];
                $scope.__get_package_status();
            }else{
                BMBase.PickupItemStatus().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_pickup_status']   = result.data.data;
                        $scope.pickup_status                        = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_package_status();
                });
            }
        }

        $scope.__get_putaway_status = function(){
            if($localStorage['warehouse_putaway_status'] != undefined){
                $scope.putaway_status    = $localStorage['warehouse_putaway_status'];
                $scope.__get_pickup_status();
            }else{
                BMBase.PutAwayStatus().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_putaway_status']   = result.data.data;
                        $scope.putaway_status                = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_pickup_status();
                });
            }
        }

        $scope.__get_bm_drstatus  = function(){
            if($localStorage['warehouse_drstatus'] != undefined){
                $scope.dr_status    = $localStorage['warehouse_drstatus'];
                $scope.__get_putaway_status();
            }else{
                BMBase.DrStatus().then(function (result) {
                    if(!result.data.error){
                        $localStorage['warehouse_drstatus']   = result.data.data;
                        $scope.dr_status                = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_putaway_status();
                });
            }
        }

        $scope.__get_user_vip   = function(){
            if($localStorage['list_vip'] == undefined){
                Base.UserVip().then(function (result) {
                    if(!result.data.error){
                        $scope.list_vip                 = result.data.data;
                        $localStorage['list_vip']       = result.data.data;
                    }
                }).finally(function() {
                    $scope.__get_bm_drstatus();
                });
            }else{
                $scope.list_vip = $localStorage['list_vip'];
                $scope.__get_bm_drstatus();
            }
        }

        $scope.__get_district   = function(){
            if($localStorage['district'] != undefined){
                $scope.district         = $localStorage['district'];
                $scope.list_district    = $localStorage['list_district'];
                $scope.__get_user_vip();
            }else{
                Base.AllDistrict().then(function (result) {
                    if(!result.data.error){
                        angular.forEach(result.data.data, function(value, key) {
                            if($scope.list_district[value.city_id] == undefined){
                                $scope.list_district[value.city_id]  = {};
                            }
                            $scope.district[value.id]   = value;
                            $scope.list_district[value.city_id][value.id]  = value;
                        });

                        $localStorage['district']           = $scope.district;
                        $localStorage['list_district']      = $scope.list_district;
                    }
                }).finally(function() {
                    $scope.__get_user_vip();
                });
            }

        }

        $scope.__get_city   = function(){
            if($localStorage['list_city'] != undefined){
                $scope.list_city = $localStorage['list_city'];
                $scope.city      = $localStorage['city'];
                $scope.__get_district();
            }else{

                Base.City().then(function (result) {
                    if(!result.data.error){
                        $localStorage['list_city']      = result.data.data;
                        $scope.list_city                = result.data.data;
                        angular.forEach(result.data.data, function(value, key) {
                            if([18,19,6,1,3,14,12,7,10,5,4,17,16,15,11,2,23,22,25,24,8,28,20,26,31,30,27,32,29,35,34,37,36,33].indexOf(value.id) != -1){
                                value.city_name += '(MB)';
                            }else{
                                value.city_name += '(MN)';
                            }
                            $scope.city[value.id]       = value.city_name;
                        });
                        $localStorage['city']           = $scope.city;
                    }
                }).finally(function() {
                    $scope.__get_district();
                });
            }

        }

        $scope.__get_country   = function(){
            if($localStorage['list_country'] != undefined){
                $scope.list_country = $localStorage['list_country'];
                $scope.country      = $localStorage['country'];
                $scope.__get_city();
            }else{
                Base.Country().then(function (result) {
                    if(!result.data.error){
                        $localStorage['list_country']      = result.data.data;
                        $scope.list_country                = result.data.data;
                        angular.forEach(result.data.data, function(value, key) {
                            $scope.country[value.id]       = value.country_name;
                        });
                        $localStorage['country']           = $scope.country;
                    }
                }).finally(function() {
                    $scope.__get_city();
                });
            }
        }

        $scope.__get_courier = function(){
            if($localStorage['list_courier'] != undefined){
                $scope.list_courier = $localStorage['list_courier'];
                $scope.courier      = $localStorage['courier'];
                $scope.__get_country();
            }else{
                Base.Courier().then(function (result) {
                    if(!result.data.error){
                        $localStorage['list_courier']   = result.data.data;
                        $scope.list_courier             = result.data.data;
                        angular.forEach(result.data.data, function(value, key) {
                            $scope.courier[value.id]    = value.name;
                        });
                        $localStorage['courier']    = $scope.courier;
                    }
                }).finally(function() {
                    $scope.__get_country();
                });
            }
        }

       $scope.__get_courier();

      $scope.logout=function(){
		loginService.logout();
      }

        // add journey process
        $scope.action   = function(item, list_pipe_status, step, type, group){
            var modalInstance = $modal.open({
                templateUrl: 'ModalAdd.html',
                controller: 'ModalAddCtrl',
                resolve: {
                    items: function () {
                        return item;
                    },
                    pipe_status : function(){
                        return list_pipe_status;
                    },
                    step : function(){
                        return step;
                    },
                    type : function(){
                        return type;
                    },
                    group : function(){
                        return group;
                    }
                }
            });

            modalInstance.result.then(function (data) {
                if(data) {
                    if(type == 2){
                        item.pipe_status = 1*group;
                    }else{
                        item.pipe_status = data.pipe_status;
                    }
                    
                    item.pipe_journey.push({
                        user_id         : $rootScope.userInfo.id,
                        pipe_status     : 1*data.pipe_status,
                        group_process   : 1*group,
                        note            : data.note,
                        time_create     : date.getTime()/1000
                    });
                }
            });
        }


  }])
  // signin controller
.controller('SigninFormController', ['$scope', '$state', 'loginService', function($scope, $state, loginService) {
    $scope.user     = {};

    $scope.login_fb = function(){
        $scope.authError = null;
        loginService.loginfb($scope,$state);
    }

    $scope.authError = null;
    $scope.login = function(data) {
      $scope.authError = null;
      // Try to login
      $scope.onProgress = true;
      loginService.login(data,$scope,$state); //call login service
    };
}])
.controller('DashBoardCtrl', ['$scope', '$rootScope', 'PhpJs', 'Order', 'Base', 'Config_Status',
                      function($scope, $rootScope, PhpJs, Order, Base, Config_Status) {
    $scope.list_status  = {};
    $scope.group_status = {};
    $scope.status       = '';
    $scope.list_color   = Config_Status.order_color;
    $scope.color        = Config_Status.list_color;
    $scope.statistic    = {};
    $scope.order_now    = [];
    $scope.label_color  = [];
    $scope.obj          = 'order';

    $scope.list         = {};
    $scope.user         = {};
    $scope.data         = {};
    $scope.chart_sale   = [];

    var group        = Config_Status.group;
    $scope.waiting   = true;

    $scope.Sale   = function(){
      Order.Sale().then(function (result) {
          if(!result.data.error){
                $scope.list = result.data.list;
                $scope.user = result.data.user;
                $scope.data = result.data.data;

              var sum;
              var first;
              var pre;

              angular.forEach(result.data.user, function(value, key) {
                if($scope.list['first'][value.id] != undefined){
                    first   = 1*$scope.list['first'][value.id];
                }else{
                    first   = 0;
                }

                if($scope.list['pre'][value.id] != undefined){
                    pre   = 1*$scope.list['pre'][value.id];
                }else{
                    pre   = 0;
                }

                  sum = (first + pre)/1000;
                  $scope.label_color.push($scope.color[27]);
                  $scope.chart_sale.push({'label' : value.fullname, 'data' : [[10, sum]]});
              });
          }
          $scope.waiting   = false;
      });
    }

    if($rootScope.userInfo != undefined && $rootScope.userInfo.group != undefined && $rootScope.userInfo.privilege != undefined){
        if([3,10].indexOf($rootScope.userInfo.group) != -1){ // Sale
            $scope.obj  = 'sale';
            $scope.Sale();
        }else{ // # sale
            Base.GroupStatus().then(function (result) {
                if(!result.data.error){
                    angular.forEach(result.data.list_group, function(value, key) {
                        if($rootScope.userInfo.privilege != 2){
                            //Quyền lấy hàng
                            if($scope.check_privilege('PRIVILEGE_PICKUP', 'view')){
                                if(group['PRIVILEGE_PICKUP'].indexOf(value.id) != -1){
                                    $scope.group_status[value.id]   = value.name;
                                    $scope.statistic[+value.id]     = 0;
                                    $scope.order_now.push({'label' : value.name, 'data' : [[10,0]]});
                                    $scope.label_color.push($scope.color[value.id]);
                                    if(value.group_order_status){
                                        angular.forEach(value.group_order_status, function(v) {
                                            $scope.list_status[+v.order_status_code]    = v.group_status;
                                            $scope.status                              += v.order_status_code+',';
                                        });
                                    }
                                }
                            }

                            //Quyền giao hàng
                            if($scope.check_privilege('PRIVILEGE_DELIVERY', 'view')){
                                if(group['PRIVILEGE_DELIVERY'].indexOf(value.id) != -1){
                                    $scope.group_status[value.id]   = value.name;
                                    $scope.statistic[+value.id]     = 0;
                                    $scope.order_now.push({'label' : value.name, 'data' : [[10,0]]});
                                    $scope.label_color.push($scope.color[value.id]);
                                    if(value.group_order_status){
                                        angular.forEach(value.group_order_status, function(v) {
                                            $scope.list_status[+v.order_status_code]    = v.group_status;
                                            $scope.status                              += v.order_status_code+',';
                                        });
                                    }
                                }
                            }

                            //Quyền chuyển hoàn
                            if($scope.check_privilege('PRIVILEGE_RETURN', 'view')){
                                if(group['PRIVILEGE_RETURN'].indexOf(value.id) != -1){
                                    $scope.group_status[value.id]   = value.name;
                                    $scope.statistic[+value.id]     = 0;
                                    $scope.order_now.push({'label' : value.name, 'data' : [[10,0]]});
                                    $scope.label_color.push($scope.color[value.id]);
                                    if(value.group_order_status){
                                        angular.forEach(value.group_order_status, function(v) {
                                            $scope.list_status[+v.order_status_code]    = v.group_status;
                                            $scope.status                              += v.order_status_code+',';
                                        });
                                    }
                                }
                            }
                        }else{
                            $scope.group_status[value.id]   = value.name;
                            $scope.statistic[+value.id]     = 0;
                            $scope.order_now.push({'label' : value.name, 'data' : [[10,0]]})
                            $scope.label_color.push($scope.color[value.id]);
                            if(value.group_order_status){
                                angular.forEach(value.group_order_status, function(v) {
                                    $scope.list_status[+v.order_status_code]    = v.group_status;
                                });
                            }
                        }

                    });

                    if($rootScope.userInfo.privilege == 2 || ($scope.status != undefined && $scope.status.length > 0)){
                        $scope.Statistic($scope.status);
                    }else{
                        $scope.waiting   = false;
                    }
                }
            });
        }
    }

    $scope.Statistic    = function(list_status){
        var list_status     = PhpJs.rtrim(list_status,',');
        var data            = {};
        var data_now        = [];
       $scope.order_now     = [];

        Order.Statistic(list_status).then(function (result) {
            if(!result.data.error){
                data    = result.data.data;
                angular.forEach(data.group, function(value, key) {
                    if($scope.list_status[key] && $scope.statistic[$scope.list_status[key]] != undefined){
                        $scope.statistic[$scope.list_status[key]]   += 1*value;
                    }
                });
                angular.forEach(data.day, function(value, key) {
                    if($scope.list_status[key]){
                        if(data_now[$scope.list_status[key]] == undefined){
                            data_now[$scope.list_status[key]]   = 0;
                        }
                        data_now[$scope.list_status[key]] += 1*value;
                    }
                });
                angular.forEach($scope.group_status, function(value, key) {
                    if(data_now[key]){
                        $scope.order_now.push({'label' : value, 'data' : [[10,data_now[key]]]});
                    }else{
                        $scope.order_now.push({'label' : value, 'data' : [[10, 0]]});
                    }
                });
        }
            $scope.waiting   = false;
        });
    }

}])
.controller('ModalAddCtrl', ['$scope', '$modalInstance', 'items', 'pipe_status', 'step', 'Pipe', 'type', 'group',
    function($scope, $modalInstance, items, pipe_status, step, Pipe, type, group) {
        $scope.frm_submit       = false;
        $scope.item             = items;
        $scope.pipe_status      = pipe_status;
        $scope.type             = type;
        $scope.data             = {'tracking_code' : items.id, 'group' : group, 'pipe_status' : 0, 'note' : '', 'type' : type};
        
        if(type == 2){
          $scope.data.tracking_code = items.user_id;
        }

        $scope.step             = step > 0 ? step : 0;

        $scope.close = function(){
            $modalInstance.close($scope.frm_submit);
        }

        $scope.save = function(data){
            $scope.frm_submit = true;
            Pipe.Create(data).then(function (result) {
                if(result.data.error){
                    $scope.frm_submit   = false;
                }else{
                    $modalInstance.close($scope.data);
                }
            });
        }

    }
])

.controller('ModalAddTaskCtrl', ['$scope', '$modalInstance', '$http', 'toaster', 'Tasks', 'refer', 'Base', '$q', 'refer_item',
    function($scope, $modalInstance, $http, toaster, Tasks, Refer, Base, $q, refer_item) {
        var current_date = new Date();
        $scope.time         = {
            due_date        : new Date(current_date.setHours(current_date.getHours() + 4))
        };
        $scope.item         = {
            state: "IN_PROCESS"
        };
        $scope.refers        = Refer || [];


        $scope.assigner      =  [];
        $scope._SuggestModel = "";

        $scope.currentType = 0;

        $scope.list_state = [
            {
                "key" : "NOT_STARTED",
                "name": "Chưa bắt đầu",
                "icon": "fa-stop"
            },
            {
                "key" : "IN_PROCESS",
                "name": "Đang làm",
                "icon": "fa-play"
            },
            {
                "key" : "SUCCESS",
                "name": "Hoàn thành",
                "icon": "fa-check"
            },
            {
                "key" : "PAUSED",
                "name": "Dừng lại",
                "icon": "fa-pause"
            }
        ];


        $scope.dateOptions  = {
            formatYear: 'yy',
            startingDay: 1
        };

        $scope.close = function (){
            $modalInstance.close();
        }


        Base.user_admin().then(function (result) {
            $scope.list_user       = result.data.data.map(function (value){
                if(value.user){
                    return {
                        'text': value.user.fullname + " - " +value.user.email,
                        'id'  : value.user.id
                    }
                }
            });
        });


        $scope.getListAdmin = function (query){
            var defer = $q.defer();
            
            var data = $scope.list_user.filter(function(user) {
                if(user){
                    return user.text.toLowerCase().indexOf(query.toLowerCase()) != -1;
                }
            });

            defer.resolve(data.slice(0,10));
            return defer.promise;
            
        }

        $scope.addTaskProcessing = false;

        $scope.addTask = function (time, item){

            if(time.due_date){
                item.due_date = new Date(time.due_date).getTime()/ 1000;
            }
            if(time.reminder){
                item.reminder = new Date(time.reminder).getTime()/ 1000;
            }


            item.task_refer = $scope.refers;
            item.assigner   = [];
            angular.forEach($scope.assigner, function (value){
                item.assigner.push(value.id)
            })
            item.assigner = item.assigner.join(',');
            $scope.addTaskProcessing     = true;
            $http.post(ApiPath + 'tasks/add-task', item).success(function (response){
                $scope.addTaskProcessing = false;
                if(response.error){
                    return toaster.pop('warning', 'Thông báo', response.error_message);
                }

                toaster.pop('success', 'Thông báo', response.error_message);
                $modalInstance.close(response.data);
                refer_item.task = response.data;

            })
        }


        $scope.getCategory = function (){
            $http.get(ApiPath + 'tasks/task-category').success(function (resp){
                if(!resp.error){
                    $scope.list_category = resp.data;
                }
            })
        }

        $scope.onSuggestPick = function ($item, $model, $label){
            $scope.refers.push({
                refer_id : $item.id,
                name     : $item.name,
                type     : $scope.currentType
            });
            $scope._SuggestModel = "";
        }

        $scope.removeRefer = function (refer){
            $scope.refers.splice($scope.refers.indexOf(refer), 1);
        }

        $scope.suggest = function (value){
            var type;
            var email_regex = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

            if(value.match(/^SC\d+$/g)){
                type = 1;
            }else if(email_regex.test(value)){
                type = 3;
            }else {
                type = 2;
            }

            return Tasks.SuggestOrder(value, type).then(function (result){
                if(result){
                    return result.data.data;
                }
            })
        }


        $scope.getCategory();

    }
])

.controller('ModalChangeStatusCtrl', ['$scope', '$modalInstance', 'PhpJs', 'Order', 'items', 'group', 'status_accept', 'list_status', 'group_order_status', 'list_courier',
    function($scope, $modalInstance, PhpJs, Order, items, group, status_accept, list_status, group_order_status, list_courier) {
        $scope.frm_submit           = false;
        $scope.tracking_code        = items.tracking_code;
        $scope.data                 = {sc_code : items.tracking_code, status: 0, city: 'SC-HN', note: '', courier : list_courier[items.courier_id]['prefix']};
        $scope.status_accept        = status_accept;
        $scope.list_status          = list_status;
        $scope.group_order_status   = group_order_status;
        $scope.list_status_accept   = [];
        $scope.status               = items.status;

        if($scope.status_accept[group] == undefined || $scope.status_accept[group].length == 0){
            Order.StatusAccept(group).then(function (result) {
                if(!result.data.error){
                    $scope.status_accept[group]     = result.data.data;
                    var list_status_accept  = [];
                    angular.forEach(result.data.data, function(value) {
                        if(group_order_status[value] != undefined){
                            list_status_accept = list_status_accept.concat(group_order_status[value]);
                        }
                    });
                    $scope.list_status_accept   = PhpJs.array_unique(list_status_accept);
                }
            });
        }else{
            var list_status_accept  = [];
            angular.forEach($scope.status_accept[group], function(value) {
                list_status_accept   = list_status_accept.concat(group_order_status[value]);

            });
            $scope.list_status_accept   = PhpJs.array_unique(list_status_accept);
        }

        $scope.close = function(){
            $modalInstance.close('');
        }

        $scope.save = function(data){
            $scope.frm_submit = true;
            Order.ChangeStatus(data).then(function (result) {
                if(result.data.error != 'success'){
                    $scope.frm_submit   = false;
                }else{
                    $modalInstance.close($scope.data.status);
                }
            });
        }


    }
])
.controller('ModalChangeCourierCtrl', ['$scope', '$modalInstance', 'PhpJs', 'Order', 'items',
    function($scope, $modalInstance, PhpJs, Order, items) {
        $scope.frm_submit           = false;
        $scope.tracking_code        = items.tracking_code;
        $scope.courier_id           = items.courier_id;
        $scope.data                 = {tracking_code : items.tracking_code,courier_id : 0};
        $scope.list_courier         = {};

        Order.SuggestCourier({'tracking_code' : items.tracking_code}).then(function (result) {
            if(!result.data.error){
                $scope.list_courier = result.data.data;
            }
        });

        $scope.close = function(){
            $modalInstance.close('');
        }

        $scope.save = function(data){
            $scope.frm_submit = true;
            Order.ChangeCourier(data).then(function (result) {
                if(result.data.error){
                    $scope.frm_submit   = false;
                }else{
                    $modalInstance.close($scope.data.courier_id);
                }
            });
        }


    }
])

.controller('PostmanActionCtrl', ['$scope', '$modal', '$http', 'toaster', 'Location', 'Base',
    function($scope, $modal, $http, toaster, Location, Base) {

        $scope.OpenListPostMan = function (city, district, ward, courier_id){
            var modalInstance = $modal.open({
                templateUrl: 'tpl/search/modal.list.postman.html',
                controller: function($scope, $modalInstance, $http, city, district, ward, courier_id, $timeout, Base) {
                    $scope.frm = {
                        city: 0,
                        district: 0,
                        ward: 0
                    };
                    $scope.list_postman = [];

                    $scope.loading = false;

                    Base.Courier().then(function (result) {
                        if(!result.data.error){
                            delete result.data.data[2];
                            delete result.data.data[3];
                            delete result.data.data[4];
                            delete result.data.data[5];
                            delete result.data.data[7];
                            delete result.data.data[10];
                            delete result.data.data[11];
                            $scope.list_courier        = result.data.data;
                            angular.forEach(result.data.data, function(value, key) {
                                $scope.courier[value.id]    = value.name;
                            });
                        }
                    });
                    Location.province('all').then(function (result) {
                        if(result){
                            if(!result.data.error){
                                $scope.list_city        = result.data.data;
                                if(city){
                                    $timeout(function() {
                                        $scope.getDistrict(city);
                                        $scope.frm.city = city;
                                        city = 0;
                                    }, 0)
                                }
                            }else{
                                toaster.pop('warning', 'Thông báo', 'Tải danh sách Tỉnh/Thành Phố lỗi !');
                            }
                        }else{
                            toaster.pop('warning', 'Thông báo', 'Kết nối dữ liệu thất bại !');
                        }
                    });

                    $scope.getDistrict = function (City, callback){
                        $scope.districts        = [];
                        Location.district(City,'all', true ).then(function (districts) {
                            $timeout(function (){
                                $scope.districts = districts.data.data;
                                if(callback && typeof callback == 'function'){
                                    callback();
                                }
                                if(district){
                                    $timeout(function() {
                                        $scope.getWard(district);
                                        $scope.frm.district = district;
                                        district = 0
                                    }, 0);
                                    

                                }
                            });
                            
                        });
                    }

                    $scope.getWard = function (District, callback){
                        $scope.wards        = [];
                        Location.ward(District,'all', true ).then(function (wards) {
                            $timeout(function (){
                                if(callback && typeof callback == 'function'){
                                    callback();
                                }
                                $scope.wards = wards.data.data;
                                if(ward){
                                    $timeout(function() {
                                        $scope.frm.ward = ward;
                                        ward = 0
                                    })

                                }
                            });
                            
                        });
                    }

                    $scope.load = function(frm){
                        var city_id     = frm.city,
                            district_id = frm.district,
                            ward_id     = frm.ward;
                            courier_id     = frm.courier;

                        if(!city_id || !district_id){
                            return;
                        }
                        $scope.loading = true;

                        $http.get(ApiPath + 'postman?city_id=' + city_id + '&district_id=' + district_id + '&ward_id=' + ward_id + '&courier_id=' + courier_id).success(function (resp){
                            $scope.loading = false;
                            if(!resp.error){
                                $scope.list_postman = resp.data;
                            }
                        })
                    }
                    
                    if((city && district)){
                        $scope.load({
                            city: city,
                            district: district,
                            ward: ward,
                            courier_id: courier_id
                        })
                    }
                },
                size: 'lg',
                resolve: {
                    city: function () {
                        return city;
                    },
                    district: function () {
                        return district;
                    },
                    ward: function (){
                        return ward;
                    },
                    courier_id: function (){
                        return courier_id;
                    }

                }
            });
        }
    }
]);