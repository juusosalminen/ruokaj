<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="paivaraportti.css" rel="StyleSheet" type="text/css" />
<title>Päiväraportti</title>
</head>
<body>
<?php
include "../navbar.php";
if (isset($_GET['pvm'])) $paiva = $_GET['pvm'];
else $paiva = date("Y-m-d");
$tanaan = date("Y-m-d");
$eilen = date("Y-m-d",strtotime("-1 days"));
echo "<h1>Päivän {$paiva} tietoja</h1>";
echo "<a href=\"paivaraportti.php?pvm={$tanaan}\">Tänään</a></br>";
echo "<a href=\"paivaraportti.php?pvm={$eilen}\">Eilen</a>";
?>

<form action="paivaraportti.php" method="get">
	<label for="pvm">Valitse toinen päivä</label>
	<input type="date" name="pvm" id="pvm">
	<input type="submit" value="Lähetä">
</form>

<table class="ravinteet">
<tr><th>Ravinne</th><th>Arvo</th></tr>
<?php
require "../yhteys.php";

$sarakehaku = "SHOW columns FROM ravinto";

$kysely = $yhteys->prepare($sarakehaku);
$kysely->execute();
$sarakkeet = [];
while ($rivi = $kysely->fetch()) {
	$sarakkeet[] = $rivi['Field'];
}

// nimi ja id pois
unset($sarakkeet[56]);
unset($sarakkeet[0]);

$i = 0;
foreach ($sarakkeet as $k => $ravinne) {
	if ($i == round(count($sarakkeet) / 2, 0)) {
		echo "</table>	 
			<table class=\"ravinteet\">
			<tr><th>Ravinne</th><th>Arvo</th></tr>";
	} 
	$ravinnehaku = "SELECT SUM({$ravinne} / 100 * ru.maara) as summa
				FROM ravinto ra
				JOIN ruokailu ru
				USING (ravinto_id)
				WHERE ru.pvm = '{$paiva}'";

	$kysely = $yhteys->prepare($ravinnehaku);
	$kysely->execute();
	while ($rivi = $kysely->fetch()) {
		$arvo = $rivi['summa'];
		$nimi = str_replace('_', ' ', ucfirst($ravinne));
		$nimi = preg_replace('/(g)$|(\w{1,2}g)$/', '($0)', $nimi);
		$nimi = preg_replace('/^(\S\d{0,2}) /', '$1-', $nimi);
		$nimi = preg_replace('/kJ/', '(kcal)', $nimi);
		if ($ravinne == 'energia_kJ') $arvo *= 0.23;
		$arvo = round($arvo, 2);
		if ($i % 2 == 0) {
			echo "<tr class=\"tumma\">";
		} else echo "<tr class=\"vaalea\">";
		echo "	<td>
				<form method=\"post\" action=\"/ruoka/merkinnat/tarkastelu/kasittely.php\" target=\"_blank\">
				<input type=\"hidden\" name=\"funktio\" value=\"ravinnepylvaat\">
				<input type=\"hidden\" name=\"ravinne\" value=\"{$ravinne}\">
				<input type=\"hidden\" name=\"lkm\" value=\"7\">
				<button type=\"submit\" class=\"link-button\">
					{$nimi}
				</button>
				</form>
				</td>
				<td class=\"arvo\">{$arvo}</td>
			</tr>";
	}
	$i++;
}
echo "<table class=\"kulutus\">
 	<tr><th>Tuote</th><th>Määrä (g)</th></tr>";
$kulutushaku = "SELECT t.nimi, sum(r.maara) as summa
				FROM ruokailu r
				JOIN tuote t
				USING(tuote_id)
				WHERE r.pvm = '{$paiva}'
				GROUP BY t.nimi";
				
$kysely = $yhteys->prepare($kulutushaku);
$kysely->execute();

while ($rivi = $kysely->fetch()) {
	$tuote = $rivi['nimi'];
	$tuotelinkki = "tuoteraportti.php?tuote={$tuote}";
	$maara = $rivi['summa'];
	echo "<tr>
			<td><a href=\"{$tuotelinkki}\">{$tuote}</a></td>
			<td>{$maara}</td>
		</tr>";
}
?>
</table>
</body>
</html>