<?php namespace warehousemodel;
use ordermodel\OrderItemModel;
use LMongo;

class StatisticReportProductModel extends \Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'statistics_reportproduct';
    protected $connection       = 'warehousebm';
    protected $guarded          = array();
    public    $timestamps       = false;

    static function PlusInventoryWait($bsin, $quantity, $warehouse, $Order = []){
        $LMongo     = new LMongo;
        $LogModel   = $LMongo::collection('log_report_inventory');
        $InsertData = [
            'bsin'      => $bsin,
            'quantity'  => $quantity,
            'warehouse' => $warehouse,
            'type'      =>'plus',
            'status'    => 1,
            'time'      => time(),
            'order'     => $Order
        ];

        try{
            $Item = StatisticReportProductModel::where('sku', $bsin)->where('warehouse', $warehouse)->first();
            $InsertData['old'] = $Item;
            $Item->inventory_wait += (int) $quantity;
            $Item->save();
        }catch(\Exception $e){
            $InsertData['status'] = 2;
        }

        $InsertData['new'] = $Item;
        
        try{
            $LogModel->insert($InsertData);
        }catch(\Exception $e){
            return false;
        }
        
        return true;

    }

    static function MinusInventoryWait($bsin, $quantity, $warehouse){
        $LMongo     = new LMongo;
        $LogModel   = $LMongo::collection('log_report_inventory');
        $InsertData = [
            'bsin'      => $bsin,
            'quantity'  => $quantity,
            'warehouse' => $warehouse,
            'type'      =>'minus',
            'status'    => 1,
            'time'      => time()
        ];

        try{
            $Item = StatisticReportProductModel::where('sku', $bsin)->where('warehouse', $warehouse)->first();
            $InsertData['old']      = $Item;
            $Item->inventory_wait   -= (int) $quantity;
            $Item->inventory        += (int) $quantity;
            

            if($Item->inventory_wait < 0){
                $InsertData['status'] = 3;
            }

            $Item->save();
        }catch(\Exception $e){
            $InsertData['status'] = 2;
        }

        $InsertData['new'] = $Item;
        

        try{
            $LogModel->insert($InsertData);
        }catch(\Exception $e){
            return false;
        }
        
        return true;
    }
    
}
