<?php

/*
 * формирование ODT документа по шаблону
 */
session_start();
require_once($_SESSION['LIB'] . 'PHP/phpodt-0.3.3/phpodt.php');

require_once($_SESSION['ProjectRoot'] . "Report/Report_classes.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "FileUpload/FileUploadBackEndFunction.php");

function LoadCellStylesObject($CellStylesFileName) {// загружается объект содержащий определения стилей ячеек таблицы
  $s2 = file_get_contents($_SESSION['APP_INI_DIR'] . $CellStylesFileName);
  $result1 = unserialize($s2);
  $arr = array();
  foreach ($result1 as $value) {
    $arr[$value->StyleName] = $value;
  }
  return $arr;
}

function BuildODTReportBody($Object, $Template, $ParamValuesArray, &$SubReportItterationArray, $ThisIsSubReport = false) { //формирование самого тела отчета по шаблону
  if ($ThisIsSubReport == true) {//если это подотчет то параметры переданы из родителя и оформление свойств отчета не производится
    $ParamValues = $ParamValuesArray;
  } else {
// загружается функцией LoadCellStylesObject  из файла ODTReportConstants::CellStylesFileName
    $CellStylesArray = LoadCellStylesObject(ReportConstants::CellStylesFileName); // загружается объект содержащий определения стилей ячеек таблицы
//задать формат страниц из $ODT_Template
    $pageStyle = new PageStyle('myPageStyle');

//    if ($ODT_Template->PageStartNumber != null)
    if ($Template->ShowPAGE_NUMBER == true) {
      if ($Template->FirstPageNumber == true) {
        $pageStyle->setFooterContent(StyleConstants::PAGE_NUMBER);
      } else {
        $pageStyle->setPageNumber(StyleConstants::FOOTER, StyleConstants::PAGENUMDISPLAY_PREVIOUS, 1);
      }
    }
    $PageSizeWidth = $Template->PageSizeWidth . 'cm';
    $PageSizeHeight = $Template->PageSizeHeight . 'cm';
    $pageStyle->setPageSize($PageSizeWidth, $PageSizeHeight);
    switch ($Template->PageOrientation) {
      case 'RB_PORTRAIT': $pageStyle->setOrientation(StyleConstants::PORTRAIT);
        break;
      case 'RB_LANDSCAPE': $pageStyle->setOrientation(StyleConstants::LANDSCAPE);
        break;
    }
    $MarginLeft = $Template->MarginLeft . 'cm';
    $MarginRight = $Template->MarginRight . 'cm';
    $pageStyle->setHorizontalMargin($MarginLeft, $MarginRight);
    $MarginTop = $Template->MarginTop . 'cm';
    $MarginBottom = $Template->MarginBottom . 'cm';
    $pageStyle->setVerticalMargin($MarginTop, $MarginBottom);

    $ParamValues = array(); //здесь будут лежать значения для подстановки в запросы
//этот массив связан по ключам с набором настроенных параметров, но может быть дполнен несуществующими
//параметрами из списка входных параметров
    foreach ($Template->ReportParams as $reportParam) { //значения по умолчанию беру как текущие
      $ParamValues[$reportParam->ParamCode] = $reportParam->ParamDefaultValue;
    }
    foreach ($ParamValuesArray as $key => $value) { //значения входных параметров подставляю как текущие
      $ParamValues[$key] = $value;
    }
  }

//отформатировать значения параметров сообразно типам -  везде подставляю как строку
//сформировать запрос $ODT_Template->SQL + $ODT_Template->SQLConditions
  if (isset($Template->SQLConditions) && ($Template->SQLConditions != '')) {
// заменить параметры по коду на значение в  $Template->SQLConditions
    foreach ($ParamValues as $key => $value) {
      $Template->SQLConditions = str_replace(':' . $key . ':', $value, $Template->SQLConditions);
    }
    $sql = $Template->SQL . ' where 1=1 and ' . $Template->SQLConditions;
  } else {
    $sql = $Template->SQL;
  }
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    $row = array();
//если объект результата запроса не создан то создаю пустой чтоб дальше небыло обращений к пустоте в функциях
    if ($Template->ShowError == true) {
      $ErrMessage = my_escape_string('Ошибка выполнения запроса ' . PHP_EOL . $sql . PHP_EOL . $s_err . PHP_EOL);
      writeErrorString($ErrMessage);
    }
  } else {
    if (kometa_num_rows($res) > 0) {
      $row = kometa_fetch_object($res);
    } else {
      $row = array();
// надо ли проверять и уведомлять что запрос что то вернул?
      if ($Template->ShowEmptyMessage == true) {
        $ErrMessage = my_escape_string('Запрос вернул пустой набор данных' . PHP_EOL);
        writeErrorString($ErrMessage);
      }
    }
  }

  if (isset($Template->children))
    foreach ($Template->children as $reportItem) {
      switch ($reportItem->ItemType) {
        case 'paragraph': writeParagraph($reportItem, $row, $ParamValues, $CellStylesArray);
          break;
        case 'table': writeTable($reportItem, $row, $ParamValues, $CellStylesArray);
          break;
        case 'list': writeList($reportItem, $row, $ParamValues, $CellStylesArray);
          break;
        case 'embedded_report': writeSubReport($Object, $reportItem, $row, $ParamValues, $SubReportItterationArray);
          break;
      }
    }
  return true;
}

