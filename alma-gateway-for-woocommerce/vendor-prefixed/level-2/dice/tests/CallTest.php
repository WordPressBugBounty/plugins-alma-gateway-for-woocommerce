<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_CallTest extends Alma_Gateway_DiceTest {
	public function testCall() {
		$rule = [];
		$rule['call'][] = array('callMe', array());
		$dice = $this->dice->addRule('Alma_Gateway_TestCall', $rule);
		$object = $dice->create('Alma_Gateway_TestCall');
		$this->assertTrue($object->isCalled);
	}

	public function testCallWithParameters() {
		$rule = [];
		$rule['call'][] = array('callMe', array('one', 'two'));
		$dice = $this->dice->addRule('Alma_Gateway_TestCall2', $rule);
		$object = $dice->create('Alma_Gateway_TestCall2');
		$this->assertEquals('one', $object->foo);
		$this->assertEquals('two', $object->bar);
	}

	public function testCallWithInstance() {
		$rule = [];
		$rule['call'][] = array('callMe', array([\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma_Gateway_A']));
		$dice = $this->dice->addRule('Alma_Gateway_TestCall3', $rule);
		$object = $dice->create('Alma_Gateway_TestCall3');

		$this->assertInstanceOf('a', $object->a);

	}

	public function testCallAutoWireInstance() {
		$rule = [];
		$rule['call'][] = array('callMe', []);
		$dice = $this->dice->addRule('Alma_Gateway_TestCall3', $rule);
		$object = $dice->create('Alma_Gateway_TestCall3');

		$this->assertInstanceOf('a', $object->a);
	}

	public function testCallReturnValue() {
		$rule = [];

		$returnValue = null;

		$rule['call'][] = array('callMe', [], function($return) use (&$returnValue) {
			$returnValue = $return;
		});


		$dice = $this->dice->addRule('Alma_Gateway_TestCall3', $rule);
		$object = $dice->create('Alma_Gateway_TestCall3');

		$this->assertInstanceOf('a', $object->a);
		$this->assertEquals('callMe called', $returnValue);
	}


	public function testCallChain() {
		$rules = [
			'Alma_Gateway_TestCallImmutable' => [
				'call' => [
					['call1', ['foo'], \Alma\Vendor\Dice\Dice::CHAIN_CALL],
					['call2', ['bar'], \Alma\Vendor\Dice\Dice::CHAIN_CALL]
				]
			]
		];

		$dice = $this->dice->addRules($rules);

		$object = $dice->create('Alma_Gateway_TestCallImmutable');

		$this->assertEquals('foo', $object->a);
		$this->assertEquals('bar', $object->b);
	}

	public function testCallShareVariadic() {
	    // Alma_Gateway_Shared params should not be passed to variadic call

        $rules = [
            'Alma_Gateway_TestCallVariadic' => [
                'call' => [
                    ['callMe', ['test1']]
                ]
            ]
        ];

        $dice = $this->dice->addRules($rules);
        $object = $dice->create('Alma_Gateway_TestCallVariadic', [], [new Alma_Gateway_F()]);

        $this->assertEquals(['test1'], $object->data);
    }
}