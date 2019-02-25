<?php
set_time_limit(0);
//ini_set('display_errors', 1);ini_set('display_startup_errors', 1);error_reporting(E_ALL);


define('APPDIR', str_replace('api', '', dirname(__FILE__)));

if(count($_POST) == 0 and isset($_GET['source']) and trim($_GET['source']))
{
  if(file_exists(APPDIR.'/permanent/'.$_GET['source'].'.sett'))
  {
    $file = file(APPDIR.'/permanent/'.$_GET['source'].'.sett');
    $sett = json_decode($file[0],true);
    $_POST['method'] = $sett['method'];
    $_POST['parameter'] = $sett['parameter'];
    if($sett['collapse'] or $sett['collapsed'])$_POST['collapsed'] = 1;
    $_POST['origsource'] = $sett['origsource'];
    $origsource = $sett['origsource'];
    $columnnames = $sett['columnnames'];
    $_POST['origname'] = $sett['origname'];
    $outputjson = 'permanent/'.$_GET['source'].'.json';
    $_POST['limitpocet'] = $sett['limitpocet'];

    $_POST['origmapsource'] = $sett['origmapsource'];
    $_POST['origmapname'] = $sett['origmapname'];
    
    copy(APPDIR.'/permanent/'.$_GET['source'].'.txt', APPDIR.'/source/'.$sett['origsource']);
    
    $_FILES[1] = 1;
  }
  elseif(file_exists(APPDIR.'/output/'.$_GET['source'].'.sett'))
  {
    $file = file(APPDIR.'/output/'.$_GET['source'].'.sett');
    $sett = json_decode($file[0],true);
    $_POST['method'] = $sett['method'];
    $_POST['parameter'] = $sett['parameter'];
    if($sett['collapse'] or $sett['collapsed'])$_POST['collapsed'] = 1;
    $_POST['origsource'] = $sett['origsource'];
    $_POST['origname'] = $sett['origname'];
    $outputjson = 'output/'.$_GET['source'].'.json';

    $_POST['limitpocet'] = $sett['limitpocet'];

    $_POST['origmapsource'] = $sett['origmapsource'];
    $_POST['origmapname'] = $sett['origmapname'];
    
    $_FILES[1] = 1;
  }
}

$napovedaBut = (P('submit') == 'COMPUTE GRAPH 2' or P('submit') == 'ADVISED GRAPH')?true:false;
$napovedaCheck = true;// (isset($_POST['adviced']))?true:false;

if(count($_POST) == 0)
{
  $origsource = 'urban_data.csv';
  $datafile = APPDIR.'/source/'.$origsource;
  $origname = 'urban_data.csv';
  $datasource = $origname;
}
$origmapsource = P('origmapsource', 'urban_map');
$origmapname = P('origmapname', 'urban_map');


function P($name, $val = 1)
{
  return isset($_POST[$name])?$_POST[$name]:$val;
}

