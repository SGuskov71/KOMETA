<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

Class CFilterConditionItem { //объект описания одного условия фильтра

  public $field_code;
  public $field_condition;
  public $field_value; //если $multivalue то содержит массив значений
  public $multivalue; //если true то в $field_value одномерный массив значений
  public $id_user; // ид пользователя
  public $id_object; //ид объекта
  public $id_link;
  public $sysname;

  function __construct($field_code, $field_condition, $field_value) {
    $this->field_code = $field_code;
    $this->field_condition = $field_condition;
    $this->field_value = $field_value;
  }

}

Class CFilterSettingsItem { //объект описания одного условия настроек фильтра

  public $field_code;
  public $field_descr;
  public $condition_list; //условие на значение(опреация отношения)
  public $input_type; //способ ввода (1- текст, 2- комбо, 3-дата, 4-словарь)
  public $multivalue; //true - под вод нескольких значений
  public $combo_values_array; // массив возможных значений
  public $slv_id_object; //объект для выбора значений из словаря
  public $ValidateCondition; //условие проверки значеня при вводе
  public $id_link;
  public $sysname;

  function __construct($field_code, $field_descr, $condition_list, $input_type, $multivalue) {
    $this->field_code = $field_code;
    $this->field_descr = $field_descr;
    $this->condition_list = $condition_list;
    $this->input_type = $input_type;
    $this->multivalue = $multivalue == 1;
  }

}