function writeParagraph($Props, $ReportQueryRow, $ParamValues, $TextStylesArray) {
  $pStyle = new ParagraphStyle(uniqid('myPStyle')); //для каждого абзаца уникальный идентификатор
//задать стиль абзаца
  if (isset($Props->TextAlignment)) {
    switch ($Props->TextAlignment) {
      case 'RB_LEFT':$pStyle->setTextAlign(StyleConstants::LEFT);
        break;
      case 'RB_CENTER':$pStyle->setTextAlign(StyleConstants::CENTER);
        break;
      case 'RB_RIGHT':$pStyle->setTextAlign(StyleConstants::RIGHT);
        break;
      case 'RB_START':$pStyle->setTextAlign(StyleConstants::START);
        break;
      case 'RB_END':$pStyle->setTextAlign(StyleConstants::END);
        break;
      case 'RB_JUSTIFY':$pStyle->setTextAlign(StyleConstants::JUSTIFY);
        break;
    }
  }
//отступы с краев
  $LeftMargin = $Props->LeftMargin . 'cm';
  $RightMargin = $Props->RightMargin . 'cm';
  $pStyle->setHorizontalMargins($LeftMargin, $RightMargin);
//разрыв перед BreakBefore
  if (isset($Props->BreakBefore) && ($Props->BreakBefore == true)) {
    $pStyle->setBreakBefore(StyleConstants::PAGE);
  }
//сдвиг красной строки
  if (isset($Props->TextIdent)) {
    $TextIdent = $Props->TextIdent . 'cm';
    $pStyle->setTextIndent($TextIdent);
  }

  $p = new Paragraph($pStyle);

  if (isset($Props->children))
    foreach ($Props->children as $reportItem) {
      switch ($reportItem->ItemType) {
        case 'text':writeText($reportItem, $p, $ReportQueryRow, $ParamValues, $TextStylesArray);
          break;
        case 'hyperlink': writeHyperlink($reportItem, $p, $ReportQueryRow, $ParamValues);
          break;
        case 'image': writeImage($reportItem, $p, $ReportQueryRow, $ParamValues);
          break;
        case 'linebreak': $p->addLineBreak();
          break;
      }
    }
}

function writeText($Props, $Paragraph, $ReportQueryRow, $ParamValues, $TextStylesArray) {
  $textStyleCode = $Props->TextStyle;
  $textStyleObject = $TextStylesArray[$textStyleCode];
//задать стиль текста
  $textStyle = new TextStyle();
  if (isset($textStyleObject)) {
    $textStyle->setColor($textStyleObject->FontColor);
    $textStyle->setTextBackgroundColor($textStyleObject->BackgroundColor);
    if ($textStyleObject->Underline == true) {
      $textStyle->setTextUnderline();
    }
    if ($textStyleObject->Bold == true)
      $textStyle->setBold();
    if ($textStyleObject->Italic == true)
      $textStyle->setItalic();
    $FontSize = $textStyleObject->FontSize . 'pt';
    $textStyle->setFontSize($FontSize);
  }
  switch ($Props->DataSource) {
    case 'RB_Text':
      foreach ($ParamValues as $key => $value) {
        $Props->TextBlock = str_replace(':' . $key . ':', $value, $Props->TextBlock);
      }
      foreach ($ReportQueryRow as $key => $value) {
        $Props->TextBlock = str_replace(':' . $key . ':', $value, $Props->TextBlock);
      }
      $Paragraph->addText($Props->TextBlock, $textStyle);
      break;
    case 'RB_SQL': {
        $DataField = $Props->DataField;
        $Paragraph->addText($ReportQueryRow->$DataField, $textStyle);
      }
      break;
  }
}

