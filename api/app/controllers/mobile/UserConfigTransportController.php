<?php namespace mobile;
use Response;
use Input;
class UserConfigTransportController extends \BaseController {
    private $domain = '*';

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
    const TRANSPORT_EMAIL = 2;
    const TRANSPORT_APP   = 5;

    public function __construct(){
        
    }
    //
    public function getIndex($json = true)
	{
        $UserInfo     = $this->UserInfo();
		$Model        = new \UserConfigTransportModel;
        $Model        = $Model->where('user_id', $UserInfo['id'])->get()->toArray();

        $ReturnData   = [
            'email'     => 0,
            'app'       => 0
        ];

        foreach ($Model as $key => $value) {
            if($value['transport_id'] == self::TRANSPORT_EMAIL){
                $ReturnData['email'] = $value['active'];
            }
            if($value['transport_id'] == self::TRANSPORT_APP){
                $ReturnData['app'] = $value['active'];
            }
        }

        $contents = array(
            'error'                 => false,
            'error_message'         => '',
            'data'                  => $ReturnData
        );

        if($json == false){
            return $ReturnData;
        }

        return Response::json($contents);
	}

    public function postUpdate(){
        $UserInfo     = $this->UserInfo();
        $Model        = new \UserConfigTransportModel;

        $Email      = Input::has('email') ? (int)Input::get('email') : '';
        $App        = Input::has('app')   ? (int)Input::get('app')   : '';
        $IosToken   = Input::has('ios_token')   ? Input::get('ios_token')   : '';

        $Data = $this->getIndex(false);

        
        if(gettype($Email) == 'integer'){
            $Model::where('user_id', $UserInfo['id'])->where('transport_id', self::TRANSPORT_EMAIL)->update(['active'=> $Email]);
            $Data['email'] = $Email;
        }

        if(gettype($App) == 'integer'){
            $Model::where('user_id', $UserInfo['id'])->where('transport_id', self::TRANSPORT_APP)->update(['active'=> $App]);
            $Data['app'] = $App;
        }

        if(!empty($IosToken)){
            $_UserInfo = \sellermodel\UserInfoModel::where('user_id', $UserInfo['id'])->first();
            
            if($_UserInfo){
                $_UserInfo->ios_device_token = $IosToken;
                $_UserInfo->save();
            }
        }
        

        $contents = array(
            'error'                 => false,
            'error_message'         => '',
            'data'                  => $Data
        );
        return Response::json($contents);


    }

}
?>