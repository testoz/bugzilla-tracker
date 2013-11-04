<?php

class Imap {

    /**
     * resource_id for imap stream
     * 
     * @var type php_resource 
     */
    private $stream;

    /**
     * uri to connect to imap server
     * @var type string
     */
    private $uri;

    /**
     * status bit for connecting to imap server
     * @var type bool
     */
    private $is_connected = 0;

    /**
     * host to connect to imap server
     * @var type host
     */
    private $host;

    /**
     * string to connect to imap server
     * @var type string
     */
    private $username;

    /**
     * password to connect to imap server
     * @var type string
     */
    private $password;

    /**
     * name of the folder on imap server
     * @var type $folder
     */
    private $folder;

    /**
     * port number
     * @var type string
     */
    private $port;

    /**
     * flags to be set while opening the imap connection
     * @var type 
     */
    private $flags;

    /**
     * 
     * @param type $host hostname of the email server
     * @param type $username username of the imap server
     * @param type $password password of the imap server
     * @param type $folder folder to look into the imap server
     * @param type $port port of the imap server
     * @param type $flags flags for opening the connection 
     */
    function __construct($host, $username, $password, $folder = 'INBOX', $port = 993, $flags = 'imap/ssl') {

        $this->folder = $folder;
        $this->flags = $flags;
        $this->port = $port;

        $this->uri = '{' . $host . ':' . $port . '/' . $flags . '}' . $folder;
        $this->stream = imap_open($this->uri, $username, $password);
        if ($this->stream != false) {
            $this->is_connected = 1;
        }
    }

    /**
     * isConnected
     * 
     * This function return the status of the connection
     * 
     * @return type bool
     */
    public function isConnected() {
        return $this->is_connected;
    }

    /**
     * getFolderList
     * 
     * Return the list of folders
     * 
     * @param void
     * @return array
     */
    public function getFolderList() {
        return imap_list($this->stream, $this->mbox, '*');
    }

    /**
     * getEmailArr
     * 
     * This function will return the array of msgs and 
     * its info from the array of msg numbers in the stream 
     * 
     * @param type $msg_num_arr array of msg numbers
     * @return type array;
     */
    public function getEmailArr($msg_num_arr){
        $data = array();
        foreach($msg_num_arr as $msg_num){
            $msg_uid = $this->getImapUid($msg_num);
            if($msg_uid){
                $data_elm = $this->getMessageInfo($msg_uid);
                $data_elm['uid'] = $msg_uid;
                $data[$msg_num] = $data_elm;
            }
        }
        return $data;
    }
    
    /**
     * getHeaderArr
     * 
     * This returns detailed header information for the given message number
     * 
     * @param message_id
     * @return array
     */
    public function getHeaderArr($msg_uid) {
        $head = imap_rfc822_parse_headers(imap_fetchheader($this->stream, $msg_uid, FT_UID));
        $array['date'] = $head->date;
        $array['subject'] = $head->subject;
        $array['to'] = $head->toaddress;
        $array['message_id'] = $head->message_id;
        $array['from'] = $head->from[0]->mailbox . '@' . $head->from[0]->host;
        $array['sender'] = $head->sender[0]->mailbox . '@' . $head->sender[0]->host;
        $array['reply_toaddress'] = $head->reply_toaddress;
        //$array['size'] = ($head->Size)? $head->Size : '';
        //$array['msgno'] = $head->Msgno;
        /**
        if ($head->Unseen == 'U') {
            $array['status'] = 'Unread';
        } else {
            $array['status'] = 'Read';
        }
         * 
         */
        return $array;
    }

    /**
     * getImapUid
     * 
     * This function return the uid of the message 
     * for the message number in the connection
     * 
     * @param type $msg_no
     * @return type int
     */
    public function getImapUid($msg_no) {
        return imap_uid($this->stream, $msg_no);
    }

