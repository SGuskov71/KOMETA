<?php

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

if (!isset($ID_User)) {
  // соединение посрочено необходимо переподключение
  $result = new JSON_Result(false, ('Соединение разоравано. Требуется переподключение.'), 're_connect');
  echo json_encode($result);
} else {
//переадресация комманд на функции
  if ($_GET['get_sysname']) {
    echo get_sysname($_GET['id_object']);
  } else if ($_GET['get_id_object']) {
    echo get_id_object($_GET['sysname']);
  } else if ($_GET['LoadTouchMenu']) {
    LoadTouchMenu();
  } else if ($_POST['LoadMenuObject']) {
    LoadMenuObject($_POST['id'], $_POST['text']);
  }
}

function GetTaskMenu($id_parent = NULL) {
  $result = array();
  if (isset($id_parent))
    $w = "id_parent=$id_parent  ";
  else
    $w = " id_parent is NULL ";
  $sqlOut = "select id_task,short_name,full_name,style,exec_script,id_operation_kind,code_help"
  . " from mb_task where exists(select * from  mba_grant_task "
  . " where mba_grant_task.id_task=mb_task.id_task"
  . " and mba_grant_task.id_group in (" . get_id_user_groups() . ") and  $w ) order by ord,short_name";
  $res = kometa_query($sqlOut);
  while ($row = kometa_fetch_object($res)) {
    unset($mi);
    $mi = new stdClass();
    $mi->text = $row->short_name;
    $mi->id = $row->exec_script;
    $mi->id_operation_kind = $row->id_operation_kind;
    $n = array_push($result, $mi);
    $newItem = $result[$n - 1];
    $newItem->children = GetTaskMenu($row->id_task);
    if (count($newItem->children) == 0)
      $newItem->leaf = true;
  }
  return $result;
}

function LoadTouchMenu() {
  // построить и вернут объект json по типу описанному в файле source.json
  $result = GetTaskMenu();
  //return ($result);
  echo json_encode($result);
}

function LoadMenuObject($id, $text) {
  $res = 'Ты выбрал: ' . $text . ' с кодом ' . $id;
  $result = new JSON_Result(true, $s_err, $res);
  echo json_encode($result);
}
