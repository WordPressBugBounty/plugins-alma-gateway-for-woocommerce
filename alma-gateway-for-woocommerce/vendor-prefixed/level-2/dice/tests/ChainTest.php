<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_ChainTest extends Alma_Gateway_DiceTest {
	public function testChainCall() {
		$dice = $this->dice->addRules([
				'$someClass' => [
					'instanceOf' => 'Alma_Gateway_Factory',
					'call' => [
						['get', [], \Alma\Vendor\Dice\Dice::CHAIN_CALL]
					]
				]
		]);

		$obj = $dice->create('$someClass');

		$this->assertInstanceOf('Alma_Gateway_FactoryDependency', $obj);

	}

    public function testMultipleChainCall() {
        $dice = $this->dice->addRules([
            '$someClass' => [
                'instanceOf' => 'Alma_Gateway_Factory',
                'call' => [
                    ['get', [], \Alma\Vendor\Dice\Dice::CHAIN_CALL],
                    ['getBar', [], \Alma\Vendor\Dice\Dice::CHAIN_CALL]
                ]
            ]
        ]);

        $obj = $dice->create('$someClass');

        $this->assertEquals('bar', $obj);

    }

	public function testChainCallShared() {
		$dice = $this->dice->addRules([
				'$someClass' => [
					'shared' => true,
					'instanceOf' => 'Alma_Gateway_Factory',
					'call' => [
						['get', [], \Alma\Vendor\Dice\Dice::CHAIN_CALL]
					]
				]
		]);

		$obj = $dice->create('$someClass');

		$this->assertInstanceOf('Alma_Gateway_FactoryDependency', $obj);
	}


	public function testChainCallInject() {
		$dice = $this->dice->addRules([
				'Alma_Gateway_FactoryDependency' => [
					'instanceOf' => 'Alma_Gateway_Factory',
					'call' => [
						['get', [], \Alma\Vendor\Dice\Dice::CHAIN_CALL]
					]
				]
		]);

		$obj = $dice->create('Alma_Gateway_RequiresFactoryDependecy');

		$this->assertInstanceOf('Alma_Gateway_FactoryDependency', $obj->dep);
	}

	public function testChainCallInjectShared() {
		$dice = $this->dice->addRules([
				'Alma_Gateway_FactoryDependency' => [
					'shared' => true,
					'instanceOf' => 'Alma_Gateway_Factory',
					'call' => [
						['get', [], \Alma\Vendor\Dice\Dice::CHAIN_CALL]
					]
				]
		]);


		$dice->create('Alma_Gateway_FactoryDependency');

		$obj = $dice->create('Alma_Gateway_RequiresFactoryDependecy');

		$this->assertInstanceOf('Alma_Gateway_FactoryDependency', $obj->dep);

		$obj2 = $dice->create('Alma_Gateway_RequiresFactoryDependecy');


		$this->assertNotSame($obj, $obj2);
		$this->assertSame($obj->dep, $obj2->dep);
	}

}


class Alma_Gateway_Factory {
	public function get() {
		return new Alma_Gateway_FactoryDependency;
	}
}

class Alma_Gateway_FactoryDependency {
    public function getBar() {
        return 'bar';
    }
}

class Alma_Gateway_RequiresFactoryDependecy {
	public $dep;

	public function __construct(Alma_Gateway_FactoryDependency $dep) {
		$this->dep = $dep;
	}
}