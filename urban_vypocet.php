<?php
$mc = microtime(true);
$times = array();
$times['all']= array('start'=>$mc, 'end'=>false);

$clqfiletmp = uniqid(rand(), true).'.clq';

function mcgd($clqfile, $citac = 0)
{
  global $subjects, $mcgdvysledky, $limitpocet, $disjunktni, $clqfiletmp;
  if($citac > 1000) die('1000 iteraci zvedni to blbe');
  //echo 'call '.$clqfile.' '.$citac.'<br />';
  $DIR = dirname(__FILE__).'/';
  $exe = $DIR.'mcqd2';
  $cmd = $exe.' '.$clqfile.' | grep Maximum | sed -e \'s/Maximum clique: //\'';
  //echo $citac." ".$cmd."<br />";die();
  $output = shell_exec($cmd);
  $output = explode(' ', trim($output));
  //pocet je velikost kliky
  if($pocet = count($output))
  {
    if($pocet < $limitpocet) return true;
    if(!isset($mcgdvysledky[$pocet])) $mcgdvysledky[$pocet] = array();
    //echo join(' ', $output)."<br />";
    $nazvy = array();
    foreach($output as $o)
    {
      $nazvy[] = $subjects[$o];
      //echo $subjects[$o].' ';
    }
    //echo '<br />';
    $mcgdvysledky[$pocet][] = $nazvy;
    $f = file($clqfile);
    $fp = fopen($clqfiletmp, 'w+');
    $t = 0;
    foreach($f as $r)
    {
      $r = explode(' ', trim($r));
      //jsou oba vrcholy vyhodim hranu z noveho souboru
      if($disjunktni)
      {
        if(in_array($r[1], $output) or in_array($r[2], $output)) continue;
      }
      else
      {
        if(in_array($r[1], $output) and in_array($r[2], $output)) continue;
      }
      fputs($fp, 'e '.$r[1].' '.$r[2]."\n");
      $t++;
    }
    unset($f);
    fclose($fp);
    if($t)
    {
      $citac++;
      mcgd($clqfiletmp, $citac);
    }
  }
  else
  {
    var_dump($output);
    die('uz nic nenasel');
  }
}//EOF mcgd

$rucni = false;

//zdrojova data
$file = file($datafile);
//promenne
$names = false;//jmena extra sloupcu (po subject group)
$subject = 'ID';//'ZUJ';//sloupec similarity
$path = 'Scanpath string';//sloupec scanpath
$group = 'REGION';//sloupec group
if(false and P('mapdata') == 'data')
{
  $subject = 'KOMMNR';
  $group = 'FNAVN';
}

//surova data pro pozdejsi ulozeni
$extracols = array();
$data = array();
$subjects = array();

$randname = uniqid(rand(), true);
$outputjson =  $randname. '.json';

$maxscore = 0;//maxscore
$comp = 0;
//$leve = true;//pouzivat levehstejne
//$limit = 0.15;//limit pro matici 0/1

//parametry insert/replace/delete
//$cost_ins = 1;
//$cost_rep = 1;
//$cost_del = 1;
$columnnames = array();//jmena sloupcu
$groups = array();

