<?php

/*
 * формирование HTML документа по шаблону
 */
session_start();
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "FileUpload/FileUploadBackEndFunction.php");

function BuildHTMLReportBody($HTML_Template, $ParamValuesArray, &$SubReportItterationArray, $ThisIsSubReport = false) { //формирование самого тела отчета по шаблону

  function AddToStyle($style, $str) {
    if (($style == '') || !isset($style))
      return $str;
    else {
      return $style . ';' . $str;
    }
  }

  function LoadCellStylesObject($CellStylesFileName) {// загружается объект содержащий определения стилей ячеек таблицы
    $s2 = file_get_contents($_SESSION['APP_INI_DIR'] . $CellStylesFileName);
    $result1 = unserialize($s2);
    $arr = array();
    foreach ($result1 as $value) {
      $arr[$value->StyleName] = $value;
    }
    return $arr;
  }

  function writeParagraph($Props, $ReportQueryRow, $ParamValues, $TextStylesArray) {
    $pStyle = '';
//задать стиль абзаца

    if (isset($Props->TextAlignment)) {
      switch ($Props->TextAlignment) {
        case 'RB_LEFT':$pStyle = AddToStyle($pStyle, 'align:left');
          break;
        case 'RB_CENTER':$pStyle = AddToStyle($pStyle, 'align:center');
          break;
        case 'RB_RIGHT':$pStyle = AddToStyle($pStyle, 'align:right');
          break;
        case 'RB_JUSTIFY':$pStyle = AddToStyle($pStyle, 'align:jubstify');
          break;
      }
    }
//отступы с краев
    if (!isset($Props->LeftMargin))
      $Props->LeftMargin = 0;
    if (!isset($Props->RightMargin))
      $Props->RightMargin = 0;
    if (!isset($Props->TextIdent))
      $Props->TextIdent = 0;
    $LeftMargin = round($Props->LeftMargin / 0.0352777778) . 'pt';
    $RightMargin = round($Props->RightMargin / 0.0352777778) . 'pt';
    $pStyle = AddToStyle($pStyle, 'margin-left:' . $LeftMargin);
    $pStyle = AddToStyle($pStyle, 'margin-right:' . $RightMargin);
//сдвиг красной строки
    if (isset($Props->TextIdent)) {
      $TextIdent = round($Props->TextIdent / 0.0352777778) . 'pt';
      $pStyle = AddToStyle($pStyle, ' text-indent:' . $TextIdent);
    }

    $p = "<p style=\"$pStyle\">";

    if (isset($Props->children))
      foreach ($Props->children as $reportItem) {
        switch ($reportItem->ItemType) {
          case 'text':$p.=writeText($reportItem, $ReportQueryRow, $ParamValues, $TextStylesArray);
            break;
          case 'hyperlink': $p.=writeHyperlink($reportItem, $ReportQueryRow, $ParamValues);
            break;
          case 'image': $p.=writeImage($reportItem, $ReportQueryRow, $ParamValues);
            break;
          case 'linebreak': $p.="<hr>";
            break;
        }
      }
    $p.='</p>';
    return $p;
  }

  function writeText($Props, $ReportQueryRow, $ParamValues, $TextStylesArray) {
    $textStyleCode = $Props->TextStyle;
    $textStyleObject = $TextStylesArray[$textStyleCode];
//задать стиль текста
    $textStyle = '';
    if (isset($textStyleObject)) {
      $textStyle = AddToStyle($textStyle, ' color:' . $textStyleObject->FontColor);
      $textStyle = AddToStyle($textStyle, ' background-color:' . $textStyleObject->BackgroundColor);
      if ($textStyleObject->Underline == true) {
        $textStyle = AddToStyle($textStyle, ' text-decoration:"underline"');
      }
      if ($textStyleObject->Bold == true)
        $textStyle = AddToStyle($textStyle, ' font-weight:"bold');
      if ($textStyleObject->Italic == true)
        $textStyle = AddToStyle($textStyle, 'font-style:"italic"');
      $FontSize = $textStyleObject->FontSize . 'pt';
      $textStyle = AddToStyle($textStyle, ' font-size:' . $FontSize);
    }
    switch ($Props->DataSource) {
      case 'RB_Text':
        foreach ($ParamValues as $key => $value) {
          $Props->TextBlock = str_replace(':' . $key . ':', $value, $Props->TextBlock);
        }
        foreach ($ReportQueryRow as $key => $value) {
          $Props->TextBlock = str_replace(':' . $key . ':', $value, $Props->TextBlock);
        }
        return "<span style=\"$textStyle\">" . $Props->TextBlock . "</span>";
      case 'RB_SQL': {
          $DataField = $Props->DataField;
          return "<span style=\"$textStyle\">" . $ReportQueryRow->$DataField . "</span>";
        }
    }
  }

  function writeHyperlink($Props, $ReportQueryRow, $ParamValues) {
    switch ($Props->DataSource) {
      case 'RB_Text':
        return "<a href=\"$Props->LinkURL\">$Props->LinkText</a>";
      case 'RB_SQL': {
          $DataField1 = $Props->DBLinkText;
          $DataField2 = $Props->DBLinkURL;
          return "<a href=\"$ReportQueryRow->$DataField2\">$ReportQueryRow->$DataField1</a>";
        }
    }
  }

  function writeImage($Props, $ReportQueryRow, $ParamValues) {
    if (!isset($Props->imgWidth))
      $Props->imgWidth = 10;
    if (!isset($Props->imgHeight))
      $Props->imgHeight = 10;
    $imgWidth = round($Props->imgWidth / 0.0352777778) . 'pt'; //The dimensions can be expressed with percentage "%" or one og these units ("mm", "cm", "in" (2.54cm), "pt" (1/72in)).
    $imgHeight = round($Props->imgHeight / 0.0352777778) . 'pt';
    switch ($Props->DataSource) {
      case 'RB_Text': if (file_exists($Props->ImagePath)) {
          $serverFileName = $Props->ImagePath;
        }
        break;
      case 'RB_SQL': {
          $DataField = $Props->DataField;
          $md5FilePath = Get_md5FilePath($ReportQueryRow->$DataField);
          $serverFileName = $md5FilePath . $ReportQueryRow->$DataField;
        }
        break;
    }
    if (file_exists($serverFileName)) {
      $img_content = file_get_contents($serverFileName);
      $img_content = chunk_split(base64_encode($img_content));
      $img_content = 'data: ' . mime_content_type($serverFileName) . ';base64,' . $img_content;
      return "<img width=$imgWidth height=$imgHeight src='$img_content'>";
    }
  }

  function ApplyStyle($textStyleObject) { //применяет стиль к объекту
    $style = '';
    if (isset($textStyleObject)) {
      if (isset($textStyleObject->TextAlign))
        switch ($textStyleObject->TextAlign) {
          case 'RB_LEFT':
            $style = AddToStyle($style, " align:left");
            break;
          case 'RB_CENTER':
            $style = AddToStyle($style, " align:center");
            break;
          case 'RB_RIGHT':
            $style = AddToStyle($style, " align:right");
            break;
          case 'RB_JUSTIFY':
            $style = AddToStyle($style, 'align:jubstify');
        }


      $style = AddToStyle($style, " color:$textStyleObject->FontColor");
      $style = AddToStyle($style, "background-color:$textStyleObject->BackgroundColor");
      if ($textStyleObject->Underline == true) {
        $style = AddToStyle($style, ' text-decoration:underline');
      }
      if ($textStyleObject->Bold == true)
        $style = AddToStyle($style, ' font-weight:bold');
      if ($textStyleObject->Italic == true)
        $style = AddToStyle($style, ' font-style:italic');
      $FontSize = $textStyleObject->FontSize . 'pt';
      $style = AddToStyle($style, ' font-size:' . $FontSize);
    }
    return $style;
  }

  function writeTable($Props, $ReportQueryRow, $ParamValues, $TextStylesArray) {

    $sql = $Props->SQL;
    foreach ($ParamValues as $key => $value) {
      $sql = str_replace(':' . $key . ':', $value, $sql);
    }
    foreach ($ReportQueryRow as $key => $value) {
      $sql = str_replace(':' . $key . ':', $value, $sql);
    }

    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      if ($Props->ShowIfEmptyData != true) { //если не указано выводить таблицу с пустым набором то выхожу
        return false;
      } else {
//если объект результата запроса не создан то создаю пустой чтоб дальше небыло обращений к пустоте в функциях
        if ($Props->ShowError = true) {
          $ErrMessage = my_escape_string('Ошибка выполнения запроса ' . PHP_EOL . $sql . PHP_EOL . $s_err . PHP_EOL);
          writeErrorString($ErrMessage);
        }
      }
    } else {
      if (!(kometa_num_rows($res) > 0)) {
        if ($Props->ShowIfEmptyData != true) { //если не указано выводить таблицу с пустым набором то выхожу
          return false;
        } else {
// надо ли проверять и уведомлять что запрос что то вернул?
          if ($Props->ShowEmptyMessage == true) {
            $ErrMessage = my_escape_string('Запрос вернул пустой набор данных' . PHP_EOL);
            writeErrorString($ErrMessage);
          }
        }
      }
    }

    $tableStyle = 'id=' . uniqid('table');
    if (!isset($Props->TabelWidth))
      $Props->TabelWidth = 10;

    $TabelWidth = round($Props->TabelWidth / 0.0352777778) . 'pt';
    $tableStyle.=" border=1 cellpadding=0 cellspacing=0 width=$TabelWidth";
    if (isset($Props->TableAlignment)) {
      switch ($Props->TableAlignment) {
        case 'RB_LEFT':$tableStyle.=" align:left";
          break;
        case 'RB_CENTER': $tableStyle.=" align:center";
          break;
        case 'RB_RIGHT':$tableStyle.=" align:right";
          break;
//      case 'RB_MARGINS':$tableStyle.="margin:";
//        break;
      }
    }
    $table.="<table $tableStyle>";

    $ColCount = count($Props->children);
//ширины колонок
    $ColumnsWidthArray = array();
    $FormatedHeaders = array();
    $headers = array();
    $headerStyles = array();
    $arrayCellProps = array(); //массив свощйств ячеек (пока только вертикальное выравнивание)
    //для того чтобы потом установить формат яцеек веду дополнительный массив
    //по матрице аналогичный заполняемому $rows в который записываю свойства ячеек для объекта получаемого getCellStyle(i, j)
    //которые не могу выставить пока не создан в документе массив ячеек фу-ей  addRows
    //после того как в документе появятся ячейки от addRows я к этим ячейкам обращусь и выставлю их свойства
    //если выводился addHeader то нумерация строк ячеек увеличится на единицу т.е. getCellStyle(1, 0) вернет ячейку заголовка
    foreach ($Props->children as $key => $value) {
      array_push($ColumnsWidthArray, $value->WidthColumn);
      array_push($headers, $value->LabelColumn);
      array_push($headerStyles, $value->HeaderStyle);
    }
    if ($Props->ShowTableHeader == true) { //надо выводить заголовок
      $arrayRowProps = array();
      for ($i = 0; $i < $ColCount; $i++) {
        if (isset($headers[$i])) {
//??        $pStyle = new ParagraphStyle(uniqid('myPStyleHeader'));
          $textStyle = '';
          if (isset($headerStyles[$i]) && ($headerStyles[$i] != '')) {
            $textStyleObject = $TextStylesArray[$headerStyles[$i]];
            $textStyle = ApplyStyle($textStyleObject);
            array_push($arrayRowProps, $textStyleObject->VerticalAlign); //верт выравнивание
          } else {
            array_push($arrayRowProps, '');
          }
          $p = "<p style=\"$textStyle\">" . $headers[$i] . '</p>';
          array_push($FormatedHeaders, $p);
        }
      }
      array_push($arrayCellProps, $arrayRowProps);

      $table.='<tr>';
      foreach ($FormatedHeaders as $key => $value) {
        if (isset($Props->TabelWidth))
          $table.="<td height=\"" . round($Props->HeaderHeight / 0.0352777778) . 'pt' . "\">$value</td>";
      }
      $table.='<\tr>';
    }

    $rows = array(); //массив строк
    if (kometa_num_rows($res) > 0) {
      $i = 1;
      while ($row = kometa_fetch_object($res)) {
        $r = array();
        $arrayRowProps = array();
        foreach ($Props->children as $key => $value) {
          $colName = $value->DataFieldColumn;
          $cellValue = $row->$colName;

          $colStyleName = $value->StyleFieldColumn;
          if (isset($colStyleName)) {
            $cellStyleValue = $row->$colStyleName;
          } else
            $cellStyleValue = null;
          if (isset($cellStyleValue) && ($cellStyleValue != '')) {
            $textStyleObject = $TextStylesArray[$cellStyleValue];
            $style = ApplyStyle($textStyleObject);
          } else {
            $style = '';
          }

          $p = "<p style=\"$style\">" . $cellValue . "</p>";
          array_push($r, $p);
        }
        array_push($rows, $r);
        //array_push($arrayCellProps, $arrayRowProps);
        $i++;
      }
    }

    foreach ($rows as $keyrow => $row) {
      $table.="<tr>";
      foreach ($row as $key => $value) {
        $table.="<td>$value</td>";
      }
      $table.="</tr>";
    }
    $table.='</table>';
//можно обратиться к стилям ячеек
    return $table;
  }

  function writeList($Props, $ReportQueryRow, $ParamValues, $TextStylesArray) { //строит элемент отчета список
    $StyleName = $Props->TextStyle;
    if (!empty($StyleName)) {
      $pStyle = '';
      $textStyle = '';
      $textStyleObject = $TextStylesArray[$StyleName];
      $textStyle=ApplyStyle($textStyleObject, $pStyle, $textStyle);
    } else {
      $pStyle = '';
      $textStyle = '';
    }
    $list = '<ul>';
    switch ($Props->DataSource) {
      case 'RB_Text': {
          $ListItems = explode("\n", $Props->ListValues);
          foreach ($ListItems as $listField) {
            $list.="<li><span style='$textStyle'>$listField</span></li>";
          }
        }
        break;
      case 'RB_SQL': {
//сформировать запрос $Template->SQL + $Template->SQLConditions
          if (isset($Props->SQLConditions) && ($Props->SQLConditions != '')) {
// заменить параметры по коду на значение в  $Template->SQLConditions
            foreach ($ParamValues as $key => $value) {
              $Props->SQLConditions = str_replace(':' . $key . ':', $value, $Props->SQLConditions);
            }
            foreach ($ReportQueryRow as $key => $value) {
              $Props->SQLConditions = str_replace(':' . $key . ':', $value, $Props->SQLConditions);
            }
            $sql = $Props->SQL . ' where 1=1 and ' . $Props->SQLConditions;
          } else {
            $sql = $Props->SQL;
          }

          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            if ($Props->ShowError == true) {
              $ErrMessage = my_escape_string('Ошибка выполнения запроса ' . PHP_EOL . $sql . PHP_EOL . $s_err . PHP_EOL);
              writeErrorString($ErrMessage);
            }
            return false;
          } else {
            if (!(kometa_num_rows($res) > 0)) {
// надо ли проверять и уведомлять что запрос что то вернул?
              if ($Props->ShowEmptyMessage == true) {
                $ErrMessage = my_escape_string('Запрос вернул пустой набор данных' . PHP_EOL);
                writeErrorString($ErrMessage);
              }
              return false;
            }
          }
          if (kometa_num_rows($res) > 0) {
            $listField = $Props->DBLinkText;
            while ($row = kometa_fetch_object($res)) {
              $list.="<li><span style='$textStyle'>" . $row->$listField . '</span></li>';
            }
          }
        }
        break;
    }
    $list.='</ul>';
    return $list;
  }

  function writeSubReport($Object, $reportItem, $row, $ParamValues, &$SubReportItterationArray) {// строит вложенный отчет
    $b = $SubReportItterationArray[$reportItem->Template_Code];
    if (($b != null) || ($b == true)) {//предотвращаю рекурсию вложенных отчетов
      if ($reportItem->ShowError) {
        return writeErrorString('Вложенный отчет ' . $reportItem->Template_Descr . " уже был сформирован в рамках основного" . PHP_EOL .
                'Для предотвращения зацикливания он больше не формируется!');
      }
      
    } else {
      $result = LoadTemplate($reportItem->Template_Code);
      if ($result->success == true) {
//что делать с набором данных $row родительского отчета надо ли его подставлять во вложенный
        return BuildHTMLReportBody( $result->result, $ParamValues, $SubReportItterationArray, true);
        if ($result != true) {
          if ($reportItem->ShowError) {
            return writeErrorString('Ошибка формирования вложенного отчета ' . $reportItem->Template_Descr . " с сообщением " . $result);
          }
        }
      } else {
        if ($reportItem->ShowError) {
          return writeErrorString('Ошибка загрузки вложенного отчета ' . $reportItem->Template_Descr . " с сообщением " . $result->msg);
        }
      }
    }
  }

  function writeErrorString($Text) { //создает абзац с форматированным текстовым сообщением
    $textStyle = ' color:#FF0000';
    $p = "<p style=\"$textStyle\">$Text";
    return $p;
  }

  if ($ThisIsSubReport == true) {//если это подотчет то параметры переданы из родителя и оформление свойств отчета не производится
    $ParamValues = $ParamValuesArray;
  } else {
// загружается функцией LoadCellStylesObject  из файла ODTReportConstants::CellStylesFileName
    $CellStylesArray = LoadCellStylesObject(ReportConstants::CellStylesFileName); // загружается объект содержащий определения стилей ячеек таблицы

    $ParamValues = array(); //здесь будут лежать значения для подстановки в запросы
//этот массив связан по ключам с набором настроенных параметров, но может быть дполнен несуществующими
//параметрами из списка входных параметров
    foreach ($HTML_Template->HTMLReportParams as $reportParam) { //значения по умолчанию беру как текущие
      $ParamValues[$reportParam->ParamCode] = $reportParam->ParamDefaultValue;
    }
    foreach ($ParamValuesArray as $key => $value) { //значения входных параметров подставляю как текущие
      $ParamValues[$key] = $value;
    }
  }

  $result = '';
