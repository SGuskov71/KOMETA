<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/SqlFotmatter/SqlFormatter.php");

class ConnectionINIObject_class {

  function get_ConnectionINIObject() {
    $ConnectionINIObject = new stdClass();

    // читаем Connection.ini
    $sapi = php_sapi_name();
    $path_parts = pathinfo(__FILE__);
    $workdir = $path_parts['dirname'];
    if (($sapi == 'cli') /* && isset($workdir) */) {
      $INIfilename = $argv[1] . 'Connection.ini';
    } else {
      $INIfilename = $_SESSION['APP_INI_DIR'] . 'Connection.ini';
    }
    if (!file_exists($INIfilename)) {
      $result = new JSON_Result(false, 'Отсутствует ' . $INIfilename, null);
    } else {
      $ConnectionINIArray = parse_ini_file($INIfilename);
      $ConnectionINIObject->DisableBrowserBack = $ConnectionINIArray['DisableBrowserBack'];
      $ConnectionINIObject->TYPE_LOGIN = $ConnectionINIArray['TYPE_LOGIN'];
      $ConnectionINIObject->logo_img = $ConnectionINIArray['logo_img'];
      $ConnectionINIObject->AppTitle = $ConnectionINIArray['AppTitle'];
      $ConnectionINIObject->css_desktop = $ConnectionINIArray['css_desktop'];
      $ConnectionINIObject->css_grid = $ConnectionINIArray['css_grid'];
      $result = new JSON_Result(true, '', $ConnectionINIObject);
    }
    return $result;
  }

}
