<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
        $Singleton = App_Singleton::getInstance();
        $User =  $Singleton->getUserLogged();
        if(!$User) {
            $this->_forward("index-not-logged");
        }
    }

    public function indexNotLoggedAction()
    {
        $this->_helper->layout->disableLayout ();
        // action body
    }
}

