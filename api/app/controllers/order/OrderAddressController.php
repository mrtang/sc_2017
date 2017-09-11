<?php namespace order;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use ordermodel\OrdersModel;
use ordermodel\AddressModel;
use CityModel;
use DistrictModel;
class OrderAddressController extends \BaseController {

    public function getIndex() {
        $itemsPerPage = Input::has('itemPerPage') ? Input::get('itemsPerPage') : 20;
        $currentPage    =   Input::has('currentPage') ? Input::get('currentPage') : 1;
        $fromDate = strtotime(date("Y-m-d")." 00:00:00");
        $toDate = strtotime(date("Y-m-d")." 23:59:59");
        $listAddressID = OrdersModel::where('time_create','>=',$fromDate)->where('time_create','<=',$toDate)->lists('to_address_id');
        $listAddressID[] = 0;
        $addressModel = AddressModel::whereIn('id',$listAddressID);
        $totalAddress = $addressModel->count();

        $listAddress = $addressModel->take($itemsPerPage)->skip($itemsPerPage*($currentPage-1))->get();

        if(!$listAddress->isEmpty()) {
            $listCityID = $listDistrictID = [];
            foreach($listAddress as $oneAddress) {
                $listCityID[] = $oneAddress->city_id;
                $listDistrictID[] = $oneAddress->province_id;
            }

            $city = CityModel::whereIn('id',$listCityID)->get();
            $district = DistrictModel::whereIn('id',$listDistrictID)->get();

            $listCity = $listDistrict = [];
            if(!$city->isEmpty()) {
                foreach($city as $oneCity) {
                    $listCity[$oneCity->id]  = $oneCity->city_name;
                }
            }
            if(!$district->isEmpty()) {
                foreach($district as $oneDistrict) {
                    $listDistrict[$oneDistrict->id] = $oneDistrict->district_name;
                }
            }

            foreach($listAddress as $k => $oneAddress) {
                $listAddress[$k]->city_name = isset($listCity[$oneAddress->city_id]) ? $listCity[$oneAddress->city_id] : '';
                $listAddress[$k]->district_name= isset($listDistrict[$oneAddress->district_id]) ? $listDistrict[$oneAddress->province_id] : '';
            }
            return Response::json([
                'status'    =>  true,
                'data'      =>  $listAddress,
                'total'     =>  $totalAddress
            ]);
        } else {
            return Response::json([
                'status'    =>  false,
                'message'   =>  'Không có dữ liệu'
            ]);
        }

    }

    public function getExport() {

        $fromDate = strtotime(date("Y-m-d")." 00:00:00");
        $toDate = strtotime(date("Y-m-d")." 23:59:59");

        $listAddressID = OrdersModel::where('time_create','>=',$fromDate)->where('time_create','<=',$toDate)->lists('to_address_id');
        $listAddressID[] = 0;
        $addressModel = AddressModel::whereIn('id',$listAddressID);

        $listAddress = $addressModel->get();

        if(!$listAddress->isEmpty()) {
            $listCityID = $listDistrictID = [];
            foreach ($listAddress as $oneAddress) {
                $listCityID[] = $oneAddress->city_id;
                $listDistrictID[] = $oneAddress->province_id;
            }

            $city = CityModel::whereIn('id', $listCityID)->get();
            $district = DistrictModel::whereIn('id', $listDistrictID)->get();

            $listCity = $listDistrict = [];
            if (!$city->isEmpty()) {
                foreach ($city as $oneCity) {
                    $listCity[$oneCity->id] = $oneCity->city_name;
                }
            }
            if (!$district->isEmpty()) {
                foreach ($district as $oneDistrict) {
                    $listDistrict[$oneDistrict->id] = $oneDistrict->district_name;
                }
            }

            foreach ($listAddress as $k => $oneAddress) {
                $listAddress[$k]->city_name = isset($listCity[$oneAddress->city_id]) ? $listCity[$oneAddress->city_id] : '';
                $listAddress[$k]->district_name = isset($listDistrict[$oneAddress->district_id]) ? $listDistrict[$oneAddress->province_id] : '';
            }

            return Excel::create('Dia_Chi', function ($excel) use($listAddress) {
                $excel->sheet('Địa chỉ', function ($sheet) use($listAddress) {
                    $sheet->mergeCells('C1:F1');
                    $sheet->row(1, function ($row) {
                        $row->setFontSize(20);
                    });
                    $sheet->row(1, array('','','Địa chỉ'));
                    // set width column
                    $sheet->setWidth(array(
                        'A'     => 5,
                        'B'     => 20,
                        'C'     => 25,
                        'D'     => 15,
                        'E'     => 40,
                        'F'     => 30,
                        'G'     => 20,
                        'H'     => 30,
                        'I'     => 30,
                        'J'     => 30,
                        'K'     => 30,
                        'L'     => 30,
                        'M'     => 30,
                        'N'     => 30,
                        'O'     => 30,
                        'P'     => 30,
                        'Q'     => 30,
                        'R'     => 30,
                        'S'     => 30
                    ));
                    // set content row
                    $sheet->row(3, array(
                        'STT', 'Địa chỉ', 'Tỉnh thành', 'Quận huyện'
                    ));
                    $sheet->row(3,function($row){
                        $row->setBackground('#B6B8BA');
                        $row->setBorder('solid','solid','solid','solid');
                        $row->setFontSize(12);
                    });
                    //
                    $i = 1;
                    foreach ($listAddress AS $value) {
                        $dataExport = array(
                            'STT' => $i++,
                            'Địa chỉ' => $value['address'],
                            'Tỉnh thành' => $value['city_name'],
                            'Quận huyện' => $value['district_name']
                        );
                        $sheet->appendRow($dataExport);
                    }
                });
            })->export('xls');

        } else {
            return "Không có dữ liệu";
        }
    }
}