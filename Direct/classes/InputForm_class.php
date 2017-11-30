<?php

/*
 * проектирование форм ввода
 */
session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

class InputForm_class {

  function get_child_object() {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    // получение id и sysname связанного объекта
    $sql = "SELECT mb_object.id_object,mb_object.sysname FROM mb_object_link inner join mb_object on mb_object_link.id_object_child = mb_object.id_object where id_link=" . $_GET['id_link'];
    $result = kometa_query($sql);
    return $result;
  }

  function get_value_descr_by_id() {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $res = get_value_descr_by_id($_GET['sysname'], $_GET['id_obj']);
    return $res;
  }

  function GetListInputForm() {//получить список
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $sql = "SELECT f.*, o.short_name as name_object FROM mb_form f left join mb_object o on f.id_object=o.id_object order by ord";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    $_list = array();
    while ($row = kometa_fetch_object($res))
      array_push($_list, $row);
    $result = new JSON_Result(true, $s_err, $_list);
    return $result;
  }

  function LoadInputForm($code, $id_form) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if ((!isset($code) || ($code === '')) && (!isset($id_form) || ($id_form === ''))) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      return $result;
    }
    if (isset($code) && ($code != ''))
      $sql = "SELECT * FROM mb_form where code='$code'";
    else
      $sql = "SELECT * FROM mb_form where id_form='$id_form'";

    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    if (kometa_num_rows($res) == 0) {
      $result = new JSON_Result(false, 'Форма ввода не найдена', NULL);
      return $result;
    }
    $row = kometa_fetch_object($res);
    $result->InputFormTemplate = json_decode($row->content);
    $result = new JSON_Result(true, $row->short_name, $result);
    return $result;
  }

  function InitObject($id_form, $id_object) {

    function work_container(&$form, $id_object) {

      foreach ($form as $key => $value) {
        if (isset($form[$key]->DataField) && ($form[$key]->DataField != '')) {
          // проверяем есть ли такое поле
          $sql = "SELECT id_field from mb_object_field where fieldname='" . $form[$key]->DataField . "' and id_object=$id_object";
          $res = kometa_query($sql);
          if (kometa_num_rows($res) == 0) {
            unset($form[$key]);
          }
        } 
        if ($form[$key]->InputType == "pickerfield") {
          $sn = $form[$key]->SLVObject;
          $id = get_id_object($sn);
          if ((!isset($id)) || ($id == '')) {
            $form[$key]->SLVObject = '';
            $form[$key]->SLVObjectDescr = '';
          }
          $sn1 = $form[$key]->SLVObject4Display;
          $id=get_id_object($sn1);
          if ((! isset($id)) ||($id == '')) {
            $form[$key]->SLVObject4Display = '';
            $form[$key]->SLVObjectDescr4Display = '';
          }
        } 
        if (($form[$key]->ItemType == 'container') || ($form[$key]->ItemType == 'form')) {
          $result = work_container($form[$key]->children, $id_object);
          if (($result) && ($result->success == false)) {
            return $result;
          }
        }
      }
      return null;
    }

    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

//подгружаю дополнительные свойства
    $InputControlPropsArray = Array();
    include('InputForm_include/InputControlProps.php');
    $InputContainerProps = new stdClass();
    include('InputForm_include/InputContainerProps.php');
    $labelProps = new stdClass();
    include('InputForm_include/labelProps.php');
    $InputContainerLayouts = Array();
    include('InputForm_include/InputContainerLayouts.php');

    if (!isset($id_form)) {//новый
      $result->InputFormTemplate->text = 'Форма ввода';
      $result->InputFormTemplate->expanded = true;
      $result->InputFormTemplate->leaf = false;
      $result->InputFormTemplate->Code = GenerateUnicalCodeField('mb_form', 'code');
      $result->InputFormTemplate->iconCls = 'report';
      $result->InputFormTemplate->children = array();
      $result->InputFormTemplate->ItemType = 'form';
      $result->InputFormTemplate->Layout = 'auto';
      $result->InputFormTemplate->id_object = $id_object;
      $result->InputFormTemplate->form_width = 800;
      $result->InputFormTemplate->form_height = 600;
      $result->InputFormTemplate->id_objectDescription = get_object_descr($id_object);
      $result->InputFormTemplate->Description = 'Новая Форма ввода';
      $result->InputFormTemplate->ExpressionBeforeShow = '';
      $result->InputFormTemplate->ContainerProps = new stdClass();
      $result->InputFormTemplate->ShowOrder = 1;

      $result->InputControlPropsArray = $InputControlPropsArray;
      $result->InputContainerProps = $InputContainerProps;
      $result->labelProps = $labelProps;
      $result->InputContainerLayouts = $InputContainerLayouts;

      $result = new JSON_Result(true, '', $result);
      return $result;
    }
    $sql = "SELECT * FROM mb_form  "
            . " where id_form='" . $id_form . "'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    if ($row = kometa_fetch_object($res)) {
      $result->InputFormTemplate = json_decode($row->content);

      work_container($result->InputFormTemplate->children, $row->id_object);
      $result->InputFormTemplate->Code = $row->code;
      $result->InputFormTemplate->ShowOrder = $row->ord;
      $result->InputFormTemplate->id_object = $row->id_object;
      $result->InputFormTemplate->id_objectDescription = get_object_descr($result->InputFormTemplate->id_object);

      $result->InputControlPropsArray = $InputControlPropsArray;
      $result->InputContainerProps = $InputContainerProps;
      $result->labelProps = $labelProps;
      $result->InputContainerLayouts = $InputContainerLayouts;

      $result = new JSON_Result(true, '', $result);
      return $result;
    } else {
      $result = new JSON_Result(false, 'Пусто', NULL);
      return $result;
    }
  }

  function SaveInputFormTemplate($InputFormTemplate) {//сохранение шаблона в БД
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    //$InputFormTemplate = json_decode($InputFormTemplate);
    //зачищаю ненужные свойства
    $id_form = $InputFormTemplate->id_form;
    $id_object = $InputFormTemplate->id_object;
    unset($InputFormTemplate->id_form);
    unset($InputFormTemplate->id_object);
    unset($InputFormTemplate->id_objectDescription);
    $Code = $InputFormTemplate->Code;
    unset($InputFormTemplate->Code);
    $ShowOrder = $InputFormTemplate->ShowOrder;
    unset($InputFormTemplate->ShowOrder);

    if (isset($id_form)) {
      $sql = "SELECT id_form FROM mb_form where id_form=$id_form";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      }

      $sql = "SELECT * FROM mb_form where id_form<>$id_form and code='$Code'";
      $res1 = kometa_query($sql);
      $row1 = kometa_fetch_object($res1);
      $s_err = kometa_last_error();
      if ($row1) {
        $result = new JSON_Result(false, "Форма с кодом '$Code' существует. Введите другой код.", NULL);
        return $result;
      }
    }
    if ($row = kometa_fetch_object($res)) {
      $sql = "UPDATE mb_form SET short_name=" . my_escape_string($InputFormTemplate->Description)
              . ", content =" . my_escape_string(json_encode($InputFormTemplate))
              . ", ord =" . $ShowOrder
              . ", code ='$Code'"
              . "  where id_form=" . $row->id_form;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, my_escape_string($s_err), NULL);
        return $result;
      } else {
        $result = new JSON_Result(true, 'Успешно обновлено', $id_form);
        return $result;
      }
    } else {
// такое  не найдено добавляем
      $sql = "INSERT INTO mb_form(code, id_object, short_name, ord, content)"
              . "VALUES ("
              . "'$Code', $id_object, "
              . my_escape_string($InputFormTemplate->Description) . ", "
              . $ShowOrder . ", "
              . my_escape_string(json_encode($InputFormTemplate)) . ")";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, my_escape_string($s_err), NULL);
        return $result;
      } else {
        $sql = "SELECT id_form FROM mb_form where code='$Code'"; //получаю ид добавленной записи по коду
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $result = new JSON_Result(false, $s_err, NULL);
          return $result;
        } else {
          $row = kometa_fetch_object($res);
          $result = new JSON_Result(true, 'Успешно добавлено', $row->id_form);
          return $result;
        }
      }
    }
  }

  function GetListField($Params) { //список полей объекта для выбора поля в контроле
    $id_object = $Params->id_object;
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $sql = "select o_f.id_field, o_f.id_object, o_f.id_field_type, o_f.id_field_dtype, o_f.id_filter_type, o_f.id_field_style, 
       o_f.is_visibility, o_f.id_slv_object, o_f.fieldname, o_f.short_name, 
       o_f.full_name, o_f.multi_value, o_f.is_field_key, o_f.is_field_code, o_f.mandatory, 
       o_f.is_descr, o_f.is_field_parent, o_f.is_field_history, o_f.is_field_readonly, 
       o_f.is_filter_use, o_f.display_mask, f_t.input_check_condition from mb_object_field o_f "
            . " left join mb_field_type as f_t on o_f.id_field_type=f_t.id_field_type"
            . " where o_f.id_object=$id_object ";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    $_list = array();
    while ($row = kometa_fetch_object($res))
      array_push($_list, $row);
    $result = new JSON_Result(true, $s_err, $_list);
    return $result;
  }

  function Check_id_object_type($id_object) { //проверить объект соответствующий id_object на предмет его типа (поле id_object_type таблицы mb_object. Если это не 1 то ругаться)
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $sql = "select id_object_type from mb_object where id_object=$id_object";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    $row = kometa_fetch_object($res);
    if ($row->id_object_type == 1) {
      $result = new JSON_Result(true, $s_err, NULL);
    } else {
      $result = new JSON_Result(false, 'id_object_type<> 1 Операция невозможна', NULL);
    }
    return $result;
  }

  function GetInputContainerLayouts() {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $InputContainerLayouts = Array();
    include('InputForm_include/InputContainerLayouts.php');
    $result = new JSON_Result(true, '', $InputContainerLayouts);
    return $result;
  }

  function ReadForms($id_object, $id_form) {
    global $id_edit_object;
    $id_edit_object = '';

    function work_form(&$form) {
      global $type_login;
      global $id_edit_object;
      foreach ($form as $key => $value) {
        if (isset($form[$key]->DataField) && ($form[$key]->DataField != '')) {
          // проверяем есть ли такое поле
          $sql = "SELECT id_field from mb_object_field where fieldname='" . $form[$key]->DataField . "' and id_object=$id_edit_object";
          $res = kometa_query($sql);
          if (kometa_num_rows($res) == 0) {
            unset($form[$key]);
          }
        } 
        if (($form[$key]->ItemType == 'container') || ($form[$key]->ItemType == 'form')) {
          $result = work_form($form[$key]->children);
          if (($result) && ($result->success == false)) {
            return $result;
          }
        } 
        if ($form[$key]->InputType == "pickerfield") {
          $sn = $form[$key]->SLVObject;
          if ($sn == '') {
            $result = new JSON_Result(false, "Не задан объект для заполнения поля " . $form[$key]->DataField, '');
            return $result;
          }
          $id = get_id_object($sn);
          if ((! isset($id)) || ($id == '')) {
            $result = new JSON_Result(false, "Объект $sn для заполнения поля " . $form[$key]->DataField . " не найден", '');
            return $result;
          }

          $sn1 = $form[$key]->SLVObject4Display;
          $id = get_id_object($sn1);
          if ((! isset($id)) || ($id == '')) {
            $result = new JSON_Result(false, "Объект $sn для заполнения поля " . $form[$key]->DataField . " не найден", '');
            return $result;
          }

          $form[$key]->text = get_descr_spr_object($sn, get_id_object($sn));
        } elseif ($form[$key]->InputType == "combobox") {
          $sn = $form[$key]->SLVObject;
          if ($sn == '') {
            $result = new JSON_Result(false, "Не задан объект для заполнения поля " . $form[$key]->DataField, '');
            return $result;
          }
          $id_o = get_id_object($sn);
          if (!isset($id_o)) {
            $result = new JSON_Result(false, "Объект \"$sn\" для заполнения поля " . $form[$key]->DataField . " не найден", '');
            return $result;
          }
          $key_field = get_key_field($sn);
          $aDescr = get_array_descr_fields($sn);
          $connector = get_connector($sn);
          $s = '';
          $coma = '';
          foreach ($aDescr as $key1 => $value1) {
            $s.="$coma $value1";
            if ($type_login == 3)
              $coma = " + ";
            else
              $coma = " || ";
          }
          $sql = "SELECT $key_field as \"ID\",$s as name FROM $sn";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if ($s_err != '') {
            $result = new JSON_Result(false, "Не определено ключевое или характеризующее поле у информационого объекта \"$sn\". Элемент ввода \"" . $form[$key]->Caption . "\" ", '');
            return $result;
          } else {
            $i = kometa_num_rows($res);
            if ($i > 50) {
              $value->InputType = "pickerfield";
            } else {
              $form[$key]->data = array();

              while ($row = kometa_fetch_object($res)) {
                array_push($form[$key]->data, $row);
              }
            }
          }
        }
      }
      return null;
    }

    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $Result = array();
    $id_edit_object = get_id_edit_object($id_object);
    if (!isset($id_edit_object)) {
      $result = new JSON_Result(false, ('Не определен объект для редактирования'), NULL);
      return $result;
    } else {

      if (isset($id_form)) {
        $sql = "select mb_form.id_form, mb_form.id_object, mb_form.code, mb_form.short_name, mb_form.ord, mb_form.content "
                . " from mb_form  where id_object=$id_edit_object and id_form=$id_form";
        if ($id_edit_object != $id_object) {
          $sql.= " and (exists(select * from mb_link_form_object where mb_link_form_object.id_object=$id_object and mb_form.id_form=mb_link_form_object.id_form))";
        }
        $sql.= " order by ord";
      } else {

        $sql = "select mb_form.id_form, mb_form.id_object, mb_form.code, mb_form.short_name, mb_form.ord, mb_form.content "
                . " from mb_form  where id_object=$id_edit_object ";
        if ($id_edit_object != $id_object) {
          $sql.= " and (exists(select * from mb_link_form_object where mb_link_form_object.id_object=$id_object and mb_form.id_form=mb_link_form_object.id_form))";
        }
        $sql.= "order by ord";
      }
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        $result = new JSON_Result(false, ('Ошибка чтения форм'), NULL);
        return $result;
      } else {
        $i = 0;
//        $Result.= "[" . PHP_EOL;

        while ($row = kometa_fetch_object($res)) {
          $s = $row->content;
          $form = json_decode($s);
          if (isset($form)) {
            $result = work_form($form->children);
            if (($result) && ($result->success == false)) {
              $result->msg.=" форма ввода \"" . $form->text . "\"";
              return $result;
            }
          }
          array_push($Result, $form);

          $i++;
        }
        //$Result.= "]" . PHP_EOL;
        if ($i == 0) {
          $result = new JSON_Result(false, ('Формы ввода отсутствуют'), NULL);
          return $result;
        }
      }
      if ($i > 0) {
        $result = new JSON_Result(true, '', $Result);
        return $result;
      }
    }
  }

  function ReadObject($EditDataParams, $isNew, $InitFormObject) {
    global $ID_User;
    global $type_login;
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $id_edit_object = get_id_edit_object($EditDataParams->id_object);

    $sysname = get_sysname($id_edit_object);
    $key_fld_list_edit_object = get_key_field_as_array($sysname);
    // проверяем соответствуют ли имена ключевых полей представления и объекта для редактирования этого представления
    // если нет то беда
    $err = false;
    if (count($key_fld_list_edit_object) <> count($EditDataParams->key_fld_list))
      $err = true;
    else {
      foreach ($key_fld_list_edit_object as $key => $value) {
        if (array_search($value, $EditDataParams->key_fld_list) === false) {
          $err = true;
        }
      }
    }
    if ($err == true) {
      $result = new JSON_Result(false, my_escape_string("Список ключевых слов представления и объекта для редактирования не совпадают"), NULL);
      return $result;
    }
    $obj = $InitFormObject->ValueKeyFields;

    $sql = "SELECT fieldname from mb_object_field where id_object=$id_edit_object";
    $res = kometa_query($sql);
    $fld_list = '';
    $fld_list_for_new = '';
    $coma = '';
    while ($row = kometa_fetch_object($res)) {
      $fld = $row->fieldname;
      $fld_list.=$coma . $fld;
      $val = $InitFormObject->DefaultValues->$fld;
      if (!isset($val)) {
        $val = ' NULL ';
      } else {
        eval("\$val=\"$val\";");
      }
      $fld_list_for_new .="$coma $val as $q" . $row->fieldname . "$q";
      $coma = ',';
    }
    if ($type_login == 3) {
      $q = "'"; // для mssql надо слово брать в кавычки одинарные
    } else {
      $q = '"'; // для postgresql надо слово брать в кавычки двойные
    }

    foreach ($InitFormObject->DefaultValues as $fld => $val) {
      if (isset($val)) {
        //$val = my_escape_string($val);
        eval("\$val=\"$val\";");
      } else {
        $val = ' NULL ';
      }
      if (isset($InitFormObject->DialogSLVText->$fld)) {
        $val = get_descr_spr_object($InitFormObject->DialogSLVText->$fld, $val);
        if (isset($val)) {
          $val = my_escape_string($val);
        } else {
          $val = ' NULL ';
        }
        $fld_list_for_new .="$coma $val as $q#$fld$q";
      }
      $coma = ',';
    }

    foreach ($InitFormObject->DialogSLVText as $fld => $slv) {
      $slv_key = get_key_field($slv);
      $desc_a = get_array_descr_fields($slv);
      $connector = get_connector($slv);
      $sss = '';
      $coma1 = '';


      foreach ($desc_a as $fld1) {
        $sss.="$coma1 $fld1";
        $coma1 = "|| '$connector'|| ";
      }
      $fld_list.="$coma (SELECT $sss FROM $slv t_slv where t_slv.$slv_key=$sysname.$fld) as $q#$fld$q ";
    }
    $sql = "select $fld_list from $sysname  ";
    $coma = ' where ';
    foreach ($obj as $key => $value) {
      if (isset($value)) {
        $sql.="$coma $key = '$value'";
      } else {
        $sql.="$coma $key is NULL";
      }
      $coma = ' and ';
    }
    if ($isNew == 'true') {
      $sql = "SELECT $fld_list_for_new";
    }
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, my_escape_string("Ошибка чтения записи редактирования записи для редактирования $s_err $sql"), NULL);
      return $result;
    } else {
      $row = kometa_fetch_object($res);
      $result = new JSON_Result(true, '', $row);
      return $result;
    }
  }

  function SaveRecord($EditDataParams, $DataStore) {
    global $type_login;
    global $ID_User;
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);


    $id_edit_object = get_id_edit_object($EditDataParams->id_object);

    $sysname = get_sysname($id_edit_object);

    $obj = new stdClass();

    $code_field = get_code_field($sysname); // кодовое поле

    $history_field = get_history_field($sysname); // поле с признаком истории

    $f_save_error = ''; //Ошибка о сохранении

    $coma = '';
    $coma1 = '';


