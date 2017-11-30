<?php

Class JSON_Result { //стандартный объект возврата JSON данных

  public $success; //признак успеха выполнения
  public $msg; //сообщение обработчика
  public $result; //запрошенный результат

  function __construct($_success, $_msg, $result) {
    $this->success = $_success;
    $this->msg = $_msg;
    $this->result = $result;
  }

}

//$path_parts = pathinfo(__FILE__);
//$workdir = $path_parts['dirname'];
//if (isset($workdir)) {
//  chdir($workdir);
//}

$sapi = php_sapi_name();
if ($sapi == 'cli') {
  $ProjectRoot = GetPathConsole('ProjectRoot');
  $LIB = GetPathConsole('LIB');
} else {
  $ProjectRoot = $_SESSION['ProjectRoot'];
  $LIB = $_SESSION['LIB'];
}
require_once($LIB . 'PHP/PEAR/Table.php');

function CheckExistCode($tablename, $codefieldname, $value) {
  $sql = "select count(*) as cnt from $tablename where $codefieldname ='$value'";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  return $row->cnt > 0;
}

function GenerateUnicalCodeField($tablename, $codefieldname) {//создает новое уникальное значение кода для таблицы
  $sql = "select count(*) as cnt from $tablename";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  $x = $row->cnt + 1;
  $result = $tablename . "_" . $x;
  while (CheckExistCode($tablename, $codefieldname, $result) == true) {
    $x++;
    $result = $tablename . "_" . $x;
  }
  return $result;
}

function get_type_history($sysname) {
// получение типа ведения истории
  $sql = "select id_history_type from mb_object where sysname='$sysname'";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  return $row->id_history_type;
}

function get_sq_current($sq_name) {
// получить текущее значение счетчика
// $sq_name - имя счетчика
  $sql = "SELECT currval('$sq_name') as cur";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  return $row->cur;
}

function get_id_field($sysname, $fldname) {
  $sql = "SELECT id_field FROM mb_object_field as f,mb_object as o WHERE f.id_object=o.id_object and o.sysname='$sysname' and f.fieldname='$fldname'";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  if (isset($row))
    return $row->id_field;
  else
    return null;
}

function get_short_name_field($sysname, $fldname) {
  $sql = "SELECT f.short_name FROM mb_object_field as f,mb_object as o WHERE f.id_object=o.id_object and o.sysname='$sysname' and f.fieldname='$fldname'";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  if (isset($row))
    return $row->short_name;
  else
    return null;
}

function get_id_parent_object($sysname) {
// получить ИД головного объекта
  $sql_1 = "SELECT case when p.sysname is null then o.sysname else p.sysname end as sysname,"
          . " case when p.id_object is null then o.id_object else p.id_object end as id_object "
          . " FROM mb_object as o left join mb_object as p on o.id_parent=p.id_object"
          . " where o.sysname='$sysname'";
//echo $sql_1;
  $res_1 = kometa_query($sql_1);
//  echo $res_1;
  $row_1 = kometa_fetch_object($res_1);
  $_sysname = $row_1->sysname;
//echo $_sysname;
  $_id_object = $row_1->id_object;
  return $_id_object;
}

function get_sysname_parent_object($sysname) {
// получить системное имя головного объекта
  $sql_1 = "SELECT case when p.sysname is null then o.sysname else p.sysname end as sysname,"
          . " case when p.id_object is null then o.id_object else p.id_object end as id_object "
          . " FROM mb_object as o left join mb_object as p on o.id_field_parent=p.id_object"
          . " where o.sysname='$sysname'";
  $res_1 = kometa_query($sql_1);
  $row_1 = kometa_fetch_object($res_1);
  $_sysname = $row_1->sysname;
  $_id_object = $row_1->id_object;
  return $_sysname;
}

function get_id_obj_first($sysname, $id_obj) {
// получить ИД первого экземпляра объекта из группы синонимов
  if (isset($id_obj) && isset($sysname)) {
    $sysname = get_sysname_parent_object($sysname);
    $fld_key = get_key_field($sysname);
    $fld_code = get_code_field($sysname);
    if (isset($fld_code) && isset($fld_key)) {
      $sql = "select min(t.$fld_key)/*,t.$fld_code*/ from $sysname t,$sysname t1 where t.$fld_code=t1.$fld_code and t1.$fld_key='$id_obj'";
      $res = kometa_query($sql);
      $row = kometa_fetch_row($res);
      if (isset($row[0])) {
//return $row[1];
        return $row[0];
      } else
        return $id_obj;
    }
    else {
      return $id_obj;
    }
  } else
    return null;
}