function writeHyperlink($Props, $Paragraph, $ReportQueryRow, $ParamValues) {
  switch ($Props->DataSource) {
    case 'RB_Text': $Paragraph->addHyperlink($Props->LinkText, $Props->LinkURL, $Props->LinkText);
      break;
    case 'RB_SQL': {
        $DataField1 = $Props->DBLinkText;
        $DataField2 = $Props->DBLinkURL;
        $Paragraph->addHyperlink($ReportQueryRow->$DataField1, $ReportQueryRow->$DataField2, $ReportQueryRow->$DataField1);
      }
      break;
  }
}

function writeImage($Props, $Paragraph, $ReportQueryRow, $ParamValues) {
  $imgWidth = $Props->imgWidth . 'mm'; //The dimensions can be expressed with percentage "%" or one og these units ("mm", "cm", "in" (2.54cm), "pt" (1/72in)).
  $imgHeight = $Props->imgHeight . 'mm';
  switch ($Props->DataSource) {
    case 'RB_Text': if (file_exists($Props->ImagePath)) {
        $Paragraph->addImage($Props->ImagePath, $imgWidth, $imgHeight);
      }
      break;
    case 'RB_SQL': {
        $DataField = $Props->DataField;
        $md5FilePath = Get_md5FilePath($ReportQueryRow->$DataField);
        $serverFileName = $md5FilePath . $ReportQueryRow->$DataField;
        if (file_exists($serverFileName)) {
          $Paragraph->addImage($serverFileName, $imgWidth, $imgHeight);
        }
      }
      break;
  }
}

