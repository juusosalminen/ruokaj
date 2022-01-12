<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<body>
<p>Oletuksena tulevia nimeä ja hintaa voi muokata tarkemmiksi.</p>
<form action="uusiostos.php" method="post">
    <?php
    $maara = $_POST["maara"];
    for ($i=0; $i < $maara; $i++) {
            $tuote = $_POST["tuote".$i];
        if (empty($tuote)) {
                echo "<p>Tietoja puuttuu. <a href=\"alku.html\">Palaa alkuun</a></p>";
        }
            echo "<label for=\"tuote".$i."\">Tuote</label>
            <input name=\"tuote".$i."\" type=\"text\" value=\"{$tuote}\" />";
            echo "<label for=\"hinta".$i."\">Anna hinta</label>
            <input type=\"text\" name=\"hinta".$i."\" "; 
            
            include "yhteys.php";

        $sql= "SELECT hinta FROM tuote WHERE nimi='{$tuote}'";


        $kysely = $yhteys->prepare($sql);
        $kysely->execute();
        while ($rivi = $kysely->fetch()) {
            $tuotehinta = htmlspecialchars($rivi["hinta"]);
            echo "value={$tuotehinta}>";
            
        }
        echo "<label for=\"tarkka\">Tarkka nimi (valinnainen)</label>
            <input type=\"text\" name=\"tarkka".$i."\" />";
            echo "</br>";
    }
    ?>
    
    <label for="ostopvm">Ostopäivä</label>
    <input id="ostopvm" type="date" name="ostopvm" value="<?php echo date('Y-m-d'); ?>">
    <input type="hidden" name="maara" value="<?php echo $maara ?>"/>
    <input type="submit" value="Lähetä">
</form>
</body>
</html>