function get_id_obj_first_by_code($sysname, $code) {
// получить ИД первого экземпляра объекта из группы синонимов по коду
  if (isset($code) && isset($sysname)) {
    $sysname = get_sysname_parent_object($sysname);
    $fld_key = get_key_field($sysname);
    $fld_code = get_code_field($sysname);
    $sql = "select min(t.$fld_key) from $sysname t where t.$fld_code='$code'";
    $res = kometa_query($sql);
    $row = kometa_fetch_row($res);
    if (isset($row[0])) {
//return $row[1];
      return $row[0];
    } else
      return $id_obj;
  } else
    return null;
}

function get_id_object($sysname) {
// получение ИД объекта по системному имени
  if (isset($sysname)) {
    $sysname = strtolower($sysname);
    $sql = "select id_object from mb_object where sysname='$sysname'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      echo $sql . '<br>' . $s_err . '<br>';
      return null;
    }
    $row = kometa_fetch_object($res);
    return $row->id_object;
  } else
    return null;
}

function get_sysname($id_object) {
// получение имя физ. таблицы объекта по коду объекта
  if (isset($id_object)) {
    $sql = "select sysname from mb_object where id_object='$id_object'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      echo $sql . '<br>' . $s_err . '<br>';
      return null;
    }
    $row = kometa_fetch_object($res);
    return $row->sysname;
  } else
    return null;
}

function get_object_descr($id_object) {
// получение имя физ. таблицы объекта по коду объекта
  if (isset($id_object)) {
    $sql = "select short_name from mb_object where id_object='$id_object'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      echo $sql . '<br>' . $s_err . '<br>';
      return null;
    }
    $row = kometa_fetch_object($res);
    return $row->short_name;
  } else
    return null;
}

// полчение имени поля содержащий признак невозможности редактирования записи
function get_readonly_fld($sysname) {
  if (isset($sysname)) {
// получить ключевое поле
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select mb_object_field.fieldname from mb_object_field,mb_object"
            . " where mb_object_field.id_object=mb_object.id_object and mb_object_field.is_field_readonly=1 "
            . " and mb_object.sysname='$sysname' ")) {
// запрос отработал успешно
      if ($row = kometa_fetch_object($res)) {
// записи найдены
        $lst = $row->fieldname;
//        while ($row = kometa_fetch_object($res))
//          $lst.="," . $row->fieldname;
        return $lst;
      } else
        return null;
    } else
      return null;
  } else
    return null;
}

function get_key_field($sysname) {
  if (isset($sysname)) {
// получить ключевое поле
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select mb_object_field.fieldname from mb_object_field,mb_object"
            . " where mb_object_field.id_object=mb_object.id_object and mb_object_field.is_field_key=1 "
            . " and mb_object.sysname='$sysname' ")) {
// запрос отработал успешно
      if ($row = kometa_fetch_object($res)) {
// записи найдены
        $lst = $row->fieldname;
        while ($row = kometa_fetch_object($res))
          $lst.="," . $row->fieldname;
        return $lst;
      } else {
        return null;
      }
    } else {
      return null;
    }
  } else {
    return null;
  }
}

function get_key_field_as_array($sysname) {
  if (isset($sysname)) {
// получить ключевое поле
    $sysname = strtolower($sysname);
    $sql = "select mb_object_field.fieldname from mb_object_field,mb_object"
            . " where mb_object_field.id_object=mb_object.id_object and mb_object_field.is_field_key=1 "
            . " and mb_object.sysname='$sysname' ";
    if ($res = kometa_query($sql)) {
// запрос отработал успешно
      if ($row = kometa_fetch_object($res)) {
// записи найдены
        $obj = array();
        $fld = $row->fieldname;
        //      $obj[$fld] = '';
        array_push($obj, $fld);
        while ($row = kometa_fetch_object($res)) {
          $fld = $row->fieldname;
//          $obj[$fld] = '';
          array_push($obj, $fld);
        }
        return $obj;
      } else
        return null;
    } else
      return null;
  } else
    return null;
}

