<?php
/**
 *
 * @author applect
 */
interface BugzillaInterface {
    public function init();
    public function addBug(BugzillaBug $bug);
}

?>
