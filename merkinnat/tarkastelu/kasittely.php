<!DOCTYPE html>
<html lang="fi">
<head>
<meta charset="UTF-8" />
<link href="../tyyli.css" rel="StyleSheet" type="text/css" />
<title>Tulostus</title>
</head>
<body>
<?php
include "../navbar.php";
require "funktioita.php";

//kuvaa klikattaessa, muuten post
if (!empty($_GET)) {
	$_GET['funktio']();
} else $_POST['funktio']();
?>
</body>
</html>