<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Standardien lisäys</title>
</head>
<body>
<?php
require "../navbar.php";
$maara = $_POST["maara"];

for ($i=0 ; $i < $maara ; $i++) {
    if (empty($_POST["tuote".$i])==false and empty($_POST["koko".$i])==false and empty($_POST["kuvaus".$i])==false) {
        include "../yhteys.php";
        $tuote=$_POST["tuote".$i];
        $paino=$_POST["koko".$i];
        $kuvaus=$_POST["kuvaus".$i];
        $t_id_haku="SELECT tuote_id FROM tuote WHERE nimi='{$tuote}'";

        $sql=   "INSERT INTO standardipaino (tuote_id, kuvaus, paino)
                VALUES (?,?,?)";

        $kysely = $yhteys->prepare($t_id_haku);
        $kysely->execute();


        while ($rivi=$kysely->fetch()) {
            $t_id=$rivi['tuote_id'];
        }
        
        $varmistus =    "SELECT * FROM standardipaino
        WHERE tuote_id = '{$t_id}'
        AND kuvaus = '{$kuvaus}'";

        $kysely = $yhteys->prepare($varmistus);
        $kysely->execute();

        if ($kysely->fetch()) {
            echo "Vastaava standardi ({$kuvaus}, {$paino}g) on jo olemassa tuotteelle {$tuote}.<br>";
            continue;
        }

        $kysely = $yhteys->prepare($sql);
        $kysely->execute(array($t_id, $kuvaus, $paino));

        echo "Standardipaino {$paino}g on lisätty tuotteelle {$tuote} kuvauksella {$kuvaus}.<br>";
    } else {
        continue;
    }
}
?>
</body>
</html>