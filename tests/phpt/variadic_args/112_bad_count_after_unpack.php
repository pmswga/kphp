@kphp_should_fail
/Too many arguments to function call, expected 2, have 4/
/Too many arguments to function call, expected 2, have 5/
/Too few arguments to function call, expected 2, have 1/
<?php

function f(int $s, int $i) {
    echo $s, $i;
}

f(...[1, 2, 3, 4]);
f(...[1, 2, ...[3, 4]], ...[5]);


function g($a, $b, $c = 0.0) {
}

g(...[1, ...[]]);
