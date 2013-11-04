<?php
/**
 *
 * @author applect
 */
interface ImapInterface {
    public function init($imap_uri , $imap_username, $imap_password , $options);
    public function getEmails($after_time);
}

?>
