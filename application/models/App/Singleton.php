<?php
/**
 * Description of App_Singleton
 * @author diego
 */
class App_Singleton {
    private static $instance;
    public static $APP_CONFIG = array();
    public static $logger = null;

    const CRYPT_KEY = 'twico.._2011';
    
    public static function cryptme($text){
        $encrypted = crypt ( $text, self::CRYPT_KEY );
        return $encrypted;
    }
    /**
     * getInstance
     * Retrives the singleton
     * @return App_Singleton
     * @access public
     */
    public static function getInstance() {
        if (! self::$instance instanceof self) {
            /*loads application.ini into $APP_CONFIG*/
            //$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            //self::$APP_CONFIG = $bootstrap->getOptions();

            //$logger = new Zend_Log();
            //$writer = new Zend_Log_Writer_Stream('logs.txt');
            //$logger->addWriter($writer);

            //self::$logger = $logger;

            self::$instance = new self ( );
        }
        return self::$instance;
    }
    /**
     * getConfig
     * @return $APP_CONFIG
     */
    public function getConfig() {
        return self::$APP_CONFIG;
    }
    
    static function setDefaultConfig(Zend_Config $config){
        self::$APP_CONFIG = $config->toArray();
    }
    /**
     * Gets logged in user
     * @return App_DB_User
     */
    public function getUserLogged(){
        $session = new Zend_Session_Namespace('Auth');
        if(isset($session->User)){
            $UT = new App_Db_Users();
            //make te row RW (if not Read Only)
            try {
                $session->User->setTable($UT);
            } catch(Zend_Db_Table_Row_Exception $e) {
                $this->logout();
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
		        $redirector->gotoUrl("/");
            }
            return $session->User;
        }

        $uLoginCookie = App_Cookie::getCookie ("ulogin");
        $uLoginHashCookie = App_Cookie::getCookie ("uloginhash");

        if(isset($uLoginCookie) && isset($uLoginHashCookie)) {
            $UT = new App_Db_Users();
            $User = $UT->fetchRow("id='".$uLoginCookie."'");
            if($User) {
                $loginhash = self::cryptme($User->id);
                if($loginhash == $uLoginHashCookie) {                 
                    $UserTokens = App_Service_Twitter::getUserTokens($User->id);
                    
                    $session->user_id = $User->id;
                    $session->user_name = $User->name;
                    if($UserTokens) {
                        $session->twitter_id = $UserTokens["twitter_id"];
                    }
                    $session->User = $User;
                    $session->loggedin_at = mktime();
                    return $User;
                }
            } else {
                $this->logout();
            }
        }
        return false;
    }
	/**
     * isTwitterConnected
     * @return int or false
     */
    public function isTwitterConnected($debug=false){
        //$User = $this->getUserLogged();
        $session = new Zend_Session_Namespace('Auth');
        if(isset($session->twitter_id) && !empty($session->twitter_id)){
            return $session->twitter_id;
        } else {
            $User = $this->getUserLogged();
            $UserTokens = App_Service_Twitter::getUserTokens($User->id);
            if($UserTokens) {
                return $UserTokens["twitter_id"];
            }
        }
        if($debug==1) {
            var_dump($session);
        }
        return false;
    }
    /**
     * isLogged
     * @return User or false
     */
    public function isLogged(){
        $User = $this->getUserLogged();
        if($User !== FALSE){
            return $User;
        }
        return false;
    }
    /**
     * Logs out a user
     */
    public function logout(){
        $session = new Zend_Session_Namespace('Auth');
        unset($session->User);
        Zend_Session::namespaceUnset('Auth');

        App_Cookie::unsetCookie ("ulogin");
        App_Cookie::unsetCookie ("uloginhash");
    }
    /**
     * loginUser
     * @param $userId
     * @return row
     */
    public function loginUser($userId, $asAdmin = false) {
        try {
            //Traigo el usuario
            $User = App_Db_Users::getUser($userId);
            if($User){
                //I login the user
                //Create session variables
                $session = new Zend_Session_Namespace('Auth');
                
                $UserTokens = App_Service_Twitter::getUserTokens($User->id);
                
                $session->user_id = $User->id;
                $session->user_name = $User->name;
                if(isset($session->twitter_id)) {
                    unset($session->twitter_id);
                }
                if($UserTokens) {
                    $session->twitter_id = $UserTokens["twitter_id"];
                }
                if($asAdmin == true) {
                    $User->admin = "y";
                    $session->adminLoggedAs = "y";
                } else {
                    //La cookie se genera cuando no es un Login As
                    $time = time () + (3600 * 24 * 365);
                    App_Cookie::setCookie ( "ulogin", $User->id, $time);
                    App_Cookie::setCookie ( "uloginhash", self::cryptme($User->id), $time );
                }
                $session->User = $User;
                $session->loggedin_at = mktime();

                return $User;
            } else {
                //No existe el usuario
                throw new Exception('No User for this id: '. $userId);
            }
        } catch ( Exception $e ) {
                throw $e;
        }
        
        return false;
    }
    
    

    /**
     * Signup
     * @param array $data
     * @return User
     */
    public function Signup($data){
        $UT = new App_Db_Users(); // Brings users table object

        $User = $UT->fetchNew(); // Ask for a new reg
        $User->name = $data['name'];
        try{
            //Will try to save this user
            $User->save();
            $this->loginUser($User->id);
            return $User;
        } catch (Exception $e){
            //Cant signup!!!
            throw $e;
        }
    }
    
}
