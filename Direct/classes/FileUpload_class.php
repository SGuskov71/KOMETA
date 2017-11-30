<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . 'FileUpload/FileUploadBackEndFunction.php');

class FileUpload_class {

  function GetServerDirListBackEnd() {
    $response = array();
    $obj->name = 'FileTempDir';
    array_push($response, $obj);
    unset($obj);
    $obj->name = 'FileStorage';
    array_push($response, $obj);
    unset($obj);
    $obj->name = 'UserData';
    array_push($response, $obj);

    return $response;
  }

  function DeleteFileByMd5Name($FileMd5Name) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    global $ID_User;
    if (!isset($FileMd5Name)) {
      $result = new JSON_Result(false, 'Не определено имя файла', NULL);
      return $result;
    }

    $sql = "SELECT * FROM mbf_store   where code='$FileMd5Name' and (id_user=$ID_User or ($ID_User=0))"; // Удалить может или владелец или администратор
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    if (kometa_num_rows($res) == 0) {
      $result = new JSON_Result(false, 'Файл в БД не зарегистрирован для этого пользователя', NULL);
      return $result;
    } else {
      Trans_Begin();
      $sql = "delete FROM mbf_store  ";
      $sql .=" where code='$FileMd5Name' and id_user=" . $ID_User;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        Trans_RollBack();
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      }
      $sql = "SELECT * FROM mbf_store  ";
      $sql .=" where code='$FileMd5Name' ";
      $res = kometa_query($sql);
      if (kometa_num_rows($res) == 0) {//если для других пользователей не зарегестрирован то пытаюсь удалить физически
        $md5FilePath = Get_md5FilePath($FileMd5Name);
        $serverFileName = $md5FilePath . $FileMd5Name;
        if (file_exists($serverFileName)) {
          try {
            unlink($serverFileName);
          } catch (Exception $e) {
            Trans_RollBack();
            $result = new JSON_Result(false, 'Не могу физически удалить файл ' . $serverFileName, NULL);
            return $result;
          }
        }
        Trans_Commit();
        $result = new JSON_Result(true, 'Удалено успешно!', NULL);
        return $result;
      }
    }
  }

  function DownloadFileByMd5Name($FileMd5Name) {
    global $ID_User;
    if (!isset($FileMd5Name)) {
      $result = new JSON_Result(false, 'Не определено имя файла', NULL);
      return $result;
    }
    $sql = "SELECT f.*, t.code mime_type FROM mbf_store as f left join mbf_type as t on f.id_type=t.id_type ";
    $sql .=" where f.code='$FileMd5Name' and f.id_user=" . $ID_User;
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    if (kometa_num_rows($res) == 0) {
      $result = new JSON_Result(false, 'Файл в БД не найден', NULL);
      return $result;
    }
    $row = kometa_fetch_object($res);
    $filename = $row->filename;
    $filetype = $row->mime_type;
    if ((!isset($filetype)) || ($filetype == '') || ($filetype == 'none')) {
      $filetype = 'application/octet-stream';
    }
    $md5FilePath = Get_md5FilePath($FileMd5Name);
    $serverFileName = $md5FilePath . $FileMd5Name;
    if (!file_exists($serverFileName)) {
      $result = new JSON_Result(false, 'Файл не найден на сервере!', $serverFileName);
      return $result;
    } else {
      $response = array(
        'serverFileName' => $serverFileName,
        'filename' => $filename,
        'filetype' => $filetype
      );
      $result = new JSON_Result(true, 'Файл Передан!', $response);
      return $result;
    }
  }

}
