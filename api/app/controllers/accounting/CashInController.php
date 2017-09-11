<?php namespace accounting;

use accountingmodel\CashInModel;

class CashInController extends BaseCtrl {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
    public function getIndex()
    {
        $page                   = Input::has('page')                    ? (int)Input::get('page')                   : 1;
        $itemPage               = Input::has('limit')                   ? Input::get('limit')                       : 20;
        $TimeStart              = Input::has('time_start')              ? trim(Input::get('time_start'))            : '';
        $TimeEnd                = Input::has('time_end')                ? trim(Input::get('time_end'))              : '';
        $FirstShipmentStart     = Input::has('first_shipment_start')    ? trim(Input::get('first_shipment_start'))  : '';
        $KeyWord                = Input::has('keyword')                 ? trim(Input::get('keyword'))               : '';

        $Model = new CashInModel;

        if(!empty($TimeStart)){
            $Model = $Model->where('time_accept','>=',$TimeStart);
        }

        if(!empty($TimeEnd)){
            $Model = $Model->where('time_accept','<',$TimeEnd);
        }

        if(!empty($KeyWord)){
            $ModelUser  = new \User;
            if (filter_var($KeyWord, FILTER_VALIDATE_EMAIL)){  // search email
                $ModelUser          = $ModelUser->where('email','LIKE','%'.$KeyWord.'%');
            }elseif(filter_var((int)$KeyWord, FILTER_VALIDATE_INT,array('option'=>array('min_range'=>8,'max_range'=>20)))){  // search phone
                $ModelUser          = $ModelUser->where('phone','LIKE','%'.$KeyWord.'%');
            }else{ // search code
                $ModelUser          = $ModelUser->where('fullname','LIKE','%'.$KeyWord.'%');
            }
            $ListUser = $ModelUser->lists('id');

            if(empty($ListUser)){
                return Response::json([
                    'error'         => false,
                    'code'          => 'SUCCESS',
                    'message_error' => 'Thành công',
                    'item_page'     => $itemPage,
                    'total'         => 0,
                    'data'          => []
                ]);
            }

            $Model  = $Model->whereIn('merchant_id',$ListUser);
        }

        if(!empty($FirstShipmentStart)){
            $ListUser = $this->__get_user_boxme($FirstShipmentStart);
            if(empty($ListUser)){
                return $this->ResponseData();
            }
            $Model  = $Model->whereIn('merchant_id', $ListUser);
        }

        $ModelTotal = clone $Model;

        $Total = $ModelTotal->count();
        $Data  = array();

        $Model = $Model->orderBy('time_accept','DESC');
        if($Total > 0){
            if((int)$itemPage > 0){
                $itemPage   = (int)$itemPage;
                $offset     = ($page - 1)*$itemPage;
                $Model       = $Model->skip($offset)->take($itemPage);
            }

            $Data = $Model->with('User')->get()->toArray();
        }

        return Response::json([
            'error'         => false,
            'code'          => 'SUCCESS',
            'message_error' => 'Thành công',
            'item_page'     => $itemPage,
            'total'         => $Total,
            'data'          => $Data
        ]);
    }
}
