<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */




class Alma_Gateway_BasicTest extends Alma_Gateway_DiceTest {

	public function testCreate() {
		$this->getMockBuilder('TestCreate')->getMock();
		$myobj = $this->dice->create('TestCreate');
		$this->assertInstanceOf('TestCreate', $myobj);
	}



	public function testCreateInvalid() {
		//"can't expect default exception". Not sure why.
		$this->expectException('ErrorException');
		try {
			$this->dice->create('SomeClassThatDoesNotExist');
		}
		catch (Exception $e) {
			throw new ErrorException('Error occurred');
		}
	}

	public function testNoConstructor() {
		$a = $this->dice->create('Alma_Gateway_NoConstructor');
		$this->assertInstanceOf('Alma_Gateway_NoConstructor', $a);
	}


	public function testSetDefaultRule() {
		$defaultBehaviour = [];
		$defaultBehaviour['shared'] = true;
		$dice = $this->dice->addRule('*', $defaultBehaviour);

		$rule = $dice->getRule('*');
		foreach ($defaultBehaviour as $name => $value) {
			$this->assertEquals($rule[$name], $defaultBehaviour[$name]);
		}
	}


	public function testDefaultRuleWorks() {
		$defaultBehaviour = [];
		$defaultBehaviour['shared'] = true;

		$dice = $this->dice->addRule('*', $defaultBehaviour);

		$rule = $dice->getRule('Alma_Gateway_A');

		$this->assertTrue($rule['shared']);

		$a1 = $dice->create('Alma_Gateway_A');
		$a2 = $dice->create('Alma_Gateway_A');

		$this->assertSame($a1, $a2);
	}


	/*
	 * Object graph creation cannot be tested with mocks because the constructor need to be tested.
	 * You can't set 'expects' on the objects which are created making them redundant for that as well
	 * Need real classes to test with unfortunately.
	 */
	public function testObjectGraphCreation() {
		$a = $this->dice->create('Alma_Gateway_A');
		$this->assertInstanceOf('Alma_Gateway_B', $a->b);
		$this->assertInstanceOf('c', $a->b->c);
		$this->assertInstanceOf('Alma_Gateway_D', $a->b->c->d);
		$this->assertInstanceOf('Alma_Gateway_E', $a->b->c->e);
		$this->assertInstanceOf('Alma_Gateway_F', $a->b->c->e->f);
	}

	public function testSharedNamed() {
		$rule = [];
		$rule['shared'] = true;
		$rule['instanceOf'] = 'Alma_Gateway_A';

		$dice = $this->dice->addRule('[Alma_Gateway_A]', $rule);

		$a1 = $dice->create('[Alma_Gateway_A]');
		$a2 = $dice->create('[Alma_Gateway_A]');
		$this->assertSame($a1, $a2);
	}

	public function testSharedRule() {
		$shared = [];
		$shared['shared'] = true;

		$dice = $this->dice->addRule('Alma_Gateway_MyObj', $shared);

		$obj = $dice->create('Alma_Gateway_MyObj');
		$this->assertInstanceOf('Alma_Gateway_MyObj', $obj);

		$obj2 = $dice->create('Alma_Gateway_MyObj');
		$this->assertInstanceOf('Alma_Gateway_MyObj', $obj2);

		$this->assertSame($obj, $obj2);


		//This check isn't strictly needed but it's nice to have that safety measure!
		$obj->setFoo('bar');
		$this->assertEquals($obj->getFoo(), $obj2->getFoo());
		$this->assertEquals($obj->getFoo(), 'bar');
		$this->assertEquals($obj2->getFoo(), 'bar');
	}


	public function testInterfaceRule() {
		$rule = [];

		$rule['shared'] = true;
		$dice =  $this->dice->addRule('interfaceTest', $rule);

		$one = $dice->create('Alma_Gateway_InterfaceTestClass');
		$two = $dice->create('Alma_Gateway_InterfaceTestClass');

		$this->assertSame($one, $two);
	}

