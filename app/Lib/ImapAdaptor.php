<?php
/**
 * Description of ImapAdaptor
 *
 * @author applect
 */
App::uses("Imap", "Lib");
App::uses("ImapEmail", "Lib");

class ImapAdaptor implements ImapInterface{
    public $imap;
    
    public function init($imap_uri , $imap_username , $imap_password , $options=array()){
        $this->imap = new Imap($imap_uri , $imap_username , $imap_password , "INBOX" , 993, "imap/ssl");
        if($this->imap->isConnected()){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * This function will form a search criteria for the email
     * to be searched on the imap server. and then form a array
     * of ImapEmail objects to be returned;
     * 
     * @return array of ImapEmail Description
     */
    public function getEmails($after_time) {
        $return_arr = array();
        
        $after_time      = strToTime ("$after_time");
        $after_time_query = strToTime ("$after_time -1 day");
        $search_str = "ALL" . (($after_time_query)? " SINCE \"" . date("d F Y", $after_time_query) . "\"" : ""); 
        $emailid_arr = $this->imap->searchEmails($search_str);
        $email_arr   = $this->imap->getEmailArr($emailid_arr);
        foreach ($email_arr as $email) {
            $arrive_time = strtotime($email['header']['date']);
            if($arrive_time > $after_time){
                $return_arr[] = $this->getImapEmailObj($email);
            }
            
        }
        return $return_arr;
    }
    
    /**
     * This function will return a ImapEmail object 
     * from the email array;
     * 
     * @param array $email_info_arr
     * @return \ImapEmail
     */
    public function getImapEmailObj($email_info_arr){
        $imap_email_obj = new ImapEmail();
        $imap_email_obj->msg_id = $email_info_arr['uid'];
        $imap_email_obj->body = (isset($email_info_arr['text'][0]))? $email_info_arr['text'][0] : html_entity_decode(strip_tags($email_info_arr['html'][0]));
        $imap_email_obj->subject = $email_info_arr['header']['subject'];
        $imap_email_obj->from = $email_info_arr['header']['from'];
        $imap_email_obj->reach_time = strtotime($email_info_arr['header']['date']);
        $imap_email_obj->reach_time_str = date('Y-m-d H:i:s' , $imap_email_obj->reach_time);
        $imap_email_obj->attachments = (isset($email_info_arr['attachment']))?$email_info_arr['attachment']:array();
        return $imap_email_obj;
    }
}



?>
