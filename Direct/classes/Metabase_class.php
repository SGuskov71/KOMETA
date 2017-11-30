<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['LIB'] . 'PHP/PEAR/Table.php');
//require_once($_SESSION['ProjectRoot'] . "sys/common_import.php");
require_once($_SESSION['ProjectRoot'] . "DB2XML/UploadCRC.php");

class Metabase_class {

  function ImportMetaDefinition() {
    global $ID_group_sys;
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    if (file_exists($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini')) {
      $pdm_files = parse_ini_file($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini', true);
      $pdm_files = $pdm_files['PDM'];
      foreach ($pdm_files as $key => $value) {
        set_time_limit(7000);
        updateUserSessionTtl();

        if (!file_exists($value)) {
          $result = new JSON_Result(false, "Файл $value не найден", NULL);
          return $result;
        } else {
          $structureXML[$key] = new DOMDocument('1.0');
          $structureXML[$key]->load($value);
          if ($structureXML[$key]->documentElement == undefined) {
            $result = new JSON_Result(false, "Файл $value имеет неправильный формат", NULL);
            return $result;
          }
        }
      }
    } else {
      $result = new JSON_Result(false, "Файл " . $_SESSION['APP_INI_DIR'] . "'LoadFiles.ini' не найден<br>", NULL);
      return $result;
    }

    $sql = "TRUNCATE TABLE  mb_field_group_operation";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

    $sql = "DELETE FROM  mb_object_link_field";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

    $sql = "DELETE FROM  mb_object_link";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

    $sql = "update  mb_object_field set id_field_style=NULL,id_slv_object=NULL";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

    $sql = "delete from  mb_object_field ";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

    // для началы зачищаем ссылки объектов на пакеты и заодно
    // переводим все объекты в состояние архив. Это надо чтобы не следели формы ввода и другие привязанные объекты
    $sql = "update   mb_object set id_group=null,is_history=1,id_edit_object =NULL ";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }
    // удаляем группы
    $sql = "delete from mb_object_group";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

    $sql = "delete from mb_link_operation_object";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

    $protocol = '';

        set_time_limit(7000);
    foreach ($structureXML as $keyXML => $node) {
// Группы пользователей
      $cnt['Группы пользователей']['Добавлено'] = 0;
      $cnt['Группы пользователей']['Изменено'] = 0;
      $cnt['Группы пользователей']['Ошибок'] = 0;
      foreach ($node->getElementsByTagName('mba_group') AS $domNode) {

        $code = my_escape_string(strtolower($domNode->getElementsByTagName('code')->item(0)->nodeValue));
        $short_name = my_escape_string($domNode->getElementsByTagName('short_name')->item(0)->nodeValue);
        $sql = "SELECT * FROM mba_group where code=$code";
        $res = kometa_query($sql);
        if ($row = kometa_fetch_object($res)) {
          $sql = "UPDATE mba_group set short_name=$short_name where code=$code";
        } else {
          $sql = "INSERT INTO mba_group (code, short_name) VALUES ($code,$short_name )";
        }
        kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Группы пользователей']['Ошибок'] ++;
        }
        if (substr($sql, 0, 1) == 'I')
          $cnt['Группы пользователей']['Добавлено'] ++;
        else
          $cnt['Группы пользователей']['Изменено'] ++;
      }

        set_time_limit(7000);
      // Грузим пользовательские типы данных
      $cnt['Пользовательские типы']['Добавлено'] = 0;
      $cnt['Пользовательские типы']['Изменено'] = 0;
      $cnt['Пользовательские типы']['Ошибок'] = 0;
      foreach ($node->getElementsByTagName('domain') AS $domNode) {

        $code = my_escape_string(strtolower($domNode->getElementsByTagName('code')->item(0)->nodeValue));
        $short_name = my_escape_string($domNode->getElementsByTagName('short_name')->item(0)->nodeValue);
        $input_check_condition = my_escape_string($domNode->getElementsByTagName('input_check_condition')->item(0)->nodeValue);
        $group_operation = my_escape_string($domNode->getElementsByTagName('group_operation')->item(0)->nodeValue);

        $op['flt_opr_eqv'] = $domNode->getElementsByTagName('flt_opr_eqv')->item(0)->nodeValue;
        $op['flt_opr_not_eqv'] = $domNode->getElementsByTagName('flt_opr_not_eqv')->item(0)->nodeValue;
        $op['flt_opr_more'] = $domNode->getElementsByTagName('flt_opr_more')->item(0)->nodeValue;
        $op['flt_opr_more_eqv'] = $domNode->getElementsByTagName('flt_opr_more_eqv')->item(0)->nodeValue;
        $op['flt_opr_less'] = $domNode->getElementsByTagName('flt_opr_less')->item(0)->nodeValue;
        $op['flt_opr_less_eqv'] = $domNode->getElementsByTagName('flt_opr_less_eqv')->item(0)->nodeValue;
        $op['flt_opr_like'] = $domNode->getElementsByTagName('flt_opr_eqv')->item(0)->nodeValue;
        $op['flt_opr_begin'] = $domNode->getElementsByTagName('flt_opr_begin')->item(0)->nodeValue;
        $op['flt_opr_null'] = $domNode->getElementsByTagName('flt_opr_null')->item(0)->nodeValue;
        $op['flt_opr_not_null'] = $domNode->getElementsByTagName('flt_opr_not_null')->item(0)->nodeValue;
        $op['flt_opr_not_like'] = $domNode->getElementsByTagName('flt_opr_not_like')->item(0)->nodeValue;
        $op['flt_opr_not_begin'] = $domNode->getElementsByTagName('flt_opr_not_begin')->item(0)->nodeValue;

        $sql = "select id_field_type, code, short_name, input_check_condition, "
                . " group_operation from mb_field_type where code=$code";
        $res = kometa_query($sql);

        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Пользовательские типы']['Ошибок'] ++;
        }

        if ($row = kometa_fetch_object($res)) {
          $sql = "update mb_field_type set short_name=$short_name,"
                  . "input_check_condition=$input_check_condition,group_operation=$group_operation where code=$code";
          if ((my_escape_string($row->short_name) != $short_name) || (my_escape_string($row->input_check_condition) != $input_check_condition) || (my_escape_string($row->group_operation) != $group_operation)) {
            $protocol.= "!!! <b>Предупреждение</b> тип данных $code отличается от ранее описанного<br>";
          }
        } else
          $sql = "INSERT INTO mb_field_type(code, short_name, input_check_condition,group_operation) "
                  . " VALUES ($code,$short_name,$input_check_condition,$group_operation)";

        kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Пользовательские типы']['Ошибок'] ++;
        } else
        if (substr($sql, 0, 1) == 'I')
          $cnt['Пользовательские типы']['Добавлено'] ++;
        else
          $cnt['Пользовательские типы']['Изменено'] ++;
        // получяем ИД типа
        $sql = "select id_field_type from mb_field_type where code=$code";
        $res = kometa_query($sql);

        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);
        $id_type = $row->id_field_type;

