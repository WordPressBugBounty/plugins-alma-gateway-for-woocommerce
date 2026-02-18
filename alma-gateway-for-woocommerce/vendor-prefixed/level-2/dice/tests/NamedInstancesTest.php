<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_NamedInstancesTest extends Alma_Gateway_DiceTest {
	public function testMultipleSharedInstancesByNameMixed() {
		$rule = [];
		$rule['shared'] = true;
		$rule['constructParams'][] = 'FirstY';

		$dice = $this->dice->addRule('Alma_Gateway_Y', $rule);

		$rule = [];
		$rule['instanceOf'] = 'Alma_Gateway_Y';
		$rule['shared'] = true;
		$rule['inherit'] = false;
		$rule['constructParams'][] = 'SecondY';

		$dice = $dice->addRule('[Alma_Gateway_Y2]', $rule);

		$rule = [];
		$rule['constructParams'] = [ [\Alma\Vendor\Dice\Dice::INSTANCE => 'Alma_Gateway_Y'], [\Alma\Vendor\Dice\Dice::INSTANCE => '[Alma_Gateway_Y2]']];

		$dice = $dice->addRule('Alma_Gateway_Z', $rule);

		$z = $dice->create('Alma_Gateway_Z');
		$this->assertEquals($z->y1->name, 'FirstY');
		$this->assertEquals($z->y2->name, 'SecondY');
	}

	public function testNonSharedComponentByNameA() {
		$rule = [];
		$rule['instanceOf'] = 'Alma_Gateway_ExtendedB';
		$dice = $this->dice->addRule('$B', $rule);

		$rule = [];
		$rule['constructParams'][] = [\Alma\Vendor\Dice\Dice::INSTANCE => '$B'];
		$dice = $dice->addRule('Alma_Gateway_A', $rule);

		$a = $dice->create('Alma_Gateway_A');
		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}

	public function testNonSharedComponentByName() {

		$rule = [];
		$rule['instanceOf'] = 'Alma_Gateway_Y3';
		$rule['constructParams'][] = 'test';


		$dice = $this->dice->addRule('$Y2', $rule);


		$y2 = $dice->create('$Y2');
		//echo $y2->name;
		$this->assertInstanceOf('Alma_Gateway_Y3', $y2);

		$rule = [];

		$rule['constructParams'][] = [\Alma\Vendor\Dice\Dice::INSTANCE => '$Y2'];
		$dice = $dice->addRule('Alma_Gateway_Y1', $rule);

		$y1 = $dice->create('Alma_Gateway_Y1');
		$this->assertInstanceOf('Alma_Gateway_Y3', $y1->y2);
	}

	public function testSubstitutionByName() {
		$rule = [];
		$rule['instanceOf'] = 'Alma_Gateway_ExtendedB';
		$dice = $this->dice->addRule('$B', $rule);

		$rule = [];
		$rule['substitutions']['Alma_Gateway_B'] = [\Alma\Vendor\Dice\Dice::INSTANCE => '$B'];

		$dice = $dice->addRule('Alma_Gateway_A', $rule);
		$a = $dice->create('Alma_Gateway_A');

		$this->assertInstanceOf('Alma_Gateway_ExtendedB', $a->b);
	}

	public function testMultipleSubstitutions() {
		$rule = [];
		$rule['instanceOf'] = 'Alma_Gateway_Y2';
		$rule['constructParams'][] = 'first';
		$dice = $this->dice->addRule('$Y2A', $rule);

		$rule = [];
		$rule['instanceOf'] = 'Alma_Gateway_Y2';
		$rule['constructParams'][] = 'second';
		$dice = $dice->addRule('$Y2B', $rule);

		$rule = [];
		$rule['constructParams'] = array([\Alma\Vendor\Dice\Dice::INSTANCE => '$Y2A'], [\Alma\Vendor\Dice\Dice::INSTANCE => '$Y2B']);
		$dice = $dice->addRule('Alma_Gateway_HasTwoSameDependencies', $rule);

		$twodep = $dice->create('Alma_Gateway_HasTwoSameDependencies');

		$this->assertEquals('first', $twodep->y2a->name);
		$this->assertEquals('second', $twodep->y2b->name);
	}

	public function testNamedInstanceCallWithInheritance() {
		$rule1 = [];
		$rule1['call'] = [
				['callMe', [1, 3] ],
				['callMe', [3, 4] ]
		];

		$dice = $this->dice->addRule('Alma_Gateway_Y', $rule1);

		$rule2 = [];
		$rule2['instanceOf'] = 'Alma_Gateway_Y';
		$rule2['constructParams'] = ['Alma_Gateway_Foo'];

		$dice = $dice->addRule('$MyInstance', $rule2);

		$this->assertEquals(array_merge_recursive($rule1, $rule2), $dice->getRule('$MyInstance'));

	}

	public function testNamedInstanceCallWithoutInheritance() {
		$rule1 = [];
		$rule1['call'] = [
				['callMe', [1, 3] ],
				['callMe', [3, 4] ]
		];

		$dice = $this->dice->addRule('Alma_Gateway_Y', $rule1);

		$rule2 = [];
		$rule2['instanceOf'] = 'Alma_Gateway_Y';
		$rule2['inherit'] = false;
		$rule2['constructParams'] = ['Alma_Gateway_Foo'];

		$dice = $dice->addRule('$MyInstance', $rule2);

		$this->assertEquals($rule2, $dice->getRule('$MyInstance'));
	}

}