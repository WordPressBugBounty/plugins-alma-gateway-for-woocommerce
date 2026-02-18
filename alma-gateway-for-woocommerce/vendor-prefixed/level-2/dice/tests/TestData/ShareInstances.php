<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_TestSharedInstancesTop {
	public $shared;
	public $share1;
	public $share2;

	public function __construct(Alma_Gateway_Shared $shared, Alma_Gateway_SharedInstanceTest1 $share1, Alma_Gateway_SharedInstanceTest2 $share2) {
		$this->shared = $shared;
		$this->share1 = $share1;
		$this->share2 = $share2;
	}
}




class Alma_Gateway_SharedInstanceTest1 {
	public $shared;

	public function __construct(Alma_Gateway_Shared $shared) {
		$this->shared = $shared;
	}
}


class Alma_Gateway_SharedInstanceTest2 {
	public $shared;

	public function __construct(Alma_Gateway_Shared $shared) {
		$this->shared = $shared;
	}
}



class Alma_Gateway_M1 {
	public $f;
	public function __construct(Alma_Gateway_F $f) {
		$this->f = $f;
	}
}

class Alma_Gateway_M2 {
	public $e;
	public function __construct(Alma_Gateway_E $e) {
		$this->e = $e;
	}
}

class Alma_Gateway_Foo77 {
	public $bar;

	public function __construct(Alma_Gateway_Bar77 $bar) {
		$this->bar = $bar;
	}
}

class Alma_Gateway_Bar77 {
	public $a;

	public function __construct($a) {
		$this->a = $a;
	}
}


class Alma_Gateway_Baz77 {
	public static function create() {
		return new Alma_Gateway_Bar77('Alma_Gateway_Z');
	}
}

class Alma_Gateway_Shared {
	public $uniq;

	public function __construct() {
		$this->uniq = uniqid();
	}
}