if(isset($_FILES) and count($_FILES))
{
  $datafile = false;
  $datasource = false;
  $matrixfile = false;
  $limit = str_replace(',', '.', P('parameter', 0.15));
  $leve = (P('method') == 'wunsch')?false:true;
  $leve2 = (P('method') == 'leve2')?true:false;
  if($leve) $leve2 = true;
  $collapsed = (isset($_POST['collapsed']) and $_POST['collapsed'] == 1)?true:false;
  if($leve)
  {
    //parametry insert/replace/delete
    $cost_ins = (int)(isset($_POST['par1']) and $_POST['par1']!=='')?(int)$_POST['par1']:1;
    $cost_rep = (int)(isset($_POST['par2']) and $_POST['par2']!=='')?(int)$_POST['par2']:1;
    $cost_del = (int)(isset($_POST['par3']) and $_POST['par3']!=='')?(int)$_POST['par3']:1;
    //$limit = (1-$limit);
  }
  else
  {
    $cost_match = (int)(isset($_POST['par1w']) and $_POST['par1w']!=='')?(int)$_POST['par1w']:1;
    $cost_gap = (int)(isset($_POST['par2w']) and $_POST['par2w']!=='')?(int)$_POST['par2w']:0;
    $cost_mismatch = (int)(isset($_POST['par3w']) and $_POST['par3']!=='')?(int)$_POST['par3w']:-1;
  }
  $randname = uniqid(rand(), true);
  
  if(P('origsource'))
  {
    $origsource = P('origsource');
    $datafile = APPDIR.'/source/'.$origsource;
    $origname = P('origname');
    $datasource = $origname;
  }
  
  if(isset($_FILES['mapfile']) and isset($_FILES['mapfile']['tmp_name']) and $_FILES['mapfile']['tmp_name'])
  {
    $mapfile = $_FILES['mapfile']['tmp_name'];
    $mapsource = $_FILES['mapfile']['name'];
    $path = pathinfo($datasource);
    $origmapsource = str_replace(' ', '_', $mapsource);
    $origmapname = $origmapsource;
    copy($_FILES['mapfile']['tmp_name'], APPDIR.'/maps/'.$origmapsource);
  }
  
  if(isset($_FILES['datafile']) and isset($_FILES['datafile']['tmp_name']) and $_FILES['datafile']['tmp_name'])
  {
    $datafile = $_FILES['datafile']['tmp_name'];
    $datasource = $_FILES['datafile']['name'];
    $path = pathinfo($datasource);
    $origsource = $randname.'.'.$path['extension'];
    $origname = $datasource; 
    copy($_FILES['datafile']['tmp_name'], APPDIR.'/source/'.$origsource);
  }
  if(isset($_FILES['datamatrix']) and isset($_FILES['datamatrix']['tmp_name']) and $_FILES['datamatrix']['tmp_name'])
  {
    $matrixfile = $_FILES['datamatrix']['tmp_name'];
    $path = pathinfo($_FILES['datamatrix']['name']);
    $origmatrix = 'matrix_'.$randname.'.'.$path['extension'];
    copy($_FILES['datamatrix']['tmp_name'], APPDIR.'/source/'.$origmatrix);
  }
  
  $type = shell_exec("file -i ".$datafile." |awk '{print $2}'");
  $usezip = false;
  if(strpos($type, '/zip') !== false)
  {
    $usezip = true;
    include('zipupload.php');
  }
  else if($datafile)
  {
	  include('urban_vypocet.php');
	}
}
?>
<!DOCTYPE html>
<html lang="cs-cz">
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="" />
<meta name="description" content="" />
<meta name="author" content="Department of Geoinformatics, UPOL" />
<meta name="generator" content="Department of Geoinformatics, UPOL" />
<meta name="robots" content="noindex, nofollow" />
<meta http-equiv="X-UA-Compatible" content="IE=EDGE" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes" />
<meta content='True' name='HandheldFriendly' />
<link rel="stylesheet" type="text/css" href="./css/style.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script type="text/javascript" src="./js/script.js"></script>
<script type="text/javascript">
<?php
if(isset($columnnames) and count($columnnames))
{
  echo 'var columnmames = ["'.join('", "', $columnnames).'"];';
  echo 'var weight = ["'.join('", "', $weightset).'"];';
}
echo '</script>';

if(isset($outputmatice) or isset($outputjson))
{
  echo '<script type="text/javascript">';
  echo 'var JSONFILE="'.$outputjson.'";';
  echo 'var nekresligraf = true;';
  echo '</script>';
  echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>';
  echo '<script type="text/javascript" src="./js/graph.js"></script>';
}
?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-6387249-5', 'auto');
  ga('send', 'pageview');

</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/openlayers/3.17.1/ol.css" type="text/css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.2/jquery.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/openlayers/3.17.1/ol.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.2.1/proj4.js" type="text/javascript"></script>
<?php
if((isset($_FILES) and count($_FILES)) or isset($outputjson))
{
  echo '<script type="text/javascript">var mapdata="'.P('origmapsource', 'urban_map').'";</script>';
  echo '<script src="./js/map.js" type="text/javascript"></script>';
}
?>
</head>
<body>

<div id="header">
<img src="./img/head-web.jpg" height="60" alt="SimUrb">
</div>

<div id="leftCol">