//nacti soubor a preved do matice
$weightset = false;//vahy pro jednotlive sloupce
$maxset = false;
$minset = false;
foreach($file as $radek=>$row)
{
  if(strpos($row, '#') === 0 or trim($row) == '') continue;
  $row = preg_replace('/[\"\']/', '', trim($row));
  if(strpos($row, ';'))
  {  
    $row = explode(";", $row);
  }
  else
  {
    $row = explode("\t", $row);
  }
  //rozliseni jmen sloupcu zatim nic
  if($names == false)
  {
    foreach($row as $k=>$column)
    {
      $column = trim($column);
      if(is_numeric($group))
      {
        //extracols
        $extracols[]=$column;
      }
      if($column === $subject) $subject = $k;
      if($column === $path) $path = $k;
      if($column === $group) $group = $k;
      $columnnames[$k] = $column;
      //continue;
    }
    $names = true;
    unset($columnnames[$subject]);
    unset($columnnames[$group]);
    continue;
  }

  if($weightset === false and strtolower(trim($row[0])) == 'weight')
  {
    $weightset = array();
    foreach($columnnames as $col=>$cname)
    {
      $weightset[($col-1)] = str_replace(',', '.', $row[$col]);
    }
    continue;
  }
  if($minset === false and strtolower(trim($row[0])) == 'min')
  {
    $minset = array();
    foreach($columnnames as $col=>$cname)
    {
      $minset[($col-1)] = str_replace(',', '.', $row[$col]);
    }
    continue;
  }
  if($maxset === false and strtolower(trim($row[0])) == 'max')
  {
    $maxset = array();
    foreach($columnnames as $col=>$cname)
    {
      $maxset[($col-1)] = str_replace(',', '.', $row[$col]);
    }
    continue;
  }

  if($weightset !== false)
  {  

    $subj = trim($row[$subject]);
    //unset($row[$subject]);
    $grp = trim($row[$group]);
    array_splice($row, $subject, 1);

    foreach($weightset as $col=>$w)
    {
      if($w == '') continue;
      //echo $col." ".$w." ".$row[$col]."<br />";
      $row[$col] = str_replace(',', '.', $row[$col])*$w;
    }

    $out = $row;
    //$subj = base64_encode($subj);
    $subjects[] = $subj;
    if(!in_array($grp, $groups)) $groups[] = $grp;
    $data[$subj] = array('subject'=>$subj, 'path'=>$out, 'group'=>$grp);
    if(!isset($pocetsloupcu)) $pocetsloupcu = count($out);
    //if(count($data) == 101) $data = array();
    //if(count($data) == 100) break;
  }
}
//var_dump($subjects);die();
//var_dump($data);die();
/*$t = 500020;
$k = 0;
var_dump($data[$t]['path'][$k]-$data[500496]['path'][$k]);
die();*/
//$pocetsloupcu = P('pocetsloupcu');
//$pocetsloupcu = 6;


$pocetsubjektu = count($subjects);

//zdrojova matice pro ohodnoce vzdalenosti A-B A-C A-D atd.
//$zdroj = 'zdroj2.txt';
$matice_zdroj = array();
if($matrixfile and file_exists($matrixfile))
{
  $file = file($matrixfile);
  foreach($file as $i=>$row)
  {
    if(strpos($row, '#') === 0 or trim($row) == '') continue;
    $radek = explode("\t", $row);
    foreach($radek as $j=>$val)
    {
      $matice_zdroj[$i][$j] = $val;
    }
  }
}
else
{
  for($i=0;$i<$pocetsubjektu;$i++)
  {
    for($j=0;$j<$pocetsubjektu;$j++)
    {
      if($j == $i) $val = 0;
      else
      {
        $sum = 0;
        for($k = 0;$k<$pocetsloupcu;$k++)
        {
          //beru jen to co je jako int nebo float (do path leze subject a region)
          if(is_string($data[$subjects[$i]]['path'][$k]))
          {
            continue;
          }
          $sum += pow(($data[$subjects[$i]]['path'][$k]-$data[$subjects[$j]]['path'][$k]), 2);
        }
        $val = round(sqrt($sum), 4);
      }
      //echo $val." ".$i." ".$j."<br />";
      $matice_zdroj[$i][$j] = $val;
    }
  }
}
//var_dump($data[22]['path']);die();
//die();var_dump($matice_zdroj);die();


//var_dump($subjects);die();
$matrix = array();
$emptyrow = array();
foreach($subjects as $s)
{
  $emptyrow[$s] = 0;
}

//pocitam maximalni hodnotu z dostupnych dat
//$maxvaluedata = abs((float)str_replace(',','.', P('maxvalue', 5))-(float)str_replace(',','.', P('minvalue', 1)));
//$maxscore = sqrt($maxvaluedata*$maxvaluedata*P('pocetsloupcu'));//vypocet maximal possible distance 0 - 5 v nasem pripade to vyssi cislo byt nemuze
if($maxset !== false and $minset !== false and $weightset !== false)
{
  $maxscoreset = array();
  $totalmax = 0;
  foreach($weightset as $col=>$w)
  {
    if($w == '') continue;
    //$row[$col] = $row[$col]*$w;
    $a = ($maxset[$col]-$minset[$col])*$w;
    $totalmax += $a*$a;
  }
  $maxscore = sqrt($totalmax);
}