// получить характеризующие поля по объекту, возвращает список полей через запятую
function get_descr_field($sysname) {
  if (isset($sysname)) {
// получить ключевое поле
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select f.fieldname from mb_object_field as f,mb_object as o where f.is_descr=1 and o.id_object = f.id_object and o.sysname='$sysname' order by f.order_view ")) {
// запрос отработал успешно
      $coma = '';
      while ($row = kometa_fetch_object($res)) {
// записи найдены
        $flds.= "$coma$row->fieldname";
        $coma = ',';
      }
      return $flds;
    } else
      return null;
  } else
    return null;
}

// получить характеризующие поля по объекту, возвращает список полей через запятую в кавычках
function get_descr_field_($sysname) {
  if (isset($sysname)) {
// получить ключевое поле
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select f.fieldname from mb_object_field as f,mb_object as o where f.is_descr=1 and o.id_object = f.id_object and o.sysname='$sysname' order by f.order_view ")) {
// запрос отработал успешно
      $coma = '';
      while ($row = kometa_fetch_object($res)) {
// записи найдены
        $flds.= "$coma '$row->fieldname'";
        $coma = ',';
      }
      return $flds;
    } else
      return null;
  } else
    return null;
}

function get_descr_field_as_array($sysname) {
  if (isset($sysname)) {
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select f.fieldname from mb_object_field as f,mb_object as o where f.is_descr=1 and o.id_object = f.id_object and o.sysname='$sysname' order by f.order_view ")) {
      if ($row = kometa_fetch_object($res)) {
        $obj = array();
        $fld = $row->fieldname;
//        $obj[$fld] = '';
        array_push($obj, $fld);
        while ($row = kometa_fetch_object($res)) {
          $fld = $row->fieldname;
          // $obj[$fld] = '';
          array_push($obj, $fld);
        }
        return $obj;
      } else
        return null;
    } else
      return null;
  } else
    return null;
}

// Пролучить разделитель характеризующих полей из описания объекта
function get_connector($sysname) {
  $sql = "select connector from mb_object where sysname='$sysname'";
  $res = kometa_query($sql);
  if (($row = kometa_fetch_object($res)) && isset($row->connector))
    return $row->connector;
  else
    return ' ';
}

// получить список значений характеризующих полей словаря по id
function get_descr_spr_object($sysname, $id) {
  $descr = get_descr_field($sysname);
  $key = get_key_field($sysname);
  $connector = get_connector($sysname);
  $sql = "SELECT $descr from $sysname where $key=$id";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  $s = get_descr_object($descr, $row, $connector);
  return $s;
}

// объеденить характеризующие поля записи объекта
function get_descr_object($d_flds, $row, $connector) {
  $a = explode(',', str_replace(' ', '', $d_flds));
  $s = '';
  foreach ($a as $key => $value) {
    if ($s == '')
      $s.=$row->$value;
    else
      $s.=$connector . $row->$value;
  }
  return $s;
}

// получить характеризующие поля по объекту, возвращает массив
function get_array_descr_fields($sysname) {
  $a_descr = array();
  if (isset($sysname)) {
// получить ключевое поле
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select f.fieldname from mb_object_field as f,mb_object as o where f.is_descr=1 and o.id_object = f.id_object and o.sysname='$sysname' order by f.order_view ")) {
// запрос отработал успешно
      while ($row = kometa_fetch_object($res)) {
// записи найдены
        array_push($a_descr, $row->fieldname);
      }
      return $a_descr;
    } else
      return null;
  } else
    return null;
}

function get_code_field($sysname) {
  if (isset($sysname)) {
// получить кодовое поле
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select mb_object_field.fieldname from mb_object_field,mb_object"
            . " where mb_object_field.id_object=mb_object.id_object and mb_object_field.is_field_code=1 "
            . " and mb_object.sysname='$sysname' ")) {
// запрос отработал успешно
      if ($row = kometa_fetch_object($res)) {
// записи найдены
        $lst = $row->fieldname;
//        while ($row = kometa_fetch_object($res))
//          $lst.="," . $row->fieldname;
        return $lst;
      } else
        return null;
    } else
      return null;
  } else
    return null;
}

// получить имя поля истории  (вариант для базы mpt)
function get_history_field($sysname) {
  if (isset($sysname)) {
// получить кодовое поле
    $sysname = strtolower($sysname);
    if ($res = kometa_query("select mb_object_field.fieldname from mb_object_field,mb_object"
            . " where mb_object_field.id_object=mb_object.id_object and mb_object_field.is_field_history=1 "
            . " and mb_object.sysname='$sysname' ")) {
// запрос отработал успешно
      if ($row = kometa_fetch_object($res)) {
// записи найдены
        $lst = $row->fieldname;
        return $lst;
      } else
        return null;
    } else
      return null;
  } else
    return null;
}

