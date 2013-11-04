<?php
/*
 * BugzillaPHP - PHP class interface to Bugzilla (version 3.2 and above).
 * Copyright 2009 Scott Teglasi <steglasi@subpacket.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Bugzilla Connector class.  
 * 
 * @author Scott Teglasi <steglasi@subpacket.com>
 * @version 0.1
 * @copyright 2009 Scott Teglasi <steglasi@subpacket.com>
 */
class BugzillaConnector {
    private $cookies;
    private $bugzillaUrl;
    private $buglistPath = '/buglist.cgi';
    private $postbugPath = '/post_bug.cgi';
    private $showbugPath = '/show_bug.cgi';
    private $processbugPath = '/process_bug.cgi';
    
    private $configPath  = '/config.cgi?ctype=rdf';
    private $xmlrpcPath  = '/xmlrpc.cgi';
    
    private $fieldList;
    
    /**
     * List of bugzilla products with associated components, classifications, etc.
     *
     * @var array
     */
    private $products;
    
    /**
     * List of all classifications
     * 
     * @var array
     */
    private $classifications;
    
    /**
     * List of all available components
     * 
     * @var array
     */
    private $components;
    
    
    public function __construct($bugzillaUrl, $cookies = array()) {
        // Let's set this puppy up.
        $this->bugzillaUrl = $bugzillaUrl;
        if (!empty($cookies)) {
            $this->cookies = $cookies;
        }
        
    }
    /**
     * Perform a bugzilla login.  Upon success, returns true.
     * NOTE: Bugzilla depends upon cookies to be sent to it in order
     * to perpetuate a logged in "session".  To retrieve the cookies 
     * sent back, use getCookies() and store it in your own PHP session.  
     * To set cookies sent to bugzilla use setCookies().  
     *
     * @param string $username
     * @param string $password
     * @param boolean $rememberMe
     * @return int
     */
    public function login($username, $password, $rememberMe = false) {
        $response = $this->xmlrpcRequest('User.login',array('login'=>$username,'password'=>$password,'rememberMe'=>$rememberMe));
        $userId = intval($response['id']);
        if ($userId > 0) {
            return $userId;
        }
        return false;
    }
    
    /**
     * Perform a bugzilla "logout".
     *
     * @return boolean
     */
    public function logout() {
        $this->xmlrpcRequest('User.logout',array());
        return true;
    }
    
    
    /**
     * Perform a bugzilla search, based on parameters passed in via a BugzillaSearchParameters
     * object.  Returns an array of BugzillaBug objects with the list of bugs available.
     *
     * @param BugzillaSearchParameters $params
     * @return array of BugzillaBug objects
     */
    public function search(BugzillaSearchParameters $params) {
        // Perform the query.
        $response = $this->sendRequest($this->bugzillaUrl . $this->buglistPath . '?' . $params->toString() . '&ctype=rdf');
        // TODO: Put a decent non-error-barfing method of checking for errors.
        // Ex: when logins are required and noone's logged in.
        
        $responseXml = simplexml_load_string($response);
        
        if (!$responseXml instanceof SimpleXMLElement) {
            // If we can't parse the XML response sent back, then
            // for all intents and purposes, the search failed.
            return false;
        }
        // Get the list of bugs returned.
        $result = $responseXml->xpath('//bz:id');
        
        foreach ($result as $bug) {
            // Extract bug id.
            $bugIds[] = (int)$bug[0];
        }
        // Retrieve all the bugs in the list.
        return $this->getBugs($bugIds);
        
        
    }
    
    /**
     * Retrieve the list of bug ids given.
     *
     * @param array $idList
     * @return array array of BugzillaBug objects.
     */
    public function getBugs($idList) {
        // Perform the request to get info on all the bugs.
        $url = $this->bugzillaUrl . $this->showbugPath . '?id=' . join('&id=',$idList) . '&ctype=xml';
        $response = $this->sendRequest($url,'GET');
        $responseXml = simplexml_load_string($response);
        $bugXml = $responseXml->xpath('//bug');
        foreach ($bugXml as $item) {
            $bug = new BugzillaBug();
            // Convert long_desc into an array for the bug to hold.
            
            $itemArray = (array)$item;
            unset($itemArray['long_desc']);
            foreach ($item->long_desc as $key=>$desc) {
                $itemArray['long_desc'][] = (array)$desc;
            }
            
            $bug->fromArray((array)$itemArray);
            $buglist[] = $bug;
            unset($bug);
        }
        return $buglist;
    }
    
