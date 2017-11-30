<?php

/*
 * формирование документа по шаблону
 */
session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Report/Report_classes.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/DiagramTemplate_include/DiagramTemplate_function.php");

class Report_class {

  function InitObject($Template_Code) {//загрузка шаблона из БД

    function GetSQLFieldsList_local($sql) {//возвращает масив полей запроса для подстановки в комбо выбора в объект
      $result = CheckConnection();
      if ($result->success === false) {
        return $result;
      }
      unset($result);

      $_list = array();
      if (!isset($sql)) {
        return $_list;
      }
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        return $_list;
      }
      $n = kometa_num_fields($res);
      for ($index = 0; $index < $n; $index++) {
        $field_name = kometa_field_name($res, $index);
        array_push($_list, $field_name);
      }
      return $_list;
    }

    function GetColorListArray() {
      $_list = array();
      include($_SESSION['ProjectRoot'] . 'Report\colors.php');
      return $_list;
    }

    function GetImageListArray() {
      $dir = $_SESSION['APP_INI_DIR'] . "img";
      $FilesArray = scandir($dir);
//$ss=error_get_last();
      $a = array();
      $s = "Не задан";
      array_push($a, $s);
      foreach ($FilesArray as $file) {
        $f_info = pathinfo($file);
        if ((strtolower($f_info['extension']) == "png") || (strtolower($f_info['extension']) == "jpg") || (strtolower($f_info['extension']) == "gif") || (strtolower($f_info['extension']) == "bmp") || (strtolower($f_info['extension']) == "tif")) {
          $s = $f_info['basename'];
          array_push($a, $s);
        }
      }
      return $a;
    }

    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    global $ParamTypeInput;

    if (!isset($Template_Code)) {//новый
      $result->ReportTemplate->text = 'Шаблон';
      $result->ReportTemplate->expanded = true;
      $result->ReportTemplate->leaf = false;
      $result->ReportTemplate->iconCls = 'report';
      $result->ReportTemplate->children = array();
      $result->ReportTemplate->ItemType = 'report';
      $result->ReportTemplate->description = 'Новый шаблон';
      $result->ReportTemplate->ShowPAGE_NUMBER = true;
      $result->ReportTemplate->PageStartNumber = 1;
      $result->ReportTemplate->PageSizeWidth = 21;
      $result->ReportTemplate->PageSizeHeight = 29;
      $result->ReportTemplate->PageOrientation = 'RB_PORTRAIT';
      $result->ReportTemplate->MarginLeft = 3.0;
      $result->ReportTemplate->MarginRight = 1.5;
      $result->ReportTemplate->MarginTop = 2.0;
      $result->ReportTemplate->MarginBottom = 2.0;
      $result->ReportTemplate->FirstPageNumber = false;
      $result->ReportTemplate->id_report_template = null;
      $result->ReportTemplate->Code = GenerateUnicalCodeField('mbr_report_template', 'code');
      $result->ComboReportFieldListStoreData = array();
      $result->ComboColorListStoreData = GetColorListArray();
      $result->ComboDiagramStoreData = _GetListDiagramTemplate();
      $result = new JSON_Result(true, '', $result);
      return $result;
    } else {
      $sql = "SELECT id_report_template,code, contents FROM mbr_report_template  "
              . " where code='" . $Template_Code . "'";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      } else if ($row = kometa_fetch_object($res)) {
        $result->ReportTemplate = json_decode($row->contents);
        $result->ReportTemplate->Code = $row->code;
        $result->ReportTemplate->id_report_template = $row->id_report_template;
        $result->ComboReportFieldListStoreData = GetSQLFieldsList_local($result->ReportTemplate->SQL);
        $result->ComboColorListStoreData = GetColorListArray();
        $result->ComboDiagramStoreData = _GetListDiagramTemplate();
        $result = new JSON_Result(true, '', $result);
        return $result;
      } else {
        $result = new JSON_Result(false, 'Пусто', NULL);
        return $result;
      }
    }
  }