//echo "maxvaluedata ".$maxvaluedata."<br />";
//echo "maxscore ".$maxscore."<br />";
//uz zadava uzivatel

//napocitat matici
//$matrix[0] = array_keys($data);
foreach($data as $subj=>$vals)
{
  $matrix[$subj] = $emptyrow;
}
//var_dump($matrix);die();

$times['comp'] = array('start'=>microtime(true), 'end'=>false);
foreach($subjects as $k=>$s1)
{
  foreach($subjects as $j=>$s2)
  {
    if($matrix[$s2][$s1] != 0) continue;
    $matrix[$s1][$s2] = str_replace('.', ',', $matice_zdroj[$k][$j]);
    $matrix[$s2][$s1] = $matrix[$s1][$s2];
  }
}
//var_dump($matrix);die();


/*
    //if($matrix[$s2][$s1] != 0) continue;
    /*$P1 = $data[$s1]['path'];
    $P2 = $data[$s2]['path'];
    if($leve == true)
    {
      $cost_rep = (int)$matice_zdroj[$k][$j];
      if($data[$s1]['length'] > 254 or $data[$s2]['length'] > 254)
      {
        $score = levenshtein2($P1, $P2, $cost_ins, $cost_rep, $cost_del);
      }
      else
      {
        $score = levenshtein($P1, $P2, $cost_ins, $cost_rep, $cost_del);
      }
    }
    else
    {
      $wunsch->compute($P1, $P2);
      $score = $wunsch->optimal_alignment['score'];
    }
    $maxscore = max($score, $maxscore);
    $matrix[$k][$j] = $matice_zdroj[$s1][$s2];
    $matrix[$s2][$s1] = $matrix[$s1][$s2];
  }
}/**/
$times['comp']['end'] = microtime(true);
//podelmatici nejvyssim cislem
$matrix2 = $matrix;
$matrix3 = $matrix;
$puvodni = $matrix;

$nodes = array();
$links = array();

//test json data

//$hrany = array();//hrany budu ukladat primo do souboru kvuli RAM
$jednicky = 0;
$nuly = 0;
$maxscore = ($maxscore == 0)?1:$maxscore;
$fp = fopen($clqfiletmp, 'w+');
$fpjson = fopen("output/".$outputjson, 'w+');
$outputjson = "output/".$outputjson;
if(preg_match('/window/i', $_SERVER['HTTP_USER_AGENT'])) fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

/*$json = "{\"nodes\":[\n";
foreach($subjects as $s)
{
    $nodes[] = '{"name":"'.trim($s).'","group":"'.trim($data[$s]['group']).'"}';//
}

$json .= join(",\n", $nodes);
$json .= "],\n";
$json .= '"links": ['."\n";*/
fputs($fpjson, "{\"nodes\":[\n");

$cc = 1;
foreach($subjects as $s)
{
  $addcoma = ($cc < $pocetsubjektu)?',':'';
  fputs($fpjson, '{"name":"'.trim($s).'","group":"'.trim($data[$s]['group']).'"}'.$addcoma."\n");
  $cc++;
}
fputs($fpjson, "],\n");
fputs($fpjson, '"links": ['."\n");

