<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<body>
<form method="post" action="autolahetys.php">
<table>
<tr><th>Nr.</th><th>Tuote</th><th>Hinta</th><th>Tarkka nimi</th><th>Paino (g)</th><th>Valittuna</th></tr>
<?php 
$maara=$_POST['maara'];
for ($i=0; $i < $maara; $i++) {
    echo "<tr><td>".($i+1)."</td><td><input name=\"tuote".$i."\" type=\"text\" list=\"tuotteita\"/>
    <datalist id=\"tuotteita\">";

    include "yhteys.php";

    $sql= "SELECT nimi FROM tuote";

    $kysely = $yhteys->prepare($sql);
    $kysely->execute();
    while ($rivi = $kysely->fetch()) {
        $tuotenimi = htmlspecialchars($rivi["nimi"]);
        echo "<option value={$tuotenimi}>{$tuotenimi}</option>";
    }
    echo "</datalist></br></td>";
    echo "<td><input type=\"text\" name=\"hinta".$i."\"</td>";
    echo "<td><input type=\"text\" name=\"tarkka".$i."\"</td>";
    echo "<td><input type=\"text\" name=\"paino".$i."\"</td>";
    
    echo "<td><input type=\"checkbox\" name=\"valittu".$i."\" checked=\"checked\"</td></tr>"; 
    
} 

?>
</table>
<input type="date" name="ostopvm" value="<?php echo date('Y-m-d'); ?>">
<input type="hidden" name="maara" value="<?php echo $maara ?>"/>
<input type="submit" value="Lähetä">
</form>
</body>
</html>