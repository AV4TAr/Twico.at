<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

	/**
	 * Starts Session
	 */
	protected function _initSession(){
		Zend_Session::start();
	}
	
    protected function _initConfig(){
        $app_path = APPLICATION_PATH . '/config/application.ini';
        $config = new Zend_Config_Ini($app_path, APPLICATION_ENV);
        App_Singleton::setDefaultConfig($config);
    }
        
}

