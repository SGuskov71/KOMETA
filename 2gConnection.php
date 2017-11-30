<?php

// Корректность ссылки (URL)
// Существование ссылки (URL)
function open_url($url) {
  $url.='index.html';
  $url_c = parse_url($url);

  if (!empty($url_c['host'])) {
    // Ответ сервера
    if ($otvet = get_headers($url)) {
      $s=substr($otvet[0], 9, 2);
      return ($s!='') && (substr($s,0,1)!='4');
    }
  }
  return false;
}

function my_escape_string($d) {
  $d = trim($d);
  if ((!isset($d)) || ($d === 0) || (trim($d) === '')) {
    return ('NULL');
  } else {
//$s = str_replace("'", "''", $d);
    $s = kometa_escape_string($d);
//    $s = "'{$s}'";
    $s = "'$s'";
    return ($s);
  }
}

set_magic_quotes_runtime(0);
/*
  Подключение к базе данных и основной набор функций необходимых для работы
  Необходимо включить в каждый файл в котором осуществляется обращение к базе данных
 */
if (!isset($_COOKIE[ini_get('session.name')])) {
  session_start();
}
if (!isset($_SESSION['APP_INI_DIR'])) {
  if (isset($_GET['root']))
  $_SESSION['APP_INI_DIR'] = $_GET['root'];
  else  {
    $d=pathinfo(__FILE__);
  $_SESSION['APP_INI_DIR'] = $d['dirname'];
  }
  if (isset($_SESSION['APP_INI_DIR']) && (substr($_SESSION['APP_INI_DIR'], -1, 1) <> '/'))
    $_SESSION['APP_INI_DIR'].='/';
}
//unset( $_SESSION['PG_DB']);
//error_reporting(E_ALL & ~E_DEPRECATED);
if (file_exists($_SESSION['APP_INI_DIR'] . 'ProjectPath.ini')) {
  $ProjectPathINIArray = parse_ini_file($_SESSION['APP_INI_DIR'] . 'ProjectPath.ini');
  if (!script_is_ajax()) {
    foreach ($ProjectPathINIArray as $key => $value) {
      if (isset($value) && (substr($value, -1, 1) <> '/'))
        $value.='/';
      $_SESSION["$key"] = $value;
      if ((!file_exists($value)) && (!open_url($value))) {
        echo "<script>alert(\"Path '$value'not found \");</script>";
      }
    }
  }
}

if (!isset($_SESSION['ProjectRoot'])) {
  require_once($_SESSION['ProjectRoot'] . "ProjectPath.php");
}
global $cConn; // подключение
global $ID_User; //ИД пользователь
global $ID_User_sys; // ИД администратора системы по умолчанию
global $ID_group_sys; // ИД группы админисраторов
global $LOGIN_User; //логин пользователя в системе
global $type_login; //способ подключения
global $db_usr; //логин пользователя подключенного к базе
global $AppTitle; // заголовок онна проекта
global $logo_img; // ссылка на картинку для логина
global $LOGON_CAPTION; // стока логон
global $Version; // Версия системы
//Функции для получения ID пользователя при логине через базу
// формирование строки с дотой для добавления в БД

function script_is_ajax() {
  return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}

function dateAdd($interval, $number, $date) {
  $date_time_array = getdate($date);
  $hours = $date_time_array['hours'];
  $minutes = $date_time_array['minutes'];
  $seconds = $date_time_array['seconds'];
  $month = $date_time_array['mon'];
  $day = $date_time_array['mday'];
  $year = $date_time_array['year'];

  switch ($interval) {
    case 'yyyy':
      $year+=$number;
      break;
    case 'q':
      $year+=($number * 3);
      break;
    case 'm':
      $month+=$number;
      break;
    case 'y':
    case 'd':
    case 'w':
      $day+=$number;
      break;
    case 'ww':
      $day+=($number * 7);
      break;
    case 'h':
      $hours+=$number;
      break;
    case 'n':
      $minutes+=$number;
      break;
    case 's':
      $seconds+=$number;
      break;
    default:
      break;
  }
  $timestamp = mktime($hours, $minutes, $seconds, $month, $day, $year);
  return $timestamp;
}