// получить id редактируемого объекта по ид класса объекта
function get_id_edit_object($id) {
  if (isset($id)) {
// получить кодовое поле
    if ($res = kometa_query("select case when id_object_type=1 then id_object when id_object_type=2 then id_edit_object end as id_edit_object from mb_object where id_object='$id'")) {
// запрос отработал успешно
      if ($row = kometa_fetch_object($res)) {
// записи найдены
        return $row->id_edit_object;
      } else
        return null;
    } else
      return null;
  } else
    return null;
}

// получитть имя поля по id
function Get_FieldNameBy_Id_Field($id_field) {
  if (isset($id_field)) {
    if ($res = kometa_query("select fieldname from mb_object_field where id_field='$id_field' ")) {
// запрос отработал успешно
      if ($row = kometa_fetch_object($res)) {
// записи найдены
        return $row->fieldname;
      } else
        return null;
    } else
      return null;
  } else
    return null;
}

function get_id_spr_by_code($sysname, $code) {
// получение ИД объекта справочника по коду
// опеределяем ключевое поле
  if (isset($sysname) && isset($code) && ($code != '') && ($sysname != '')) {
    $sysname = strtolower($sysname);
    $fld_code = get_code_field($sysname);
    $fld_key = get_key_field($sysname);
    $fld_history = get_history_field($sysname);
    $s_h = '';
    if (isset($fld_history))
      $s_h = "and $fld_history=0";

    $code = my_escape_string($code);
    $sql = "select $fld_key as id from $sysname where $fld_code=$code $s_h";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      echo $sql . '<br>' . $s_err . '<br>';
      return null;
    }
    $row = kometa_fetch_object($res);
    return $row->id;
  } else
    return null;
}

//получить значение характеризующего поля по id
function get_value_descr_by_id($sysname, $id_obj) {
  if (isset($sysname) && isset($id_obj)) {

    $sysname = strtolower($sysname);
    $fld_descr = get_descr_field($sysname);
    $fld_key = get_key_field($sysname);

    $sql = "select $fld_descr as val from $sysname where $fld_key='$id_obj'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      echo $sql . '<br>' . $s_err . '<br>';
      return null;
    }
    $row = kometa_fetch_object($res);
    return $row->val;
  } else
    return null;
}

// converts pure string into a trimmed keyed array
function string2KeyedArray($string, $delimiter = ';', $kv = '=>') {
  if ($a = explode($delimiter, $string)) { // create parts
    foreach ($a as $s) { // each part
      if ($s) {
        if ($pos = strpos($s, $kv)) { // key/value delimiter
          $ka[trim(substr($s, 0, $pos))] = trim(substr($s, $pos + strlen($kv)));
        } else { // key delimiter not found
          $ka[] = trim($s);
        }
      }
    }
    return $ka;
  }
}

// string2KeyedArray
//получение GUID в стандартах Микрософта
function create_guid($namespace = '') {
  static $guid = '';
  $uid = uniqid("", true);
  $data = $namespace;
  $data .= $_SERVER['REQUEST_TIME'];
  $data .= $_SERVER['HTTP_USER_AGENT'];
  $data .= $_SERVER['LOCAL_ADDR'];
  $data .= $_SERVER['LOCAL_PORT'];
  $data .= $_SERVER['REMOTE_ADDR'];
  $data .= $_SERVER['REMOTE_PORT'];
  $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
  $guid = '{' .
          substr($hash, 0, 8) .
          '-' .
          substr($hash, 8, 4) .
          '-' .
          substr($hash, 12, 4) .
          '-' .
          substr($hash, 16, 4) .
          '-' .
          substr($hash, 20, 12) .
          '}';
  return $guid;
}

function kometa_delete_obj($id_object, $id_obj) {
// опрелляем головной объект
  $sql = "SELECT id_object,id_edit_object,id_object_type FROM mb_object WHERE id_object=$id_object";
  if (($res = kometa_query($sql)) == false)
    return 1;

  $row = kometa_fetch_object($res);
  if (!isset($row))
    return 1;
  if (isset($row->id_edit_object))
    $id_object = $row->id_edit_object;
  else if ($row->id_object_type != 1)
    return 1;
  else
    $id_object = $row->id_object;
// определяем является ли объект из которого удаляем таблицей


  $sysname = get_sysname($id_object);

// определяем какие объекты зависят от удаляемого
  $sql = "SELECT f_p.fieldname AS fieldname_parent, f_c.fieldname AS fieldname_child, o_c.sysname as sysname_child,f_c.id_object as id_object_c "
          . " FROM mb_object_link, mb_object_field f_p, mb_object_field f_c, mb_object as o_c "
          . " WHERE mb_object_link.id_field_parent = f_p.id_field AND mb_object_link.id_field_child = f_c.id_field AND "
          . " f_c.id_object = o_c.id_object and o_c.id_object_type=1  and f_p.id_object=$id_object";
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    return 0;
  }