function ApplyStyle($textStyleObject, &$pStyle, &$textStyle) { //применяет стиль к объекту
  if (isset($textStyleObject)) {
    if (isset($textStyleObject->TextAlign)) {
      switch ($textStyleObject->TextAlign) {
        case 'RB_LEFT':$pStyle->setTextAlign(StyleConstants::LEFT);
          break;
        case 'RB_CENTER':$pStyle->setTextAlign(StyleConstants::CENTER);
          break;
        case 'RB_RIGHT':$pStyle->setTextAlign(StyleConstants::RIGHT);
          break;
        case 'RB_START':$pStyle->setTextAlign(StyleConstants::START);
          break;
        case 'RB_END':$pStyle->setTextAlign(StyleConstants::END);
          break;
        case 'RB_JUSTIFY':$pStyle->setTextAlign(StyleConstants::JUSTIFY);
          break;
      }
    }

    $textStyle->setColor($textStyleObject->FontColor);
    $textStyle->setTextBackgroundColor($textStyleObject->BackgroundColor);
    if ($textStyleObject->Underline == true) {
      $textStyle->setTextUnderline();
    }
    if ($textStyleObject->Bold == true)
      $textStyle->setBold();
    if ($textStyleObject->Italic == true)
      $textStyle->setItalic();
    $FontSize = $textStyleObject->FontSize . 'pt';
    $textStyle->setFontSize($FontSize);
  }
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

  $table = new Table(uniqid('table')); //для каждой таблицы уникальный идентификатор

  $tableStyle = new TableStyle($table->getTableName());
  $TabelWidth = $Props->TabelWidth . 'cm';
  $tableStyle->setWidth($TabelWidth);
  if (isset($Props->TableAlignment)) {
    switch ($Props->TableAlignment) {
      case 'RB_LEFT':$tableStyle->setAlignment(StyleConstants::LEFT);
        break;
      case 'RB_CENTER': $tableStyle->setAlignment(StyleConstants::CENTER);
        break;
      case 'RB_RIGHT':$tableStyle->setAlignment(StyleConstants::RIGHT);
        break;
      case 'RB_MARGINS':$tableStyle->setAlignment(StyleConstants::MARGINS);
        break;
    }
  }

  $table->setStyle($tableStyle);

  $ColCount=count($Props->children);
  $table->createColumns($ColCount, true);

  $ColumnsWidthArray = array();
  $FormatedHeaders = array();
  $headers = array();
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


//ширины колонок
  if (count($ColumnsWidthArray) >= $ColCount) {
    for ($i = 0; $i < $ColCount; $i++) {
      if (isset($ColumnsWidthArray[$i]) && ( floatval($ColumnsWidthArray[$i]) > 0)) {
        $ColumnWidth = $ColumnsWidthArray[$i] . 'cm';
        $columnStyle = $table->getColumnStyle($i);
        $columnStyle->setWidth($ColumnWidth);
      }
    }
  }

  //для того чтобы потом установить формат яцеек веду дополнительный массив
  //по матрице аналогичный заполняемому $rows в который записываю свойства ячеек для объекта получаемого getCellStyle(i, j)
  //которые не могу выставить пока не создан в документе массив ячеек фу-ей  addRows
  //после того как в документе появятся ячейки от addRows я к этим ячейкам обращусь и выставлю их свойства
  //если выводился addHeader то нумерация строк ячеек увеличится на единицу т.е. getCellStyle(1, 0) вернет ячейку заголовка

  if ($Props->ShowTableHeader == true) { //надо выводить заголовок
    $FormatedHeaders = array();
    $arrayRowProps = array();
        foreach ($Props->children as $key => $value) {
        if (!isset($value->HeaderStyle) || ($value->HeaderStyle == ''))
          $value->HeaderStyle = 'default';
        $pStyle = new ParagraphStyle(uniqid('myPStyleHeader'));
        $textStyle = new TextStyle();
          $textStyleObject = $TextStylesArray[$value->HeaderStyle];
          ApplyStyle($textStyleObject, $pStyle, $textStyle);
          array_push($arrayRowProps, $textStyleObject->VerticalAlign); //верт выравнивание
        $p = new Paragraph($pStyle);
        $p->addText($value->LabelColumn, $textStyle);
        array_push($FormatedHeaders, $p);
      }
    
    array_push($arrayCellProps, $arrayRowProps);
    $table->addHeader($FormatedHeaders, true);
  }

  $rows = array(); //массив строк
  if (kometa_num_rows($res) > 0) {
//    $i = 1;
    while ($row = kometa_fetch_object($res)) {
      $r = array();
      $arrayRowProps = array();
        foreach ($Props->children as $key => $value) {
        $pStyle = new ParagraphStyle(uniqid('myPStyle'));
        $textStyle = new TextStyle();

        $colName = $value->DataFieldColumn;
        $cellValue = $row->$colName;

        $colStyleName = $value->StyleFieldColumn;
        if (isset($colStyleName)) {
          $cellStyleValue = $row->$colStyleName;
        } else
          $cellStyleValue = null;
        if (isset($cellStyleValue) && ($cellStyleValue != '')) {
          $textStyleObject = $TextStylesArray[$cellStyleValue];
          ApplyStyle($textStyleObject, $pStyle, $textStyle);
          array_push($arrayRowProps, $textStyleObject->VerticalAlign); //верт выравнивание
        } else {
          array_push($arrayRowProps, '');
        }

        $p = new Paragraph($pStyle);
        $p->addText($cellValue, $textStyle);
        array_push($r, $p);
      }
      array_push($rows, $r);
      array_push($arrayCellProps, $arrayRowProps);
    }
  }

  if (count($rows) > 0) {
    $table->addRows($rows, true);
  }

//можно обратиться к стилям ячеек
  $cnt_arrayCellProps = count($arrayCellProps);
  for ($j = 0; $j < $cnt_arrayCellProps; $j++) {
    $arrayRowProps = $arrayCellProps[$j];
    if (isset($arrayRowProps) && (is_array($arrayRowProps))) {
      for ($i = 0; $i < $ColCount; $i++) {
        $VerticalAlign = $arrayRowProps[$i];
        if (isset($VerticalAlign) && ($VerticalAlign != '')) {
          $cellStyle = $table->getCellStyle($i, $j);
          switch ($VerticalAlign) {
            case 'RB_TOP':$cellStyle->setVerticalAlign(StyleConstants::TOP);
              break;
            case 'RB_MIDDLE': $cellStyle->setVerticalAlign(StyleConstants::MIDDLE);
              break;
            case 'RB_BOTTOM':$cellStyle->setVerticalAlign(StyleConstants::BOTTOM);
              break;
            case 'RB_AUTO':$cellStyle->setVerticalAlign(StyleConstants::AUTO);
              break;
          }
          // $cellStyle1->setBgColor('#00f0ff');
          if ($Props->ShowTableBorder)
            $cellStyle->setBorder('#000000');
          //$cellStyle1->setBgImage('image.png', StyleConstants::NO_REPEAT, StyleConstants::CENTER);
          //$cellStyle1->setPadding('0.2cm');
        }
      }
    }
  }
}

