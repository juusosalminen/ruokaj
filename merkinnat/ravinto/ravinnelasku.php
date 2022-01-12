<?php
/**
 * @file Laskee annoksen ravintoarvot.
 */

 /**
  * Hakee tiedot päivämäärän ja ateriatyypin perusteella.
  * 
  * @param  string $tyyppi aterian tyyppi
  * @param  string $pvm    päivämäärä
  * @return unknown
  */
function ravinnelasku($tyyppi, $pvm) 
{ 
    $ravinteet = ['energia_kJ*0.23','proteiini_g', 'hiilihydraatti_imeytyvä_g', 'rasva_g','kuitu_kokonais_g'];
    include "../yhteys.php";
    
    foreach ($ravinteet as $ravinne) {
        $annoshaku = "SELECT sum(ra.{$ravinne} * ru.maara / 100) AS yht
                        FROM ravinto ra
                        JOIN ruokailu ru
                        USING (ravinto_id) 
                        WHERE ru.tyyppi = '{$tyyppi}'
                        AND ru.pvm = '{$pvm}'";
    
        $kysely = $yhteys->prepare($annoshaku);
        $kysely->execute();

        while ($rivi = $kysely->fetch()) {
            $ravinne = ucfirst(preg_replace('/_.*/', ' ', $ravinne));
            $arvo = round($rivi['yht'], 2);
            echo "<tr><td>{$ravinne}</td><td>{$arvo}</td></tr>";
            
        }
    }
}

?>