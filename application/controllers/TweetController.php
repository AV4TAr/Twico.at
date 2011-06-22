<?php

class TweetController extends Zend_Controller_Action
{
    public function __init(){
        //cambio el contexto si me piden cosas por json
        $contextSwitch = $this->_helper->contextSwitch();
        $contextSwitch->addActionContext('post', 'json')
                      ->initContext();
    }
    
    public function postAction(){
        $text = $this->getRequest()->getParam("text");
        $Singleton = App_Singleton::getInstance();
        $User = $Singleton->getUserLogged();
        $status = "error";
        if($text) {
            try {
                $status= stripslashes($text);
                App_Service_Twitter::updateUserStatus($User->id, $status);
                $status = "ok";
            } catch (Exception $e) {
                $status = "error";
            }
        } else {
            $Json = new App_Json('error', 'Data is missing.');
        }
        $this->view->status = $status;
    }
}