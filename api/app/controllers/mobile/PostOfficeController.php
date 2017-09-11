<?php
namespace mobile;

use DB;
use Input;
use Response;
use CourierPostOfficeModel;
use CourierModel;
use Excel;
use Guzzle\Http\Client;

class PostOfficeController extends \BaseController
{

    public function __construct()
    {
    }

    public function getRequestGooogle($address = '')
    {
        $client = new Client('http://maps.googleapis.com/maps/api/geocode/json');
        $params = array(
            'address' => $address,
            'sensor' => false
        );

        $client->setDefaultOption('query', $params);
        $params = $client->getDefaultOption('query');
        $request = $client->get();
        $response = $request->send()->json();
        $LatLng = [];
        if (sizeof($response['results']) > 0) {
            $Location = $response['results'][0]['geometry']['location'];
            $LatLng = [
                'lat' => $Location['lat'],
                'lng' => $Location['lng'],
            ];
        }
        return $LatLng;
    }

    public function getSyncLatlng()
    {
        $Model = new CourierPostOfficeModel;
        $Model = $Model->where('lat', 0)->where('lng', 0)->take(50);
        $Data = $Model->get();

        if ($Data->isEmpty()) {
            return 'DONE';
        }

        foreach ($Data as $key => $value) {

            $address = $value->address;
            $LatLng = $this->getRequestGooogle($address);

            if (!empty($LatLng)) {
                $value->lat = $LatLng['lat'];
                $value->lng = $LatLng['lng'];
                try {
                    $value->save();
                } catch (Exception $e) {
                    return 'ERROR_' . $e->getMessage();
                }
            } else {
                //return Response::json($value);
            }
        }
        return 'CONTINUE';

    }

