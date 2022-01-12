<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Tuoteraportti</title>
</head>
<body>
<?php
require "../yhteys.php";
require "../navbar.php";

$tuote = $_GET['tuote'];
if (isset($_GET['piste'])) $piste = $_GET['piste'];
else $piste = 0.001;

echo "<h1>{$tuote}</h1>";
?>
<table>
<?php
$haku = "SELECT COUNT(o.tuote_id) AS lkm
        FROM ostos o
        WHERE tuote_id = (
            SELECT tuote_id
            FROM tuote t
            WHERE t.nimi = '{$tuote}'
        )";

$kysely = $yhteys->prepare($haku);
$kysely->execute();

$ostot_lkm = $kysely -> fetch()['lkm'];
echo "<tr><td>Ostettu yhteensä</td><td>{$ostot_lkm}</td></tr>";

$output = shell_exec("python ../../ostokset/laskut.py keskim {$tuote}");
echo utf8_encode($output);
echo "</table>";

$tuotekuva = "../kuvat/hist_tod_nak_{$tuote}.jpeg";

if (file_exists("$tuotekuva")) $exif_data = exif_read_data($tuotekuva);

// Jos sama kuva on valmiina niin ei tehdä uudestaan
if (isset($exif_data) && round($exif_data['COMPUTED']['UserComment'], 3) == round($piste, 3)) {
    echo "<img src=\"../kuvat/hist_tod_nak_{$tuote}.jpeg\">";
} else {
    shell_exec("python ../../ostokset/laskut.py tuotehinta_tod_nak {$tuote} {$piste} --piirra");
    echo "<img src=\"../kuvat/hist_tod_nak_{$tuote}.jpeg\">";
}
?>

</body>
</html>