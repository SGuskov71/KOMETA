<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}

//глобальные константы каталогов для работы с файлами
global $upload_dir_root;
$upload_dir_root = $_SESSION['FileStorage'];
global $Temp_dir;
$Temp_dir = $_SESSION['FileTempDir'];

// проверка начальной доступности директории
function CheckDirPermission($dir) {
  $tf = $dir . md5(rand()) . ".test";
  $f = @fopen($tf, "w");
  if ($f == false) {
    return false;
  } else {
    fclose($f);
    unlink($tf);
    return true;
  }
}

function Get_md5FilePath($filename) {//возвращает путь хранения файла по его хэш имени
  $result = $_SESSION['FileStorage'];
  $result.= substr($filename, 0, 2) . '/' . substr($filename, 2, 2) . '/';
  return $result;
}

function GetFile_MIME_Type($filename) {//возвращает MIME
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $filetype = finfo_file($finfo, $filename);
  finfo_close($finfo);
  return $filetype;
}

function Get_MIME_Type_id($MIME_Type) {//возвращает код типа MIME
  if ($MIME_Type == '')
    $MIME_Type = 'none';
  $sql = "SELECT id_type,code, short_name, ext FROM mbf_type  ";
  $sql .=" where code='$MIME_Type' ";
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if ($s_err != '') {
    return 0;
  }
  if (kometa_num_rows($res) == 0) {
    $sql = "INSERT INTO mbf_type(code, short_name)"
            . "VALUES ("
            . "'" . $MIME_Type . "', "
            . "'" . $MIME_Type . "')";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      return 0;
    } else {
      $sql = "SELECT id_type,code, short_name, ext FROM mbf_type  ";
      $sql .=" where code='$MIME_Type' ";
      $res = kometa_query($sql);
    }
  }
  $row = kometa_fetch_object($res);
  return $row->id_type;
}

function GetFileExt($fileName) {
  return preg_replace('/^.*\.([^.]+)$/D', '$1', $fileName);
}

function _log($value) {
  error_log(print_r($value, true));
}

function _response($success = true, $message = 'OK') {
  $response = array(
      'success' => $success,
      'message' => $message
  );

  echo json_encode($response);
  exit();
}

function _error($message) {
  return _response(false, $message);
}
