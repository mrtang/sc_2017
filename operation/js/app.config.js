(function (app) {
    app
        .constant('Api_Path', {
            _Base           :   ApiBase+'base/',
            BmOps           :   ApiBase+'bm_ops/',
            Loyalty         :   ApiLoyalty,
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
        .constant('Boxme_Status',{
            status_color    : {
                0   : 'bg-info',
                1   : 'bg-info dker',
                2   :'bg-warning',
                3   :'bg-warning',
                4   :'bg-warning',
                5   :'bg-info dker',
                6   :'bg-danger',
                7   :'bg-primary',
                8   :'bg-primary',
                9   :'bg-primary',
                10   :'bg-primary',
                11   :'bg-orange',
                12   :'bg-success',
                13   :'bg-orange',
                14   :'bg-orange'
            }
        })
        .constant('Pipe_Process',{
            pipe_type    : {
                1   : 'Đơn hàng',
                2   : 'Khách hàng',
                3   : 'Địa chỉ',
                4   : 'Khách hàng Vip',
                5   : 'Đơn hàng xử lý',
                10  : 'Boxme - Order',
                11  : 'Boxme - Inventory',
                12  : 'Boxme - Shipment',
                13  :'Boxme - Cần xử lý'
            }
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
                40   :'bg-danger',
                42   : 'bg-info',
                43   : 'bg-info dker',
                44   :'bg-info',
                45   :'bg-orange',
                46   :'bg-orange',
                47   :'bg-success',
                48   :'bg-warning',
                49   :'bg-warning',
                50   :'bg-danger',
                71   :'bg-warning'
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
                40   : '#f05050',

                42   : '#23b7e5',
                43   : '#fad733',
                44   : '#FFA931',
                45   : '#FFA931',
                46   : '#FFA931',
                47   : '#27c24c',
                48   : '#f7de69',
                49   : '#f05050'
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

                28 : [50,51,67,76,79,80,81],
                29 : [54,55,56,57,58,59,75,77],
                30 : [52,53],
                35 : [70,71,73],
                36 : [66],
                40 : [27],
                49 : [12,23],
                71 : [40]
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
                LOCATION_NOT_EXISTS     : {text : 'Mã không tồn tại',       color: 'bg-danger'},
                FROM_DISTRICT_EMPTY     : {text : 'Không tồn tại quận huyện gửi',       color: 'bg-danger'},
                TO_DISTRICT_EMPTY       : {text : 'Không tồn tại quận huyện nhận',       color: 'bg-danger'},
                UPDATE_ERROR            : {text : 'Cập nhật thất bại',       color: 'bg-danger'},

                INSERT                  : {text : 'Chờ xác nhận',       color: 'bg-info'},
                WAITING                 : {text : 'Chờ xử lý',          color: 'bg-primary'},
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
                                {code:'MBB'  ,name:'Ngân Hàng Quân Đội (MB BANK)'},
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
                                'AGB'   : 'Ngân hàng Nông nghiệp & Phát triển nông thôn',
                                'BIDV'  : 'Ngân hàng Đầu tư & Phát triển Việt Nam'
                            },
               
                vimo_bank   :   [	{code :'ABB',    name        : 'ABBank - Ngân hàng TMCP An Bình'}, // ok
                    {code :'ACB',    name        : 'ACB - Ngân hàng TMCP Á Châu'}, // ok
                    {code :'BAB',    name        : 'BacA Bank - Ngân hàng TMCP Bắc Á'},// ok
                    {code : 'BVB',   name       : 'Baoviet Bank - Ngân hàng TMCP Bảo Việt'}, // ok
                    {code : 'GAB',   name       : 'DaiA Bank - Ngân hàng TMCP Đại Á'}, // ok  : old DAB
                    {code : 'EXB',   name       : 'Eximbank - Ngân hàng TMCP XNK Việt Nam'}, 
                    {code : 'GPB',   name       : 'GPBank - Ngân hàng TMCP Dầu khí Toàn Cầu'}, 
                    {code : 'HDB',   name       : 'HD Bank - Ngân hàng Phát triển Nhà TPHCM'},
                    {code : 'LVB',   name        : 'Lien Viet Post Bank - Ngân hàng Bưu Điện Liên Việt'}, // ok : old VLPB
                    {code : 'MBB',   name       : 'MB Bank - Ngân hàng TMCP Quân Đội'}, 
                    {code : 'MHB',   name       : 'MHB - Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long'},
                    {code : 'NVB',   name       : 'Navibank - Ngân hàng TMCP Nam Việt'},
                    {code : 'OJB',   name        : 'OceanBank - Ngân hàng TMCP Đại Dương'}, // ok old : OCEB
                    {code : 'SCB',   name       : 'Sacombank - Ngân hàng TMCP Sài Gòn thương tín'}, 
                    {code : 'SHB',   name       : 'SHB - Ngân hàng TMCP Sài Gòn - Hà Nội'},
                    {code : 'TCB',   name       : 'Techcombank - Ngân hàng TMCP Kỹ Thương Việt Nam'},
                    {code : 'TPB',   name       : 'TienPhong Bank - Ngân hàng TMCP Tiên Phong'},
                    {code : 'VIB',   name       : 'VIB - Ngân hàng TMCP Quốc tế'},
                    {code : 'VAB',   name       : 'Viet A Bank - Ngân hàng TMCP Việt Á'},
                    {code : 'VCB',   name       : 'Vietcombank - Ngân hàng TMCP Ngoại Thương Việt Nam'},
                    {code : 'ICB',   name       : 'VietinBank - Ngân hàng TMCP Công Thương Việt Nam'},
                    {code : 'VPB',   name       : 'VPBank - Ngân hàng TMCP Việt Nam Thịnh Vượng'},
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
                vimo   :   {
                        'ABB'       : 'ABBank - Ngân hàng TMCP An Bình',
                        'ACB'       : 'ACB - Ngân hàng TMCP Á Châu',
                        'BAB'       : 'BacA Bank - Ngân hàng TMCP Bắc Á',
                        'BVB'       : 'Baoviet Bank - Ngân hàng TMCP Bảo Việt',
                        'GAB'       : 'DaiA Bank - Ngân hàng TMCP Đại Á',
                        'EXB'       : 'Eximbank - Ngân hàng TMCP XNK Việt Nam',
                        'GPB'       : 'GPBank - Ngân hàng TMCP Dầu khí Toàn Cầu',
                        'HDB'       : 'HD Bank - Ngân hàng Phát triển Nhà TPHCM',
                        'LVB'       : 'Lien Viet Post Bank - Ngân hàng Bưu Điện Liên Việt',
                        'MBB'       : 'MB Bank - Ngân hàng TMCP Quân Đội', 
                        'MHB'       : 'MHB - Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long',
                        'NVB'       : 'Navibank - Ngân hàng TMCP Nam Việt',
                        'OJB'       : 'OceanBank - Ngân hàng TMCP Đại Dương',
                        'SCB'       : 'Sacombank - Ngân hàng TMCP Sài Gòn thương tín', 
                        'SHB'       : 'SHB - Ngân hàng TMCP Sài Gòn - Hà Nội',
                        'TCB'       : 'Techcombank - Ngân hàng TMCP Kỹ Thương Việt Nam',
                        'TPB'       : 'TienPhong Bank - Ngân hàng TMCP Tiên Phong',
                        'VIB'       : 'VIB - Ngân hàng TMCP Quốc tế',
                        'VAB'       : 'Viet A Bank - Ngân hàng TMCP Việt Á',
                        'VCB'       : 'Vietcombank - Ngân hàng TMCP Ngoại Thương Việt Nam',
                        'ICB'       : 'VietinBank - Ngân hàng TMCP Công Thương Việt Nam',
                        'VPB'       : 'VPBank - Ngân hàng TMCP Việt Nam Thịnh Vượng',
                        'HLBVN'     : 'Ngân hàng Hong Leong Việt Nam',
                        'OCB'       : 'Ngân hàng TMCP Phương Đông',
                        'AGB'       : 'Ngân hàng NN&PT Nông thôn',
                        'ANZ'       : 'Ngân hàng ANZ',
                        'BIDC'      : 'Ngân hàng ĐT&PT Campuchia',
                        'CTB'       : 'Ngân hàng CITY BANK',
                        'DAB'       : 'Ngân hàng TMCP Đông Á',
                        'HSB'       : 'Ngân hàng HSBC',
                        'IVB'       : 'Ngân Hàng Indovina',
                        'KLB'       : 'Ngân hàng TMCP Kiên Long',
                        'MDB'       : 'Ngân hàng TMCP PT Mê Kông',
                        'MHB'       : 'Ngân hàng TMCP PT Nhà Đồng bằng sông Cửu Long',
                        'NCB'       : 'Ngân hàng TMCP Quốc Dân',
                        'NHOFFLINE' : 'Ngân hàng Offline',
                        'PGB'       : 'Ngân hàng TMCP Xăng dầu Petrolimex',
                        'PNB'       : 'Ngân hàng Phương Nam',
                        'PVB'       : 'Ngân hàng TMCP Đại Chúng Việt Nam',
                        'SEA'       : 'Ngân hàng TMCP Đông Nam Á',
                        'SGB'       : 'Ngân hàng TMCP Sài Gòn Công Thương',
                        'SGCB'      : 'Ngân hàng TMCP Sài Gòn',
                        'SHNB'      : 'Ngân hàng SHINHAN',
                        'SMB'       : 'Ngân hàng SUMITOMO-MITSUI',
                        'STCB'      : 'Ngân hàng STANDARD CHARTERED',
                        'VB'        : 'Ngân hàng Việt Nam Thương Tín',
                        'VCCB'      : 'Ngân hàng TMCP Bản Việt',
                        'VDB'       : 'Ngân hàng Phát triển Việt Nam',
                        'VIDPB'     : 'Ngân hàng VID Public Bank',
                        'VNCB'      : 'Ngân hàng TMCP Xây dựng Việt Nam',
                        'VRB'       : 'Ngân hàng Liên doanh Việt - Nga',
                        'VSB'       : 'Ngân Hàng Liên Doanh Việt Thái',
                        'NONE'      : 'Ngân hàng khác (áp dụng với thẻ visa)', //add 
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
                5   : 'Dịch vụ vận tải',
                6   : 'Xuất hàng tại kho',
                8   : 'Chuyển phát nhanh quốc tế',
                9   : 'Chuyển phát tiết kiệm quốc tế'
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