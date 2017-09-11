<?php

class AsteriskCDRModel extends Eloquent{

    protected $table        = 'cdr' ;
    protected $connection   = 'freepbx';
    protected $guarded      = array();
    public    $timestamps   = false;

}