<div id="summaryBox">
<?php
if(isset($outputmatice) or isset($outputjson))
{
  echo '<pre>';
  //echo $MCP."<br />";
  if(isset($SHOWTIME)) echo 'Time: '.round($SHOWTIME, 4)." s<br />";
  //echo "Method: ".$metody[P('method')]."<br />";//$const
  echo "Source: ".$datasource."<br />";
  //ech/o "Collapsed: ".(($collapsed)?'true':'false')."<br />";
  //echo "Max score: ".$maxscore."<br />";
  echo "Parameter: ".P('parameter', $limit).'<br />';
  echo "Min. size of group: ".$limitpocet.'<br />';
  //echo "Maxvaluedata: ".$maxvaluedata."<br />";
  //echo "Maxvalue: ".$maxscore.'<br />';
  //echo "Disjoint: ".(($disjunktni)?'true':'false')."<br />";
  //$disjunktni = P('andor');
  /*if(isset($edgesnumber))
  {
    echo "Edges: ".$edgesnumber." (".$edgesproc."%)";
  }*/
  if(isset($msg)) echo '<span class="red">'.$msg.'</span>';
  echo '</pre>';

  echo '<input type="button" name="" id="colorbutton" value="Colours by regions" style="margin:6px 0px" />';
  echo '<input type="button" name="" id="labelbutton" value="Hide labels" style="margin:6px 0px 6px 6px;width:90px" />';
  echo '<br />';
  echo "<div id='linkIcons' />";
  //echo "<a href='index.php?jsonfile=".$outputjson."' target='_blank'>Zobraz graf</a><br />";
  echo "<a href='output/".$outputmatice."' target='_blank' title=''><img src='./img/csv.png' title='Matrix as CSV' style='width:40px;' /></a> ";
  echo "<a href='' id='geolink' target='_blank' download='data.geojson'><img src='./img/geojson.png' title='GeoJSON' style='width:40px;' /></a> ";  
  echo "<a href='#' onclick='return createPermaLink(\"".$randname."\");' title=''><img src='./img/link.png' title='Permanent link' alt='Permanent link' width='40' height='40' /></a>";
?>
<a href="#" class="tooltip"> <img src="./img/info.png" alt="Info" height="40" width="40" class="infoImg" />
<span style="width:400px">
By clicking to <b>CSV icon</b>, the user can download the table with all matrices (original matrix, modified matrix, and adjacency matrix), listed similarity groups with their attributes, input data and overview of the settings.<br><br>
By clicking on <b>GeoJSON icon</b>, the user can dowload the GeoJSON file containing the municipalities with identificator of the group they belong into. Municipalities that are not a part of any group will have ID "999".<br /><br />


Click to <b>Hyperlink icon</b> allows creating a permanent link to the displayed visualization.

</span>
</a>
<?php
  echo '</div>';
}

?>


</div>

<form action="" method="post" enctype="multipart/form-data" id="thisform">
<fieldset>
<legend>Input data</legend>
<label for="datafile">Attribute data</label><br />
<input type="file" name="datafile" id="datafile" onchange="fileinputchange(this);" /><a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
SimUrb application is designed to find similarities between municipalities
based on multivariate attribute data.
It requires map layer as JSON file and CSV file with attribute data about
municipalities with specific structure. <br>

<br>
<b>In the CSV file, the most important are the names of three crucial columns
(yellow).</b>
<br>
ID containing the IDs of municipalities<br>
NAME containing the name of the municipality<br>
REGION containing the name of the region<br><br>

All the other columns contain the attribute data. It is necessary to
specify the weight (blue) and range (green) for each attribute <br><br>
<img src="./img/napoveda_table.png" title="" alt="" width="894" height="142" /><br />
Note: Uploaded data are stored anonymously at out server and will not be used for any other purposes.
</span>
</a><br />
<?php
if(isset($origsource))
{
  echo 'Source: '.$origname.'<br />';
  echo '<input type="hidden" name="origsource" value="'.$origsource.'" />';
  echo '<input type="hidden" name="origname" value="'.$origname.'" />';
}
?>
<br />

<div id="druhaZipMetodaBox" style="display:<?php echo ($usezip)?'block':'none';?>">
<label for="druhazipmetoda" style="width:auto">Multifile method selection</label>
<select name="druhazipmetoda" id="druhazipmetoda" <?php echo ($usezip)?'':'disabled="disabled"';?>">
<?php
$druhazipmezoda = P('druhazipmetoda');
$opselected = (P('method') == 'wunsch')?' selected="selected"':'';
$druhemetody = array(
'prumer'=>'Mean',

);
//'vyskyt'=>'Occurrance'
foreach($druhemetody as $k=>$v)
{
  $opselected = ($druhazipmezoda == $k)?' selected="selected"':'';
  echo '<option value="'.$k.'"'.$opselected.'>'.$v.'</option>';
}
?>
</select>


