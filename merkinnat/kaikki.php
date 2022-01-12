<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<body>
<form action="lopetus.php" method="post">
<table class=table20>
<tr>
    <th>Ostos-id</th>
    <th>Tuotteen nimi</th>
    <th>Osto</th>
    <th>Loppuminen</th>
    <th> </th>
</tr>
<?php
require "yhteys.php";
include "navbar.php";
$i=0;
$sql= "SELECT nimi, pvm_alku, pvm_loppu, ostos_id 
FROM ostos JOIN tuote 
USING(tuote_id)
WHERE pvm_loppu IS NULL";
$kysely = $yhteys->prepare($sql);
    $kysely->execute();
$j=0;
while ($rivi = $kysely->fetch()) {
        $tuote= $rivi['nimi'];
        $alkupvm= $rivi['pvm_alku'];
        $loppuminen= $rivi['pvm_loppu'];
        $id = $rivi['ostos_id'];
        echo "<tr><td>{$id}</td><td>{$tuote}</td><td>{$alkupvm}</td>";
    if (is_null($loppuminen)) {
            echo "<td>
            <input type=\"date\" name=\"loppupvm".$i."\"/></td><td><input type=\"hidden\" name=\"id".$i."\" value=\"{$id}\"></td></tr>";
            $i++;
    } else {
        echo "<td>{$loppuminen}</td></tr>";
    }
    $j++;
    if ($j % 20 == 0) {
        echo "</table>";
        echo "<table class=\"table".$j."\">
        <tr>
        <th>Ostos-id</th>
        <th>Tuotteen nimi</th>
        <th>Osto</th>
        <th>Loppuminen</th>
        <th> </th>
        </tr>";
    }
}
echo "</table>";
echo "<input type=\"hidden\" name=\"maara\" value=\"{$i}\">";
?>

<input type="submit" value="Lähetä tiedot" name="laheta">
</form>
</body>
</html>