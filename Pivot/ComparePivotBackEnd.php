<?php

header("Content-type: application/json; charset=utf-8");
session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
//$path_parts = pathinfo(__FILE__);
//$workdir = $path_parts['dirname'];
//if (isset($workdir)) {
//  chdir($workdir);
//}
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
//$path_parts = pathinfo(__FILE__);
//$workdir = $path_parts['dirname'];
//if (isset($workdir)) {
//  chdir($workdir);
//}
//

if (!isset($ID_User)) {
  // соединение посрочено необходимо переподключение
  $result = new JSON_Result(false, my_escape_string($s_err), 're_connect');
  echo json_encode($result);
  exit();
}
require_once("RunPivotBackEnd.php");
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}

if ($_POST['GetPivotSavedDataList']) {
  GetPivotSavedDataList();
} else if ($_POST['GetPivotCoparedData']) {
  GetPivotCoparedData();
} else if ($_POST['DeletePivotSavedData']) {
  DeletePivotSavedData();
} else if ($_POST['SavePivotData']) {
  SavePivotData();
} else if ($_POST['GetPivotCompareByCode']) {
  GetPivotCompareByCode();
} else
  exit();

function GetPivotCompareByCode() {
  global $type_login;
  if (!isset($_POST['pivot_code'])) {
    $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
    echo json_encode($result);
    exit();
  }
  $sql = "SELECT id_pivot_storage, id_object, id_user, short_name, description, code FROM mb_pivot_storage where code='" . $_POST['pivot_code'] . "'";
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if ($s_err != '') {
    $result = new JSON_Result(false, $s_err, NULL);
    echo json_encode($result);
    exit();
  }
  if (kometa_num_rows($res) > 0) {
    $row = kometa_fetch_object($res);
    $result->id_pivot_storage = $row->id_pivot_storage;
    $result->PivotCaption = $row->short_name;
    if ($type_login == 3)
      $sql = "SELECT id_pivot_time_slice, convert(varchar,dt_building,120) as dt_building FROM mb_pivot_time_slice where id_pivot_storage=" . $result->id_pivot_storage;
    else
      $sql = "SELECT id_pivot_time_slice, dt_building::timestamp FROM mb_pivot_time_slice where id_pivot_storage=" . $result->id_pivot_storage;
    $res = kometa_query($sql);
    $pivot_list = array();
    while ($row = kometa_fetch_object($res))
      array_push($pivot_list, $row);
    $result->combo_data = $pivot_list;
    $result = new JSON_Result(true, '', $result);
    echo json_encode($result);
    exit();
  } else {
    $result = new JSON_Result(false, 'Не найдено записей с кодом ' . $_POST['pivot_code'], NULL);
    echo json_encode($result);
    exit();
  }
}

function GetPivotSavedDataList() {
  global $type_login;
  if (!isset($_POST['id_pivot_storage'])) {
    $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
    echo json_encode($result);
    exit();
  }
  if ($type_login == 3)
    $sql = "SELECT id_pivot_time_slice, convert(varchar,dt_building,120) as dt_building FROM mb_pivot_time_slice where id_pivot_storage=" . $_POST['id_pivot_storage'];
  else
    $sql = "SELECT id_pivot_time_slice, dt_building::text FROM mb_pivot_time_slice where id_pivot_storage=" . $_POST['id_pivot_storage'];
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if ($s_err != '') {
    $result = new JSON_Result(false, $s_err, NULL);
    echo json_encode($result);
    exit();
  }
  $pivot_list = array();
  while ($row = kometa_fetch_object($res)) {
    array_push($pivot_list, $row);
  }

  $result = new JSON_Result(true, $s_err, $pivot_list);
  echo json_encode($result);
}