foreach($subjects as $k=>$s1)
{
  foreach($subjects as $j=>$s2)
  {
    $matrix2[$s1][$s2] = round(1-($matice_zdroj[$k][$j]/$maxscore), 4);
    $matrix2[$s2][$s1] = $matrix2[$s1][$s2];
    
    //kdyz je mensi jak dany limit a neni 0 tak nastav 0
    $matrix3[$s1][$s2] = ($matrix2[$s1][$s2]>=$limit)?1:0;
    $matrix3[$s2][$s1] = $matrix3[$s1][$s2];

    $matrix2[$s1][$s2] = str_replace('.', ',', $matrix2[$s1][$s2]);
    $matrix2[$s2][$s1] = $matrix2[$s1][$s2];

    if($matrix3[$s1][$s2] === 1 and $s1 != $s2)
    {
      $jednicky++;
      //$links[] = '{"source":'.$j.',"target":'.$k.',"value":1}';
      //$hrany[] = array($k, $j);
      //fputs($fpclose, '{"source":'.$j.',"target":'.$k.',"value":1},'."\n");
      //fputs($fpjson, '{"source":'.$j.',"target":'.$k.',"value":1},'."\n");
      fputs($fp, 'e '.$k.' '.$j."\n");
    }
    else
    {
      $nuly++;
    }
  }
}
fclose($fp);
fputs($fpjson, "]\n");


//var_dump($matrix2);die();
//$json .= join(",\n", $links);
//$json .= "]\n";


$out1 = '-;'.join(';', $subjects)."\n";
$out2 = $out1;
$out3 = $out2;
$jednickyanuly = array();//ulozim si kolik tam mam jednicek a nul
$soucetjednicek = 0;
foreach($subjects as $k=>$s1)
{
  $row = array();
  $row2 = array();
  $row3 = array();
  foreach($subjects as $j=>$s2)
  {
    $row[] = $matrix2[$s1][$s2];
    $row2[] = $matrix3[$s1][$s2];
    $row3[] = $matrix[$s1][$s2];
    if(!isset($jednickyanuly[$s1])) $jednickyanuly[$s1] = array(0=>0, 1=>0);
    $jednickyanuly[$s1][0] += !(int)($matrix3[$s1][$s2]);
    $jednickyanuly[$s1][1] += (int)($matrix3[$s1][$s2]);
  }
  $soucetjednicek += $jednickyanuly[$s1][1];
  array_unshift($row3, $s1);
  $out3 .= join(';',$row3)."\n";
  array_unshift($row, $s1);
  $out1 .= join(';',$row)."\n";
  array_unshift($row2, $s1);
  $out2 .= join(';',$row2)."\n";
}
//var_dump($matrix);die();
//


$matrix4 = $matrix3;

//vyhodim ty co maji jen nuly
$citac = 1;
$zuzenysubjects = array();
foreach($jednickyanuly as $s=>$v)
{
  //vyhodim radky kde jsou jen nuly nebo 1 jednicka
  if($v[1] < 2)
  {
    unset($matrix4[$s]);
    foreach($subjects as $k=>$s1)
    {
      unset($matrix4[$s1][$s]);
    }
  }
  else
  {
    $zuzenysubjects[$s] = $citac;//dale pak budu vyrazovat jen tady z techto
    $citac++;
  }
}
//var_dump($matrix4);die();

//test json ???
$jsonout = array();
if(count($zuzenysubjects))
{
  foreach($zuzenysubjects as $s=>$a)
  {
    //echo $data[$s]['path']." ";//zuzenysubjects
    if(is_array($data[$s]['path']))
    {
      $trimmed_array  =array_map('trim', $data[$s]['path']);
      $jsonout[$s] = $trimmed_array;
    }
    else
    {
      $jsonout[$s] = $data[$s]['path'];
    }
  }
  //$json .= ',"datasub":'.json_encode($jsonout);
  fputs($fpjson, ',"datasub":'.json_encode($jsonout));
}
fputs($fpjson, "}\n");
fclose($fpjson);
//$json .= "}\n";

$limitpocet = (P('limitpocet', 10));
$limitpocet = ($limitpocet < 2)?2:$limitpocet;

//settings
$settfile = $randname.'.sett';
$fp = fopen("output/".$settfile, 'w+');
if(preg_match('/window/i', $_SERVER['HTTP_USER_AGENT'])) fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
$settjson = array('method'=>P('method'), 'collapsed'=>$collapsed, 'parameter'=>P('parameter', $limit), 'origsource'=>$origsource, 'origname'=>$origname, 'columnnames'=>$columnnames, 'limitpocet'=>$limitpocet,
'origmapsource'=>$origmapsource,
'origmapname'=>$origmapname
);
fputs($fp, json_encode($settjson));
fclose($fp);




