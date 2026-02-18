<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_NamespaceTest extends Alma_Gateway_DiceTest {
	public function testNamespaceBasic() {
		$a = $this->dice->create('Alma\\Vendor\\Foo\\A');
		$this->assertInstanceOf('Alma\\Vendor\\Foo\\A', $a);
	}


	public function testNamespaceWithSlash() {
		$a = $this->dice->create('\\Alma\\Vendor\\Foo\\A');
		$this->assertInstanceOf('\\Alma\\Vendor\\Foo\\A', $a);
	}

	public function testNamespaceWithSlashrule() {
		$rule = [];
		$rule['substitutions']['Alma\\Vendor\\Foo\\A'] = [\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma\\Vendor\\Foo\\ExtendedA'];
		$dice = $this->dice->addRule('\\Alma\\Vendor\\Foo\\B', $rule);

		$b = $dice->create('\\Alma\\Vendor\\Foo\\B');
		$this->assertInstanceOf('Alma\\Vendor\\Foo\\ExtendedA', $b->a);
	}

	public function testNamespaceWithSlashruleInstance() {
		$rule = [];
		$rule['substitutions']['Alma\\Vendor\\Foo\\A'] = [\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma\\Vendor\\Foo\\ExtendedA'];
		$dice = $this->dice->addRule('\\Alma\\Vendor\\Foo\\B', $rule);

		$b = $dice->create('\\Alma\\Vendor\\Foo\\B');
		$this->assertInstanceOf('Alma\\Vendor\\Foo\\ExtendedA', $b->a);
	}

	public function testNamespaceTypeHint() {
		$rule = [];
		$rule['shared'] = true;
		$dice = $this->dice->addRule('Alma\\Vendor\\Bar\\A', $rule);

		$c = $dice->create('Alma\\Vendor\\Foo\\C');
		$this->assertInstanceOf('Alma\\Vendor\\Bar\\A', $c->a);

		$c2 = $dice->create('Alma\\Vendor\\Foo\\C');
		$this->assertNotSame($c, $c2);

		//Check the rule has been correctly recognised for type hinted classes in a different namespace
		$this->assertSame($c2->a, $c->a);
	}

	public function testNamespaceInjection() {
		$b = $this->dice->create('Alma\\Vendor\\Foo\\B');
		$this->assertInstanceOf('Alma\\Vendor\\Foo\\B', $b);
		$this->assertInstanceOf('Alma\\Vendor\\Foo\\A', $b->a);
	}


	public function testNamespaceRuleSubstitution() {
		$rule = [];
		$rule['substitutions']['Alma\\Vendor\\Foo\\A'] = [\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma\\Vendor\\Foo\\ExtendedA'];
		$dice = $this->dice->addRule('Alma\\Vendor\\Foo\\B', $rule);

		$b = $dice->create('Alma\\Vendor\\Foo\\B');
		$this->assertInstanceOf('Alma\\Vendor\\Foo\\ExtendedA', $b->a);
	}

}