<?php
/**
 * App_Cookie
 * Gestion de cookies
 * @author WHObyYOU team
 * @version 0.1
 */
class App_Cookie {
    /**
     * Sets a cookie
     *
     * @param string $cookiename, name of the cookie
     * @param string $value, value to store
     * @param int $expire Unix timestamp, Ex; time()+3600 = 1 hour
     */
    static public function setCookie($cookiename, $value, $expire = 0, $path = "/", $domain = "") {
        setcookie ( $cookiename, $value, $expire, $path, $domain );
    }
    /**
     * getCookie
     * Gets the value of a cookie
     * @param string $cookiename, name of the cookie
     * @return false if the cookie dont exists, string value of the cookie.
     */
    static public function getCookie($cookiename) {
        if (isset ( $_COOKIE ["$cookiename"] )) {
            return $_COOKIE ["$cookiename"];
        } else {
            return false;
        }
    }
    /**
     * unsetCookie
     * Deletes a cookie
     * @param string $cookiename, name of the cookie
     */
    static public function unsetCookie($cookiename, $path = "/", $domain = "") {
        setcookie ( $cookiename, 0, time () - 3600, $path, $domain );
    }
}