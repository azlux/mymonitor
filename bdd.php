<?php
$hostname = 'localhost';
$username = 'sensors';
$password = '**************';

try {
    $bdd = new PDO("mysql:host=$hostname;dbname=sensors;charset=utf8", $username, $password);
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}
