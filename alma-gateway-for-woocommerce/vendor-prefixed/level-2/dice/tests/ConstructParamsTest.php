<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_ConstructParamsTest extends Alma_Gateway_DiceTest {

	public function testConstructParams() {
		$rule = [];
		$rule['constructParams'] = array('foo', 'bar');
		$dice = $this->dice->addRule('Alma_Gateway_RequiresConstructorArgsA', $rule);

		$obj = $dice->create('Alma_Gateway_RequiresConstructorArgsA');

		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
	}


	public function testInternalClass() {
		$rule = [];
		$rule['constructParams'][] = '.';

		$dice = $this->dice->addRule('DirectoryIterator', $rule);

		$dir = $dice->create('DirectoryIterator');

		$this->assertInstanceOf('DirectoryIterator', $dir);
	}

	public function testInternalClassExtended() {
		$rule = [];
		$rule['constructParams'][] = '.';

		$dice = $this->dice->addRule('Alma_Gateway_MyDirectoryIterator', $rule);

		$dir = $dice->create('Alma_Gateway_MyDirectoryIterator');

		$this->assertInstanceOf('Alma_Gateway_MyDirectoryIterator', $dir);
	}


	public function testInternalClassExtendedConstructor() {
		$rule = [];
		$rule['constructParams'][] = '.';

		$dice = $this->dice->addRule('Alma_Gateway_MyDirectoryIterator2', $rule);

		$dir = $dice->create('Alma_Gateway_MyDirectoryIterator2');

		$this->assertInstanceOf('Alma_Gateway_MyDirectoryIterator2', $dir);
	}

	public function testDefaultNullAssigned() {
		$rule = [];
		$rule['constructParams'] = [ [\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma_Gateway_A'], null];
		$dice = $this->dice->addRule('Alma_Gateway_MethodWithDefaultNull', $rule);
		$obj = $dice->create('Alma_Gateway_MethodWithDefaultNull');
		$this->assertNull($obj->b);
	}

	public function testConstructParamsNested() {
		$rule = [];
		$rule['constructParams'] = array('foo', 'bar');
		$dice = $this->dice->addRule('Alma_Gateway_RequiresConstructorArgsA', $rule);

		$rule = [];
		$rule['shareInstances'] = array('Alma_Gateway_D');
		$dice = $dice->addRule('Alma_Gateway_ParamRequiresArgs', $rule);

		$obj = $dice->create('Alma_Gateway_ParamRequiresArgs');

		$this->assertEquals($obj->a->foo, 'foo');
		$this->assertEquals($obj->a->bar, 'bar');
	}


	public function testConstructParamsMixed() {
		$rule = [];
		$rule['constructParams'] = array('foo', 'bar');
		$dice = $this->dice->addRule('Alma_Gateway_RequiresConstructorArgsB', $rule);

		$obj = $dice->create('Alma_Gateway_RequiresConstructorArgsB');

		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
		$this->assertInstanceOf('Alma_Gateway_A', $obj->a);
	}


	public function testSharedClassWithTraitExtendsInternalClass()	{
		$rule = [];
		$rule['constructParams'] = ['.'];

		$dice = $this->dice->addRule('Alma_Gateway_MyDirectoryIteratorWithTrait', $rule);

		$dir = $dice->create('Alma_Gateway_MyDirectoryIteratorWithTrait');

		$this->assertInstanceOf('Alma_Gateway_MyDirectoryIteratorWithTrait', $dir);
	}

	public function testConstructParamsPrecedence() {
		$rule = [];
		$rule['constructParams'] = ['Alma_Gateway_A', 'Alma_Gateway_B'];
		$dice = $this->dice->addRule('Alma_Gateway_RequiresConstructorArgsA', $rule);

		$a1 = $dice->create('Alma_Gateway_RequiresConstructorArgsA');
		$this->assertEquals('Alma_Gateway_A', $a1->foo);
		$this->assertEquals('Alma_Gateway_B', $a1->bar);

		$a2 = $dice->create('Alma_Gateway_RequiresConstructorArgsA', ['Alma_Gateway_C', 'Alma_Gateway_D']);
		$this->assertEquals('Alma_Gateway_C', $a2->foo);
		$this->assertEquals('Alma_Gateway_D', $a2->bar);
	}

	public function testNullScalar() {
		$rule = [];
		$rule['constructParams'] = [null];
		$dice = $this->dice->addRule('Alma_Gateway_NullScalar', $rule);

		$obj = $dice->create('Alma_Gateway_NullScalar');
		$this->assertEquals(null, $obj->string);
	}

	public function testNullScalarNested() {
		$rule = [];
		$rule['constructParams'] = [null];
		$dice = $this->dice->addRule('Alma_Gateway_NullScalar', $rule);

		$obj = $dice->create('Alma_Gateway_NullScalarNested');
		$this->assertEquals(null, $obj->nullScalar->string);
	}

	public function testNullableClassTypeHint() {
		$nullableClassTypeHint = $this->dice->create('Alma_Gateway_NullableClassTypeHint');

		$this->assertEquals(null, $nullableClassTypeHint->obj);
	}

}