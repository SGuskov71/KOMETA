<?php

function _GetHTMLReportParamList($HTML_Template_Code) {// возвращает список  параметров
  $sql = "SELECT contents FROM mbr_report where code='$HTML_Template_Code'";
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
  $Template = json_decode($row->contents);
  $Params = $Template->HTMLReportParams;
  $InteractiveParams = Array();
  foreach ($Params as $par) {
    array_push($InteractiveParams, $par);
  }
  $result = new JSON_Result(true, '', $InteractiveParams);
  return $result;
}

function Compose_SingleReport($Code_TemplateReport, $ParamsArray) {
  set_time_limit(70000);
  global $ID_User;
//  $ParamsArray = json_decode($ParamsArray);
  $FieldNamePrefix = 'p_'; //префикс имени поля в sql запросе
  $FieldColorPrefix = 'c_'; //префикс цвета поля в sql запросе
  $FieldIsImagePrefix = 'img_'; //префикс цвета поля в sql запросе
  $ColumnCount = 0; //кол-во полей
  $MagicWordSerialNo = 'SerialNo'; //волшебное слово для определения автоинкрементной колонки, начальное значение беру из sql запроса
  $result_str = '';
  $sql = "SELECT short_name, code, contents  FROM mbr_report where code='$Code_TemplateReport'";
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    return my_escape_string($sql . '<br>' . $s_err . '<br>');
  }

  $row = kometa_fetch_object($res);

  if (!isset($row)) {
    return my_escape_string("Не найден отчет с кодом \"$Code_TemplateReport\"");
  }

  $Template = json_decode($row->contents);

  // Вычисляем не динамические диаграммы и отчеты
  $EmbeddedObjects = $Template->EmbeddedObjects;

  foreach ($EmbeddedObjects as $key => $value) {
    if ($value->EmbeddedObjectDinamyc == false) {
      $t = $value->EmbeddedObjectType;

      if ($t == 'diagram') {
        $sres = "<img src=" . $_SESSION['URLProjectRoot'] 
                . 'Diagram_Template/ComposeDiagram_pChart.php?Diagram_Template_Code=' 
                . $value->EmbeddedObjectCode . "&ParamValuesArray=" . urlencode (json_encode($ParamsArray)) . ">";
//ComposeDiagramForHTML($value->EmbeddedObjectCode, $ParamsArray);
      } else {
        $sres = Compose_SingleReport($value->EmbeddedObjectCode, $ParamsArray);
      }
    }
    $EmbeddedObjects[$key]->sRes = $sres;
  }

  $Header = $Template->Header;
  // ищем последнее вхождение закрывающего тега </table> и убираем его и то что после него
  $i = strrpos($Header, '</table>');
  $Header = substr($Header, 0, $i);

  foreach ($ParamsArray as $key => $value) {
    $Header = str_replace(':' . $key . ':', $value, $Header);
  }
  foreach ($EmbeddedObjects as $key => $value) {
    $Header = str_replace(':' . $value->EmbeddedObjectCodeInReport . ':', $value->sRes, $Header);
  }
  $result_str = $result_str . $Header;

  $Footer = '</table>' . $Template->Footer;
  foreach ($ParamsArray as $key => $value) {
    $Footer = str_replace(':' . $key . ':', $value, $Footer);
  }

  foreach ($EmbeddedObjects as $key => $value) {
    $Footer = str_replace(':' . $value->EmbeddedObjectCodeInReport . ':', $value->sRes, $Footer);
  }
  $ColumnCount = $Template->Column_count;

  //отбираю запросы по этому отчету
  $QueryList = $Template->children;

  foreach ($QueryList as $Query) {
    $sql = $Query->SQL;
    $MaxRecordShow = $Query->Limit;
    foreach ($ParamsArray as $key => $value) {
      $sql = str_replace(':' . $key . ':', $value, $sql);
    }

    eval("\$sql = \"$sql\";");
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      return my_escape_string($sql . '<br>' . $s_err . '<br>');
      echo json_encode($result);
      return;
    }

    $AutoincVal = 1;
    $PerformedRecordCount = 0;
    $k = kometa_num_rows($res);
    if (isset($MaxRecordShow) && (intval($MaxRecordShow) > 0)) {
      if ($k > $MaxRecordShow) {
        $result_str = $result_str . "<br>";
        $result_str = $result_str . "Кол-во записей в выборке $k, установлен лимит отображения $MaxRecordShow";
        $result_str = $result_str . "<br>";
      }
    }


    while (($row = kometa_fetch_object($res))) {
      if (isset($MaxRecordShow) && (intval($MaxRecordShow) > 0)) {
        if ($PerformedRecordCount >= $MaxRecordShow) {
          break;
        }
      }


      $ParamsArray_dinamic = $ParamsArray;
      foreach ($row as $fld => $value) {
        $ParamsArray_dinamic->$fld = $value;
      }

      foreach ($EmbeddedObjects as $key => $value) {
        if ($value->EmbeddedObjectDinamyc == true) {
          $t = $value->EmbeddedObjectType;

          if ($t == 'diagram') {
            $sres = ComposeDiagramForHTML($value->EmbeddedObjectCode, $ParamsArray_dinamic);
          } else
            $sres = Compose_SingleReport($value->EmbeddedObjectCode, $ParamsArray_dinamic);
        }
        $EmbeddedObjects[$key]->sRes = $sres;
      }


//заполняю массив значений  параметров и переношу их в значения выборки, замещая по коду параметра
      $ReportImgArray = array(); //список всех рисунков в отчете
      $result_str = $result_str . "     <tr>";
      for ($i = 1; $i <= $ColumnCount; $i++) {
        $CurColorField = $FieldColorPrefix . $i;
        $CurImgField = $FieldIsImagePrefix . $i; //здесь может быть признак что в этой колонке путь к файлу рискнка напр p_5=1
        if (isset($row->$CurColorField)) {
          $s = $row->$CurColorField;
          $result_str = $result_str . "     <td  $s>";
        } else
          $result_str = $result_str . "     <td>";

        $CurFieldName = $FieldNamePrefix . $i;
        if ($row->$CurFieldName == $MagicWordSerialNo) {
          $result_str = $result_str . $AutoincVal;
        } else
        if (isset($row->$CurFieldName) && (trim($row->$CurFieldName) != '')) {
          if ((isset($row->$CurImgField))) {//вставка изображения
            //поле $FieldNamePrefix . $i содержит настройки отображения картинки  'width=200 height=20' as img_4
            $ImgName = $row->$CurFieldName;
            $md5FilePath = Get_md5FilePath($ImgName);
            $serverFileName = $md5FilePath . $ImgName;
            if (file_exists($serverFileName)) {
              $ReportImgArray[$ImgName] = $serverFileName;
              $result_str = $result_str . "<img src=" . $_SESSION['URLProjectRoot'] . 'FileUpload/getImage.php?img_code=' . $ImgName . "  " . $row->$CurImgField . ">";
            }
          } else {
            $tf = pg_field_type($res, pg_field_num($res, $CurFieldName));
            if ($tf == 'date')
              $result_str = $result_str . date("d/m/Y", strtotime($row->$CurFieldName));
            else if ($tf == 'numeric') {
              $result_str = $result_str . $s = number_format($row->$CurFieldName, 2, '.', ' ');
            } else if (substr($tf, 0, 3) == 'int') {
              $result_str = $result_str . $s = number_format($row->$CurFieldName, 0, '.', ' ');
            } else {
              $s = $row->$CurFieldName;
              foreach ($EmbeddedObjects as $key => $value) {
                $s = str_replace(':' . $value->EmbeddedObjectCodeInReport . ':', $value->sRes, $s);
              }

              $result_str = $result_str . $s;
            }
          }
        } else {
          $result_str = $result_str . '<br>';
        }
        $result_str = $result_str . "     </td>";
      }
      $result_str = $result_str . "     </tr>";
      $AutoincVal++;
      $PerformedRecordCount++;
    }
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result_str = $result_str . $sql . '<br>' . $s_err . '<br>';
      return $result_str;
    }
  };
  $result_str = $result_str . $Footer;

  return $result_str;
}
