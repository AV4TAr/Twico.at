<?php
/**
 * App_Service_Twitter
 * @author dsapriza
 */
require_once 'twitteroauth/OAuth.php';
require_once 'twitteroauth/twitteroauth.php';
class App_Service_Twitter
{
    private static $OAUTH_CALLBACK = null;
    private static $USER_TOKENS = false;
    
    public static $USER_DATA = array();
    public static $LAST_TWEET = array();
    public static $FRIENDS_DATA = array();

    public static $USER_FRIENDS_COUNT = array();
    
    /**
     * get
     * constructs a TwitterOAuth object
     * @param $oauth_token
     * @param $oauth_token_secret
     * @return TwitterOAuth
     */
    static function get($oauth_token=NULL, $oauth_token_secret=NULL) {
    	$consumer_key = '';
    	$consumer_secret = '';
    	
    	$Singleton = App_Singleton::getInstance();
        $config = $Singleton->getConfig();
        $config = $config["twitter"];

        if($config["key"] == null || $config["secret"] == null) {
            throw new Exception('Can not connect to twitter.');
        } else {
            $consumer_key = $config["key"];
            $consumer_secret = $config["secret"];
        }        
        return new TwitterOAuth($consumer_key,$consumer_secret, $oauth_token, $oauth_token_secret);
    }
    /**
     * getAuthCallback
     * @param string $addToUrl
     * @return string
     */
    static function getAuthCallback($addToUrl = '' ) {
    	if(self::$OAUTH_CALLBACK == null){
            $Singleton = App_Singleton::getInstance();
            $config = $Singleton->getConfig();
            $config = $config["twitter"];
            if($config["callback"] == null ){
                throw new Exception('Can not connect to twitter.');
            } else {
                self::$OAUTH_CALLBACK = $config["callback"].$addToUrl;
            }
    	}
    	return self::$OAUTH_CALLBACK;
    }
    /**
     * 
     * @param $userid
     * @param $tokens
     * @return void
     */
    static function saveUserTokens($userid, $tokens) {
        $TwConn = new App_Db_TwitterConnections();
        $data = array( "oauth_token" => $tokens['oauth_token'],
                       "oauth_token_secret" => $tokens['oauth_token_secret'],
                       "users_id" => $tokens['users_id'],
                       "screen_name" => $tokens['screen_name'] );
        try{
            $data_ins = $data;
            $data_ins["users_id"] = $userid;
            $TwConn->insert($data_ins);
        } catch (Exception $e) {
            if($e->getCode() == '23000'){
                $TwConn->update($data, 'users_id='.$userid);
            } else {
                throw new Exception($e->getMessage(), $e->getCode()); 
            }
            
        }
    }
    /**
     * getUserTokens
     * @param int $userid
     * @return array | false if no tokens
     */
   static function getUserTokens ($userid) {
        if($userid > 0){
            if(isset(self::$USER_TOKENS[$userid])){
                return self::$USER_TOKENS[$userid];
            }
            $TwConn = new App_Db_TwitterConnections();
            $tokens = $TwConn->fetchRow('users_id='.$userid);
            if($tokens != NULL){
                self::$USER_TOKENS[$userid] = $tokens->toArray();
                return self::$USER_TOKENS[$userid];
            } 
            return false;
        } else {
            return false;
        }
    }
    /**
     * disconnect
     * @param id $userid
     * @return bool
     */
    function disconnect($userid) {
        if($userid > 0){
            $TwConn = new App_Db_TwitterConnections();
            $tokens = $TwConn->fetchRow('users_id='.$userid);
            if($tokens != null){
                $tokens->delete();
            }
            return true;
        } else {
            return false;
        }
        return false;
    }
    /**
     * 
     * @param $userid
     * @param mixed $status
     * @return stdClass
     */
    static function updateUserStatus($userid, $status) {
        $access_token = self::getUserTokens($userid);
        if($access_token != false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            if(is_array($status)) {
                $parameters = array('status' => $status["status"]);
                if(isset($status["in_reply_to_status_id"])) {
                    $parameters["in_reply_to_status_id"] = $status["in_reply_to_status_id"];
                }
            } else {
                $parameters = array('status' => $status);
            }

            $status = $connection->post('statuses/update', $parameters);
            if (200 == $connection->http_code) {
                return $status;
            } else{
                throw new Exception('Can not update status because: '.$status->error);
            }
        }
    }
	/**
     * 
     * @param $userid
     * @param string $tweetid
     * @return stdClass
     */
    static function retweetStatus($userid, $tweetid) {
        $access_token = self::getUserTokens($userid);
        if($access_token != false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            $params = array('id' => $tweetid);

            $retweet = json_decode($connection->OAuthRequest($connection->host.'statuses/retweet/'.$params["id"].'.'.$connection->format, 'POST', $params));
            if (200 == $connection->http_code) {
                return $retweet;
            } else{
                throw new Exception('Can not retweet because: '.$retweet->error);
            }
        }
    }
    /**
     * Undocumented on twitter api wiki!!!
     * http://api.twitter.com/1/related_results/show/25044314886.json
     * @param $userid int 
     * @param string $tweeitId
     * @return
     */
    static function getRelatedResultsToTweet($userid, $tweetId) {
        $access_token = self::getUserTokens($userid);
        if($access_token != false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            $tweets = $connection->get('related_results/show/'.$tweetId);
            if (200 == $connection->http_code) {
                return $tweets;
            } else{
                throw new Exception('Can not get related results: '.$status->error);
            }
        }
    }
    /**
     * getUserFriends
     * @param $userid
     * @return array with twitter id
     */
    static function getUserFriends($userid,$cursor = 1) {
        $friends_ids = array();
        $access_token = self::getUserTokens($userid);
        if($access_token !== false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            $parameters = array('cursor='=>$cursor);
            $friends_ids = $connection->get('friends/ids',  $parameters);
            if (200 != $connection->http_code) {
                throw new Exception('Can not get user friends - '.$friends_ids->error);
            }
        }
        return $friends_ids;
    }
    /**
     * sendDirectMessage
     * @param int $userid
     * @param int $toTwitterId
     * @param string $text
     * @return string tweet
     */
    static function sendDirectMessage($userid, $toTwitterId, $text) {
        $access_token = self::getUserTokens($userid);
        $message = '';
        
        if($access_token !== false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            $params = array('user_id' => $toTwitterId, 'text' => $text);
            $message = json_decode($connection->OAuthRequest($connection->host.'direct_messages/new.'.$connection->format, 'POST', $params));
            if (200 == $connection->http_code) {
                return $message;
            } else{
            	throw new Exception('Can not send direct message: '.$message->error);
            }
        }
        return $message;
    }
    /**
     * getFriendsData
     * @param $userid
     * @param $pos int, 0 = from 0 to 99, 1 = from 100 to 199...
     * @return array with twitter id
     */
    static function getFriendsData($userid, $pos = 0) {
        $friends_data = array();
        $friends_ids = array();
        $access_token = self::getUserTokens($userid);
        if($access_token !== false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            $parameters = array('cursor='=>1);
            $friends_ids = $connection->get('friends/ids',  $parameters);
            if (200 != $connection->http_code) {
                throw new Exception('Can not get friends - '.$friends_ids->error);
            }
            
            if(is_array($friends_ids) && count($friends_ids)>0) {
                self::$USER_FRIENDS_COUNT[$userid] = count($friends_ids);
                $ini = $pos*100;
                $fin = ($pos*100) + 100;
                $friends_ids = array_slice($friends_ids, $ini, $fin);
                $fidsStr = implode(",",$friends_ids);
                $params = array('cursor'=>1,'user_id'=>$fidsStr);
                $friends_data = json_decode($connection->OAuthRequest($connection->host.'users/lookup.'.$connection->format, 'POST', $params));
            }
        }
        return $friends_data;
    }
    /**
     * get User Data
     * @param $userid
     * @return array with user data
     */
    static function getUserData($userid) {
    	if(!isset(self::$USER_DATA[$userid])){
            $access_token = self::getUserTokens($userid);
            $user_data=false;
            if($access_token !== false){
                $connection = Wby_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
                $parameters = array('id'=>$access_token['users_id']);
                $user_data = $connection->get('users/show', $parameters);
                if (200 != $connection->http_code) {
                    throw new Exception('Can not get user data - '.$user_data->error);
                }
            }
            self::$USER_DATA[$userid] = $user_data;
    	}
        return self::$USER_DATA[$userid];
    }
    /**
     * get Friend Data
     * @param app userid $userid
     * @param twitter id $id
     * @return array with user data
     */
    static function getFriendData($userid, $id) {
    	if(!isset(self::$FRIENDS_DATA[$id])){
            $access_token = self::getUserTokens($userid);
            $user_data=false;
            if($access_token !== false){
                $connection = Wby_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
                $parameters = array('id'=>$id);
                $user_data = $connection->get('users/show', $parameters);
                if (200 != $connection->http_code) {
                    throw new Exception('Can not get friend data - '.$user_data->error);
                }
            }
            self::$FRIENDS_DATA[$id] = $user_data;
    	}
        return self::$FRIENDS_DATA[$id];
    }
    /**
     * get Tweet Data
     * @param app userid $userid
     * @param tweet id $id
     * @return array with user data
     */
    static function getTweetData($userid, $tweet_id) {
    	$access_token = self::getUserTokens($userid);
    	$tweet=false;
        if($access_token !== false){
            $connection = Wby_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            $parameters = array('id'=>$tweet_id, 'include_entities'=>1);
            $tweet = $connection->get('statuses/show', $parameters);
            if (200 != $connection->http_code) {
                throw new Exception('Can not get tweet data: '.$tweet->error);
            }
        }       
        return $tweet;
    }
    /**
     * search twitter Search API
     * http://dev.twitter.com/doc/get/search
     * @param string $query
     * @return json
     * @throws Exception
     */
    static function search($query, $since = null, $page = 1, $rpp = 100) {
    	$data = false;
    	$query = urlencode($query);
    	$twitterSearchUrl = 'http://search.twitter.com/search.json?q=';
    	$twitterSearchUrl .= $query;
        if($since) {
            $twitterSearchUrl .= "&since=".$since; 
        }
        $twitterSearchUrl .= "&page=".$page;
        $twitterSearchUrl .= "&rpp=".$rpp;
        echo $twitterSearchUrl;

    	$client = new Zend_Http_Client();
        $client->setUri($twitterSearchUrl);
        $client->setConfig(array('maxredirects' => 0,
    				 'timeout'      => 30));
	$response = $client->request(Zend_Http_Client::GET);
    	switch ($response->getStatus() == 200) {
            case 200:
                $data = Zend_Json::decode ( $response->getBody (), Zend_Json::TYPE_ARRAY );
                if(isset($data["error"]) && $data["error"] != ''){
                    throw new Exception($data["error"], '49999');
                }
                break;
	    default:
                throw new Exception(__CLASS__.'::search: Something went wrong on connection');
	        break;
        }
	return $data;
    }
    /**
     * getUsers
     * @param $userid
     * @return array with twitter id
     */
    static function searchUsers($userid,$query,$cursor = 1) {
        $twusers = array();
        $access_token = self::getUserTokens($userid);
        if($access_token !== false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            $parameters = array('cursor='=>$cursor,"q"=>$query);
            $twusers = $connection->get('users/search',  $parameters);
            if (200 != $connection->http_code) {
                throw new Exception('Can not get users - '.$twusers->error);
            }
        }
        return $twusers;
    }
    /**
     * getHomeTimeline
     * @param $userid
     * @param $page
     * @param $skipSinceId
     * @param $qty
     * @return array
     */
    static function getHomeTimeline($userid, $page = 1, $skipSinceId = false, $qty = 50, $maxid = null) {
        $tweets = array();
        $access_token = self::getUserTokens($userid);
        $lastTweetId = null;
        if($access_token !== false){
            $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
            //,'count'=>100
            $parameters = array("page"=>$page,"include_entities"=>1,"count"=>$qty);
            if($skipSinceId == false) {
                if(isset(self::$LAST_TWEET[$userid])){
                    $lastTweetId = self::$LAST_TWEET[$userid];
                } else {
                    $ADU = new App_Db_Users ( );
                    $User = $ADU->fetchRow("id='".$userid."'");
                    $lastTweetId = $User->getLastTweetId();
                    self::$LAST_TWEET[$userid] = $lastTweetId;
                }
                if($lastTweetId) {
                    $parameters['since_id'] = $lastTweetId;
                }
            }
            
            if($maxid != null) {
                $parameters['max_id'] = $maxid;
            }
            
            $tweets = $connection->get('statuses/home_timeline',  $parameters);
            //$Singleton = App_Singleton::getInstance();
            //$Singleton::$logger->info("Twitter->getHomeTimeline:: user:" . $User->id . " | page:" . $page . " | tweets:".count($tweets) . " | tweetInfo:".print_r($tweets,true));
            if (200 != $connection->http_code) {
                $error = "";
                if(isset($tweets->error)) {
                    $error = $tweets->error;
                }
                throw new Exception('Can not get home timeline: '. $error);
                //$Singleton::$logger->info("Twitter->getHomeTimeline:: user:" . $User->id . " | page:" . $page . " | tweets:".count($tweets) . " | tweetInfo:".$tweets->error);
            }
        }
        return $tweets;
    }
    /**
     * getUserTimeline
     * @param $account
     * @param $page
     * @param $qty
     * @return object
     */
    static function getUserTimeline($account, $page = 1, $qty = 20) {
    	$data = false;
    	$uri = "http://api.twitter.com/1/statuses/user_timeline.json?screen_name=";
        $uri.= $account."&page=".$page."&count=".$qty."&include_entities=1";

    	$client = new Zend_Http_Client();
        $client->setUri($uri);
        $client->setConfig(array('maxredirects' => 0,
    				 'timeout'      => 60));
	    $response = $client->request(Zend_Http_Client::GET);
    	switch ($response->getStatus() == 200) {
            case 200:
                $data = Zend_Json::decode ( $response->getBody (), Zend_Json::TYPE_OBJECT );
                if(isset($data["error"]) && $data["error"] != ''){
                    throw new Exception($data["error"], '49999');
                }
                break;
	        default:
                throw new Exception(__CLASS__.'::search: Something went wrong on connection');
	        break;
        }
	    return $data;
    }
    /**
     * usersLookup
     * @param $userid
     * @param $by (screen_name or user_id)
     * @param $values array (screen_names or user_ids)
     * @return array
     */
    static function usersLookup($userid, $values = array(), $by = "screen_name") {
        $users_data = array();
        $access_token = self::getUserTokens($userid);
        if($access_token !== false){
            if(is_array($values) && count($values)>0) {
                $connection = App_Service_Twitter::get($access_token['oauth_token'], $access_token['oauth_token_secret']);
                $valuesStr = implode(",",$values);
                $params = array('cursor'=>1,$by=>$valuesStr);
                $users_data = json_decode($connection->OAuthRequest($connection->host.'users/lookup.'.$connection->format, 'POST', $params));
            }
        }
        return $users_data;
    }
}