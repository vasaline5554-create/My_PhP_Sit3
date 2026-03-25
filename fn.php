<?php
function say() {
    echo "Hello, World!";
}
function sayHello($name = '') {
    echo "Hello, $name!";
}
function add($a=0, $b = 0) {
    return $a + $b;
}
$x = add(1,2);
$y = add(3,4);
echo $x + $y;
say();
sayHello();
// native function or built-in function
date_default_timezone_set('Asia/Bangkok');
echo date('d/m/Y h:i');

class Person {
    public $name = "John Doe"; // property or attribute or field or data
    public function sayHello() { // method or member function
        echo "Hello, $this->name";
    }
}

$person = new Person();
$person->name = "Dev";
$person->sayHello();


?>