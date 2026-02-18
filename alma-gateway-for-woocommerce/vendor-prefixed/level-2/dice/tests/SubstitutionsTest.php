<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_SubstitutionsTest extends Alma_Gateway_DiceTest {
	public function testNoMoreAssign() {
		$rule = [];
		$rule['substitutions']['Alma_Gateway_Bar77'] = [\Alma\Vendor\Dice\Dice::INSTANCE => function() {
			return Alma_Gateway_Baz77::create();
		}];

		$dice = $this->dice->addRule('Alma_Gateway_Foo77', $rule);

		$foo = $dice->create('Alma_Gateway_Foo77');

		$this->assertInstanceOf('Alma_Gateway_Bar77', $foo->bar);
		$this->assertEquals('Alma_Gateway_Z', $foo->bar->a);
	}

	public function testNullSubstitution() {
		$rule = [];
		$rule['substitutions']['Alma_Gateway_B'] = null;
		$dice = $this->dice->addRule('Alma_Gateway_MethodWithDefaultNull', $rule);
		$obj = $dice->create('Alma_Gateway_MethodWithDefaultNull');
		$this->assertNull($obj->b);
	}

	public function testSubstitutionText() {
		$rule = [];
		$rule['substitutions']['Alma_Gateway_B'] = [\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma_Gateway_ExtendedB'];
		$dice = $this->dice->addRule('Alma_Gateway_A', $rule);

		$a = $dice->create('Alma_Gateway_A');

		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}

	public function testSubstitutionTextMixedCase() {
		$rule = [];
		$rule['substitutions']['Alma_Gateway_B'] = [\Alma\Vendor\Dice\Dice::INSTANCE => 'exTenDedb'];
		$dice = $this->dice->addRule('Alma_Gateway_A', $rule);

		$a = $dice->create('Alma_Gateway_A');

		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}

	public function testSubstitutionCallback() {
		$rule = [];
		$injection = $this->dice;
		$rule['substitutions']['Alma_Gateway_B'] = [\Alma\Vendor\Dice\Dice::INSTANCE => function() use ($injection) {
			return $injection->create('Alma_Gateway_ExtendedB');
		}];

		$dice = $this->dice->addRule('Alma_Gateway_A', $rule);

		$a = $dice->create('Alma_Gateway_A');

		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}


	public function testSubstitutionObject() {
		$rule = [];

		$rule['substitutions']['Alma_Gateway_B'] = $this->dice->create('Alma_Gateway_ExtendedB');

		$dice = $this->dice->addRule('Alma_Gateway_A', $rule);

		$a = $dice->create('Alma_Gateway_A');
		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}

	public function testSubstitutionString() {
		$rule = [];

		$rule['substitutions']['Alma_Gateway_B'] = [\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma_Gateway_ExtendedB'];

		$dice = $this->dice->addRule('Alma_Gateway_A', $rule);

		$a = $dice->create('Alma_Gateway_A');
		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}


	public function testSubFromString() {
		$rule = [
			'substitutions' => ['Alma\Vendor\Bar' => 'Alma_Gateway_Baz']
		];
		$dice = $this->dice->addRule('*', $rule);

		$obj = $dice->create('Alma_Gateway_Foo');

		$this->assertInstanceOf('Alma_Gateway_Baz', $obj->bar);

	}

	public function testSubstitutionWithFuncCall() {
		$rule = [];

		$rule['substitutions']['Alma\Vendor\Bar'] = [\Alma\Vendor\Dice\Dice::INSTANCE => ['Alma_Gateway_Foo2', 'bar']];

		$dice = $this->dice->addRule('Alma_Gateway_Foo', $rule);

		$a = $dice->create('Alma_Gateway_Foo');
		$this->assertInstanceOf('Alma_Gateway_Baz', $a->bar);
	}
}


class Alma_Gateway_Foo {
	public $bar;
	public function __construct(Bar $bar) {
		$this->bar = $bar;
	}
}

class Alma_Gateway_Foo2 {
	public function bar() {
		return new Alma_Gateway_Baz;
	}
}

interface Bar {

}

class Alma_Gateway_Baz implements Bar {

}
