<?php
$hostname = 'localhost';
$username = 'sensors';
$password = 'th61r5h48tr1h5g1fdhb64ht8jngzsq1e6r5';

try {
    $bdd = new PDO("mysql:host=$hostname;dbname=sensors;charset=utf8", $username, $password);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}
