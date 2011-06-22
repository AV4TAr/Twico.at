<?php
/**
 * App_Db_Users
 * @author jorge
 */
class App_Db_Users extends Zend_Db_Table_Abstract{
    protected $_name = 'users';
    protected $_primary = 'id';
    protected $_sequence = true;

    protected $_rowClass = "App_Db_User";
    /**
     * getUser
     * @param int $userid
     * @return row
     */
    static public function getUser($userid) {
        $UT = new App_Db_Users();
        $row = $UT->fetchRow("id='".$userid."'");
        return $row;
    }
    /**
     * validateSignup
     */
    static function validateSignup($email, $password) {
        $errors = array();
        
        $validator = new Zend_Validate_NotEmpty();
        if (!$validator->isValid($password)) {
            foreach ($validator->getMessages() as $message) {
                $errors['Password'] = $message;
            }
        }
        
        $validator = new Zend_Validate_EmailAddress();
        $validator->setOptions(array('domain' => false));
        if (!$validator->isValid($email)) {
                $errors['Email'] = "Please enter a valid email address";
        } else {
            $ADU = new App_Db_Users();
            $User = $ADU->fetchRow("email='".App_Generic::sanitize($email)."'");
            if($User) {
                $errors['Email'] = "Email already been registered";
            }
        }
        
        return $errors;
    }
    
	/**
     * getTwitterUsers
     * @return row
     */
    static public function getTwitterUsers() {
        $ADU = new App_Db_Users ( );
        $db = $ADU->getAdapter ();
        
        $sql = "SELECT users_id AS id from twitter_connections";

        $users = $db->fetchAll ( $sql, null, Zend_Db::FETCH_OBJ );
        return $users;
    }
    
    /**
     * returns users hasthags
     * @return App_Db_UsersHashTags[]
     */
    public function getHashtags(){
        $UH = new App_Db_UsersHashTags();
        $hashTags = $UH->fetchAll("users_id=".$this->id);
        return $hashTags;
    }
    
    public function addHashtag(App_Db_UsersHashTag $hashTag){
        
    }
}