        // здесь id так как при описании поля используется ссылка на домен а не его имя
        foreach ($op as $key => $value) {
          // проверяем есть ли такая операция
          $sql = "SELECT t.id_filter_operation from mb_field_type_filter_operation as t,mbs_filter_operation as o where t.id_filter_operation=o.id_filter_operation and t.id_field_type=$id_type and o.code='$key'";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt_error++;
          }
          if ($row = kometa_fetch_object($res)) {
            // такая запись есть
            if ($op[$key] == '1') {
              // не чего не делаем
            } else {
              // операция существует ноее не должно быть
              $sql = "Delete from mb_field_type_filter_operation where id_field_type=$id_type and id_filter_operation=$row->id_filter_operation";
              $res = kometa_query($sql);

              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $protocol.= $sql . '<br>' . $s_err . '<br>';
                $cnt_error++;
              }
            }
          } else {
            // такой записи нет
            if ($op[$key] == '1') {
              // привязываем операцию к типу
              $sql = "INSERT INTO mb_field_type_filter_operation( id_field_type, id_filter_operation) "
                      . "SELECT $id_type as id_field_type,id_filter_operation from mbs_filter_operation where code='$key'";
              $res = kometa_query($sql);

              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $protocol.= $sql . '<br>' . $s_err . '<br>';
                $cnt_error++;
              }
            } else {
              // не чего не делаем
            }
          }
        }
      }

        set_time_limit(7000);
      // Грузим операции
      $cnt['Операции']['Добавлено'] = 0;
      $cnt['Операции']['Изменено'] = 0;
      $cnt['Операции']['Ошибок'] = 0;
      foreach ($node->getElementsByTagName('object_operation') AS $domNode) {

        $code = my_escape_string(strtolower($domNode->getElementsByTagName('code')->item(0)->nodeValue));
        $short_name = my_escape_string($domNode->getElementsByTagName('short_name')->item(0)->nodeValue);
        $full_name = my_escape_string($domNode->getElementsByTagName('full_name')->item(0)->nodeValue);
        $op_style = my_escape_string(strtolower($domNode->getElementsByTagName('op_style')->item(0)->nodeValue));
        $func_class_name = my_escape_string($domNode->getElementsByTagName('func_class_name')->item(0)->nodeValue);
        $func_name = my_escape_string($domNode->getElementsByTagName('func_name')->item(0)->nodeValue);
        $param_list = my_escape_string($domNode->getElementsByTagName('param_list')->item(0)->nodeValue);
        $is_default_operation = $domNode->getElementsByTagName('is_default_operation')->item(0)->nodeValue;
        $is_available = $domNode->getElementsByTagName('is_available')->item(0)->nodeValue;
        $code_group = my_escape_string($domNode->getElementsByTagName('code_group')->item(0)->nodeValue);

        $sql = "SELECT id_object_operation, code FROM mb_object_operation where code=$code_group";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Операции']['Ошибок'] ++;
        }
        if ($row = kometa_fetch_object($res))
          $id_code_group = my_escape_string($row->id_object_operation);
        else
          $id_code_group = 'NULL';

        $sql = "SELECT id_object_operation, code FROM mb_object_operation where code=$code";
        $res = kometa_query($sql);

        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Операции']['Ошибок'] ++;
        }

        if ($row = kometa_fetch_object($res)) {
          $sql = "UPDATE mb_object_operation  SET short_name=$short_name, full_name=$full_name, "
                  . "func_class_name=$func_class_name,"
                  . "func_name=$func_name,"
                  . "param_list=$param_list,"
                  . "op_style=$op_style, "
                  . "is_default_operation=$is_default_operation,is_available=$is_available"
                  . ",id_parent=$id_code_group  where code=$code";
        } else
          $sql = "INSERT INTO mb_object_operation(code, short_name, full_name, "
                  . " op_style, is_default_operation,is_available,id_parent"
                  . ",func_class_name,func_name,param_list)  "
                  . "VALUES ($code, $short_name, $full_name, "
                  . "$op_style,$is_default_operation,$is_available,$id_code_group"
                  . ",$func_class_name,$func_name,$param_list)";

        kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Операции']['Ошибок'] ++;
        } else
        if (substr($sql, 0, 1) == 'I')
          $cnt['Операции']['Добавлено'] ++;
        else
          $cnt['Операции']['Изменено'] ++;
        // получяем ИД типа
        $sql = "select id_field_type from mb_field_type where code=$code";
        $res = kometa_query($sql);

        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);
        $id_type = $row->id_field_type;
      }

        set_time_limit(7000);
      // Грузим пакеты
      $cnt['Группы объектов']['Добавлено'] = 0;
      $cnt['Группы объектов']['Изменено'] = 0;
      $cnt['Группы объектов']['Ошибок'] = 0;

      foreach ($node->getElementsByTagName('package') AS $grNode) {
        set_time_limit(7000);
        updateUserSessionTtl();

        $parent = $grNode->getElementsByTagName('parent')->item(0)->nodeValue;
        $code = $grNode->getElementsByTagName('code')->item(0)->nodeValue;
        $short_name = $grNode->getElementsByTagName('short_name')->item(0)->nodeValue;
        $full_name = $grNode->getElementsByTagName('full_name')->item(0)->nodeValue;
//        $sort_order = $grNode->getElementsByTagName('sort_order')->item(0)->nodeValue;

        if ((!isset($full_name)) || ($full_name == ''))
          $full_name = $short_name;
        // поиск id_parent
        $sql = "SELECT id_group FROM mb_object_group WHERE code='$parent'";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);
        if ($row) {
          $id_parent = $row->id_group;
        } else
          $id_parent = 'NULL';

        // ищем группу верхнего уровня
        if ($row) {
          $id_gr_parent = $row->id_group;
        } else
          $id_gr_parent = 'NULL';

        $code = my_escape_string($code);
        $short_name = my_escape_string($short_name);
        $full_name = my_escape_string($full_name);

        $sql = "INSERT INTO mb_object_group( short_name,full_name, id_parent,code) VALUES ($short_name,$full_name, $id_parent,$code)";
        kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Группы объектов']['Ошибок'] ++;
        } else {
          $cnt['Группы объектов']['Добавлено'] ++;
        }
      }

      // грузим объекты

        set_time_limit(7000);
      $cnt['Объекты']['Добавлено'] = 0;
      $cnt['Объекты']['Изменено'] = 0;
      $cnt['Объекты']['Ошибок'] = 0;
      foreach ($node->getElementsByTagName('object') AS $domNode) {
        $object_type = my_escape_string($domNode->getElementsByTagName('object_type')->item(0)->nodeValue);
        $sysname = my_escape_string($domNode->getElementsByTagName('sysname')->item(0)->nodeValue);
        $short_name = my_escape_string($domNode->getElementsByTagName('short_name')->item(0)->nodeValue);
        $full_name = my_escape_string($domNode->getElementsByTagName('full_name')->item(0)->nodeValue);
        $group = my_escape_string($domNode->getElementsByTagName('group')->item(0)->nodeValue);
        $add_where = my_escape_string($domNode->getElementsByTagName('add_where')->item(0)->nodeValue);
        $code_help = my_escape_string($domNode->getElementsByTagName('code_help')->item(0)->nodeValue);
        $edit_object = my_escape_string($domNode->getElementsByTagName('edit_object')->item(0)->nodeValue);
        $connector = my_escape_string($domNode->getElementsByTagName('connector')->item(0)->nodeValue);

        $sql = "SELECT id_group FROM mb_object_group WHERE code=$group";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);
        $group = my_escape_string($row->id_group);

        if ($edit_object != 'NULL') {
          $sql = "SELECT id_object FROM mb_object WHERE sysname=$edit_object";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt_error++;
          }
          $row = kometa_fetch_object($res);
          $edit_object = my_escape_string($row->id_object);
        }

        $sql = "SELECT id_object FROM mb_object WHERE sysname=$sysname";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);

        if (isset($row->id_object)) {
          $sql = "UPDATE mb_object SET is_history=0,id_object_type=$object_type,   short_name=$short_name, full_name=$full_name,"
                  . " id_group=$group, id_edit_object=$edit_object, code_help=$code_help,  "
                  . "connector=$connector,add_where=$add_where WHERE sysname=$sysname";
        } else {
          $sql = "INSERT INTO mb_object( id_object_type, sysname, short_name,full_name, id_group, id_edit_object, code_help, connector,add_where)"
                  . " VALUES ($object_type, $sysname, $short_name,$full_name, $group, $edit_object, $code_help, $connector,$add_where)";
        }
        kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Объекты']['Ошибок'] ++;
        } else {
          if (substr($sql, 0, 1) == 'I')
            $cnt['Объекты']['Добавлено'] ++;
          else
            $cnt['Объекты']['Изменено'] ++;
        }
        $sql = "SELECT id_object FROM mb_object WHERE sysname=$sysname";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);
        $id_obj = $row->id_object;
        // формируем доступ для группы администраторов
        $sql = "select id_object,id_group from mba_grant_object where id_object=$id_obj and id_group=$ID_group_sys";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        if (kometa_num_rows($res) == 0) {
          $sql = "INSERT INTO mba_grant_object (id_object,id_group) VALUES($id_obj,$ID_group_sys)";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt_error++;
          }
        }
      }

        set_time_limit(7000);
      $cnt['Поля объектов']['Добавлено'] = 0;
      $cnt['Поля объектов']['Изменено'] = 0;
      $cnt['Поля объектов']['Ошибок'] = 0;

      foreach ($node->getElementsByTagName('field') AS $domNode) {
        $fieldname = my_escape_string($domNode->getElementsByTagName('fieldname')->item(0)->nodeValue);
        $sysname = my_escape_string($domNode->getElementsByTagName('sysname')->item(0)->nodeValue);
        $short_name = my_escape_string($domNode->getElementsByTagName('short_name')->item(0)->nodeValue);
        $full_name = my_escape_string($domNode->getElementsByTagName('full_name')->item(0)->nodeValue);
        $domain = my_escape_string($domNode->getElementsByTagName('field_type')->item(0)->nodeValue);
        $multi_value = my_escape_string($domNode->getElementsByTagName('multi_value')->item(0)->nodeValue);
        $mandatory = my_escape_string($domNode->getElementsByTagName('mandatory')->item(0)->nodeValue);
        $id_filter_type = my_escape_string($domNode->getElementsByTagName('id_filter_type')->item(0)->nodeValue);
        $id_field_dtype = $domNode->getElementsByTagName('id_field_dtype')->item(0)->nodeValue;
        $is_visibility = my_escape_string($domNode->getElementsByTagName('is_visibility')->item(0)->nodeValue);
        $slv_object = my_escape_string($domNode->getElementsByTagName('slv_object')->item(0)->nodeValue);
        $o_fld_name_id['is_field_key'] = $domNode->getElementsByTagName('field_key')->item(0)->nodeValue;
        $o_fld_name_id['is_field_code'] = $domNode->getElementsByTagName('field_code')->item(0)->nodeValue;
        $o_fld_name_id['is_descr'] = $domNode->getElementsByTagName('descr')->item(0)->nodeValue;
        $o_fld_name_id['is_field_parent'] = $domNode->getElementsByTagName('field_parent')->item(0)->nodeValue;
        $o_fld_name_id['is_field_history'] = $domNode->getElementsByTagName('field_history')->item(0)->nodeValue;
        $o_fld_name_id['is_field_readonly'] = $domNode->getElementsByTagName('field_readonly')->item(0)->nodeValue;
        $filter_use = my_escape_string($domNode->getElementsByTagName('filter_use')->item(0)->nodeValue);
        $order_view = my_escape_string($domNode->getElementsByTagName('order_view')->item(0)->nodeValue);
        $display_mask = my_escape_string($domNode->getElementsByTagName('display_mask')->item(0)->nodeValue);

        if (!isset($display_mask))
          $display_mask='';
        
        if ($order_view == 'NULL')
          $order_view = '9999';
        if ($mandatory == 'NULL')
          $mandatory = '0';

        $sql = "SELECT id_object FROM mb_object WHERE sysname=$sysname";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);
        $id_object = my_escape_string($row->id_object);

        if ($slv_object != 'NULL') {
          $sql = "SELECT id_object FROM mb_object WHERE sysname=$slv_object";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt_error++;
          }
          $row = kometa_fetch_object($res);
          $slv_object = my_escape_string($row->id_object);
        }
        // получяем ИД типа
        $sql = "select id_field_type from mb_field_type where code=$domain";
        $res = kometa_query($sql);

        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt_error++;
        }
        $row = kometa_fetch_object($res);
        $id_type = $row->id_field_type;


        $sql = "INSERT INTO mb_object_field(id_object, id_field_type, id_field_dtype, id_filter_type,  fieldname, short_name, full_name, is_visibility, "
                . "order_view, is_filter_use, mandatory,id_slv_object,multi_value,display_mask";
        foreach ($o_fld_name_id as $key => $value) {
          $sql.=", " . $key;
        }
        $sql.= ") VALUES ($id_object, $id_type, $id_field_dtype, $id_filter_type,  $fieldname, $short_name, $full_name, $is_visibility, "
                . "$order_view,  $filter_use, $mandatory,$slv_object,$multi_value,$display_mask";
        foreach ($o_fld_name_id as $key => $value) {
          $sql.="," . $value;
        }
        $sql.=")";


        kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Поля объектов']['Ошибок'] ++;
        } else {
          if (substr($sql, 0, 1) == 'I')
            $cnt['Поля объектов']['Добавлено'] ++;
          else
            $cnt['Поля объектов']['Изменено'] ++;
        }
      }

        set_time_limit(7000);
      $cnt['Изменено стилей полей объектов']['Добавлено'] = 0;
      $cnt['Изменено стилей полей объектов']['Изменено'] = 0;
      $cnt['Изменено стилей полей объектов']['Ошибок'] = 0;

      foreach ($node->getElementsByTagName('field_style') AS $domNode) {
        $sysname = $domNode->getElementsByTagName('sysname')->item(0)->nodeValue;
        $fieldname = $domNode->getElementsByTagName('fieldname')->item(0)->nodeValue;
        $fieldstyle = $domNode->getElementsByTagName('style')->item(0)->nodeValue;
        $id_field = get_id_field($sysname, $fieldname);
        $id_fieldstyle = get_id_field($sysname, $fieldstyle);
        if (isset($id_field) && isset($id_fieldstyle)) {
          $sql = "UPDATE mb_object_field set id_field_style=$id_fieldstyle where id_field=$id_field";
          kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt['Изменено стилей полей объектов']['Ошибок'] ++;
          } else {
            if (substr($sql, 0, 1) == 'I')
              $cnt['Изменено стилей полей объектов']['Добавлено'] ++;
            else
              $cnt['Изменено стилей полей объектов']['Изменено'] ++;
          }
        }
      }


        set_time_limit(7000);
      $cnt['Групповые операции над полями объектов']['Добавлено'] = 0;

      $cnt['Групповые операции над полями объектов']['Изменено'] = 0;
      $cnt['Групповые операции над полями объектов']['Ошибок'] = 0;

      foreach ($node->getElementsByTagName('fields_group_operation') AS $node1)
        foreach ($node1->getElementsByTagName('group_operation') AS $domNode) {
          $sysname = $domNode->getElementsByTagName('sysname')->item(0)->nodeValue;
          $fieldname = $domNode->getElementsByTagName('fieldname')->item(0)->nodeValue;
          $id_group_operation = $domNode->getElementsByTagName('id_group_operation')->item(0)->nodeValue;
          $id_field = get_id_field($sysname, $fieldname);

          if (isset($id_field) && isset($id_group_operation)) {
            $sql = "INSERT INTO mb_field_group_operation (id_field,id_group_operation) VALUES ($id_field,$id_group_operation)";
            $res = kometa_query($sql);
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              $protocol.= $sql . '<br>' . $s_err . '<br>';
              $cnt['Групповые операции над полями объектов']['Ошибок'] ++;
            } else {
              if (substr($sql, 0, 1) == 'I')
                $cnt['Групповые операции над полями объектов']['Добавлено'] ++;
              else
                $cnt['Групповые операции над полями объектов']['Изменено'] ++;
            }
          }
        }

      // доступы   на объекты
      $cnt['Доступы на объекты']['Добавлено'] = 0;

      $cnt['Доступы на объекты']['Изменено'] = 0;
      $cnt['Доступы на объекты']['Ошибок'] = 0;

      foreach ($node->getElementsByTagName('link_object_permission') AS $domNode) {
        $sysname = $domNode->getElementsByTagName('sysname')->item(0)->nodeValue;
        $group_code = $domNode->getElementsByTagName('group_code')->item(0)->nodeValue;

        $sql = "SELECT id_object FROM mb_object WHERE sysname='$sysname'";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
          $cnt['Доступы на объекты']['Ошибок'] ++;
        }
        $row = kometa_fetch_object($res);
        $id_obj = $row->id_object;

        // ищем группу
        $ID_group_grant = get_id_spr_by_code('mba_group', $group_code);
        if (isset($ID_group_grant)) {
          // формируем доступ для группы администраторов
          $sql = "select id_object,id_group from mba_grant_object where id_object=$id_obj and id_group=$ID_group_grant";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt['Доступы на объекты']['Ошибок'] ++;
          }
          if (kometa_num_rows($res) == 0) {
            $sql = "INSERT INTO mba_grant_object (id_object,id_group) VALUES($id_obj,$ID_group_grant)";
            $res = kometa_query($sql);
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              $protocol.= $sql . '<br>' . $s_err . '<br>';
              $cnt['Доступы на объекты']['Ошибок'] ++;
            } else {
              $cnt['Доступы на объекты']['Добавлено'] ++;
            }
          }
        }
      }


      //  
      $cnt['Операции над объектами']['Добавлено'] = 0;
      $cnt['Операции над объектами']['Изменено'] = 0;
      $cnt['Операции над объектами']['Ошибок'] = 0;
        set_time_limit(7000);
      foreach ($node->getElementsByTagName('link_object_operation') AS $domNode) {
        $sysname = $domNode->getElementsByTagName('sysname')->item(0)->nodeValue;
        $object_operation_code = strtolower($domNode->getElementsByTagName('object_operation_code')->item(0)->nodeValue);
        $btn_number = $domNode->getElementsByTagName('btn_number')->item(0)->nodeValue;
        $id_object_operation = get_id_spr_by_code('mb_object_operation', $object_operation_code);
        $id_object = get_id_object($sysname);

        if (isset($id_object) && isset($id_object_operation)) {
          $sql = "INSERT INTO mb_link_operation_object(id_object, id_object_operation, btn_number) "
                  . "VALUES ($id_object, $id_object_operation, $btn_number)";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt['Операции над объектами']['Ошибок'] ++;
          } else {
            if (substr($sql, 0, 1) == 'I')
              $cnt['Операции над объектами']['Добавлено'] ++;
            else
              $cnt['Операции над объектами']['Изменено'] ++;
          }
        }
        else {
          $protocol.= "операция $object_operation_code не найдена<br>";
          $cnt['Операции над объектами']['Ошибок'] ++;
        }
      }

        set_time_limit(7000);
      $cnt['Связи между объектами']['Добавлено'] = 0;

      $cnt['Связи между объектами']['Изменено'] = 0;
      $cnt['Связи между объектами']['Ошибок'] = 0;
      foreach ($node->getElementsByTagName('reference') AS $domNode) {
        $code = $domNode->getElementsByTagName('code')->item(0)->nodeValue;
        $short_name = $domNode->getElementsByTagName('short_name')->item(0)->nodeValue;
        $full_name = $domNode->getElementsByTagName('full_name')->item(0)->nodeValue;
        $sort_order = $domNode->getElementsByTagName('sort_order')->item(0)->nodeValue;
        $object_parent = $domNode->getElementsByTagName('object_parent')->item(0)->nodeValue;
        $object_child = $domNode->getElementsByTagName('object_child')->item(0)->nodeValue;

        if ((!isset($short_name) || ($short_name == '') ) && isset($full_name) && ($full_name != ''))
          $short_name = $full_name;

        $id_object_parent = get_id_object($object_parent);
        $id_object_child = get_id_object($object_child);


        if (isset($id_object_parent) && isset($id_object_child)) {
          $code1 = my_escape_string($code);
          $short_name = my_escape_string($short_name);
          $full_name = my_escape_string($full_name);

          $sql = "INSERT INTO mb_object_link(code,id_object_parent, id_object_child, short_name, full_name, sort_order) "
                  . " VALUES ($code1,$id_object_parent, $id_object_child, $short_name, $full_name, $sort_order)";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
            $cnt['Связи между объектами']['Ошибок'] ++;
          } else {
            if (substr($sql, 0, 1) == 'I')
              $cnt['Связи между объектами']['Добавлено'] ++;
            else
              $cnt['Связи между объектами']['Изменено'] ++;
          }
          $id_link = get_id_spr_by_code('mb_object_link', $code);
          foreach ($domNode->getElementsByTagName('join') AS $join) {
            $column_parent = $domNode->getElementsByTagName('column_parent')->item(0)->nodeValue;
            $column_child = $domNode->getElementsByTagName('column_child')->item(0)->nodeValue;
            $id_field_parent = get_id_field($object_parent, $column_parent);
            $id_field_child = get_id_field($object_child, $column_child);
            if (isset($id_field_parent) && isset($id_field_child)) {
              $sql = "INSERT INTO mb_object_link_field(id_link, id_field_parent, id_field_child)   VALUES ($id_link, $id_field_parent, $id_field_child)";
              $res = kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $protocol.= $sql . '<br>' . $s_err . '<br>';
                $cnt_error++;
              }
            } else {
              if (!isset($id_field_parent))
                $protocol.= "Не найдено поле $column_parent в объекте $object_parent<br>";
              if (!isset($id_field_child))
                $protocol.= "Не найдено поле $column_child в объекте $object_child<br>";
            }
          }
        }
      }
      $protocol.= $pdm_files[$keyXML] . '<br>';
      $table = new HTML_Table();
      $table->setAttribute('border', 1);
      $table->setAttribute('width', '100%');
      $table->setAttribute('align', 'center');
      $table->setAttribute('cellspacing', 0);
      $table->setAttribute('cellpadding', 0);
      $i = 1;
      foreach ($cnt as $key => $value) {
        $table->setCellContents($i, 0, $key);
        $j = 1;
        foreach ($value as $key1 => $value1) {
          $table->setCellContents(0, $j, $key1);
          $table->setCellContents($i, $j, $value1);

          $j++;
        }
        $i++;
      }
      $protocol.= $table->toHtml() . '<br>';
    }
    $resultXSD = $this->ImportXSD();
    $protocol.='<br>' . $resultXSD->result;
    $resultTask = $this->ImportSysTask();
    $protocol.='<br>' . $resultTask->msg;
    if (isset($resultTask->result))
      $protocol.='<br>' . $resultTask->result;
    $resultTask = $this->ImportTask();
    $protocol.='<br>' . $resultTask->msg;
    if (isset($resultTask->result))
      $protocol.='<br>' . $resultTask->result;
    if (isset($resultTask->result))
      $protocol.='<br>' . $resultTask->result;
    $result = new JSON_Result(true, 'Метаописание загружено', $protocol);
    return $result;
  }

  function ImportXSD() {
        set_time_limit(7000);
    $protocol = '';
    if (!file_exists($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini')) {
      $result = new JSON_Result(false, 'Файл ' . $_SESSION['APP_INI_DIR'] . 'LoadFiles.ini' . ' с описанием системных загружаемых файлов не найден', NULL);
      return $result;
    }

    $files = parse_ini_file($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini', true);
    $files = $files['MBI_SCHEMAS'];
    foreach ($files as $key => $value) {
      $protocol.= "Файл $value <br>";
      $filename = $value;
      $sysname = 'mbi_schema';
      global $cntins; // сколько добавлено
      global $cntupd; // сколько заменено

      if (file_exists($filename)) {
        $structureXML = new DOMDocument('1.0', 'UTF-8');

        $structureXML->load($filename);
        $node = $structureXML->documentElement;

        foreach ($node->getElementsByTagName('IN_XSD') AS $domNode) {
          $ID_XSD = $domNode->getElementsByTagName('ID_XSD')->item(0)->nodeValue;
          $xsd_name = my_escape_string(strtolower($domNode->getElementsByTagName('XSDNAME')->item(0)->nodeValue));
          $full_name = my_escape_string($domNode->getElementsByTagName('DEFINITION')->item(0)->nodeValue);
          $XSD = $domNode->getElementsByTagName('XSD')->item(0)->nodeValue;
          $ID_PERIODICITY = my_escape_string($domNode->getElementsByTagName('ID_PERIODICITY')->item(0)->nodeValue);
          $loader_name = my_escape_string($domNode->getElementsByTagName('LOADER')->item(0)->nodeValue);
          $SYSNAME = $domNode->getElementsByTagName('SYSNAME')->item(0)->nodeValue;
          $object_row_tag = my_escape_string($domNode->getElementsByTagName('OBJECT_ROW_TAG')->item(0)->nodeValue);
          $PRIORITY = my_escape_string($domNode->getElementsByTagName('PRIORITY')->item(0)->nodeValue);
          $id_data_type = my_escape_string($domNode->getElementsByTagName('ID_VID_INPUT')->item(0)->nodeValue);
          $ID_SOURCE = my_escape_string($domNode->getElementsByTagName('ID_SOURCE')->item(0)->nodeValue);
          $SUBSTITUTION = my_escape_string($domNode->getElementsByTagName('SUBSTITUTION')->item(0)->nodeValue);

          if (!isset($full_name) || ($full_name == 'NULL'))
            $full_name = "'$SYSNAME'";

          $id_object = get_id_object($SYSNAME);

          $id_object = my_escape_string($id_object);
          if ((!isset($XSD)) || (trim($XSD) === ''))
            $XSD = 'null';
          else {
            $XSD = kometa_escape_string($XSD);
            $XSD = "'{$XSD}'";
          }

          $res = kometa_query("select count(*) as cnt from mbi_schema where id_xsd=$ID_XSD");
          $row = kometa_fetch_object($res);
          if ($row->cnt > 0) {
            if ($xsd_name == 'NULL')
              $sql = "DELETE FROM mbi_log WHERE id_xml in (SELECT id_xml FROM mbi_xml where id_xsd=$ID_XSD);DELETE FROM mbi_xml where id_xsd=$ID_XSD;DELETE from mbi_schema  where id_xsd=$ID_XSD";
            else
              $sql = "update mbi_schema set full_name=$full_name, xsd=$XSD, id_periodicity=$ID_PERIODICITY, id_object=$id_object,object_row_tag=$object_row_tag,  " .
                      " id_data_type=$id_data_type,code=$xsd_name, loader_name=$loader_name, priority=$PRIORITY,ID_SOURCE=$ID_SOURCE,SUBSTITUTION=$SUBSTITUTION where id_xsd=$ID_XSD";
            kometa_query($sql);

            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              
            } else {
              if (substr($sql, 0, 1) == 'I')
                $cntdel[$sysname] = $cntdel[$sysname] + 1;
              else if (substr($sql, 0, 1) == 'I')
                $cntins[$sysname] = $cntins[$sysname] + 1;
              else
                $cntupd[$sysname] = $cntupd[$sysname] + 1;
            }
          }
          else if (($xsd_name != 'NULL') && ($full_name != 'NULL')) {
            $sql = "INSERT INTO mbi_schema (id_xsd, full_name, xsd, id_periodicity," .
                    " id_data_type,code,loader_name,priority,ID_SOURCE,SUBSTITUTION,object_row_tag,id_object)" .
                    " VALUES($ID_XSD, $full_name, $XSD, $ID_PERIODICITY," .
                    " $id_data_type,$xsd_name,$loader_name,$PRIORITY,$ID_SOURCE,$SUBSTITUTION,$object_row_tag,$id_object)";

            kometa_query($sql);
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              if ($sapi != 'cli')
                $protocol.= ( $sql . '<br>' . $s_err . '<br>');
            } else {
              if (substr($sql, 0, 1) == 'I')
                $cntins[$sysname] = $cntins[$sysname] + 1;
              else
                $cntupd[$sysname] = $cntupd[$sysname] + 1;
            }
          }
        }
      }
      else {
        $protocol.= "Файл $filename для загрузки схем импорта не найден<br>";
      }
    }
    $table = new HTML_Table();
    $table->setAttribute('border', 1);
    $table->setAttribute('width', '100%');
    $table->setAttribute('align', 'center');
    $table->setAttribute('cellspacing', 0);
    $table->setAttribute('cellpadding', 0);

    $table->setCellContents(0, 0, 'Наименование справочника');
    $table->setCellContents(0, 1, 'Вего терминов');
    $table->setCellContents(0, 2, 'Загружено новых');
    $table->setCellContents(0, 3, 'Заменено');
    $table->setCellContents(0, 4, 'Удалено');

    $res = kometa_query("Select count(*) as cnt from mbi_schema");

    $row = kometa_fetch_object($res);
    $table->setCellContents(1, 0, 'Схемы загрузки');
    $table->setCellContents(1, 1, $row->cnt);
    $table->setCellContents(1, 2, $cntins['mbi_schema']);
    $table->setCellContents(1, 3, $cntupd['mbi_schema']);
    $table->setCellContents(1, 4, $cntdel['mbi_schema']);

    $hrAttrs = array('bgcolor' => 'silver');
    $table->setRowAttributes(0, $hrAttrs, true);
    $hrAttrs = array('bgcolor' => 'silver');
    $table->setColAttributes(0, $hrAttrs, true);

    $protocol.= $table->toHtml() . '<br>';
    $result = new JSON_Result(true, 'Схема входной информации загружены', $protocol);
    return $result;
  }

  function ImportSysTask() {
    global $ID_User_sys; // ИД администратора системы по умолчанию
    global $ID_group_sys; // ИД группы админисраторов

    $protocol = '';

    function working_sysTask($node, $parent) {
      global $nc;
      global $ID_User_sys; // ИД администратора системы по умолчанию
      global $ID_group_sys; // ИД группы админисраторов
      global $protocol;

      $nodes = $node->childNodes;
      if (isSet($nodes)) {
        foreach ($nodes AS $domNode) {
          $s = $domNode->tagName;
          if (is_a($domNode, 'DOMElement')) {
            if ($domNode->tagName == "Task") {
              // Выбираем параметры задачи
              $fname = '';
              $sname = '';
              $code = '';
              $exec = '';
              $ord = 1000;
              $style = '';
              $img = '';
              $func_class_name = '';
              $func_name = '';
              $param_list = '';

              foreach ($domNode->childNodes AS $aNode) {
                $s = $aNode->nodeName;
                if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'FullName')) {
                  $fname = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'ShortName')) {
                  $sname = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'Exec')) {
                  $exec = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'func_class_name')) {
                  $func_class_name = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'func_name')) {
                  $func_name = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'param_list')) {
                  $param_list = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'Order')) {
                  $ord = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'Style')) {
                  $style = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'code_help')) {
                  $code_help = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'code')) {
                  $code = $aNode->nodeValue;
                }
              }
              // сохраняем задачу в базу

              $sparent = my_escape_string($parent);

              $func_class_name = my_escape_string($func_class_name);
              $func_name = my_escape_string($func_name);
              $param_list = my_escape_string($param_list);

              if (!isset($ord) || ($ord == ''))
                $ord = 1000;

              $sname = my_escape_string(mb_substr($sname, 0, 250, 'utf-8'));
              $fname = my_escape_string(mb_substr($fname, 0, 512, 'utf-8'));


              $sql = "INSERT INTO mb_task (id_task,code, id_parent, short_name, full_name,ord,style,is_sys,code_help,func_class_name,func_name,param_list) "
                      . "VALUES ($nc,'$code',$sparent,$sname,$fname,$ord,'$style',1,'$code_help',$func_class_name,$func_name,$param_list)";

              kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $protocol.= $sql . '<br>' . $s_err . '<br>';
              }
              $sql = "insert into mba_grant_task (id_group,id_task)  values ($ID_group_sys,$nc)";
              kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $protocol.= $sql . '<br>' . $s_err . '<br>';
              }

              $nc = $nc + 1;

              working_sysTask($domNode, $nc - 1);
            }
          }
        }
      }
    }

    if (!file_exists($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini')) {
      $result = new JSON_Result(false, 'Файл ' . $_SESSION['APP_INI_DIR'] . 'LoadFiles.ini' . ' с описанием системных загружаемых файлов не найден', NULL);
      return $result;
    }

    $files = parse_ini_file($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini', true);
    $files = $files['TASK'];
    if (!file_exists($files['systask'])) {
      $result = new JSON_Result(false, 'Файл ' . $files['systask'] . ' для формирования системного меню не найден<br>', NULL);
      return $result;
    }

    global $nc;
// удаляем все задачи кроме загруженых при создании БД
    $sql = "delete from mba_grant_task where exists(SELECT * from mb_task where mba_grant_task.id_task=mb_task.id_task and is_sys=1)";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }
    $sql = "delete from mb_task where is_sys=1";
    kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
      return $result;
    }