<a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
Choose of method of multifile computation:<br>
MEAN: <br>
ScanGraph will compute the mean of modified matrices for all used stimuli. 
OCCURRANCE: <br>
Vertices are connected if they are connected in given % (degree of occurrance) of graphs for each stimuli.  
</span>
</a>
<br /><br /></div>



<label for="mapfile">Map data</label><br />
<input type="file" name="mapfile" id="mapfile" style="width: 220px" />
<a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
Map data should be in JSON file format and have WGS84 projection
(EPSG:4326)<br>
It is useful to simplify geometry for faster loading.<br />
<br>
<b>Map layer has to contain three columns:</b><br>
ID containing the IDs of municipalities<br>
NAME containing the name of the municipality<br>
REGION_NAME containing the name of the region<br><br>
<img src="./img/napoveda_json.png" title="" alt="" width="329" height="111" />
</span>
</a>
<br class="clearBoth" />
<?php
if(isset($origmapsource))
{
  echo 'Source: '.$origmapname.'<br />';
  echo '<input type="hidden" name="origmapsource" value="'.$origmapsource.'" />';
  echo '<input type="hidden" name="origmapname" value="'.$origmapname.'" />';
}
?>
<br class="clearBoth" />

<span id="miravyskytubox" style="display:<?php echo (($usezip and P('druhazipmetoda') == 'vyskyt')?'block':'none');?>">
<label for="kvartil" style="width:auto">Degree of occurrance &lt;0,1&gt;</label><br />
<input type="text" name="miravyskytu" id="miravyskytu" value="<?php echo P('miravyskytu', '0.75');?>" size="1" />
<a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
Define degree of occurrance</span>
</a><br class="clearBoth" />
</span><?php //konec span id=miravyskytubox ?>

<label for="limitpocet">Min. size of group</label> <input type="number" name="limitpocet" id="limitpocet" value="<?php echo P('limitpocet', 3);?>" size="6" class="inpCls" min="2" /><a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
This number represents the minimal number of municipalities in each resulting group.<br>
The lowest possible value is 2.
</span>
</a><br class="clearBoth" />

<label for="parameter">Use parameter p &lt;0,1&gt;</label> <input type="text" id="parameter" name="parameter" value="<?php echo P('parameter', 0.9);?>" id="parameter" size="1" class="inpCls" /><a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
The parameter <i>p</i> takes value from the interval <0,1> and represents the degree of similarity between municipalities. 
<br>The higher value of <i>p</i>, the higher similarity of the municipalities.
</span>
</a><br class="clearBoth" />
<label for="brosh">Use Bron Kerbosch</label>
<?php
$checked = ((isset($_POST['brosh']) and $_POST['brosh'])?' checked="checked"':'');
?>
<input type="checkbox" name="brosh" id="brosh"<?php echo $checked;?> />
<a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
Use this option when the desired groups are not disjunct.   
</span>
</a><br class="clearBoth" />
<?php
//Just output <input type="checkbox" name="justoutput" id="justoutput" /><br />
?>

<input type="submit" value="COMPUTE" name="submit" id="compute" />
<br style="clear: right" />
<?php
//<div onclick="exportToJava()">Export to JAVA</div>
?>
</fieldset>
<br />


<div id="leve2met" class="<?php echo $showleve;?>">
<!--Insert (optional)<br />
<input type="text" name="par1" value="<?php echo P('par1', '1');?>" /><br />
Replace (optional)<br />
<input type="text" name="par2" value="<?php echo P('par2', '1');?>" /><br />
Delete (optional)<br />
<input type="text" name="par3" value="<?php echo P('par3', '1');?>" /><br />
</div>
<div id="wunschmet" class="<?php echo $showwun;?>">
Match (optional)<br />
<input type="text" name="par1w" value="<?php echo P('par1w', '1');?>" /><br />
Gap (optional)<br />
<input type="text" name="par2w" value="<?php echo P('par2w', '0');?>" /><br />
Mismatch (optional)<br />
<input type="text" name="par3w" value="<?php echo P('par3w', '-1');?>" /><br />
-->
</div>



</form>

