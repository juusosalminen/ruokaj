<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<body>
<p>Näyttää vain tuotteet, joiden loppumispäivää ei ole merkitty. 
Jos täytät kaikki kentät, hakutuloksissa näkyvät kohteet, joissa pätee aikaväli JA tuotteen nimi.</p>
<p>Jätä kenttä tyhjäksi, jos et halua sen vaikuttavan hakuun.</p>
<form action="haku.php" method="post">
<p>Anna aikaväli, jolta ostoksia haetaan.</p>
<label for="alkuaika">Aikavälin alku</label>
<input id="alkuaika" type="date" name="alkuaika" value="<?php echo date('Y-m-d', strtotime('-1 week'));?>">
<label for="loppuaika">Aikavälin loppu</label>
<input id="loppuaika" type="date" name="loppuaika" value="<?php echo date('Y-m-d'); ?>">
<p>Hae tuotteen mukaan:</p>
<label for="tuote">Tuotteen nimi</label>
<input id="tuote" type="text" name="tuote" list="tuotteita"/>
<datalist id="tuotteita">
<?php 
      require "yhteys.php";

      $sql= "SELECT nimi FROM tuote";

      $kysely = $yhteys->prepare($sql);
      $kysely->execute();
while ($rivi = $kysely->fetch()) {
          $tuotenimi = htmlspecialchars($rivi["nimi"]);
          echo "<option value={$tuotenimi}>{$tuotenimi}</option>";
}

?>
      </datalist>
<br/>
<input type="submit" value="Lähetä"/>
</form>
<p>Paina <a href="kaikki.php">tästä</a>, jos haluat nähdä kaikki merkitsemättömät tuotteet.</p>
</body>
</html>