//отформатировать значения параметров сообразно типам -  везде подставляю как строку
//сформировать запрос $HTML_Template->SQL + $HTML_Template->SQLConditions
  if (isset($HTML_Template->SQLConditions) && ($HTML_Template->SQLConditions != '')) {
// заменить параметры по коду на значение в  $HTML_Template->SQLConditions
    foreach ($ParamValues as $key => $value) {
      $HTML_Template->SQLConditions = str_replace(':' . $key . ':', $value, $HTML_Template->SQLConditions);
    }
    $sql = $HTML_Template->SQL . ' where 1=1 and ' . $HTML_Template->SQLConditions;
  } else {
    $sql = $HTML_Template->SQL;
  }
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    $row = array();
//если объект результата запроса не создан то создаю пустой чтоб дальше небыло обращений к пустоте в функциях
    if ($HTML_Template->ShowError == true) {
      $ErrMessage = my_escape_string('Ошибка выполнения запроса ' . PHP_EOL . $sql . PHP_EOL . $s_err . PHP_EOL);
      $result.=writeErrorString($ErrMessage);
    }
  } else {
    if (kometa_num_rows($res) > 0) {
      $row = kometa_fetch_object($res);
    } else {
      $row = array();
// надо ли проверять и уведомлять что запрос что то вернул?
      if ($HTML_Template->ShowEmptyMessage == true) {
        $ErrMessage = my_escape_string('Запрос вернул пустой набор данных' . PHP_EOL);
        $result.=writeErrorString($ErrMessage);
      }
    }
  }

  if (isset($HTML_Template->children))
    foreach ($HTML_Template->children as $reportItem) {
      switch ($reportItem->ItemType) {
        case 'paragraph': $result.=writeParagraph($reportItem, $row, $ParamValues, $CellStylesArray);
          break;
        case 'table': $result.=writeTable($reportItem, $row, $ParamValues, $CellStylesArray);
          break;
        case 'list': $result.=writeList($reportItem, $row, $ParamValues, $CellStylesArray);
          break;
        case 'embedded_report': $result.=writeSubReport($HTMLObject, $reportItem, $row, $ParamValues, $SubReportItterationArray);
          break;
      }
    }
  return $result;
}
