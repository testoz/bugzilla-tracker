<?php
/**
 * Description of Cron
 *
 * @author applect
 */
App::uses("CronDetail", "Model");
abstract class Cron {
    /**
     * Shell object to so some logging work;
     */
    protected $shell;
    
    /**
     * This variable will contain the data of the 
     * current row for cron_detail table
     * 
     * @var type CronDetail
     */
    public $CronDetail;
    
    /**
     * Name of the cron
     * 
     * @var type string
     */
    public $name;
    
    /**
     * primary key of the cron table for the current cron
     * 
     * @var type int
     */
    public $id;

    /**
     * This function will do all the initiation for the cron 
     * class object.
     * 
     * @todo to explain more about the function in the desciption.
     * @throws NameNotFoundException - when $this->name is not defined
     */
    protected function initCron(AppShell $shell){
        $this->shell = $shell;
        
        /**
         * Starting cron
         */
        $this->shell->out("Hi, I am cron :)");
                
        if(!$this->name){
            $this->shell->out('Too bad you haven\'t kept my name yet. I cannot continue bye :(' );
            throw new NameNotFoundException("Name of the cron is not declared");
        }
        
        $this->shell->out("and my name is " . $this->name);
        
        $this->CronDetail = new CronDetail();
        
        $this->shell->out("Checking my existance in the system ..");
        
        $cron_row = $this->CronDetail->find('first' , array('name' => $this->name));
        if($cron_row){
            $this->shell->out("Ok.. I am been here so we are cool .. right.");
            $this->id = $cron_row['CronDetail']['id'];
        }else{
            $this->shell->out("So I am new here.. Good to see you again");
            $this->CronDetail->save(array('name' => $this->name));
            $this->id = $this->CronDetail->id;
        }
    }
    
    /**
     * It stores a string data in the data column
     * of the current row.  
     * 
     * @param string $data
     */
    protected function setCronData($data){
        $this->shell->out("Setting cron data ($data)");
        $this->CronDetail->id = $this->id;
        $this->CronDetail->set('data', $data);
        $this->CronDetail->save();
    }
    
    /**
     * This function will return the data stored in the
     * data field
     * 
     * @return string - data stored in the data field 
     */
    protected function getCronData(){
        $row = $this->CronDetail->findById($this->id);
        $data = $row['CronDetail']['data'];
        $this->shell->out("Getting data ($data) from the table");
        return $data;
    }
    
    /**
     * This function will conclude the working of the cron all all the 
     * concluding work will happen here
     */
    
    protected function concludeCron(){
        $this->shell->out("Its time to say good bye :) Dont miss me ..");
        $this->CronDetail->id = $this->id;
        $this->CronDetail->set('end_time' , date("Y-m-d H:i:s"));
        $this->CronDetail->save();
    }
    
    /**
     * Hook function run before function doWork
     */
    protected function beforeCron(){
        $this->shell->out("Noting to do before actual fun begins");
    }
    
    /**
     * Function to be implemented by the child class
     * where the real fun happens
     */
    abstract protected function doWork();
    
    /**
     * Hook function run after the doWork function.
     */
    protected function afterCron(){
        $this->shell->out("Noting to followup");
    }
    
    /**
     * Template function that decide the order 
     * of execution in any cron. This will also 
     * be the starting point and any public method in the 
     * whole cron system. 
     */
    public function execute(AppShell $shell){
        $this->initCron($shell);
        $this->beforeCron();
        $this->doWork();
        $this->afterCron();
        $this->concludeCron();
    }
}

/**
 * NameNotFoundException to be thrown by the Cron::initCron() function
 */
class NameNotFoundException extends Exception{
    
}

?>
