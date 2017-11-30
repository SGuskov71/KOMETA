<?php

if (!isset($_COOKIE[ini_get('session.name')])) {
  session_start();
}
// Получение оставшегося времени для перезагрузки
// читаем Connection.ini
$sapi = php_sapi_name();
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (($sapi == 'cli') && isset($workdir)) {
  chdir($workdir);
}

$INIfilename = $_SESSION['APP_INI_DIR'] . 'Connection.ini';
if (!file_exists($_SESSION['APP_INI_DIR'] . 'Connection.ini')) {
  echo 'Отсутствует Connection.ini';
} else {
  $ConnectionINIArray = parse_ini_file($_SESSION['APP_INI_DIR'] . 'Connection.ini');
  $db = $ConnectionINIArray['PG_DB'];
  $port = $ConnectionINIArray['PG_PORT'];
  $host = $ConnectionINIArray['PG_HOST'];
  $db_usr = $ConnectionINIArray['PG_USR'];
  $db_psw = $ConnectionINIArray['PG_PWD'];
  $type_login = $ConnectionINIArray['TYPE_LOGIN'];
}

switch ($type_login) {
  case 0: {
      // загрузка с запросом логина для портала
      if (!($cConn = pg_pconnect("host=$host port=$port dbname=$db user='$db_usr' password='$db_psw'"))) {
        
      }
      break;
    }
  case 1: {
      // загрузка без включеной защитой gss
      $db_usr = $_SERVER['PHP_AUTH_USER'];
      $i = strpos('@', $db_usr);
      if ($i > 0) {
        $db_usr = substr($db_usr, 0, $i);
      }

      if (!($cConn = pg_pconnect("host=$host port=$port dbname=$db user=$db_usr"))) {
        
      }
      break;
    }
  case 2: {
      $db_usr = $_SERVER['PHP_AUTH_USER'];
      $i = strpos('@', $db_usr);
      if ($i > 0) {
        $db_usr = substr($db_usr, 0, $i);
      }
      // загрузка с включеной защитой gss
      if (!($cConn = pg_pconnect("host=$host port=$port dbname=$db"))) {
        
      }
      break;
    }
  case 3: { // MSSQL
      //$serverName = "<имя_вашего_sql-сервера\имя_инстанции,номер_порта>"; //если instance и port стандартные, то можно не указывать
      $connectionInfo = array("UID" => $db_usr, "PWD" => $db_psw, "Database" => $db);

      // загрузка с запросом логина для портала

      if (!($cConn = sqlsrv_connect($host, $connectionInfo))) {
        
      }
      break;
    }
};

if ($cConn === false) {
  exit();
}
if ($type_login == 3) {  //способ подключения
  //MSSQL
  $sql = "select DATEDIFF (minute,getdate(),session_ttl) as state  from mba_sessions WHERE is_closed=0 and code_session='".session_id()."'";
  $sql1 = mb_convert_encoding($sql, 'Windows-1251', 'UTF-8');
  $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
  $params = array();
  $res = sqlsrv_query($cConn, $sql1, $params, $options);
  $r = sqlsrv_fetch_object($res);
} else {
   $sql = "select trunc(EXTRACT(epoch FROM (session_ttl-current_timestamp))/60) as state  from mba_sessions WHERE is_closed=0 and code_session='".session_id()."'";
  $res = pg_query($cConn, $sql);
  $r = pg_fetch_object($res);
}
echo $r->state;
