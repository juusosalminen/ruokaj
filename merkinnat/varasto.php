<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Varasto</title>
</head>
<body>
<form method="post" action="varaston_muutos.php" >
<table>
<tr><th>Lajike</th><th>Varastossa</th></tr>

<?php
require "yhteys.php";

$sql='SELECT nimi, maara, tuote_id FROM tuote';

$kysely = $yhteys->prepare($sql);
$kysely->execute();
$i=0;
while ($rivi = $kysely->fetch()) {
    $nimi=$rivi['nimi'];
    $maara=$rivi['maara'];
    $id=$rivi['tuote_id'];
    echo ("<tr><td>{$nimi}</td><td><input type=\"text\" name=\"maara".$i."\" value=\"{$maara}\"></td>");
    echo ("<td><input type=\"hidden\" name=\"id".$i."\" value=\"{$id}\"></td></tr>");
    $i++;
}
echo "</table>";
echo "<input type=\"hidden\" name=\"luku\" value=\"{$i}\">";

?>

<input type="submit" value="Lähetä">
</form>
</body>
</html>