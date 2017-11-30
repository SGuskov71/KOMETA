<?php

/**
 * Description of Pivot_class
 *
 * @author d.khakhulin
 */
session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");


$gr_op_ = array(1 => 'sum', 2 => 'avg', 5 => 'count', 3 => 'min', 4 => 'max');
global $HierarhiSeparator;
global $sym_concat;
$HierarhiSeparator = '#';
if ($type_login == 3) {
  $sym_concat = '+';
} else {
  $sym_concat = '||';
}

class Pivot_class {

  function GetPivotObject($code_pivot, $id_object) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    if (isset($code_pivot)) {
      $sql = "SELECT id_object, short_name, description  FROM mb_pivot_storage where code=" . my_escape_string($code_pivot); //." ORDER BY order_view";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, '');
        return $result;
      }
      if ($row = kometa_fetch_object($res)) {
        $result = new JSON_Result(true, "", $row);
        $id_object = $row->id_object;
      } else {
        $result = new stdClass();
        $result = new JSON_Result(true, "", $result);
      }
    } else {
      $result = new stdClass();
    }
//Получение информации о массивах полей
    $sql = "SELECT fieldname,short_name from sv_mb_object_field_pivot where id_object=" . $id_object; //." ORDER BY order_view";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, '');
      return $result;
    }

    $a_list = array();
    while ($row = kometa_fetch_object($res)) {
      array_push($a_list, $row);
    }

    $sql = "select id_field,fieldname,short_name,NULL as gr_oper from sv_mb_object_fields_gr_oper where id_object=" . $id_object; //." ORDER BY order_view";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, '');
      return $result;
    }
    $a_list_gr = array();
    while ($row = kometa_fetch_object($res)) {
      $sql1 = "SELECT id_group_operation from mb_field_group_operation where id_field=" . $row->id_field;
      $res1 = kometa_query($sql1);
      $list_gr_op = array();
      while ($row1 = kometa_fetch_object($res1)) {
        array_push($list_gr_op, $row1->id_group_operation);
      }
      $row->gr_oper = $list_gr_op;
      array_push($a_list_gr, $row);
    }
    $sql = "select short_name from mb_object where id_object=" . $id_object;
    $res = kometa_query($sql);
    $row = kometa_fetch_object($res);
    $object_Caption = $row->short_name;

    $pivot_field = new Pivot_Field($a_list, $a_list_gr, $object_Caption);

    $result->result->pivot_field = $pivot_field;
    if (isset($result->result->description))
      $result->result->description = json_decode($result->result->description);
    else
      $result->result->description = null;
// $result = new JSON_Result(true, $s_err, $result);
    return $result;
  }

  function DeleteFromPivotList($id_pivot_storage) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if (!isset($id_pivot_storage)) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
