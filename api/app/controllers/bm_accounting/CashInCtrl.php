<?php namespace bm_accounting;

use bm_accmodel\MerchantModel;
use metadatamodel\OrganizationUserModel;
use bm_sellermodel\PaymentModel;

class CashInCtrl extends BaseCtrl {
    private $config         = [];

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
        $page           = Input::has('page')            ? (int)Input::get('page')                       : 1;
        $itemPage       = Input::has('limit')           ? Input::get('limit')                           : 20;

        $TimeStart      = Input::has('create_start')    ? trim(Input::get('create_start'))              : '';
        $TimeEnd        = Input::has('create_end')      ? trim(Input::get('create_end'))                : '';
        $SuccessStart   = Input::has('success_start')   ? trim(Input::get('success_start'))             : '';
        $SuccessEnd     = Input::has('success_end')     ? trim(Input::get('success_end'))               : '';

        $KeyWord        = Input::has('search')          ? trim(Input::get('search'))                    : '';
        $Transaction    = Input::has('transaction')     ? trim(Input::get('transaction'))               : '';
        $ReferCode      = Input::has('refer_code')      ? strtoupper(trim(Input::get('refer_code')))    : '';
        $Status         = Input::has('status')          ? strtoupper(trim(Input::get('status')))        : '';
        $cmd            = Input::has('cmd')             ? strtoupper(trim(Input::get('cmd')))           : '';

        $Model = new PaymentModel;

        if(!empty($TimeStart)){
            $TimeStart      = $this->__convert_time($TimeStart);
            $Model          = $Model->where('created','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $TimeEnd        = $this->__convert_time($TimeEnd);
            $Model          = $Model->where('created','<=',$TimeEnd);
        }

        if(!empty($SuccessStart)){
            $SuccessStart   = $this->__convert_time($SuccessStart);
            $Model          = $Model->where('succed','>=',$SuccessStart);
        }

        if(!empty($SuccessEnd)){
            $SuccessEnd     = $this->__convert_time($SuccessEnd);
            $Model          = $Model->where('succed','<=',$SuccessEnd);
        }

        if(!empty($KeyWord)){
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $User          = \User::where('email',$KeyWord)->lists('id');
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $User          = \User::where('phone',$KeyWord)->lists('id');
            }else{
                $User          = \User::where('fullname',$KeyWord)->lists('id');
            }


            if(!empty($User)){
                $User    = array_unique($User);
                $Model   = $Model->whereIn('user',$User);
            }else{
                return $this->ResponseData();
            }
        }

        if(!empty($Transaction)){
            $Model          = $Model->where('transaction_id',$Transaction);
        }

        if(!empty($ReferCode)){
            $Model          = $Model->where('reference_code',$ReferCode);
        }

        if(!empty($Status)){
            switch ($Status) {
                case 'SUCCESS':
                    $Model = $Model->where('status',2);
                    break;
                case 'PROCESSING':
                    $Model = $Model->where('status',1);
                    break;
                case 'WAITING':
                    $Model = $Model->where('status',3);
                    break;
                default:
                    $Model = $Model->where('status',0);
                    break;
            }
        }

        if($cmd == 'EXPORT'){
            $this->data = $Model->with(['getOrganization','getUser'])->get()->toArray();
            return $this->ResponseData();
        }

        $ModelTotal     = clone $Model;
        $this->total    = $ModelTotal->count();

        if($this->total > 0){
            $itemPage   = (int)$itemPage;
            $offset     = ($page - 1)*$itemPage;
            $Model      = $Model->skip($offset)->take($itemPage);
            $this->data = $Model->orderBy('created','DESC')->with(['getOrganization','getUser'])->get()->toArray();
        }

        return $this->ResponseData();
	}

    private function ResponseData(){
        return Response::json([
            'error'         => false,
            'message'       => 'success',
            'item_page'     => 20,
            'total'         => $this->total,
            'data'          => $this->data
        ]);
    }

    public function postEdit(){
        $Id             = Input::has('id')                  ? (int)Input::get('id')                                 : 0;
        $Transaction    = Input::has('transaction_id')      ? strtoupper(trim(Input::get('transaction_id')))        : '';
        $ReferCod       = Input::has('reference_code')      ? strtoupper(trim(Input::get('reference_code')))      : '';

        $PaymentModel   = PaymentModel::where('id',$Id)->whereIn('status',[1,2])->first();

        if(!isset($PaymentModel->id)){
            return Response::json([
                'error'         => true,
                'code'          => 'NOT_EXISTS',
                'error_message' => 'Mã nạp tiền không tồn tại'
            ]);
        }

        if(!empty($Transaction)){
            $PaymentModel->transaction_id   = $Transaction;
        }

        if(!empty($ReferCod)){
            $PaymentModel->reference_code   = $ReferCod;
        }

        try{
            $PaymentModel->save();
        }catch(\Exception $e){
            return Response::json([
                'error'         => true,
                'code'          => 'UPDATE_ERROR',
                'error_message' => 'Cập nhật thất bại'
            ]);
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành Công'
        ]);
    }

    public function postCreate(){
        $Email          = Input::has('email')               ? strtolower(trim(Input::get('email')))             : '';
        $Transaction    = Input::has('transaction_id')      ? strtoupper(trim(Input::get('transaction_id')))    : '';
        $ReferCode      = Input::has('refer_code')          ? strtoupper(trim(Input::get('refer_code')))        : '';
        $Type           = Input::has('type')                ? strtoupper(trim(Input::get('type')))              : '';
        $Amount         = Input::has('amount')              ? str_replace(',','',trim(Input::get('amount')))   : '';

        Input::merge(['amount' => (int)$Amount]);

        $validation = Validator::make(Input::all(), array(
            'email'             => 'required|email',
            'transaction_id'    => 'required',
            'refer_code'        => 'required',
            'amount'            => 'required|integer|min:1000',
            'type'              => 'required|in:NL,NH',
        ));

        //error
        if($validation->fails()) {
            return Response::json(['error' => true, 'code' => 'ERROR', 'error_message' => $validation->messages()]);
        }

        $User           = \User::where('email', $Email)->first();
        if(!isset($User->organization) || empty($User->organization)){
            return Response::json(['error' => true, 'code' => 'EMAIL_ERROR','error_message' => 'email không chính xác']);
        }

        try{
            PaymentModel::insert([
                'user'          => $User->id,
                'organization'  => $User->organization,
                'amount'        => $Amount,
                'description'   => 'Kế toán nạp tiền cho khách hàng Boxme',
                'payment_method' => $Type,
                'transaction_id' => $Transaction,
                'reference_code' => $ReferCode,
                'status'         => 1,
                'created'        => date("Y-m-d H:i:s")
            ]);
        }catch(\Exception $e){
            return Response::json([
                'error'         => true,
                'code'          => 'INSERT_ERROR',
                'error_message' => 'Thêm mới thất bại '. $e->getMessage()
            ]);
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành Công'
        ]);
    }
}
