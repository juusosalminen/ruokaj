<?php
$command=escapeshellcmd('C:\Python\MyScripts\ostokset\tarkkavienti.py');
//$command=escapeshellcmd('C:\Python\MyScripts\ostokset\ruokamerkinta_auto.py');
$output=shell_exec($command);
echo $output;

?>