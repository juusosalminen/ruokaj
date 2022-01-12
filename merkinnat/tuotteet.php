<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Uusi ostos</title>
</head>
  <body>
    <form action="ostokset.php" method="post">
    <?php
    $maara = $_POST["maara"];
    if (empty($maara)) {
        echo "<p>Tuotteiden määrää ei asetettu. <br/>";
        echo "<a href=\"alku.html\">Palaa alkuun</a><br/></p>";
    }
    for ($i=0 ; $i < $maara ; $i++) {
        echo "<label class=\"lista\" for=\"tuote".$i."\">Tuote</label>
          <input name=\"tuote".$i."\" type=\"text\" list=\"tuotteita\"/>
          <datalist id=\"tuotteita\">";
      
        include "yhteys.php";

        $sql= "SELECT nimi FROM tuote";

        $kysely = $yhteys->prepare($sql);
        $kysely->execute();
        while ($rivi = $kysely->fetch()) {
            $tuotenimi = htmlspecialchars($rivi["nimi"]);
            echo "<option value={$tuotenimi}>{$tuotenimi}</option>";
        }
        echo "</datalist><br/>";
    }
    ?>
      
      <input type="hidden" name="maara" value="<?php echo $maara ?>"/>
      <input type="submit" value="Lähetä">
    </form>

  </body>
</html>