// обновление последнего входа в сессию
function updateUserSessionTtl() {
  global $cConn;
  global $ID_User;
  global $type_login; //способ подключения

  if (!isset($cConn))
    die('Подключение не создано');

  if (!isset($ID_User))
    die('Пользователь не определен');

  if ($type_login == 3)
    $sqlOut = "UPDATE mba_sessions SET session_ttl=dateAdd(minute, 30, GETDATE()) WHERE is_closed=0 and  code_session='" . session_id() . "';";
  else
    $sqlOut = "UPDATE mba_sessions SET session_ttl= current_timestamp +INTERVAL '30 MINUTES' WHERE is_closed=0 and code_session='" . session_id() . "';";
  $res = kometa_query($sqlOut);
}

// получение id пользователя работающего в системе с указанного ip
function get_id_user() {
  global $cConn;
  global $ID_User;
  global $type_login; //способ подключения

  if (!isset($cConn))
    die('Подключение не создано');

  // удаляем устаревшие блокировки
  if ($type_login == 3)
    $sqlOut = "DELETE FROM mba_lock WHERE exists(select *  from mba_sessions ses where ses.is_closed=0 and ses.id_session=mba_lock.id_session and ses.session_ttl < GETDATE());";
  else
    $sqlOut = "DELETE FROM mba_lock WHERE exists(select *  from mba_sessions ses where ses.is_closed=0 and ses.id_session=mba_lock.id_session and ses.session_ttl < '" . date("c") . "');";
  $res = kometa_query($sqlOut);
  // удаляем старые сеесии
  if ($_SESSION['reopen_sesion']) {
    $ss = " or (code_session='" . session_id() . "')";
    $_SESSION['reopen_sesion'] = null;
  } else
    $ss = '';
  if ($type_login == 3)
    $sqlOut = "UPDATE mba_sessions SET is_closed=1 WHERE session_ttl < GETDATE() $ss;";
  else
    $sqlOut = "UPDATE mba_sessions SET is_closed=1 WHERE session_ttl < current_timestamp $ss;";
  $res = kometa_query($sqlOut);
  $sqlOut = "SELECT id_user from mba_sessions where is_closed=0 and code_session='" . session_id() . "';";
  $res = kometa_query($sqlOut);

  if (($res) && ($row = kometa_fetch_object($res))) {
    $ID_User = $row->id_user;


    return $row->id_user;
  } else
    return NULL;
}

// Устаноить блокировку
function set_lock($id_object, $id_obj) {
  global $ID_User;
  // проверяю является ли этот объект таблицей
  $sql = "select id_object from mb_object where id_object=$id_object";
  $res = kometa_query($sql);
  if (($row = kometa_fetch_object($res) ) && ($row->id_object_type = 1)) {
    // объеккт существует и он являетя таблицей, тогда можно делать блокировку
    // проверяю нет ли блокировки на этот объект
    $sql = "SELECT id_object,$id_obj from mba_lock as lck where  lck.id_object=$id_object and lck.$id_obj='$id_obj'";
    $res = kometa_query($sql);
    if (kometa_num_rows($res) > 0) {
      // блокировка установлена возвращаем
      return false;
    }
  } else {

    return false;
  }
}

function table_exists($table_name) {
  global $type_login; //способ подключения
  // Определяем есть ли такая таблица
  if ($type_login == 3) {  //способ подключения
    $sql = "SELECT * FROM sys.tables where name='$table_name'";
  } else {
    $sql = "SELECT * FROM pg_tables where tablename='$table_name'";
  }
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    if ($sapi != 'cli')
      echo "Ошибка поиска таблицы для загрузки<br>";
    ProtWriteInput(1, $id_xml, 'Ошибка подготовки к загрузке ', $sql . '<br>' . $s_err . '<br>');
    exit;
  }

  return (kometa_num_rows($res) != 0);
}

// Снять блокировку
function set_unlock($id_object, $id_obj) {
  return true;
}

// Проверить на наличие блокировки
function is_lock($id_object, $id_obj) {
  // получить тип объекта  блокировка только для таблиц
  $sql = "select id_object from mb_object where id_object=$id_object";
  $res = kometa_query($sql);
  if (($row = kometa_fetch_object($res) ) && ($row->id_object_type = 1)) {
    // объеккт существует и он являетя таблицей, тогда можно делать блокировку
    // проверяю нет ли блокировки на этот объект
    $sql = "SELECT id_object from mba_lock as lck where  lck.id_object=$id_object and lck.$id_obj='$id_obj'";
    $res = kometa_query($sql);
    return (kometa_num_rows($res) > 0);
  } else {
    return false;
  }
}

