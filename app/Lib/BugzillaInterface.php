<?php
/**
 *
 * @author applect
 */
interface BugzillaInterface {
    public function init($url, $username , $password , $options);
    public function addBug(Bug $bug);
}

Class Bug{
    
    public $title;
    public $description;
    public $attachments = array();
    public $report_user_email;
    public $report_customer_email;
    public $assigned_to_email;
    public $product;
    public $component;
    public $version;
}

?>