Class CObjectFilter { //объект для работы с фильтрами, содержит сервисные функции для AJAX

  public $id_object;
  public $id_ObjectFilter; //ключ фильтра в базе
  public $FilterConditions; // массив типа CFilterConditionItem
  public $FilterCaption;
  public $FilterSQLWhereStr;
  public $Save2DB;

  function __construct($id_object, $for_query) {
    $this->id_object = $id_object;
    $this->for_query = $for_query;
  }

  function GetFieldListJSON() {//формирует JSON строку со списком полей
    global $type_login;
    $aray = array();
    $combo_values_array = null;
    $slv_settings = null;
//список полей для фильтра
    if ($type_login == 3) {
      $SQL = "SELECT null as id_link,fld.id_field, fld.id_filter_type, fld.short_name,  ft.code as type_code, cast(ft.input_check_condition as varchar)  as input_check_condition, fld.multi_value,
                fld.fieldname, fld.id_slv_object ,o.sysname,fld.order_view,0 as ord
                FROM mb_object_field as fld left join  mb_field_type as ft   on  fld.id_field_type =  ft.id_field_type
				join mb_object as o on o.id_object=fld.id_object
                WHERE is_filter_use=1 and fld.id_object=$this->id_object
				 order by ord,sysname,order_view,short_name";
    } else {
      $SQL = "SELECT null as id_link,fld.id_field, fld.id_filter_type, fld.short_name,  ft.code as type_code, ft.input_check_condition , fld.multi_value,
                fld.fieldname, fld.id_slv_object ,o.sysname,fld.order_view,0 as ord
                FROM mb_object_field as fld left join  mb_field_type as ft   on  fld.id_field_type =  ft.id_field_type
				join mb_object as o on o.id_object=fld.id_object
                WHERE is_filter_use=1 and fld.id_object=$this->id_object
				 order by ord,sysname,order_view,short_name";
    }
    $res = kometa_query($SQL);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      return $result = new JSON_Result(false, $s_err, '');
    }

    while ($row = kometa_fetch_object($res)) {
      $cond = array(); //список операции по полю
      $combo_values_array = null;
      if ($row->id_filter_type == 1) {
        $sysname = $row->sysname;
        $distinct_field = $row->fieldname;
        $SQL = "SELECT distinct $distinct_field FROM $sysname";
        $res1 = kometa_query($SQL);
        if (kometa_num_rows($res1) < 50) {
          $combo_values_array = array();
          while ($row1 = kometa_fetch_object($res1)) {
            array_push($combo_values_array, $row1->$distinct_field);
          }
        } else
          $row->id_filter_type = 0;
      }
      else if ($row->id_filter_type == 3) {
        $slv_id_object = $row->id_slv_object;
      } else
        $slv_id_object = null;


      $SQL = "SELECT fo.code, fo.sql_operation, fo.short_name
                    FROM mb_object_field f join mb_field_type_filter_operation fto on f.id_field_type=fto.id_field_type
                    join mbs_filter_operation fo on fto.id_filter_operation = fo.id_filter_operation
                    where f.id_field=$row->id_field order by fo.id_filter_operation";
      $res1 = kometa_query($SQL);

      while ($row1 = kometa_fetch_object($res1)) {
        if (($row->id_filter_type == 1) && ($this->for_query == false) && (($row1->code == 'flt_opr_like') || ($row1->code == 'flt_opr_begin'))) {

        } else
          $cond[$row1->code] = $row1->short_name;
      }

      $n = array_push($aray, new CFilterSettingsItem($row->fieldname, $row->short_name, $cond, $row->id_filter_type, $row->multi_value));
      $aray[$n - 1]->slv_id_object = $slv_id_object;
      $aray[$n - 1]->combo_values_array = $combo_values_array;
      $aray[$n - 1]->ValidateCondition = $row->input_check_condition;
      if (isset($row->id_link)) {
        $aray[$n - 1]->sysname = $row->sysname;
        $aray[$n - 1]->id_link = $row->id_link;
      }
    }

    return $aray;
  }

  function GetFilterSettingsJSON() {//формирует JSON строку с условиями настроек фильтра
    global $type_login;
    $aray = array();
    $combo_values_array = null;
    $slv_settings = null;
//список полей для фильтра
    if ($type_login == 3) {
      $SQL = "SELECT id_link,id_field,id_filter_type,short_name,type_code, input_check_condition,multi_value,fieldname,id_slv_object,sysname from
(
SELECT null as id_link,fld.id_field, fld.id_filter_type, fld.short_name,  ft.code as type_code, cast(ft.input_check_condition as varchar)  as input_check_condition, fld.multi_value,
                fld.fieldname, fld.id_slv_object ,o.sysname,fld.order_view,0 as ord
                FROM mb_object_field as fld left join  mb_field_type as ft   on  fld.id_field_type =  ft.id_field_type
				join mb_object as o on o.id_object=fld.id_object
                WHERE is_filter_use=1 and fld.id_object=$this->id_object
				union
SELECT o_lnk.id_link,fld.id_field, fld.id_filter_type, o_lnk.short_name+' &#8594; '+fld.short_name as short_name, ft.code  as type_code, cast(ft.input_check_condition as varchar) as input_check_condition, fld.multi_value,
                o.sysname+'.'+fld.fieldname as fieldname, fld.id_slv_object,o.sysname,fld.order_view,1 as ord
                FROM mb_object_field as fld left join  mb_field_type as ft   on  fld.id_field_type =  ft.id_field_type
				join mb_object_link o_lnk on  exists(select * from mba_grant_object where mba_grant_object.id_group in (" . get_id_user_groups() . ") and mba_grant_object.id_object=o_lnk.id_object_child)
        and o_lnk.id_object_parent=$this->id_object  and fld.id_object=o_lnk.id_object_child
		join mb_object as o on o.id_object=fld.id_object
                WHERE is_filter_use=1
				) as t
				 order by ord,sysname,order_view,short_name";
    } else {
      $SQL = "SELECT id_link,id_field,id_filter_type,short_name,type_code, input_check_condition,multi_value,fieldname,id_slv_object,sysname from
(
SELECT null as id_link,fld.id_field, fld.id_filter_type, fld.short_name,  ft.code as type_code, ft.input_check_condition , fld.multi_value,
                fld.fieldname, fld.id_slv_object ,o.sysname,fld.order_view,0 as ord
                FROM mb_object_field as fld left join  mb_field_type as ft   on  fld.id_field_type =  ft.id_field_type
				join mb_object as o on o.id_object=fld.id_object
                WHERE is_filter_use=1 and fld.id_object=$this->id_object
				union
SELECT o_lnk.id_link,fld.id_field, fld.id_filter_type, o_lnk.short_name||' &#8594; '||fld.short_name as short_name, ft.code  as type_code, ft.input_check_condition, fld.multi_value,
                o.sysname||'.'||fld.fieldname as fieldname, fld.id_slv_object,o.sysname,fld.order_view,1 as ord
                FROM mb_object_field as fld left join  mb_field_type as ft   on  fld.id_field_type =  ft.id_field_type
				join mb_object_link o_lnk on  exists(select * from mba_grant_object where mba_grant_object.id_group in (" . get_id_user_groups() . ") and mba_grant_object.id_object=o_lnk.id_object_child)
        and o_lnk.id_object_parent=$this->id_object  and fld.id_object=o_lnk.id_object_child
		join mb_object as o on o.id_object=fld.id_object
                WHERE is_filter_use=1
				) as t
				 order by ord,sysname,order_view,short_name";
    }
    $res = kometa_query($SQL);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      return $result = new JSON_Result(false, $s_err, '');
    }

    while ($row = kometa_fetch_object($res)) {
      $cond = array(); //список операции по полю
      $combo_values_array = null;
      if ($row->id_filter_type == 1) {
        $sysname = $row->sysname;
        $distinct_field = $row->fieldname;
        $SQL = "SELECT distinct $distinct_field FROM $sysname";
        $res1 = kometa_query($SQL);
        if (kometa_num_rows($res1) < 50) {
          $combo_values_array = array();
          while ($row1 = kometa_fetch_object($res1)) {
            array_push($combo_values_array, $row1->$distinct_field);
          }
        } else
          $row->id_filter_type = 0;
      }
      else if ($row->id_filter_type == 3) {
        $slv_id_object = $row->id_slv_object;
      } else
        $slv_id_object = null;


      $SQL = "SELECT fo.code, fo.sql_operation, fo.short_name
                    FROM mb_object_field f join mb_field_type_filter_operation fto on f.id_field_type=fto.id_field_type
                    join mbs_filter_operation fo on fto.id_filter_operation = fo.id_filter_operation
                    where f.id_field=$row->id_field order by fo.id_filter_operation";
      $res1 = kometa_query($SQL);

      while ($row1 = kometa_fetch_object($res1)) {
        if (($row->id_filter_type == 1) && (($row1->code == 'flt_opr_like') || ($row1->code == 'flt_opr_begin'))) {

        } else
          $cond[$row1->code] = $row1->short_name;
      }

      $n = array_push($aray, new CFilterSettingsItem($row->fieldname, $row->short_name, $cond, $row->id_filter_type, $row->multi_value));
      $aray[$n - 1]->slv_id_object = $slv_id_object;
      $aray[$n - 1]->combo_values_array = $combo_values_array;
      $aray[$n - 1]->ValidateCondition = $row->input_check_condition;
      if (isset($row->id_link)) {
        $aray[$n - 1]->sysname = $row->sysname;
        $aray[$n - 1]->id_link = $row->id_link;
      }
    }

    return $aray;
  }

  function ReadFilterConditionsJSON($JSONString) { //считывает условия фильтра из строки в формате JSON
    global $ID_User;
    $tmp = json_decode($JSONString);
    $this->FilterConditions = $tmp->FilterConditions;
    $this->FilterCaption = $tmp->FilterCaption;
    $this->id_object = $tmp->id_object;
    $this->id_ObjectFilter = $tmp->id_ObjectFilter;
    $this->Save2DB = $tmp->Save2DB;
    if (!isset($tmp->id_ObjectFilter))
      $tmp->id_user = $ID_User;
    $this->id_user = $tmp->id_user;
    return TRUE;
  }

  function GetFilterConditionsJSON() { //формирует JSON строку с условиями фильтра
    $tmp = new CObjectFilter($this->id_object);
    $tmp->FilterConditions = $this->FilterConditions;
    $tmp->id_object = $this->id_object;
    $tmp->FilterCaption = $this->FilterCaption;
    $tmp->id_ObjectFilter = $this->id_ObjectFilter;
    $tmp->FilterSQLWhereStr = $this->FilterSQLWhereStr;
    $tmp->id_user = $this->id_user;
    return $tmp;
  }

  function AppendSQLCondition($oper_array, $operation_code, $field_code, $field_value) {//вызывается изнутри BildFilterSQLWhereStr
    $result = '';
    if (isset($operation_code) && ($operation_code != '') && isset($field_code) && ($field_code != '') && isset($field_value)) {
      if ($operation_code === "flt_opr_null") {
        $result .= " $field_code $oper_array[$operation_code]";
      } else if ($operation_code === "flt_opr_not_null") {
//        $result .= " $field_code like '_%'";
        $result .= " $field_code $oper_array[$operation_code]";
      } else if ($operation_code === "flt_opr_like") {
        $result .= " $field_code $oper_array[$operation_code] '%$field_value%'";
      } else if ($operation_code === "flt_opr_begin") {
        $result .= " $field_code $oper_array[$operation_code] '$field_value%'";
      } else {
        //здесь надо определить тип поля и для дат и строк менять формат
        $result .= " $field_code $oper_array[$operation_code] '$field_value'";
      }
    };
    return $result;
  }

  function BildFilterSQLWhereStr() {//формирует часть sql предложения с условиями этого фильтра
    $oper_array = array();
    $sql = "SELECT id_filter_operation, short_name, code, sql_operation from mbs_filter_operation";
    $res = kometa_query($sql);
    while ($row = kometa_fetch_object($res)) {//заполняю массив операций
      $oper_array[$row->code] = $row->sql_operation;
    }
    $this->FilterSQLWhereStr = '';

    $tab_prev = '';
    $id_link = '';
    $ss_exists = '';
    foreach ($this->FilterConditions as $FilterCondition) {//пробежаться по $this->FilterConditions и сформировать условие where sql
      // выделяем из кода поля информационный объект к которому он относится
      // ищем точку
      $i = strpos($FilterCondition->field_code, '.');
      if ($i != false) {
        // выделяем id_link
        $id_lnk = $FilterCondition->id_link;
        $fld = $FilterCondition->field_code;
        $tab = $FilterCondition->sysname;
        if ((($tab != $tab_prev) || (($FilterCondition->field_condition == flt_opr_eqv) && (strpos($ss_exists, $fld . ' = ')))) && ($ss_exists != '')) {
          // опеределяем условия связи
          $sql = "select f_p.fieldname fld_p,f_c.fieldname fld_c from mb_object_link_field lnk inner join mb_object_field f_p on lnk.id_field_parent=f_p.id_field  "
          . " inner join mb_object_field f_c on lnk.id_field_child=f_c.id_field where id_link=$id_link";
          $res = kometa_query($sql);
          $lnk_w = '';

          while ($row = kometa_fetch_object($res)) {
            $lnk_w.=" and t." . $row->fld_p . "=$tab_prev." . $row->fld_c;
          }

          $this->FilterSQLWhereStr .= " and EXISTS(SELECT * from $tab_prev where 1=1 $lnk_w $ss_exists)";
          $ss_exists = '';
        }
        $tab_prev = $tab;
        $id_link = $id_lnk;
        if ($FilterCondition->multivalue) {
          $coma = '';
          $ss = '';
          foreach ($FilterCondition->field_value as $value) {
            $s = $this->AppendSQLCondition($oper_array, $FilterCondition->field_condition, $fld, $value);
            if ($s != '') {
              $ss .= $coma . $s;
              $coma = ' or ';
            }
          }

          if ($ss != '')
            $ss_exists .= " and ($ss)";
        } else {
          $s = $this->AppendSQLCondition($oper_array, $FilterCondition->field_condition, $fld, $FilterCondition->field_value);
          if ($s != '')
            $ss_exists .= " and " . $s;
        }
      } else {
        if (($tab_prev != '') && ($ss_exists != '')) {
          // опеределяем условия связи
          $sql = "select f_p.fieldname fld_p,f_c.fieldname fld_c from mb_object_link_field lnk inner join mb_object_field f_p on lnk.id_field_parent=f_p.id_field  "
          . " inner join mb_object_field f_c on lnk.id_field_child=f_c.id_field where id_link=$id_link";
          $res = kometa_query($sql);
          $lnk_w = '';

          while ($row = kometa_fetch_object($res)) {
            $lnk_w.=" and t." . $row->fld_p . "=$tab_prev." . $row->fld_c;
          }

          $this->FilterSQLWhereStr .= " and EXISTS(SELECT * from $tab_prev where 1=1 $lnk_w $ss_exists)";
        }
        $ss_exists = '';
        $tab_prev = '';
        if ($FilterCondition->multivalue) {
          $coma = '';
          $ss = '';
          foreach ($FilterCondition->field_value as $value) {
            $s = $this->AppendSQLCondition($oper_array, $FilterCondition->field_condition, $FilterCondition->field_code, $value);
            if ($s != '') {
              $ss .= $coma . $s;
              $coma = ' or ';
            }
          }

          if ($ss != '')
            $this->FilterSQLWhereStr .= " and ($ss)";
        } else {
          $s = $this->AppendSQLCondition($oper_array, $FilterCondition->field_condition, $FilterCondition->field_code, $FilterCondition->field_value);
          if ($s != '')
            $this->FilterSQLWhereStr .= " and " . $s;
        }
      }
    }
    if (($tab_prev != '') && ($ss_exists != '')) {
      // опеределяем условия связи
      $sql = "select f_p.fieldname fld_p,f_c.fieldname fld_c from mb_object_link_field lnk inner join mb_object_field f_p on lnk.id_field_parent=f_p.id_field  "
      . " inner join mb_object_field f_c on lnk.id_field_child=f_c.id_field where id_link=$id_link";
      $res = kometa_query($sql);
      $lnk_w = '';

      while ($row = kometa_fetch_object($res)) {
        $lnk_w.=" and t." . $row->fld_p . "=$tab_prev." . $row->fld_c;
      }

      $this->FilterSQLWhereStr .= " and EXISTS(SELECT * from $tab_prev where 1=1 $lnk_w $ss_exists)";
    }
    return true;
  }

  function ReadFilterConditionsDB($id_ObjectFilter) { //считывает условия фильтра из БД mb_filter_storage
    unset($this->FilterConditions);
    $this->FilterConditions = array();
    if ($id_ObjectFilter < 1) {
      $id_ObjectFilter = -1;
      $this->FilterCaption = 'Новый фильтр';
    } else {//читаем из БД
      $sql = "SELECT id_filter_storage, id_object, id_user, short_name, content from mb_filter_storage where id_filter_storage=$id_ObjectFilter";
      $res = kometa_query($sql);
      $row = kometa_fetch_object($res);
      $this->id_ObjectFilter = $row->id_filter_storage;
      $this->FilterCaption = $row->short_name;
      $this->id_object = $row->id_object;
      $this->id_user = $row->id_user;
      $FilterConditions = $row->content;
      $this->FilterConditions = json_decode($FilterConditions);
    }
    return TRUE;
  }

  function SaveFilterConditionsDB() {// сохраняет этот фильтр в БД
    global $ID_User;
    $FilterConditions = json_encode($this->FilterConditions);
    if ($this->id_user == $ID_User) {// Сохраняем только фильтры текущего пользователя
      if ($this->id_ObjectFilter < 1) {
        $sql = "insert into mb_filter_storage (id_object, id_user, short_name, content) "
        . " values($this->id_object, $ID_User, '$this->FilterCaption', '$FilterConditions')";
        kometa_query($sql);
        // получение ИД последнего
        $this->id_ObjectFilter = -1;
        $sql = "SELECT currval('sq_mb_storage_filteres') as cur";
        $res = kometa_query($sql);
        $row = kometa_fetch_object($res);
        $this->id_ObjectFilter = $row->cur;
        return $this->id_ObjectFilter;
      } else {
        $sql = "update mb_filter_storage set id_object=$this->id_object,"
        . " id_user=$ID_User, short_name= '$this->FilterCaption', content= '$FilterConditions'"
        . " where id_filter_storage=$this->id_ObjectFilter";
        kometa_query($sql);
      }
    }
  }

}

?>