// khdg  function GetODTReportInteractiveParamList($ODT_Template_Code) {// возвращает список интеррактивных параметров
  function GetReportParamList($Template_Code) {// возвращает список не вычисляемых параметров
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);


    if (!isset($Template_Code)) {
      $result = new JSON_Result(false, 'Не определен отчет', NULL);
      return $result;
    } else {
      $sql = "SELECT contents FROM mbr_report_template where code='$Template_Code'";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      } else {
        if (kometa_num_rows($res) == 0) {
          $result = new JSON_Result(false, 'Шаблон отчета не найден', NULL);
        } else {
          $row = kometa_fetch_object($res);
          $Template = json_decode($row->contents);
          $Params = $Template->ReportParams;
          $InteractiveParams = Array();
          foreach ($Params as $par) {
            array_push($InteractiveParams, $par);
          }
          $result = new JSON_Result(true, '', $InteractiveParams);
        }
        return $result;
      }
    }
  }

  function SaveReportTemplate($Template) {//сохранение шаблона в БД
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $Template_Code = $Template->Code;
    $description = $Template->description;
    $id_report_template = $Template->id_report_template;
    if (!isset($Template_Code)) {
      $result = new JSON_Result(false, 'Не определен код шаблона', NULL);
      return $result;
    } else {
      unset($Template->id_Template_Code);
      unset($Template->Code);
      unset($Template->isNew);
      if (isset($id_report_template)) {
        $sql = "SELECT id_report_template  FROM mbr_report_template where code='" . $Template_Code . "'";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $result = new JSON_Result(false, $s_err, NULL);
          return $result;
        }
        if (($row = kometa_fetch_object($res)) && ($row->id_report_template != $id_report_template)) {
          $result = new JSON_Result(false, ('Такой код шаблона уже есть!'), NULL);
          return $result;
        }
// такое имя найдено заменяем
        $sql = "UPDATE mbr_report_template SET description=" . my_escape_string($description)
                . ", code =" . my_escape_string($Template_Code)
                . ", contents =" . my_escape_string(json_encode($Template))
                . "  where id_report_template=" . $id_report_template;
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $result = new JSON_Result(false, $s_err, NULL);
          return $result;
        } else {
          $res = kometa_query("SELECT id_report_template from mbr_report_template where id_report_template=" . $Template->id_report_template);
          $s_err = kometa_last_error();
          if ($s_err != '') {
            $result = new JSON_Result(false, $s_err, NULL);
            return $result;
          }
          $row = kometa_fetch_object($res);
          $result = new JSON_Result(true, 'Успешно обновлено', $row);
          return $result;
        }
      } else {
// такое имя не найдено добавляем
        $sql = "INSERT INTO mbr_report_template(code, description, contents)"
                . "VALUES ("
                . "'" . $Template_Code . "', "
                . my_escape_string($description) . ", "
                . my_escape_string(json_encode($Template)) . ")";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $result = new JSON_Result(false, $s_err, NULL);
          return $result;
        } else {
          $sql = "SELECT id_report_template from mbr_report_template where code='$Template_Code'";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if ($s_err != '') {
            $result = new JSON_Result(false, $sql . '<br>' . $s_err, NULL);
            return $result;
          }
          $row = kometa_fetch_object($res);
          $result = new JSON_Result(true, 'Успешно добавлено', $row);
          return $result;
        }
      }
    }
  }

  function GetListReportTemplate() {//получить список шаблонов
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $sql = "SELECT id_report_template, description, code_help, code FROM mbr_report_template ";
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

  function DeleteReportTemplate($Template_Code) {//удалить шаблон
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    if (!isset($Template_Code)) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      return $result;
    }
    $sql = "delete FROM mbr_report_template where code='$Template_Code'";
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

  function LoadTemplate($Template_Code) { //загружает шаблон отчета
    if (!isset($Template_Code)) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      return $result;
    }
    $sql = "SELECT description, contents FROM mbr_report_template where code='$Template_Code'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    }
    if (kometa_num_rows($res) == 0) {
      $result = new JSON_Result(false, 'Шаблон отчета не найден', NULL);
      return $result;
    }
    $row = kometa_fetch_object($res);
    $result = new JSON_Result(true, $row->description, json_decode($row->contents));
    return $result;
  }

  function CreateODTReport($Template_Code, $ParamValuesArray) { //формирование документа по шаблону обертка
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);


    include ($_SESSION['ProjectRoot'] . "Report/CreateODTReport.php");

    global $db_usr;

    $result = $this->LoadTemplate($Template_Code);
    if ($result->success == true) {
      $FileName = $_SESSION["UserData"] . $db_usr . '/File___' . $Template_Code . '.odt';

      file_put_contents($FileName, 'Пустой файл'); // затычка

      $URLFileName = $_SESSION["URLUserData"] . $db_usr . '/File___' . $Template_Code . '.odt';
      $odt = ODT::getInstance();
      $SubReportItterationArray = array(); //в этот массив будут заноситься коды всех подотчетов для анализа зацикливания
      $result = BuildODTReportBody($odt, $result->result, $ParamValuesArray, $SubReportItterationArray);
      if ($result == true) {
        $odt->output($FileName);
        $result = new JSON_Result(true, 'Успешно сформирован файл', $URLFileName);
        return $result;
      } else {
        $result = new JSON_Result(false, $result, NULL);
        return $result;
      }
    } else {
      return $result;
    }
  }

  function GetSQLFieldsList($sql) {//возвращает масив полей запроса для подстановки в комбо выбора в ответ клиенту
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      $result = new JSON_Result(false, $s_err, NULL);
      return $result;
    } else {

      $_list = array();
      $n = kometa_num_fields($res);
      for ($index = 0; $index < $n; $index++) {
        $field_name = kometa_field_name($res, $index);
        array_push($_list, $field_name);
      }
      $result = new JSON_Result(true, '', $_list);
      return $result;
    }
  }

  function GetCellStylesArray() {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $s2 = file_get_contents($_SESSION['APP_INI_DIR'] . ReportConstants::CellStylesFileName);
    $result1 = unserialize($s2);

    $arr = array();
    foreach ($result1 as $value) {
      $arr[$value->StyleName] = $value;
    }
    $result = new JSON_Result(true, '', $arr);
    return $result;
  }

  function SaveCellStylesArray($StylesArray) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $_list = $StylesArray;
    $s = serialize($_list);
    file_put_contents($_SESSION['APP_INI_DIR'] . ReportConstants::CellStylesFileName, $s);

    $result = new JSON_Result(true, 'Успешно сохранено', null);
    return $result;
  }

  function CreatePDFReport($PDF_Template_Code, $ParamValuesArray) { //формирование документа по шаблону обертка
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    include ($_SESSION['ProjectRoot'] . "Report/CreatePDFReport.php");

    global $db_usr;

    $result = $this->LoadTemplate($PDF_Template_Code);
    if ($result->success == true) {
      $PDF_FileName = $_SESSION["UserData"] . $db_usr . '/File___' . $PDF_Template_Code . '.pdf';

      $PDF_URLFileName = $_SESSION["URLUserData"] . $db_usr . '/File___' . $PDF_Template_Code . '.pdf';

      define('FPDF_FONTPATH', $_SESSION['LIB'] . 'PHP/pdf/fonts');

      $PageOrientation=$result->result->PageOrientation;
      if ($PageOrientation == 'RB_PORTRAIT')
        $PageOrientation='P';
      else $PageOrientation='L';
      $pdf = new pdf($PageOrientation,'cm',array($result->result->PageSizeWidth ,$result->result->PageSizeHeight));
      $pdf->SetMargins($result->result->MarginLeft, $result->result->MarginTop , $result->result->MarginRight);
      $pdf->AddFont('courier', '', 'courier.php');
      $pdf->AddFont('courierBI', '', 'courierbi.php');
      $pdf->AddFont('courierB', '', 'courierb.php');
      $pdf->AddFont('courierI', '', 'courieri.php');
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(TRUE, $MarginBottom);

      $SubReportItterationArray = array(); //в этот массив будут заноситься коды всех подотчетов для анализа зацикливания
      $result = BuildPDFReportBody($pdf, $result->result, $ParamValuesArray, $SubReportItterationArray);
      if ($result == true) {
        $s = $pdf->Output('S');
        file_put_contents($PDF_FileName, $s);
        $result = new JSON_Result(true, 'Успешно сформирован файл', $PDF_URLFileName);
        return $result;
      } else {
        $result = new JSON_Result(false, $result, NULL);
        return $result;
      }
    } else {
      return $result;
    }
  }

  function CreateHTMLReport($HTML_Template_Code, $ParamValuesArray) { //формирование документа по шаблону обертка
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    include ($_SESSION['ProjectRoot'] . "Report/CreateHTMLReport.php");

    global $db_usr;

    $result = $this->LoadTemplate($HTML_Template_Code);
    if ($result->success == true) {
      $HTML_FileName = $_SESSION["UserData"] . $db_usr . '/File___' . $HTML_Template_Code . '.html';

//      file_put_contents($PDF_FileName, 'Пустой файл'); // затычка

      $HTML_URLFileName = $_SESSION["URLUserData"] . $db_usr . '/File___' . $HTML_Template_Code . '.html';


      $SubReportItterationArray = array(); //в этот массив будут заноситься коды всех подотчетов для анализа зацикливания
      $result = '<HTML>
    <HEAD>
        <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
        <TITLE>
            Просмотр данных
        </TITLE>
    </HEAD>
    <body>' .
              BuildHTMLReportBody($result->result, $ParamValuesArray, $SubReportItterationArray)
              . '</body></HTML>';
      if (isset($result)) {
        file_put_contents($HTML_FileName, $result);
        $result = new JSON_Result(true, 'Успешно сформирован файл', $HTML_URLFileName);
        return $result;
      } else {
        $result = new JSON_Result(false, $result, NULL);
        return $result;
      }
    } else {
      return $result;
    }
  }

}
