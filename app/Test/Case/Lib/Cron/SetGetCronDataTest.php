<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CronTest
 *
 * @author applect
 */
App::uses("Cron", "Lib");
App::uses("DummyCron", "Lib");

class SetGetCronDataTest extends CakeTestCase{
    public $fixtures = array('app.Cron/CronDetail');
    public $autoFixtures = false;
    public $shell;

    public function setup(){
        $this->shell = $this->getMock("AppShell");
    }
    
    public static function setupBeforeClass(){
        $test_con_config = ConnectionManager::getDataSource('test')->config;
        ConnectionManager::getDataSource('default')->close();
        ConnectionManager::drop('default');
        ConnectionManager::create('default', $test_con_config);
        ConnectionManager::getDataSource('default')->connect();
    }
    
    public function testUpdateCronData(){
        $this->loadFixtures('CronDetail');
        // SUT
        $dummyCron = new DummyCron();
        $dummyCron->publicInitCron($this->shell);
        
        $dummyCron->publicSetCronData('DUMMY_DATA');
        
        // Verifying result
        $cronDetails = new CronDetail(); 
        $actual = $cronDetails->find('first' , array('name' => $dummyCron->name));
        $this->assertEqual($actual['CronDetail']['data'],"DUMMY_DATA", "Actual cron data should be updated");
    }
    
    /**
     * @depends testUpdateCronData
     */
    public function testGetCronData(){
        $this->loadFixtures('CronDetail');
        // SUT
        $dummyCron = new DummyCron();
        $dummyCron->publicInitCron($this->shell);
        $dummyCron->publicSetCronData('DUMMY_DATA');
        //die();
        $actual_data = $dummyCron->publicGetCronData();
        $this->assertEqual('DUMMY_DATA', $actual_data , "it should fetch the actual data from the cron table");
        
    }
    
    public function testConcludeCron(){
        $this->loadFixtures('CronDetailWithData');
        
        $dummyCron = new DummyCron();
        $dummyCron->publicInitCron($this->shell);
        //exit;
        $dummyCron->publicConcludeCron();
        
        // Verifying result
        $cronDetails = new CronDetail(); 
        $actual = $cronDetails->find('first' , array('name' => $dummyCron->name));
        $time_diff = (time() - strtotime($actual['CronDetail']['end_time']));
        $this->assertLessThan(2, $time_diff , "current time should be updated");
        
    }
    
    
    
    
}
?>
