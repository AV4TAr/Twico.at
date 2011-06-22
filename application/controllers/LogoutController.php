<?php

class LogoutController extends Zend_Controller_Action
{
    public function __init(){
        
    }
    
    public function indexAction(){
        //Login out the user
        $Singleton = App_Singleton::getInstance();
        $User =  $Singleton->getUserLogged();
        if($User) {
            $Singleton->logout();
            //Logint out the User
        }
        $this->_redirect("/");
        
    }
}