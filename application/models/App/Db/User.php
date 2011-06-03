<?php
/**
 * App_Db_User
 * @author jorge
 */
class App_Db_User extends Zend_Db_Table_Row_Abstract {
    /**
     * getTimeline
     * @param array $filterArray (metadata: tdl o expanded link)
     * @param bool $notExpanded
     * @param int $limit
     * @param int $sinceTweetId
     * @param string $order
     * @param int $sinceInsertedId
     * @param int $timeLimit (days back)
     * @return rowset
     */
    public function getTimeline($filterArray = null, $notExpanded = false, $limit = 1000, $sinceTweetId = null, $order = "t.tweet_id DESC", $sinceInsertedId = null, $timeLimit = 2, $maxId = null) {
        $UserTokens = App_Service_Twitter::getUserTokens($this->id);
        $uTwitterId = $UserTokens["twitter_id"];
        
        $ADU = new App_Db_Users ( );
        $db = $ADU->getAdapter ();
        $sqlAdd = "";
        if($filterArray) {
            foreach($filterArray as $field=>$value) {
                //$sqlAdd.= " AND tm.".$field." LIKE '%".$value."%' ";
                $sqlAdd.= " AND tm.".$field." = '".$value."' ";
            }
        }
        if($notExpanded) {
            $sqlAdd.=" AND (tm.expanded_link = '' OR tm.expanded_link IS NULL) AND tm.processed = 'n' ";
        }
        if($sinceTweetId) {
            $sqlAdd.=" AND t.tweet_id > ".$sinceTweetId;
        }
        if($sinceInsertedId) {
            $sqlAdd.=" AND t.id > ".$sinceInsertedId;
        }
        if($maxId) {
            $sqlAdd.=" AND t.tweet_id <= ".$maxId;
        }
        if($timeLimit) {
            $sqlAdd.=" AND t.created <= '".date("Y-m-d H:i:s")."' AND t.created >= '".date("Y-m-d H:i:s",strtotime(-1*$timeLimit. " days"))."'";
        }
		
	    $sql = "SELECT t.id, t.tweet_id, t.twitter_id, t.screen_name, t.tweet, t.created, tm.link, tm.expanded_link, tm.tdl, lm.title, lm.description, lm.id AS link_id FROM tweets t, tweets_timelines tt, tweets_metadata tm LEFT JOIN links_metadata lm ON tm.expanded_link = lm.expanded_link WHERE tt.tweets_id = t.id AND t.id = tm.tweets_id AND tt.twitter_id = '".$uTwitterId."' ".$sqlAdd." ORDER BY ".$order." LIMIT ".$limit;

	    //echo $sql;
        //exit;
        $Tweets = $db->fetchAll ( $sql, null, Zend_Db::FETCH_OBJ );

	    return $Tweets;
    }
    /**
     * getLastTweetId
     * @return int
     */
    public function getLastTweetId() {
        $UserTokens = App_Service_Twitter::getUserTokens($this->id);
        $uTwitterId = $UserTokens["twitter_id"];
        
        $ADU = new App_Db_Users ( );
        $db = $ADU->getAdapter ();
        
        $sql = "SELECT MAX(t.tweet_id) as tweet_id FROM tweets t, tweets_timelines tt WHERE tt.tweets_id = t.id AND tt.twitter_id = '".$uTwitterId."'";

        $Tweet = $db->fetchRow ( $sql, null, Zend_Db::FETCH_OBJ );
        if($Tweet) {
            return $Tweet->tweet_id;
        } else {
            return null;
        }
        
    }
    /**
     * getLastInsertedTweetId
     * @return int
     */
    public function getLastInsertedTweetId() {
        $UserTokens = App_Service_Twitter::getUserTokens($this->id);
        $uTwitterId = $UserTokens["twitter_id"];
        
        $TwT = new App_Db_TweetsTimelines();
        $lastTweetRel = $TwT->fetchRow("twitter_id='".$uTwitterId."'","id DESC");
        if($lastTweetRel) {
            $Tw = new App_Db_Tweets();
            $lastTweet = $Tw->fetchRow("id='".$lastTweetRel->tweets_id."'");
            if($lastTweet) {
                return $lastTweet->id;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    /**
     * getBookmarks
     * @return rowset
     */
    public function getBookmarks() {
        $Book = new App_Db_Bookmarks();
        return $Book->fetchAll("users_id='".$this->id."'");
    }
    /**
     * getInterests
     * @return rowset
     */
    public function getInterests() {
        $ADU = new App_Db_Users ( );
        $db = $ADU->getAdapter ();
        $sql = "SELECT i.id, i.interest FROM interests i, user_interests ui WHERE i.id = ui.interests_id AND ui.users_id = '".$this->id."'";
        $results = $db->fetchAll ( $sql, null, Zend_Db::FETCH_OBJ );
        return $results;
    }
    /**
     * getInterestsListToChooseFrom
     * @return rowset
     */
    public function getInterestsListToChooseFrom() {
        $ADU = new App_Db_Users ( );
        $db = $ADU->getAdapter ();
        $sql = "SELECT i.id, i.interest FROM interests i LEFT JOIN user_interests ui ON i.id = ui.interests_id WHERE ui.users_id = '".$this->id."' OR i.important='y'";
        $results = $db->fetchAll ( $sql, null, Zend_Db::FETCH_OBJ );
        return $results;
    }
    /**
     * getInterestsKeywords
     * @return rowset
     */
    public function getInterestsKeywords($arrayFormatted = false) {
        $ADU = new App_Db_Users ( );
        $db = $ADU->getAdapter ();
        $sql = "SELECT DISTINCT ki.keyword FROM interests i, keywords_interests ki, user_interests ui WHERE i.id = ui.interests_id AND ui.users_id = '".$this->id."' AND ui.interests_id = ki.interests_id";
        $results = $db->fetchAll ( $sql, null, Zend_Db::FETCH_OBJ );
        if($arrayFormatted) {
            $keywords = array();
            if($results) {
                foreach($results as $key) {
                    $keywords[] = $key->keyword;
                }
            }
            $results = $keywords;
        }
        return $results;
    }
    /**
     * getInterestingScreenNames
     * @return rowset
     */
    public function getInterestingScreenNames() {
        $AUI = new App_Db_UserInterests();
        $intids = array();
        $accounts = array();
        $interests = $AUI->fetchAll("users_id='".$this->id."'");
        if($interests) {
            foreach($interests as $interest) {
                $intids[] = $interest->interests_id;
            }
        }
        if(count($intids)>0) {
            $ADU = new App_Db_Users ( );
            $db = $ADU->getAdapter ();
            
            $sql = "SELECT DISTINCT screen_name FROM screen_names_interests WHERE interests_id IN (".implode(",",$intids).")";
            $results = $db->fetchAll ( $sql, null, Zend_Db::FETCH_OBJ );
            
            if($results) {
                foreach($results as $result) {
                    $accounts[] = $result->screen_name;
                }
            }
        }
        return $accounts;
    }
    
    /**
     * getLastSeenId
     * @return rowset
     */
    public function getLastSeenId() {
        $AU = new App_Db_Users();
        $thisUser = $AU->fetchRow("id='".$this->id."'");

        return $thisUser->last_tweet_id;
    }
}