// перебираем связанные объекты
  while ($row = kometa_fetch_object($res)) {
    $key = get_key_field($row->sysname_child);
    $sql1 = "SELECT  " . $key . " as id_obj_c FROM " . $row->sysname_child . " WHERE " . get_key_field($sysname) . "='$id_obj'";
    $res1 = kometa_query($sql1);
// перебираем и удаляем экземпляры связанного объекта
    while ($row1 = kometa_fetch_object($res1)) {
      $code_error = kometa_delete_obj($row->id_object_c, $row1->id_obj_c);
      if ($code_error != 0)
        return 1;
    }
  }
// а теперь удаляем сам объект
  $sql = "DELETE FROM " . $sysname . " WHERE " . get_key_field($sysname) . "='$id_obj'";
  $res = kometa_query($sql);
  if (isset($s_err) && ($s_err != '')) {
    return 1;
  } else {
    return 0;
  }
}

function exists_from_input($id_object) {
  $id_edit_object = get_id_edit_object($id_object);
  if (!isset($id_edit_object)) {
    return false;
  } else {
    $sql = "select id_form from mb_form where id_object=$id_edit_object";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ((isset($s_err) && ($s_err != '')) || (kometa_num_rows($res) == 0)) {
      return false;
    } else {
      return true;
    }
  }
}

function delete_object($id_edit_object, $ValueKeyFields, $Cascade) {
  $sysname = get_sysname($id_edit_object);
  $code_field = get_code_field($sysname); // кодовое поле

  if (isset($history_field) and ( $Cascade != 'true')) {
    // ведется история переводим текущую запись в архив
    $sql = "update $sysname set $history_field=1 where ";
    $coma = '';
    foreach ($ValueKeyFields as $key => $value) {
      $sql.="$coma $key='$value'";
      $coma = ' and ';
    }
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $f_save_error = "Ошибка перевода записи в архив. $sql  $s_err";
    }
  } else {
    if ($Cascade == 'true') {
      // удаляем те записи, которые ссылаются на текущую
      // ищем объекты которые связаны
      $sql = "select lnk.id_link,oc.sysname,oc.id_object from mb_object_link lnk join mb_object oc on lnk.id_object_child=oc.id_object and oc.id_object_type=1  "
              . "where id_object_parent=$id_edit_object";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $f_save_error = "Ошибка поиска связей  $sql  $s_err";
      }
      while ($row = kometa_fetch_object($res)) {
        $c_sysname = $row->sysname;
        $sql_lnk_fld = "select c_fld.fieldname as c_fieldname,p_fld.fieldname as p_fieldname from mb_object_link_field lnk_fld "
                . " join mb_object_field as c_fld on lnk_fld.id_field_child=c_fld.id_field "
                . " join mb_object_field as p_fld on lnk_fld.id_field_parent=p_fld.id_field"
                . " where id_link=" . $row->id_link;
        $res_lnk_fld = kometa_query($sql_lnk_fld);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $f_save_error = "Ошибка поиска связей  $sql_lnk_fld  $s_err";
        }
        // строим joins
        $sql = "";
        $coma = '';
        while ($row_lnk_fld = kometa_fetch_object($res_lnk_fld)) {
          $ff = $row_lnk_fld->p_fieldname;
          $sql.=$coma . $row_lnk_fld->c_fieldname . '=' . $ValueKeyFields->$ff;
          $coma = ' and ';
        }

        $sql = "select " . get_key_field($c_sysname) . " from $c_sysname where " . $sql;
        $res_link = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $f_save_error = "Ошибка поиска связей  $sql  $s_err";
        }
        while ($row_link = kometa_fetch_object($res_link)) {
          delete_object(get_id_object($c_sysname), $row_link, $Cascade);
        }
      }
    }
    $sql = "delete from $sysname  where ";
    $coma = '';
    foreach ($ValueKeyFields as $key => $value) {
      $sql.="$coma $key='$value'";
      $coma = ' and ';
    }
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $f_save_error = "Ошибка удаления записи в архив. $sql  $s_err";
    }
  }
  return $f_save_error;
}