// определяем максимальный существующий ключ задачи
    $res = kometa_query("select max(id_task) as cnt from mb_task");
    $row = kometa_fetch_object($res);
    $nc = $row->cnt + 1;

    $path_parts = pathinfo(__FILE__);
    $workdir = $path_parts['dirname'];
    if (isset($workdir)) {
      chdir($workdir);
    }
// грузим аналитические задачи

    $structureXML = new DOMDocument('1.0', 'UTF-8');
    $structureXML->load($files['systask']);
    $curNode1 = $structureXML->documentElement;


    working_sysTask($curNode1, '');
    if ($protocol == '')
      $result = new JSON_Result(true, 'Системное меню успешно загружено', $protocol);
    else
      $result = new JSON_Result(true, 'В ходе загрузки системного меню возникли ошибки', $protocol);
    return $result;
  }

  function ImportTask() {
    global $ID_User_sys; // ИД администратора системы по умолчанию
    global $ID_group_sys; // ИД группы админисраторов
    global $protocol;

    $protocol = '';

    function working_Task($node, $parent) {
      global $nc;
      global $ID_group_sys;

      $nodes = $node->childNodes;
      if (isSet($nodes)) {
        foreach ($nodes AS $domNode) {
          $s = $domNode->tagName;
          if (is_a($domNode, 'DOMElement')) {
            if ($domNode->tagName == "Task") {
              // Выбираем параметры задачи
              $fname = '';
              $sname = '';
              $exec = '';
              $ord = 1000;
              $style = '';
              $img = '';

              $func_class_name = '';
              $func_name = '';
              $param_list = '';


              foreach ($domNode->childNodes AS $aNode) {
                $s = $aNode->nodeName;
                if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'FullName')) {
                  $fname = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'ShortName')) {
                  $sname = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'Exec')) {
                  $exec = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'func_class_name')) {
                  $func_class_name = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'func_name')) {
                  $func_name = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'param_list')) {
                  $param_list = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'Order')) {
                  $ord = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'Style')) {
                  $style = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'code_help')) {
                  $code_help = $aNode->nodeValue;
                } else if (is_a($aNode, 'DOMElement') && ($aNode->nodeName == 'code')) {
                  $code = $aNode->nodeValue;
                }
              }
              // сохраняем задачу в базу

              $sparent = my_escape_string($parent);
              $func_class_name = my_escape_string($func_class_name);
              $func_name = my_escape_string($func_name);
              $param_list = my_escape_string($param_list);

              if (!isset($ord) || ($ord == ''))
                $ord = 1000;

              $sname = my_escape_string(mb_substr($sname, 0, 250, 'utf-8'));
              $fname = my_escape_string(mb_substr($fname, 0, 512, 'utf-8'));


              $sql = "INSERT INTO mb_task (id_task,code, id_parent, short_name, full_name, ord,style,is_sys,code_help,func_class_name,func_name,param_list) "
                      . "VALUES ($nc,'$code',$sparent,$sname,$fname,$ord,'$style',0,'$code_help',$func_class_name,$func_name,$param_list)";

              kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $protocol.= $sql . '<br>' . $s_err . '<br>';
              }
              // добавляем в группу администраторов
              $sql = "insert into mba_grant_task (id_group,id_task)  values ($ID_group_sys,$nc)";
              kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $protocol.= $sql . '<br>' . $s_err . '<br>';
              }

              $nc = $nc + 1;

              working_Task($domNode, $nc - 1);
            }
          }
        }
      }
    }

    if (!file_exists($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini')) {
      $result = new JSON_Result(false, 'Файл ' . $_SESSION['APP_INI_DIR'] . 'LoadFiles.ini' . ' с описанием системных загружаемых файлов не найден', NULL);
      return $result;
    }

    global $nc;
// удаляем все задачи кроме загруженых при создании БД
    kometa_query("delete from mba_grant_task where exists(SELECT * from mb_task where mba_grant_task.id_task=mb_task.id_task and is_sys=0)");
    kometa_query("delete from mb_task where is_sys=0");

// определяем максимальный существующий ключ задачи
    $res = kometa_query("select max(id_task) as cnt from mb_task");
    $row = kometa_fetch_object($res);
    $nc = $row->cnt + 1;

// грузим задачи
    $files = parse_ini_file($_SESSION['APP_INI_DIR'] . 'LoadFiles.ini', true);
    $files = $files['TASK'];
    if (!file_exists($files['task'])) {
      $result = new JSON_Result(false, 'Файл ' . $files['task'] . ' для формирования пользовательского меню не найден<br>', NULL);
      return $result;
    }
//    echo "загрузка файла пользовательского меню " . $files['task'];
    $structureXML = new DOMDocument('1.0', 'UTF-8');
    $structureXML->load($files['task']);
    $curNode1 = $structureXML->documentElement;
    foreach ($curNode1->getElementsByTagName('Menu') AS $curNode1) {
      break;
    }

    working_Task($curNode1, '');
    foreach ($curNode1->getElementsByTagName('grant_task') AS $domNode) {
      $code_task = $domNode->getElementsByTagName('code_task')->item(0)->nodeValue;
      $group_code = $domNode->getElementsByTagName('code_group')->item(0)->nodeValue;

      $sql = "SELECT id_task FROM mb_task WHERE code='$code_task'";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        $protocol.= $sql . '<br>' . $s_err . '<br>';
      }
      $row = kometa_fetch_object($res);
      $id_task = $row->id_task;

      // ищем группу
      $ID_group_grant = get_id_spr_by_code('mba_group', $group_code);
      if (isset($ID_group_grant)) {
        // формируем доступ для группы администраторов
        $sql = "select id_grant_task from mba_grant_task where id_task=$id_task and id_group=$ID_group_grant";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $protocol.= $sql . '<br>' . $s_err . '<br>';
        }
        if (kometa_num_rows($res) == 0) {
          $sql = "INSERT INTO mba_grant_task (id_task,id_group) VALUES($id_task,$ID_group_grant)";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            $protocol.= $sql . '<br>' . $s_err . '<br>';
          } else {
            
          }
        }
      }
    }
    if ($protocol == '')
      $result = new JSON_Result(true, 'Пользовательское меню успешно загружено', $protocol);
    else
      $result = new JSON_Result(false, 'В ходе загрузки пользовательского меню возникли ошибки', $protocol);
    return $result;
  }

  function RegistrationInputFile($sys) {
    // Регистрация поступивших файлов
    $URL_loader_dir = $_SESSION['URL_loader_dir'];

    //  $import_dir = get_Param_value('import_dir');
    if ($sys == 1) {
      $import_dir = $_SESSION['ProjectRoot'] . "sys/SYSXML/";
    }
    else  if ($sys == 2) {
      $import_dir = $_SESSION['autoimport_dir'];
    }else
      $import_dir = $_SESSION['import_dir'];

    $arxml = null;
    if (!file_exists("$import_dir")) {
      $result = new JSON_Result(false, "Каталог \"$import_dir\" не найден ", NULL);
      return $result;
    }
    $XMLFilesArray = scandir("$import_dir");
    $structureXML = new DOMDocument('1.0', 'UTF-8');
    foreach ($XMLFilesArray as $file) {
      set_time_limit(700);
      updateUserSessionTtl();
      $s = $file;
      if (!mb_check_encoding($file, 'utf8'))
        continue;
      if (strtolower(substr($file, strlen($file) - 4)) == ".xml") {
        // если расширение файла xml 
        // открываем файл и смитрим какой шаблон в нем прописан
        try {
          $s = $import_dir . $file;
          if (!$structureXML->load($s)) {
            // перенести в каталог плохих файлов
            set_bad_file($file);
            continue;
          }
        } catch (DOMException $e) {
          if (($sys != 1) &&($sys != 2)) {
            set_bad_file($file);
          }
          continue;
        };
        $node = $structureXML->documentElement;
        $id = null;

        foreach ($node->childNodes AS $item)
          if (strtolower($item->nodeName) == "id") {
            $id = strtolower(trim($item->nodeValue));
            //echo $id.'<br>';
            break;
          }
        if (isset($id)) {
          // схема загрузки определена ищем а есть ли такая схема
          $res = kometa_query("select id_xsd, id_data_type, id_source, id_periodicity, id_object, "
                  . "full_name, xsd, code, loader_name, priority, is_history, substitution, object_row_tag, "
                  . "is_crc, is_std_import from mbi_schema where code='$id' and is_history=0");
          if ($row = kometa_fetch_object($res)) {
            // счема найдена добавляем файл в список загрузки
            //!!!!! Здесь надо добавить еще проверку на соответствие XML-схеме
            // 
            $id_xsd = $row->id_xsd; // 
            $xsd = $row->xsd;
            if (($sys != 1) &&($sys != 2)) {

              //проверка на соответствие xsd
              $chk_xsd = true;
              if (($xsd != null) || (trim(xsd) == '')) {
                $chk_xsd = $structureXML->schemaValidateSource($xsd);

                if (!$chk_xsd) {
                  set_bad_file($file);
                  continue;
                }
              }
              $NeedCheckCRC = ($row->is_crc == 1);
              if ($NeedCheckCRC) {
                if (!CheckUploadCRCFile($s)) {// проверка CRC суммы
                  // перенести в каталог плохих файлов
                  set_bad_file($file);
                  continue;
                }
              };
            };

            $id_xml = null;
            // Определеяем зарегистрирован ли этот файл в "Поступающие данные в формате XML" со статусом не загружался
            $res = kometa_query("select id_xml, id_xsd, id_source, id_status, file_name, dt_input from mbi_xml where file_name='$import_dir$file' and id_status=1");
            if ($row = kometa_fetch_object($res)) {
              $id_xml = $row->id_xml;
            } else {
              // файл не найден, добавляем его в список для загрузки
              $sql = "INSERT INTO mbi_xml(id_xsd, id_status, file_name)  VALUES ( $id_xsd, 1, '$import_dir$file');";
              kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                $result = new JSON_Result(false, $sql . ' ' . $s_err, NULL);
                return $result;
              }
            }
          } else {
            // переносим файл в хранилище неизвестных
          }
        }
      }
    }
    $result = new JSON_Result(true, 'Регистрация файлов для загрузки прошла успешно', $protocol);
    return $result;
  }

  function ImportFile($id_xml) {
    global $sys;
    global $sapi;
    global $cntins; // сколько добавлено
    global $cntupd; // сколько заменено
    global $cnterr; // сколько ошибок
    global $cntskip; // сколько пропущено
    global $cntwarning; //предупреждений
    global $type_login;
//загрузка sql запросами из  mbi_schema в поле substitution
//формат substitution представление массива строками вида:        
//имя поля=>sql запрос с :параметром(имя поля из $id_xml)PHP_EOL
//sql запрос должен возвращать значение в имя поля             select F1 as "имя поля"

    updateUserSessionTtl();
    $structureXML = new DOMDocument('1.0', 'UTF-8');

//получаю ключ по файлу в mbi_schema            
    $sql = "SELECT id_xml, id_xsd, id_source, id_status, file_name, dt_input FROM mbi_xml where id_xml=$id_xml";
    $res = kometa_query($sql);
    if (!isset($res)) {
      $result = new JSON_Result(false, 'Ошибка выполнения запроса ' . $sql, NULL);
      return $result;
    }
    $row = kometa_fetch_object($res);

    $file_name = $row->file_name;
    if (!file_exists($file_name)) {
      if ($sapi != 'cli') {
        $msg = 'Файл не найден';
      } else {
        $msg = 'File Not Found';
      }
      set_status_xml_filename($id_xml, 4);
      $result = new JSON_Result(false, $msg, NULL);
      return $result;
    }

    $id_xsd = $row->id_xsd;
    if (isset($id_xsd)) {
//получаю массив substitution         
      $sql = "SELECT id_xsd, id_data_type, id_source, id_periodicity, id_object, full_name, xsd, "
              . "code, loader_name, priority, is_history, substitution, object_row_tag, is_crc, is_std_import "
              . "FROM mbi_schema where id_xsd=$id_xsd";
      $res = kometa_query($sql);
      if (!isset($res)) {
        $result = new JSON_Result(false, 'Ошибка выполнения запроса ' . $sql, NULL);
        return $result;
      }
      $row = kometa_fetch_object($res);
      $object_row_tag = $row->object_row_tag;
      if (!isset($object_row_tag) || ($object_row_tag == ''))
        $object_row_tag = 'mb_ObjectFields';
      $substitution = $row->substitution;
      if (!isset($row->id_object)) {
        if ($sapi != 'cli')
          ProtWriteInput(1, $id_xml, 'Не зарегистрирован объект, в который необходимо осуществить загрузку информации ', 'Не зарегистрирован объект, в который необходимо осуществить загрузку информации');
        $result = new JSON_Result(false, 'Не зарегистрирован объект, в который необходимо осуществить загрузку информации ', NULL);
        return $result;
      }
      $sysname = get_sysname($row->id_object);
      $id_object = $row->id_object;
      if (isset($substitution) && ($substitution != '')) {
        $SQLSubstitutionArray = string2KeyedArray($substitution, ';');
      } else {
        $SQLSubstitutionArray = Array();
      }
    }
    // выставляем признак наличия поля id_xml
    $sql = "SELECT  count(*) as cnt FROM  information_schema.COLUMNS c where c.column_name='id_xml'  and table_name='$sysname'";
    $res = kometa_query($sql);
    $row = kometa_fetch_object($res);
    if ($row->cnt > 0)
      $is_id_xml = true;

    $structureXML->load($file_name);
    $node = $structureXML->documentElement;

    $cnterr = 0;

    if (!table_exists($sysname)) {
      ProtWriteInput(1, $id_xml, 'Отсутствует таблица для занесения информации ', 'Не заполнено наименование TableName=' . $sysname);
      $result = new JSON_Result(false, 'Отсутствует таблица для занесения информации ', 'Не заполнено наименование TableName=' . $sysname, NULL);
      return $result;
    }

    // формируем список полей и определяем ключевое поле
    $sql = "SELECT mb_object_field.mandatory,mb_object_field.fieldname,is_field_key is_key "
            . " FROM mb_object_field where mb_object_field.id_object=$id_object";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      if ($sapi != 'cli')
        ProtWriteInput(1, $id_xml, 'Ошибка получения списка полей ', $sql . '<br>' . $s_err . '<br>');
      $result = new JSON_Result(false, "Ошибка получения списка полей по информационному объекту с кодом $sysname", NULL);
      return $result;
    }

    $fld_key = null;
    $flds = Array();
    $flds_mandatory = Array();
    $flds_default = Array();
    $x = 0;
    while ($row = kometa_fetch_object($res)) {
      // проверяем определено ли для этого поля значение по умолчанию
      if ($type_login == 3) {
        $sql1 = "SELECT t.name table_name, c.name column_name"
                . ", case when (c.is_identity=1) "
                . " or Exists(select * from sys.default_constraints dc where dc.object_id=t.object_id and dc.parent_column_id=c.column_id) then 1 else 0 end column_default  "
                . "FROM sys.tables as t join sys.all_columns as c on t.object_id=c.object_id "
                . "join sys.schemas as s on s.schema_id=t.schema_id "
                . "where t.type_desc='USER_TABLE' and s.name='dbo' "
                . "and c.name='" . $row->fieldname . "' and t.name='$sysname'";
      } else
        $sql1 = "SELECT  t.table_name, c.column_name, c.column_default "
                . " FROM information_schema.TABLES t JOIN information_schema.COLUMNS c ON t.table_name::text = c.table_name::text "
                . " WHERE t.table_schema::text = 'public'::text AND t.table_catalog::name = current_database() AND "
                . " t.table_type::text = 'BASE TABLE'::text AND NOT \"substring\"(t.table_name::text, 1, 1) = '_'::text"
                . " and c.column_default is  not null and c.column_name='" . $row->fieldname . "' and t.table_name='$sysname'";
      $res1 = kometa_query($sql1);
      $flds_default[$row->fieldname] = (kometa_num_rows($res1) > 0);

      if ($row->mandatory == 1) {
        if ($flds_default[$row->fieldname])
          $flds_mandatory[$row->fieldname] = 0;
        else
          $flds_mandatory[$row->fieldname] = 1;
      } else
        $flds_mandatory[$row->fieldname] = 0;
      if ($row->is_key) {
        $fld_key = $row->fieldname;
      }
      $flds[$row->fieldname] = '';

      $x++;
    }

    $fld_code = get_code_field($sysname);
    $b = array_keys($flds);