    /**
     * Convenience function to retrieve a single bug.
     *
     * @param unknown_type $id
     * @return BugzillaBug
     */
    public function getBug($id) {
        $buglist = $this->getBugs(array($id));
        return $buglist[0];
    }
    
    public function searchBug($bugVars){
        return $this->xmlrpcRequest('Bug.search',  $bugVars);
    }
    
    /**
     * Create a bug in bugzilla based on the BugzillaBug object passed in.
     *
     * @param BugzillaBug $bug
     * @return boolean
     */
    public function createBug($bugVars) {
        return $this->xmlrpcRequest('Bug.create',  $bugVars);
    }
    
    /**
     * Update an existing bug.
     *
     * @param BugzillaBug $bug
     * @return boolean
     */
    public function updateBug($bugVars) {
        return $this->xmlrpcRequest('Bug.update',  $bugVars);
    }
    
    public function addComment($bugVars){
        return $this->xmlrpcRequest('Bug.add_comment',  $bugVars);
    }
    
    public function createAttachment($bugVars){
        return $this->xmlrpcRequest('Bug.add_attachment',  $bugVars);
    }
    
    /**
     * Perform an XMLRPC request to bugzilla.
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    private function xmlrpcRequest($method, $params) 
    {
        $request = xmlrpc_encode_request($method,$params);
        $response = $this->sendRequest($this->bugzillaUrl . $this->xmlrpcPath,'POST',$request);
        return xmlrpc_decode($response);
    }
    
    /**
     * Send a request to bugzilla.
     *
     * @param string $url
     * @param string $requestType GET or POST
     * @param string $body
     * @param array $postvars
     * @return string
     */
    private function sendRequest($url, $requestType = 'GET', $body = '', $postvars = '') {
        if ($this->cookies) {
            $header = $this->cookiesToHeader() . "\n";
        } else {
            $header = "";
        }
        if ($postvars) {
            $header .= 'Content-type: application/x-www-form-urlencoded';
        } else {
            $header .= 'Content-type: text/xml';
        }
        
        if (!empty($postvars)) {
            // Process them.
            $body = http_build_query($postvars);
        }
        
        $context = stream_context_create(array('http' => array(
              'method' => $requestType,
              'header' => $header,
              'content' => $body
        )));

        $response = file_get_contents($url, false, $context);
        
        // Grab any cookies that were sent in         this request and stash 'em in the session.
        $cookieList = array();
        foreach ($http_response_header as $item) {
            if (substr($item,0,11) == 'Set-Cookie:') {
                // Got a cookie.  Save it!
                $cookieList[] = substr($item,12);
            }
        }
        // Save cookies.
        if (count($cookieList) > 0) {
            $this->saveCookies($cookieList);
        }
        
        return $response;
    }
    
    /**
     * Sets the cookie list to be sent to bugzilla in subsequent requests.
     *
     * @param array $cookieList
     */
    public function setCookies($cookieList) {
        $this->cookies = $cookieList;
    }
    
    /**
     * Returns an array of cookies used by bugzilla.
     * 
     * @return array
     */
    public function getCookies() {
    	return $this->cookies;
    }
    
    /**
     * Converts the cookie array into a proper HTTP header.
     *
     * @return string
     */
    private function cookiesToHeader() {
        // Convert the cookie array into a cookie header.
        $header = false;
        if (is_array($this->cookies)) {
            $header = 'Cookie: $Version=0; ';
            foreach ($this->cookies as $cookie) {
                $header .= $cookie['name'] . '=' . $cookie['value'] . '; ';
                $header .= '$Path=' . $cookie['path'] . '; ';
            }
        }
        return $header;
    }
    
    private function saveCookies($cookieHeaders) {
        foreach ($cookieHeaders as $cookie) {
            // Get rid of Set-cookie.
            $cookie = str_replace('Set-Cookie: ','',$cookie);
            $cookieParts = explode(";",$cookie);
            // first one should be the cookie name and value.
            $cookieParams['name'] = substr($cookieParts[0],0,strpos($cookieParts[0],'='));
            $cookieParams['value'] = substr($cookieParts[0],strpos($cookieParts[0],'=')+1);

            foreach ($cookieParts as $piece) {
                $keyval = explode('=',$piece);
                switch (trim($keyval[0])) {
                    case "path":
                        $cookieParams['path'] = $keyval[1];
                        break;
                    case "expires":
                        $cookieParams['expires'] = $keyval[1];
                        break;
                }
            }
            $cookieList[] = $cookieParams;
            unset($cookieParams);
            unset($cookieParts);
            unset($piece);
            unset($keyval);
            
        }
        $this->cookies = $cookieList;
    }
}
?>