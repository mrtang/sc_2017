(function (app) {
    app
        .constant('Api_Path', {
            Base            :   ApiPath+'api/base/',
            Acc             :   ApiPath+'accounting/',
            Rest            :   ApiPath+'rest/',
            Search          :   'http://boxme.vn:9200/address/_search?q=name:'
        })
        .constant('Config_Status', {
            StatusVerify            : {
                ALL                     : {text : 'Tất cả',                 color:'bg-default'},
                NOT_ACTIVE              : {text : 'Mới tạo',                color : 'bg-info'},
                SUCCESS                 : {text : 'Thành công',             color: 'bg-success'},
                MISMATCH                : {text : 'Sai Lệch',               color: 'bg-warning'},
                TO_ADDRESS_NOT_EXIST    : {text : 'Địa chỉ nhận không tồn tại',               color: 'bg-warning'},
                SERIVCE_NOT_EXISTS      : {text : 'Dịch vụ không tồn tại',               color: 'bg-warning'},
                UPDATE_ORDER_FAIL       : {text : 'CN thời gian đối soát',  color: 'bg-danger'},
                COURIER_ERROR           : {text : 'HVC không đúng',  color: 'bg-warning'},
                CITY_MAP_NOT_EXIST      : {text : 'Thành phố không tồn tại',  color: 'bg-warning'},
                UPDATE_ORDER_VERIFY_FAIL: {text : 'CN bảng đối soát',       color: 'bg-danger'},
                NOT_EXITS               : {text : 'Không tồn tại hoặc đã đối soát', color: 'bg-primary'},

                ORDER_NOT_EXISTS        : {text : 'Vận đơn không tồn tại',       color: 'bg-danger'},
                ORDER_DETAIL_NOT_EXISTS        : {text : 'Vận đơn chi tiết không tồn tại', color: 'bg-danger'},
                STATUS_NOT_WAITING      : {text : 'Bản kê đã được xử lý',       color: 'bg-warning'},
                UPDATE_STATUS_FAIL      : {text : 'Cập nhật trạng thái lỗi',    color: 'bg-warning'},
                REFER_CODE_EXISTS       : {text : 'Mã tham chiếu đã tồn tại',    color: 'bg-warning'},
                EMAIL_NL_ERROR          : {text : 'Email NL không chính xác',   color: 'bg-danger'},
                EMAIL_NOT_EXISTS        : {text : 'Email không chính xác',      color: 'bg-danger'},
                TRANSACTION_CODE_EMPTY  : {text : 'Thiếu mã giao dịch',         color: 'bg-danger'},
                TRANSACTION_ID_EMPTY    : {text : 'Thiếu mã giao dịch',         color: 'bg-danger'},
                AMOUNT_ERROR            : {text : 'Tổng tiền không chính xác',  color: 'bg-danger'},
                INSERT_TRANSACTION_FAIL : {text : 'Ghi nhận giao dịch lỗi',     color: 'bg-danger'},
                MERCHANT_NOT_EXISTS     : {text : 'Merchant không tồn tại',     color: 'bg-danger'},
                UPDATE_BALANCE_ERROR    : {text : 'Cập nhật số dư lỗi',         color: 'bg-danger'},
                UPDATE_VERIFY_ERROR     : {text : 'Cập nhật bản kê lỗi',        color: 'bg-danger'},
                REQUEST_NOT_EXISTS      : {text : 'Bản kê không tồn tại',       color: 'bg-danger'},
                USER_NL_ID_ERROR        : {text : 'User NL ID không chính xác',       color: 'bg-danger'},
                ACCOUNT_NUMBER_ERROR    : {text : 'Mã tài khoản không chính xác',       color: 'bg-danger'},

                WAITING                 : {text : 'Chờ xử lý',          color: 'bg-info'},
                PROCESSING              : {text : 'Đang xử lý',         color: 'bg-warning'},
                SUCCESS                 : {text : 'Đã xử lý',           color: 'bg-success'}
            },
            StatusBalance           : {
                SUCCESS                 : 'Chuyển thành công'
            },
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
                38   :'bg-warning',
                40   :'bg-danger',
                71   :'bg-orange'
            }
        })
        .constant('Privilege', {
            router : {
                'app.provider'              : 'PRIVILEGE_ACCOUNTING',
                'app.order'                 : 'PRIVILEGE_ACCOUNTING',
                'app.report_merchants'      : 'PRIVILEGE_ACCOUNTING_REPORT',
                'app.report_orders'         : 'PRIVILEGE_ACCOUNTING_REPORT',

                'app.balance.report'        : 'PRIVILEGE_ACCOUNTING_BALANCE',
                'app.balance.audit'         : 'PRIVILEGE_ACCOUNTING_BALANCE',
                'app.balance.cash_in'       : 'PRIVILEGE_ACCOUNTING_BALANCE',
                'app.balance.cash_out'      : 'PRIVILEGE_ACCOUNTING_BALANCE',

                'app.cash_out'              : 'PRIVILEGE_ACCOUNTING_PAYMENT',
                'app.refund'                : 'PRIVILEGE_ACCOUNTING_PAYMENT',
                'app.recover'               : 'PRIVILEGE_ACCOUNTING_PAYMENT',

                'app.verify.money_collect'          : 'PRIVILEGE_ACCOUNTING_VERIFY',
                'app.verify.upload_money_collect'   : 'PRIVILEGE_ACCOUNTING_VERIFY',
                'app.verify.fee'                    : 'PRIVILEGE_ACCOUNTING_VERIFY',
                'app.verify.upload_fee'             : 'PRIVILEGE_ACCOUNTING_VERIFY',
                'app.verify.service'                : 'PRIVILEGE_ACCOUNTING_VERIFY',
                'app.verify.upload_service'         : 'PRIVILEGE_ACCOUNTING_VERIFY',

                'app.verify.create_verify'  : 'PRIVILEGE_VERIFY',
                'app.payment_verify'        : 'PRIVILEGE_VERIFY',

                'app.payment'               : 'PRIVILEGE_VERIFY',
                'app.invoice'               : 'PRIVILEGE_ACCOUNTING_INVOICE',
                'app.transaction'           : 'PRIVILEGE_ACCOUNTING_TRANSATION'
            }
        })
    ;
})(app);