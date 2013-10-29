<?php
class BugReporterShell extends AppShell {
    public $tasks = array('BugzillaCron');

    public function main() {
        $this->BugzillaCron->execute();    
    }
}