//повсем записям загружаемого файла
    $field_history = get_history_field($sysname);
    $exists_is_history = isset($field_history);

    foreach ($node->getElementsByTagName($object_row_tag) AS $domNode) {
      set_time_limit(7000);
      // Зачистка значений полей
      foreach ($flds as $key1 => $value1) {
        $flds[$key1] = '';
      }
//заполняем в масиив все значения XML файла

      foreach ($domNode->childNodes AS $aNode) {
        $a = $aNode->tagName;

        $flds[strtolower($a)] = trim($domNode->getElementsByTagName($a)->item(0)->nodeValue);
        $n = $domNode->getElementsByTagName($a)->item(0);
        if (isset($n)) {
          $atr = $n->getAttribute('type');
          if (isset($atr) && ($atr == 'sql_query')) {
            $res_1 = kometa_query($flds[strtolower($a)]);
            $row_1 = kometa_fetch_row($res_1);
            $flds[strtolower($a)] = $row_1[0];
          }
        }
      }
      //заполняю массив значений по $SQLSubstitutionArray               
      $SubstitutionValueArray = Array();
      foreach ($SQLSubstitutionArray as $key => $value) {
        $sql = $value;
        ////замещаю в sql параметры значениями из массива значений XML                    
        foreach ($flds as $key1 => $value1)
          if (isset($key1) && ($key1 != '') && isset($value1) && ($value1 != '')) {
            $sql = str_replace(":$key1:", kometa_escape_string($value1), $sql);
          }
        if ($sql != $value) {
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            ProtWriteInput(1, $id_xml, 'Ошибка подготовки данных для загрузки ', $sql . '<br>' . $s_err . '<br>');
            $result = new JSON_Result(false, 'Ошибка подготовки данных для загрузки ', $sql . ' ' . $s_err);
            return $result;
          }
          if ($res) {
            $row = kometa_fetch_object($res);
            if ((!$row) || (!isset($row->$key))) {
              ProtWriteInput(3, $id_xml, 'Не найдено соответствие для поля ' . $key, 'Не найдено соответствие для поля ' . $key . 'SQL:: ' . $sql);
              $cntwarning++;
            }
            $SubstitutionValueArray[$key] = $row->$key;
          }
        }
      }
