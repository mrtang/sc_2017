<?php 
    use ordermodel\OrdersModel;
    use PhpAmqpLib\Connection\AMQPStreamConnection;
    use PhpAmqpLib\Connection\AMQPConnection;
    use PhpAmqpLib\Message\AMQPMessage;

	class ModelObserve extends \BaseController{

		function __construct(){
			$this->ObserveModel();
		}


    	private function ObserveModel(){

            OrderOrdersModel::updating(function ($Orders){
                $Orders->is_changed = 1;
            });

            OrdersModel::updating(function ($Orders){
                $Orders->is_changed = 1;
            });

            OrderOrdersModel::saving(function ($Orders){
                $Orders->is_changed = 1;
            });

            OrdersModel::saving(function ($Orders){
                $Orders->is_changed = 1;
            });
            

            
            OrderOrdersModel::updated(function ($Orders){
                //$this->PushSyncElasticsearch('bxm_orders', 'orders', 'updated', $Orders);
            });

            OrderOrdersModel::created(function ($Orders){
                //$this->PushSyncElasticsearch('bxm_orders', 'orders', 'created', $Orders);
/*                echo '<b>Total Execution Time:</b> '.$execution_time.' Mins';
*/
            });

            OrdersModel::updated(function ($Orders){
                //$this->PushSyncElasticsearch('bxm_orders', 'orders', 'updated', $Orders);
            });
            
            OrdersModel::created(function ($Orders){
                //$this->PushSyncElasticsearch('bxm_orders', 'orders', 'created', $Orders);
            });
            
        }

    }

?>