//TODO projit matici a vyhodit vse co ma 0, jednu 1 nebo 2 jednicky
//a projit opet znovu dokud tam neco zbyde =>
//pokud nic nezustane tak asi neres, graf kreslis z puvodnich dat a to uz delas tak jsi sikovny
//

//var_dump(count($matrix4));die();//CHCIPNI
//unset($matrix3);
unset($matrix2);
unset($matrix);
//zkontrolovat jestli matice nejsou jenom 1 (vsude)

$jenjednicky = true;

function jsouToJenJednicky($subjects, $matrix)
{
  $jenjednicky = true;

  foreach($subjects as $s1=>$v1)
  {
    if($jenjednicky == false) break;
    foreach($subjects as $s2=>$v2)
    {
      if($matrix[$s1][$s2] == 0)
      {
        $jenjednicky = false;
        break;
      }
    }
  }
  return $jenjednicky;
}

if(isset($_POST['justoutput']))
{
 
}
else
{

//TODO: podmnoziny mnoziny ktera je vy vysledku uz nepocitat at se usetri cas
//takze postupne budu vyrazovat

//$zuzenysubjects = array('petr'=>1, 'jana'=>1, 'ondra'=>1, 'fana'=>1);
$pocet = count($zuzenysubjects);
$zkeys = array_keys($zuzenysubjects);

//ted pocitam podle Bron Kerbosch tak preskocim heuristiku i C# reseni

$fp = fopen('maticeCC.txt', 'w+');
//fputs($fp, $outCC);
foreach($matrix4 as $subj => $arr)
{
  fputs($fp, join(' ', $arr)."\n");
}
fclose($fp);

$DIR = dirname(__FILE__);

include('mcp_nehez.php');

//Bron Kerbosch
if(isset($_POST['brosh']) and $_POST['brosh'])
{
  include('classes/class.graph.php');
  include('classes/class.bron.php');

  $graph = new Graph($zuzenysubjects, $matrix4);
  $bron = new CliqueFinder($graph);
  $bron->find_all_cliques();

  ob_start();
  $bron->print_cliques();
  $output = ob_get_contents();
  ob_end_clean();
  $output = explode("\n", trim($output));

  $VYSLEDKY = array();
  foreach($output as $r)
  {
    $r=trim($r);
    if($r == '') continue;
    $kliky = explode(" ", $r);
    $pocet = count($kliky);
    if($pocet < $limitpocet) continue;
    if(!isset($VYSLEDKY[$pocet])) $VYSLEDKY[$pocet] = array();
    $nazvy = array();
    foreach($kliky as $index)
    {
      $nazvy[] = $zkeys[($index-1)];
    }
    $VYSLEDKY[$pocet][] = $nazvy;
  }
  krsort($VYSLEDKY);
}
else
{
  $disjunktni = (isset($_POST['andor']) and $_POST['andor']);
  $disjunktni = true;
  $mcgdvysledky = array();
  mcgd($clqfiletmp);
  krsort($mcgdvysledky);
  $VYSLEDKY = $mcgdvysledky;
}

unlink($clqfiletmp);

  $GROUPY = '';
  $GROUPYTXT = '';
  $GROUPYCNT = 0;
  $GROUPYITEMCOUNT = 0;

  $GROUPYcsv = '';
  $PATHScsv = array();

  $gkey = 1;
  foreach($VYSLEDKY as $h=>$a)
  {
    foreach($a as $kk)
    {
      $serazeniskupin = array();
      foreach($kk as $subj)
      {
        if(!isset($serazeniskupin[$data[$subj]['group']])) $serazeniskupin[$data[$subj]['group']] = array();
        $serazeniskupin[$data[$subj]['group']][] = $subj;
      }
      $kk = array();
      foreach($serazeniskupin as $pp)
      {
        $kk = array_merge($kk, $pp);
      }
      unset($serazeniskupin);
    
    	$GROUPYITEMCOUNT += count($kk);
      $GROUPY .= count($kk)."\n";
      $GROUPY .= join("\n", $kk);
      $GROUPY .= "\n\n";

      //$GROUPYcsv .= count($kk)."\n";
      $transformace = array();//ZUJ => OBEC
      
      foreach($kk as $subj)
      {
        if(is_array($data[$subj]['path']))
        {
          //$GROUPYcsv .= $subj.';'.$data[$subj]['group'].';'.implode(', ',$data[$subj]['path'])."\n";
          $GROUPYcsv .= ($GROUPYCNT+1).';'.$subj.';'.implode(';',$data[$subj]['path'])."\n";
        }
        else
        {
          //$GROUPYcsv .= $subj.';'.$data[$subj]['group'].';'.$data[$subj]['path']."\n";
          $GROUPYcsv .= ($GROUPYCNT+1).';'.$subj.';'.$data[$subj]['path']."\n";
        }
        $transformace[] = $data[$subj]['path'][0].' ('.$subj.')';
      }
      //$GROUPYcsv .= "\n";
      
      $GROUPYTXT .= '<div class="group" data-group="group'.$GROUPYCNT.'"><span class="groupc">'.count($transformace)."</span>\n";
      $GROUPYTXT .= '<span class="groupv">'.join("</span>\n<span class='groupv'>", $transformace).'</span>';
      $GROUPYTXT .= "</div>\n";
      $GROUPYCNT++;
    }
  }


}//end if justoutput

