<?php

/**
 * Description of BugzillaCron
 * 
 * This cron class will
 *
 * @author applect
 */
App::uses("BugzillaInterface", "Lib");
App::uses("ImapInterface", "Lib");

class BugzillaCron extends Cron {

    public $name = "BugzillaCron";

    # IMAP Settings
    public $imap_url = 'imap.gmail.com';
    public $imap_username = 'testbugzilla562@gmail.com';
    public $imap_password = 'pass@word';

    # Bugzilla Settings
    public $bugzilla_url = 'http://bugzilla.mn.com';
    public $bugzilla_username = 'testbugzilla562@gmail.com';
    public $bugzilla_password = 'chikoo@1';
    public $bugzilla_product = 'Meritnation.com';
    public $bugzilla_product_ver = "1.0";
    public $bugzilla_component = 'CustomerBugs';
    public $bugzilla_report_username = "testbugzilla562@gmail.com";
    public $bugzilla_report_fullname = "Bugzilla Report Cron";
    public $bugzilla_assigned_username = "testbugzilla562@gmail.com";
    public $bugzilla_assigned_fullname = "Bugzilla Report Cron";
    private $_imap_driver = null;
    private $_bugzilla_driver = null;

    public function __construct(BugzillaInterface $bugzilla_driver, ImapInterface $imap_driver) {
        $this->_imap_driver = $imap_driver;
        $this->_bugzilla_driver = $bugzilla_driver;
    }

    protected function addBugInBugzilla(ImapEmail $email) {
        # Setting a bug from email and other constants
        $bug = new Bug();
        $bug->title = $email->subject;
        $bug->description = $email->body;
        $bug->report_customer_email = $email->from;
        $bug->product = $this->bugzilla_product;
        $bug->component = $this->bugzilla_component;
        $bug->version = $this->bugzilla_product_ver;
        $bug->assigned_to_email = $this->bugzilla_assigned_username;
        $bug->assigned_to_name = $this->bugzilla_assigned_fullname;
        $bug->report_user_email = $this->bugzilla_report_username;
        $bug->report_user_name = $this->bugzilla_report_fullname;
        $bug->attachments = $email->attachments;
        # Adding a bug
        $response = $this->_bugzilla_driver->addBug($bug);

        if (is_array($response) && isset($response['bug_status'])) {
            if ($response['bug_status'] == 1) {
                $this->shell->out("New bug added with bugid #" . $response['id']);
            } else if ($response['bug_status'] == 2) {
                $this->shell->out("Bug already existed as bugid #" . $response['id']
                        . " therefore email added as comment with commentid #" . $response['comment_id']);
            }
        } else {
            $this->shell->out("processing failed");
        }
    }

    protected function getEmails($from_last_run_time) {
        $this->shell->out("Getting emails from IMAP server");
        return $this->_imap_driver->getEmails($from_last_run_time);
    }

    protected function validateEmail(ImapEmail $email) {
        if (strlen(trim($email->body)) > 0 and strlen(trim($email->subject)))
            return true;
        else
            return false;
    }

    protected function beforeCron() {
        $this->shell->out("Initializing imap and bugzilla drivers");
        if ($this->_imap_driver->init($this->imap_url, $this->imap_username, $this->imap_password)) {
            $this->shell->out("Imap driver initialized..");
        } else {
            throw new Exception("Not able to connect to IMAP server");
        }

        if ($this->_bugzilla_driver->init($this->bugzilla_url, $this->bugzilla_username, $this->bugzilla_password)) {
            $this->shell->out("Bugzilla driver initialized..");
        } else {
            throw new Exception("Not able to connect to Bugzilla server");
        }
    }

    protected function doWork() {
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
        if (count($emails_arr) > 0) {
            $this->shell->out("=======================Processing of mail started======================");
            foreach ($emails_arr as $email) {
                $this->shell->out("\nProcessing mail from " . $email->from);
                if ($this->validateEmail($email)) {
                    $this->addBugInBugzilla($email);
                    $this->setCronData($email->reach_time_str);
                } else {
                    $this->shell->out("Validation of email failed");
                }
            }
            $this->shell->out("\n=======================Processing of mail ended=========================");
        }else{
            $this->shell->out("No mails to process ..");
        }
    }

}

?>