function GetPivotCoparedData() {
  if (!isset($_POST['id_pivot_time_slice_1']) || !isset($_POST['id_pivot_time_slice_2']) || !isset($_POST['operation'])) {
    $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
    echo json_encode($result);
    exit();
  }
  $sql = "SELECT pivotdata FROM mb_pivot_time_slice where id_pivot_time_slice=" . $_POST['id_pivot_time_slice_1'];
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if ($s_err != '') {
    $result = new JSON_Result(false, $s_err, NULL);
    echo json_encode($result);
    exit();
  }
  $row = kometa_fetch_object($res);
  $TempObj = json_decode($row->pivotdata);
  $PivotObjectResult = new CPivotResultObject;
  $PivotObjectResult->header = $TempObj->header;
  $PivotObjectResult->border = $TempObj->border;

  $sql = "SELECT pivotdata FROM mb_pivot_time_slice where id_pivot_time_slice=" . $_POST['id_pivot_time_slice_2'];
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if ($s_err != '') {
    $result = new JSON_Result(false, $s_err, NULL);
    echo json_encode($result);
    exit();
  }
  $row = kometa_fetch_object($res);
  $TempObj = json_decode($row->pivotdata);
  $SecondPivotObject = new CPivotResultObject;
  $SecondPivotObject->header = $TempObj->header;
  $SecondPivotObject->border = $TempObj->border;
  $PivotObjectResult->border_cnt = $TempObj->border_cnt;
  $PivotObjectResult->top_cnt = $TempObj->top_cnt;

  $PivotObjectResult->GridModel = $PivotObjectResult->GetGridModel();
  $PivotObjectResult->GridColumnModel = $PivotObjectResult->GetGridColumnModel();
  global $HierarhiSeparator;
  $PivotObjectResult->GridData = $PivotObjectResult->GetComparedGridData($SecondPivotObject, $_POST['operation'], $HierarhiSeparator);

  $result = new JSON_Result(true, '', $PivotObjectResult);
  $result->result->header = null; //зачищаю ненужные данные
  $result->result->border = null; //зачищаю ненужные данные
  $result->result->HeaderColumnCounter = null; //зачищаю ненужные данные
  echo json_encode($result);
}

function DeletePivotSavedData() {
  if (!isset($_POST['id_pivot_time_slice'])) {
    $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
    echo json_encode($result);
    exit();
  }
  $sql = "delete FROM mb_pivot_time_slice where id_pivot_time_slice=" . $_POST['id_pivot_time_slice'];
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if ($s_err != '') {
    $result = new JSON_Result(false, $s_err, NULL);
    echo json_encode($result);
    exit();
  } else {
    $result = new JSON_Result(true, 'Успешно удалено', NULL);
    echo json_encode($result);
    exit();
  }
}

function SavePivotData() {
  global $type_login;
  if (!isset($_POST['id_pivot_storage'])) {
    $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
    echo json_encode($result);
    exit();
  }
  $sql = "SELECT id_pivot_storage, id_object, id_user, short_name, description, code FROM mb_pivot_storage WHERE id_pivot_storage=" . $_POST['id_pivot_storage'];
  $PivotObject = GetPivotObject($sql);
  if ($PivotObject->success == true) {
    $PivotObject = $PivotObject->result;
    $PivotObject->GridModel == null; //зачищаю ненужные данные
    $PivotObject->GridColumnModel == null; //зачищаю ненужные данные
    $PivotObject->GridData == null; //зачищаю ненужные данные

    if ($type_login == 3)
      $dt = "CONVERT(datetime, '" . date("Y-m-d\TH:i:s", time()) . "', 126)";
    else
      $dt = "current_timestamp";


    $sql = "INSERT INTO mb_pivot_time_slice(id_pivot_storage, dt_building, pivotdata)"
        . "VALUES (" . $_POST['id_pivot_storage'] . ", $dt, "
        . my_escape_string(json_encode($PivotObject)) . ")";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, my_escape_string($s_err), NULL);
      echo json_encode($result);
      exit();
    } else {
      $result = new JSON_Result(true, my_escape_string('Успешно сохранено'), NULL);
      echo json_encode($result);
      exit();
    }
  } else {
    $result = new JSON_Result(false, my_escape_string('Объект не создан'), NULL);
    echo json_encode($result);
    exit();
  }
}
