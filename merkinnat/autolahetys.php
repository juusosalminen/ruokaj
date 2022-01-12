<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<body>
<?php
require "yhteys.php";
$maara = $_POST["maara"];

for ($i=0; $i < $maara; $i++) {
        $tuote = $_POST["tuote".$i];
        $hinta = $_POST["hinta".$i];
        $ostopvm = $_POST["ostopvm"];
        $tarkka = $_POST["tarkka".$i];
        $paino = $_POST['paino'.$i];
        
    if (empty($tuote) or empty($hinta) or empty($ostopvm)) {
        echo $i+1 . ": Tietoja puuttuu. <br/>";
        echo "<a href=\"alku.html\">Palaa takaisin</a><br/>";
        continue;
    } if (isset($_POST["valittu".$i])) {
        $sql = "INSERT INTO ostos (hinta, pvm_alku, nimi_tarkka, maara)
        VALUES (?, ?,?,?);

        UPDATE ostos SET tuote_id =
        (SELECT tuote.tuote_id FROM tuote WHERE tuote.nimi='{$tuote}') 
        WHERE pvm_alku='{$ostopvm}' AND nimi_tarkka='{$tarkka}';
        
        UPDATE tuote SET maara=maara+{$paino}
        WHERE nimi='{$tuote}'";
    } else {
        continue;
    }

    $varmistus = "SELECT nimi FROM tuote WHERE nimi='{$tuote}'";
    $testi= $yhteys->prepare($varmistus);
    $testi->execute();
    $exists = $testi->fetchColumn();
    if ($exists) {
        ;
    } else {
        $lisays = "INSERT INTO tuote (nimi)
        VALUES (?)";
        $lisaaminen=$yhteys->prepare($lisays);
        $lisaaminen->execute(array($tuote));
        echo "{$tuote} lisätty tuotteisiin, tuotekategoria pitää merkitä itse.";
    }
    $kysely = $yhteys->prepare($sql);
    $kysely->execute(array($hinta, $ostopvm, $tarkka, $paino));
    
    
    echo $i+1 . ": Ostos {$tuote} on lisätty tietokantaan. </br>";
}
?>
</body>
</html>