//set_error_handler('err_handler'); //включаю запись ошибок в файл
// Возвращает полное имя каталока с завршающим слешом
function get_document_root() {
  $s = $_SERVER["DOCUMENT_ROOT"];
  if (isset($s) && (substr($s, -1, 1) <> '/'))
    $s.='/';
  return $s;
}

function set_Help_Label($idObject, $typeObject) {
  global $cConn;

  if (!isset($cConn))
    exit(1);
  $_SESSION["HelpFile"] = null;
  switch ($typeObject) {
    case 1: // Задача (кнопка)
      $sql = "select code_help from mb_task where id_task=$idObject";
      $res = kometa_query($sql);
      $help = kometa_fetch_object($res);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        echo $sql . '<br>' . $s_err . '<br>';
      }
      $h = $help->code_help;
      $_SESSION["HelpFile"] = htmlspecialchars($h);
      break;
    case 2: // Объект
      $sql = "select code_help from mb_object where id_object=$idObject";
      $res = kometa_query($sql);
      $help = kometa_fetch_object($res);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        echo $sql . '<br>' . $s_err . '<br>';
      }
      $h = $help->code_help;
      $_SESSION["HelpFile"] = htmlspecialchars($h);
      break;
    case 3: // Отчет
      $sql = "select code_help from mbr_report where id_report=$idObject";
      $res = kometa_query($sql);
      $help = kometa_fetch_object($res);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        echo $sql . '<br>' . $s_err . '<br>';
      }
      $h = $help->code_help;
      $_SESSION["HelpFile"] = htmlspecialchars($h);


      break;
    case 4:
      break;
  }
}

function err_handler($errno, $errmsg, $filename, $linenum) {
  if (!in_array($errno, Array(E_NOTICE, E_STRICT, E_WARNING))) {
    $date = date('Y-m-d H:i:s (T)');
    $f = fopen('errors.log', 'a');
    if (!empty($f)) {
      $err = "<error>\r\n";
      $err .= "  <date>$date</date>\r\n";
      $err .= "  <errno>$errno</errno>\r\n";
      $err .= "  <errmsg>$errmsg</errmsg>\r\n";
      $err .= "  <filename>$filename</filename>\r\n";
      $err .= "  <linenum>$linenum</linenum>\r\n";
      $err .= "</error>\r\n";
      flock($f, LOCK_EX); //блокировка
      fwrite($f, $err);
      flock($f, LOCK_UN);
      fclose($f);
    }
  }
}

//
// получить список групп текущего пользователя.
function get_id_user_groups() {
  global $cConn;
  global $ID_User;

  if (!isset($cConn))
    exit(1);
  $sql = "select id_group from mba_user_group as ug where ug.id_user=$ID_User";
  $res = kometa_query($sql);
  $s = '-1';
  while ($row = kometa_fetch_object($res))
    $s .= ',' . $row->id_group;

  return $s;
}

//Начао транзакции
function Trans_Begin() {
  /*  kometa_query('BEGIN;');
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != ''))
    return 0;
    else
   */
  return 1;
}

//Сохранение результатов транзакции
function Trans_Commit() {
  /*
    kometa_query('COMMIT;');
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != ''))
    return 0;
    else */
  return 1;
}

//Откат результатов транзакции
function Trans_RollBack() {
  kometa_query('ROLLBACK;');
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != ''))
    return 0;
  else
    return 1;
}

function kometa_close() {
  global $cConn;
  global $type_login; //способ подключения
  if (!isset($cConn))
    return NULL;
  if ($type_login == 3) {  //способ подключения
    //MSSQL
    sqlsrv_close($cConn);
  } else
    pg_close();
}

function kometa_query($sql) {
  set_time_limit(70000);
  global $cConn;
  global $type_login; //способ подключения
  if (!isset($cConn))
    return NULL;
  if ($type_login == 3) {  //способ подключения
    //MSSQL
    $sql1 = mb_convert_encoding($sql, 'Windows-1251', 'UTF-8');
    $options = array("Scrollable" => SQLSRV_CURSOR_KEYSET);
    $params = array();
    $res = sqlsrv_query($cConn, $sql1, $params, $options);
  } else
    $res = pg_query($cConn, $sql);

  $s_err = kometa_last_error();

  return $res;
}

