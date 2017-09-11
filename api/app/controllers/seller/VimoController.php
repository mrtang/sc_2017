<?php namespace seller;

use Validator;
use Response;
use Input;
use sellermodel\BankingModel;
use sellermodel\VimoModel;
use sellermodel\UserInfoModel;
use LMongo;
use Excel;

class VimoController extends \BaseController {

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
    

    function check_cc($number, $abc = false)
    {
        $number=preg_replace('/[^\d]/','',$number);
        if (preg_match('/^3[47][0-9]{13}$/',$number))
        {
            return 'American Express';
        }
        elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
        {
            return 'Diners Club';
        }
        elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
        {
            return 'Discover';
        }
        elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
        {
            return 'JCB';
        }
        elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
        {
            return 'MasterCard';
        }
        elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
        {
            return 'Visa';
        }
        else
        {
            return 'ATM';
        }
    }
	public function postCreate()
	{
		/**
        *  Validation params
        * */
        
        $UserInfo = $this->UserInfo();
        
        
        Validator::getPresenceVerifier()->setConnection('sellerdb');
        
        $validation = Validator::make(Input::json()->all(), array(
            'id'                => 'sometimes|numeric|exists:vimo_config,id'  ,
            'account_number'    => 'numeric|required',
            'account_name'      => 'required',
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => "Dữ liệu nhập vào không đúng, quý khách vui lòng kiểm tra lại"));
        }
        
        /**
         * Get Data 
         * */
         
        $Email              = Input::has('email')              ? Input::get('email')         : Input::json()->get('email');
        $BankCode           = Input::has('bank_code')       ? Input::get('bank_code')       : Input::json()->get('bank_code');
        $AccountName        = Input::has('account_name')    ? Input::get('account_name')    : Input::json()->get('account_name');
        $AccountNumber      = Input::has('account_number')  ? Input::get('account_number')  : Input::json()->get('account_number');
        $Atm                = Input::has('atm_image')       ? Input::get('atm_image')       : Input::json()->get('atm_image');
        $Cmnd_before        = Input::has('cmnd_before_image') ? Input::get('cmnd_before_image') : Input::json()->get('cmnd_before_image');
        $Cmnd_after         = Input::has('cmnd_after_image')  ? Input::get('cmnd_after_image')  : Input::json()->get('cmnd_after_image');
        $Code               = Input::has('code')                ? trim(strtoupper(Input::get('code')))                   : '';


        if(!empty($Email)){
            $User = \User::where('email', $Email)->first();
            if(!$User){
                return ['error' => true, 'message' => 'Email không hợp lệ', 'email'=> $Email, 'user'=>$User];
            }
            $UserId = $User->id;
        }else {
            $UserId = $UserInfo['id'];
            $User = \User::where('id', $UserId)->first();

            $userInfoModel      = \sellermodel\UserInfoModel::where('user_id', $UserId)->first(['layers_security']);
            if($userInfoModel->layers_security == 1){ // security layers on
                $Security = $this->__check_security($UserId, $Code, 4);
                if($Security['error']){
                    return Response::json($Security);
                }else{
                    $Security   = $Security['security'];
                }
            }
        }

        $DataCreate         = array('user_id' => $UserId);
        
        if($AccountNumber && (strlen($AccountNumber) > 20 || strlen($AccountNumber) < 15) ){
            return ['error' => true, 'message' => 'Số thẻ ngân hàng không hợp lệ.'];
        }
        if(empty($Atm)){
            return ['error' => true, 'message' => 'Vui lòng tải lên ảnh scan mặt trước của thẻ ATM.'];
        }

        if(empty($Cmnd_before) || empty($Cmnd_after)){
            return ['error' => true, 'message' => 'Vui lòng tải lên ảnh scan hai mặt của CMND.'];
        }

        /*$VimoModel = new VimoModel;
        $VimoModel = $VimoModel->where('user_id', $UserId)->first();

        if($VimoModel && $VimoModel->active == 1){

            return ['error' => true, 'message' => 'Tài khoản của bạn đã được xác thực, để cập nhật vui lòng hiện hệ bộ phận CSKH của Shipchung. Trân trọng !'];
        }*/

        $VerifyBankingModel = new \omsmodel\VerifyBankingModel;

