<?php
/**
 * App_Db_TwitterConnections
 * @author jorge
 */
class App_Db_TwitterConnections extends Zend_Db_Table_Abstract{
   	protected $_name = 'twitter_connections';
	protected $_primary = 'id';
	protected $_sequence = true;
}
