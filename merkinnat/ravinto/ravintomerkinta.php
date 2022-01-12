<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Ravintomerkintä</title>
</head>
<body>
<?php require "../navbar.php";?>
<form method="post" action="ainekset.php">
<label for="maara">Ainesten määrä</label>
<input type="number" name="maara">
<input type="submit" value="Lähetä" name="laheta">

</form>
</body>
</html>