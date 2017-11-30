<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/Field_class.php");

class SimpleGrid_class {

//статические свойства  объекта считанные из метабазы
  public $sysname; //код объекта
  public $id_object; //ID объекта
  public $code_help; //код справки объекта
  public $add_where; //условие выборки данных объекта читается из настроек БД
  public $key_fld_list; //массив это ключевые поля
//    for (key in $key_fld_list) {
//  /* ... делать что-то с $key_fld_list[key] ... */
//}
  public $code_fld; //кодовое поле объекта
  public $descr_fld_list; //массив это характеризующие поля
  public $descr_fld_connector; //строка связки характеризующих полей
  public $ObjectCaption; //описание объекта
  public $readonly_fld; // имя поля в котором лежит признак объекта - тоько для чтения
//поля объекта
  public $field_list; //именованный массив объект свойства которого это поля
  //именами свойств объекта field_list являются код поля метабазы а значения свойств это объекты типа Field_class
//динамические свойства для отбора данных отображаемых в гриде
  public $GetDataSQL; //текст запроса на выборку данных
  public $pageSize; //количество записей в выборке данных
  // public $CurrentPage; //Текущая страница в выборке данных
  public $FilterWhereCond; //условие SQL запроса сформированное фильтром
  public $joins; //условие SQL запроса сформированное отношением мастер детал
  public $ExtFilterWhereCond; //условие SQL запроса сформированное внешней процедурой вызвавшей объект
//объектые свойства объекта   SimpleGrid
  // public $GridSettings; //объект настроек отображения грида на клиенте
  public $FilterComboData; //Данные для комбо  фильтра на клиенте
  public $list_operation; //массив объектов операций

  const GridSettings_Preffix = 'GridSettings_';

  function __construct() {
    //нужно создать объектные свойства
    $this->key_fld_list = array();
    $this->descr_fld_list = array();
    $this->field_list = array();
    $this->FilterComboData = array();
    $this->list_operation = array();
  }

  //получение значения ключевого поля для записи
  //вызывать из других мест через пространство имен класса SimpleGrid_class::GetIDRecordValue
  //для единообразия способ формирования ключа должен быть описан в одном месте
  //из JS можно вызыват через direct SimpleGrid_class.GetIDRecordValue
  function GetIDRecordValue($row, $key_fld_list) {
    $res = '';
    $coma = '';
    foreach ($key_fld_list as $Field) {
      $res = $res . $coma . $Field . ':' . $row->$Field;
      $coma = ';';
    }
    return $res;
  }

  public function Create($sysname, $Params) {
    global $type_login; //тип используемой субд напр 3 это MSSQL
    $this->sysname = $sysname;
    //  $this->CurrentPage = 1;
    $this->FilterWhereCond = '';
    $this->joins = '';
    $this->ExtFilterWhereCond = $Params->ExtFilterWhereCond;
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);

      global $ID_User;

      $this->key_fld_list = get_key_field_as_array($sysname);
      $this->readonly_fld = get_readonly_fld($sysname);
      if (!isset($this->key_fld_list)) {
        $kometa_last_error = "Не определено ключевое поле для объекта $sysname";
        return $result = new JSON_Result(false, $kometa_last_error, NULL);
      }
      $this->code_fld = get_code_field($sysname);
      $this->descr_fld_list = get_descr_field_as_array($sysname);
      if (array_count_values($this->descr_fld_list) == 0) {
        array_push($this->descr_fld_list, $this->code_fld);
      }

