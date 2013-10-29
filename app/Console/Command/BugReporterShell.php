<?php
App::uses("Cron", "Lib");
App::uses("BugzillaCron", "Lib");

class BugReporterShell extends AppShell {

    public function main() {
        $cron = new BugzillaCron();
        $cron->execute();
    }
}

