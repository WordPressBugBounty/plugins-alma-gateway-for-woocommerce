<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_ShareInstancesTest extends Alma_Gateway_DiceTest {
	public function testShareInstances() {
		$rule = [];
		$rule['shareInstances'] = ['Alma_Gateway_Shared'];
		$dice = $this->dice->addRule('Alma_Gateway_TestSharedInstancesTop', $rule);


		$shareTest = $dice->create('Alma_Gateway_TestSharedInstancesTop');

		$this->assertinstanceOf('Alma_Gateway_TestSharedInstancesTop', $shareTest);

		$this->assertInstanceOf('Alma_Gateway_SharedInstanceTest1', $shareTest->share1);
		$this->assertInstanceOf('Alma_Gateway_SharedInstanceTest2', $shareTest->share2);

		$this->assertSame($shareTest->shared, $shareTest->share1->shared);
		$this->assertSame($shareTest->share1->shared, $shareTest->share2->shared);
		$this->assertEquals($shareTest->shared->uniq, $shareTest->share1->shared->uniq);
		$this->assertEquals($shareTest->share1->shared->uniq, $shareTest->share2->shared->uniq);

	}

	public function testNamedShareInstances() {

		$rule = [];
		$rule['instanceOf'] = 'Alma_Gateway_Shared';
		$dice = $this->dice->addRule('$Shared', $rule);

		$rule = [];
		$rule['shareInstances'] = ['$Shared'];
		$dice = $dice->addRule('Alma_Gateway_TestSharedInstancesTop', $rule);


		$shareTest = $dice->create('Alma_Gateway_TestSharedInstancesTop');

		$this->assertinstanceOf('Alma_Gateway_TestSharedInstancesTop', $shareTest);

		$this->assertInstanceOf('Alma_Gateway_SharedInstanceTest1', $shareTest->share1);
		$this->assertInstanceOf('Alma_Gateway_SharedInstanceTest2', $shareTest->share2);

		$this->assertSame($shareTest->shared, $shareTest->share1->shared);
		$this->assertSame($shareTest->share1->shared, $shareTest->share2->shared);
		$this->assertEquals($shareTest->shared->uniq, $shareTest->share1->shared->uniq);
		$this->assertEquals($shareTest->share1->shared->uniq, $shareTest->share2->shared->uniq);


		$shareTest2 = $dice->create('Alma_Gateway_TestSharedInstancesTop');
		$this->assertNotSame($shareTest2->share1->shared, $shareTest->share2->shared);
	}


	public function testShareInstancesNested() {
		$rule = [];
		$rule['shareInstances'] = ['Alma_Gateway_F'];
		$dice = $this->dice->addRule('Alma_Gateway_A4',$rule);
		$a = $dice->create('Alma_Gateway_A4');
		$this->assertTrue($a->m1->f === $a->m2->e->f);
	}


	public function testShareInstancesMultiple() {
		$rule = [];
		$rule['shareInstances'] = ['Alma_Gateway_Shared'];
		$dice = $this->dice->addRule('Alma_Gateway_TestSharedInstancesTop', $rule);


		$shareTest = $dice->create('Alma_Gateway_TestSharedInstancesTop');

		$this->assertinstanceOf('Alma_Gateway_TestSharedInstancesTop', $shareTest);

		$this->assertInstanceOf('Alma_Gateway_SharedInstanceTest1', $shareTest->share1);
		$this->assertInstanceOf('Alma_Gateway_SharedInstanceTest2', $shareTest->share2);

		$this->assertSame($shareTest->shared, $shareTest->share1->shared);
		$this->assertSame($shareTest->share1->shared, $shareTest->share2->shared);
		$this->assertEquals($shareTest->shared->uniq, $shareTest->share1->shared->uniq);
		$this->assertEquals($shareTest->share1->shared->uniq, $shareTest->share2->shared->uniq);


		$shareTest2 = $dice->create('Alma_Gateway_TestSharedInstancesTop');
		$this->assertSame($shareTest2->shared, $shareTest2->share1->shared);
		$this->assertSame($shareTest2->share1->shared, $shareTest2->share2->shared);
		$this->assertEquals($shareTest2->shared->uniq, $shareTest2->share1->shared->uniq);
		$this->assertEquals($shareTest2->share1->shared->uniq, $shareTest2->share2->shared->uniq);

		$this->assertNotSame($shareTest->shared, $shareTest2->shared);
		$this->assertNotSame($shareTest->share1->shared, $shareTest2->share2->shared);
		$this->assertNotEquals($shareTest->shared->uniq, $shareTest2->shared->uniq);
		$this->assertNotEquals($shareTest->share1->shared->uniq, $shareTest2->share2->shared->uniq);

	}
}