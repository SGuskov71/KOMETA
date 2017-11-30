<?php

/*
 * проектирование запросов
 */
session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/QueryBuilder_include/QueryBuilder_function.php");

class QueryBuilder_class {

  function GetListQuery() {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $sql = "SELECT f.id_stored_query, f.id_object, f.id_user, f.code, f.short_name, f.content, o.short_name as name_object FROM mb_stored_query f left join mb_object o on f.id_object=o.id_object ";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    $_list = array();
    while ($row = kometa_fetch_object($res))
      array_push($_list, $row);
    $result = new JSON_Result(true, '', $_list);
    return $result;
  }

  function DeleteStoredQuery($code) {//удалить запрос
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if (!isset($code)) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      return $result;
    }
    $sql = "delete FROM mb_stored_query where code='$code'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    } else {
      $result = new JSON_Result(true, 'Успешно удалено', NULL);
      return $result;
    }
  }

  function InitObject($id_stored_query, $id_object) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $sysname = get_sysname($id_object);

    if (!isset($id_stored_query)) {//новый
      $result->QueryTemplate->text = 'Запрос';
      $result->QueryTemplate->expanded = true;
      $result->QueryTemplate->leaf = false;
      $result->QueryTemplate->isMasterObject = true; //означает что это ветка объекта в которй лежат настройки объекта дочерних веток
      $result->QueryTemplate->Code = GenerateUnicalCodeField('mb_stored_query', 'code');
      $result->QueryTemplate->iconCls = 'query';
      $result->QueryTemplate->children = array();
      $result->QueryTemplate->ItemType = 'Query';
      $result->QueryTemplate->GroupCondition = 'and';
      $result->QueryTemplate->id_object = $id_object;
      $result->QueryTemplate->sysname = $sysname;
      $result->QueryTemplate->id_objectDescription = get_object_descr($id_object);
      $result->QueryTemplate->Description = 'Новый запрос';
      $result->QueryTemplate->ShowOrder = 1;

      $result = new JSON_Result(true, '', $result);
      return $result;
    } else {
      $sql = "SELECT id_stored_query, id_object, id_user, code, short_name, content FROM mb_stored_query  where id_stored_query=" . $id_stored_query;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      }
      if ($row = kometa_fetch_object($res)) {
        $result->QueryTemplate = json_decode($row->content);
        $result->QueryTemplate->id_stored_query = $id_stored_query;
        $result->QueryTemplate->Code = $row->code;
        $result->QueryTemplate->ShowOrder = $row->ord;
        $result->QueryTemplate->id_object = $row->id_object;
        $result->QueryTemplate->sysname = get_sysname($row->id_object);

        $result = new JSON_Result(true, '', $result);
        return $result;
      } else {
        $result = new JSON_Result(false, 'Пусто', NULL);
        return $result;
      }
    }
  }

  function SaveQueryTemplate($InputFormTemplate) {//сохранение шаблона в БД
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    global $ID_User;
    $id_stored_query = $InputFormTemplate->id_stored_query;
    unset($InputFormTemplate->id_stored_query);
    $id_object = $InputFormTemplate->id_object;
//зачищаю ненужные свойства
    unset($InputFormTemplate->id_object);
// unset($InputFormTemplate->id_objectDescription);
    $Code = $InputFormTemplate->Code;
    unset($InputFormTemplate->Code);
    $ShowOrder = $InputFormTemplate->ShowOrder;
    unset($InputFormTemplate->ShowOrder);

    unset($row);
    if (isset($id_stored_query) && ($id_stored_query != '')) {
      $sql = "SELECT id_stored_query FROM mb_stored_query where id_stored_query=$id_stored_query";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      }
      $row = kometa_fetch_object($res);
    }
    if (isset($row)) {
      $sql = "UPDATE mb_stored_query SET short_name=" . my_escape_string($InputFormTemplate->Description)
      . ", content =" . my_escape_string(json_encode($InputFormTemplate))
      . ", code ='$Code'"
      . "  where id_stored_query=" . $row->id_stored_query;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, my_escape_string($s_err), NULL);
        return $result;
      } else {
        $result = new JSON_Result(true, 'Успешно обновлено', $id_stored_query);
        return $result;
      }
    } else {
// такое  не найдено добавляем
      $sql = "INSERT INTO mb_stored_query(code, id_object, short_name, id_user, content)"
      . "VALUES ("
      . "'$Code', $id_object, "
      . my_escape_string($InputFormTemplate->Description) . ", "
      . $ID_User . ", "
      . my_escape_string(json_encode($InputFormTemplate)) . ")";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, my_escape_string($s_err), NULL);
        return $result;
      } else {
        $sql = "SELECT id_stored_query FROM mb_stored_query where code='$Code'"; //получаю ид добавленной записи по коду
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $result = new JSON_Result(false, $s_err, NULL);
          return $result;
        } else {
          $row = kometa_fetch_object($res);
          $result = new JSON_Result(true, 'Успешно добавлено', $row->id_stored_query);
          return $result;
        }
      }
    }
  }

  function GetStoredQueryParamList($codeStoredQuery) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $result = _GetStoredQueryParamList($codeStoredQuery);
    return $result;
  }

  function GetStoredQueryCondition($codeStoredQuery, $ParamValuesArray) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    global $s_error;
    $s_error = '';
    if (!isset($codeStoredQuery)) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      return $result;
    } else {
      $sql = "SELECT id_stored_query, id_object, id_user, code, short_name, content FROM mb_stored_query where code='$codeStoredQuery'";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      } else {
        if (kometa_num_rows($res) == 0) {
          $result = new JSON_Result(false, 'Запрос не найден', NULL);
          return $result;
        } else {
          $row = kometa_fetch_object($res);
          $QueryTemplate = json_decode($row->content);
          $Params = $QueryTemplate->QueryParams;
//здесь Дима должен разобрать структуру шаблона запроса и построить SQL подставляя значения параметров
          $StoredQueryCondition = $this->QueryParams2QueryCondition($QueryTemplate, $ParamValuesArray);
          if ($s_error != '') {
            $result = new JSON_Result(false, "Ошибка выполнения запроса: $s_error", NULL);
            return $result;
          }
          $result = new stdClass();
          $sysname = get_sysname($row->id_object);
          $result->sysname = $sysname;
          $result->StoredQueryCondition = $StoredQueryCondition;
          $result->Description = $row->short_name;
          $result = new JSON_Result(true, "", $result);
          return $result;
        }
      }
    }
  }

  function QueryParams2QueryCondition($QueryTemplate, $ParamValuesArray) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    global $oper_array;
    global $s_error;
    $oper_array = array();

    function BuildQueryGroup($QGroup, $AliasTable, $ParamValuesArray) {
      global $s_error;

      $s = '';
      $op = ' ';
      foreach ($QGroup->children as $key => $value) {
        if ($value->ItemType == 'GroupParam') {
          $s.=$op . BuildQueryGroup($value, $AliasTable, $ParamValuesArray);
          if ($s_error != '') {
            return;
          }
        } else if ($value->ItemType == 'FieldCondition') {
          $s.=$op . BuildQueryCondition($value, $AliasTable, $ParamValuesArray);
          if ($s_error != '') {
            return;
          }
        } else if ($value->ItemType == 'Link') {
          $t_lnk = $AliasTable . '_lnk';
          $sw = BuildQueryGroup($value, $t_lnk, $ParamValuesArray);
          if ($s_error != '') {
            return;
          }

          // Строю условия связи
          $id_link = $value->id_link;
          $sql = "select f_p.fieldname fld_p, f_c.fieldname fld_c from mb_object_link_field lnk inner join mb_object_field f_p on lnk.id_field_parent = f_p.id_field "
          . " inner join mb_object_field f_c on lnk.id_field_child = f_c.id_field where id_link = $id_link";
          $res = kometa_query($sql);
          $lnk_w = '';

          while ($row = kometa_fetch_object($res)) {
            $lnk_w.=" and t." . $row->fld_p . " = $t_lnk." . $row->fld_c;
          }

          $s.=$op . "EXISTS(SELECT * FROM " . $value->sysname . " as $t_lnk WHERE $sw $lnk_w)";
        }
        $op = ' ' . $QGroup->GroupCondition . ' ';
      }
      if (trim($s) == '')
        $s = '1=1';
      if ($QGroup->NOT_GroupCondition == true)
        $op = ' not ';
      else
        $op = '';
      return $op . '(' . $s . ')';
    }

    function BuildQueryCondition($QCondition, $AliasTable, $ParamValuesArray) {
      $result = CheckConnection();
      if ($result->success === false) {
        return $result;
      }
      unset($result);

      global $oper_array;
      global $s_error;
      $op = $oper_array[$QCondition->Operation];
      $s = $QCondition->DataField . ' ' . $op;
      if ($QCondition->DataSource == 'RB_Value') {
        $v = $QCondition->Value;
      } else if ($QCondition->DataSource == 'RB_Param') {
        $p = $QCondition->ParamCode;
        if (!isset($p)) {
          $s_error = "Неопределен параметр в выражении для отбора по полю \"" . $QCondition->Caption . "\"";
          return;
        }
        $v = $ParamValuesArray->$p;
      }
      if ($QCondition->Operation == 'flt_opr_like')
        $v = "%$v%";
      else if ($QCondition->Operation == 'flt_opr_begin')
        $v = "%$v%";
      $s.="'$v'";
      return $s;
    }

    $sql = "SELECT id_filter_operation, short_name, code, sql_operation from mbs_filter_operation";
    $res = kometa_query($sql);
    while ($row = kometa_fetch_object($res)) {//заполняю массив операций
      $oper_array[$row->code] = $row->sql_operation;
    }

    if (count($QueryTemplate->children) == 0)
      $result = '1=1';
    else {
      $result = BuildQueryGroup($QueryTemplate, 't', $ParamValuesArray);
      if ($s_error != '') {
        return;
      }
    }
    return $result;
  }

}
