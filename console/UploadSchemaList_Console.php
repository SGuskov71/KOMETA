<?php

$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}
require_once("../2gConnection.php");
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}
$sqlOut = "select id_xsd,full_name from mbo_schema join mbs_receive on mbs_receive.id_receiver=mbo_schema.id_receiver where mbs_receive.code <>'kometajs'";
$res = kometa_query($sqlOut);
echo kometa_last_error();
$y = 0;
$ResArray = Array();
while ($row = kometa_fetch_object($res)) {
  $s = 'php ../DB2XML/DB2XML.php '  . $argv[1] .' '. $row->id_xsd;
  echo $row->full_name.'----'.$s . PHP_EOL;
  exec($s);
}
?>