        $refer_code         = 'SCV_'.md5($UserId.'_'.$this->time());
        $UserConfig         = \UserConfigTransportModel::where('transport_id',2)->where('user_id', (int)$UserId)->first();

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        
        try{
            $VerifyBankingModel->insert([
                'user_id'           => (int)$UserId,
                'received'          => !empty($Email) ? $Email : $User->email,
                'token'             => $refer_code,
                'bank_code'         => $BankCode,
                'account_name'      => $AccountName,
                'account_number'    => $AccountNumber,
                'atm_image'         => $Atm,
                'cmnd_before_image' => $Cmnd_before,
                'cmnd_after_image'  => $Cmnd_after,
                'ip'                => $ip,
                'time_create'       => $this->time(),
                'time_expired'      => $this->time() + 48*60*60,
                'actived'           => 0
            ]);

            if(isset($userInfoModel->layers_security) && $userInfoModel->layers_security == 1){
                $Security->active = 2;
                $Security->save();
            }
        }catch(Exception $e){
            return ['error' => true, 'message' => 'Lỗi kết nối dữ liệu, hãy thử lại!'];
        }

        return ['error' => false, 'message' => 'Thành công'];

	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
    public function getVerifyBank($token){

        $VerifyBankingModel = new \omsmodel\VerifyBankingModel;
        $Data               = $VerifyBankingModel::where('token', $token)->first();

        if(!$Data){
            return ['error' => true, 'message' => 'TOKEN_NOT_FOUND'];
        }else {
            if($Data->time_expired < $this->time()){
               return ['error' => true, 'message' => 'TOKEN_EXPIRED']; 
            }
            if($Data->actived == 1){
               return ['error' => true, 'message' => 'TOKEN_EXPIRED']; 
            }

            

            $VimoModel = new VimoModel;
            $VimoModel = $VimoModel->where('user_id',  $Data->user_id)->first();
            $Old = array();
            if(!$VimoModel){
                $VimoModel              = new VimoModel;
                $VimoModel->user_id     = $Data->user_id;
                $VimoModel->time_create = $this->time();
                
            }else {

                if($Data->time_create < $VimoModel->time_update){
                    return ['error' => true, 'message' => 'TOKEN_EXPIRED', 'code' => 'TIME_EXPIRED']; 
                }

                $Old = $VimoModel->toArray();
                $VimoModel->time_update = $this->time();
            }
            $Note = "";

            if($VimoModel->actived == 1){
                $Note = "Khách hàng cập nhật lại thông tin";
            }
            
            $VimoModel->bank_code         = $Data->bank_code;
            $VimoModel->account_name      = $Data->account_name;
            $VimoModel->account_number    = $Data->account_number;
            $VimoModel->atm_image         = $Data->atm_image;
            $VimoModel->cmnd_before_image = $Data->cmnd_before_image;
            $VimoModel->cmnd_after_image  = $Data->cmnd_after_image;
            $VimoModel->ip                = $Data->ip;
            $VimoModel->active            = 0;
            $VimoModel->time_accept       = 0;
            $VimoModel->delete            = 2;
            $VimoModel->note              = $Note;

            $result = $VimoModel->save();

            $New = $VimoModel->toArray();
            $Data->actived = 1;
            $Data->save();

            $User = UserInfoModel::where('user_id', $Data->user_id)->first();
            if($User && empty($User->email_nl) && $User->priority_payment != 1){
                $User->priority_payment = 1;
                $User->save();
            }

            if($result){
                try{
                    \LMongo::collection('log_verify_bank')->insert(array(
                        'old' => $Old,
                        'new' => $New
                    ));
                }catch(Exception $e){
                    return ['error' => true, 'message'  => 'INSERT_LOG_FAIL'];
                }
                return ['error' => false, 'message' => 'SUCCESS', "data"=> $VimoModel]; 
            }
            return ['error' => true, 'message' => 'ERROR', "data"=> ""]; 
        }

    }

    public function getU($id){
        $VerifyBankingModel = new \omsmodel\VerifyBankingModel;
        $Data = $VerifyBankingModel->orderBy('id', 'DESC')->where('user_id', $id)->get()->toArray();
        return Response::json($Data);
    }

