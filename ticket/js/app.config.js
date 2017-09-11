;
(function (app) {
    app
        .constant('Api_Path', {
            Base            :   ApiPath,
            _Base           :   ApiBase,
            list_user       :   ApiPath+'user?item_page=8',
            Upload          :   ApiPath+'upload/',
            OrderStatus     :   ApiPath+'order-status',
            Pipe            :   ApiPath+'pipe-status'
        })
        .constant('Config', {
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
            service          :    {
                1   : 'Chuyển phát thường',
                2   : 'Chuyển phát nhanh',
                3   : 'Boxme Chuyển phát thường',
                4   : 'Boxme Chuyển phát nhanh'
            }
        })
        .constant('Config_Status', {
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
            Nav_Ticket               :  [
                { code : 'ALL'                      , content : 'Tất cả'},
                { code : 'NEW_ISSUE'                , content : 'Mới tạo (chờ tiếp nhận)'},
                { code : 'ASSIGNED'                 , content : 'Đã tiếp nhận (Chờ xử lý)'},
                { code : 'PENDING_FOR_CUSTOMER'     , content : 'Đã trả lời (Chờ phản hồi)'},
                { code : 'CUSTOMER_REPLY'           , content : 'Khách đã phản hồi'},
                { code : 'PROCESSED'                , content : 'Đã xử lý (Chờ đóng)'},
                { code : 'CLOSED'                   , content : 'Đã đóng'}
            ],
            Ticket               :  [
                { code : 'NEW_ISSUE'                , content : 'Mới tạo (chờ tiếp nhận)'},
                { code : 'ASSIGNED'                 , content : 'Đã tiếp nhận (Chờ xử lý)'},
                { code : 'PENDING_FOR_CUSTOMER'     , content : 'Đã trả lời (Chờ phản hồi)'},
                { code : 'CUSTOMER_REPLY'           , content : 'Khách đã phản hồi'},
                { code : 'PROCESSED'                , content : 'Đã xử lý (Chờ đóng)'}
            ],
            Ticket_Master               :  [
                { code : 'NEW_ISSUE'                , content : 'Mới tạo (chờ tiếp nhận)'},
                { code : 'ASSIGNED'                 , content : 'Đã tiếp nhận (Chờ xử lý)'},
                { code : 'PENDING_FOR_CUSTOMER'     , content : 'Đã trả lời (Chờ phản hồi)'},
                { code : 'PROCESSED'                , content : 'Đã xử lý (Chờ đóng)'},
                { code : 'CUSTOMER_REPLY'           , content : 'Khách đã phản hồi'},
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
                    'name'      : 'Khách đã phản hồi',
                    'bg'        : 'bg-primary'
                },
                'PROCESSED'     : {
                    'name'      : 'Đã xử lý',
                    'bg'        : 'bg-success'
                },
                'CLOSED'        : {
                    'name'      : 'Đã đóng',
                    'bg'        : 'bg-light'
                }
            },
            priority            :       {
                0           : {
                    'name' : 'Tất cả',
                    'bg'   : 'bg-default',
                    'text' : 'text-info'
                },
                1           : {
                    'name' : 'Bình thường',
                    'bg'   : 'bg-info',
                    'text' : 'text-info'
                },
                2           : {
                    'name'  : 'Quan trọng',
                    'bg'    : 'bg-warning',
                    'text'  : 'text-warning'
                },
                3           : {
                    name    : 'Rất quan trọng',
                    'bg'    : 'bg-danger',
                    'text'  : 'text-danger'
                }
            },
            priority_case   :       {
                0          : 'Độ quan trọng',
                1          : 'Bình thường',
                2          : 'Quan trọng',
                3          : 'Rất quan trọng'
            },
            CourierPrefix               : {
                                        1: 'vtp',
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
        });
})(app);