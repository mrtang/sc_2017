<?php namespace ordermodel;
use warehousemodel\StatisticReportProductModel;
use Eloquent;
class OrderItemModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'order_item' ;
    protected $connection       = 'orderdb';
    protected $guarded          = array();
    public    $timestamps       = false;
 
    public function Item(){
        return $this->belongsTo('ordermodel\ItemsModel','item_id');
    }   

    public function BMCheckBSINAvailableInStock ($order_id, $warehouse){
        $ListItems = OrderItemModel::where('order_id', $order_id)->get()->toArray();

        $ReturnData = [
            'available'         => true,
            'unavailable_bsin'  => [],
            'available_bsin'    => [],
            'list_item'         => $ListItems,
            'warehouse'         => $warehouse,
            'order_id'          => $order_id
        ];

        if(empty($ListItems)){

            $ReturnData['available'] = false;
            goto done;
        }
        
        $ListItemByBSIN = [];
        $ListBSINId     = [];

        foreach($ListItems as $item){
            if(!empty($item['bsin'])){
                $ListBSINId[]           = $item['bsin'];
                $ListItemByBSIN[$item['bsin']] = $item;
            }
        }

        if(empty($ListBSINId)){
            $ReturnData['available'] = false;
            goto done;
        }
        $Statistic  = StatisticReportProductModel::where('warehouse', $warehouse)->whereIn('sku', $ListBSINId)->get()->toArray();
        
        if(empty($Statistic)){
            $ReturnData['available'] = false;
            goto done;
        }

        foreach ($Statistic as $item){
            if(!empty($ListItemByBSIN[$item['sku']])){
                $OrderItem = $ListItemByBSIN[$item['sku']];

                if((int)($item['inventory'] - $item['inventory_wait']) < (int)$OrderItem['quantity']){
                    $ReturnData['available']    = false;
                    $ReturnData['iteem']        = $item;
                    $ReturnData['OrderItem']    = $OrderItem;
                    

                    $ReturnData['unavailable_bsin'][] = [
                        'sku'       => $item['sku'],
                        'quantity'  => $OrderItem['quantity'],
                    ];
                }else {
                    $ReturnData['available_bsin'][] = [
                        'sku'       => $item['sku'],
                        'quantity'  => $OrderItem['quantity'],
                    ];
                }
            }   
        }

        done:
            return $ReturnData;
    }
}
