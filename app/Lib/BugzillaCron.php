<?php
/**
 * Description of BugzillaCron
 * 
 * This cron class will
 *
 * @author applect
 */
App::uses("BugzillaInterface","Lib");
App::uses("ImapInterface","Lib");
class BugzillaCron extends Cron{

    public $name                = "BugzillaCron";
    public $imap_url            = '';
    public $imap_username       = '';
    public $imap_password       = '';
    private $_imap_driver       = null;
    private $_bugzilla_driver   = null;
    
    
    public function __construct(BugzillaInterface $bugzilla_driver, ImapInterface $imap_driver) {
        $this->_imap_driver = $imap_driver;
        $this->_imap_driver->init($this->imap_url, $this->imap_username, $this->imap_password);
        $this->_bugzilla_driver = $bugzilla_driver;
        $this->_bugzilla_driver->init();
    }
    
    protected function addBugInBugzilla(ImapEmail $email){
        $this->_bugzilla_driver->addBug();
    }

    protected function getEmails($from_last_run_time){
        return $this->_imap_driver->getEmails($from_last_run_time);
    }

    protected function validateEmail(ImapEmail $email){
        
    }
    
    protected function doWork(){
        /**
         * Getting the last email checked time stored in crondata
         */
        $late_checked_email_time = $this->getCronData();
        /**
         * Getting the emails from the lasttime
         */
        $emails_arr = $this->getEmails($late_checked_email_time);
        /**
         * Loop through the mails
         */
        foreach ($emails_arr as $email) {
            if($this->validateEmail($email)){
                $this->addBugInBugzilla($email);
                $this->setCronData($email->reach_time);
            }
        }
                
    }
    
    
}

?>
