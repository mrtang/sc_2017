(function (app) {
    app
        .constant('Api_Path', {
            Seller          :   ApiSeller,
            Base            :   ApiPath,
            User            :   ApiPath+'user/',
            UserInfo        :   ApiPath+'user-info/',
            ApiKey          :   ApiPath+'api-key/',
            Bussiness       :   ApiPath+'bussiness-info/',
            FeeConfig       :   ApiPath+'fee-config/',
            Inventory       :   ApiPath+'inventory-config/',
            CashIn          :   ApiPath+'user-cash-in/',
            Order           :   ApiPath+'order',
            PublicOrder     :   ApiPath+'public-order',
            OrderStatus     :   ApiPath+'order-status',
            ChangeOrder     :   ApiPath+'order-change',
            OrderProcess    :   ApiPath+'order-process/',
            OrderLading     :   ApiRest+'lading/',
            OrderVerify     :   ApiPath+'order-verify',
            WarehouseVerify :   ApiPath+'warehouse-verify',
            
            OrderInvoice    :   ApiPath+'order-invoice',
            Transaction     :   ApiPath+'transaction',
            Search          :   'http://s.shipchung.vn/address/_search?q=name:',
            SearchProduct   :   'http://s.shipchung.vn/jdbc/_search?type=myitem&q=name:',
            SearchBuyer     :   'http://s.shipchung.vn/jdbc/_search?type=mybuyer&q=fullname:',
            Barcode         :   ApiPath+'order/barcode/',
            Upload          :   ApiPath+'upload/'
        })
        .constant('LogoutRoleNow', {
        	email:""	
        })
        .constant('Site_Config', {
        	domain:	BOXME_DOMAIN ? BOXME_DOMAIN :"dev.boxme.vn"
        })
        //end Config status
        .constant('Config_Status', {
            Priority :  [1, 2, 3, 4, 5],
            Ticket :[	{ code : 'ALL'                      , content : 'Tất cả'},
	                    { code : 'NEW_ISSUE'                , content : 'Mới tạo (chờ tiếp nhận)'},
	                    { code : 'ASSIGNED'                 , content : 'Đã tiếp nhận (Chờ xử lý)'},
	                    { code : 'PENDING_FOR_CUSTOMER'     , content : 'Đã trả lời (Chờ phản hồi)'},
	                    { code : 'CUSTOMER_REPLY'           , content : 'Đã phản hồi(Chờ trả lời)'},
	                    { code : 'PROCESSED'                , content : 'Đã xử lý (Chờ đóng)'},
	                    { code : 'CLOSED'                   , content : 'Đã đóng'}
                    ],
            Ticket_en :[	{ code : 'ALL'                      , content : 'All'},
	                    { code : 'NEW_ISSUE'                , content : 'Waiting assign'},
	                    { code : 'ASSIGNED'                 , content : 'Assigned'},
	                    { code : 'PENDING_FOR_CUSTOMER'     , content : 'Pending'},
	                    { code : 'CUSTOMER_REPLY'           , content : 'Processing'},
	                    { code : 'PROCESSED'                , content : 'Processed'},
	                    { code : 'CLOSED'                   , content : 'Closed'}
                    ],        
            Ticket_thailand :[	{ code : 'ALL'              , content : 'ทั้งหมด'},
	                    { code : 'NEW_ISSUE'                , content : 'คำขอใหม่'},
	                    { code : 'ASSIGNED'                 , content : 'ได้รับแล้ว'},
	                    { code : 'PENDING_FOR_CUSTOMER'     , content : 'รอยืนยันกลับ'},
	                    { code : 'CUSTOMER_REPLY'           , content : 'รอคำตอบ'},
	                    { code : 'PROCESSED'                , content : 'ปีดแล้ว'},
	                    { code : 'CLOSED'                   , content : 'ปีดแล้ว'}
                    ], 
            ticket_btn_en : {'NEW_ISSUE'     : {
                        name      : 'New',
                        'bg'        : 'bg-info'
                    },
                    'ASSIGNED'      : {
                        name      : 'Assigned',
                        'bg'        : 'bg-primary'    
                    },
                    'PENDING_FOR_CUSTOMER'  : {
                        name      : 'Pending',
                        'bg'        : 'bg-warning'    
                    },

                    'CUSTOMER_REPLY'  : {
                        name      : 'Processing',
                        'bg'        : 'bg-primary'    
                    },
                    'PROCESSED'     : {
                        name      : 'Processed',
                        'bg'        : 'bg-success'
                    },
                    'CLOSED'        : {
                        name      : 'Closed',
                        'bg'        : 'bg-light dker'
                    }
                },        
            ticket_btn_thailand :        {
                        'NEW_ISSUE': {
                            name : 'คำขอใหม่'
                        },
                        'ASSIGNED' : {
                            name : 'ได้รับแล้ว'
                        },
                        'PENDING_FOR_CUSTOMER': {
                            name: 'รอยืนยันกลับ'
                        },

                        'CUSTOMER_REPLY': {
                            name: 'รอคำตอบ'  
                        },
                        'PROCESSED' : {
                            name : 'ได้แก้ไขแล้ว'
                        },
                        'CLOSED' : {
                            name: 'ปีดแล้ว'
                        }
                    },
            ticket_btn : {'NEW_ISSUE'     : {
                                name      : 'Yêu cầu mới',
                                'bg'        : 'bg-info'
                            },
                            'ASSIGNED'      : {
                                name      : 'Đã tiếp nhận',
                                'bg'        : 'bg-primary'    
                            },
                            'PENDING_FOR_CUSTOMER'  : {
                                name      : 'Chờ phản hồi',
                                'bg'        : 'bg-warning'    
                            },

                            'CUSTOMER_REPLY'  : {
                                name      : 'Chờ trả lời',
                                'bg'        : 'bg-primary'    
                            },
                            'PROCESSED'     : {
                                name      : 'Đã xử lý',
                                'bg'        : 'bg-success'
                            },
                            'CLOSED'        : {
                                name      : 'Đã đóng',
                                'bg'        : 'bg-light dker'
                            }
                        },
            order_color  :{	12   : 'bg-info',
                            13   : 'bg-info dker',
                            14   :'bg-warning',
                            15   :'bg-light dker',
                            16   :'bg-orange',
                            17   :'bg-orange',
                            18   :'bg-danger lt',
                            19   :'bg-success bg',
                            20   :'bg-primary lter',
                            21  :'bg-primary',
                            22  :'bg-danger'
                    	},
            ExcelOrder  : {	'ALL'	 : 'Tất cả',
                            'SUCCESS': 'Tạo thành công',
                            'FAIL'   : 'Tạo thất bại',
                            'CANCEL' : 'Đã hủy',
                            'USER_NOT_EXISTS'   	: 'Email không tồn tại',
                            'INVENTORY_NOT_EXISTS' 	: 'Bạn chưa cấu hình kho hàng'
                        },
            ExcelOrder_en  : {	'ALL'	 : 'ALL',
                            'SUCCESS': 'SUCCESS',
                            'FAIL'   : 'FAIL',
                            'CANCEL' : 'CANCEL',
                            'USER_NOT_EXISTS'   	: 'USER NOT EXISTS',
                            'INVENTORY_NOT_EXISTS' 	: 'INVENTORY NOT EXISTS'
                        },
            ExcelOrder_thailand  : {	'ALL'	 : 'ทั้งหมด',
                            'SUCCESS': 'ดาวน์โหลดสำเร็จ!',
                            'FAIL'   : 'ข้อผิดพลาดในการเชื่อมต่อ',
                            'CANCEL' : 'ยกเลิก',
                            'USER_NOT_EXISTS'   	: 'ไม่พบผลลัพธ์จากคําค้นของคุณ',
                            'INVENTORY_NOT_EXISTS' 	: 'คุณยังไม่ได้กำหนดรุปแบบคลังสินค้า'
                        },                        
            CourierPrefix : {	1: 'vtp',
			                    2: 'vnp',
			                    3: 'ghn',
			                    4: 'gao',
			                    5: 'net',
			                    6: 'gtk',
			                    7: 'sc',
			                    8: 'ems',
			                    9: 'gts',
			                    10: 'ctp'
		                }
        })
        //end Config status
        .constant('Config_Accounting', {
            list_bank       :  [{code:'VCB'  ,name:'Ngân hàng TMCP Ngoại Thương Việt Nam(VIETCOMBANK)'},
                                {code:'DAB' ,name:'Ngân hàng Đông Á (DAB)'},
                                {code:'TCB' ,name:'Ngân hàng Kỹ Thương (TECHCOMBANK)'},
                                {code:'VIB' ,name:'Ngân hàng Quốc tế(VIB)'},
                                {code:'MBB'  ,name:'Ngân Hàng Quân Đội (MB BANK)'},
                                {code:'ICB' ,name:'Ngân hàng Công Thương Việt Nam(VIETINBANK)'},
                                {code:'HDB' ,name:'Ngân hàng Phát triển Nhà TPHCM (HD BANK)'},
                                {code:'EXB' ,name:'Ngân hàng Xuất Nhập Khẩu(EXIMBANK)'},
                                {code:'ACB' ,name:'Ngân hàng Á Châu (ACB)'},
                                {code:'SHB' ,name:'Ngân hàng Sài Gòn-Hà Nội (SHB)'},
                                {code:'PGB' ,name:'Ngân hàng Xăng dầu Petrolimex (PGB)'},
                                {code:'TPB' ,name:'Ngân hàng Tiền Phong (TPB)'},
                                {code:'SCB' ,name:'Ngân hàng Sài Gòn Thương tín (SCB)'},
                                {code:'MSB' ,name:'Ngân hàng Hàng Hải (MSB)'},
                                {code:'AGB' ,name:'Ngân hàng Nông nghiệp & Phát triển nông thôn (AGB)'},
                                {code:'BIDV',name:'Ngân hàng Đầu tư & Phát triển Việt Nam (BIDV)'}
                                ],
            bank            : {	'VCB'   : 'Ngân hàng TMCP Ngoại Thương Việt Nam(VIETCOMBANK)',
                                'DAB'   : 'Ngân hàng Đông Á',
                                'TCB'   : 'Ngân hàng Kỹ Thương (TECHCOMBANK)',
                                'VIB'   : 'Ngân hàng Quốc tế(VIB)',
                                'MBB'    : 'Ngân Hàng Quân Đội (MB BANK)',
                                'ICB'   : 'Ngân hàng Công Thương Việt Nam(VIETINBANK)',
                                'HDB'   : 'Ngân hàng Phát triển Nhà TPHCM (HD BANK)',
                                'EXB'   : 'Ngân hàng Xuất Nhập Khẩu(EXIMBANK)',
                                'ACB'   : 'Ngân hàng Á Châu (ACB)',
                                'SHB'   : 'Ngân hàng Sài Gòn-Hà Nội',
                                'PGB'   : 'Ngân hàng Xăng dầu Petrolimex',
                                'TPB'   : 'Ngân hàng Tiền Phong',
                                'SCB'   : 'Ngân hàng Sài Gòn Thương tín',
                                'MSB'   : 'Ngân hàng Hàng Hải',
                                'AGB'   : 'Ngân hàng Nông nghiệp & Phát triển nông thôn (AGB)',
                                'BIDV'  : 'Ngân hàng Đầu tư & Phát triển Việt Nam (BIDV)'
                            },
            vimo_bank   :   [	{code :'ABB',    name        : 'ABBank - Ngân hàng TMCP An Bình'}, // ok
                                {code :'ACB',    name        : 'ACB - Ngân hàng TMCP Á Châu'}, // ok
                                {code :'BAB',    name        : 'BacA Bank - Ngân hàng TMCP Bắc Á'},// ok
                                {code : 'BVB',   name       : 'Baoviet Bank - Ngân hàng TMCP Bảo Việt'}, // ok
                                {code : 'GAB',   name       : 'DaiA Bank - Ngân hàng TMCP Đại Á'}, // ok  : old DAB
                                {code : 'EXB',   name       : 'Eximbank - Ngân hàng TMCP XNK Việt Nam'}, //ok 
                                {code : 'GPB',   name       : 'GPBank - Ngân hàng TMCP Dầu khí Toàn Cầu'}, //ok 
                                {code : 'HDB',   name       : 'HD Bank - Ngân hàng Phát triển Nhà TPHCM'}, //ok
                                {code : 'LVB',   name        : 'Lien Viet Post Bank - Ngân hàng Bưu Điện Liên Việt'}, // ok : old VLPB
                                {code : 'MBB',   name       : 'MB Bank - Ngân hàng TMCP Quân Đội'}, //ok 
                                {code : 'MHB',   name       : 'MHB - Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long'}, //ok
                                {code : 'NVB',   name       : 'Navibank - Ngân hàng TMCP Nam Việt'}, //ok
                                {code : 'OJB',   name        : 'OceanBank - Ngân hàng TMCP Đại Dương'}, // ok old : OCEB
                                {code : 'SCB',   name       : 'Sacombank - Ngân hàng TMCP Sài Gòn thương tín'}, //ok 
                                {code : 'SHB',   name       : 'SHB - Ngân hàng TMCP Sài Gòn - Hà Nội'}, //ok
                                {code : 'TCB',   name       : 'Techcombank - Ngân hàng TMCP Kỹ Thương Việt Nam'}, //ok
                                {code : 'TPB',   name       : 'TienPhong Bank - Ngân hàng TMCP Tiên Phong'}, //ok
                                {code : 'VIB',   name       : 'VIB - Ngân hàng TMCP Quốc tế'}, //ok
                                {code : 'VAB',   name       : 'Viet A Bank - Ngân hàng TMCP Việt Á'}, //ok
                                {code : 'VCB',   name       : 'Vietcombank - Ngân hàng TMCP Ngoại Thương Việt Nam'}, //ok
                                {code : 'ICB',   name       : 'VietinBank - Ngân hàng TMCP Công Thương Việt Nam'}, //ok
                                {code : 'VPB',   name       : 'VPBank - Ngân hàng TMCP Việt Nam Thịnh Vượng'}, //ok,
                                {code : 'HLBVN', name     : 'Ngân hàng Hong Leong Việt Nam'},//add 
                                {code : 'OCB',   name       : 'Ngân hàng TMCP Phương Đông'}, 
                                {code:'AGB',    name: 'Ngân hàng NN&PT Nông thôn'},
                                {code:'ANZ',    name: 'Ngân hàng ANZ'},
                                {code:'BIDC',   name: 'Ngân hàng ĐT&PT Campuchia'},
                                {code:'CTB',    name: 'Ngân hàng CITY BANK'},
                                {code:'DAB',    name: 'Ngân hàng TMCP Đông Á'},
                                {code:'HSB',    name: 'Ngân hàng HSBC'},
                                {code:'IVB',    name: 'Ngân Hàng Indovina'},
                                {code:'KLB',    name: 'Ngân hàng TMCP Kiên Long'},
                                {code:'MDB',    name: 'Ngân hàng TMCP PT Mê Kông'},
                                {code:'MHB',    name: 'Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long'},
                                {code:'NCB',    name: 'Ngân hàng TMCP Quốc Dân'},
                                {code:'NHOFFLINE', name: 'Ngân hàng Offline'},
                                {code:'PGB',    name: 'Ngân hàng TMCP Xăng dầu Petrolimex'},
                                {code:'PNB',    name: 'Ngân hàng Phương Nam'},
                                {code:'PVB',    name: 'Ngân hàng TMCP Đại Chúng Việt Nam'},
                                {code:'SEA',    name: 'Ngân hàng TMCP Đông Nam Á'},
                                {code:'SGB',    name: 'Ngân hàng TMCP Sài Gòn Công Thương'},
                                {code:'SGCB',   name: 'Ngân hàng TMCP Sài Gòn'},
                                {code:'SHNB',   name: 'Ngân hàng SHINHAN'},
                                {code:'SMB',    name: 'Ngân hàng SUMITOMO-MITSUI'},
                                {code:'STCB',   name: 'Ngân hàng STANDARD CHARTERED'},
                                {code:'VB',     name: 'Ngân hàng Việt Nam Thương Tín'},
                                {code:'VCCB',   name: 'Ngân hàng TMCP Bản Việt'},
                                {code:'VDB',    name: 'Ngân hàng Phát triển Việt Nam'},
                                {code:'VIDPB',  name: 'Ngân hàng VID Public Bank'},
                                {code:'VNCB',   name: 'Ngân hàng TMCP Xây dựng Việt Nam'},
                                {code:'VRB',    name: 'Ngân hàng Liên doanh Việt - Nga'},
                                {code:'VSB',    name: 'Ngân Hàng Liên Doanh Việt Thái'},
                                {code : 'NONE',  name     : 'Ngân hàng khác (áp dụng với thẻ visa)'} ,//add ,
                                
                            ] ,
                            vimo   :   [
                                {'ABB'       : 'ABBank - Ngân hàng TMCP An Bình'}, // ok
                                {'ACB'       : 'ACB - Ngân hàng TMCP Á Châu'}, // ok
                                {'BAB'       : 'BacA Bank - Ngân hàng TMCP Bắc Á'},// ok
                                {'BVB'       : 'Baoviet Bank - Ngân hàng TMCP Bảo Việt'}, // ok
                                {'GAB'       : 'DaiA Bank - Ngân hàng TMCP Đại Á'}, // ok  : old DAB
                                {'EXB'       : 'Eximbank - Ngân hàng TMCP XNK Việt Nam'}, //ok 
                                {'GPB'       : 'GPBank - Ngân hàng TMCP Dầu khí Toàn Cầu'}, //ok 
                                {'HDB'       : 'HD Bank - Ngân hàng Phát triển Nhà TPHCM'}, //ok
                                {'LVB'       : 'Lien Viet Post Bank - Ngân hàng Bưu Điện Liên Việt'}, // ok : old VLPB
                                {'MBB'       : 'MB Bank - Ngân hàng TMCP Quân Đội'}, //ok 
                                {'MHB'       : 'MHB - Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long'}, //ok
                                {'NVB'       : 'Navibank - Ngân hàng TMCP Nam Việt'}, //ok
                                {'OJB'       : 'OceanBank - Ngân hàng TMCP Đại Dương'}, // ok old : OCEB
                                {'SCB'       : 'Sacombank - Ngân hàng TMCP Sài Gòn thương tín'}, //ok 
                                {'SHB'       : 'SHB - Ngân hàng TMCP Sài Gòn - Hà Nội'}, //ok
                                {'TCB'       : 'Techcombank - Ngân hàng TMCP Kỹ Thương Việt Nam'}, //ok
                                {'TPB'       : 'TienPhong Bank - Ngân hàng TMCP Tiên Phong'}, //ok
                                {'VIB'       : 'VIB - Ngân hàng TMCP Quốc tế'}, //ok
                                {'VAB'       : 'Viet A Bank - Ngân hàng TMCP Việt Á'}, //ok
                                {'VCB'       : 'Vietcombank - Ngân hàng TMCP Ngoại Thương Việt Nam'}, //ok
                                {'ICB'       : 'VietinBank - Ngân hàng TMCP Công Thương Việt Nam'}, //ok
                                {'VPB'       : 'VPBank - Ngân hàng TMCP Việt Nam Thịnh Vượng'}, //ok,
                                {'HLBVN'     : 'Ngân hàng Hong Leong Việt Nam'},//add 
                                {'OCB'       : 'Ngân hàng TMCP Phương Đông'}, //add 
                                {'AGB'       : 'Ngân hàng NN&PT Nông thôn'},
                                {'ANZ'       : 'Ngân hàng ANZ'},
                                {'BIDC'      : 'Ngân hàng ĐT&PT Campuchia'},
                                {'CTB'       : 'Ngân hàng CITY BANK'},
                                {'DAB'       : 'Ngân hàng TMCP Đông Á'},
                                {'HSB'       : 'Ngân hàng HSBC'},
                                {'IVB'       : 'Ngân Hàng Indovina'},
                                {'KLB'       : 'Ngân hàng TMCP Kiên Long'},
                                {'MDB'       : 'Ngân hàng TMCP PT Mê Kông'},
                                {'MHB'       : 'Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long'},
                                {'NCB'       : 'Ngân hàng TMCP Quốc Dân'},
                                {'NHOFFLINE' : 'Ngân hàng Offline'},
                                {'PGB'       : 'Ngân hàng TMCP Xăng dầu Petrolimex'},
                                {'PNB'       : 'Ngân hàng Phương Nam'},
                                {'PVB'       : 'Ngân hàng TMCP Đại Chúng Việt Nam'},
                                {'SEA'       : 'Ngân hàng TMCP Đông Nam Á'},
                                {'SGB'       : 'Ngân hàng TMCP Sài Gòn Công Thương'},
                                {'SGCB'      : 'Ngân hàng TMCP Sài Gòn'},
                                {'SHNB'      : 'Ngân hàng SHINHAN'},
                                {'SMB'       : 'Ngân hàng SUMITOMO-MITSUI'},
                                {'STCB'      : 'Ngân hàng STANDARD CHARTERED'},
                                {'VB'        : 'Ngân hàng Việt Nam Thương Tín'},
                                {'VCCB'      : 'Ngân hàng TMCP Bản Việt'},
                                {'VDB'       : 'Ngân hàng Phát triển Việt Nam'},
                                {'VIDPB'     : 'Ngân hàng VID Public Bank'},
                                {'VNCB'      : 'Ngân hàng TMCP Xây dựng Việt Nam'},
                                {'VRB'       : 'Ngân hàng Liên doanh Việt - Nga'},
                                {'VSB'       : 'Ngân Hàng Liên Doanh Việt Thái'},
                                {'NONE'      : 'Ngân hàng khác (áp dụng với thẻ visa)'}, //add 

                            ]
        })
        .constant('Config_Child', {
            Roles            :  [{id: 1,name:'Quản lý'}]
        })
        .constant('OrderStatus', {
            //vi
        	pay_pvc :{
                        1 : 'Thu hộ theo số tiền quý khách nhập',
                        2 : 'Thu hộ tiền hàng, phí vận chuyển, phí thu hộ',
                        3 : 'Chỉ thu hộ tiền hàng',
                        4 : 'Chỉ thu hộ phí vận chuyển, phí thu hộ',
                        5 : 'Không thu hộ'
                    },
            //en
            pay_pvc_en :{
	                        1 : 'Collect money as you want',
	                        2 : 'Collect product value, shipping & CoD fee',
	                        3 : 'Collect product value',
	                        4 : 'Collect shipping & CoD fee',
	                        5 : 'Not CoD'
                    	}, 
           //thai         	
           pay_pvc_thailand  :{
                                1: 'เก็บเงินตามจำนวนเงินที่ผู้ฝากส่งกรอกไว้',
                                2: 'เก็บรับปลายทางค่าสินค้า ค่าฝากส่ง ค่าธรรมเนียมฝากรับเงิน',
                                3: 'เพียงแต่เก็บรับเงินปลายทางสินค้า',
                                4: 'เพียงแต่เก็บค่าฝากส่ง ค่าธรรมเนียมฝากรับเงิน',
                                5: 'ไม่เก็บปลายทางค่าสินค้า',
                            },
             //list payment vi               
            list_pay_pvc   :  [{	'id'    : 1,
                                  	name  : 'Thu hộ theo số tiền quý khách nhập'
                                },{	'id'    : 2,
                                    name  : 'Thu hộ tiền hàng, phí vận chuyển, phí thu hộ'
                                },{	'id'    : 3,
                                    name  : 'Chỉ thu hộ tiền hàng'
                                },{	'id'    : 4,
                                    name  : 'Chỉ thu hộ phí vận chuyển, phí thu hộ'
                                },{	'id'    : 5,
                                    name  : 'Không thu hộ'
                                }],
              //list payment thailand                        
             list_pay_pvc_thailand :[{ 'id'    : 1,
                                        name  : 'เก็บเงินตามจำนวนเงินที่ผู้ฝากส่งกรอกไว้',
                                      },{'id'    : 2,
                                         name  :  'เก็บรับปลายทางค่าสินค้า ค่าฝากส่ง ค่าธรรมเนียมฝากรับเงิน',
                                      },{'id'    : 3,
                                         name  : 'เพียงแต่เก็บรับเงินปลายทางสินค้า',
                                      },{'id'    : 4,
                                         name  : 'เพียงแต่เก็บค่าฝากส่ง ค่าธรรมเนียมฝากรับเงิน',
                                      },{'id'    : 5,
                                         name  : 'ไม่เก็บปลายทางค่าสินค้า',
                                      }],
        //list payment en                             
            list_pay_pvc_en         :[{'id'    : 1,
                                       name  : 'Collect money as you want'
                                      },{'id'    : 2,
                                         name  : 'Collect product value, shipping & CoD fee'
                                      },{'id'    : 3,
                                         name  : 'Collect product value'
                                      },{'id'    : 4,
                                         name  : 'Collect shipping & CoD fee'
                                      },{'id'    : 5,
                                         name  : 'Not CoD'
                                      }], 
           //list service vi                           
            service            :    {	1 : 'Chuyển phát tiết kiệm',
                                        2 : 'Chuyển phát nhanh',
                                        8 : 'Chuyển phát quốc tế'
            						},
            //list service en						
            service_en         :    {	1 : 'Economy delivery service',
                                        2 : 'Express delivery service',
                                        8 : 'International Service'
            						},
           //list service thailand
           service_thailand    : 	{	1 : 'บริการจัดส่งสินค้าแบบประหยัด',
                                        2 : 'บริการจัดส่งสินค้าแบบด่วน',
                                        8 : 'การจัดส่งสินค้าระหว่างประเทศ'
           							},
           //list service thailand							
           list_service_thailand    : [{'id'    : 1,
                                         name  : 'บริการจัดส่งสินค้าแบบประหยัด',
                                    },{	'id'    : 2,
                                         name  : 'บริการจัดส่งสินค้าแบบด่วน',
                                    },{	'id'    : 8,
                                        name  : 'การจัดส่งสินค้าระหว่างประเทศ'
                                    }],  
           //list service vi
            list_service            :[{'id'    : 1,
                                        name  : 'Chuyển phát tiết kiệm'
                                     },{'id'    : 2,
                                         name  : 'Chuyển phát nhanh'
                                     },{'id'    : 8,
                                         name  : 'Chuyển phát quốc tế'
                                     }],
            //list service en                         
            list_service_en           :[{'id'    : 1,
                                          name  : 'Economy delivery service'
                                       },{'id'    : 2,
                                           name  : 'Express delivery service'
                                       },{'id'    : 8,
                                           name  : 'International Service'
                                       }]                        
        });
})(app);