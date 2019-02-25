<?php
//ini_set('display_errors', 1);ini_set('display_startup_errors', 'On');error_reporting(E_ALL);
set_time_limit(0);

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

if($leve)
{
  $limit = 1;//bod 1 parametr 0
}
else
{
  $limit = 0;
}

$limit = P('parameter');

if(P('submit') == 'ADVISED GRAPH')
{
  $_POST['kvartil'] = 5;
}

$kvartil = 100/P('kvartil');

//hlavicka v novem souboru
$images_array = array();//pole obrazku
$listfiles = array();//pole souboru pro vytvoreni zip souboru
$zipname = 'output.zip';

$DIR = dirname(__FILE__).'/zipdata/';
if(file_exists($DIR))
{
  array_map('unlink', glob("$DIR/*.*"));
  rmdir($DIR);
}
//if(file_exists($zipname)) unlink($zipname);
mkdir($DIR);


$zipresult = false;  
$zip = new ZipArchive();
if ($zip->open($datafile) === TRUE)
{
  $zip->extractTo($DIR);
  $zip->close();
  $zipresult = true;
}

//promenne
$names = false;//jmena extra sloupcu (po subject group)
$subject = 'ID';//'ZUJ';//sloupec similarity
$path = 'Scanpath string';//sloupec scanpath
$group = 'REGION';//sloupec group
//surova data pro pozdejsi ulozeni
$extraCols = array();
$data = array();
$subjects = array();
$subjectsset = false;

$druhazipmetoda = (P('druhazipmetoda') == 'vyskyt');//nedelam prumer z modified ale az souhrn z adjacency
$alldata = array();
$all_modified_matrix = array();
$allmatrix2 = false;
$allmatrix3 = array();

$matrix2 = array();
$pocetsouboru=0;


if($zipresult)
{
  $scan = scandir($DIR);
  foreach($scan as $filename)
  {
    if (!is_dir($DIR.'/'.$filename))
    {
      $type = shell_exec("file -i ".$DIR.'/'.$filename." |awk '{print $2}'");
      if(substr($type, 0, 5) == "text/")
      {
        //zjistim subject z prvniho souboru
        $data = array();
        $file = file($DIR.'/'.$filename);
				$weightset = false;//vahy pro jednotlive sloupce
				$maxset = false;
				$minset = false;
				$names = false;
				$columnnames = array();
				$pocetsouboru++;
				if(isset($pocetsloupcu)) unset($pocetsloupcu);
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

						$sum = 0;
						foreach($weightset as $col=>$w)
						{
							if($w == '') continue;
							$row[$col] = str_replace(',', '.', $row[$col])*$w;
						}

						$out = $row;
						if(!in_array($subj, $subjects)) $subjects[] = $subj;
						if(!in_array($grp, $groups)) $groups[] = $grp;
						$data[$subj] = array('subject'=>$subj, 'path'=>$out, 'group'=>$grp);
						if(!isset($pocetsloupcu)) $pocetsloupcu = count($out);
					}

					
        }//EOF FOREACH FILE - projdi radky souboru
				if($maxset !== false and $minset !== false and $weightset !== false)
				{
					$maxscoreset = array();
					$maxscore = 0;
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
        
					$matice_zdroj = array();
					$pocetsubjektu = count($subjects);
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
					
					$matrix = array();
					$emptyrow = array();
					foreach($subjects as $s)
					{
						$emptyrow[$s] = 0;
					}
					
					foreach($subjects as $k=>$s1)
					{
						foreach($subjects as $j=>$s2)
						{
							if($matrix[$s2][$s1] != 0) continue;
							$matrix[$s1][$s2] = str_replace('.', ',', $matice_zdroj[$k][$j]);
							$matrix[$s2][$s1] = $matrix[$s1][$s2];
						}
					}
					foreach($subjects as $k=>$s1)
					{
						foreach($subjects as $j=>$s2)
						{
							$pricistdrivejsihodnotu = 0;
							if(isset($matrix2[$s1]) and isset($matrix2[$s1][$s2]))
							{
								$pricistdrivejsihodnotu = $matrix2[$s1][$s2];
							}
							$matrix2[$s1][$s2] = round(1-($matice_zdroj[$k][$j]/$maxscore)+$pricistdrivejsihodnotu, 4);//." ".$pricistdrivejsihodnotu.";";

						}
					}
      }
    }
  }//end foreach files in unziped directory
  $matrix3 = $matrix;
  unset($matrix);

	$jednicky = 0;
	$nuly = 0;
	$maxscore = ($maxscore == 0)?1:$maxscore;
	$randname = uniqid(rand(), true);
	$outputjson =  $randname. '.json';
	$fp = fopen($clqfiletmp, 'w+');
	$fpjson = fopen("output/".$outputjson, 'w+');
	$outputjson = "output/".$outputjson;
	if(preg_match('/window/i', $_SERVER['HTTP_USER_AGENT'])) fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	
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
      $matrix2[$s1][$s2] = round(($matrix2[$s1][$s2]/$pocetsouboru), 4);
		  $matrix3[$s1][$s2] = ($matrix2[$s1][$s2]>=$limit)?1:0;
		  $matrix3[$s2][$s1] = $matrix3[$s1][$s2];

		  $matrix2[$s1][$s2] = str_replace('.', ',', $matrix2[$s1][$s2]);
		  //$matrix2[$s2][$s1] = $matrix2[$s1][$s2];
		  
		  if($matrix3[$s1][$s2] === 1 and $s1 != $s2)
		  {
		    $jednicky++;
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
		  if(!isset($jednickyanuly[$s1])) $jednickyanuly[$s1] = array(0=>0, 1=>0);
		  $jednickyanuly[$s1][0] += !(int)($matrix3[$s1][$s2]);
		  $jednickyanuly[$s1][1] += (int)($matrix3[$s1][$s2]);
		}
		$soucetjednicek += $jednickyanuly[$s1][1];
		array_unshift($row, $s1);
		$out1 .= join(';',$row)."\n";
		array_unshift($row2, $s1);
		$out2 .= join(';',$row2)."\n";
	}
	
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

	unset($matrix2);
	unset($matrix);
	
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
	
	$outputmatice = $randname . '.csv';
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
	fclose($fp);/**/	
	
}
