<?php

function _GetDiagramParamList($Diagram_Template_Code) {// возвращает список  параметров
  if (!isset($Diagram_Template_Code)) {
    $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
    return $result;
  } else {
    $sql = "SELECT contents FROM mbg_diagram_template where code='$Diagram_Template_Code'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    } else {
      if (kometa_num_rows($res) == 0) {
        $result = new JSON_Result(false, 'Шаблон диаграммы не найден', NULL);
        return $result;
      } else {
        $row = kometa_fetch_object($res);
        $Template = json_decode($row->contents);
        $Params = $Template->DiagramParams;
        $InteractiveParams = Array();
        foreach ($Params as $par) {
          array_push($InteractiveParams, $par);
        }
        $result = new JSON_Result(true, '', $InteractiveParams);
        return $result;
      }
    }
  }
}

function _GetListDiagramTemplate() {//получить список шаблонов
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
  return $_list;
}