<?php
$edgesnumber = 0;
$edgesproc = 0;
if($napovedaCheck and isset($HODNOTALIMIT))
{
  echo '<div id="advicedbox">';
  echo '<strong><span>&nbsp;p &nbsp;</span><span>&nbsp;&nbsp;&nbsp;edges</span>&nbsp;&nbsp; &nbsp; %</strong><br />';
  $HODNOTALIMIT = array_reverse($HODNOTALIMIT, true);
  foreach($HODNOTALIMIT as $k=>$v)
  {
    if($v == 0) continue;//nebude vypisovat nuly
    echo '<div>';
    if(trim(str_replace('_', '.', $k))*1 == trim(P('parameter')))
    {
      echo '<strong style="color:blue">';
    }
    echo '<span>'.number_format(str_replace('_', '.', $k), 2).'</span> : ';
    //procento
    $proc = number_format((($v/($HODNOTA0*$kvartil))*100), 1);
    if(strlen($proc) == 3)
    {
      $proc = '&nbsp;&nbsp;'.$proc;
    }
    elseif(strlen($proc) == 4)
    {
      $proc = '&nbsp;'.$proc;
    }
    //str_pad($proc, 8, '0', STR_PAD_LEFT);
    
    $delka = 4-strlen($v);
    $v = str_repeat ( '&nbsp;', $delka).$v;
    
    echo '<span>'.$v.'</span> : ';
    echo $proc.'%';
    if(trim(str_replace('_', '.', $k))*1 == trim(P('parameter')))
    {
      echo '</strong>';
      $edgesnumber = $v;
      $edgesproc = str_replace('&nbsp;', '', $proc);//number_format((($v/($HODNOTA0*$kvartil))*100), 1);
    }
    echo '</div>';
  }
  echo '</div>';
}

if(false)//isset($outputmatice) or isset($outputjson))
{
  echo "<div id='linkIcons' />";
  //echo "<a href='index.php?jsonfile=".$outputjson."' target='_blank'>Zobraz graf</a><br />";
  echo "<a href='output/".$outputmatice."' target='_blank' title=''><img src='./img/csv.png' title='Matrix as CSV' style='width:40px;' /></a> ";
  echo "<a href='#' onclick='return createPermaLink(\"".$randname."\");' title=''><img src='./img/link.png' title='Permanent link' alt='Permanent link' width='40' height='40' /></a>";
?>
<a href="#" class="tooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="40" width="40" class="infoImg" />
<span>
By clicking to <b>CSV icon</b>, the user can download the table with all matrices (original matrix, modified matrix, and adjacency matrix), listed similarity groups with their attributes, input data and overview of the settings.<br><br> 
Click to <b>Hyperlink icon</b> allows creating a permanent link to the displayed graph.

</span>
</a>
<?php
  echo '</div>';
}

?>
<?php
if(!$druhazipmetoda)
{
?>
<pre id="klikyTxt" style="height:auto;">
</pre>
<?php
}
?>


</div>

<div id="rightCol">





<div id="graphRow">

<div id="klikyBox">
<?php
if(isset($GROUPYTXT))
{
  //var_dump($nodes);
  echo '<strong>Regions</strong><br />';
  echo '<div id="legendBox"></div>';
  echo '<br /><strong>Similar groups ('.$GROUPYCNT.'/'.$GROUPYITEMCOUNT.')';
  ?>
<a href="#" class="tooltip levytooltip dejtopravo"> <img src="./img/info.png" alt="Info" height="20" width="20" class="infoImg" />
<span>
Groups of similar municipalities are listed here. <br>After <b>clicking on any group</b>, the group will be <b>highlighted</b> in the map. In the left part of the interface, the table with attributes of municipalities contained in the selected group will be displayed.<br>
The names of the attributes can be displayed using the legend button nearby.
</span>
</a></strong>
  <?php
  echo '<div id="klikyScroll" style="clear:right" >';
  echo $GROUPYTXT;
  echo '</div>';
}
?>
</div>

<div id="graphBox"><div id="map"></div>
</div>


<br class="clearAll" />
</div>


</div>
<div id="footer">&copy; 2016 - <?php echo date('Y');?> <a href="mailto:jitka.dolezalova@upol.cz">Jitka Doležalová</a>, <a href="mailto:stanislav.popelka@upol.cz">Stanislav Popelka</a> & Ondřej Štrubl, 
Palacký University Olomouc, Faculty of Science, <a href="http://www.geoinformatics.upol.cz" target="_blank">Department of Geoinformatics</a></div>
</body>
</html>
