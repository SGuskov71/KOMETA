<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Diagram_Template/ComposeDiagram.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/DiagramTemplate_include/DiagramTemplate_function.php");

class DiagramTemplate_class {

  function InitObject($Diagram_Template_Code) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if (!isset($Diagram_Template_Code)) {//новый
      $result->DiagramTemplate->text = 'Диаграмма';
      $result->DiagramTemplate->expanded = true;
      $result->DiagramTemplate->leaf = false;
      $result->DiagramTemplate->iconCls = 'report';
      $result->DiagramTemplate->children = array();
      $result->DiagramTemplate->ItemType = 'diagram';
      $result->DiagramTemplate->Description = 'Новый шаблон диаграммы';
      $result->DiagramTemplate->code_help = '';
      $result->DiagramTemplate->label_rotate = '0';
      $result->DiagramTemplate->label_type_visible = 'vertical';
      $result->DiagramTemplate->label_display = 'none';
      $result->DiagramTemplate->diagram_type = '3';
      $result->DiagramTemplate->orientation = '0';
      $result->DiagramTemplate->legend_position = '0';
      $result->DiagramTemplate->bg_color = '';
      $result->DiagramTemplate->label_color = '';
      $result->DiagramTemplate->diagram_theme = 'Base';

      $result->DiagramTemplate->x_name = '';
      $result->DiagramTemplate->y_name = '';
      $result->DiagramTemplate->diagram_width = 0;
      $result->DiagramTemplate->diagram_height = 0;
      $result->DiagramTemplate->properties = '';
      $result->DiagramTemplate->ShowError = true;
      $result->DiagramTemplate->ShowGrid = true;
      $result->DiagramTemplate->ShowEmptyMessage = true;
      $result->DiagramTemplate->Code = GenerateUnicalCodeField('mbg_diagram_template', 'code');

      $result->DiagramTemplate->func_class_name = '';
      $result->DiagramTemplate->func_name = '';
      $result->DiagramTemplate->param_list = '';

      $result = new JSON_Result(true, '', $result);
      return $result;
    } else {
      $sql = "SELECT * FROM mbg_diagram_template  "
      . " where code='" . $Diagram_Template_Code . "'";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      }
      if ($row = kometa_fetch_object($res)) {
        $result->DiagramTemplate = json_decode($row->contents);
        $result->DiagramTemplate->Code = $row->code;
        $result = new JSON_Result(true, '', $result);
        return $result;
      }
      $result = new JSON_Result(false, 'Пусто', NULL);
      return $result;
    }
  }

  function DeleteDiagramTemplate($Diagram_Template_Code) {//удалить шаблон
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if (!isset($Diagram_Template_Code)) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      return $result;
    }
    $sql = "delete FROM mbg_diagram_template where code='" . $Diagram_Template_Code . "'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    $result = new JSON_Result(true, 'Успешно удалено', NULL);
    return $result;
  }

  function GetListDiagramTemplate() {//получить список шаблонов
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $sql = "SELECT id_diagram_template, description, code FROM mbg_diagram_template ";
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

  function GetDiagramParamList($Diagram_Template_Code) {// возвращает список  параметров
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $result = _GetDiagramParamList($Diagram_Template_Code);
    return $result;
  }

  function SaveDiagramTemplate($Diagram_Template) {//сохранение шаблона в БД
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $Diagram_Template_Code = $Diagram_Template->Code;
    $id_Diagram = $Diagram_Template->id_Diagram;
    if (!isset($Diagram_Template_Code)) {
      $result = new JSON_Result(false, 'Не определен код шаблона', NULL);
      return $result;
    }
    if (!isset($id_Diagram) || ($id_Diagram == '')) {
      $sql = "SELECT id_diagram_template  FROM mbg_diagram_template where code='" . $Diagram_Template_Code . "'";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      }
      $row = kometa_fetch_object($res);
      $id_Diagram = $row->id_diagram_template;
    }


    if (!isset($id_Diagram)) {
// такое имя не найдено добавляем
      $sql = "INSERT INTO mbg_diagram_template(code, description, contents)"
      . "VALUES ("
      . "'" . $Diagram_Template->Code . "', "
      . my_escape_string($Diagram_Template->Description) . ", "
      . my_escape_string(json_encode($Diagram_Template)) . ")";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, my_escape_string($s_err), NULL);
        return $result;
      } else {
        $sql = "SELECT id_diagram_template  FROM mbg_diagram_template where code='" . $Diagram_Template_Code . "'";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $result = new JSON_Result(false, $s_err, NULL);
          return $result;
        }
        $row = kometa_fetch_object($res);
        $id_Diagram = $row->id_diagram_template;
        $result = new JSON_Result(true, 'Успешно добавлено', $row);
        return $result;
      }
    } else {
// такое имя найдено заменяем
      $sql = "UPDATE mbg_diagram_template SET description=" . my_escape_string($Diagram_Template->Description)
      . ",code =" . my_escape_string($Diagram_Template->Code)
      . ", contents =" . my_escape_string(json_encode($Diagram_Template))
      . "  where id_diagram_template=" . $id_Diagram;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, my_escape_string($s_err), NULL);
        return $result;
      } else {
        $result = new JSON_Result(true, 'Успешно обновлено', NULL);
        return $result;
      }
    }
  }

  function GetDiagramCaption($Diagram_Template_Code) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $sql = "SELECT description FROM mbg_diagram_template where code='$Diagram_Template_Code'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    } else {
      if (kometa_num_rows($res) == 0) {
        $result = new JSON_Result(false, 'Диаграмма не найдена', NULL);
        return $result;
      } else {
        $row = kometa_fetch_object($res);

        $result = new JSON_Result(true, '', $row->description);
        return $result;
      }
    }
  }

  function ComposeDiagram($Diagram_Template_Code, $ParamValuesArray) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $result = _ComposeDiagram($Diagram_Template_Code, $ParamValuesArray);
    return $result;
  }

}

// строит объект json с информацией для построения графика

