<?php
session_start();
print_r($_SESSION);
// defining an array
$colors = ['red', 'green', 'blue', 1, true];
$colors2 = array('red', 'green', 'blue');
// accessing array elements
echo $colors[0]; // outputs 'red'
echo $colors2[1]; // outputs 'green'
// associative array
$person = [
    'name' => 'John',
    'age' => 30,
    'email' => 'gE9oC@example.com'
];
echo $person['name']; // outputs 'John'
echo $person['age']; // outputs 30

foreach($person as $key => $value) {
    echo "$key: $value<br>";
}
foreach($colors as $color) {
    echo "$color<br>";
}
// debugging arrays
print_r($colors);
var_dump($person);