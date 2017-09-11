<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Shipchung;

interface LogsInterface {
    function _write($prefix, $type, $eventName, $datas);
    static function Log($prefix, $datas);
}



?>