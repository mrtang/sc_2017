<?php namespace api;

use omsmodel\StatisticModel;
use ordermodel\OrdersModel;

class ApiOrderCtrl extends \BaseController {

    public function getReport() {
        $timeStart = strtotime(date("Y-m-d 00:00:00"));
        $InTime = $this->time() - 30*86400;

        //đơn hàng phát sinh trong ngày
        //đơn hàng duyệt trong ngày
        $orderPSTN = $orderDTN = 0;

        $Model  = OrdersModel::where(function($query) {
            $query->where('time_accept','>=', $this->time() - 8035200)
                ->orWhere('time_accept',0);
        });

        $Order = $Model->get();
        if(!$Order->isEmpty()) {
            foreach($Order as $OneOrder) {
                ++$orderPSTN;
                if($OneOrder->time_accept >= $timeStart) {
                    ++$orderDTN;
                }
            }
        }
        //lấy thành công
        $ModelLTC = clone  $Model;
        $orderLTC = $ModelLTC->where('status',36)->where('time_pickup','>=',$timeStart)->count();
        //đơn hàng hủy
        $ModelHuy = clone  $Model;
        $orderHuy = $ModelHuy->whereIn('status',[22,23,24,25])->where('time_update','>=',$timeStart)->count();
        //giao hàng thành công
        $ModelGHTC = clone  $Model;
        $orderGHTC = $ModelGHTC->where('status',52)->where('time_update','>=',$timeStart)->count();
        //đang giao hàng
        $ModelDGH = clone  $Model;
        $orderDGH = $ModelDGH->where('status',51)->where('time_pickup','>=',$timeStart)->count();
        //đang chuyển hoàn
        $ModelDCH = clone  $Model;
        $orderDCH = $ModelDCH->whereIn('status',[62,63,64,65])->where('time_update','>=',$timeStart)->count();
        //chờ xác nhận chuyển hoàn
        $ModelCXNCH = clone  $Model;
        $orderCXNCH = $ModelCXNCH->where('status',60)->where('time_update','>=',$timeStart)->count();

        $statistic = StatisticModel::firstOrNew(['time_create'   =>  $timeStart]);
        $statistic->order_pstn = $orderPSTN;
        $statistic->order_dtn = $orderDTN;
        $statistic->order_ltc = $orderLTC;
        $statistic->order_huy = $orderHuy;
        $statistic->order_ghtc = $orderGHTC;
        $statistic->order_dgh = $orderDGH;
        $statistic->order_dch = $orderDCH;
        $statistic->order_cxnch = $orderCXNCH;
        $statistic->save();
        return "Cap nhat thanh cong";
    }
}