<?php

$tiedot = parse_ini_file("conf.ini");
$host = $tiedot['host'];
$username = $tiedot['username'];
$password = $tiedot['password'];
try {
    $yhteys = new PDO("mysql:host={$host}:3306;dbname=ruoka;", $username, $password);
} catch (PDOException $e) {
    die("VIRHE: " . $e->getMessage());
}
// virheenkäsittely: virheet aiheuttavat poikkeuksen
$yhteys->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>