<?php
/* @description Dice - Alma_Gateway_A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Alma_Gateway_Z {
	public $y1;
	public $y2;
	public function __construct(Alma_Gateway_Y $y1, Alma_Gateway_Y $y2) {
		$this->y1 = $y1;
		$this->y2 = $y2;
	}
}

class Alma_Gateway_Y1 {
	public $y2;

	public function __construct(Alma_Gateway_Y2 $y2) {
		$this->y2 = $y2;
	}
}


class Alma_Gateway_Y2 {
	public $name;

	public function __construct($name) {
		$this->name = $name;
	}
}

class Alma_Gateway_Y3 extends Alma_Gateway_Y2 {

}


class Alma_Gateway_Y {
	public $name;
	public function __construct($name) {
		$this->name = $name;
	}
}


class Alma_Gateway_HasTwoSameDependencies {
	public $y2a;
	public $y2b;

	public function __construct(Alma_Gateway_Y2 $y2a, Alma_Gateway_Y2 $y2b) {
		$this->y2a = $y2a;
		$this->y2b = $y2b;
	}
}