// зачищаем значения ключевых полей если определено ключевое поле
    foreach ($EditDataParams->key_fld_list as $key => $value) {
      if (isset($history_field))
        $obj->$value = NULL;
      else {
        $obj->$value = $DataStore->$value;
      }
    }

// проверяем заполнены ли значения для ключевых полей
    $b = false;
    foreach ($obj as $key => $value) {
      if (isset($value) && ($value != '')) {
        $b = true;
        break;
      }
    }

    if (!$b) {
      // значения ключевых полей не определены. Делаем добавление новой записи
      foreach ($DataStore as $key => $value)
        if (substr($key, 0, 1) != '#') {

          if (is_bool($value) === true) {
            if ($value)
              $value = "1";
            else
              $value = "0";
          }
          $value = my_escape_string($value);
          // формируем строку для insert

          if ($value != 'NULL') {
            $f .= $coma . "$key";
            $fv .= $coma . "$value";
            $coma = ' , ';
          }
        }

      $sql = "insert into $sysname ($f) VALUES ($fv) ";
      if ($type_login != 3) {
        $coma = ' returning ';
        foreach ($obj as $key => $value) {
          $sql.="$coma $key";
          $coma = ' , ';
        }
      }
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $f_save_error = "Ошибка добавления записи $sql  $s_err";
      } else {
        if ($type_login == 3) {
          $sql = "SELECT IDENT_CURRENT('$sysname') as $key";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if ($s_err != '') {
            $f_save_error = 'Ошибка получения идентификатора добавленной записи';
          }
        }

        if (($f_save_error == '') || !isset($f_save_error)) {
          $row = kometa_fetch_object($res);
          if (!isset($row))
            $f_save_error = 'Ошибка получения идентификатора добавленной записи';

//          foreach ($row as $key => $value) {
//            $id = $row->$key;
//          }
//          if (!isset($id)) {
//            $f_save_error = 'Ошибка: идентификатор добавленной записи не определен';
//          }
        }
      }
    } else {
//изменение в запись
      foreach ($DataStore as $k => $value)
        $b = true;
      foreach ($obj as $key_obj => $value_obj) {
        if ($key == $key_obj) {
          $b = false;
        }
      }
      if (isset($history_field) && isset($code_field) && (substr($key, 0, 1) != '#') && ($b)) {
        // в этом случае предыдущуюзапись суказаныым кодом в архив
        // копируем текущую запись с комом архивная но новым id

        if ($value != 'NULL') {
          $f .= $coma . "$key";
          if ($key == $history_field)
            $fv .= $coma . "1";
          $coma = ' , ';
        }
        $coma = '';
        $w = '';
        foreach ($obj as $key => $value) {
          $w.="$coma $key='$value'";
          $coma = ' and ';
        }

        $sql = "insert into $sysname (f) (SELECT $fv FROM $sysname where $w)";

        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $f_save_error = "Ошибка перевода записи в архив. $sql  $s_err";
        } else {
//и добавляем новую с ключевыми как у старой
          foreach ($DataStore as $key => $value)
            $b = true;
          foreach ($obj as $key_obj => $value_obj) {
            if ($key == $key_obj) {
              $b = false;
            }
          }
          if ((substr($key, 0, 1) != '#') && ($b)) {

            if (is_bool($value) === true) {
              if ($value)
                $value = "1";
              else
                $value = "0";
            }
            $value = my_escape_string($value);
            // формируем строку для insert

            if ($value != 'NULL') {
              $f .= $coma . "$key";
              $fv .= $coma . "$value";
              $coma = ' , ';
            }
          }
          // теперь в конец подписывам ключевые поля
          foreach ($obj as $key => $value) {
            $f .= $coma . "$key";
            $fv .= $coma . "$value";
            $coma = ' , ';
          }
        }
        $sql = "insert into $sysname ($f) VALUES ($fv)  ";

        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) || ($s_err != ''))
          $f_save_error = "Ошибка добавления записи взамен переведенной в архив. $sql $s_err";
        else {
          $row = $obj;
        }
      } else {
        // история изменений не ведется измененяем запись
        foreach ($DataStore as $key => $value)
          if (substr($key, 0, 1) != '#') {

            if (is_bool($value) === true) {
              if ($value)
                $value = "1";
              else
                $value = "0";
            }
            $value = my_escape_string($value);

            // формируем строку для update
            $b = true;
            foreach ($obj as $key_obj => $value_obj) {
              if ($key == $key_obj) {
                $b = false;
              }
            }
            if ($b) {
              $ff .= $coma1 . "$key=$value";
              $coma1 = ',';
            }
          }
        $sql = "update $sysname set $ff   ";
        $coma = ' where ';
        foreach ($obj as $key => $value) {
          $sql.="$coma $key='$value'";
          $id = $value;
          $coma = ' and ';
        }
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '')
          $f_save_error = 'Ошибка обновления записи: ' . $s_err . '<br>' . $sql;
        else {
          $row = $obj;
        }
      }
    }

    if (isset($f_save_error) && ($f_save_error != '')) {
      if ($type_login == 3)
        $f_save_error = str_replace('"', '\"', $f_save_error);
      else {
        if (strpos($f_save_error, 10) > 0)
          $f_save_error = str_replace('"', '\"', substr($f_save_error, 0, strpos($f_save_error, 10)));
        else
          $f_save_error = str_replace('"', '\"', $f_save_error);
      }
      $result = new JSON_Result(false, $f_save_error, '');
    } else {
//      $res = new Object();
//      $res->id = $id;
      $result = new JSON_Result(true, 'Запись сохранена', $row);
    }
    return $result;
  }

}

//class Object {
//  
//}
