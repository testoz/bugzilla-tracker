<?php
/**
 * Description of ImapAdaptor
 *
 * @author applect
 */
App::uses("ImapEmail", "Lib");
class ImapAdaptor implements ImapInterface{
    
    public function init($imap_uri , $imap_username , $imap_password){
        
    }
    
    /**
     * @return array of ImapEmail Description
     */
    public function getEmails($after_time) {
        return array(new ImapEmail());
    }
}



?>
