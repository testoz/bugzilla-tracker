<?php
/**
 * A dummy class to extend Cron class for
 * Cron class unit testing purpose
 * 
 * @author Tejaswi Sharma <tejaswi.sharma@meritnation.com>
 */
class DummyCron extends Cron{
    public $name;
    public function __construct(){
        $this->name = "DUMMY_CRON";
    }
    
    /**
     * Public accesser method for 
     * Cron::initCron() method
     */
    public function publicInitCron(AppShell $shell){
        $this->initCron($shell);
    }
    
    /**
     * Public accesser method for 
     * Cron::setCronData() method
     */
    
    public function publicSetCronData($data){
        $this->setCronData($data);
    }
    
    /**
     * Public accesser method for 
     * Cron::getCronData() method
     */
    
    public function publicGetCronData(){
        return $this->getCronData();
    }
    
    /**
     * Public accesser method for 
     * Cron::concludeCron() method
     */
    public function publicConcludeCron(){
        $this->concludeCron();
    }
    
    /**
     * Function to be declared because doWork
     * function is declared as abstract in the
     * parent class
     */
    
    protected function doWork() {
        
    }
}

?>