    public function getFindAround()
    {
        $Lat = Input::has('lat') ? Input::get('lat') : 0;
        $Lng = Input::has('lng') ? Input::get('lng') : 0;
        $Radius = Input::has('radius') ? Input::get('radius') : 2;

        $Cmd = Input::has('cmd') ? Input::get('cmd') : "";

        if (empty($Lng) || empty($Lat)) {
            return Response::json([
                'error' => true,
                'error_message' => 'Vui lòng nhập vị trí hiện tại của bạn '
            ]);
        }
        if (!empty($Cmd)) {
            $Data = [
                [
                    "id" => 1,
                    "bccode" => "TRN",
                    "name" => "Tràng Tiền",
                    "city_code" => "HNI",
                    "level" => 0,
                    "department_code" => "HNI",
                    "address" => "Số 18 - Tam Trinh - HBT - Hà Nội",
                    'city_id' => 18,
                    'district_id' => 173,
                    'phone' => '048283293',
                    'courier_id' => 1,
                    'courier_name' => 'Viettel Post',
                    "ttkt_code" => "TTKT1",
                    "lat" => 20.0364417,
                    "lng" => 105.8194542,
                    "distance" => 2.0123474614971
                ],
                [
                    "id" => 1,
                    "bccode" => "TCO",
                    "name" => "Thành Cổ",
                    "city_code" => "QTI",
                    "level" => 0,
                    "department_code" => "QTI",
                    "ttkt_code" => "TTKT2",
                    "address" => "Số 19 - Thành Cổ - HBT - Hà Nội",
                    'city_id' => 18,
                    'district_id' => 173,
                    'courier_id' => 2,
                    'courier_name' => 'VietnamPost',
                    'phone' => '0948581923',
                    "lat" => 20.09566,
                    "lng" => 105.8518,
                    "distance" => 4.0123474614971
                ],
                [
                    "id" => 1,
                    "bccode" => "TCO",
                    "name" => "Thành Cổ",
                    "city_code" => "QTI",
                    "level" => 0,
                    "department_code" => "QTI",
                    "ttkt_code" => "TTKT2",
                    "address" => "Số 19 - Thành Cổ - HBT - Hà Nội",
                    'city_id' => 18,
                    'district_id' => 173,
                    'courier_id' => 2,
                    'courier_name' => 'VietnamPost',
                    'phone' => '0948581923',
                    "lat" => 20.92566,
                    "lng" => 105.8318,
                    "distance" => 4.0123474614971
                ],
                [
                    "id" => 1,
                    "bccode" => "TCO",
                    "name" => "Thành Cổ",
                    "city_code" => "QTI",
                    "level" => 0,
                    "department_code" => "QTI",
                    "ttkt_code" => "TTKT2",
                    "address" => "Số 19 - Thành Cổ - HBT - Hà Nội",
                    'city_id' => 18,
                    'district_id' => 173,
                    'courier_id' => 2,
                    'courier_name' => 'VietnamPost',
                    'phone' => '0948581923',
                    "lat" => 20.99566,
                    "lng" => 104.8118,
                    "distance" => 4.0123474614971
                ],
                [
                    "id" => 1,
                    "bccode" => "TCO",
                    "name" => "Thành Cổ",
                    "city_code" => "QTI",
                    "level" => 0,
                    "department_code" => "QTI",
                    "ttkt_code" => "TTKT2",
                    "address" => "Số 19 - Thành Cổ - HBT - Hà Nội",
                    'city_id' => 18,
                    'district_id' => 173,
                    'courier_id' => 2,
                    'courier_name' => 'VietnamPost',
                    'phone' => '0948581923',
                    "lat" => 21.19566,
                    "lng" => 105.8518,
                    "distance" => 4.0123474614971
                ],
                [
                    "id" => 1,
                    "bccode" => "TCO",
                    "name" => "Thành Cổ",
                    "city_code" => "QTI",
                    "level" => 0,
                    "department_code" => "QTI",
                    "ttkt_code" => "TTKT2",
                    "address" => "Số 19 - Thành Cổ - HBT - Hà Nội",
                    'city_id' => 18,
                    'district_id' => 173,
                    'courier_id' => 2,
                    'courier_name' => 'VietnamPost',
                    'phone' => '0948581923',
                    "lat" => 22.94566,
                    "lng" => 105.8518,
                    "distance" => 4.0123474614971
                ],
                [
                    "id" => 1,
                    "bccode" => "TCO",
                    "name" => "Thành Cổ",
                    "city_code" => "QTI",
                    "level" => 0,
                    "department_code" => "QTI",
                    "ttkt_code" => "TTKT2",
                    "address" => "Số 19 - Thành Cổ - HBT - Hà Nội",
                    'city_id' => 18,
                    'district_id' => 173,
                    'courier_id' => 2,
                    'courier_name' => 'VietnamPost',
                    'phone' => '0948581923',
                    "lat" => 21.19566,
                    "lng" => 105.8518,
                    "distance" => 4.0123474614971
                ]
            ];
            return Response::json([
                'error'         => false,
                'error_message' => 'Thành công',
                'data'          => $Data
            ]);
        }

        $Model = new CourierPostOfficeModel;
        $Model = $Model->select('*', DB::raw("ROUND(3959 * acos (cos ( radians($Lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($Lng) ) + sin ( radians($Lat) ) * sin( radians( lat ))), 2) AS distance"));
        $Data = $Model
            ->having('distance', '<=', $Radius)
            ->where('courier_id', '!=', 2)
            ->where('lat', '>', 0)
            ->where('lng', '>', 0)
            ->where('ward_id', '>', 0)
            ->orderBy('distance', 'ASC')
            ->take(10)
            ->get()->toArray();
        if (!empty($Data)) {

            $Courier = $this->getCourier();

            foreach ($Data as $key => $value) {
                $Data[$key]['courier_name'] = "";
                if (!empty($Courier[$value['courier_id']])) {
                    $Data[$key]['courier_name'] = $Courier[$value['courier_id']];
                }
            }
        }
        return Response::json([
            'error' => false,
            'error_message' => 'Thành công',
            'data' => $Data
        ]);
    }
}


?>