function get_code_value($sysname, $keyvalue) {
  $keyfld = get_key_field($sysname);
  $codefld = get_code_field($sysname);
  $sql = "select $codefld from $sysname where $keyfld='$keyvalue'";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  return $row->$codefld;
}

function get_link_info($id_link) {
  $sql = "SELECT mb_object_link.id_link, mb_object_link.id_object_child, mb_object_link.full_name,mb_object.sysname FROM mb_object_link join mb_object on mb_object.id_object=mb_object_link.id_object_child where id_link=$id_link";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  return $row;
}

function ProtWriteInput($type_record, $id_xml, $short_msg, $full_msg) {
  $short_msg = my_escape_string($short_msg);
  $sapi = php_sapi_name();
  if ($sapi == 'cli') {
    echo $full_msg . PHP_EOL;
    echo $short_msg . PHP_EOL;
  }
  $full_msg = my_escape_string($full_msg);
  $sql = "INSERT INTO mbi_log(id_xml, id_protocol_type, short_name, full_name)    VALUES ($id_xml, $type_record, $short_msg,$full_msg)";
  kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    echo $sql . '<br>' . $s_err . '<br>';
  }
}

// Получение имени файла по ИД из информационного объекта "Поступающие данные в формате XML"
// функция возвращает поллное имя файла
function get_xml_filename($id_xml) {
  global $import_dir;
  global $ProjectRoot;
  $sql = "select id_xml, id_xsd, id_source, id_status, file_name, dt_input from mbi_xml where id_xml=$id_xml";

  $res = kometa_query($sql);

  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    return false;
  } else {
    $row = kometa_fetch_object($res);
    return ($row->file_name);
  }
}

function set_status_xml_filename($id_xml, $id_status) {
  //1-Не производилась
  //2-Загружено успешно
  //3-Загружено частично
  //4-Не загружено
  // global $sys;
  // global $import_dir;
  $import_ok_dir = $_SESSION['import_ok_dir'];

  $sql = "update mbi_xml set id_status=$id_status where id_xml=$id_xml";

  $res = kometa_query($sql);

  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    return $sql . '<br>' . $s_err . '<br>';
  }

  $sql = "select id_xml, id_xsd, id_source, id_status, file_name, dt_input from mbi_xml where id_xml=$id_xml";


  $res = kometa_query($sql);

  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    return $sql . '<br>' . $s_err . '<br>';
  } else {
    $row = kometa_fetch_object($res);
    $path_parts = pathinfo($row->file_name);
    $import_dir = $path_parts['dirname'] . '/';
    if (($id_status == 2) || ($id_status == 3)) {

      if (!file_exists($import_ok_dir)) {
        if ($import_dir == $_SESSION['import_dir'])
          mkdir($import_ok_dir);
      }

      if (!(file_exists($import_ok_dir . date("Ymd")))) {
        if ($import_dir == $_SESSION['import_dir'])
          mkdir($import_ok_dir . date("Ymd"));
      }

      if ($import_dir == $_SESSION['import_dir']) {
        if (!rename($row->file_name, $import_ok_dir . '/' . date("Ymd") . '/' . $path_parts['filename']))
          return "переименование файла " . $import_dir . $row->file_name . " Завершилось с ошибкой";
      }
//перемещаю CRC файл
      $CRCFile = GetCRC_FileName($row->file_name);
      if ($import_dir == $_SESSION['import_dir']) {
        if ($sys != 1)
          rename($CRCFile, $import_ok_dir . '/' . date("Ymd") . '/' . $CRCFile);
      }
    }
  }
}

function set_bad_file($file_name) {
  global $import_dir;
  global $import_bad_dir;

  if (!file_exists($import_bad_dir)) {
    mkdir($import_bad_dir);
  }

  if (!(file_exists($import_bad_dir . '/' . date("Ymd")))) {
    mkdir($import_bad_dir . date("Ymd"));
  }
  rename($import_dir . $file_name, $import_bad_dir . date("Ymd") . '/' . $file_name);
//перемещаю CRC файл
  $CRCFile = GetCRC_FileName($file_name);
  if (file_exists($CRCFile)) {
    rename($import_dir . $CRCFile, $import_bad_dir . date("Ymd") . '/' . $CRCFile);
  }
}

?>