//объединяю массивы значений sql и xml
      $flds['id_xml'] = $id_xml;
      foreach ($SubstitutionValueArray as $key => $value) {
        $flds[$key] = $value;
      }
      $s_mandatory = '';

      // Проверяем заполнены ли обязательные поля
      foreach ($flds_mandatory as $a => $value) {
        if ((!isset($flds[$a]) || ($flds[$a] == '')) && ($value == 1)) {
          $s_mandatory .= "Не заполнено обязательное поле \"$a\" <br>";
        }
      }

      if ($s_mandatory != '') {
        ProtWriteInput(2, $id_xml, 'Не заполнено одно из обязательных полей ', $s_mandatory);
        if ($sapi != 'cli')
        // echo $s_mandatory . '<b>Запись пропущена</b><br>';
          $cntskip++;
        continue;
      }

//заполняем в масиив все значения найденных в физ. таблице полей

      if ((isset($fld_code)) && ($fld_code != '')) {
        // ищем соответствующую запись по ключу
        if ($exists_is_history)
          $sql = "SELECT $fld_code FROM $sysname where $fld_code='$flds[$fld_code]' and is_history=0";
        else
          $sql = "SELECT $fld_code FROM $sysname where $fld_code='$flds[$fld_code]' ";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          if ($sapi != 'cli')
            ProtWriteInput(2, $id_xml, 'Ошибка поиска записи по коду ', $sql . '<br>' . $s_err . '<br>');
          $cnterr++;
        }
      }
      if (!((isset($fld_code)) && ($fld_code != '')) || (kometa_num_rows($res) == 0)) {
        // Если отсутствует ключевое поле или значение ключевого поля не определено или не существует записи с заданным ключом
        // запись отсутствует необходимо формировать инструкцию insert
        // если есть поле id_xml то добавляем его в список для   
// ищем есть ли такая  запись,если да до изменяем если нет то добавляем
        $sql = "SELECT count(*) as cnt FROM $sysname where $fld_key='$flds[$fld_key]'";
        $res = kometa_query($sql);
        $row = kometa_fetch_object($res);
        if ($row->cnt > 0) {
          $lst_fld = '';
          foreach ($b as $a)
            if (($a != $fld_key) && isset($flds[$a]) && ($flds[$a] != '')) {
              if ($lst_fld != '')
                $lst_fld.=',';
              $lst_fld .=$a . '=' . my_escape_string($flds[$a]);
            }
          if ($is_id_xml)
            $lst_fld.='id_xml=' . $id_xml;

          $sql = "UPDATE $sysname set $lst_fld where $fld_key='$flds[$fld_key]'";
          $res = kometa_query($sql);
          //echo $sql . '<br>';
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
            $cnterr++;
          } else {
            if (substr($sql, 0, 1) == 'U')
              $cntupd++;
            else
              $cntins++;
          }
        }
        else {
          $lst_fld = '';
          $lst_val = '';
          foreach ($b as $a) {
            if (/* (($a != $fld_key) || (!$flds_default[$a])) && */ (isset($flds[$a]) && ($flds[$a] != ''))) {
              if ($lst_fld != '')
                $lst_fld.=',';
              $lst_fld .=$a;
              if ($lst_val != '')
                $lst_val.=',';
              $lst_val .= my_escape_string($flds[$a]);
            }
          }
          if ($is_id_xml) {
            $lst_fld.=',id_xml';
            $lst_val.=",$id_xml";
          }
          if (($type_login == 3) && (isset($flds[$fld_key])) && ($flds[$fld_key] != '')) {
            $sql = "SET IDENTITY_INSERT $sysname ON";
            $res = kometa_query($sql);
            //echo $sql . '<br>';
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
            }
          }
          $sql = "INSERT INTO $sysname ($lst_fld) VALUES ($lst_val)";
          $res = kometa_query($sql);
          //echo $sql . '<br>';
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
            $cnterr++;
          } else {
            if (substr($sql, 0, 1) == 'U')
              $cntupd++;
            else
              $cntins++;
          }
          if (($type_login == 3) && (isset($flds[$fld_key])) && ($flds[$fld_key] != '')) {
            $sql = "SET IDENTITY_INSERT $sysname OFF";
            $res = kometa_query($sql);
            //echo $sql . '<br>';
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
            }
          }
        }
      } else {
        // проверяем есть ли поле is_history
        if (!$exists_is_history) {
          // Если такого поля нет, то заменяем
          $lst_fld = '';
          foreach ($b as $a)
            if (($a != $fld_key) && isset($flds[$a]) && ($flds[$a] != '')) {
              if ($lst_fld != '')
                $lst_fld.=',';
              $lst_fld .=$a . '=' . my_escape_string($flds[$a]);
            }
          $sql = "UPDATE $sysname set $lst_fld where $fld_code='$flds[$fld_code]'";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            if ($sapi != 'cli')
              echo 'Ошибка изменения записи<br>';
            ProtWriteInput(2, $id_xml, 'Ошибка изменения записи', $sql . '<br>' . $s_err . '<br>');
            $cnterr++;
          } else {
            $cntupd++;
          }
        } else {
          // запись присутствует необходимо формировать инструкцию update
          $NeedUpdate = False;
          //надо проверить все ли поля совпадают, если не все то апдейтим, а старую делаем архивной
          $sql = "SELECT fieldname from mb_object_field where id_object=$id_object";
          $res = kometa_query($sql);
          $fld_list = '';
          $coma = '';
          while ($row = kometa_fetch_object($res)) {
            $fld_list.=$coma . $row->fieldname;
            $coma = ',';
          }
          $sql = "SELECT $fld_list FROM $sysname where $fld_code='$flds[$fld_code]' and is_history=0";
          $res = kometa_query($sql);
          $row = kometa_fetch_object($res);
          foreach ($b as $a)
            if ($a != $fld_key) {
              if (($flds[$a] != $row->$a) && ($a != 'id_xml') && ($a != 'is_history') && ($flds_default[$a] == 0)) {
                $NeedUpdate = True;
                ProtWriteInput(3, $id_xml, 'Предупреждение: изменено зачение поля', 'Предупреждение: ' . "$fld_code='" . $flds[$fld_code] . "' различие по полю '$a' значения старое='" . $row->$a . "' новое='" . $flds[$a]);
                $cntwarning++;
                break;
              }
            }

          if ($NeedUpdate) {
            $sql = "UPDATE $sysname set is_history=1 where $fld_code='$flds[$fld_code]'";
            $res = kometa_query($sql);
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              ProtWriteInput(2, $id_xml, 'Ошибка записи is_history=1 по коду', $sql . '<br>' . $s_err . '<br>');
              $cnterr++;
            } else {
              $lst_fld = '';
              $lst_val = '';
              foreach ($b as $a)
                if (($a != $fld_key) && isset($flds[$a]) && ($flds[$a] != '')) {
                  if ($lst_fld != '')
                    $lst_fld.=',';
                  $lst_fld .=$a;
                  if ($lst_val != '')
                    $lst_val.=',';
                  $lst_val .= my_escape_string($flds[$a]);
                }
              // если есть поле id_xml то добавляем его в список для   
              $sql = "SELECT  count(*) as cnt FROM  information_schema.COLUMNS c where c.column_name='id_xml'  and table_name='$sysname'";
              $res = kometa_query($sql);
              $row = kometa_fetch_object($res);
              $sql = "INSERT INTO $sysname ($lst_fld) VALUES ($lst_val)";
              $res = kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                ProtWriteInput(2, $id_xml, 'Ошибка добавления записи', $sql . '<br>' . $s_err . '<br>');
                $cnterr++;
              } else {
                $cntupd++;
              }
            }
          } else {
            $cntskip++;
          }
        }
      }
    }
    $id_status = 1;
    if ($cnterr == 0)
      $id_status = 2;
    else
      $id_status = 3;

    $res = kometa_query("Select count(*) as cnt from $sysname");

    $row = kometa_fetch_object($res);

    $table = new HTML_Table();
    $table->setAttribute('border', 1);
    $table->setAttribute('width', '100%');
    $table->setAttribute('align', 'center');
    $table->setAttribute('cellspacing', 0);
    $table->setAttribute('cellpadding', 0);

    $table->setCellContents(0, 0, 'Всего терминов');
    $table->setCellContents(0, 1, 'Загружено новых');
    $table->setCellContents(0, 2, 'Заменено');
    $table->setCellContents(0, 3, 'Пропущено');
    $table->setCellContents(0, 4, 'Ошибок');
    $table->setCellContents(0, 5, 'Предупреждения');

    $table->setCellContents(1, 0, $row->cnt);
    $table->setCellContents(1, 1, $cntins);
    $table->setCellContents(1, 2, $cntupd);
    $table->setCellContents(1, 3, $cntskip);
    $table->setCellContents(1, 4, $cnterr);
    $table->setCellContents(1, 5, $cntwarning);



    $s = "Загружено <br>" . $table->toHtml();
//    if ($sapi != 'cli') {
//      echo $s;
//    } else {
//      echo "All    =" . $row->cnt . PHP_EOL;
//      echo "New    =" . $cntins . PHP_EOL;
//      echo "Updated=" . $cntupd . PHP_EOL;
//      echo "Skip   =" . $cntskip . PHP_EOL;
//      echo "Error  =" . $cnterr . PHP_EOL;
//    }
    ProtWriteInput(5, $id_xml, "Итоги загрузки", $s);
    set_status_xml_filename($id_xml, $id_status);
    $result = new JSON_Result(true, 'Успешно загружено', $s);
    return $result;
  }

  function ImportFiles() {
    $res_f = kometa_query('select mbi_xml.id_xml, mbi_xml.id_xsd, mbi_schema.is_std_import '
            . 'from mbi_xml left join mbi_schema on mbi_schema.id_xsd=mbi_xml.id_xsd where id_status=1 order by mbi_schema.priority,mbi_xml.file_name');
    $rs = kometa_num_rows($res_f);
    while ($row_f = kometa_fetch_object($res_f)) {
      $result=$this->ImportFile($row_f->id_xml);
      if ($result->success===false){
        return $result;
      }
    }
  }

}
