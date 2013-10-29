<?php
App::uses('CronDetail', 'Model');

/**
 * CronDetail Test Case
 *
 */
class CronDetailTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'app.cron_detail'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->CronDetail = ClassRegistry::init('CronDetail');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->CronDetail);

		parent::tearDown();
	}

}
