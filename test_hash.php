<?php
$password = 'admin';
$hash_admin = '$2y$10$kRiib.6x53OtyOABfOVdsu1u20wd1lgNgNpJS6QJDhrnv9l2Ss4uq';
$hash_admin1 = '$2y$10$4Ed.2N4rF4mZ3Qz3oW8zq.A1I4V8p6k4r7Qz2x3y5W6z7A8B9C0D';
var_dump(password_verify($password, $hash_admin));
var_dump(password_verify($password, $hash_admin1));
?>