function writeList($Props, $ReportQueryRow, $ParamValues, $TextStylesArray) { //строит элемент отчета список
  $StyleName = $Props->TextStyle;
  if (!empty($StyleName)) {
    $pStyle = new ParagraphStyle(uniqid('myListParagrStyle'));
    $textStyle = new TextStyle(uniqid('myListTextStyle'));
    $textStyleObject = $TextStylesArray[$StyleName];
    ApplyStyle($textStyleObject, $pStyle, $textStyle);
  } else {
    $pStyle = null;
    $textStyle = null;
  }
  switch ($Props->DataSource) {
    case 'RB_Text': {
        $ListItems = explode("\n", $Props->ListValues);
        $list = new ODTList(array());
        foreach ($ListItems as $listField) {
          $list->addItem($listField, $pStyle, $textStyle);
        }
      }
      break;
    case 'RB_SQL': {
//сформировать запрос $ODT_Template->SQL + $ODT_Template->SQLConditions
        if (isset($Props->SQLConditions) && ($Props->SQLConditions != '')) {
// заменить параметры по коду на значение в  $ODT_Template->SQLConditions
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
          $list = new ODTList(array());
          while ($row = kometa_fetch_object($res)) {
            $list->addItem($row->$listField, $pStyle, $textStyle);
          }
        }
      }
      break;
  }
}

function writeSubReport($Object, $reportItem, $row, $ParamValues, &$SubReportItterationArray) {// строит вложенный отчет
  $b = $SubReportItterationArray[$reportItem->Template_Code];
  if (($b != null) || ($b == true)) {//предотвращаю рекурсию вложенных отчетов
    if ($reportItem->ShowError) {
      writeErrorString('Вложенный отчет ' . $reportItem->Template_Descr . " уже был сформирован в рамках основного" . PHP_EOL .
              'Для предотвращения зацикливания он больше не формируется!');
    }
    return;
  } else {
    $result = LoadTemplate($reportItem->Template_Code);
    if ($result->success == true) {
//что делать с набором данных $row родительского отчета надо ли его подставлять во вложенный
      $result = BuildODTReportBody($Object, json_decode($result->result), $ParamValues, $SubReportItterationArray, true);
      if ($result != true) {
        if ($reportItem->ShowError) {
          writeErrorString('Ошибка формирования вложенного отчета ' . $reportItem->Template_Descr . " с сообщением " . $result);
        }
      }
    } else {
      if ($reportItem->ShowError) {
        writeErrorString('Ошибка загрузки вложенного отчета ' . $reportItem->Template_Descr . " с сообщением " . $result->msg);
      }
    }
  }
}

function writeErrorString($Text) { //создает абзац с форматированным текстовым сообщением
  $textStyle = new TextStyle();
  $textStyle->setTextBackgroundColor('#FF0000');
  $pStyle = new ParagraphStyle('myPStyle');
  $pStyle->setTextAlign(StyleConstants::CENTER);

  $p = new Paragraph($pStyle);
  $p->addText($Text, $textStyle);
}
