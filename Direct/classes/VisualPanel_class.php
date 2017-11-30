<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/QueryBuilder_include/QueryBuilder_function.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/HTML_report_include/HTML_report_function.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/DiagramTemplate_include/DiagramTemplate_function.php");

class VisualPanel_class {

  function Get_URLVisualPanelMakets() {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $result = new JSON_Result(true, '', $_SESSION["URLVisualPanelMakets"]);
      return $result;
    } else {
      return $result;
    }
  }

  function GetVisualPanelList() {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $sql = "SELECT  * FROM mbv_viewpanel ";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        return $result = new JSON_Result(false, $s_err, NULL);
      }
      $VisualPanel_list = array();
      while ($row = kometa_fetch_object($res))
        array_push($VisualPanel_list, $row);
      return $result = new JSON_Result(true, '', $VisualPanel_list);
    } else {
      return $result;
    }
  }

  function get_code_value($sysname, $keyvalue) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      return $result = new JSON_Result(true, '', get_code_value($sysname, $keyvalue));
    } else {
      return $result;
    }
  }

  function GetVisualPanel_InitObject($VisualPanelCode) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      if (isset($VisualPanelCode)) {
        $sql = "SELECT * FROM mbv_viewpanel where code='$VisualPanelCode'";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          return $result = new JSON_Result(false, $s_err, NULL);
        }
        if ($row = kometa_fetch_object($res)) {
          $ConfigObject = json_decode($row->contents);
          $ConfigObject->Description = $row->description;
          $ConfigObject->Code = $row->code;
          $ConfigObject->id_viewpanel = $row->id_viewpanel;
          foreach ($ConfigObject->MaketContainerObject as $key => $value) {
            unset($panel_params);
            switch ($value->VisualPanelItemTypes) {
              case 1: // Запрос
                $result = _GetStoredQueryParamList($value->keyObjectId);
                if ($result->success == true)
                  $panel_params = $result->result;
                break;
              case 2: // HTML-отчет
                $result = _GetHTMLReportParamList($value->keyObjectId);
                if ($result->success == true)
                  $panel_params = $result->result;
                break;
              case 3: // График
                $result = _GetDiagramParamList($value->keyObjectId);
                if ($result->success == true)
                  $panel_params = $result->result;
                break;

              default:
                break;
            }
            foreach ($panel_params as $value) {
              $b = true;
              foreach ($ConfigObject->VisualPanelParams as $value1) {
                if ($value1->ParamCode == $value->ParamCode) {
                  $b = false;
                  break;
                }
              }
              if ($b) {
                $value->ParamInterractive = false;
                array_push($ConfigObject->VisualPanelParams, $value);
              }
            }
          }
          unset($b);
        }
      } else {
        $ConfigObject = new stdClass();
        $ConfigObject->Description = 'Новая';
        $ConfigObject->Code = GenerateUnicalCodeField('mbv_viewpanel', 'code');
        $ConfigObject->isNew = true;
        $ConfigObject->id_viewpanel = NULL;
      }
      return $result = new JSON_Result(true, $s_err, $ConfigObject);
    } else {
      return $result;
    }
  }

  function Get_viewpanel_maket_List() {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $VizPanelDir = $_SESSION["VisualPanelMakets_dir"];
      $tod = 'VisualPanelMaket';
      $result_list = array();
      foreach (glob("$VizPanelDir/$tod*.js") as $JSfile) {
        $js_file_contents = file_get_contents($JSfile);
        $rows = explode("\n", $js_file_contents);
        array_shift($rows);
        foreach ($rows as $row => $data) {
          $pos = strpos($data, 'VisualPanelDescription');
          if (!($pos === false)) {
            $row_data = str_replace('VisualPanelDescription', '', $data);
            $row_data = str_replace(':', '', $row_data);
            $row_data = str_replace(',', '', $row_data);
            $row_data = str_replace("'", '', $row_data);
            unset($record);
            $record->description = $row_data;
            $record->code = basename($JSfile, ".js");
            array_push($result_list, $record);
          }
        }
      }
      return $result = new JSON_Result(true, '', $result_list);
    } else {
      return $result;
    }
  }

  function DeleteFromVisualPanel($id_viewpanel) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      if (!isset($id_viewpanel)) {
        return $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      }
      $sql = "delete FROM mbv_viewpanel where id_viewpanel=" . $id_viewpanel;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        return $result = new JSON_Result(false, $s_err, NULL);
      } else {
        return $result = new JSON_Result(true, 'Успешно удалено', NULL);
      }
    } else {
      return $result;
    }
  }

  function SaveVisualPanel($contents) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      //   $contents = json_decode($contents);
      $Description = $contents->Description;
      $Code = $contents->Code;
      $isNew = $contents->isNew;
      $id_viewpanel = $contents->id_viewpanel;
      if (!isset($id_viewpanel) || ($isNew == true)) {
        $id_viewpanel = -1;
      }
      if (!isset($Code) || (trim($Code) === '')) {
        $Code = GenerateUnicalCodeField('mbv_viewpanel', 'code');
      }
      if (!isset($Description)) {
        return $result = new JSON_Result(false, 'Не определено имя сводной таблицы', NULL);
      }
      unset($contents->Description);
      unset($contents->Code);
      unset($contents->isNew);
      unset($contents->id_viewpanel);
      $contents = json_encode($contents);
      $sql = "SELECT id_viewpanel FROM mbv_viewpanel where id_viewpanel=" . $id_viewpanel;
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        return $result = new JSON_Result(false, $s_err, NULL);
      }
      if ($row = kometa_fetch_object($res)) {
        $sql = "UPDATE mbv_viewpanel SET description=" . my_escape_string($Description)
        . ", contents =" . my_escape_string($contents)
        . ", code =" . my_escape_string($Code)
        . "  where id_viewpanel=" . $row->id_viewpanel;
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          return $result = new JSON_Result(false, my_escape_string($s_err), NULL);
        } else {
          $res = kometa_query("SELECT id_viewpanel, code from mbv_viewpanel where code='" . $Code . "' ");
          $s_err = kometa_last_error();
          if ($s_err != '') {
            return $result = new JSON_Result(false, my_escape_string($s_err), NULL);
          }
          $row = kometa_fetch_object($res);
          return $result = new JSON_Result(true, 'Успешно обновлено', $row);
        }
      } else {
// такое имя не найдено добавляем
        $sql = "INSERT INTO mbv_viewpanel(code, description, contents)"
        . "VALUES ("
        . "'" . $Code . "', "
        . my_escape_string($Description) . ", "
        . my_escape_string($contents) . ")";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          return $result = new JSON_Result(false, $s_err, NULL);
        } else {
          $res = kometa_query("SELECT id_viewpanel, code from mbv_viewpanel where code='" . $Code . "' ");
          $s_err = kometa_last_error();
          if ($s_err != '') {
            return $result = new JSON_Result(false, $s_err, NULL);
          }
          $row = kometa_fetch_object($res);
          return $result = new JSON_Result(true, 'Успешно добавлено', $row);
        }
      }
    } else {
      return $result;
    }
  }

}
