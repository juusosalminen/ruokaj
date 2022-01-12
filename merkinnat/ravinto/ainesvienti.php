<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Ainesten vienti</title>
</head>
<body>
<?php require "../navbar.php";?>
<form action="ainesmerkinta.php" method="post">
<table>
<p>Valitse ulkopuolinen, jos tuotetta ei ole ostettu tai sitä ei voi kohdistaa mihinkään olemassaolevaan tuotteeseen.</p>
<tr><th rowspan=3>Ulkopuolinen</th><th rowspan=3>Tuote</th><th rowspan=3>Ravintoaine</th><th colspan=4>Määrä grammoina (valitse yksi vaihtoehdoista)</th></tr>
<tr><th rowspan=2>Vapaavalintainen</th><th colspan=2>Edellisen ostoksen mukaan</th><th rowspan=2>Standardi</th></tr>
<tr><th>Ed. ost. paino</th><th>Kerroin</th></tr>
<?php
$maara=$_POST['maara'];

$vaihtoehtonimet=[
  "Rapsiöljy" => "Rypsiöljy",
  "Kananmunat" => "Kananmuna",
  "Ruokakerma" => "Ruoanvalmistuskerma",
  "Spagetti" => "Makaroni",
  "Vihannessekoitus" => "Pakastekasvissekoitus",
  "Kanasuikale" => "Suikale",
  "Klementiini" => "Mandariini",
  "Kala" => "Seiti",
  "Uunilenkki" => "Lenkkimakkara",
  "Kanankoipi" => "Koipi"
];

for ($i=0 ; $i < $maara ; $i++) {
    
    $tuote=$_POST['tuote'.$i];
    if (in_array($tuote, array_keys($vaihtoehtonimet))) {
        $tuote_hakusana=$vaihtoehtonimet[$tuote];
    }
    echo "<tr><td class=\"keskitetty\"><input type=\"checkbox\" name=\"valittu".$i."\"</td>";
    echo "<td>{$tuote}</td>";
    echo "<td><input name=\"ravintoaine".$i."\" type=\"text\" list=\"ravintoaineita".$i."\"/></td>
      <datalist id=\"ravintoaineita".$i."\">";
    
    include "../yhteys.php";

    if (isset($tuote_hakusana)) {
        
        $sql= "SELECT nimi, r.ravinto_id FROM ravinto r
        LEFT JOIN (
          SELECT count(*) a, ravinto_id FROM ruokailu 
          WHERE tuote_id=(
            SELECT tuote_id FROM tuote WHERE nimi='{$tuote}')
          GROUP BY ravinto_id) haku
        USING(ravinto_id) 
        WHERE nimi REGEXP '{$tuote}|{$tuote_hakusana}' 
        ORDER BY haku.a DESC";
    } else {
        $sql= "SELECT nimi, r.ravinto_id FROM ravinto r
        LEFT JOIN (
          SELECT count(*) a, ravinto_id FROM ruokailu 
          WHERE tuote_id=(
            SELECT tuote_id FROM tuote WHERE nimi='{$tuote}')
          GROUP BY ravinto_id) haku
        USING(ravinto_id) 
        WHERE nimi LIKE '%{$tuote}%' 
        ORDER BY haku.a DESC";
    }

    $kysely = $yhteys->prepare($sql);
    $kysely->execute();
    
    while ($rivi = $kysely->fetch()) {
        $nimi = $rivi['nimi'];
        $id= $rivi['ravinto_id'];
      
        echo "<option value={$id}>{$nimi}</option>";
        
    }
    
    echo "</datalist>";
    if (isset($tuote_hakusana)) {
        unset($tuote_hakusana);
    }
    echo "<td><input type=\"number\" name=\"koko".$i."\"></td>";
    $painohaku="SELECT o.maara, o.nimi_tarkka
                FROM ostos o, tuote t
                WHERE t.tuote_id = o.tuote_id
                AND t.nimi='$tuote'
                AND o.pvm_loppu IS NULL
                ORDER BY o.ostos_id 
                LIMIT 1";
    $ostoksen_paino = array();
    $kysely = $yhteys->prepare($painohaku);
    $kysely->execute();
                  
    while ($rivi = $kysely->fetch()) {
        $ostoksen_paino[] = $rivi["maara"];
        $ostoksen_paino[] = $rivi["nimi_tarkka"];
    }
    
    if (isset($ostoksen_paino[0]) and isset($ostoksen_paino[1])) {
        if (preg_match("/\d\skpl$/", $ostoksen_paino[1], $matches)) {
            $ostoksen_paino[0] = $ostoksen_paino[0]/$matches[0][0];
        }
        echo "<td>{$ostoksen_paino[0]}</td>
          <td><input type=\"text\" name=\"kerroin".$i."\" placeholder=\"Esim. 1/2, 0.25\"></td>";
        echo  "<input type=\"hidden\" name=\"ostospaino".$i."\" value=\"{$ostoksen_paino[0]}\">";
    } else {
        echo "<td class=\"keskitetty\" colspan=2>Ei tallennettua määrää</td>";
    }
    
    //standardimäärien haku 
    $stdhaku = "SELECT DISTINCT s.paino, s.kuvaus
                FROM standardipaino s, tuote t
                WHERE t.tuote_id = s.tuote_id
                AND t.nimi = '{$tuote}'"
                ;

    $kysely = $yhteys->prepare($stdhaku);
    $kysely->execute();
    
    $ylin_rivi = $kysely->fetch();
    $ylin_paino = $ylin_rivi['paino'];

    echo "<td><select name=\"std".$i."\" value=\"{$ylin_paino}\">";
    

    $kysely = $yhteys->prepare($stdhaku);
    $kysely->execute();

    while ($rivi = $kysely->fetch()) {
        $kuvaus = $rivi['kuvaus'];
        $std_paino= $rivi['paino'];
    
        echo "<option value={$std_paino}>{$kuvaus} ({$std_paino}g)</option>";  
    }        
    echo "</select></td></tr>";
    
    
    echo "<input type=\"hidden\" value=\"{$tuote}\" name=\"tuote".$i."\">";
}
echo "<input type=\"hidden\" value=\"{$maara}\" name=\"maara\">";
?>
</table>
<fieldset>
<legend>Aterian tyyppi</legend>
<input type="radio" name="tyyppi" value="aamupala" id="aamupala" required>
<label for="aamupala">Aamupala</label><br>
<input type="radio" name="tyyppi" value="lounas" id="lounas">
<label for="Lounas">Lounas</label><br>
<input type="radio" name="tyyppi" value="valipala" id="valipala">
<label for="valipala">Välipala</label><br>
<input type="radio" name="tyyppi" value="paivallinen" id="paivallinen">
<label for="paivallinen">Päivällinen</label><br>
<input type="radio" name="tyyppi" value="iltapala" id="iltapala">
<label for="iltapala">Iltapala</label><br>
</fieldset>
<label for="pvm">Päivämäärä</label>
<input type="date" name="pvm" value="<?php echo date('Y-m-d');?>"><br>
<input type="submit" value="Lähetä">
</form>
</body>
</html>