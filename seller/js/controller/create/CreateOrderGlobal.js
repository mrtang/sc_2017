'use strict';

//List Order
var OrderCreateCtrl;

angular.module('app').controller('OrderCreateParentCtrl',
		    ['$scope', '$rootScope', 'Analytics', '$localStorage','bootbox','$state','$filter',
    function ($scope,   $rootScope,   Analytics,  $localStorage,   bootbox,  $state,  $filter){
	var tran = $filter('translate');
	var i = 1;
    $scope.tabs = [i];

    $scope.addTabs = function (){
        i = i + 1;
        $scope.tabs.push(i);
    }

    $scope.notice = {};
    $rootScope.hasInventory = true;

    $scope.showNotice = function (){
        if ($rootScope.userInfo.first_order_time == 0) {
            $scope.notice.content = 'Quý khách vừa được cấp tín dụng trước 200.000 vnđ, hãy tạo đơn giao hàng ngay bay giờ! - Xem giải thích mức tín dụng & số dư khả dụng là gì <a href="https://www.shipchung.vn/ho-tro/c/thanh-toan/" target="_blank"><span class="text-info">tại đây</span></a>';
            $scope.notice.content_en = 'You got 200.000đ credits (for cash-on-delivery order only), you can create order now and pay it later.';

        }

        if ((+$rootScope.userInfo.balance + $rootScope.userInfo.provisional - $rootScope.userInfo.freeze) < 50000) {
            $scope.notice.content = 'Số dư khả dụng của bạn đang dưới 50.000vnđ, bạn nạp thêm tiền vào tài khoản để sử dụng dịch vụ - Xem hướng dẫn nạp tiền <a href="https://www.shipchung.vn/ho-tro/c/thanh-toan/" target="_blank"><span class="text-info">tại đây</span></a>.';
            $scope.notice.content_en = 'Your available balance account is under $25, please deposit money to your account first, - <a href="https://www.shipchung.vn/ho-tro/c/thanh-toan/" target="_blank"><span class="text-info">Click here to view how to deposit money</span></a>.';
            //$scope.notice.content =""+tran('OBG_SoDuKhaDungDuoi50k')+" <a href='https://www.shipchung.vn/ho-tro/c/thanh-toan/' target='_blank'><span class='text-info'>"+tran('SHIPC_tab1_p_helpLink')+"</span></a>.";
        }

        if (!$scope.hasInventory) {
            $scope.notice.content = 'Bạn chưa nhập địa chỉ lấy hàng để tạo đơn hàng nhanh hơn - Cấu hình ngay tại đây.';
            $scope.notice.content_en = 'Bạn chưa nhập địa chỉ lấy hàng để tạo đơn hàng nhanh hơn - Cấu hình ngay tại đây.';
        }
    }
    $scope.showNotice();




    Analytics.trackPage('/order/create-v2');
}]);


