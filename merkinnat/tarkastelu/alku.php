<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Tarkastelun alku</title>
</head>
<body>
<?php
include "../navbar.php"
?>
<form action="kasittely.php" method="post">

<fieldset><legend>Ostokset</legend>
<select name="funktio">
	<option disabled selected value> -- Valitse -- </option>
	<option value="kk_summat">Kuukauden ostosten summat</option>
	<option value="keskim">Tuotteiden hinta ja kesto</option> 
</select>

</fieldset>



<fieldset><legend>Ravinnetiedot</legend>
<select name="funktio">
	<option disabled selected value> -- Valitse -- </option>
	<option value="vk_ka_roll">Viikottainen liukuva keskiarvo</option>
</select>
<label for="ravinne">Ravinne</label>
<input type="text" name="ravinne" list="ravinnelista" id="ravinne">
<datalist id="ravinnelista" name="ravinnelista"
<?php
require "../yhteys.php";

$ravinnehaku = "SELECT column_name
				FROM information_schema.columns
				WHERE table_name='ravinto'";

$kysely = $yhteys->prepare($ravinnehaku);
$kysely->execute();

while ($rivi = $kysely->fetch()) {
	$ravinne = $rivi['COLUMN_NAME'];
	if ($ravinne != "nimi") {
	echo "<option>{$ravinne}</option>";
	}				
}
?>
</datalist>
</fieldset>
<input type="submit" name="laheta">
</form>
</body>
</html>