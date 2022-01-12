<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<body>
<?php
require "navbar.php";
$maara=$_POST["maara"];
$yht=0;
require "yhteys.php";
for ($i=0; $i < $maara; $i++) {
    $loppu=strtotime($_POST["loppupvm".$i]);
    if (empty($loppu)) {
        continue;
    } else {
        $loppu=date('Y-m-d', $loppu);
        $id=$_POST["id".$i];
        $sql = "UPDATE ostos SET pvm_loppu='{$loppu}' WHERE ostos_id='{$id}';
        
        UPDATE tuote SET maara=0 
        WHERE tuote_id=(
        SELECT tuote_id FROM ostos WHERE ostos_id='{$id}') AND NOT EXISTS(
        SELECT * FROM ostos 
        WHERE tuote_id=(SELECT tuote_id FROM ostos WHERE ostos_id='{$id}') AND
        pvm_loppu IS NULL);
        
        UPDATE tuote 
        SET maara = (
            SELECT sum(maara)
            FROM ostos
            WHERE tuote_id = (
                SELECT tuote_id
                FROM ostos
                WHERE ostos_id = '{$id}'
                )
            AND pvm_loppu IS NULL
            )
        WHERE tuote_id = (
            SELECT tuote_id
            FROM ostos
            WHERE ostos_id = '{$id}'
            )
        AND EXISTS (
        SELECT * FROM ostos 
        WHERE tuote_id=(SELECT tuote_id FROM ostos WHERE ostos_id='{$id}')
        AND pvm_loppu IS NULL)";

        $sql2 = "SELECT t.nimi, o.pvm_alku, t.maara 
        FROM ostos o JOIN tuote t USING(tuote_id)
        WHERE o.ostos_id='{$id}'";
        $kysely = $yhteys->prepare($sql);
        $kysely->execute();

        $kysely = $yhteys->prepare($sql2);
        $kysely->execute();
        while ($rivi = $kysely->fetch()) {
            $tuote = htmlspecialchars($rivi["nimi"]);
            $alku = $rivi["pvm_alku"];
            $jaljella= $rivi["maara"];
        }    
        echo "Ostoksen {$id} {$tuote} loppumisajaksi merkitty {$loppu}. 
        Alkamispäivä oli {$alku}. Jäljellä on {$jaljella}g <br />";

        
        $yht++;
    }
}

if ($yht==0) {
    echo "Mitään ei muokattu. <br/>";
}
?>
<a href="loput.php">Palaa alkuun</a>
</body>
</html>