//echo json_encode($result);
      return $result;
    }
    $sql = "delete   FROM mb_pivot_time_slice where id_pivot_storage=" . $id_pivot_storage;
    $res = kometa_query($sql);
    $sql = "delete   FROM mb_pivot_storage where id_pivot_storage=" . $id_pivot_storage;
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
// echo json_encode($result);
      return $result;
    } else {
      $result = new JSON_Result(true, 'Успешно удалено', NULL);
//echo json_encode($result);
      return $result;
    }
  }

  function SavePivotList($id_object, $short_name, $description, $code) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    global $ID_User;
    if (!isset($short_name)) {
      $result = new JSON_Result(false, 'Не определено имя сводной таблицы', NULL);
//echo json_encode($result);
      return $result;
    }
    $sql = "SELECT id_pivot_storage  FROM mb_pivot_storage where id_object=" . $id_object . " and id_user=$ID_User and short_name=" . my_escape_string($short_name);
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
//echo json_encode($result);
      return $result;
    }
    $CrossSettings_Code = $code;
    if (!isset($CrossSettings_Code) || empty($CrossSettings_Code)) {
      $CrossSettings_Code = GenerateUnicalCodeField('mb_pivot_storage', 'code');
    }
    if ($row = kometa_fetch_object($res)) {
// такое имя найдено заменяем
      $sql = "UPDATE mb_pivot_storage SET description=" . my_escape_string($description) . ", code='" . $CrossSettings_Code . "'  where id_pivot_storage=" . $row->id_pivot_storage;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
//echo json_encode($result);
        return $result;
      } else {
        $result = new JSON_Result(true, 'Успешно обновлено', NULL);
//echo json_encode($result);
        return $result;
      }
    } else {
// такое имя не найдено добавляем
      $sql = "INSERT INTO mb_pivot_storage(code, id_object, id_user, short_name, description)"
              . "VALUES ("
              . "'" . $CrossSettings_Code . "', "
              . $id_object
              . ", $ID_User, "
              . my_escape_string($short_name) . ", "
              . my_escape_string($description)
              . ")";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
// echo json_encode($result);
        return $result;
      } else {
        $result = new JSON_Result(true, 'Успешно добавлено', NULL);
//echo json_encode($result);
        return $result;
      }
    }
  }

  function GetPivotObjectForRun($pivot_code) {
    global $HierarhiSeparator;
    global $sym_concat;
    global $ID_User;
    $sql = "SELECT id_pivot_storage, id_object, id_user, short_name, description, code FROM mb_pivot_storage WHERE  code=" . my_escape_string($pivot_code);
    $result = $this->_GetPivotObject($sql);
    $result->result->header = null; //зачищаю ненужные данные
    $result->result->border = null; //зачищаю ненужные данные
    $result->result->HeaderColumnCounter = null; //зачищаю ненужные данные
    return $result;
  }

  function GetPivotHTML($pivot_code) {
    global $HierarhiSeparator;
    global $sym_concat;

    $result = '<html><head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
            . '</head><body>'
            . $this->_GetPivotSimpleHTML($pivot_code)
            . '</body></html>';

    return new JSON_Result(true, '', $result);
  }

  function _GetPivotSimpleHTML($pivot_code) {
    global $ID_User;
    global $header;
    global $_cols;
    global $b_cnt;
    global $s_html;
    global $HierarhiSeparator;
    global $sym_concat;
    $sql = "SELECT id_pivot_storage, id_object, id_user, short_name, description, code FROM mb_pivot_storage WHERE code=" .
            my_escape_string($pivot_code);
    $result = $this->_GetPivotObject($sql);
    $result->result->header = null; //зачищаю ненужные данные
    $result->result->border = null; //зачищаю ненужные данные
    $result->result->HeaderColumnCounter = null; //зачищаю ненужные данные

    $header = array();
    $_cols = array(); // массив имен колонок содержащих данные
    $s_html = '';
    $b_cnt = $result->result->border_cnt; // количество колонок в боковине

    function build_header($cols, $level) {
    global $HierarhiSeparator;
    global $sym_concat;
      global $header;
      global $_cols;
      if (!isset($header[$level]))
        $header[$level] = '';
      for ($i = 0; $i < count($cols); $i++) {
        $header[$level] = $header[$level] . '<td align=center ';
        if ($cols[$i]->colspan > 1)
          $header[$level] = $header[$level] . ' colspan=' . $cols[$i]->colspan;
        if ($cols[$i]->rowspan > 1)
          $header[$level] = $header[$level] . ' rowspan=' . $cols[$i]->rowspan;
        $header[$level] = $header[$level] . '>' . ($cols[$i]->text) . '</td>';
        if (isset($cols[$i]->columns)) {
          build_header($cols[$i]->columns, $level + 1);
        } else {
          array_push($_cols, $cols[$i]->dataIndex);
        }
      }
    }

    function build_data($children, $level) {
      global $HierarhiSeparator;
      global $sym_concat;
      global $s_html;
      global $_cols;
      global $b_cnt;
// вывод данных
      for ($i = 0; $i < count($children); $i++) {
        $s_html = $s_html . '<tr>';
// формируем ячейки боковины
        for ($k = 0; $k < $b_cnt; $k++) {
          $s_html = $s_html . '<td align=right>';
          if ($k == $level)
            $s_html = $s_html . ($children[$i]->treecolumn);
          else
            $s_html = $s_html . '<br>';
          $s_html = $s_html . '</td>';
        }
        for ($j = 1; $j < count($_cols); $j++) {// перебор колонок начина с 1 потомучто 0-вую нано обработать отдельно
          $s_html = $s_html . '<td align=right>';
          $colname = $_cols[$j];
          $colvalue = ($children[$i]->$colname);
          if (isset($colvalue)) {
            $colvalue = str_replace("#", '<br>', $colvalue);
            $s_html = $s_html . $colvalue;
          } else
            $s_html = $s_html . '<br>';
          $s_html = $s_html . '</td>';
        }
        $s_html = $s_html . '</tr>';
        if (isset($children[$i]->children)) {
          build_data($children[$i]->children, $level + 1);
        }
      }
    }

    build_header($result->result->GridColumnModel, 0);
    $s_html = '<table border=1 cellspacing=0 cellpadding=3 >';
// строю заголовок
    for ($i = 0; $i < count($header); $i++) {
      $s_html = $s_html . '<tr>' . $header[$i] . '</tr>';
    }
    build_data($result->result->GridData->children, 0);
    $s_html = $s_html . '</table>';
    return $s_html;
  }

  function _GetPivotObject($get_pivot_settings_sql) {
    global $gr_op_;
    global $HierarhiSeparator;
    global $sym_concat;
    global $type_login;
    if ($type_login == 3) {
      $sym_concat = '+';
    } else {
      $sym_concat = '||';
    }
    if (($res = kometa_query($get_pivot_settings_sql)) && ($row = kometa_fetch_object($res))) {
      $sysname = get_sysname($row->id_object);
      $id_object = $row->id_object;
      $id_pivot_storage = $row->id_pivot_storage;
      $pivot_code = $row->code;
      $pivot_name = $row->short_name;
      $description = json_decode($row->description);
      $sql = "SELECT ";
      $r = '_';
      $comma = "";
      if (count($description->group_field_array) == 0) {
        $result = new JSON_Result(false, 'Не определена ни одна групповая операция', null);
        return $result;
      }
      foreach ($description->group_field_array as $key => $value)
        if ($value[1] == 5) {
          $sql.= $comma . 'count(*) as value_' . $key;
          $comma = ',';
        } else {
          $g = $gr_op_[$value[1]];
          if ($value[1] == 2) {
            $fld = $value[3];
            $fld = "coalesce($fld,0)";
          } else {
            $fld = $value[3];
          }

          $sql.=$comma . " $g($fld) as value_$key";
          $comma = ',';
        }

// определяем группировки
      $gr_list_top = "";
      $gr_list_top_ = "";
      $comma = '';
      $comma_ = '';
      $top_field_descr = array();
      foreach ($description->top_field_array as $key => $value) {
        $gr_list_top.=$comma . $value;
        $comma = ',';
//      $gr_list_top_.= $comma_ . "''||replace(replace(replace(coalesce($value::text,''),'\\','\\\\'),'\"','\\\"'),'/','\/')||''";
        if ($type_login == 3)
          $gr_list_top_.= $comma_ . "''$sym_concat replace(replace(coalesce($value,''),'\\','\\\\'),'/','\/')$sym_concat''";
        else
          $gr_list_top_.= $comma_ . "''$sym_concat replace(replace(coalesce($value::text,''),'\\','\\\\'),'/','\/')$sym_concat''";
        $comma_ = "$sym_concat'$HierarhiSeparator'$sym_concat";
        array_push($top_field_descr, get_short_name_field($sysname, $value));
      }

      $gr_list_bord = "";
      $gr_list_bord_ = "";
      $comma = '';
      $comma_ = '';

      $sql_1 = $sql;

      $sql_union = '';
      $union = '';
      $border_cnt = 0;
      $top_cnt = 0;
      $border_field_descr = array();
      foreach ($description->border_field_array as $key => $value) {
        $border_cnt++;
        array_push($border_field_descr, get_short_name_field($sysname, $value));
        $gr_list_bord.=$comma . $value;
        $comma = ',';
//      $gr_list_bord_.=$comma_ . "''||replace(replace(replace(coalesce($value::text,''),'\\','\\\\'),'\"','\\\"'),'/','\/')||''";
        if ($type_login == 3)
          $gr_list_bord_.=$comma_ . "''$sym_concat replace(replace(coalesce($value,''),'\\','\\\\'),'/','\/')$sym_concat''";
        else
          $gr_list_bord_.=$comma_ . "''$sym_concat replace(replace(coalesce($value::text,''),'\\','\\\\'),'/','\/')$sym_concat''";
        $comma_ = "$sym_concat'$HierarhiSeparator'$sym_concat";

        $gr_list = '';
        $comma = '';
        $sql = $sql_1;

        if ($gr_list_bord != '') {
          $sql.=',' . $gr_list_bord_ . ' as border_fields';
          $gr_list.=$comma . $gr_list_bord;
          $comma = ',';
        }

        if ($gr_list_top != '') {
          $sql.=',' . $gr_list_top_ . ' as top_fields';
          $gr_list.=$comma . $gr_list_top;
          $comma = ',';
          $top_cnt++;
        }
        $sql.=" FROM " . $sysname;

        if ($gr_list != '') {
          $sql.=" GROUP BY " . $gr_list;
        }

        $sql_union.=$union . $sql;
        $union = ' UNION ' . PHP_EOL;
      }

      $res = kometa_query($sql_union);
      $pr = new CPivotResultObject();
      $pr->border_cnt = $border_cnt;
      $pr->top_cnt = $top_cnt;
      $pr->border_field_descr = $border_field_descr;
      $pr->top_field_descr = $top_field_descr;
      $pr->border_field_array = $description->border_field_array;
      $pr->top_field_array = $description->top_field_array;
      $pr->group_field_array = $description->group_field_array;
      $r = 0; // следующие kometa_fetch_object с начала
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $s_err = kometa_last_error();
        $result = new JSON_Result(false, my_escape_string($s_err), null);
        return $result;
      }
      while ($row = kometa_fetch_object($res, $r)) {
        $r = NULL; // перебор последовательно по одной записи
        $TempArray_t = explode($HierarhiSeparator, $row->top_fields);
        $TempArray_b = explode($HierarhiSeparator, $row->border_fields);
        $borderItem = $this->AddItemByHierarhy($pr->border, $pr, $TempArray_b, false);
        foreach ($description->group_field_array as $key => $value) {
          $tmp = $TempArray_t;
          $v = "$value[0]<br>$value[2]";
          $f = "value_$key";
          array_push($tmp, $v);
          $topItem = $this->AddItemByHierarhy($pr->header, $pr, $tmp, true);
          array_push($borderItem->NodeDataArray, new CNodeData($topItem, $row->$f));
        }
      }
      $pr->pivot_name = $pivot_name;
      $pr->id_pivot_storage = $id_pivot_storage;
      $pr->pivot_code = $pivot_code;
      $pr->sysname = $sysname;
      $pr->id_object = $id_object;
      $pr->GridModel = $pr->GetGridModel();
      $pr->GridColumnModel = $pr->GetGridColumnModel();
      $pr->GridData = $pr->GetGridData();
      $result = new JSON_Result(true, '', $pr);
      return $result;
    } else {
      $s_err = kometa_last_error();
      $result = new JSON_Result(false, my_escape_string($s_err), null);
      return $result;
    }
  }

  function FindItemByValue($ItemArray, $_val) {
    global $HierarhiSeparator;
    global $sym_concat;
    $result = null;
    foreach ($ItemArray as $value) {
      if ($value->NodeValue == $_val) {
        $result = $value;
        break;
      }
    }
    return $result;
  }

  function AddItemByHierarhy(&$_Obj, $PivotResultObject, $_Hierarhy, $isColumn = false) {//$_Hierarhy - массив иерархии
    global $HierarhiSeparator;
    global $sym_concat;
    $Items = $_Obj;
    foreach ($_Hierarhy as $value) {
      $Item = $this->FindItemByValue($Items->ChildNodes, $value);
      if (!isset($Item)) {
        $Item = new CPivotNodeItem($value);
        if ($isColumn == true)
          $Item->ColumnCode = $PivotResultObject->GetNewHeaderColumnName();
        else
          $Item->NodeDataArray = array();
        array_push($Items->ChildNodes, $Item);
      }
      $Items = $Item;
    }
    return $Item;
  }

}

