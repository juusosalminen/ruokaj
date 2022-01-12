<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<?php
require "yhteys.php";
$maara = $_POST["maara"];

for ($i=0; $i < $maara; $i++) {
        $tuote = $_POST["tuote".$i];
        $hinta = $_POST["hinta".$i];
        $ostopvm = $_POST["ostopvm"];
        $tarkka = $_POST["tarkka".$i];
    if (empty($tuote) or empty($hinta) or empty($ostopvm)) {
        echo $i+1 . ": Tietoja puuttuu. <br/>";
        echo "<a href=\"alku.html\">Palaa takaisin</a>";
        continue;
    } else {
        $haku="SELECT tuote_id FROM tuote WHERE nimi='{$tuote}'";
        $sql = "INSERT INTO ostos (tuote, hinta, pvm_alku, nimi_tarkka, tuote_id)
        VALUES (?, ?, ?,?, ?)";
        
        
        $varmistus = "SELECT nimi FROM tuote WHERE nimi='{$tuote}'";
        $testi= $yhteys->prepare($varmistus);
        $testi->execute();
        $exists = $testi->fetchColumn();
        if ($exists) {
            ;
        } else {
            $lisays = "INSERT INTO tuote (nimi, hinta)
            VALUES (?,?)";
            $lisaaminen=$yhteys->prepare($lisays);
            $lisaaminen->execute(array($tuote, $hinta));
            echo "{$tuote} lisätty tuotteisiin, tuotekategoria pitää merkitä itse.";
        }

        $kysely = $yhteys->prepare($haku);
        $kysely->execute();
        while ($rivi=$kysely->fetch()) {
            $t_id=$rivi['tuote_id'];
        }

        $kysely = $yhteys->prepare($sql);
        $kysely->execute(array($tuote, $hinta, $ostopvm, $tarkka, $t_id));
        echo $i+1 . ": Ostos {$tuote} on lisätty tietokantaan. </br>";
    }
}
?>
</body>
</html>