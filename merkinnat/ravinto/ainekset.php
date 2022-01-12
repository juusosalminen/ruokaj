<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Ainekset</title>
</head>
<body>
<form method="post" action="ainesvienti.php">
<?php
require "../navbar.php";
$maara = $_POST['maara'];

for ($i=0 ; $i < $maara ; $i++) {
    
    echo "<label class=\"lista\" for=\"tuote".$i."\">Tuote</label>
      <input class=\"tuote\" name=\"tuote".$i."\" type=\"text\" list=\"tuotteita\"/>
      <datalist id=\"tuotteita\">";
  
    include "../yhteys.php";

    $sql= "SELECT nimi, tuote_id FROM tuote";

    $kysely = $yhteys->prepare($sql);
    $kysely->execute();
    while ($rivi = $kysely->fetch()) {
        $tuotenimi = htmlspecialchars($rivi["nimi"]);
        $id= $rivi['tuote_id'];
        echo "<option value={$tuotenimi}>{$tuotenimi}</option>";
    }
    echo "</datalist><br/>";
}

echo "<input type=\"hidden\" name=\"maara\" value=\"{$maara}\">";
?>

<input type="submit" value="Lähetä" name="laheta">
</form>
</body>
</html>