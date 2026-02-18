<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_MyDirectoryIterator extends DirectoryIterator {

}


class Alma_Gateway_MyDirectoryIterator2 extends DirectoryIterator {
	public function __construct($f) {
		parent::__construct($f);
	}
}

class Alma_Gateway_ParamRequiresArgs {
    public $a;

    public function __construct(Alma_Gateway_D $d, Alma_Gateway_RequiresConstructorArgsA $a) {
        $this->a = $a;
    }
}



class Alma_Gateway_RequiresConstructorArgsB {
	public $a;
	public $foo;
	public $bar;

	public function __construct(Alma_Gateway_A $a, $foo, $bar) {
		$this->a = $a;
		$this->foo = $foo;
		$this->bar = $bar;
	}
}



trait MyTrait {
	public function foo() {}
}

class Alma_Gateway_MyDirectoryIteratorWithTrait extends DirectoryIterator {
	use MyTrait;
}


class Alma_Gateway_NullScalar {
	public $string;

	public function __construct($string = null) {
		$this->string = $string;
    }
}

class Alma_Gateway_NullScalarNested {
	public $nullScalar;

	public function __construct(Alma_Gateway_NullScalar $nullScalar) {
		$this->nullScalar = $nullScalar;
    }
}



class Alma_Gateway_NB {}

class Alma_Gateway_NC {}

class Alma_Gateway_MethodWithTwoDefaultNullC {
	public $a;
	public $b;
	public function __construct($a = null, Alma_Gateway_NB $b = null) {
		$this->a = $a;
		$this->b = $b;
	}
}

class Alma_Gateway_MethodWithTwoDefaultNullCC {
	public $a;
	public $b;
	public $c;
	public function __construct($a = null, Alma_Gateway_NB $b = null, Alma_Gateway_NC $c = null) {
		$this->a = $a;
		$this->b = $b;
		$this->c = $c;
	}
}


class Alma_Gateway_NullableClassTypeHint {
	public $obj;

	public function __construct(?Alma_Gateway_D $obj) {
		$this->obj = $obj;
	}
}
