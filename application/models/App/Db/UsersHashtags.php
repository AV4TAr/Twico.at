<?php
/**
 * App_Db_Users
 * @author jorge
 */
class App_Db_UsersHashtags extends Zend_Db_Table_Abstract{
    protected $_name = 'users_hashtags';
    protected $_primary = 'id';
    protected $_sequence = true;
    
    protected $_rowClass = "App_Db_UsersHashtag";
}