Class Pivot_Field {

  public $field_pivot;
  public $field_gr_oper;
  public $object_Caption;

  function __construct($_field_pivot, $_field_gr_oper, $object_Caption) {
    $this->field_pivot = $_field_pivot;
    $this->field_gr_oper = $_field_gr_oper;
    $this->object_Caption = $object_Caption;
  }

}

Class CPivotResultObject { //объект результата анализа

  public $header;
  public $border;
  public $border_cnt;
  public $top_cnt;

//  var $HeaderColumnCounter;

  function __construct() {
    $this->header = new CPivotNodeItem('Столбцы');
    $this->border = new CPivotNodeItem('Строки');
    $this->HeaderColumnCounter = 0;
  }

  function GetNewHeaderColumnName() {
    $this->HeaderColumnCounter++;
    return 'Col_' . $this->HeaderColumnCounter;
  }

  function FindInFieldArray($FieldArray, $_val) {
    $result = false;
    foreach ($FieldArray as $value) {
      if ($value->name == $_val) {
        $result = true;
        break;
      }
    }
    return $result;
  }

  function GetSubField($ColArray, &$DestArray) {
    foreach ($ColArray as $value) {
      $item = null;
      $item->name = $value->ColumnCode;
      $item->type = 'string';
      $item->flex = 1;
      if (!$this->FindInFieldArray($DestArray, $item->name))
        array_push($DestArray, $item);
      if (count($value->ChildNodes) > 0) {
        $this->GetSubField($value->ChildNodes, $DestArray);
      }
    }
  }

  function GetGridModel() {
    $result = array();
    $item = null;
    $item->name = 'treecolumn';
    $item->flex = 1;
    $item->type = 'string';
    array_push($result, $item);
    foreach ($this->header->ChildNodes as $value) {
      $item = null;
      $item->name = $value->ColumnCode;
      $item->type = 'string';
      if (!$this->FindInFieldArray($result, $item->name))
        array_push($result, $item);
      if (count($this->header->ChildNodes) > 0) {
        $this->GetSubField($this->header->ChildNodes, $result);
      }
    }
    return $result;
  }

  function GetSubColumn($ColArray, &$DestArray) {
    $result = 0;
    foreach ($ColArray as $value) {
      $item = null;
      $item->text = $value->NodeValue;
      $item->flex = 1;
      $item->align = 'right';
      $item->sortable = false;
      $item->resizable = false;
      $item->dataIndex = $value->ColumnCode;
      $item->menuDisabled = true;
      $i = count($value->ChildNodes);
      if (count($value->ChildNodes) > 0) {
        $item->columns = array();
        $item->colspan = $item->colspan + $this->GetSubColumn($value->ChildNodes, $item->columns);
      } else {
        $item->colspan = $item->colspan + 1;
      }
      $result = $result + $item->colspan;
      array_push($DestArray, $item);
    }
    return $result;
  }

  function GetGridColumnModel() {
    $result = array();
    $item = null;
    $item->xtype = 'treecolumn';
    $item->text = 'Группировка:<br>';

    for ($i = 0; $i < $this->border_cnt; $i++) {
      $item->text .= str_repeat('-', $i + 1) . '>' . $this->border_field_descr[$i] . '<br>';
    }
// $item->flex = 1;
    $item->width = 160;
    $item->sortable = false;
    $item->dataIndex = 'treecolumn';
    $item->enableColumnHide = false;
    $item->enableColumnMove = false;
    $item->locked = true;
    $item->menuDisabled = true;
    $item->colspan = $this->top_cnt;
    $item->rowspan = $this->border_cnt + 1;

    array_push($result, $item);
    if (count($this->header->ChildNodes) > 0) {
      $this->GetSubColumn($this->header->ChildNodes, $result);
    }
    return $result;
  }

  function GetSubData($RowArray, &$DestArray) {
    foreach ($RowArray as $value) {
      $item = null;
      $item->treecolumn = $value->NodeValue;
      $item->expanded = true;
      if (count($value->NodeDataArray) > 0) {
        foreach ($value->NodeDataArray as $DataItem) {
          $ColName = $DataItem->ColumnReference->ColumnCode;
          $item->$ColName = $DataItem->Value;
        }
      }
      if (count($value->ChildNodes) > 0) {
        $item->children = array();
        $this->GetSubData($value->ChildNodes, $item->children);
      }
      array_push($DestArray, $item);
    }
  }

  function GetGridData() {
    $result->text = ".";
    $result->children = array();
    if (count($this->border->ChildNodes) > 0) {
      foreach ($this->border->ChildNodes as $value) {
        $item = null;
        $item->treecolumn = $value->NodeValue;
        $item->expanded = true;
        if (count($value->NodeDataArray) > 0) {
          foreach ($value->NodeDataArray as $DataItem) {
            $ColName = $DataItem->ColumnReference->ColumnCode;
            $item->$ColName = $DataItem->Value;
          }
        }
        if (count($value->ChildNodes) > 0) {
          $item->children = array();
          $this->GetSubData($value->ChildNodes, $item->children);
        }
        array_push($result->children, $item);
      }
    }
    return $result;
  }

  function GetComparedSubData($RowArray, &$DestArray, $SecondSubData, $operation, $valueSeparator) {
    $x = 0;
    foreach ($RowArray as $value) {
      $item = null;
      $item->treecolumn = $value->NodeValue;
      $item->expanded = true;
      $SecondItem = $SecondSubData->ChildNodes[$x];
      if (count($value->NodeDataArray) > 0) {
        $n = 0;
        foreach ($value->NodeDataArray as $DataItem) {
          $ColName = $DataItem->ColumnReference->ColumnCode;
          if (!(($DataItem->Value == undefined) && ($SecondItem->NodeDataArray[$n]->Value == undefined)))
            $item->$ColName = $DataItem->Value . $valueSeparator . $SecondItem->NodeDataArray[$n]->Value;
          else
            $item->$ColName = null;
          if ($item->$ColName != undefined) {
            if ($operation = 1) {
              $operRes = intval($DataItem->Value) - intval($SecondItem->NodeDataArray[$n]->Value);
              $item->$ColName.=$valueSeparator . $operRes;
            }
          }
          $n++;
        }
      }
      if (count($value->ChildNodes) > 0) {
        $item->children = array();
        $this->GetComparedSubData($value->ChildNodes, $item->children, $SecondItem, $operation, $valueSeparator);
      }
      array_push($DestArray, $item);
      $x++;
    }
  }

  function GetComparedGridData($SecondGridData, $operation, $valueSeparator) {
    $result->text = ".";
    $result->children = array();
    if (count($this->border->ChildNodes) > 0) {
      $x = 0;
      foreach ($this->border->ChildNodes as $value) {
        $item = null;
        $item->treecolumn = $value->NodeValue;
        $item->expanded = true;
        $SecondItem = $SecondGridData->border->ChildNodes[$x];
        if (count($value->NodeDataArray) > 0) {
          $n = 0;
          foreach ($value->NodeDataArray as $DataItem) {
            $ColName = $DataItem->ColumnReference->ColumnCode;
            if (!(($DataItem->Value == undefined) && ($SecondItem->NodeDataArray[$n]->Value == undefined)))
              $item->$ColName = $DataItem->Value . $valueSeparator . $SecondItem->NodeDataArray[$n]->Value;
            else
              $item->$ColName = null;
            if ($item->$ColName != undefined) {
              if ($operation = 1) {
                $operRes = intval($DataItem->Value) - intval($SecondItem->NodeDataArray[$n]->Value);
                $item->$ColName.=$valueSeparator . $operRes;
              }
            }
            $n++;
          }
        }
        if (count($value->ChildNodes) > 0) {
          $item->children = array();
          $this->GetComparedSubData($value->ChildNodes, $item->children, $SecondItem, $operation, $valueSeparator);
        }
        array_push($result->children, $item);
        $x++;
      }
    }
    return $result;
  }

}

Class CNodeData { //объект где хранятся данные единственной ячейки таблицы

  public $ColumnReference;
  public $Value;

  function __construct($_ColumnReference, $_Value) {
    $this->ColumnReference = $_ColumnReference;
    $this->Value = $_Value;
  }

}

Class CPivotNodeItem {//ветка древовидного объекта CPivotResultObjectCPivotResultObject->border(или header)

  public $NodeValue;
  public $ChildNodes; //массив дочерних веток
  public $NodeDataArray; //массив объетов содержащий соответствия колонок и значений текущей записи типа CNodeData
  public $ColumnCode; //для объекта header уникальные имена колонок

  function __construct($NodeValue) {
    $this->NodeValue = $NodeValue;
    $this->ChildNodes = array();
  }

}
