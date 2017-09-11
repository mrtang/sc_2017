(function (app) {
    app
        .constant('Api_Path', {
            _Base           :   ApiBase+'base/',
            Ops             :   ApiOps,
            Base            :   ApiPath,
            Root            :   ApiBase,
            OrderStatus     :   ApiPath+'order-status',
            Courier         :   ApiPath+'courier',
            City            :   ApiPath+'city',
            Status          :   ApiPath+'list_status',
            ChangeOrder     :   ApiPath+'order-change',
            Pipe            :   ApiPath+'pipe-status',

            Order           :   ApiOms+'order',
            PipeJourney     :   ApiOms+'pipe-journey',
            Inventory       :   ApiOms+'inventory',
            User            :   ApiOms+'user',
            Upload          :   ApiOms+'upload/',

            list_user       :  ApiPath+'user?item_page=8'
        })
        .constant('Config_Status', {
            Ticket               :  [
                                        { code : 'ALL'                      , content : 'Tất cả'},
                                        { code : 'NEW_ISSUE'                , content : 'Mới tạo (chờ tiếp nhận)'},
                                        { code : 'ASSIGNED'                 , content : 'Đã tiếp nhận (Chờ xử lý)'},
                                        { code : 'PENDING_FOR_CUSTOMER'     , content : 'Đã trả lời (Chờ phản hồi)'},
                                        { code : 'CUSTOMER_REPLY'           , content : 'Đã phản hồi(Chờ trả lời)'},
                                        { code : 'PROCESSED'                , content : 'Đã xử lý (Chờ đóng)'},
                                        { code : 'CLOSED'                   , content : 'Đã đóng'}
                                    ],
            ticket_btn          :    {
                                        'NEW_ISSUE'     : {
                                            'name'      : 'Yêu cầu mới',
                                            'bg'        : 'bg-info'
                                        },
                                        'ASSIGNED'      : {
                                            'name'      : 'Đã tiếp nhận',
                                            'bg'        : 'bg-primary'    
                                        },
                                        'PENDING_FOR_CUSTOMER'  : {
                                            'name'      : 'Chờ phản hồi',
                                            'bg'        : 'bg-warning'    
                                        },

                                        'CUSTOMER_REPLY'  : {
                                            'name'      : 'Chờ trả lời',
                                            'bg'        : 'bg-primary'    
                                        },
                                        'PROCESSED'     : {
                                            'name'      : 'Đã xử lý',
                                            'bg'        : 'bg-success'
                                        },
                                        'CLOSED'        : {
                                            'name'      : 'Đã đóng',
                                            'bg'        : 'bg-light dker'
                                        }
            },
            TabLadingReport         :  [
                { code : 'ALL'                      , content : 'Tất cả'},
                { code : 'HN'                       , content : 'Nội thành Hà Nội'},
                { code : 'HCM'                      , content : 'Nội thành Hồ Chí Minh'},
                { code : 'FHN'                      , content : 'Từ Hà Nội'},
                { code : 'FHCM'                     , content : 'Từ Hồ Chí Minh'},
                { code : 'OTHER'                    , content : 'Liên tỉnh'},
                { code : 'DISTRICT'                 , content : 'Huyện xã'}
            ],
            order_color          :    {
                23   : 'bg-info',
                24   : 'bg-info dker',
                25   :'bg-warning',
                26   :'bg-danger',
                27   :'bg-orange',
                28   :'bg-orange',
                29   :'bg-danger lt',
                30   :'bg-success bg',
                31   :'bg-primary',
                32   :'bg-warning',
                33   :'bg-danger',
                35   :'bg-orange',
                36   :'bg-success',
                40   :'bg-danger'
            },
            tag_color : {
                trangthailh     : 'bg-info',
                layhang         : 'bg-info',
                giaohang        : 'bg-info',
                sodu            : 'bg-info',
                huydon          : 'bg-info',
                kythuat         : 'bg-info',
                livechat        : 'bg-info',
                lienket         : 'bg-info',

                ngoaituyen      : 'bg-primary',
                vandon          : 'bg-primary',
                trangthaigh     : 'bg-primary',
                lienlac         : 'bg-primary',
                chuyenhoan      : 'bg-primary',
                tichhop         : 'bg-primary',
                luukho          : 'bg-primary',

                doisoat         : 'bg-orange',
                buutalh         : 'bg-orange',
                buutagh         : 'bg-orange',
                hangcam         : 'bg-orange',
                kll             : 'bg-orange',
                ddcgh           : 'bg-orange',
                thuho           : 'bg-orange',
                vuotcan         : 'bg-orange',

                lhc             : 'bg-warning',
                cgh             : 'bg-warning',
                khieunai        : 'bg-warning',
                giaogap         : 'bg-warning',
                giaolai         : 'bg-warning'
            },
            group   : {
                PRIVILEGE_PICKUP    : [23, 24, 25, 26, 27,33], // nhóm lấy hàng
                PRIVILEGE_DELIVERY  : [28,29,30,35,40],
                PRIVILEGE_RETURN    : [31,32,36]
            },
            list_color          :    {
                23   : '#23b7e5',
                24   : '#1199c4',
                25   : '#fad733',
                26   : '#f05050',
                27   : '#FFA931',
                28   : '#FFA931',
                29   : '#f06a6a',
                30   : '#27c24c',
                31   : '#958dc6',
                32   : '#f7de69',
                33   : '#f05050',
                35   : '#FFA931',
                36   : '#27c24c',
                40   : '#f05050'
            },
            group_status  : {
                23 : [20],
                24 : [21],
                25 : [30,35,38],
                26 : [31,32,33,34],
                27 : [36,37],
                33 : [22,23,24,25,27,28,29],

                31 : [60,61],
                32 : [62,63,64,65],

                28 : [40,50,51,67,76,79,80,81],
                29 : [54,55,56,57,58,59,75,77],
                30 : [52,53],
                35 : [70,71,73],
                36 : [66],
                40 : [27]
            },
            StatusVerify            : {
                ALL                     : {text : 'Tất cả',                 color:'bg-default'},
                NOT_ACTIVE              : {text : 'Mới tạo',                color : 'bg-info'},
                SUCCESS                 : {text : 'Thành công',             color: 'bg-success'},
                MISMATCH                : {text : 'Sai Lệch',               color: 'bg-warning'},
                NOT_EXITS               : {text : 'Không tồn tại hoặc đã đối soát', color: 'bg-primary'},
                NOT_EXISTS              : {text : 'Không tồn tại',          color: 'bg-warning'},
                COURIER_NOT_EXISTS      : {text : 'Hãng vc không tồn tại',  color: 'bg-danger'},

                ORDER_NOT_EXISTS        : {text : 'Vận đơn không tồn tại',       color: 'bg-danger'},
                ORDER_DETAIL_NOT_EXISTS        : {text : 'Vận đơn chi tiết không tồn tại', color: 'bg-danger'},
                STATUS_ERROR            : {text : 'Trạng thái sai',       color: 'bg-warning'},
                UPDATE_STATUS_FAIL      : {text : 'Cập nhật trạng thái lỗi',    color: 'bg-warning'},
                INSERT_LOG_FAIL         : {text : 'Cập nhật log Lỗi',   color: 'bg-danger'},
                INSERT_FAIL             : {text : 'Tạo hành trình lỗi',         color: 'bg-danger'},
                API_FAIL                : {text : 'Hệ thống cập nhật lỗi',       color: 'bg-danger'},
                DATA_EMPTY              : {text : 'Thiếu dữ liệu',       color: 'bg-danger'},
                ORDER_CANNOT_CHANGE     : {text : 'Không được phép sửa',       color: 'bg-danger'},

                WAITING                 : {text : 'Chờ xử lý',          color: 'bg-info'},
                PROCESSING              : {text : 'Đang xử lý',         color: 'bg-warning'},
                SUCCESS                 : {text : 'Đã xử lý',           color: 'bg-success'}
            }
        })
        .constant('Config_Accounting', {
            unit            :  [{id:1, code:'VND', name:'Việt Nam Đồng (VND)'}, {id:2, code:'USD', name:'Dollar (USD)'}, {id:3, code:'EUR', name: 'Euro (EUR)'}],
            payment_terms   :  [{id:1,code:'EOM'},{id:2,code:'HDPE'}],
            list_bank       :  [{code:'VCB'  ,name:'Ngân hàng TMCP Ngoại Thương Việt Nam(VIETCOMBANK)'},
                                {code:'DAB' ,name:'Ngân hàng Đông Á'},
                                {code:'TCB' ,name:'Ngân hàng Kỹ Thương (TECHCOMBANK)'},
                                {code:'VIB' ,name:'Ngân hàng Quốc tế(VIB)'},
                                {code:'MB'  ,name:'Ngân Hàng Quân Đội (MB BANK)'},
                                {code:'ICB' ,name:'Ngân hàng Công Thương Việt Nam(VIETINBANK)'},
                                {code:'HDB' ,name:'Ngân hàng Phát triển Nhà TPHCM (HD BANK)'},
                                {code:'EXB' ,name:'Ngân hàng Xuất Nhập Khẩu(EXIMBANK)'},
                                {code:'ACB' ,name:'Ngân hàng Á Châu (ACB)'},
                                {code:'SHB' ,name:'Ngân hàng Sài Gòn-Hà Nội'},
                                {code:'PGB' ,name:'Ngân hàng Xăng dầu Petrolimex'},
                                {code:'TPB' ,name:'Ngân hàng Tiền Phong'},
                                {code:'SCB' ,name:'Ngân hàng Sài Gòn Thương tín'},
                                {code:'MSB' ,name:'Ngân hàng Hàng Hải'},
                                {code:'AGB' ,name:'Ngân hàng Nông nghiệp & Phát triển nông thôn'},
                                {code:'BIDV',name:'Ngân hàng Đầu tư & Phát triển Việt Nam'}
                                ],
            bank            : {
                                'VCB'   : 'Ngân hàng TMCP Ngoại Thương Việt Nam(VIETCOMBANK)',
                                'DAB'   : 'Ngân hàng Đông Á',
                                'TCB'   : 'Ngân hàng Kỹ Thương (TECHCOMBANK)',
                                'VIB'   : 'Ngân hàng Quốc tế(VIB)',
                                'MB'    : 'Ngân Hàng Quân Đội (MB BANK)',
                                'ICB'   : 'Ngân hàng Công Thương Việt Nam(VIETINBANK)',
                                'HDB'   : 'Ngân hàng Phát triển Nhà TPHCM (HD BANK)',
                                'EXB'   : 'Ngân hàng Xuất Nhập Khẩu(EXIMBANK)',
                                'ACB'   : 'Ngân hàng Á Châu (ACB)',
                                'SHB'   : 'Ngân hàng Sài Gòn-Hà Nội',
                                'PGB'   : 'Ngân hàng Xăng dầu Petrolimex',
                                'TPB'   : 'Ngân hàng Tiền Phong',
                                'SCB'   : 'Ngân hàng Sài Gòn Thương tín',
                                'MSB'   : 'Ngân hàng Hàng Hải',
                                'AGB'   : 'Ngân hàng Nông nghiệp & Phát triển nông thôn',
                                'BIDV'  : 'Ngân hàng Đầu tư & Phát triển Việt Nam'
                            },
                vimo   :  {
                'ABB'        : 'ABBank - Ngân hàng TMCP An Bình', // ok
                'ACB'        : 'ACB - Ngân hàng TMCP Á Châu', // ok
                'BAB'        : 'BacA Bank - Ngân hàng TMCP Bắc Á',// ok
                'BVB'       : 'Baoviet Bank - Ngân hàng TMCP Bảo Việt', // ok
                'GAB'       : 'DaiA Bank - Ngân hàng TMCP Đại Á', // ok  : old DAB
                'EXB'       : 'Eximbank - Ngân hàng TMCP XNK Việt Nam', //ok 
                'GPB'       : 'GPBank - Ngân hàng TMCP Dầu khí Toàn Cầu', //ok 
                'HDB'       : 'HD Bank - Ngân hàng Phát triển Nhà TPHCM', //ok
                'LVB'        : 'Lien Viet Post Bank - Ngân hàng Bưu Điện Liên Việt', // ok : old VLPB
                'MB'       : 'MB Bank - Ngân hàng TMCP Quân Đội', //ok 
                'MHB'       : 'MHB - Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long', //ok
                'NVB'       : 'Navibank - Ngân hàng TMCP Nam Việt', //ok
                'OJB'        : 'OceanBank - Ngân hàng TMCP Đại Dương', // ok old : OCEB
                'SCB'       : 'Sacombank - Ngân hàng TMCP Sài Gòn thương tín', //ok 
                'SHB'       : 'SHB - Ngân hàng TMCP Sài Gòn - Hà Nội', //ok
                'TCB'       : 'Techcombank - Ngân hàng TMCP Kỹ Thương Việt Nam', //ok
                'TPB'       : 'TienPhong Bank - Ngân hàng TMCP Tiên Phong', //ok
                'VIB'       : 'VIB - Ngân hàng TMCP Quốc tế', //ok
                'VAB'       : 'Viet A Bank - Ngân hàng TMCP Việt Á', //ok
                'VCB'       : 'Vietcombank - Ngân hàng TMCP Ngoại Thương Việt Nam', //ok
                'ICB'       : 'VietinBank - Ngân hàng TMCP Công Thương Việt Nam', //ok
                'VPB'       : 'VPBank - Ngân hàng TMCP Việt Nam Thịnh Vượng', //ok,
                'HLBVN'     : 'Ngân hàng Hong Leong Việt Nam',//add 
                'OCB'       : 'Ngân hàng TMCP Phương Đông'//add 
            }

        })
        .constant('Config', {
            business_model: [
                {id: 1, name: 'Thời trang'},
                {id: 2, name: 'Mỹ phẩm'},
                {id: 3, name: 'Gia dụng'},
                {id: 4, name: 'Đồ công nghệ, điện tử'},
                {id: 5, name: 'Khác'},
            ],
            service          :    {
                1   : 'Chuyển phát thường',
                2   : 'Chuyển phát nhanh',
                3   : 'Boxme Chuyển phát thường',
                4   : 'Boxme Chuyển phát nhanh'
            },
            router : {
                23 : 'pickup.request',
                24 : 'pickup.accept',
                25 : 'pickup.stocking',
                26 : 'pickup.problem',
                27 : 'pickup.stocked',
                33 : 'pickup.cancel',

                28 : 'delivery.delivering',
                29 : 'delivery.problem',
                30 : 'delivery.delivered',
                40 : 'delivery.cancel',
                35 : 'delivery.status_problem',

                31 : 'return.waiting',
                32 : 'return.returning',
                36 : 'return.return'
            },
            router_privilege : {
                'pickup.request'        : 'PRIVILEGE_PICKUP',
                'pickup.accept'         : 'PRIVILEGE_PICKUP',
                'pickup.stocking'       : 'PRIVILEGE_PICKUP',
                'pickup.problem'        : 'PRIVILEGE_PICKUP',
                'pickup.stocked'        : 'PRIVILEGE_PICKUP',
                'pickup.cancel'         : 'PRIVILEGE_PICKUP',
                'pickup.address'        : 'PRIVILEGE_PICKUP_ADDRESS',
                'pickup.addressInDay'   : 'PRIVILEGE_PICKUP_ADDRESS',

                'delivery.delivering'       : 'PRIVILEGE_DELIVERY',
                'delivery.problem'          : 'PRIVILEGE_DELIVERY',
                'delivery.cancel'           : 'PRIVILEGE_DELIVERY',
                'delivery.status_problem'   : 'PRIVILEGE_DELIVERY',
                'delivery.delivered'        : 'PRIVILEGE_DELIVERY',

                'return.waiting'            : 'PRIVILEGE_RETURN',
                'return.returning'          : 'PRIVILEGE_RETURN',
                'return.return'             : 'PRIVILEGE_RETURN',

                'upload.journey'            : 'PRIVILEGE_IMPORT_JOURNEY',
                'upload.upload_journey'     : 'PRIVILEGE_IMPORT_JOURNEY',
                'upload.weight'             : 'PRIVILEGE_UPDATE_WEIGHT',
                'upload.upload_weight'      : 'PRIVILEGE_UPDATE_WEIGHT',

                'order.update_slow'         : 'PRIVILEGE_ORDER_PROBLEM',
                'order.amount'              : 'PRIVILEGE_ORDER_PROBLEM',
                'order.over_weight'         : 'PRIVILEGE_ORDER_PROBLEM',
                'order.delivery_slow'       : 'PRIVILEGE_ORDER_PROBLEM',

                'accounting.coupons'        : 'PRIVILEGE_ACCOUNTING',
                'accounting.coupons-list'   : 'PRIVILEGE_ACCOUNTING',
                'accounting.vimo-verify'    : 'PRIVILEGE_ACCOUNTING',

                'accounting.cashin'                 : 'PRIVILEGE_CASHIN',
                'accounting.cashin.show'            : 'PRIVILEGE_CASHIN',
                'accounting.cashin.add'             : 'PRIVILEGE_CASHIN',
                'accounting.cashin.add-excel'       : 'PRIVILEGE_CASHIN',
                'accounting.cashin.add-excel-list'  : 'PRIVILEGE_CASHIN',

                'merchant.list'                     : 'PRIVILEGE_SELLER',
                'merchant.vip_process'              : 'PRIVILEGE_SELLER',
                'merchant.vip_list'                 : 'PRIVILEGE_SELLER',
                'merchant.vip'                      : 'PRIVILEGE_SELLER',

                'app.config.group_user'             : 'PRIVILEGE_CONFIG',
                'app.config.privileges'             : 'PRIVILEGE_CONFIG',
                'app.config.pipe_status'            : 'PRIVILEGE_CONFIG'
            }
        });
})(app);