<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Ainesten tallennus</title>
</head>
<body>
<?php
require "../navbar.php";
$maara=$_POST['maara'];

$ehdokkaat= [];
$loppujat =[];
for ($i=0 ; $i < $maara ; $i++) {
    if (empty($_POST['koko'.$i]) and empty($_POST['kerroin'.$i]) and empty($_POST['std'.$i])
        or empty($_POST['ravintoaine'.$i])
    ) {
        $lopetus = true;
        echo "Joitakin paino- tai ravinnetietoja puuttuu.";
        

    }
}
echo "<ul class=tapahtuneet>";
include "../yhteys.php";
if (empty($lopetus)) {
    for ($i=0 ; $i < $maara ; $i++) {
        

        $tuote=$_POST['tuote'.$i];
        
        $tyyppi=$_POST['tyyppi'];
        $ravinto=$_POST['ravintoaine'.$i];
        $pvm=$_POST['pvm'];

        if (empty($_POST["koko".$i])==false) {
            $koko=$_POST['koko'.$i];
        } elseif (empty($_POST["kerroin".$i])==false) {
            if (preg_match("/\d\/\d/", $_POST["kerroin".$i])) {
                preg_match_all("/\d+/", $_POST["kerroin".$i], $matches);
                $kerroin = $matches[0][0] / $matches[0][1];
            } else {
                $kerroin = $_POST["kerroin".$i];
            }
            $koko= round($kerroin * $_POST["ostospaino".$i]);
        } else {
            $koko = $_POST["std".$i];
        }

        if (isset($_POST["valittu".$i])) {
            $sql = "INSERT INTO ruokailu (maara, tyyppi, ravinto_id, pvm)
            VALUES (?,?,?,?)";

            $kysely = $yhteys->prepare($sql);
            $kysely->execute(array($koko, $tyyppi, $ravinto, $pvm));
        } else {
            $ostetut = 0;
            $haku="SELECT tuote_id, maara FROM tuote WHERE nimi='{$tuote}'";
            
            $kysely = $yhteys->prepare($haku);
            $kysely->execute();
            while ($rivi=$kysely->fetch()) {
                $t_id=$rivi['tuote_id'];
                $varastossa = $rivi['maara'];
            }
            if ($koko > $varastossa) {
                $vahennettava = $varastossa;
            } else {
                $vahennettava = $koko;
            }
            $sql= "INSERT INTO ruokailu (maara, tyyppi, ravinto_id, pvm, tuote_id)
            VALUES (?,?,?,?,?);
            
            UPDATE tuote SET maara=maara-{$vahennettava}
            WHERE tuote_id='{$t_id}';
            ";
            

            $kysely = $yhteys->prepare($sql);
            $kysely->execute(array($koko, $tyyppi, $ravinto, $pvm, $t_id));
            
            $jaljella_haku="SELECT t.maara AS varasto, haku.ostos
                            FROM tuote t LEFT JOIN (
                                SELECT o.tuote_id, o.maara AS ostos
                                FROM ostos o
                                WHERE tuote_id = '{$t_id}'
                                AND o.pvm_alku <> (
                                    SELECT min(o.pvm_alku)
                                    FROM ostos o
                                    WHERE tuote_id = '{$t_id}'
                                    AND o.pvm_loppu IS NULL)
                                AND o.pvm_loppu IS NULL) AS haku
                            USING (tuote_id)
                            WHERE t.tuote_id = '{$t_id}'";

            $kysely = $yhteys->prepare($jaljella_haku);
            $kysely->execute();

            
            while ($rivi=$kysely->fetch()) {
                $jaljella=$rivi['varasto'];
                if (is_null($rivi['ostos'])==false) {
                    $ostetut += $rivi['ostos'];
                }
            }

            echo "<li>Aineksen {$tuote} väheneminen ({$koko}g) on lisätty tietokantaan,
                jäljellä on {$jaljella}g.</li>";
            if ($jaljella < 10 ) {
                $loppujat[]=$tuote;
            } elseif ($ostetut !== 0 and $jaljella <= $ostetut+10) { 
                $loppujat[]=$tuote;
            }
            //echo "<br>";
            if ($_POST["koko".$i]=$koko) {
                $ehdokkaat[$tuote]=$koko;
            }
            
        }
    } 
    echo "</ul>";

    echo "<table class=\"ravinteet\">";
    echo "<tr><th colspan=2>Annoksen ravintoarvot</th></tr>";
    include "ravinnelasku.php";
    ravinnelasku($tyyppi, $pvm);
    
    echo "</table>";



    if (count($loppujat) > 0) {
        echo "<div>
            <p>Seuraavat tuotteet näyttäisivät olevan lopussa. Valitse, jos tahdot merkitä loppumisen</p>
            <form method=\"post\" action=\"../haku.php\">";
        foreach ($loppujat as $loppuja) {
                echo "<label for=\"loppuja{$loppuja}\">{$loppuja}</label>
                <input type=\"checkbox\" name=\"loppuja_{$loppuja}\" id=\"loppuja_{$loppuja}\" value=\"{$loppuja}\"><br>";
        }
            echo "<input type=\"submit\" name=\"laheta\" value=\"Lähetä\">
        </form>
        </div>";
    }

    echo "<form action= \"std_lisays.php\" method=\"post\">";
    $maara=count(array_keys($ehdokkaat));
    $j=0;
    echo "<p>Haluatko lisätä standardimäärän seuraaville tuotteille?</p>";
    foreach ($ehdokkaat as $tuote => $paino) {
        
        
        echo "<label for=\"kuvaus\">{$tuote}, {$paino}g </label>";
        echo "<input type=\"text\" name=\"kuvaus".$j."\" list=\"vaihtoehtoja\">";
        echo "<datalist id=\"vaihtoehtoja\">
        <option value=\"pieni\"></option>
        <option value=\"keskikokoinen\"></option>
        <option value=\"suuri\"></option>
        <option value=\"normaali annos\"></option>
        
        </datalist>";
        echo "<input type=\"hidden\" name=\"tuote".$j."\" value=\"{$tuote}\">";
        echo "<input type=\"hidden\" name=\"koko".$j."\" value=\"{$paino}\" >";
        $j++;
        echo "<br>";
    }

    echo "<input type=\"submit\" name=\"laheta\" value=\"Lähetä\">";

    echo "<input type=\"hidden\" name=\"maara\" value=\"{$maara}\" >";
    echo"</form>";
}
$pvm = date('Y-m-d');
$merkhaku = "SELECT DISTINCT tyyppi
            FROM ruokailu
            WHERE pvm = '{$pvm}'";
$tyypit = ["aamupala", "lounas", "valipala", "paivallinen", "iltapala"];
echo "<div class=\"oikea\">
    <p>Tänään merkitty</p>
    <ul>";
$kysely = $yhteys->prepare($merkhaku);
$kysely->execute();

while ($rivi = $kysely->fetch()) {
    $tyyppi = $rivi['tyyppi'];
    
    if (in_array($tyyppi, $tyypit)) {
        echo "<li>{$tyyppi}</li>";
    }
}
echo "</ul></div>";



?>


<a href="ravintomerkinta.php">Palaa alkuun</a>
</body>
</html>