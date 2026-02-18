<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_CreateArgsTest extends Alma_Gateway_DiceTest {

	public function testConsumeArgs() {
		$rule = [];
		$rule['constructParams'] = ['Alma_Gateway_A'];
		$dice = $this->dice->addRule('Alma_Gateway_ConsumeArgsSub', $rule);
		$foo = $dice->create('Alma_Gateway_ConsumeArgsTop',['Alma_Gateway_B']);

		$this->assertEquals('Alma_Gateway_A', $foo->a->s);
	}


    public function testConstructArgs() {
		$obj = $this->dice->create('Alma_Gateway_RequiresConstructorArgsA', array('foo', 'bar'));
		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
	}

	public function testConstructArgsMixed() {
		$obj = $this->dice->create('Alma_Gateway_RequiresConstructorArgsB', array('foo', 'bar'));
		$this->assertEquals($obj->foo, 'foo');
		$this->assertEquals($obj->bar, 'bar');
		$this->assertInstanceOf('Alma_Gateway_A', $obj->a);
	}

	public function testCreateArgs1() {
		$a = $this->dice->create('Alma_Gateway_A', array($this->dice->create('Alma_Gateway_ExtendedB')));
		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}


	public function testCreateArgs2() {
		$a2 = $this->dice->create('Alma_Gateway_A2', array($this->dice->create('Alma_Gateway_ExtendedB'), 'Alma_Gateway_Foo'));
		$this->assertInstanceOf('Alma_Gateway_B', $a2->b);
		$this->assertInstanceOf('Alma_Gateway_C', $a2->c);
		$this->assertEquals($a2->foo, 'Alma_Gateway_Foo');
	}


	public function testCreateArgs3() {
		//reverse order args. It should be smart enough to handle this.
		$a2 = $this->dice->create('Alma_Gateway_A2', array('Alma_Gateway_Foo', $this->dice->create('Alma_Gateway_ExtendedB')));
		$this->assertInstanceOf('Alma_Gateway_B', $a2->b);
		$this->assertInstanceOf('Alma_Gateway_C', $a2->c);
		$this->assertEquals($a2->foo, 'Alma_Gateway_Foo');
	}

	public function testCreateArgs4() {
		$a2 = $this->dice->create('Alma_Gateway_A3', array('Alma_Gateway_Foo', $this->dice->create('Alma_Gateway_ExtendedB')));
		$this->assertInstanceOf('Alma_Gateway_B', $a2->b);
		$this->assertInstanceOf('Alma_Gateway_C', $a2->c);
		$this->assertEquals($a2->foo, 'Alma_Gateway_Foo');
	}

	public function testBestMatch() {
		$bestMatch = $this->dice->create('Alma_Gateway_BestMatch', array('foo', $this->dice->create('Alma_Gateway_A')));
		$this->assertEquals('foo', $bestMatch->string);
		$this->assertInstanceOf('Alma_Gateway_A', $bestMatch->a);
	}

	public function testTwoDefaultNullClass() {
		$obj = $this->dice->create('Alma_Gateway_MethodWithTwoDefaultNullC');
        $this->assertNull($obj->a);
		$this->assertInstanceOf('Alma_Gateway_NB',$obj->b);
    }

    public function testTwoDefaultNullClassClass() {
		$obj = $this->dice->create('Alma_Gateway_MethodWithTwoDefaultNullCC');
        $this->assertNull($obj->a);
		$this->assertInstanceOf('Alma_Gateway_NB',$obj->b);
		$this->assertInstanceOf('Alma_Gateway_NC',$obj->c);
    }

    public function testScalarConstructorArgs() {
    	$obj = $this->dice->create('Alma_Gateway_ScalarConstructors', ['string', null]);
    	$this->assertEquals('string', $obj->string);
    	$this->assertEquals(null, $obj->null);
    }

}