      $res = kometa_query("SELECT id_object, id_object_type, id_edit_object, sysname, short_name, full_name, id_group, code_help, connector, add_where "
      . "from mb_object where sysname='$sysname'");
      $row = kometa_fetch_object($res);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        return $result = new JSON_Result(false, $s_err, NULL);
      }
      $this->descr_fld_connector = $row->connector;
      $this->code_help = $row->code_help;
      $this->id_object = $row->id_object;
      eval("\$add_where = \"$row->add_where\";");
      $this->add_where = $add_where;
      $this->ObjectCaption = get_object_descr($this->id_object);

      $CaptionSQL = "select f.is_visibility, f.fieldname, f.short_name, f.full_name, f.display_mask, f.is_field_key, "
      . " f.is_filter_use, f.order_view, f.id_field_dtype,"
      . " t.code as typecode, f_st.fieldname as code_field_style, dt.code as type_field_EXTJS "
      . " from mb_object_field as f "
      . " left join mb_field_type as t on t.id_field_type=f.id_field_type "
      . " left join mbs_datatype as dt on dt.id_datatype=t.id_datatype"
      . " left join mb_object_field as f_st on f_st.id_field=f.id_field_style"
      . " where f.id_object=$this->id_object order by f.order_view";
      $res = kometa_query($CaptionSQL);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        return $result = new JSON_Result(false, $s_err, NULL);
      }

      $this->GetDataSQL = 'SELECT '; //текст запроса на выборку данных

      $comma = '';
      while ($row = kometa_fetch_object($res)) {
        $FieldObject = new Field_class;
        $FieldObject->fieldname = $row->fieldname; //код поля
        $FieldObject->type_field = $row->typecode; //тип поля в понимании доменов метабазы
        $FieldObject->type_field_EXTJS = $row->type_field_extjs; //тип поля в понимании EXJS
        $FieldObject->seachable = $row->is_filter_use; //поисковое
        $FieldObject->code_field_style = $row->code_field_style; //имя поля для стиля
        $FieldObject->order_view = $row->order_view; //порядок отображения колонок
        $FieldObject->create_grid_column = $row->is_visibility == 1; //создавать колонку грида
        $FieldObject->visible_in_grid = $row->is_visibility == 1; //видимость в гриде
        $FieldObject->short_name = $row->short_name; //краткое наим
        $FieldObject->full_name = $row->full_name; //полное наим
        //  $FieldObject->column_width = $row->width; //ширина колонки
        $FieldObject->render_mask = $row->display_mask; //маска форматирования значения в колонке
        $FieldObject->render_type = $row->id_field_dtype; // ключ типа рендера формата поля из таблицы mbs_field_dtype

        if ($type_login == 3) {
          if ($FieldObject->type_field == 't_date')
            $fld = "substring(convert(varchar,$FieldObject->fieldname,120),1,11) as $FieldObject->fieldname";
          else if ($FieldObject->type_field == 't_timestamp')
            $fld = "convert(varchar,$FieldObject->fieldname,120)  as $FieldObject->fieldname";
        } else
          $this->GetDataSQL .= $comma . $FieldObject->fieldname;
        $comma = ',';

        //array_push($this->field_list, $FieldObject);
        $this->field_list[$FieldObject->fieldname] = $FieldObject;
        unset($FieldObject);
      }

      $this->GetDataSQL .= " FROM $sysname as t WHERE 1=1 ";