function kometa_num_rows($res) {
  global $cConn;
  global $type_login; //способ подключения
  if (!isset($cConn))
    return NULL;

  if (isset($res))
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      $n = sqlsrv_num_rows($res);
      return $n;
    } else
      return pg_num_rows($res);
  else
    return NULL;
}

function kometa_fetch_row($res, $row) {
  // $row -  смещение от начала, необходимо для начала перебора
  global $type_login; //способ подключения
  if (isset($res)) {
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      return sqlsrv_fetch_row($res, SQLSRV_SCROLL_ABSOLUTE, $row);
    } else
      return pg_fetch_row($res, $row);
  } else
    return NULL;
}

function kometa_escape_string($str) {
  global $type_login; //способ подключения
  if ($type_login == 3) {
    $str = str_replace("'", "''", $str);
    return $str;
  } else
    return pg_escape_string($str);
}

function kometa_fetch_object($res, $rownum) {
  global $type_login; //способ подключения
  // $row -  смещение от начала, необходимо для начала перебора
  if (isset($res))
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      $ctorParams = array();
      if (isset($rownum))
        $r = sqlsrv_fetch_object($res, NULL, $ctorParams, SQLSRV_SCROLL_ABSOLUTE, $rownum);
      else
        $r = sqlsrv_fetch_object($res); //, 'stdClass', $ctorParams, SQLSRV_SCROLL_ABSOLUTE, $rownum);

      foreach ($r as $key => $value) {
        $t = get_class($r->$key);
        if ((is_object($r->$key))/* && ($t == 'DateTime') */) {
          $d = $r->$key;
          $s = $d->format("Y-m-d");
          $r->$key = $s;
        }if (is_numeric($value)) {
          if ((float) $value != (int) $value) {
            $r->$key = (float) $value;
          } else {
            $r->$key = iconv('cp1251', 'utf-8', $value);
          }
        } else
          $r->$key = iconv('cp1251', 'utf-8', $value);
      }
      return $r;
    } else {
      if (isset($rownum)) {
        $r = pg_fetch_object($res, $rownum);
        $r = pg_fetch_object($res);
      } else
        $r = pg_fetch_object($res);
      foreach ($r as $key => $value) {
        if (is_numeric($value)) {
          if ((float) $value != (int) $value) {
            $r->$key = (float) $value;
          } else {
            $r->$key = $value;
          }
        };
      }
      return $r;
    } else
    return NULL;
}

function kometa_last_error() {
  global $cConn;
  global $type_login; //способ подключения
  if (!isset($cConn))
    return NULL;
  else {
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      if (($errors = sqlsrv_errors(SQLSRV_ERR_ERRORS) ) != null) {
        $s = '';
        foreach ($errors as $error) {
          $s.= "SQLSTATE: " . iconv('cp866', 'utf-8', $error['SQLSTATE']) . "<br />";
          $s.= "Код: " . iconv('cp866', 'utf-8', $error['code']) . "<br />";
          $s.= "Сообщение: " . iconv('cp866', 'utf-8', $error['message']) . "<br />";
        }
      }
      return $s;
    } else
      return pg_last_error($cConn);
  }
}

function kometa_num_fields($res) {
  global $type_login; //способ подключения
  if (isset($res)) {
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      return sqlsrv_num_fields($res);
    } else
      return pg_num_fields($res);
  } else
    return NULL;
}

function kometa_field_name($res, $n) {
  global $type_login; //способ подключения
  if (isset($res))
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      $s = sqlsrv_get_field($res, $n + 1);
      $s_err = kometa_last_error();
      return $s;
    } else
      return pg_field_name($res, $n);
  else
    return NULL;
}

////////
// читаем Connection.ini
$sapi = php_sapi_name();
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
//echo $argv[1];
if (($sapi == 'cli') /*&& isset($workdir)*/) {
//  chdir($workdir);
  $INIfilename = $argv[1].'Connection.ini';
}
else {
$INIfilename = $_SESSION['APP_INI_DIR'] . 'Connection.ini';
}
//echo $INIfilename;
if (!file_exists($INIfilename)) {
  echo 'Отсутствует '.$INIfilename;
} else {
  $ConnectionINIArray = parse_ini_file($INIfilename);
  $db = $ConnectionINIArray['PG_DB'];
  $port = $ConnectionINIArray['PG_PORT'];
  $host = $ConnectionINIArray['PG_HOST'];
  $db_usr = $ConnectionINIArray['PG_USR'];
  $db_psw = $ConnectionINIArray['PG_PWD'];
  $type_login = $ConnectionINIArray['TYPE_LOGIN'];
  $logo_img = $ConnectionINIArray['logo_img'];
  $AppTitle = $ConnectionINIArray['AppTitle'];
  $s_logon = $ConnectionINIArray['LOGON_CAPTION'];
  $css_user_desktop = $ConnectionINIArray['css_desktop'];
  $css_user_grid = $ConnectionINIArray['css_grid'];
  

  if (!isset($AppTitle))
    $AppTitle = 'Демонстрационная версия';
}

