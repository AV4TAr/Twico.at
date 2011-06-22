<?php

class MyHashtagsController extends Zend_Controller_Action
{
    private $User = NULL;
    
    public function init()
    {
        $Singleton = App_Singleton::getInstance();
        $User =  $Singleton->getUserLogged();
        if(!$User) {
            $this->_redirect("/");
        } else {
            $this->User = $User;
        }
        
        //cambio el contexto si me piden cosas por json
        $contextSwitch = $this->_helper->contextSwitch();
        $contextSwitch->addActionContext('list', 'json')
                ->addActionContext('new', 'json')
                ->addActionContext('delete', 'json')
                    ->initContext();       
    }

    public function indexAction()
    {
        
    }
    
    public function ajaxAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        /**
        if($this->getRequest->isXmlHttpRequest()) {
            echo json_encode($someData);
        } else {
            echo 'This is the normal output';
        }
         */   
    
        $action = $this->getRequest()->getParam("action");
        $data = $this->getRequest()->getParam("data");
        
        switch($action){
            case "add":
                try{
                    $UHTTbl = new App_Db_UsersHashTags();
                    $newHastag = $UHTTbl->fetchNew();
                    $newHastag->tweeter_id = $this->User->tweeter_id;
                    $newHastag->hashtag = $data;
                    $newHastag->save();
                    $message = 'ok';
                } catch (Exception $e){
                    echo $e->getMessage();
                    die();
                }
                
                break;
            case "del":
                break;
        }
        
        $this->view->message = "ok";
    }
    
    /**
     * List items
     */
    public function listAction()
    {
        $UHT = new App_Db_UsersHashTags();
        $myHashTags = $UHT->fetchAll("twitter_id=".$this->User->twitter_id, "hashtag ASC");
        $this->view->myHashTags = $myHashTags->toArray();
    }
    
    public function newAction()
    {
        if($this->getRequest()->isPost() && $this->getRequest()->getParam('new_hash_tag')){
            try {
                $new_hash_tag = $this->getRequest()->getParam('new_hash_tag');
                $new_hash_tag = str_replace("#", "", $new_hash_tag);
                $UHTTbl = new App_Db_UsersHashTags();
                $newHastag = $UHTTbl->fetchNew();
                $newHastag->twitter_id = $this->User->twitter_id;
                $newHastag->hashtag = $new_hash_tag;
                $newHastag->save();
                $this->view->new_hashtag = $newHastag->toArray();
            } catch (Exception $e){
                if($e->getCode() == '23000'){
                    $this->view->error = "Hashtag already inserted...";
                } else {
                    $this->view->error = $e->getMessage();
                }
            }
        } else {
            $this->view->error="An error has ocurred";
        }
    }
    
    public function deleteAction(){
         if($this->getRequest()->isPost() && $this->getRequest()->getParam('delete_hash_tag')){
            try {
                $delete_hash_tag = $this->getRequest()->getParam('delete_hash_tag');
                $UHTTbl = new App_Db_UsersHashTags();
                $Hashtag = $UHTTbl->fetchRow("id=".$delete_hash_tag);
                if($Hashtag){
                    $Hashtag->delete();
                } else {
                    $this->view->error = "Invalid hash tag...";
                }
             } catch (Exception $e){
                $this->view->error = $e->getMessage();
             }
        } else {
            $this->view->error="An error has ocurred";
        }
    }
}
