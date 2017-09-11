<?php

class OauthCtrl extends \BaseController {
    
    function getIndex(){
        echo 1;die;
    }
    
    function getAccess(){
        return ResourceServer::getClientId();
    }
    
    function postAccess(){
        return AuthorizationServer::performAccessTokenFlow();
    }
    
    function postTestxxx(){
        return Input::all();
    }
    
}