    public function postUploadScanImg(){
        if (Input::hasFile('file')) {
            $File       = Input::file('file');
            $name       = $File->getClientOriginalName();
            $extension  = $File->getClientOriginalExtension();
            $MimeType   = $File->getMimeType();
            $Size       = $File->getClientSize();
            $name       = md5($this->time().$Size).'.'.$extension;

            if(!in_array($MimeType, array('image/jpeg','image/png','image/jpg'))){

                 return Response::json(array(
                    'error'         => true,
                    'error_message' => 'Tải lên không thành công.',
                    'data'          => ''

                ));
            }
            if(in_array(strtolower($extension), array('jpg','png'))){
                $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cards';
                $fullpath = DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cards'. DIRECTORY_SEPARATOR .$name;
                
                if($File->move($uploadPath, $name)){
                    return Response::json(array(
                        'error'         => false,
                        'error_message' => 'Tải lên thành công',
                        'data'          => $fullpath
                    ));
                }else {
                    return Response::json(array(
                        'error'         => true,
                        'error_message' => 'Tải lên không thành công.',
                        'data'          => ''
                    ));
                }
                
                
            }else {
                return Response::json(array(
                    'error'         => true,
                    'error_message' => 'Định dạng file không đúng.',
                    'data'          => ''
                ));
            }
        }
        return Response::json(array(
            'error'         => true,
            'error_message' => 'Tải lên không thành công.',
            'data'          => ''
        ));
    }

	public function getShow()
	{
	    $UserInfo   = $this->UserInfo();
        $id = (int)$UserInfo['id'];
        
		$Model      = new VimoModel;
        $Data       = $Model::where('user_id','=',$id)->where('delete', 2)->first();
        
        if($Data){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Data
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits',
                'data'      => array()
            );
        }
        
