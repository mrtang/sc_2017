<?php
use ordermodel\OrdersModel;
use metadatamodel\GroupStatusModel;
use Illuminate\Support\Facades\Response;
use metadatamodel\OrderStatusModel;
use sellermodel\UserInventoryModel;
use Maatwebsite\Excel\Facades\Excel;
use metadatamodel\GroupOrderStatusModel;
use Elasticsearch\Client;


class ElasticSearchController extends BaseController {


    /**
    * @desc  Suggest khách hàng khi tạo vận đơn 
    * @author ThinhNV <thinhnv@peacesoft.net>
    */

    public function getBuyers(){
        $size   = Input::has('size')    ?   (int)Input::get('size')     : 10;
        $q      = Input::has('q')       ?   Input::get('q')             : " ";

        $validation = Validator::make(Input::all(), array(
            'q'            => 'required'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'error_message' => $validation->messages()));
        }

        $user = $this->UserInfo();
        
        if(!isset($user['id']) || $user['id'] == 0){
            return Response::json([
                "error"             => true,
                "error_message"     => "Bạn không có quyền truy cập",
                "data"              => []
            ]);
        }
        $searchParams['index']  =   'buyers_suggestion';
        $searchParams['type']   =   'buyers';
        $searchParams['size']   =   $size;
        
        $searchParams['body']['query']['bool']['must'][] = [
            'match' => [
                'seller_id'=> $user['id']
            ]

        ];
        
        $searchParams['body']['query']['bool']['must'][] = [
            'wildcard'=> [
                'phone' => "*".$q."*"
            ]
        ];
        try {
            $result = Es::search($searchParams);
        } catch (Exception $e) {
            return Response::json([
                "error"             => true,
                "error_message"     => "Lỗi kết nối máy chủ, vui lòng thử lại sau",
                "message"           => $e->getMessage(),
                "data"              => []
            ]);
        }

        $_return = [];

        if($result['hits']['total'] > 0){
            $hits = $result['hits']['hits'];
            foreach ($hits as $key => $value) {
                $source = $value['_source'];
                if (!empty($source['phone'])) {
                    $source['phone_arr'] = explode(',', $source['phone']);
                    $source['phone_primary']     = $source['phone_arr'][0];
                }
                $_return[] = $source;
            }
        }


        return Response::json([
            "error"             => true,
            "error_message"     => "",
            "data"              => $_return
        ]);
    }


    /**
    * @desc  Suggest sản phẩm khi tạo vận đơn 
    * @author ThinhNV <thinhnv@peacesoft.net>
    */
    public function getItems(){
        $size   = Input::has('size')    ? (int)Input::get('size')       : 10;


        $user = $this->UserInfo();

        if(!isset($user['id']) || $user['id'] == 0){
            return Response::json([
                "error"             => true,
                "error_message"     => "Bạn không có quyền truy cập",
                "data"              => []
            ]);
        }


        try {
            $result = new \ElasticBuilder('bxm_orders', 'order_item');
            $result = $result->where('seller_id', $user['id'])->take(20)->get();
        } catch (Exception $e) {
            return Response::json([
                "error"             => true,
                "error_message"     => "Lỗi kết nối máy chủ, vui lòng thử lại sau",
                "data"              => []
            ]);
        }

        return Response::json([
            "error"             => true,
            "error_message"     => "",
            "data"              => $result
        ]);
    }

    public function getAddress(){
        $size   = Input::has('size')    ? (int)Input::get('size')     :   10;
        $q      = Input::has('q')       ? Input::get('q')               : "";

        $validation = Validator::make(Input::all(), array(
            'q'            => 'required'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'error_message' => $validation->messages()));
        }



        $user = $this->UserInfo();

        if(!isset($user['id']) || $user['id'] == 0){
            return Response::json([
                "error"             => true,
                "error_message"     => "Bạn không có quyền truy cập",
                "data"              => []
            ]);
        }

        $searchParams['type']          =   'sc_mobile_address';
        $searchParams['size']          =   $size;
        
        //$searchParams['body']['query']['query_string']['query'] = 'name:'.$q.' AND seller_id:'.$user['id'];
        
        $searchParams['body']['query']['query_string']['query'] = 'district_name:' . $q . ' OR city_name:' . $q;

        try {
            $result = Es::search($searchParams);
        } catch (Exception $e) {
            return Response::json([
                "error"             => true,
                "error_message"     => "Lỗi kết nối máy chủ, vui lòng thử lại sau",
                "data"              => [],
                'msg'               => $e->getMessage()
            ]);
        }

        return Response::json([
            "error"             => true,
            "error_message"     => "",
            "data"              => $result
        ]);
    }

    public function getLocation($synonyms, $city_id){
        $searchParams['type']          =   'location_synonyms';
        $searchParams['size']          =   20;
        $searchParams['body']['query']['query_string']['query'] = '(synonyms:' . $synonyms.")";

        if(!empty($city_id)){
             $searchParams['body']['query']['query_string']['query'] .= ' AND (city_id:'.$city_id.")";
         }


        try {
            $result = Es::search($searchParams);
        } catch (Exception $e) {
            return Response::json([
                "error"             => true,
                "error_message"     => "Lỗi kết nối máy chủ, vui lòng thử lại sau",
                "data"              => [],
                'msg'               => $e->getMessage()
            ]);
        }

        return $result['hits']['hits'];
    }

}