//if ($sapi == 'cli')
//  $type_login = 0;
switch ($type_login) {
  case 0: {
   // echo "host=$host port=$port dbname=$db user='$db_usr' password='$db_psw'";
      // загрузка с запросом логина для портала
      if (!($cConn = pg_pconnect("host=$host port=$port dbname=$db user='$db_usr' password='$db_psw'")))
       if ($sapi != 'cli'){
              echo '<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>
  Ошибка настройки подключения</body></html>';
       }
       else{
       echo 'Ошибка настройки подключения';
       }
      else {
        $res = pg_query($cConn, "set datestyle to 'ISO, YMD'");

        $s_err = kometa_last_error();
      }
//        die('Ошибка подключения к базе занных');
      break;
    }
  case 1: {
      // загрузка без включеной защитой gss
      $db_usr = $_SERVER['PHP_AUTH_USER'];
      $i = strpos('@', $db_usr);
      if ($i > 0) {
        $db_usr = substr($db_usr, 0, $i);
      }

      if (!($cConn = pg_pconnect("host=$host port=$port dbname=$db user=$db_usr")))
        if ($sapi != 'cli'){
          echo '<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>
  Ошибка настройки подключения</body></html>';
        }
        else{
          echo 'Ошибка настройки подключения';
        }
//        die('Ошибка подключения к базе занных');
      break;
    }
  case 2: {
      $db_usr = $_SERVER['PHP_AUTH_USER'];
      $i = strpos('@', $db_usr);
      if ($i > 0) {
        $db_usr = substr($db_usr, 0, $i);
      }
      // загрузка с включеной защитой gss
      if (!($cConn = pg_pconnect("host=$host port=$port dbname=$db")))
        if ($sapi != 'cli'){
          echo '<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>
  Ошибка настройки подключения</body></html>';
        }
        else{
          echo 'Ошибка настройки подключения';
        }
//        die('Ошибка подключения к базе занных');
      break;
    }
  case 3: { // MSSQL
      //$serverName = "<имя_вашего_sql-сервера\имя_инстанции,номер_порта>"; //если instance и port стандартные, то можно не указывать
      $connectionInfo = array("UID" => $db_usr, "PWD" => $db_psw, "Database" => $db);

      // загрузка с запросом логина для портала

      if (!($cConn = sqlsrv_connect($host, $connectionInfo)))
        if ($sapi != 'cli'){
          echo '<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>
  Ошибка настройки подключения</body></html>';
        }
        else{
          echo 'Ошибка настройки подключения';
        }
//        die('Ошибка подключения к базе занных');
      break;
    }
};

if ($cConn === false) {
  echo '<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>

  Ошибка настройки подключения</body></html>';

  exit();
}
if ($type_login == 3) {
  kometa_query("SET DATEFORMAT ymd;");
}

$sqlOut = "SELECT id_user From mba_user where login='postgres'";
$res = kometa_query($sqlOut);
$row = kometa_fetch_object($res);
$ID_User_sys = $row->id_user;

