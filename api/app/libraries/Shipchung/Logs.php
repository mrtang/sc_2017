<?php 
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Shipchung;

use ElephantIO\Client,
    ElephantIO\Engine\SocketIO\Version1X;


require(app_path().'/libraries/Shipchung/lib/elephant.io/vendor/autoload.php');


class Logs implements LogsInterface{
	private $Socket;

	function __construct(){
		if(empty($this->Socket)){
			$this->Socket = new Client(new Version1X('http://52.11.215.169:9988'));
			$this->Socket->initialize();
		}
	}
	function _write($prefix, $type, $eventName, $datas){
		$this->Socket->emit($eventName, [
				'data'   => $datas, 
				'prefix' => $prefix,
				'type'	 => $type,
				'time'   => time()
			]
		);
		
	}
	static function TrackQuery($prefix = 'system'){
		$LogsInstance = new Logs();
		\Event::listen('illuminate.query', function($query, $binding, $time, $name) use ($LogsInstance, $prefix) 
		{
		 	$LogsInstance->_write($prefix,'query', 'message', [$query, $binding, $time, $name]);
		});
	}

	static function Log($prefix, $datas){
		(new Logs())->_write($prefix, 'logs', 'message', $datas);

	}

}

?>