//      if (isset($this->add_where) && (trim($this->add_where) != ''))
//        $this->GetDataSQL .= ' and ' . $this->add_where;
      //
      //получаю настройки грида
      $GridSettings = json_decode(get_Param_value(self::GridSettings_Preffix . $this->sysname, get_id_user()));
      $this->pageSize = $GridSettings->pageSize;
      //цикл порядка отображения из $GridSettings
      foreach ($GridSettings->columns as $Key) {//восстанавливаю настроики для текущего объекта
        $FieldObject = $this->field_list[$Key->dataIndex];
        $FieldObject->visible_in_grid = $Key->visible;
        $FieldObject->column_width = $Key->width;
        $FieldObject->order_view = $Key->VisibleIndex;
      }
      foreach ($this->field_list as $Key) {//непонятные индексы отображения гоним в конец
        if (!is_numeric($Key->order_view))
          $Key->order_view = 100500;
        else if ($Key->order_view < 0)
          $Key->order_view = 100500;
      }
      //отсортировать $this->field_list по порядку отображения
      usort($this->field_list, function($a, $b) {
        return ($a->order_view - $b->order_view);
      });
      //заполняю массив фильтров
      $sql = "SELECT id_filter_storage, id_object, id_user, short_name, content from mb_filter_storage where id_object=$this->id_object and (id_user=$ID_User or id_user is null)";
      $res = kometa_query($sql);
      array_push($this->FilterComboData, array('-1', 'Не выбран'));
      while ($row = kometa_fetch_object($res)) {
        array_push($this->FilterComboData, array($row->id_filter_storage, $row->short_name));
      }

      //заполняю массив операций
      $sql = "SELECT mb_object_operation.id_object_operation,mb_object_operation.code, "
      . "mb_object_operation.short_name, mb_object_operation.full_name, mb_object_operation.op_style,   "
      . "mb_object_operation.is_available, mb_object_operation.is_default_operation, func_class_name, func_name, param_list  "
      . "FROM mb_object_operation,mb_link_operation_object "
      . "where mb_object_operation.id_object_operation=mb_link_operation_object.id_object_operation"
      . " and id_object=$this->id_object order by mb_link_operation_object.btn_number";

      $res = kometa_query($sql);
      while ($row = kometa_fetch_object($res)) {
        $sql1 = "SELECT id_object_operation,code, short_name, full_name, op_style,  "
        . "is_available, is_default_operation, func_class_name, func_name, param_list "
        . "FROM mb_object_operation where id_parent=" . $row->id_object_operation;

        $res1 = kometa_query($sql1);
        $list_operation1 = array();
        while ($row1 = kometa_fetch_object($res1)) {
          array_push($list_operation1, $row1);
        }
        if (array_count_values($list_operation1) > 0) {
          $row->list_operation = $list_operation1;
        }
        array_push($this->list_operation, $row);
      }

      return $result = new JSON_Result(true, '', $this);
    } else {
      return $result;
    }
  }

  public function GetGridData($Params) {
    $SimpleGridObject = $Params->SimpleGridObject;
    //  $this->Init($SimpleGridObject);
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $result = null;

      $sqlSelect = $SimpleGridObject->GetDataSQL;
//присваиваю условие
      if (isset($SimpleGridObject->add_where) && (trim($SimpleGridObject->add_where) != ''))
        $sqlSelect = $sqlSelect . " " . $SimpleGridObject->add_where; //условие выборки данных объекта читается из настроек БД
      if (isset($SimpleGridObject->FilterWhereCond) && (trim($SimpleGridObject->FilterWhereCond) != ''))
        $sqlSelect = $sqlSelect . " " . $SimpleGridObject->FilterWhereCond; //условие SQL запроса сформированное фильтром
      if (isset($SimpleGridObject->joins) && (trim($SimpleGridObject->joins) != ''))
        $sqlSelect = $sqlSelect . " " . $SimpleGridObject->joins; //условие SQL запроса сформированное отношением мастер детал
      if (isset($SimpleGridObject->ExtFilterWhereCond) && (trim($SimpleGridObject->ExtFilterWhereCond) != ''))
        $sqlSelect = $sqlSelect . " " . $SimpleGridObject->ExtFilterWhereCond; //условие SQL запроса сформированное внешней процедурой вызвавшей объект

      $SORT_FIELD = $Params->sort;
      if (isset($SORT_FIELD)) {
        $coma_sort = '';
        $sqlOrderBy = " order by ";
        foreach ($SORT_FIELD as $out) {
          $sqlOrderBy .= "$coma_sort " . $out->property . " " . $out->direction;
          $coma_sort = ', ';
        };
      } else {
        // сортировки по умолчанию из БД
        $sort_fld = '';
        $coma_sort = '';
        foreach ($SimpleGridObject->field_list as $fld) {
          if ($fld->sort_flag === 1) {
            $sort_fld .= $coma_sort . $fld->fieldname . ' asc ';
            $coma_sort = ', ';
          } elseif ($fld->sort_flag === 1) {
            $sort_fld .= $coma_sort . $fld->fieldname . ' desc ';
            $coma_sort = ', ';
          }
        }
        if ($sort_fld != '')
          $sqlOrderBy = " order by $sort_fld ";
        else
          $sqlOrderBy = '';
      }
      $sqlOut = $sqlSelect . $sqlOrderBy;

      $start = $Params->start;
      $limit = $Params->limit;

      $res = kometa_query($sqlOut);

// Построение результирующей таблицы данных
      function TeloCiklaXaebalssiaPridumivatImiaDlaVseXTipovDB($row, $SimpleGridObject) {//вернет объект для вывода записи
        $result = new stdClass();
        $result->id = SimpleGrid_class::GetIDRecordValue($row, $SimpleGridObject->key_fld_list);
        foreach ($SimpleGridObject->field_list as $Field) {
          $outField = $Field->fieldname;
          $s = $row->$outField;
          if (!isset($s) || (trim($s) == ''))
            $s = "";
          else {
            $s = str_replace(PHP_EOL, '<br>', $s);
            $s = str_replace("\n", '<br>', $s);
            $s = htmlspecialchars($s);
            $s = trim($s);
            if ($Field->render_type == 1)
              $s = htmlspecialchars($s);
            else if ($Field->render_type == 5) {
              $s = '<table border = 0 width = 100%><tr><td>' . $s . '</td><td><input type = "button" onclick = "htmlShowDialog(\'' . $_SESSION['html_doc'] . '/' . urldecode($s) . '\');" value = "Показать"></td></tr></table>';
            } else if ($Field->render_type == 6) {
//!!! это надо исправить на постановку файла из хранилища
              //  $s = "<img width=" . $arFld_width[$outField] . " hspace=0 vspace=0 src=\"" . $_SESSION['bd_img'] . "/$s\" >";
            } else if ($Field->render_type == 7) {
              //!!! это надо исправить
              //    $s = "<input type=\"button\" onclick=\"html_from_field_ShowDialog('$SimpleGridObject->sysname', '$outField', $key);\" value=\"Показать\">";
            }
          }
          $result->$outField = $s;
        };
        return $result;
      }

      $count = kometa_num_rows($res);
      if ($count < $start)
        $start = 0;
      $OutData = new stdClass();
      $OutData->total = $count;
      $OutData->results = array();
//      $OutData = '({"total": ' . $count . ', "results":[';
      $coma_row = '';
      if ($type_login == 3) { // это субд MSSQL
        $sqlOut = $sqlSelect . $sqlOrderBy; //. " LIMIT $limit OFFSET $start";
        $res = kometa_query($sqlOut);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          return $result = new JSON_Result(false, $sqlOut . ' ' . $s_err, $sqlOut . ' ' . $s_err);
        }
        $sn = $start;
        $n = 0;
        while (($row = kometa_fetch_object($res, $sn)) && ($n < $limit)) {
          $sn = null;
          $n++;
          $record = TeloCiklaXaebalssiaPridumivatImiaDlaVseXTipovDB($row, $SimpleGridObject);
          array_push($OutData->results, $record);
        }
      } else {
        $sqlOut = $sqlSelect . $sqlOrderBy . " LIMIT $limit OFFSET $start";
        $res = kometa_query($sqlOut);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          return $result = new JSON_Result(false, $sqlOut . ' ' . $s_err, $sqlOut . ' ' . $s_err);
        }
        while ($row = kometa_fetch_object($res)) {
          $record = TeloCiklaXaebalssiaPridumivatImiaDlaVseXTipovDB($row, $SimpleGridObject);
          array_push($OutData->results, $record);
        }
      }
      // $OutData .= ']})';
//      return $result = new JSON_Result(true, '', $OutData);
      return $OutData;
    } else {
      return $result;
    }
  }

  public function SaveGridSettings($GridSettingsObject) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $code = self::GridSettings_Preffix . $GridSettingsObject->sysname;
      $GridSettings = json_encode($GridSettingsObject->GridSettings);
      $result = set_Param_value($code, "Настройки табличного представления объекта $GridSettingsObject->sysname", $GridSettings, get_id_user());
      return $result = new JSON_Result(true, '', $result);
    } else {
      return $result;
    }
  }

}
