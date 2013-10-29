<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BugzillaCron
 *
 * @author applect
 */
App::uses("CronDetail","Model");


class BugzillaCron extends Cron{
    //put your code here
    public function __construct() {
        $cronDetail = new CronDetail();
        $cronDetail->find('all');
    }
}

?>