        return Response::json($contents);
	}

    public function getOmsVerify(){
        $email     = Input::has('email')           ? Input::get('email')             : '';
        $active    = Input::has('active')          ? Input::get('active')            : '';
        $from_date = Input::has('from_date')       ? Input::get('from_date')         : 0;
        $to_date   = Input::has('to_date')         ? Input::get('to_date')           : 0;
        $deleted   = Input::has('deleted')         ? (int)Input::get('deleted')           : 2;
        $page      = Input::has('page')            ? (int)Input::get('page')         : 1;
        $itemPage  = Input::has('item_page')       ? (int)Input::get('item_page')    : 20;
        $Cmd       = Input::has('cmd')             ? Input::get('cmd')          : "";
        $offset    = ($page - 1) * $itemPage;
        $Model  = new VimoModel;

        $UserInfo   = $this->UserInfo();

        if($UserInfo['privilege'] < 1){
            $contents = array(
                'error'     => true,
                'message'   => 'Bạn không có quyên truy cập'

            );
            return Response::json($contents);
        }
        
        $Model = $Model->where('delete', $deleted);
        if(!empty($email)){
            $user  = new \User;
            $user  = $user::where('email', $email)->first();
            $byId  = ($user) ? $user->id : 0;
            $Model = $Model->where('user_id', (int)$byId);
        }

        if(!empty($active)){
            $active = ($active == 1) ? $active : 0;

            $Model = $Model->where('active', (int)$active);
        }
        if($from_date > 0){
            $Model = $Model->where('time_accept','>=' , $from_date);
            /*$Model = $Model->where('time_create','>='  , $from_date - (90 * 86400));*/
        }

        if($to_date > 0){
            $Model = $Model->where('time_accept','<',$to_date);
        }
        if($Cmd == 'export'){
            return $this->ExportExcel($Model);
        }
        if($Total = $Model->count()){
            try {
                $Model = $Model->orderBy('atm_image', 'DESC')->with(['user'])->skip($offset)->take($itemPage)->get()->toArray();
            } catch (Exception $e) {
                return Response::json(['error'=> true, "error_message"=> "Lỗi truy vấn"]);
            }
        }else {
            return Response::json(["error"=> false, "error_message" => "Thành công", "data" => [], "total" => 0, 'item_page' => $itemPage]);
        }

        foreach ($Model as $key => $value) {
            if(!empty($value['account_number'])){
                $Model[$key]['account_type'] = $this->check_cc($value['account_number'], true);
            }
        }

        return Response::json(["error"=> false, "error_message" => "Thành công", "data" => $Model, "total" => $Total, 'item_page' => $itemPage]);
        
    }


    private function ExportExcel($Model){
        $FileName   = 'DANH_SACH_VIMO';

        $from_date = Input::has('from_date')       ? Input::get('from_date')         : 0;
        $to_date   = Input::has('to_date')         ? Input::get('to_date')           : 0;

        if(!empty($from_date)){
            $FileName .= '_TU_'.date("d-m-y",$from_date);
        }

        if(!empty($to_date)){
            $FileName .= '_DEN_'.date("d-m-y",$to_date);
        }       
        
        $Data = $Model->orderBy('atm_image', 'DESC')->with(['user'])->get()->toArray();
        
        
        return Excel::create($FileName, function($excel) use($Data){
            $excel->sheet('Sheet1', function($sheet) use($Data){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách xác thực vimo'));
                // Set multiple column formats
                
                $sheet->setWidth(array(
                    'A'     =>  10, 'B' =>  30, 'C'     =>  30, 'D'     =>  60, 'E'     =>  30, 'F'     =>  30, 'G'     =>  50,'H'     =>  50,
                    'I'  => 50,'J'  => 30,'K'  => 30,'L'  => 30
                ));

                $sheet->row(3, array(
                    'STT', 'Email', 'Số điện thoại', 'Ngân hàng', 'Loại thẻ', 'Tên chủ thẻ', 'Số thẻ', 'Ảnh mặt trước ATM', 'Ảnh mặt trước CMTND', 'Ảnh mặt sau CMTND', 'Tình trạng', 'Thời gian xác thực', 'Ghi chú'
                ));

                $sheet->row(3,function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:K3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
                    $dataExport = array(
                        $i++,
                        $val['user']['email'],
                        $val['user']['phone'],
                        !empty($this->vimo[$val['bank_code']]) ? $this->vimo[$val['bank_code']] : $val['bank_code'],
                        $this->check_cc($val['account_number'], true),
                        $val['account_name'],
                        ' '.(string)$val['account_number'],
                        !empty($val['atm_image']) ? 'http://cloud.shipchung.vn'.$val['atm_image'] : '',
                        !empty($val['cmnd_before_image']) ? 'http://cloud.shipchung.vn'.$val['cmnd_before_image'] : '',
                        !empty($val['cmnd_after_image']) ? 'http://cloud.shipchung.vn'.$val['cmnd_after_image'] : '',
                        $val['active'] == 1? 'Đã xác thực' : 'Chưa xác thực',
                        $val['time_accept'] > 0 ? date("d/m/y H:m",$val['time_accept']) : '',
                        $val['note'],
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');
    }
    public function postAccept(){
        $Id     = Input::json()->get('id');
        $Model  = new VimoModel;

        $UserInfo   = $this->UserInfo();

        if(empty($Id) || $UserInfo['privilege'] < 1){
            $contents = array(
                'error'     => true,
                'message'   => 'Bạn không có quyên truy cập'

            );
            return Response::json($contents);
        }
        try {
            $Model              = $Model::where('id', $Id)->first();
            $Log                = $Model->toArray();
            $Model->active      = 1;
            $Model->delete      = 2;
            $Model->time_accept = $this->time();
            $Model->save();

            \LMongo::collection('log_accept_vimo')->insert(array(
                'vimo_info'   => $Log,
                'id'          => $Id,
                'user_accept' => $UserInfo['id'],
                'time'        => $Model->time_accept,
                'action'      => 'ACCEPT'
            ));
        } catch (Exception $e) {
            $contents = array(
                'error'     => true,
                'message'   => 'Lỗi kết nối'

            );
            return Response::json($contents);   
        }

        $contents = array(
            'error'     => false,
            'message'   => 'Thành công'
        );
        return Response::json($contents); 
        
    }

    public function postUnaccept(){
        $Id       = Input::json()->get('id');     
        $Note     = Input::json()->has('note') ? Input::json()->get('note') : '';   
        $Model    = new VimoModel;
        
        $UserInfo = $this->UserInfo();

        if(empty($Id) || $UserInfo['privilege'] < 1){
            $contents = array(
                'error'     => true,
                'message'   => 'Bạn không có quyên truy cập'
            );
            return Response::json($contents);
        }
        try {
            $Model = $Model::where('id', $Id)->first();
            $Log   = $Model->toArray();
            $Model->active = 0;
            $Model->time_accept = 0;
            
            $Model->save();

            \LMongo::collection('log_accept_vimo')->insert(array(
                'vimo_info'   => $Log,
                'id'          => $Id,
                'user_accept' => $UserInfo['id'],
                'time'        => $this->time(),
                'note'        => $Note,
                'action'      => 'UNACCEPT'
            ));
        } catch (Exception $e) {
            $contents = array(
                'error'     => true,
                'message'   => 'Lỗi kết nối'

            );
            return Response::json($contents);   
        }

        $contents = array(
            'error'     => false,
            'message'   => 'Thành công'
        );
        return Response::json($contents); 
        
    }


    public function postDelete(){
        $Id       = Input::json()->get('id');
        $Note     = Input::json()->has('note') ? Input::json()->get('note') : '';

        $Model    = new VimoModel;
        $UserInfo = $this->UserInfo();

        if(empty($Id) || $UserInfo['privilege'] < 1){
            $contents = array(
                'error'         => true,
                'error_message' => 'Bạn không có quyên truy cập'
            );
            return Response::json($contents);
        }

        try {
            $Model         = $Model::where('id', $Id)->first();
            $Log           = $Model->toArray();
            $Model->delete = 1;
            $Model->notify = 0;
            $Model->save();

            \LMongo::collection('log_accept_vimo')->insert(array(
                'vimo_info'   => $Log,
                'id'          => $Id,
                'user_accept' => $UserInfo['id'],
                'time'        => $this->time(),
                'note'        => $Note,
                'action'      => 'DELETED'
            ));
        } catch (Exception $e) {
            $contents = array(
                'error'         => true,
                'error_message' => 'Lỗi kết nối'

            );
            return Response::json($contents);   
        }

        $contents = array(
            'error'     => false,
            'error_message'   => 'Thành công'
        );
        return Response::json($contents); 
        
    }

    public function postCreateNote(){
        $Id     = Input::json()->get('id');        
        $Note   = Input::json()->has('note') ? Input::json()->get('note')  : "";        
        $Model  = new VimoModel;

        $UserInfo   = $this->UserInfo();

        if(empty($Id) || $UserInfo['privilege'] < 1){
            $contents = array(
                'error'     => true,
                'message'   => 'Bạn không có quyên truy cập'

            );
            return Response::json($contents);
        }
        try {
            $Model = $Model::where('id', $Id)->first();
            $Log = $Model->toArray();
            $Model->note = $Note;
            $Model->notify = 0;
            $Model->save();

        } catch (Exception $e) {
            $contents = array(
                'error'     => true,
                'message'   => 'Lỗi kết nối'

            );
            return Response::json($contents);   
        }

        $contents = array(
            'error'     => false,
            'message'   => 'Thành công'
        );
        return Response::json($contents); 
        
    }

    public function getLogs($Id){
        $UserInfo   = $this->UserInfo();
        if(empty($Id) || $UserInfo['privilege'] < 1){
            $contents = array(
                'error'     => true,
                'message'   => 'Bạn không có quyên truy cập'

            );
            return Response::json($contents);
        }
        $LMongo       = new LMongo;
        $ListUserId   = [];
        $ListUserInfo = [];
        $Logs         = $LMongo::collection('log_accept_vimo')->where('vimo_info.id',(int)$Id)->get()->toArray();

        foreach ($Logs as $key => $value) {
            $ListUserId[] = $value['user_accept'];
        }
        if(!empty($ListUserId)){
            $UserModel = new \User;
            $Users     = $UserModel->whereIn('id', $ListUserId)->get(['id', 'fullname'])->toArray();

            foreach ($Users as $key => $value) {
                $ListUserInfo[$value['id']] = $value['fullname'];
            }
        }
        return Response::json([
            "error"         => false,
            "error_message" => "",
            "data"          => $Logs,
            "user"          => $ListUserInfo
        ]);
    }

}
