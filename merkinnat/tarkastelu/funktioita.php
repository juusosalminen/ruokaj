<?php
function kk_summat() {
	$output = shell_exec("python ../../ostokset/laskut.py kk_summat");
	echo file_get_contents("../kuvat/kk_summat.svg");
	echo "<table>";
	echo $output;
	echo "</table>";
}

function vk_ka_roll() {
	$ravinne = $_POST['ravinne'];
	shell_exec("python ../../ostokset/ravintotiedot.py vk_ka_roll {$ravinne}");
	echo "<img src=\"../kuvat/vk_ka_roll.png\">";
}

function keskim() {
	$output = shell_exec("python ../../ostokset/laskut.py keskim");
	echo "<table>";
	echo utf8_encode($output);
	echo "</table>";
}

function paivasumma() {
	$vuosi = $_GET['vuosi']; 
	$kk = $_GET['kk'];
	shell_exec("python ../../ostokset/laskut.py paivasumma {$vuosi} {$kk}");
	echo file_get_contents("../kuvat/paivasumma.svg");
}

function paivien_kulut() {
	echo file_get_contents("../kuvat/paivasumma.svg");
	$pvm = $_GET['pvm'];
	$output = shell_exec("python ../../ostokset/laskut.py paivien_kulut {$pvm}");
	echo utf8_encode($output);
}

function paivien_muutos($ravinne) {
	echo "
	<form action=\"kasittely.php\" method=\"post\">
	<label for=\"lkm\">Muuta päivien määrää</label>
	<input type=\"hidden\" name=\"funktio\" value=\"ravinnepylvaat\">
	<input type=\"hidden\" name=\"ravinne\" value=\"". $ravinne. "\">
	<input type=\"number\" name=\"lkm\" id=\"lkm\">
	<input type=\"submit\">
	</form>
	";
}

function ravinnepylvaat() {
	$ravinne = $_POST['ravinne'];
	$lkm = $_POST['lkm'];
	
	paivien_muutos($ravinne);

	$nykyinen = hae_raja_arvo($ravinne);
	if (isset($_POST['raja_arvo'])) $raja_arvo = $_POST['raja_arvo'];
	else $raja_arvo = $nykyinen;
	if ($nykyinen != $raja_arvo) muokkaa_raja_arvoa($ravinne, $raja_arvo);

	piirra_pylvaat($ravinne, $lkm);
}

function piirra_pylvaat($ravinne, $lkm) {
	$tee = escapeshellcmd("python ../../ostokset/ravintotiedot.py ravinnepylvaat {$ravinne} {$lkm}");
	exec($tee, $output, $error);
	echo file_get_contents("../kuvat/ravinnepylvaat.svg");
}

function hae_raja_arvo($ravinne) {
	$f = file_get_contents('C:xampp/htdocs/ruoka/ostokset/raja_arvot.json');
	$raja_arvot = json_decode($f, true);
	if (isset($raja_arvot[$ravinne])) $nykyinen = $raja_arvot[$ravinne];
	else $nykyinen = 0;
	
	echo "
	<form action=\"kasittely.php\" method=\"post\">
	<input type=\"hidden\" name=\"funktio\" value=\"ravinnepylvaat\">
	<input type=\"hidden\" name=\"ravinne\" value=\"{$ravinne}\">
	<input type=\"hidden\" name=\"lkm\" value=\"7\">
	<label for=\"raja_arvo\">Muokkaa tavoitetta</label>
	<input type=\"number\" name=\"raja_arvo\" id=\"raja_arvo\" placeholder=\"{$nykyinen}\">
	<input type=\"submit\">
	</form>
	";

	return $nykyinen;
}

function muokkaa_raja_arvoa($ravinne, $raja_arvo) {
	$f_in = file_get_contents('C:xampp/htdocs/ruoka/ostokset/raja_arvot.json');
	$raja_arvot = json_decode($f_in, true);
	$raja_arvot[$ravinne] = $raja_arvo;
	
	$f_out = json_encode($raja_arvot);
	file_put_contents('C:xampp/htdocs/ruoka/ostokset/raja_arvot.json', $f_out);

	//piirra_pylvaat($ravinne, 7);
}