/**/$outputmatice = $randname . '.csv';
$fp = fopen("output/".$outputmatice, 'w+');
if(preg_match('/window/i', $_SERVER['HTTP_USER_AGENT'])) fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
$metody = array('wunsch'=>'Needleman-Wunsch', 'leve'=>'Levenshtein classic', 'leve2'=>'Levenshtein');

if(!$leve)
{
  $const = '('.$cost_match.' '.$cost_gap.' '.$cost_mismatch.')';
}
else
{
  $const = '('.$cost_ins.' '.$cost_rep.' '.$cost_del.')';
}

//fputs($fp, "Method: ".$metody[P('method')]."\n");//." ".$const
fputs($fp, "Source: ".$datasource."\n");
//fputs($fp, "Collapsed: ".(int)$collapsed."\n");
//fputs($fp, "Max score: ".$maxscore."\n");
fputs($fp, "Parameter: ".P('parameter', $limit)."\n");
fputs($fp, "Min. size of group: ".($limitpocet)."\n");
//fputs($fp, "Disjoint: ".(($disjunktni)?'true':'false')."\n");
//fputs($fp, "Edges: ".$edgesnumber." (".$edgesproc."%)"."\n");
fputs($fp, "\nOriginal matrix\n");
fputs($fp, $out3);
fputs($fp, "\n\n");
fputs($fp, "Modified matrix\n");
fputs($fp, $out1);
fputs($fp, "\n\n");
fputs($fp, "Adjacency matrix\n");
fputs($fp, $out2);
if(isset($GROUPYcsv) and $GROUPYcsv)
{
  fputs($fp, "\n\n");
  fputs($fp, "Similarity groups\n");
  fputs($fp, $GROUPYcsv);
}
if(isset($data) and count($data))
{
  fputs($fp, "\n\n");
  fputs($fp, "Input data;;;".join(';', $extracols)."\n");
  foreach($data as $subj=>$arr)
  {
    if(is_array($arr['path']))
    {
      fputs($fp, $arr['subject'].';'.str_replace('.', ',', implode(";",$arr['path']))."\n");//'.$arr['group'].';
    }
    else
    {
      fputs($fp, $arr['subject'].';'.str_replace('.', ',', $arr['path'])."\n");//'.$arr['group'].';
    }
  }

}
fclose($fp);/**/

$times['all']['end']=microtime(true);
$SHOWTIME = ($times['all']['end']-$times['all']['start']);