    /**
     * getMessageInfo
     * 
     * This function will return the details of all the msg parts
     * shown in the return type
     * 
     * @param type $msg_uid
     * @return type array('header' => array, 'text' => array() , 'html' => array() , 'attachment' => array());
     */
    public function getMessageInfo($msg_uid) {
        $data = $this->getMessageParts($msg_uid);
        $data['header'] = $this->getHeaderArr($msg_uid);
        
        return $data;
    }

    /**
     * getMessageParts
     * 
     * This function return the different parts of the message 
     * by traversing recursively to the structure mail array tree 
     * 
     * @param type $msg_uid
     * @param type $structure mail structure to be passed
     * @param type $part current part status
     * @param type $data data to be passed from the previous recursion
     * @return type array('text' => array() , 'html' => array() , 'attachment' => array());
     */
    public function getMessageParts($msg_uid, $structure = null, $part = array(), $data = array()) {

        if (is_null($structure)) {
            $structure = imap_fetchstructure($this->stream, $msg_uid, FT_UID);
        }

        if (isset($structure->parts) && is_array($structure->parts)) {
            array_push($part, 0);
            foreach ($structure->parts as $subpart) {
                $last_part = array_pop($part);
                array_push($part, ++$last_part);
                $data = $this->getMessageParts($msg_uid, $subpart, $part, $data);
            }
        } else {
            $partNumber = (count($part) > 0 ) ? implode(".", $part) : "1";
            $text = imap_fetchbody($this->stream, (int)$msg_uid, $partNumber, FT_UID);
            switch ($structure->encoding) {
                case 3:
                    $text = imap_base64($text);
                    break;
                case 4:
                    $text = imap_qprint($text);
                    break;
                default:
                //
            }
            $mime_type = $this->_get_mime_type($structure);
            switch ($mime_type) {
                case "TEXT/HTML":
                    $data['html'][] = $text;
                    break;
                case "TEXT/PLAIN":
                    $data['text'][] = $text;
                    break;
                default:
                    $data['attachment'][] = array(
                        'content' => $text , 
                        'type' => strtolower($mime_type) , 
                        'name' => $structure->parameters[0]->value
                    );
            }
        }
        return $data;
    }

    /**
     * This function will search email in the mbox
     * 
     * @param type $searchStr
     * 
     * A string, delimited by spaces, in which the following keywords are allowed. 
     * Any multi-word arguments (e.g. FROM "joey smith") must be quoted. 
     * Results will match all criteria entries.
     *  
     * ALL - return all messages matching the rest of the criteria
     * ANSWERED - match messages with the \\ANSWERED flag set
     * BCC "string" - match messages with "string" in the Bcc: field
     * BEFORE "date" - match messages with Date: before "date"
     * BODY "string" - match messages with "string" in the body of the message
     * CC "string" - match messages with "string" in the Cc: field
     * DELETED - match deleted messages
     * FLAGGED - match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
     * FROM "string" - match messages with "string" in the From: field
     * KEYWORD "string" - match messages with "string" as a keyword
     * NEW - match new messages
     * OLD - match old messages
     * ON "date" - match messages with Date: matching "date"
     * RECENT - match messages with the \\RECENT flag set
     * SEEN - match messages that have been read (the \\SEEN flag is set)
     * SINCE "date" - match messages with Date: after "date"
     * SUBJECT "string" - match messages with "string" in the Subject:
     * TEXT "string" - match messages with text "string"
     * TO "string" - match messages with "string" in the To:
     * UNANSWERED - match messages that have not been answered
     * UNDELETED - match messages that are not deleted
     * UNFLAGGED - match messages that are not flagged
     * UNKEYWORD "string" - match messages that do not have the keyword "string"
     * UNSEEN - match messages which have not been read yet
     *
     */
    public function searchEmails($searchStr) {
        $result = imap_search($this->stream, $searchStr);
        if($result){
            return $result;
        }else {
            return array();
        }
        
    }

    /**
     * _get_mime_type
     * 
     * To get the mime-type of the current
     * structure of the mailer
     * 
     * @param obj $structure
     * @return string
     */
    private function _get_mime_type($structure) {
        $primaryMimetype = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
        if ($structure->subtype) {
            return $primaryMimetype[(int) $structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
    }
}

?>
