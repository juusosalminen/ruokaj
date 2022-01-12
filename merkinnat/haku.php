<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Sivun otsikko</title>
</head>
<body>
<?php require "navbar.php";?>
<form action="lopetus.php" method="post">
<table>
<tr>
    <th>Ostos-id</th>
    <th>Tuotteen nimi</th>
    <th>Ostopäivämäärä</th>
    <th>Loppumispäivämäärä</th>
</tr>
<?php



function tulleet() 
{
    include "yhteys.php";
    unset($_POST['laheta']);
    foreach ($_POST as $k => $v) {
        $hakusanat[] = $v;
    } 
    $i=0;
    $hakusanalista = implode('\',\'', $hakusanat);
    $sql = "SELECT t.nimi, o.pvm_alku, o.ostos_id, min(ostos_id)
            FROM tuote t JOIN ostos o
            USING(tuote_id)
            WHERE o.pvm_loppu IS NULL
            AND t.nimi IN ('{$hakusanalista}')
            GROUP BY t.nimi
            ORDER BY o.pvm_alku
            ";
    
    $kysely = $yhteys->prepare($sql);
    $kysely->execute();

    while ($rivi = $kysely->fetch()) {
        $tuote= $rivi['nimi'];
        $alkupvm= $rivi['pvm_alku'];
        
        $id = $rivi['ostos_id'];
        echo "<tr><td>{$id}</td><td>{$tuote}</td><td>{$alkupvm}</td>";
        echo "<td><input type=\"date\" value=\"";
        echo date('Y-m-d');
        echo "\" name=\"loppupvm".$i."\"></td><td>
        <input type=\"hidden\" name=\"id".$i."\" value=\"{$id}\"></td></tr>";
        $i++;
    }
        echo "<input type=\"hidden\" name=\"maara\" value=\"{$i}\">";
        echo "</table>";
}

function paivilla() 
{
    include "yhteys.php";
    $alku=$_POST["alkuaika"];
    $loppu=$_POST["loppuaika"];
    $i=0;
    $sql = "SELECT t.nimi, o.pvm_alku, o.pvm_loppu, o.ostos_id 
    FROM ostos o JOIN tuote t 
    USING(tuote_id)
    WHERE (o.pvm_alku BETWEEN '{$alku}' AND '{$loppu}') AND o.pvm_loppu IS NULL";
    $kysely = $yhteys->prepare($sql);
    $kysely->execute();
    while ($rivi = $kysely->fetch()) {
            $tuote= $rivi['nimi'];
            $alkupvm= $rivi['pvm_alku'];
            $loppuminen= $rivi['pvm_loppu'];
            $id = $rivi['ostos_id'];
            echo "<tr><td>{$id}</td><td>{$tuote}</td><td>{$alkupvm}</td>";
        if (is_null($loppuminen)) {
                echo "<td><input type=\"date\" name=\"loppupvm".$i."\"></td><td>
                <input type=\"hidden\" name=\"id".$i."\" value=\"{$id}\"></td></tr>";
                $i++;
        } else {
            echo "<td>{$loppuminen}</td></tr>";
        }            
    }
    echo "<input type=\"hidden\" name=\"maara\" value=\"{$i}\">";
    echo "</table>";
}
function tuotteilla()
{
    include "yhteys.php";
    $valtuote=$_POST["tuote"];
    $i=0;
    $sql2= "SELECT t.nimi, pvm_alku, pvm_loppu, ostos_id 
    FROM ostos o JOIN tuote t USING(tuote_id) WHERE t.nimi='{$valtuote}' AND pvm_loppu IS NULL";
    $kysely2 = $yhteys->prepare($sql2);
        $kysely2->execute();
    while ($rivi = $kysely2->fetch()) {
            $tuote= $rivi['nimi'];
            $alkupvm= $rivi['pvm_alku'];
            $loppuminen= $rivi['pvm_loppu'];
            $id = $rivi['ostos_id'];
            echo "<tr><td>{$id}</td><td>{$tuote}</td><td>{$alkupvm}</td>";
        if (is_null($loppuminen)) {
                echo "<td>
                <input type=\"date\" name=\"loppupvm".$i."\" value=\""; 
                echo date('Y-m-d');
                echo"\"></td><td><input type=\"hidden\" name=\"id".$i."\" value=\"{$id}\"></td></tr>";
                $i++;
        } else {
            echo "<td>{$loppuminen}</td></tr>";
        }
    }
    echo "<input type=\"hidden\" name=\"maara\" value=\"{$i}\">";
    echo "</table>";
}
function molemmat() 
{
    include "yhteys.php";
    $valtuote=$_POST["tuote"];
    $alku=$_POST["alkuaika"];
    $loppu=$_POST["loppuaika"];
    $i=0;
    $sql= "SELECT t.nimi, pvm_alku, pvm_loppu, ostos_id 
    FROM ostos JOIN tuote t USING(tuote_id)
    WHERE t.nimi='{$valtuote}' AND pvm_loppu IS NULL AND (pvm_alku BETWEEN '{$alku}' AND '{$loppu}')";
    $kysely = $yhteys->prepare($sql);
        $kysely->execute();
    while ($rivi = $kysely->fetch()) {
            $tuote= $rivi['nimi'];
            $alkupvm= $rivi['pvm_alku'];
            $loppuminen= $rivi['pvm_loppu'];
            $id = $rivi['ostos_id'];
            echo "<tr><td>{$id}</td><td>{$tuote}</td><td>{$alkupvm}</td>";
        if (is_null($loppuminen)) {
                echo "<td>
                <input type=\"date\" name=\"loppupvm".$i."\" value=\""; 
                echo date('Y-m-d');
                echo"\"></td><td><input type=\"hidden\" name=\"id".$i."\" value=\"{$id}\"></td></tr>";
                $i++;
        } else {
            echo "<td>{$loppuminen}</td></tr>";
        }
    }
    echo "<input type=\"hidden\" name=\"maara\" value=\"{$i}\">";
    echo "</table>";
}
if (preg_match('/loppuja/', array_keys($_POST)[0])) {
    tulleet();
} elseif (empty($_POST["alkuaika"]) and empty($_POST["loppuaika"]) and empty($_POST["tuote"]) ) {
    echo "<tr><td colspan=\"4\">Hakuehtoja ei löytynyt.</td></tr></table>";
    echo "<a href=\"http://localhost/ostokset/loput.php\">Palaa takaisin</a>";
} elseif (empty($_POST["alkuaika"]) and empty($_POST["loppuaika"]) and isset($_POST["tuote"])) {
    tuotteilla();
} elseif (empty($_POST["tuote"]) and isset($_POST["alkuaika"]) and isset($_POST["loppuaika"])) {
    paivilla();
} elseif (isset($_POST["tuote"]) and isset($_POST["alkuaika"]) and isset($_POST["loppuaika"])) {
    molemmat();
} 
    
   

?>
<input type="submit" value="Lähetä tiedot" name="laheta">

</form>

</body>
</html>