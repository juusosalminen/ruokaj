<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="tyyli.css" rel="StyleSheet" type="text/css" />
<title>Varaston muutos</title>
</head>
<body>
<?php
require "yhteys.php";
$i=0;

$luku=$_POST['luku'];

while ($i < $luku) {
    $maara=$_POST['maara'.$i];
    $id=$_POST['id'.$i];
    $sql= "UPDATE tuote SET maara='{$maara}'
    WHERE tuote_id='{$id}'";
    $kysely = $yhteys->prepare($sql);
    $kysely->execute();
    $i++;
    echo $maara;
}
?>

</body>
</html>
