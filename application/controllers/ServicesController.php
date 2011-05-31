<?php
class ServicesController extends Zend_Controller_Action
{

    public function init ()
    {

    }
    /**
     * twitter
     */
    public function twitterAction()
    {
        $Singleton = App_Singleton::getInstance();
        $config = $Singleton->getConfig();
        $do = $this->getRequest()->getParam('do');
        $go = $this->getRequest()->getParam('go');
        if($do==null){
            $do = 'index';
        }
        switch($do){
            case 'index':
                break;
            case 'redirect':
                $return_url = "http://".$config["webhost"]."/services/twitter/do/callback";
                if($go){
                    $return_url = $return_url.'/go/'.$go;
                }
                $connection = App_Service_Twitter::get();
                /* Get temporary credentials. */
                $request_token = $connection->getRequestToken($return_url);
                /* Save temporary credentials to session. */
                $_SESSION['twitter']['oauth_token'] = $token = $request_token['oauth_token'];
                $_SESSION['twitter']['oauth_token_secret'] = $request_token['oauth_token_secret'];
                /* If last connection failed don't display authorization link. */
                switch ($connection->http_code) {
                    case 200:
                        /* Build authorize URL and redirect user to Twitter. */
                        $url = $connection->getAuthorizeURL($token);
                        $this->_redirect($url);
                        break;
                    default:
                        $this->_redirect('/?error=connfailed');
                        break;
                }
                break;
            case 'callback':
                if($go){
                    $return_url = "http://".$config["webhost"]."/services/twitter/do/callback/go/".$go;
                }
                /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
                $connection = App_Service_Twitter::get($_SESSION['twitter']['oauth_token'], $_SESSION['twitter']['oauth_token_secret']);
                /* Request access tokens from twitter */
                $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
                /* Save the access tokens. Normally these would be saved in a database for future use. */
                $_SESSION['twitter']['access_token'] = $access_token;
                /* Remove no longer needed request tokens */
                unset($_SESSION['twitter']['oauth_token']);
                unset($_SESSION['twitter']['oauth_token_secret']);
                /* If HTTP response is 200 continue otherwise send to connect page to retry */
                if (200 == $connection->http_code) {
                    /* The user has been verified and the access tokens can be saved for future use */
                    $UTITbl = new App_Db_TwitterConnections();
                    $UserTokens = $UTITbl->fetchRow('twitter_id="'.$access_token['user_id'].'"');
                    if($UserTokens){
                        $Singleton->loginUser($UserTokens->users_id);
                        $this->_redirect('/home');
                    } else {
                        $ASN = new App_Db_AllowedScreenNames();
                        $row = $ASN->fetchRow("screen_name='".$access_token['screen_name']."'");
                        /*If the user's screen name is in our allowed screen names table we let the signup continue*/
                        if($row) {
                            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
                            $parameters = array('id'=>$access_token['screen_name']);
                            $userData = $connection->get('users/show',  $parameters);
                            $userData = (array) $userData;
    
                            $User = $Singleton->Signup(array("name"=>$userData["name"]));
    
                            $TwC = new App_Db_TwitterConnections();
                            $UserNew = $TwC->fetchNew();
                            $UserNew->users_id = $User->id;
                            $UserNew->screen_name = $access_token['screen_name'];
                            $UserNew->twitter_id = $access_token['user_id'];
                            $UserNew->oauth_token_secret = $access_token['oauth_token_secret'];
                            $UserNew->oauth_token = $access_token['oauth_token'];
                            $UserNew->twitter_info = serialize($userData);
                            $UserNew->save();
    
                            $this->_redirect('/home?welcome');
                        } else {
                            $this->_redirect('/?error=nau');
                        }
                    }
                } else {
                    $this->_redirect('/?error');
                }
                break;
            case 'logout':
                $Singleton->logout();
                $this->_redirect('/');
                break;
            case 'test':
                break;
        }
    }
}