	public function testCyclicReferences() {
		$rule = [];
		$rule['shared'] = true;

		$dice =  $this->dice->addRule('Alma_Gateway_CyclicB', $rule);

		$a = $dice->create('Alma_Gateway_CyclicA');

		$this->assertInstanceOf('Alma_Gateway_CyclicB', $a->b);
		$this->assertInstanceOf('Alma_Gateway_CyclicA', $a->b->a);

		$this->assertSame($a->b, $a->b->a->b);
	}

	public function testInherit() {
		$rule = ['shared' => true, 'inherit' => false];

		$dice = $this->dice->addRule('Alma_Gateway_ParentClass', $rule);
		$obj1 = $dice->create('Alma_Gateway_Child');
		$obj2 = $dice->create('Alma_Gateway_Child');
		$this->assertNotSame($obj1, $obj2);
	}

	public function testSharedOverride() {

		//Set everything to shared by default
		$dice = $this->dice->addRule('*', ['shared' => true]);

		$dice =  $dice->addRule('Alma_Gateway_A', ['shared' => false]);

		$a1 = $dice->create('Alma_Gateway_A');
		$a2 = $dice->create('Alma_Gateway_A');

		$this->assertNotSame($a1, $a2);

	}

	public function testOptionalInterface() {

		$optionalInterface = $this->dice->create('Alma_Gateway_OptionalInterface');

		$this->assertEquals(null, $optionalInterface->obj);
	}


	public function testScalarTypeHintWithShareInstances() {

		$dice = $this->dice->addRule('Alma_Gateway_ScalarTypeHint', ['shareInstances' => ['Alma_Gateway_A']]);

		$obj = $dice->create('Alma_Gateway_ScalarTypeHint');

		$this->assertInstanceOf('Alma_Gateway_ScalarTypeHint', $obj);
	}

	public function testPassGlobals() {
		//write to the global $_GET variable
		$_GET['foo'] = 'bar';

		$dice = $this->dice->addRule('Alma_Gateway_CheckConstructorArgs',
			[
				'constructParams' => [
					[\Alma\Vendor\Dice\Dice::GLOBAL => '_GET']
				]
		]);

		$obj = $dice->create('Alma_Gateway_CheckConstructorArgs');

		$this->assertEquals($_GET, $obj->arg1);
	}

	public function testPassConstantString() {
		$dice = $this->dice->addRule('Alma_Gateway_CheckConstructorArgs',
			[
				'constructParams' => [
					[\Alma\Vendor\Dice\Dice::CONSTANT => '\PDO::FETCH_ASSOC']
				]
		]);

		$obj = $dice->create('Alma_Gateway_CheckConstructorArgs');

		$this->assertEquals(\PDO::FETCH_ASSOC, $obj->arg1);
	}

	public function testImmutability() {
		$this->assertEquals([], $this->dice->getRule('Alma_Gateway_Foo'));

		$dice = $this->dice->addRule('Alma_Gateway_Foo', ['shared' => true]);

		$this->assertEquals([], $this->dice->getRule('Alma_Gateway_Foo'));
	}

	public function testPassSelf() {
        $dice = $this->dice->addRule('Alma_Gateway_CheckConstructorArgs',
            [
                'constructParams' => [
                    [\Alma\Vendor\Dice\Dice::INSTANCE => \Alma\Vendor\Dice\Dice::SELF]
                ]
            ]);

        $obj = $dice->create('Alma_Gateway_CheckConstructorArgs');

        $this->assertEquals($dice, $obj->arg1);
    }


    // Issue 180
	public function testSlashNoSlash() {
		$dice = $this->dice->addRule('\someclass', ['shared' => true]);

		$b = $dice->create('\someotherclass');
		$a = $dice->create('\someclass');

		$this->assertSame($a, $b->obj);
	}
}