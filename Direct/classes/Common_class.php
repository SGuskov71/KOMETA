<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/SqlFotmatter/SqlFormatter.php");

class Common_class {

  function get_sysname($id_object) {
    $result = get_sysname($id_object);
    return $result;
  }

  function get_link_info($id_link) {
    return get_link_info($id_link);
  }

  function get_id_object($sysname) {
    $result = get_id_object($sysname);
    return $result;
  }

  function DeleteRecord($id_object, $ValueKeyFields, $Cascade) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $id_edit_object = get_id_edit_object($id_object);
    $f_save_error = delete_object($id_edit_object, $ValueKeyFields, $Cascade);

    if (isset($f_save_error) && ($f_save_error != '')) {
      $result = new JSON_Result(false, 'Произошла ошибка удаления записи.' . $f_save_error, '');
      return $result;
    } else {
      $result = new JSON_Result(true, 'Успешно удалено', '');
      return $result;
    }
  }

  function DuplicateRecord($id_object, $ValueKeyFields, $NewCodeValue) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $id_edit_object = get_id_edit_object($id_object);
    $sysname = get_sysname($id_edit_object);
    $code_field = get_code_field($sysname); // кодовое поле
// запрашиваем   список полей объекта
    $i = 0;
    $f = '';
    $v = '';
    $check = '';
    $coma = '';
    $coma_check = '';
    $sql = "SELECT  fieldname,is_field_key, is_field_code,  is_descr  FROM mb_object_field where id_object= $id_edit_object ";

    $res = kometa_query($sql);
    while ($row = kometa_fetch_object($res)) {

      if (($row->is_field_code == 1)) {
        $f.=$coma . $row->fieldname;
        $v.=$coma . my_escape_string($NewCodeValue);
        $check.=$coma_check . $row->fieldname . "=" . my_escape_string($NewCodeValue);
        $coma_check = ' and ';
        $coma = ',';
      } else if (($row->is_field_key == 1)) {
        // пропускаем
      } else {
        $f.=$coma . $row->fieldname;
        $v.=$coma . $row->fieldname;
        $coma = ',';
      }
    }
    $coma = '';
// проверяем есть ли с таким кодом объект
    $sql = "SELECT count(*) as cnt from $sysname where $check";
    $res = kometa_query($sql);
    $row = kometa_fetch_object($res);
    if ($row->cnt > 0) {
      $result = new JSON_Result(false, 'Указанный код уже используется.', '');
      return $result;
    }

    $coma = '';
    $w = '';
    foreach ($ValueKeyFields as $key => $value) {
      $w.="$coma $key='$value'";
      $coma = ' and ';
    }

    $sql = "insert into $sysname ($f) (SELECT $v from $sysname where $w) ";
    $res = kometa_query($sql);

    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, "Ошибка дублирования записи. Обратитесь к разработчику. $s_err", '');
      return $result;
    } else {
      $result = new JSON_Result(true, 'Создана новая запись с кодом ' . $NewCodeValue);
      return $result;
    }
  }

  function SqlFormatter($SourceText) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $res = SqlFormatter::format($SourceText, false);
    $result = new JSON_Result(true, '', $res);
    return $result;
  }

  function GetComboBoxStore($sysname) {

    $id_field = get_key_field($sysname);
    if (!isset($id_field)) {
      $result = new JSON_Result(false, "Не задано ключевое поле для объекта $sysname", '');
      return $result;
    } else {
      $id_obj = ($_GET['ID_OBJ']);

      if (isset($id_obj) && ($id_obj == ''))
        $dFields .= "''";
      else {
        $sql = "select connector from mb_object where sysname='$sysname'";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $result = new JSON_Result(false, $sql . ' ' . $s_err, '');
          return $result;
        }
        $row = kometa_fetch_object($res);
        $c = $row->connector;

        $sql = "select _of.fieldname from mb_object o join mb_object_field _of on o.id_object=_of.id_object where o.sysname='$sysname' and _of.is_descr=1 order by _of.order_view";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $result = new JSON_Result(false, $sql . ' ' . $s_err, '');
          return $result;
        }
        $i = 0;
        while ($row = kometa_fetch_object($res)) {
          if ($i > 0) {
            if ($type_login == 3)
              $dFields .= "+'$c'+" . "coalesce(" . $row->fieldname . ", '')";
            else
              $dFields .= "||'$c'||" . "coalesce(" . $row->fieldname . "::text, '')";
          } else {
            if ($type_login == 3)
              $dFields .= "coalesce(" . $row->fieldname . ", '')";
            else
              $dFields .= "coalesce(" . $row->fieldname . "::text, '')";
          }
          $i++;
        }
      }
      if (!isset($dFields))
        $dFields .= $id_field;
      if (!isset($id_obj))
        $sql = "select $id_field as id,$dFields as name from $sysname";
      else if ($id_obj != '')
        $sql = "select $id_field as id,$dFields as name from $sysname where $id_field='$id_obj'";
      else
        $sql = "select NULL as id,$dFields as name";

      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        $result = new JSON_Result(false, $sql . ' ' . $s_err, '');
        return $result;
      }
      $result = array();
      while ($row = kometa_fetch_object($res)) {
        array_push($result, $row);
      }
      $result = new JSON_Result(true, '', $result);
      return $result;
    }
  }

}
