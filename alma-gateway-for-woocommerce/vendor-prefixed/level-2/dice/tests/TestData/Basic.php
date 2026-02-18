<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_NoConstructor {
	public $a = 'b';
}

class Alma_Gateway_CyclicA {
	public $b;

	public function __construct(Alma_Gateway_CyclicB $b) {
		$this->b = $b;
	}
}

class Alma_Gateway_CyclicB {
	public $a;

	public function __construct(Alma_Gateway_CyclicA $a) {
		$this->a = $a;
	}
}


class Alma_Gateway_A {
	public $b;

	public function __construct(Alma_Gateway_B $b) {
		$this->b = $b;
	}
}

class Alma_Gateway_B {
	public $c;

	public function __construct(Alma_Gateway_C $c) {
		$this->c = $c;
	}
}

class Alma_Gateway_ExtendedB extends Alma_Gateway_B {

}

class Alma_Gateway_C {
	public $d;
	public $e;

	public function __construct(Alma_Gateway_D $d, Alma_Gateway_E $e) {
		$this->d = $d;
		$this->e = $e;
	}
}


class Alma_Gateway_D {

}

class Alma_Gateway_E {
	public $f;
	public function __construct(Alma_Gateway_F $f) {
		$this->f = $f;
	}
}

class Alma_Gateway_F {}

class Alma_Gateway_RequiresConstructorArgsA {
	public $foo;
	public $bar;

	public function __construct($foo, $bar) {
		$this->foo = $foo;
		$this->bar = $bar;
	}
}

class Alma_Gateway_MyObj {
	private $foo;

	public function setFoo($foo) {
		$this->foo = $foo;
	}

	public function getFoo() {
		return $this->foo;
	}
}


class Alma_Gateway_MethodWithDefaultValue {
	public $a;
	public $foo;

	public function __construct(Alma_Gateway_A $a, $foo = 'bar') {
		$this->a = $a;
		$this->foo = $foo;
	}
}

class Alma_Gateway_MethodWithDefaultNull {
	public $a;
	public $b;
	public function __construct(Alma_Gateway_A $a, Alma_Gateway_B $b = null) {
		$this->a = $a;
		$this->b = $b;
	}
}


interface interfaceTest {}

class Alma_Gateway_InterfaceTestClass implements interfaceTest {

}


class Alma_Gateway_ParentClass {
}
class Alma_Gateway_Child extends Alma_Gateway_ParentClass {
}

class Alma_Gateway_OptionalInterface {
	public $obj;

	public function __construct(InterfaceTest $obj = null) {
		$this->obj = $obj;
	}
}


class Alma_Gateway_ScalarTypeHint {
	public function __construct(string $a = null) {

	}
}

class Alma_Gateway_CheckConstructorArgs {
	public $arg1;

	public function __construct($arg1) {
		$this->arg1 = $arg1;
	}
}


class Alma_Gateway_someclass {}

class Alma_Gateway_someotherclass {
	public $obj;
	public function __construct(Alma_Gateway_someclass $obj){
	    $this->obj = $obj;
	}
}