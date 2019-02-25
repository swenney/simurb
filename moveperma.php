<?php
$dir = dirname(__FILE__);
$source = (isset($_POST['source']) and trim($_POST['source']))?$_POST['source']:false;
//$source = (isset($_GET['source']) and trim($_GET['source']))?$_GET['source']:false;
if($source)
{
  if(file_exists($dir.'/output/'.$source.'.csv') and
     file_exists($dir.'/output/'.$source.'.json') and
     file_exists($dir.'/output/'.$source.'.sett')
  )
  {
    copy($dir.'/output/'.$source.'.csv', $dir.'/permanent/'.$source.'.csv');
    copy($dir.'/output/'.$source.'.sett', $dir.'/permanent/'.$source.'.sett');
    copy($dir.'/output/'.$source.'.json', $dir.'/permanent/'.$source.'.json');
    $file = file($dir.'/permanent/'.$source.'.sett');
    $json = json_decode($file[0], true);
    copy($dir.'/source/'.$json['origsource'], $dir.'/permanent/'.$source.'.txt');
    /*unlink($dir.'/output/'.$source.'.csv');
    unlink($dir.'/output/'.$source.'.sett');
    unlink($dir.'/output/'.$source.'.json');*/
  }
}
?>
