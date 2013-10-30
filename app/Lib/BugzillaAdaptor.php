<?php
/**
 * Description of BugzillaAdaptor
 *
 * @author applect
 */
class BugzillaAdaptor implements BugzillaInterface{
    public function init(){
        
    }
    public function addBug(BugzillaBug $bug) {
        //;
    }
    //put your code here
}

class BugzillaBugs{
    public $title;
    public $description;
    public $assigned_to = "tej.nri@gmail.com";
    public $product     = "default_product";
    public $component   = "default_component";
}

?>
