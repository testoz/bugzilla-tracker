<?php

/**
 * 
 */

App::uses("Cron", "Lib");
App::uses("BugzillaCron", "Lib");
App::uses("ImapAdaptor", "Lib");
App::uses("BugzillaAdaptor", "Lib");

class BugReporterShell extends AppShell {

    public function main() {
        $cron = new BugzillaCron(new BugzillaAdaptor(),new ImapAdaptor());
        $cron->execute($this);
    }
}