$sqlOut = "SELECT id_group From mba_group where code='adm'";
$res = kometa_query($sqlOut);
$row = kometa_fetch_object($res);
$ID_group_sys = $row->id_group;
$k = 0;
$sql = "SELECT id_task FROM mb_task where is_sys=1";
$res = kometa_query($sql);
$k = kometa_num_rows($res);
//if ($k > 0) {
//  $sql = "SELECT id_task FROM mb_task where is_sys=0";
//  $res = kometa_query($sql);
//  $k = kometa_num_rows($res);
//}
if ($k > 0) {
  $sql = "SELECT id_object FROM mb_object";
  $res = kometa_query($sql);
  $k = kometa_num_rows($res);
}
if ($k == 0) {
  $ID_User = $ID_User_sys; // это первый запуск
  $sqlOut = "UPDATE  mba_sessions SET is_closed=1 WHERE is_closed=0 and code_session='" . session_id() . "' ";
  $res = kometa_query($sqlOut);
  if (isset($ID_User)) {
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      $sqlOut = "INSERT INTO mba_sessions (code_session, id_user, user_ip, session_ttl) VALUES ('" . session_id() . "', " . $ID_User . ", '" . $_SERVER['REMOTE_ADDR'] . "', DATEADD(minute,30,GETDATE()));";
    } else
      $sqlOut = "INSERT INTO mba_sessions (code_session, id_user, user_ip, session_ttl) VALUES ('" . session_id() . "', " . $ID_User . ", '" . $_SERVER['REMOTE_ADDR'] . "', current_timestamp +INTERVAL '30 MINUTES');";
    $res = kometa_query($sqlOut);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      echo 'Ошибка регистрации сессии ' . $sql . '<br>' . $s_err . '<br>';
    }
  }
} else {
  if ($sapi != 'cli')
    $ID_User = get_id_user();
  else
    $ID_User = $ID_User_sys;
}

if (isset($ID_User)) {

  updateUserSessionTtl($cConn);
  eval("\$sql=\"$s_logon\";");
  if (isset($sql)) {
    $res = kometa_query($sql);
    $row = kometa_fetch_object($res);
    $LOGON_CAPTION = $row->logon_caption;
    

  }
}

if ($_SESSION['ImportMetaDefinition'] == 1)
  $ID_User = $ID_User_sys;
if (!script_is_ajax() && (!isset($ID_User)) && (strpos($_SERVER['REQUEST_URI'], 'login') == false)) {
  echo "<script  src=\"" . $_SESSION['URLProjectRoot'] . "sys/ServiceFunction.js\" type=\"text/javascript\"></script>";
  echo '<SCRIPT TYPE="text/javascript">';

  echo 'if (isMobile.SenchaTouchSupported()) {';
  echo "findFirstWindow().window.location.href='" . $_SESSION['URLProjectRoot'] . "login.php?ret=" . $_SESSION['URLProjectRoot'] . "TouchStartMenu/TouchStartMenu.php&root=".urlencode($_GET['root'])."'";
  echo '}else{';
  echo "findFirstWindow().window.location.href='" . $_SESSION['URLProjectRoot'] . "login.php?ret=" . $_SESSION['URLProjectRoot'] . "Desktop/desktop.php&root=".urlencode($_GET['root'])."'}";
  echo "</SCRIPT>";
}

function CheckConnection() {//проверяет соедтнение с БД возращает стандартный JSON_Result,
//JSON_Result.success==true если соединение есть
  global $ID_User;
  if (!isset($ID_User)) {
// соединение посрочено необходимо переподключение
    $result = new JSON_Result(false, my_escape_string('Соединение разоравано. Требуется переподключение.'), 're_connect');
  } else {
    $result = new JSON_Result(true, my_escape_string('Соединение установлено.'), '');
  }
  return $result;
}

function set_Param_value($paramname, $short_name, $value, $id_user = null) {
  $result = 0;
  if ($id_user == null) {
    $id_user = 'NULL';
    $s_u = 'IS NULL';
  } else
    $s_u = "= $id_user";

  $short_name = my_escape_string($short_name);
  $value = my_escape_string($value);
  $paramname = my_escape_string($paramname);
  $sql = "Select code from mba_setting where code=$paramname and id_user $s_u";
  if ($res = kometa_query($sql)) {
    if (kometa_num_rows($res) > 0) {
      $sql = "update mba_setting set short_name=$short_name, value=$value where code=$paramname and id_user $s_u";
      $result = 2;
    } else {
      $sql = "INSERT INTO mba_setting(code, short_name, value, id_user) "
      . " VALUES ($paramname, $short_name,$value, $id_user )";
      $result = 1;
    }
    kometa_query($sql);
  }
  return $result;
}

function get_Param_value($paramname, $id_user = null) {
  if ($id_user == null)
    $s_u = 'IS NULL';
  else
    $s_u = "= $id_user";

  $paramname = my_escape_string($paramname);
  $res = kometa_query("Select value from mba_setting where code=$paramname and id_user $s_u");

  if (!isset($res))
    return NULL;
  if ($row = kometa_fetch_object($res)) {
    return $row->value;
  }
}

  

?>
