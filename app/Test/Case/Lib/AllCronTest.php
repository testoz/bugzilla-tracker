<?php
/**
 * Description of AllCronTest
 *
 * @author applect
 */
class AllCronTest extends CakeTestSuite {

    public static function suite() {
        $suite = new CakeTestSuite("All cron class tests");
        $suite->addTestDirectory(TESTS . 'Case' . DS . 'Lib' . DS . "Cron");
        return $suite;
    }

}

?>
