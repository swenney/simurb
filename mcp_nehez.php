<?php
//pocet prvku $pocetsubjektu
//1. pocet hran je pocet jednicek pod diagonalou v treti matici
$n = $pocetsubjektu;//$jednicky/2;//n je pocet hran
$E = $jednicky/2;
//2. krok spocitam cislo pocet hran lomeno n nad 2 ( (n*(n-1))/2 ) n je pocet vrcholu (P)
$P = $E/(($n*($n-1))/2);
//3. krok 1/ spocitane cislo z kroku 2 (b)
$b = 1/$P;
//4. krok
$r = ceil(2*log($n, $b));//zaokrouhlim nahoru
//5. krok
$k = 1000; //maximalni pocet iteraci nez se na to vysere alias zvedni to blbe
//6. krok
$pocitanekombinace = array();
$MCP = "n: ".$n." E:".$E." P:".$P." b: ".$b." r:".$r;

?>
