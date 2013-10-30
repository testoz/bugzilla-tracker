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

class InitCronTest extends CakeTestCase{
    public $fixtures = array('app.Cron/CronDetail' , 'app.Cron/CronDetailWithData');
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
    
    public function testNameAttribute(){
        $this->loadFixtures('CronDetail');
        // SUT
        $dummyCron = new DummyCron();
        $dummyCron->name = null;
        
        // Set Exception expectation
        $this->setExpectedException('NameNotFoundException');
        
        // Fire the function
        $dummyCron->publicInitCron($this->shell);
    }
    
    public function testEntryCheck(){
        $this->loadFixtures('CronDetail');
        
        // SUT
        $dummyCron = new DummyCron();
        $dummyCron->publicInitCron($this->shell);
        //

        // Verifying result
        $cronDetails = new CronDetail(); 
        $actual = $cronDetails->find('first' , array('name' => $dummyCron->name));
        
        if(empty($actual)){
            $this->fail('cron_details table should conrtain the row');
        }else{
            $this->assertEqual($actual['CronDetail']['name'],$dummyCron->name,  "Name of both the crons should be same");
        }
    }
     
    public function testEntrySkip(){
        $this->loadFixtures('CronDetailWithData');
        //die();
        // SUT
        $dummyCron = new DummyCron();
        $dummyCron->publicInitCron($this->shell);
        //
        
        $cronDetails = new CronDetail(); 
        $actual = $cronDetails->find('count' , array('name' => $dummyCron->name));
        $this->assertEqual($actual, 1, "Number of rows should not exceed one");
    }
}
?>