function paivan_ravinteet() {
	include "../yhteys.php";
	$ravinne = $_GET['ravinne'];
	$pvm = $_GET['pvm'];
	$jarjesta = $_GET['jarjesta'];
	paivien_muutos($ravinne);
	hae_raja_arvo($ravinne);
	echo file_get_contents("../kuvat/ravinnepylvaat.svg");
	echo "
	<form action=\"kasittely.php\" method=\"get\">
		<input type=\"hidden\" name=\"ravinne\" value=\"{$ravinne}\">
		<input type=\"hidden\" name=\"funktio\" value=\"paivan_ravinteet\">
		<input type=\"hidden\" name=\"pvm\" value=\"{$pvm}\">
		<div class=\"napit\">
			<button name=\"jarjesta\" type=\"submit\" value=1>Järjestä</button>
			<button name=\"jarjesta\" type=\"submit\" value=2>Sulauta</button>
			<button name=\"jarjesta\" type=\"submit\" value=0>Palauta</button>
		</div>
	</form>
	";

	echo "<table class=\"paivan_ravinneosuudet\">";
	echo "<tr><th>Tuote</th><th>Annoksen paino</th><th>Määrä</th>";
	if ($jarjesta == 1) echo "<th>Tyyppi</th>";
	echo "</tr>";

	$valisummahaku = "	SELECT ROUND(SUM(r.{$ravinne} * ru.maara / 100), 2) AS summa, ru.tyyppi
						FROM ravinto r 
						JOIN ruokailu ru 
						USING(ravinto_id) 
						WHERE ru.pvm = '{$pvm}' 
						GROUP BY ru.tyyppi";

	$kysely = $yhteys->prepare($valisummahaku);
	$kysely->execute();

	$valisummat = [];

	while ($rivi = $kysely->fetch()) {
		if ($ravinne == 'energia_kJ') $rivi['summa'] = round(0.23 * $rivi['summa'], 2);
		$valisummat[$rivi['tyyppi']] = $rivi['summa'];
	}

	//Tarvitsee left joinin sillä joillakin ruokailun aineksilla ei tuotetta
	$ravinnehaku =  "	SELECT ROUND(r.{$ravinne} * ru.maara / 100, 2) AS ravinne,
					ru.tyyppi,
					t.nimi AS tnimi,
					ru.maara, 
					r.nimi AS rnimi,
					(CASE 
						WHEN ru.tyyppi='aamupala' THEN 1    
						WHEN ru.tyyppi='lounas' THEN 2    
						WHEN ru.tyyppi='valipala' THEN 3    
						WHEN ru.tyyppi='paivallinen' THEN 4    
						WHEN ru.tyyppi='iltapala' THEN 5    
					END) AS jarjestys
				FROM ravinto r 
				JOIN ruokailu ru 
				USING(ravinto_id)
				LEFT JOIN tuote t
				USING(tuote_id) 
				WHERE ru.pvm = '{$pvm}'
				ORDER BY jarjestys";

	if ($jarjesta == 1) $ravinnehaku = substr($ravinnehaku, 0, -9) . "ravinne DESC";
	if ($jarjesta == 2) $ravinnehaku = "SELECT ROUND(SUM(r.{$ravinne}*ru.maara/100), 2) AS ravinne,
									t.nimi AS tnimi,
									SUM(ru.maara) AS maara, 
									r.nimi AS rnimi,
									ru.tyyppi
								FROM ravinto r 
								JOIN ruokailu ru 
								USING(ravinto_id)
								LEFT JOIN tuote t
								USING(tuote_id)
								WHERE ru.pvm = '{$pvm}'
								GROUP BY rnimi
								ORDER BY ravinne DESC";

	$kysely = $yhteys->prepare($ravinnehaku);
	$kysely->execute();

	$otsikko = "";
	while ($rivi = $kysely->fetch()) {
		if ($jarjesta == 0 and ucfirst($rivi['tyyppi']) != $otsikko) {
			$otsikko = ucfirst($rivi['tyyppi']);
			echo "<tr>
					<th colspan=2>{$otsikko}:</th>
					<th>{$valisummat[$rivi['tyyppi']]}</th>
				</tr>";
		}
		if ($ravinne == 'energia_kJ') $rivi['ravinne'] = round(0.23 * $rivi['ravinne'], 2);
		
		echo "<tr>";

		if (!empty($rivi['tnimi'])) echo "<td title=\"{$rivi['rnimi']}\">{$rivi['tnimi']}</td>";
		else echo "<td>{$rivi['rnimi']}</td>";

		echo 	"<td class=\"keskitetty\">{$rivi['maara']}</td>
				<td>{$rivi['ravinne']}</td>";
		if ($jarjesta == 1) echo "<td>{$rivi['tyyppi']}</td>";
		echo "</tr>";
	}
	echo "</table>";
} 
?>