angular.module('app').controller('OrderCreateV2GlobalCtrl', 
							[ '$scope','$filter', '$rootScope', '$http', '$state', '$stateParams', '$timeout', '$modal', 'AppStorage', 'Location', 'Inventory', 'Order', 'ConfigShipping', 'PhpJs', 'toaster', '$localStorage', 'WarehouseRepository',
    function OrderCreateV2Ctrl($scope,  $filter,   $rootScope,   $http,   $state,     $stateParams,    $timeout,   $modal,   AppStorage,   Location,   Inventory,   Order,   ConfigShipping,   PhpJs,   toaster,    $localStorage,  WarehouseRepository ){
	var tran = $filter('translate');
	var self = this;
	//checkchina
	$scope.arrChina = {
			country : [],
			china_1	: []
	}
	//
    $scope.fee_detail            = {};
    $scope.list_courier_detail   = [];
    
    $scope.list_ward_by_district = [];

    $scope.list_services = [
        {
            id      : 2,
            name    : 'Dịch vụ giao hàng nhanh',
            name_en	: 'Express delivery service'	
        },
        {
            id      : 1,
            name    : 'Dịch vụ giao hàng tiết kiệm',
            name_en	: 'Economy delivery service'
        }
    ];
    $scope.list_services_global = [
        {
            id      : 8,
            name    : 'Chuyển phát nhanh quốc tế',
            name_en : 'International Express',
            
        },
        {
            id      : 9,
            name    : 'Chuyển phát tiết kiệm quốc tế',
            name_en : 'International Economy',
            
        }
    ];

    $scope.calculateInfo = {
        selected_courier: ""
    }
    $scope._boxme = {
        selected_item: null,
        Items: []
    }


    $scope.clearData = function (){
        $scope._boxme.Items = [];
        $scope.Config = {
            Service     : 2,
            Protected   : "2",
            Payment     : 2,
            Type        : parseInt($localStorage['last_config_type_select']) || 2,
            Checking    : "1",
            Fragile     : "2"
        };
        $scope.From           = {

        };
        $scope.From.Inventory = {};
        $scope.To             = {
            Buyer: {
            }
        };
        $scope.Product        = {
            Amount : 0,
            Quantity: 1,
            Note: ""
        };
    }

    $scope.clearData();
    
    $scope.list_inventory   = null;  // danh sách kho hàng

    $scope.show_phone2 = false;


    var busy = false;
    $scope.loadInventory = function (params, q){
        params = params || {};
        if($stateParams.bc){
            params = angular.extend(params, {bc: $stateParams.bc});
        }
        if (busy && !params.lat) {
            return false;
        };
        busy = true;
        Inventory.loadWithPostOffice(params).then(function (result) {
            busy = false;
            if(!result.data.error){
                $scope.list_inventory  = result.data.data;
                
                if ($stateParams.bc && $stateParams.bc !== "" && $stateParams.bc !== null && $stateParams.bc !== undefined) {
                    result.data.data.forEach(function (value){
                        if (value.id == $stateParams.bc) {
                            $scope.From.Inventory = value;
                        };
                    })  
                }else {
                    $scope.From.Inventory = $scope.list_inventory[0];
                }
            }else {
                $rootScope.hasInventory = false;
            }
            $scope.showNotice();
        });
    }

    


    // // Config fee
    // ConfigShipping.load().then(function (result) {
    //     if(!result.data.error){
    //         $scope.config_fee               = result.data.data;
    //     }
    // });


    






    // ---------------------- Phone validation ---------------- //

    var isTelephoneNumber = function (phone){
        var list_telephone_prefix = ['076','025','075','020','064','072','0281','030','0240','068','0781','0350','0241','038','056','0210','0650','057','0651','052','062','0510','0780','055','0710','033','026','053','067','079','0511','022','061','066','0500','036','0501','0280','0230','054','059','037','0219','073','0351','074','039','027','0711','070','0218','0211','0321','029','08','04','0320','031','058','077','060','0231','063'];
        var _temp = phone.replace(new RegExp("^("+list_telephone_prefix.join("|")+")"), '');
        if(phone.length !== _temp.length){
            return true;
        }
        return false;
    }

    $scope.phoneIsWrong = false;
    $scope.addingPhone = function (model){
        $scope.phoneIsWrong = false;
        angular.forEach(model, function (value){
            if(!isTelephoneNumber(value.text)){
                var result = chotot.validators.phone(value.text, true);
                if(result !== value.text){
                     $scope.phoneIsWrong = true;
                     return false;
                }
            }
        }) 
    }


    //---------------------------------------------------------//


    // ----------------------Format data-----------------------//
    function get_boxsize(data){
        if(data.L != undefined && data.L != '' &&
            data.W != undefined && data.W!= '' &&
            data.H != undefined && data.H != ''){
            var long    = data.L.toString().replace(/,/gi,"");
            var width   = data.W.toString().replace(/,/gi,"");
            var height  = data.H.toString().replace(/,/gi,"");

            return long+'x'+width+'x'+height;
        }else{
            return '';
        }
    }

    function get_phone(data){
        var phone = '';
        angular.forEach(data, function(value, key) {
            if(key == 0){
                phone += value.text;
            }else if(key == 1){
                phone += ','+value.text;
            }
        });
        return phone;
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

    $scope.get_number = get_number;

    // ------------------------------------------------------ //
    var isFromStock = function (){
        return !$scope.From.Inventory.post_office || $scope.From.Inventory.post_office == false;
    }

    var isBoxmeInventory = function (){
        return $scope.From.Inventory && 
               $scope.From.Inventory.warehouse_code &&
               $scope.From.Inventory.warehouse_code.length >0;
    }

    var canCalculateShiphung = function (){
        var weight = $scope.Product.Weight  ? get_number($scope.Product.Weight)     : 0;
        var price  = $scope.Product.Price   ? get_number($scope.Product.Price)      : 0;
        var size   = $scope.Product.BoxSize ? get_boxsize($scope.Product.BoxSize)   : "";
        return $scope.From.Inventory && !$scope.From.Inventory.warehouse_code && $scope.From.Inventory.id > 0 && (weight > 0 || size != "") && price > 0;
    }

    var canCalculateBoxme = function (){
        var price  = $scope._boxme.TotalItemAmount;
        var weight = $scope._boxme.TotalItemWeight;

        return isBoxmeInventory() && 
               weight > 0 && 
               price > 0;
    }

    var getProductName = function (){
        if(isBoxmeInventory()){
            return $scope._boxme.ItemsName;
        }
        return $scope.Product.Name;
    }


    var BuildData = function (isCalculate){
        var weight  = $scope.Product.Weight  ? get_number($scope.Product.Weight)     : 0;

        var price   = $scope.Product.Price   ? get_number($scope.Product.Price)      : 0;
        var size    = $scope.Product.BoxSize ? get_boxsize($scope.Product.BoxSize)   : "";

        var data = {
            Domain: !isBoxmeInventory() ? 'seller.shipchung.vn' : 'boxme.vn'
        }



        data['From'] = {
            City        : $scope.From.Inventory.city_id,
            Province    : $scope.From.Inventory.province_id
        }
        // FROM 
        if(isFromStock()){
                data['From']['Stock'] =  $scope.From.Inventory.id;
        }else {
            data['From'] = {};
            data['From']['PostCode'] = $scope.From.Inventory.id;
        }

        if($scope.From.Inventory.ward_id != undefined && $scope.From.Inventory.ward_id > 0){
            data['From']['Ward']    = $scope.From.Inventory.ward_id;
        }
        
        
        if($scope.From.Inventory && $scope.From.Inventory.warehouse_code && $scope.From.Inventory.warehouse_code.length > 0){
            if($scope.From.Inventory.warehouse_code.indexOf('VTP') != -1 && $scope.From.Inventory.warehouse_code !== 'VTPHN02'){
                data['Courier'] = 1;
            }
        }
        

        // TO 

        // data['To'] = {
        //     City        : $scope.To.Buyer.Area.city_id,
        //     Province    : $scope.To.Buyer.Area.district_id,
        //     Address     : $scope.To.Buyer.Address,
        // };

        // if ($scope.To.Buyer.ward_id && $scope.To.Buyer.ward_id > 0) {
        //     data['To']['Ward'] = $scope.To.Buyer.ward_id
        // }


        if($scope.To.Buyer.Country && $scope.To.Buyer.Country.id !== 237 && $scope.To.Buyer.CityGlobal){
        
            data['To'] = {
                'Country'   : $scope.To.Buyer.Country.id,
                'City'      : $scope.To.Buyer.CityGlobal.id,
                'Address'   : $scope.To.Buyer.Address,
            }
             // check truong hop trung quoc 2 quoc gia (china 1, china 2)
            if($scope.To.Buyer.Country.id == -1){
            	var check_china_1 = false;
            	angular.forEach($scope.arrChina.china_1, function (value){
            		if($scope.To.Buyer.CityGlobal.id == value.id){
            			check_china_1 = true
            		}
                    
                })
                if(check_china_1 == true){
                	data['To']['Country'] = 44;
                }else{
                	data['To']['Country'] = 246
                }
            }
         // check truong hop trung quoc 2 quoc gia (china 1, china 2)	
            if($scope.To.Buyer.Zipcode && $scope.To.Buyer.Zipcode !== ""){
                data['To']['Zipcode'] = $scope.To.Buyer.Zipcode;
            }
            var checkZipCode = false
            angular.forEach($scope.list_country, function (value){
                if(data['To']['Country'] == value.id){
                    checkZipCode = value.required_zipcode;
                }
            })
            if(checkZipCode == 1 && !data['To']['Zipcode'] && !isCalculate) {
            	return tran('ORDERC_ChuaNhapZipCode');//'Quý khách chưa chọn tỉnh thành giao hàng đến';
            }
            /*if (data['To']['Country'] == null){
        		return tran('ORDERC_ChuaNhapQuocGia');//'Quý khách chưa chọn quốc gia giao hàng đến';
        	}*/
            if (data['To']['City'] == null){
            	return tran('ORDERC_ChuaNhapTinhThanh');//'Quý khách chưa chọn tỉnh thành giao hàng đến';
            }
            if (data['To']['Address'] == null){
            	return tran('ORDERC_ChuaNhapDiaChi');//'Quý khách chưa nhập địa chỉ giao hàng đến';
            }
            //data['Courier'] = 8;

        }else {
            data['To'] = {
                City        : $scope.To.Buyer.Area ? $scope.To.Buyer.Area.city_id 		: null,
                Province    : $scope.To.Buyer.Area ? $scope.To.Buyer.Area.district_id	: null,
                Address     : $scope.To.Buyer.Area ? $scope.To.Buyer.Address			: null,
                Country     : $scope.To.Buyer.Area ? $scope.To.Buyer.Country.id			: null
            };
            if ($scope.To.Buyer.ward_id && $scope.To.Buyer.ward_id > 0) {
                data['To']['Ward'] = $scope.To.Buyer.ward_id
            }
            if($scope.list_ward_by_district && $scope.list_ward_by_district.length >= 1  && !isCalculate){
            	if(data['To']['Ward'] == null){
            		return tran('ORDERC_ChuaNhapPhuongXa');
            	}
            }
            /*if (data['To']['Country'] == null){
            	return tran('ORDERC_ChuaNhapQuocGia');//'Quý khách chưa chọn quốc gia giao hàng đến';
            }*/
            if (data['To']['City'] == null ){
            	return tran('ORDERC_ChuaNhapTinhThanh');//'Quý khách chưa chọn tỉnh thành giao hàng đến';
            }
            if (data['To']['Province'] == null){
            	return tran('ORDERC_ChuaNhapQuanHuyen');//'Quý khách chưa chọn chỉ quận huyện giao hàng đến';
            }
            if (data['To']['Address'] == null && !isCalculate){
            	return tran('ORDERC_ChuaNhapDiaChi');//'Quý khách chưa nhập địa chỉ giao hàng đến';
            }
            
        }
        
        
        if(isBoxmeInventory()){
            weight = $scope._boxme.TotalItemWeight;
            price  = $scope._boxme.TotalItemAmount;
            $scope.Product.Quantity = $scope._boxme.TotalItemQuantity;

            data['Items'] = []

            angular.forEach($scope._boxme.Items, function (value){
                data['Items'].push({
                    'Name'      : value.ProductName,
                    'Price'     : value.PriceItem * value.Quantity,
                    'Quantity'  : value.Quantity,
                    'Weight'    : value.WeightItem * value.Quantity,
                    'BSIN'      : value.SellerBSIN
                });
            });

        }

        data['Order']   = {
            Weight: +weight, 
            Amount: +price,
            Quantity: $scope.Product.Quantity || 1
        };
        if(data['Order'] && !data['Order']['Quantity']){
        	return tran('ORDERC_ChuaNhapSoLuong');
        }

        if(size != ''){
            data['Order']['BoxSize']    = size;
        }


        data['Config']  = {Service: +$scope.Config.Service, Protected: +$scope.Config.Protected, Checking: +$scope.Config.Checking, Fragile: +$scope.Config.Fragile};

        switch (parseInt($scope.Config.Type)){
            case 1: 
                data['Config']['CoD']        = 1;
                data['Config']['Payment']    = 1;
                data['Config']['PaymentCod'] = 2;
            break;

            case 2: 
                data['Config']['CoD']       = 1;
                data['Config']['Payment']   = 2;
                data['Config']['PaymentCod'] = 1;
                
            break;

            case 3: 
                data['Config']['CoD']        = 1;
                data['Config']['Payment']    = 1;
                data['Config']['PaymentCod'] = 2;
            break;

            case 4: 
                data['Config']['CoD']        = 2;
                data['Config']['PaymentCod'] = 1;
                data['Config']['Payment']    = 2;
            break;

            case 5: 
                data['Config']['CoD']           = 2;
                data['Config']['Payment']       = 1;
                data['Config']['PaymentCod']    = 2;
            break;
        }


        $scope.Config.CoD     = data['Config']['CoD'];
        $scope.Config.Payment = data['Config']['Payment'];




        if ($scope.Product.MoneyCollect !== undefined && $scope.Product.MoneyCollect !== "" && $scope.Config.Type == 1) {
            data['Order']['Collect'] = get_number($scope.Product.MoneyCollect);

            // Chan tien thu ho phai >= tien hang
            /*
                if (get_number($scope.Product.MoneyCollect) > +price) {
                    data['Config']['CoD'] = 1;
                }else {
                    data['Config']['CoD'] = 2;
                }
            */        
        }



        if (isCalculate) {
            return data;
        };

        var ProductName = getProductName();
        if (!ProductName) {
            return tran('ORDERC_ChuaNhapTenSanPham');//'Quý khách chưa nhập tên sản phẩm';
        }
        if (!$scope.To.Buyer.Phone || $scope.To.Buyer.Phone == "" || $scope.To.Buyer.Phone.length <=5 ) {
            return tran('ORDERC_ChuaNhapSDTNguoiNhan');//'Quý khách chưa nhập số điện thoại người nhận';
        }

        if (!$scope.To.Buyer.Address || $scope.To.Buyer.Address == "") {
            return tran('ORDERC_ChuaNhapDiaChi');//'Quý khách chưa nhập địa chỉ người nhận';
        }

        data['From']['Address'] = $scope.From.Inventory.address;
        data['From']['Phone']   = $scope.From.Inventory.phone;
        data['From']['Name']    = $scope.From.Inventory.user_name;



        var phone               = [$scope.To.Buyer.Phone.replace(/\D/g,"")];

        if ($scope.To.Buyer.Phone2) {
            phone.push($scope.To.Buyer.Phone2);
        }

        data['To']['Address']   = $scope.To.Buyer.Address;
        data['To']['Phone']     = phone.join();
        data['To']['PhoneCode'] = $scope.To.Buyer.PhoneCode;

        data['To']['Name']      = $scope.To.Buyer.Name;
        if (!data['To']['Name']) {
            return tran('ORDERC_ChuaNhapTenNguoiNhan');
        }
        
        if ($scope.To.Buyer.Id && $scope.To.Buyer.Id > 0) {
            data['To']['BuyerId'] = $scope.To.Buyer.Id;
        }

        if ($scope.To.Buyer.POBox) {
            data['To']['POBox'] = $scope.To.Buyer.POBox;
        }



        data['Order']['ProductName'] = ProductName;

        if ($scope.Product.Code !== undefined && $scope.Product.Code !== "") {
            data['Order']['Code'] = $scope.Product.Code;
        }

        if ($scope.Product.Note !== undefined && $scope.Product.Note !== "") {
            data['Order']['Note'] = $scope.Product.Note;
        }

        if ($scope.Product.Id !== undefined && $scope.Product.Id !== "") {
            data['Order']['ItemId'] = $scope.Product.Id;
        }
        if ($scope.Product.Id !== undefined && $scope.Product.Id !== "") {
            data['Order']['ItemId'] = $scope.Product.Id;
        }
        if ($scope.Product.Description !== undefined && $scope.Product.Description !== "") {
            data['Order']['Description'] = $scope.Product.Description;
        }
        

        data['Courier'] = $scope.calculateInfo.selected_courier.courier_id;

        return data;
    }



    // Re-Caculator fee on change data

    var calculateTimeout = null;
    $scope.waiting       = false;

    

    

    var FeeChange = function (newVal, OldVal){
        if (newVal !== undefined && newVal !== undefined ) {

        	
            
            if (canCalculateShiphung() || canCalculateBoxme()) {

                if($scope.To.Buyer.Country && $scope.To.Buyer.Country.id !== 237 && $scope.To.Buyer.CityGlobal){

                }else if(!$scope.To.Buyer.Area  || !$scope.To.Buyer.Area.district_id || !$scope.To.Buyer.Area.city_id){
                    return false;
                }

                


                $scope.waiting = true;

                if (calculateTimeout) {
                    $timeout.cancel(calculateTimeout);
                }

                var data = BuildData(true);
    			if (typeof data == 'string') {
    		            return toaster.pop('warning', tran('toaster_ss_nitifi'), data);
    	        }
                calculateTimeout = $timeout(function() {
                    
                    if ($scope.Config.Type == 1 && (!data['Order']['Collect'] || data['Order']['Collect'] == "")) {
                        
                        $timeout(function (){
                            $('#money_collect').focus().addClass('ng-invalid').addClass('ng-dirty');
                        }, 100);
                        
                        //$timeout.cancel(calculateTimeout);
                        //return $scope.waiting = false; 
                    }
                    
                    if (!data) {
                        $timeout.cancel(calculateTimeout);
                        return $scope.waiting = false; 

                    };

                    

                    

                    var currentDate = new Date();
                    if (data['Config'] && data['Config']['Service'] == 2 && (data['From']['City'] == data['To']['City']) && currentDate.getHours() > 10) {
                        if (isNoiThanh(data['To']['Province'])) {
                            openDialogOverPickupTime();
                        }
                    }
                    

                    
                    Order.CalculateGlobal(data).then(function (result) {
                        $scope.waiting = false; 
                        if(!result.data.error){
                            $scope.fee_detail       = result.data.data;

                            // Cấu hình  thu phí CoD

                            /*if($scope.config_fee && $scope.config_fee.cod_fee == 1){ // người mua trả phí
                                $scope.fee_detail.seller.discount -= +$scope.fee_detail.vas.cod;
                            }*/

                            if(!$scope.fee_detail.courier.me){
                                $scope.fee_detail.courier.me        = [];
                                $scope.calculateInfo.selected_courier = $scope.fee_detail.courier.system[0];
                            }else {
                                $scope.calculateInfo.selected_courier = $scope.fee_detail.courier.me[0];
                            }

                            if(!$scope.fee_detail.courier.system){
                                $scope.fee_detail.courier.system    = [];
                            }


                            $scope.list_courier_detail  = PhpJs.array_merge_recursive($scope.fee_detail.courier.me, $scope.fee_detail.courier.system);

                            if((parseInt($scope.Config.Type) == 2 || parseInt($scope.Config.Type) == 4) && !$scope.From.Inventory.warehouse_code){
                                $scope.fee_detail.collect    += $scope.calculateInfo.selected_courier.money_pickup;
                            }


                        }else {
                            HandlerError(result.data);
                            //toaster.pop('warning', 'Thông báo', result.data.message);
                            $scope.fee_detail   = {};
                        }
                    })
                }, 2000);
                
                
            }else {
                $scope.waiting = false;
            }
        };
    };


    $scope.$watch('calculateInfo.selected_courier', function (Value, OldValue){
        if( (parseInt($scope.Config.Type) == 2 || parseInt($scope.Config.Type) == 4) && !$scope.From.Inventory.warehouse_code){

            if(Value != undefined && Value.courier_id != undefined){
                var oldv    = 0;
                var newv    = 1 * Value.money_pickup.toString().replace(/,/gi,"");

                if(OldValue.money_pickup != undefined){
                    oldv    = 1 * OldValue.money_pickup.toString().replace(/,/gi,"");
                    if(oldv > 0){
                        $scope.fee_detail.collect    -= oldv;
                    }
                    $scope.fee_detail.collect    += newv;
                }
            }
        }
    });


    $scope.$watch('From.Inventory.Area.district_id', function (newVal, oldVal){
        if ( newVal !== null && newVal !== undefined && newVal !== "" && parseInt(newVal) > 0 ) {
            Location.ward(newVal,'all').then(function (wards) {
                $scope.list_ward = wards.data.data;
            });   
        }
    })

    $scope.list_country = [];
    
    Location.country().then(function (resp){
        //$scope.list_country = resp.data.data;
    	var china 		= {}
    	
    	
        angular.forEach(resp.data.data, function (value){
        	if(value && parseInt(value.id) == 44){
        		china = value;
        		$scope.arrChina.country.push(value)
        	}
        	if(value && parseInt(value.id) == 246){
        		$scope.arrChina.country.push(value)
        	}
            if(value && [44,246].indexOf(value.id) <= -1){
            	$scope.list_country.push(value)
            }	
        });
    	china.country_name 	= "China"
    	china.id 			= -1;
        $scope.list_country.push(china)
        $scope.list_country.forEach(function (value){
        	//console.log(value)
            if(value.id == 237){
                $scope.To.Buyer.Country = value;
            }
        })
    })





    $scope.$watch('To.Buyer.Area.district_id', function (newVal, OldVal){
        if ($scope.To.Buyer && parseInt($scope.To.Buyer.ward_id) > 0) {
            $scope.To.Buyer.ward_id    = 0;
        }
        if (newVal && newVal > 0 && newVal != OldVal) {
            $scope.list_ward_by_district = [];
            $scope.list_ward_by_district = AppStorage.getWardByDistrict(newVal);
        }
    });


    // Validate phone 2
    $scope.phone2IsWrong = false;
    // $scope.$watch('To.Buyer.Phone2', function (newVal, OldVal){
    //     $scope.phone2IsWrong = false;
    //     if (newVal && newVal != OldVal && newVal.length > 5) {
    //         if(!isTelephoneNumber(newVal)){
    //             var result = chotot.validators.phone(newVal, true);
    //             if(result !== newVal){
    //                  $scope.phone2IsWrong = true;
    //                  return false;
    //             }
    //         }
    //     }
    // });


    $scope.$watch('To.Buyer._Phone', function (newVal, oldVal){
        $scope.To.Buyer.Phone     = "";
        $scope.To.Buyer.PhoneCode = "";

        if($scope.To.Buyer.PhoneCtrl){
            var rawPhone        = $scope.To.Buyer.PhoneCtrl.getNumber(intlTelInputUtils.numberFormat.NATIONAL);
            var selectedCountry = $scope.To.Buyer.PhoneCtrl.getSelectedCountryData();

            //console.log('To.Buyer.Phone Changed', newVal, $scope.To.Buyer.PhoneCtrl.getExtension(), rawPhone.replace(/\D/g,""), selectedCountry);
            
        
            $scope.To.Buyer.Phone     = rawPhone;

            if(selectedCountry){
                $scope.To.Buyer.PhoneCode = selectedCountry.dialCode;
            }

            if(selectedCountry && selectedCountry.iso2 && $scope.list_country.length > 0){
                var iso2 = selectedCountry.iso2.toUpperCase();
                angular.forEach($scope.list_country, function (value){
                    if(iso2 == value.country_code){
                        $scope.To.Buyer.Country = value; 
                    }
                })
                
            }

        }
    })


    $scope.$watch('To.Buyer._Phone2', function (newVal, oldVal){
        $scope.To.Buyer.Phone2     = "";

        if($scope.To.Buyer.Phone2Ctrl){
            // console.log('To.Buyer.Phone Changed', newVal, $scope.To.Buyer.PhoneCtrl.getExtension());
            var rawPhone        = $scope.To.Buyer.Phone2Ctrl.getNumber(intlTelInputUtils.numberFormat.NATIONAL);
            var selectedCountry = $scope.To.Buyer.Phone2Ctrl.getSelectedCountryData();

            $scope.To.Buyer.Phone2     = rawPhone;

        }
    })

    $scope.$watch('Config.Type', function (newVal, oldVal){
        if (newVal) {
            $scope.fee_detail = {};
            $scope.calculateInfo.selected_courier = "";
            $scope.money_collect = 0;
        }
    })





    var openDialog = function (title, message, callback){
        bootbox.dialog({
            message: message,
            title: title,
            buttons: {
                main: {
                    label: "Đóng",
                    className: "btn-default", 
                    callback: callback || function (){}
                },
            }
        });
    }

    var openDialogCreateSuccess = function (tracking_code, callback){
    	 bootbox.dialog({
             //message: ""+tran('ORDERC_Donhang')+" <strong>"+tracking_code+"</strong> đã tạo thành công, quý khách đừng quên duyệt đơn hàng này khi đã sẵn sàng để báo hãng vận chuyển tới lấy hàng. <br/><br/>Chúc quý khách một ngày tốt lành!",
         	message: ""+tran('ORDERC_Donhang')+" <strong>"+tracking_code+"</strong> "+tran('ORDERC_TaoDonDaTaoThanhCong')+"",
             title: tran('ORDERC_TaoDonHangThanhCong'),//"Tạo đơn hàng thành công",
             buttons: {
                 main: {
                     label: tran('ORDERC_TaoDonKhac'),//"Tạo đơn khác",
                     className: "btn-info", 
                     callback:  function (){
                         $scope.addTabs()
                     }
                 },
             }
         });
    }

    var openDialogAcceptSuccess = function (tracking_code, courier_id, callback){ 
        var currentDate = new Date();
        var timePickup  = "hôm nay";
        var moment_time = moment().minute(0).second(0);
        if (currentDate.getTime() > moment().hour(14)) {
            timePickup  = "ngày mai";
        };

        bootbox.dialog({
            //message: "Đơn hàng <strong>"+tracking_code+"</strong> đã duyệt thành công. Đơn hàng này sẽ được đối tác vận chuyển <strong>" + $scope.courier[courier_id]+"</strong> qua lấy hàng trong " + timePickup + ". Quý khách đừng quên thường xuyên theo dõi đơn hàng này trên hệ thống của chúng tôi để giao hàng tốt nhất.<br/><br/>Chúc quý khách một ngày tốt lành!",
        	message: ""+tran('ORDERC_Donhang')+" <strong>"+tracking_code+"</strong> "+tran('ORDERC_TaoDonDaDuyetThanhCong')+"  " + timePickup + ". "+tran('ORDERC_QuyKhachThuongXuyenTheoDoi')+"",
        	title: tran('ORDERC_DuyetDonHangThanhCong'),//"Duyệt đơn hàng thành công!",
            buttons: {
                print: {
                    label: tran('ORDERC_PrintPhieuGui'),//"In phiếu gửi vận đơn",
                    className: "bg-orange", 
                    callback: function (){
                        window.open('http://seller.shipchung.vn/#/print_hvc?code=' + tracking_code, '_blank')
                    }
                },
                main: {
                    label: tran('ORDERC_TaoDonKhac'),//"Tạo đơn khác",
                    className: "btn-info", 
                    callback: function (){
                        $scope.addTabs();
                    }
                },

            }
        });
    }


    var openDialogCollectNotEnought = function (tracking_code, callback){ 
    	bootbox.dialog({
            //message: "Số dư khả dụng của quý khách không đủ để duyệt đơn hàng này! Quý khách vui lòng nạp thêm tiền vào trong tài khoản để duyệt đơn hàng. <br/><br/>Xem hướng dẫn nạp tiền <a href='https://www.shipchung.vn/ho-tro/nap-tien-vao-tai-khoan/' target='_blank'><span class='text-info'>tại đây</span></a>.",
            message: tran('ORDERC_SoDuCuaQuyKhachKhongDu'),
        	title: tran('ORDERC_SoDuCuaBanKhongDu'),//"Số dư của bạn không đủ để duyệt đơn hàng",
            buttons: {
                print: {
                    label: tran('OBG_LuuLaiVaDuyetSau'),//"Lưu đơn duyệt sau",
                    className: "bg-orange", 
                    callback: function (){
                        $scope.CreateOrder(false);
                    }
                },
                main: {
                    label: tran('Cashin_Naptien'),//"Nạp tiền ngay",
                    className: "btn-info", 
                    callback: function (){
                        $scope.cash_in('');
                    }
                },

            }
        });
    }

    var openDialogOverPickupTimeRejected = false;

    var openDialogOverPickupTime = function (){

        if (openDialogOverPickupTimeRejected || parseInt($localStorage['reject_popup_pickup_time'] >= 3)) {
            return false;
        }
        openDialogOverPickupTimeRejected = true;

        bootbox.dialog({
            //message: "Đã qua khung giờ lấy hàng (Trước 10:00 sáng) hỗ trợ giao hàng trong ngày, do đó bạn nên chuyển qua dịch vụ giao hàng tiết kiệm để giảm chi phí.<br/><br/>Nếu đơn hàng được duyệt trước 2h sẽ lấy hàng trong ngày, sau thời gian này đơn hàng sẽ được lấy vào ngày hôm sau.",
              message:tran('ORDERC_DaQuaKhungGioLayHang'),
        	title: tran('ORDERC_detietkiemchiphi'),//"Bạn nên chuyển sang dịch vụ chuyển phát tiết kiệm",
            buttons: {
                print: {
                    label: tran('ORDERC_GiuNguyen'),//"Giữ nguyên",
                    className: "bg-default", 
                    callback: function (){
                        
                        $localStorage['reject_popup_pickup_time'] = parseInt($localStorage['reject_popup_pickup_time']) + 1;
                    }
                },
                main: {
                    label: tran('ORDERC_DoiQuaDichVuTietKiem'),// "Đổi qua dịch vụ tiết kiệm",
                    className: "btn-info", 
                    callback: function (){
                        $scope.Config.Service = "1";
                    }
                },
            }
        });
    }

    var isNoiThanh = function (district_id){
        var flag = false;
        angular.forEach($rootScope.list_district_by_location, function (value){
            if (value == district_id) {
                flag = true;
            };
        });
        return flag;
    }



  //("Shipchung chưa hỗ trợ phát hàng khu vực này", "Rất xin lỗi quý khách, chúng tôi chưa hỗ trợ phát hàng tới khu vực này do chưa có đối tác giao hàng đảm bảo chất lượng dịch vụ tốt và ổn định hoặc khu vực này có tỷ lệ chuyển hoàn quá cao.");
    //openDialog("Shipchung chưa hỗ trợ lấy hàng khu vực này", "Rất xin lỗi quý khách, chúng tôi chưa hỗ trợ lấy hàng tại khu vực của bạn đang kinh doanh.");
    //openDialog("Tạo/duyệt đơn hàng không thành công", "Rất xin lỗi quý khách! Đơn hàng này tạo/duyệt chưa thành công vì vấn đề kỹ thuật. <br/><br/>Quý khách xin vui lòng quay lại trong ít phút tới hoặc liên hệ tổng đài CSKH của chúng tôi để được hỗ trợ (Hotline: 1900-636-060).");
    var HandlerError = function (data){
        switch(data.code){
            case "UNSUPPORT_DELIVERY": 
            	openDialog(tran('ORDERC_ShipChungChuaHoTroKhuVucNay'), tran('ORDERC_RatXinLoiQuyKhachC'));
            break;
            case "UNSUPPORT_PICKUP": 
            	openDialog(tran('ORDERC_ShipChungChuaHoTroKhuVucNay'), tran('ORDERC_ChuaHoTroLayHang'));
            break;
            case "FAIL": 
            	openDialog(tran('ORDERC_TaoDuyetDonChuaThanhCong'), tran('ORDERC_TaoDuyetDonChuaThanhCongDoKyThuat'));
            break;
            case 'NOT_ENOUGH_MONEY':
                 openDialogCollectNotEnought();
            break;
        }
        return toaster.pop('warning', tran('toaster_ss_nitifi'), data.error_message || data.message)
    }





    $scope.trustHtml = function (html){
        return $sce.trustAsHtml(html);
    }

    $scope.createWaiting = false;
    
    $scope.CreateOrder = function (isAutoAccept){
        var data = BuildData(false);

        if (typeof data == 'string') {
            return toaster.pop('warning', tran('toaster_ss_nitifi'), data);
        }
        isAutoAccept = isAutoAccept || false;

        data['Config']['AutoAccept'] = isAutoAccept ? 1 : 0;
        if($rootScope.ViewHomeCurrency != $rootScope.exchangeRate) {
        	data['Config']['exchangeRate'] = $rootScope.exchangeRate;
        }
        
        $scope.createWaiting = true;
        Order.CreateV2Global(data).then(function (result) {
            $scope.createWaiting = false;
            if(!result.data.error){
                
                $scope.NewOrder    = {
                  'TrackingCode'    :   result.data.data.TrackingCode,
                  'CourierId'       :   result.data.data.CourierId,
                  'MoneyCollect'    :   result.data.data.MoneyCollect,
                  'status'          :   isAutoAccept ? 21 : 20
                };

                if($rootScope.userInfo.first_order_time == 0){
                    $rootScope.userInfo.first_order_time = 1;
                }
                
                if (isAutoAccept) {
                    openDialogAcceptSuccess($scope.NewOrder.TrackingCode, $scope.NewOrder.CourierId);
                    // Intercom   
                    try {
                    	if($rootScope.userInfo.first_order_time == 1){
                    		var metadata = {
                         		   create_by	: 	$rootScope.userInfo.email 	? $rootScope.userInfo.email 	: "",
                         		   order_number : 	$scope.NewOrder.TrackingCode ? $scope.NewOrder.TrackingCode : "",   
             	                   active		:	"Create and approve order",
             	                   links		:   $rootScope.userInfo.fulfillment ? "order/create-global-bm" :"order/create-global",
             	                  first_order_approved:1
                 			};
                    		window.Intercom('update', {first_order_approved :1});
                    	}else{
                    		var metadata = {
                         		   create_by	: 	$rootScope.userInfo.email 	? $rootScope.userInfo.email 	: "",
                         		   order_number : 	$scope.NewOrder.TrackingCode ? $scope.NewOrder.TrackingCode : "",   
             	                   active		:	"Create and approve order",
             	                   links		:   "order/create-v2",
                 			};
                    	}
            			Intercom('trackEvent', 'Accept Order', metadata);
                    }catch(err) {
        			    console.log(err)
        			}
                    // Intercom
                }else {
                    openDialogCreateSuccess($scope.NewOrder.TrackingCode);
                 // Intercom   
                    try {
                    	if($rootScope.userInfo.first_order_time == 1){
                    		var metadata = {
                         		   create_by	: 	$rootScope.userInfo.email 	? $rootScope.userInfo.email 	: "",
                         		   order_number : 	$scope.NewOrder.TrackingCode ? $scope.NewOrder.TrackingCode : "",   
             	                   active		:	"Create order",
             	                   links		:    $rootScope.userInfo.fulfillment ? "order/create-global-bm" :"order/create-global",
             	                   first_order_created:1
                 			};
                    		window.Intercom('update', {first_order_created :1});
                    	}else{
                    		var metadata = {
                         		   create_by	: 	$rootScope.userInfo.email 	? $rootScope.userInfo.email 	: "",
                         		   order_number : 	$scope.NewOrder.TrackingCode ? $scope.NewOrder.TrackingCode : "",   
             	                   active		:	"Create order",
             	                   links		:   "order/create-global-bm",
                 			};
                    	}
                    	
            			Intercom('trackEvent', 'Create order', metadata);
                    }catch(err) {
        			    console.log(err)
        			}
                    // Intercom
                }
                
                if(result.data.code == 'OUT_OF_STOCK'){
                    return toaster.pop('warning', tran('toaster_ss_nitifi'), "Sản phẩm đã hết hàng trong kho !");
                }

                $localStorage['last_config_type_select'] = $scope.Config.Type;
                
            }else {

                HandlerError(result.data);
            }
        })
    }




    $scope.addInventory = function (){
        $scope.From.Inventory = {};
        setTimeout(function (){
            $("#inventory_phone").focus();
        }, 200);
    }

    $scope.saveInventoryLoading = false;
    $scope.saveInventory = function (item){
        if (!item.Area.city_id) {
            //return toaster.pop('warning', 'Thông báo', 'Bạn chưa chọn khu vực');
            return toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_BanChuaChonKHuVuc'));
        }
        if (!get_phone(item.phone) && get_phone(item.phone) == "") {
           // return toaster.pop('warning', 'Thông báo', 'Bạn chưa nhập số điện thoại liên hệ ');
            return toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_BanChuaNhapSDT'));
        }

        if (!item.ward_id) {
            //return toaster.pop('warning', 'Thông báo', 'Bạn chưa chọn phường/xã');
            return toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_BanChuaChonPhuongXa'));
        };

        var data = {
            name        : "Kho - " + item.Area.full_address,
            user_name   : item.user_name,
            phone       : get_phone(item.phone),
            address     : item.address,
            ward_id     : item.ward_id,
            province_id : item.Area.district_id,
            city_id     : item.Area.city_id,
            active      : 1,
        }
        $scope.saveInventoryLoading = true;
        Inventory.create(data).then(function (result) {
            $scope.saveInventoryLoading = false;
            if (result.data.error) {
                //return toaster.pop('warning', 'Thông báo', 'Tạo kho không thành công, quý khách vui lòng thử lại sau hoặc liên hệ bộ phân CSKH của Shipchung để được hỗ trợ !');
            	return toaster.pop('warning',tran('toaster_ss_nitifi'), tran('Toaster_TaoKhoKhongThanhCong'));
            }
            $scope.loadInventory($rootScope.pos, null);
        })

    }

    

    $scope.cancelAddInventory = function (){
        $scope.loadInventory($rootScope.pos, null);
    }


    var boxSizeChange = function (newVal, OldVal){
        if (newVal) {
            if (parseInt($scope.Product.BoxSize.L) > 0 && parseInt($scope.Product.BoxSize.H) > 0 && parseInt($scope.Product.BoxSize.W) > 0) {
                FeeChange(newVal, OldVal);
            }
        };
    }

    $scope.list_city_global = [];


    $scope.loadCityGlobal = function (country_id, q){
//    	var check = 0
//    	angular.forEach(list_city_global, function (value){
//			if(q && value.city_name && q.toString() == value.city_name.toString()){
//				check = 1
//			}
//        })
        	$scope.list_city_global = [];
            if (country_id == -1){
            	Location.city_global(44, q).then(function (resp){
            		$scope.arrChina.china_1 = resp.data.data;
                    $scope.list_city_global = resp.data.data;
                })
                Location.city_global(246, q).then(function (resp){
                	if(resp.data && resp.data.data){;
                		angular.forEach(resp.data.data, function (value){
                			$scope.list_city_global.push(value);
                        })
                	}
                })
                return $scope.list_city_global;
            }else{
            	return Location.city_global(country_id, q).then(function (resp){
                    $scope.list_city_global = resp.data.data;
                })
            }
    }

    $scope.last_payment_type     = $scope.Config.Type;
    $scope.disabled_payment_type = false;
    $scope.disabled_service      = false;

    $scope.$watch('To.Buyer.Country', function (newVal){
        if(newVal && (newVal.id > 0 || newVal.id == -1)){
            if(newVal.id !== 237){
                $scope.To.Buyer.CityGlobal   = undefined;
                
                $scope.disabled_payment_type = true;
                $scope.disabled_service      = true;
                $scope.Config.Type           = 5;
                $scope.Config.Service        = 8;

            }else {
                $scope.disabled_payment_type = false;
                $scope.disabled_service      = false;
                $scope.Config.Service        = 2;
                $scope.Config.Type           = $scope.last_payment_type;
            }
            $scope.loadCityGlobal(newVal.id, "");
        }
    })

    
    $scope.$watch('To.Buyer.CityGlobal', FeeChange);

    $scope.$watch('To.Buyer.Area', FeeChange);
    $scope.$watch('To.Buyer.Zipcode', FeeChange);
    $scope.$watch('Product.Weight', FeeChange);
    $scope.$watch('Product.Price', FeeChange);
    $scope.$watch('Product.MoneyCollect', FeeChange);
    $scope.$watch('Product.BoxSize.W', boxSizeChange);
    $scope.$watch('Product.BoxSize.H', boxSizeChange);
    $scope.$watch('Product.BoxSize.L', boxSizeChange);
    $scope.$watch('From.Inventory', FeeChange);
    $scope.$watch('Config.Service', FeeChange);
    $scope.$watch('Config.Protected', FeeChange);
    $scope.$watch('Config.Type', FeeChange);


    $scope.list_products = [];
    $scope.$watch('From.Inventory', function (){
        $scope._boxme.Items             = [];
        $scope._boxme.TotalItemAmount   = 0;
        $scope._boxme.TotalItemQuantity = 0;
        $scope._boxme.TotalItemWeight   = 0;
        $scope._boxme.ItemsName         = "";

        if($scope.From.Inventory && $scope.From.Inventory.warehouse_code && $scope.From.Inventory.warehouse_code.length > 0){
            $scope.list_products            = [];
            
            WarehouseRepository.GetProducts($scope.From.Inventory.warehouse_code, true).then(function (data){
                if(data.total_items && data.total_items > 0){
                    $scope.list_products = data._embedded.product;
                }
                
            })
        }
    });

    $scope._boxme.TotalItemAmount   = 0;
    $scope._boxme.TotalItemQuantity = 0;
    $scope._boxme.TotalItemWeight   = 0;
    $scope._boxme.ItemsName         = "";
    
    


    $scope._boxme.ItemChange = function (){
        if($scope._boxme.Items && $scope._boxme.Items.length >0){

            $scope._boxme.TotalItemAmount   = 0;
            $scope._boxme.TotalItemQuantity = 0;
            $scope._boxme.TotalItemWeight   = 0;
            $scope._boxme.ItemsName         = "";
            

            var names = [];
            angular.forEach($scope._boxme.Items, function (value){
                names.push(value.ProductName);
                $scope._boxme.TotalItemAmount   += value.PriceItem * (value.Quantity * 1)
                $scope._boxme.TotalItemQuantity += parseInt(value.Quantity);
                $scope._boxme.TotalItemWeight   += parseInt(value.WeightItem) * parseInt(value.Quantity);
    
            })

            $scope._boxme.ItemsName         = names.join(',');
            
            FeeChange({'a':1}, {'b':1});
        }else {
            $scope._boxme.TotalItemAmount   = 0;
            $scope._boxme.TotalItemQuantity = 0;
            $scope._boxme.TotalItemWeight   = 0;
            $scope._boxme.ItemsName         = "";
        }
    }

    $scope._boxme.remoteProductItem = function (item){
        if(item){
            $scope._boxme.Items.splice($scope._boxme.Items.indexOf(item), 1)
            $scope._boxme.ItemChange();
        }
    }
    $scope.AddItem = function (item){
        item.Quantity   = 1;
        item            = angular.copy(item);
        $scope._boxme.Items.push(item);
        $scope._boxme.ItemChange()
    }
    


    
    $scope.$watch('box_size_check', function (newVal, Old){
        $scope.Product.BoxSize  = '';
        if (newVal !== undefined) {
            if (newVal == false && get_number($scope.Product.Weight) > 0) {
                FeeChange(newVal, Old);
            }
        }
    })


    // Get location ;

    navigator.geolocation.getCurrentPosition(function (pos){
        var crd = pos.coords;
        $rootScope.pos = {
            lat: crd.latitude,
            lng: crd.longitude
        };
        $scope.loadInventory($rootScope.pos, null);

    }, function (){
        $scope.loadInventory($rootScope.pos, null);
    }, {});
    
    $scope.loadInventory($rootScope.pos, null);

}]);