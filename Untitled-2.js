    (function (angular){
        angular.module('calculate_app', ['ui.select'])
        .directive('ngEnter', function() {
            return function(scope, element, attrs) {
                    element.bind("keydown keypress", function(event) {
                        if(event.which === 13) {
                                scope.$apply(function(){
                                        scope.$eval(attrs.ngEnter);
                                });
                                
                                event.preventDefault();
                        }
                    });
                };
        })
        .directive('formatnumber', function() {
            return {
                require: 'ngModel',
                link: function(scope, element, attrs, modelCtrl) {
                modelCtrl.$parsers.push(function(data) {
                    if(data != '' && data != undefined){
                        //convert data from view format to model format
                        var string  = data.toString().replace(/^(0*)/,"");
                        string      = string.replace(/(\D)/g,"");
                        string      = string.replace(/^$/,"0");
                        string      = string.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
                        
                        if (string!=data) {
                        modelCtrl.$setViewValue(string);
                        modelCtrl.$render();
                        }   
                                
                        return string; //converted
                    }
                    return;
                });
                
                modelCtrl.$formatters.push(function(data) {
                    //convert data from model format to view format
                    if(data != '' && data != undefined){
                        var string  = data.toString().replace(/','/,"");
                            string      = string.replace(/(\D)/g,"");
                            string      = string.replace(/^$/,"0");
                            string      = string.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
                        if (string!=data) {
                        modelCtrl.$setViewValue(string);
                        modelCtrl.$render();
                        }
                        return string;
                    }
                    return;
                });
                }
            }
            })

            .directive('districtFromGooglePlace', ['$location', '$anchorScroll','$http', '$timeout', function($location, $anchorScroll, $http, $timeout) {
                return {
                    restrict: 'ACE',
                    require: 'ngModel',
                    scope: {
                        details: '=?',
                        defaultDistrict: '=?'
                    },
                    link: function(scope, el, attr, model) {
                        window.bodauTiengViet = function(str) {  
                            if(!str){
                                return str;
                            }
                            str= str.toLowerCase();  
                            str= str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a");  
                            str= str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e");  
                            str= str.replace(/ì|í|ị|ỉ|ĩ/g,"i");  
                            str= str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o");  
                            str= str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u");  
                            str= str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y");  
                            str= str.replace(/đ/g,"d");  
                            return str;  
                        }
                        scope.placeholder = attr.placeholder || "Quận huyện/tỉnh thành";
                        scope.list_address = [];
                        scope.selected_location = undefined;

                        var url = '/api/base/list-address';

                        if (attr.full == "true") {
                            url = url + '?full=true';
                        }
                        $http.get(url)
                            .success(function (result){
                                if (!result.error) {
                                    var list_address = result.data;
                                    angular.forEach(list_address, function (value){
                                        value.tv_khong_dau = window.bodauTiengViet(value.full_address);
                                        value.symnoyms_kd = window.bodauTiengViet(value.symnoyms);
                                        value.district_kd = window.bodauTiengViet(value.district_name);
                                        value.city_kd     = window.bodauTiengViet(value.city_name);
                                    })

                                    scope.list_address = list_address;
                                    scope.$watch('defaultDistrict', function (newVal){
                                        if (newVal > 0) {
                                            var val = false;
                                            angular.forEach(scope.list_address, function (value){
                                                if (value.district_id == newVal ) {
                                                    val = value;
                                                };
                                            });

                                            if (val) {
                                                $timeout(function (){
                                                    scope.selected_location = val;
                                                }, 0);
                                            }else {
                                                scope.selected_location = {};
                                            };  
                                        }
                                    })

                                };
                            })
                            .error(function (error){
                                //toaster.pop('warning', 'Thông báo', 'Tải danh sách Tỉnh/Thành Phố lỗi !');
                            });


                        scope.limitcitysearch = 5000; //Init with no limite : to see a previous selected valued in database (edit mode)

                        scope.search_str = "";
                        scope.CheckCity = function (CityTyped) {
                            scope.search_str = CityTyped;

                            if (CityTyped.length >= 1) {
                                scope.limitcitysearch = 20;
                            }
                            else {
                            scope.limitcitysearch = 20;
                            }
                        }

                        scope.$watch('details', function (newVal){
                            if (newVal) {
                                var level_1 = "";
                                var level_2 = "";

                                for (var i = 0; i < newVal.address_components.length; i++) {
                                    var addressType = newVal.address_components[i].types[0];
                                    if (addressType == 'administrative_area_level_1')
                                        level_1 = newVal.address_components[i]['short_name'];
                                    if (addressType == 'administrative_area_level_2')
                                        level_2 = newVal.address_components[i]['short_name'];
                                }
                                var val = false;
                                angular.forEach(scope.list_address, function (value){
                                    if (value.hasOwnProperty('district_name') && value.hasOwnProperty('city_name')) {
                                        if (value.district_name.indexOf(level_2) != -1 && value.city_name.indexOf(level_1) != -1) {
                                            val = value;
                                            
                                        };
                                    };
                                });

                                if (val) {
                                    $timeout(function (){
                                        scope.selected_location = val;
                                    }, 0);
                                }else {
                                    scope.selected_location = {};
                                };
                            };
                        })  

                        
                        

                        scope.$watch('selected_location', function (value){
                            if (angular.isObject(value)) {
                                scope.search_str = value.city_name;
                            };
                            model.$setViewValue(value);
                        })
                    },
                    template: '<ui-select allow-clear="true" ng-model="$parent.selected_location"  ng-disabled="disabled" >'+
                                            '<ui-select-match placeholder="{{$parent.placeholder}}">{{$select.selected.full_address}}</ui-select-match>'+
                                            '<ui-select-choices refresh="CheckCity($select.search)" refresh-delay="100" repeat="value in list_address  | filter: search_str | limitTo: limitcitysearch track by $index">'+
                                            '<span>{{value.full_address}}</span>'+
                                            '</ui-select-choices>'+
                                        '</ui-select>'
                };
            }])
            .directive('calculateFromCity', ['$location', '$anchorScroll','$http', '$timeout', function($location, $anchorScroll, $http, $timeout) {
                return {
                    restrict: 'ACE',
                    require: 'ngModel',
                    scope: {
                        details: '=?',
                        defaultCity: '=?'
                    },
                    link: function(scope, el, attr, model) {
                        window.bodauTiengViet = function(str) {  
                            if(!str){
                                return str;
                            }
                            str= str.toLowerCase();  
                            str= str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a");  
                            str= str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e");  
                            str= str.replace(/ì|í|ị|ỉ|ĩ/g,"i");  
                            str= str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o");  
                            str= str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u");  
                            str= str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y");  
                            str= str.replace(/đ/g,"d");  
                            return str;  
                        }
                        scope.placeholder = attr.placeholder || "Quận tỉnh thành";
                        scope.list_address = [];
                        scope.selected_location = undefined;

                        //var url = '/api/v1/district/shipchung?cities=18,35,52,49,59&cmd=index';
                        var url = '/api/v1/district/shipchung';

                        if (attr.full == "true") {
                            url = url + '?full=true';
                        }
                        $http.get(url)
                            .success(function (result){
                                if (!result.error) {
                                    scope.list_address 	= result;
                                    // scope.list_address 	= [];
                                    // for (var city in list_address) {
                                    // 	scope.list_address.push(list_address[city]);
                                    // };
                                    // // angular.forEach(list_address, function (value){
                                    // //     value.tv_khong_dau = window.bodauTiengViet(value.full_address);
                                    // //     value.symnoyms_kd = window.bodauTiengViet(value.symnoyms);
                                    // //     value.district_kd = window.bodauTiengViet(value.district_name);
                                    // //     value.city_kd     = window.bodauTiengViet(value.city_name);
                                    // // })

                                    scope.$watch('defaultCity', function (newVal){
                                        if (newVal > 0) {
                                            var val = false;
                                            angular.forEach(scope.list_address, function (value){
                                                if (value.hasOwnProperty('city_name')) {
                                                    if (value.city_id == newVal ) {
                                                        val = value;
                                                    };
                                                };
                                            });

                                            if (val) {
                                                $timeout(function (){
                                                    scope.selected_location = val;
                                                }, 0);
                                            }else {
                                                scope.selected_location = {};
                                            };  
                                        }
                                    })		

                                };
                            })
                            .error(function (error){
                                //toaster.pop('warning', 'Thông báo', 'Tải danh sách Tỉnh/Thành Phố lỗi !');
                            });


                        scope.limitcitysearch = 5000; //Init with no limite : to see a previous selected valued in database (edit mode)

                        scope.search_str = "";
                        scope.CheckCity = function (CityTyped) {
                            scope.search_str = CityTyped;

                            if (CityTyped.length >= 1) {
                                scope.limitcitysearch = 20;
                            }
                            else {
                            scope.limitcitysearch = 20;
                            }
                        }

                        scope.$watch('details', function (newVal){
                            if (newVal) {
                                var level_1 = "";
                                var level_2 = "";

                                for (var i = 0; i < newVal.address_components.length; i++) {
                                    var addressType = newVal.address_components[i].types[0];
                                    if (addressType == 'administrative_area_level_1')
                                        level_1 = newVal.address_components[i]['short_name'];
                                    if (addressType == 'administrative_area_level_2')
                                        level_2 = newVal.address_components[i]['short_name'];
                                }
                                var val = false;
                                angular.forEach(scope.list_address, function (value){
                                    if (value.hasOwnProperty('district_name') && value.hasOwnProperty('city_name')) {
                                        if (value.district_name.indexOf(level_2) != -1 && value.city_name.indexOf(level_1) != -1) {
                                            val = value;
                                            
                                        };
                                    };
                                });

                                if (val) {
                                    $timeout(function (){
                                        scope.selected_location = val;
                                    }, 0);
                                }else {
                                    scope.selected_location = {};
                                };
                            };
                        })  

                        


                        scope.$watch('selected_location', function (value){
                            if (angular.isObject(value)) {
                                scope.search_str = value.city_name;
                            };
                            model.$setViewValue(value);
                        })
                    },
                    template: '<ui-select allow-clear="true" ng-model="$parent.selected_location"  ng-disabled="disabled" >'+
                                            '<ui-select-match placeholder="{{$parent.placeholder}}">{{$select.selected.city_name}}</ui-select-match>'+
                                            '<ui-select-choices refresh="CheckCity($select.search)" refresh-delay="100" repeat="value in list_address  | filter: $select.search ">'+
                                            '<span>{{value.city_name}}</span>'+
                                            '</ui-select-choices>'+
                                        '</ui-select>'
                };
            }])


            .directive('calculateToCountry', ['$location', '$anchorScroll','$http', '$timeout', function($location, $anchorScroll, $http, $timeout) {
                return {
                    restrict: 'ACE',
                    require: 'ngModel',
                    scope: {
                        details: '=?',
                        defaultCountry: '=?'
                    },
                    link: function(scope, el, attr, model) {
                        window.bodauTiengViet = function(str) {  
                            if(!str){
                                return str;
                            }
                            str= str.toLowerCase();  
                            str= str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a");  
                            str= str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e");  
                            str= str.replace(/ì|í|ị|ỉ|ĩ/g,"i");  
                            str= str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o");  
                            str= str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u");  
                            str= str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y");  
                            str= str.replace(/đ/g,"d");  
                            return str;  
                        }
                        scope.placeholder       = attr.placeholder || "Đến quốc gia";
                        scope.list_address      = [];
                        scope.selected_location = undefined;

                        
                        var url = '/api/base/country';

                    
                        $http.get(url)
                            .success(function (result){
                                if (!result.error) {
                                    scope.list_address 	= result.data;

                                    console.log('ahi', scope.list_address);

                                    scope.$watch('defaultCountry', function (newVal){
                                        console.log('defaultCountry', scope.defaultCountry, newVal);
                                        if (newVal) {
                                            var val = false;
                                            angular.forEach(scope.list_address, function (value){
                                                if (value.hasOwnProperty('country_code')) {
                                                    if (value.country_code == newVal ) {
                                                        val = value;
                                                    };
                                                };
                                            });

                                            if (val) {
                                                $timeout(function (){
                                                    scope.selected_location = val;
                                                }, 0);
                                            }else {
                                                scope.selected_location = {};
                                            };  
                                        }
                                    })		

                                };
                            })
                            .error(function (error){
                                //toaster.pop('warning', 'Thông báo', 'Tải danh sách Tỉnh/Thành Phố lỗi !');
                            });


                        scope.limitcitysearch = 5000; //Init with no limite : to see a previous selected valued in database (edit mode)

                        scope.search_str = "";
                        scope.CheckCity = function (CityTyped) {
                            scope.search_str = CityTyped;

                            if (CityTyped.length >= 1) {
                                scope.limitcitysearch = 20;
                            }
                            else {
                            scope.limitcitysearch = 20;
                            }
                        }

                        


                        scope.$watch('selected_location', function (value){
                            if (angular.isObject(value)) {
                                scope.search_str = value.country_name;
                            };
                            model.$setViewValue(value);
                        })
                    },
                    template: '<ui-select allow-clear="true" ng-model="$parent.selected_location"  ng-disabled="disabled" >'+
                                            '<ui-select-match placeholder="{{$parent.placeholder}}">{{$select.selected.country_name}}</ui-select-match>'+
                                            '<ui-select-choices refresh="CheckCity($select.search)" refresh-delay="100" repeat="value in list_address  | filter: $select.search ">'+
                                            '<span>{{value.country_name}}</span>'+
                                            '</ui-select-choices>'+
                                        '</ui-select>'
                };
            }])


            .controller("CalculateWidget", ['$scope', '$location', '$http', '$timeout', '$window',  function ($scope, $location, $http, $timeout, $window){
                $scope.openLink = function (from_city, to_district, weight, collect){
                    
                    if (from_city && to_district) {
                        var is_global = to_district.country_code ? 1 : 0;
                        var to_country = to_district.country_code ? to_district.country_code : "VN";
                        var to_countr_id = to_district.id ? to_district.id : 0;
                        

                        window.location = '/tinh-phi-van-chuyen/?from_city='+from_city.city_id+'&to_district='+to_district.district_id+'&weight='+(weight || 0)+'&collect=' + (collect || 0) + '&from_city_name=' + from_city.city_name + '&to_district_name=' + to_district.district_name + "&to_city_name=" + to_district.city_name+ '&is_global='+ is_global + '&to_country='+ to_country + '&to_country_id='+ to_countr_id;
                    };
                }	

            }])
            .controller("CalculatePage", ['$scope', '$location', '$http', '$timeout', function ($scope, $location, $http, $timeout){

                function getParameterByName(name) {
                    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
                    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                    results = regex.exec(window.location.search);
                    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
                }

                
                $scope.From = undefined;
                $scope.To 	= undefined;

                var getCenterDistrict = function (listDistrict){
                    for (var i = 0; i < listDistrict.length; i++) {
                        if (listDistrict[i].location == 1) {
                            console.log('2', listDistrict[i].district_id)
                            return listDistrict[i].district_id;
                        };
                    };
                    
                }

                var service_obj = {
                    1: "phatcham",
                    2: "phatnhanh",
                    3: "hoatoc",
                    8: "quocte"
                    

                };


                $scope.timePickup = function (){
                    var now = new Date();
                    if (now.getHours() <= 14) {
                        return 'Trong ngày';
                    }else {
                        return 'Hôm sau';

                    }
                }

                function get_number(data){
                    if(data != undefined && data != ''){
                        if(typeof data == 'string'){
                            return data.toString().replace(/,/gi,"");
                        }else {
                            return data.toString();
                        }
                    }
                    return 0;
                }

                $scope.hasResult = function (){
                    return Object.keys($scope.Result).length > 0;
                }
                $scope.Result = {};
                $scope.Collect = 0;

                var is_global_params    =  parseInt(getParameterByName('is_global'));
                var to_country_params   =  getParameterByName('to_country');
                var to_country_id_params   =  getParameterByName('to_country_id');
                
                var from_city_params 	=  getParameterByName('from_city');
                var to_district_params 	=  getParameterByName('to_district');
                var weight_params 		=  getParameterByName('weight');
                var collect_params 		=  getParameterByName('collect');

                $scope.is_global        = is_global_params;


                if (from_city_params && weight_params && collect_params) {
                    $timeout(function (){
                        $scope.Collect 	            = ''	+ collect_params;
                        $scope.Weight 	            = ''	+ weight_params;
                        $scope.defaultFromCity      = from_city_params;
                        $scope.defaultToDistrict    = to_district_params;
                        $scope.defaultToCountry     = to_country_params
                        
                        $timeout(function (){
                            $scope.tinhphi($scope.From, $scope.To, $scope.Weight, $scope.Collect);
                        }, 200)

                    }, 500)
                    

                };



                $scope.totalLoad = 0;

                $scope.tinhphi = function (from, to, weight, collect){
                    $scope.totalLoad = 0;
                    if(!to) return false;

                    var from_district = getCenterDistrict(from.districts);

                    if(is_global_params == 1){
                        $scope.calculate(from.city_id, from_district, to.id, "NA", weight, 8, collect)
                    }else {
                        $scope.calculate(from.city_id, from_district, to.city_id , to.district_id , weight, 1, collect)
                        $scope.calculate(from.city_id, from_district, to.city_id , to.district_id , weight, 2, collect)
                    }
                    
                }

                $scope.calculate = function (from_city, from_district, to_city, to_district, weight, service, collect){
                    if (!from_city || !from_district) {
                        alert('Vui lòng chọn tỉnh thành gửi');
                        return;
                    };
                    
                    if (!to_city || !to_district) {
                        alert('Vui lòng chọn tỉnh thành gửi');
                        return;
                    };



                    var frm = {
                        "From" : {
                            "City"		: from_city,
                            "Province"	: from_district
                        },
                        "Order": {
                            "Weight"	: get_number(weight),
                            "Amount"	: 10000
                        },
                        "Config": {
                            "Service"	: service,
                            "Protected"	: 2,
                            "CoD"		: 1,
                            "Payment"	: 1,
                            "Fragile"	: 2,
                            "Checking"	: 1
                        },
                        "Domain" 		: "shipchung.vn"
                    }

                    if(is_global_params == 1){
                        frm["To"] =  {
                            "Country"	: to_city
                        }
                    }else {
                        frm["To"] =  {
                            "City"		: to_city,
                            "Province"	: to_district
                        }
                    }

                    if (collect && get_number(collect)) {
                        frm['Order']['Collect'] =  get_number(collect);
                    };
                    var url_api = '/api/rest/courier/calculate';

                    if(is_global_params == 1){
                        url_api = '/api/rest/global/calculate'
                    }

                    $http.post(url_api, frm).success(function (resp){
                        $scope.Result[service_obj[service]] = resp.data;
                        $scope.totalLoad ++;
                    })
                }


            }])

            
    })(angular);
