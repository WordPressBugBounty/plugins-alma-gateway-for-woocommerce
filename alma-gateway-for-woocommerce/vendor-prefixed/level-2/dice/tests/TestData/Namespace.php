<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
namespace Alma\Vendor\Foo {

class A {

}

class B {
	public $a;

	public function __construct(A $a) {
		$this->a = $a;
	}
}

class ExtendedA extends A {

}


class C {
	public $a;

	public function __construct(\Alma_Gateway_Bar\A $a) {
		$this->a = $a;
	}
}

}

namespace Alma\Vendor\Bar {
	class Alma_Gateway_A {

	}

	class Alma_Gateway_B {

	}
}