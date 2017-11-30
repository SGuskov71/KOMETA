<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

class ExportToFile_class {

  function GenerateExcelFile($id_object, $columns, $FILTER) {
    global $db_usr;
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if (!file_exists($_SESSION['UserData'] . $db_usr) || !is_dir($_SESSION['UserData'] . $db_usr)) {
      $result = new JSON_Result(false, ('Каталог "' . $_SESSION['UserData'] . $db_usr . '" не существует или не доступен'), NULL);
      echo json_encode($result);
      exit();
    }

    require_once($_SESSION['LIB'] . 'PHP/PHPExcel/Classes/PHPExcel.php');

    $sysname = get_sysname($id_object);
    $sql = "SELECT fieldname from mb_object_field where id_object=$id_object";
    $res = kometa_query($sql);
    $fld_list = '';
    $coma = '';
    while ($row = kometa_fetch_object($res)) {
      $fld_list.=$coma . $row->fieldname;
      $coma = ',';
    }
    $QuerySQL = "SELECT $fld_list from $sysname as t ";
    if (isset($FILTER) && ($FILTER != '')) {
      $QuerySQL .=' where ' . $FILTER;
    }

    $i = 1;
    $j = 0;
    $objPHPExcel = new PHPExcel();
    $locale = 'ru';
    $validLocale = PHPExcel_Settings::setLocale($locale);

    $objPHPExcel->setActiveSheetIndex(0);
    $active_sheet = $objPHPExcel->getActiveSheet();

    $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
    $cacheSettings = array('memoryCacheSize ' => '64MB');
    PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

    foreach ($columns as $key => $value)
      if ($value->visible) {
        $active_sheet->setCellValueByColumnAndRow($j, $i, $value->caption);
        $sj = PHPExcel_Cell::stringFromColumnIndex($j);
        $maxStrLen[$sj] = strlen($value->caption);
        $active_sheet->getStyle($sj . "1")->GetFont()->SetBold(true);
        $j++;
      };

    $i++;
    $res = kometa_query($QuerySQL);
    while ($row = kometa_fetch_object($res)) {
      $j = 0;
      foreach ($columns as $Item => $value)
        if ($value->visible) {
          $active_sheet->setCellValueByColumnAndRow($j, $i, $row->$Item);
          $sj = PHPExcel_Cell::stringFromColumnIndex($j);
          $l = strlen($row->$Item);
          if ($l > $maxStrLen[$sj])
            $maxStrLen[$sj] = $l;
          $j++;
        }
      $i++;
    }

    foreach ($maxStrLen as $key => $value)
      $active_sheet->getColumnDimension($key)->setWidth($value + 5);

// Save Excel5 file
    $fileName = $_SESSION['UserData'] . $db_usr . '/' . session_id() . '.xls';
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save($fileName);
// Echo memory peak usage
//echo " Максимальное количество использованной памяти: ", (memory_get_peak_usage(true) / 1024 / 1024), " MB", EOL;
// save rtf document
    $ExcelFileNameLink = $_SESSION['URLUserData'] . $db_usr . '/' . session_id() . '.xls';

    $result = new JSON_Result(true, 'Файл в формате Excel сформирован', $ExcelFileNameLink);
    return $result;
  }

  function GenerateRTFTableFile($id_object, $columns, $FILTER) {
    global $db_usr;
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if (!file_exists($_SESSION['UserData'] . $db_usr) || !is_dir($_SESSION['UserData'] . $db_usr)) {
      $result = new JSON_Result(false, ('Каталог "' . $_SESSION['UserData'] . $db_usr . '" не существует или не доступен'), NULL);
      return $result;
    }

    $rowHeight = 1;
    $columnWidth = 1;
    $HeaderHeight = 1;
    $PageWidth = 42; //А3
    $PageHeight = 29.7;
    $rowCount = 0;
    $columnCount = 0;

    $sysname = get_sysname($id_object);
    $sql = "SELECT fieldname from mb_object_field where id_object=$id_object";
    $res = kometa_query($sql);
    $fld_list = '';
    $coma = '';
    while ($row = kometa_fetch_object($res)) {
      $fld_list.=$coma . $row->fieldname;
      $coma = ',';
    }
    $QuerySQL = "SELECT $fld_list from $sysname as t ";
    if (isset($FILTER) && ($FILTER!= '')) {
      $QuerySQL .=' where ' . $FILTER;
    }


    $columnCount = 0;
    foreach ($columns as $key => $value)
      if ($value->visible) {
        $columnCount++;
      }
 require_once($_SESSION['LIB'] . 'PHP/phprtflite-1.2.0/lib/PHPRtfLite.php');
   PHPRtfLite::registerAutoloader();
    $rtf = new PHPRtfLite();
    $rtf->setLandscape();
    $rtf->setPaperWidth($PageWidth);  // in cm
    $rtf->setPaperHeight($PageHeight); // in cm
    if ($columnCount > 0) {
      $columnWidth = ($PageWidth - $rtf->getMarginLeft() - $rtf->getMarginRight()) / $columnCount;
    }
//объекты оформлений
    $border = new PHPRtfLite_Border(
            $rtf, new PHPRtfLite_Border_Format(1, '#000000'), new PHPRtfLite_Border_Format(1, '#000000'), new PHPRtfLite_Border_Format(1, '#000000'), new PHPRtfLite_Border_Format(1, '#000000')
    );
    $CaptionFont = new PHPRtfLite_Font(14, 'Arial', '#000000', '#FFFFFF');
    $CaptionFont->setBold();

    $CaptionParFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_CENTER);

    $TitleFont = new PHPRtfLite_Font(12, 'Courier', '#000000', '#FFFFFF');
    $TitleFont->setBold();

    $sect = $rtf->addSection();
    $sect->writeText($_SESSION['ExecuteTask'] . '
    ', $CaptionFont, $CaptionParFormat);

//$sect = $rtf->addSection();
    $table = $sect->addTable();
    $table->addRow($HeaderHeight);
    $rowCount = 1;
    $columnCount = 0;
    foreach ($columns as $Item => $value)
      if ($value->visible) {
        $columnCount++;
        $table->addColumn($columnWidth);
        //   $table->writeToCell($rowCount, $columnCount, $Item);
        $cell = $table->getCell($rowCount, $columnCount);
        $cell->writeText($value->caption, $TitleFont);
        $cell->setTextAlignment(PHPRtfLite_Table_Cell::TEXT_ALIGN_CENTER);
        $cell->setVerticalAlignment(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_CENTER);
      };

    $res = kometa_query($QuerySQL);
    while ($row = kometa_fetch_object($res)) {
      $x = 0;
      $table->addRow($rowHeight);
      foreach ($columns as $Item => $value)
        if ($value->visible) {
          $cell = $table->getCell($rowCount + 1, $x + 1);
          $cell->writeText($row->$Item);
          $cell->setTextAlignment(PHPRtfLite_Table_Cell::TEXT_ALIGN_CENTER);
          $cell->setVerticalAlignment(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_CENTER);
          $x++;
        }
      $rowCount++;
    }

    $table->setBorderForCellRange($border, 1, 1, $rowCount, $columnCount);
// save rtf document
    $RTFFileName = $_SESSION['UserData'] . $db_usr . '/' . session_id() . '.rtf';
    $rtf->save($RTFFileName);
//дать в линуксе разрешения для папки
    $RTFFileNameLink = $_SESSION['URLUserData'] . '/' . $db_usr . '/' . session_id() . '.rtf';

    $result = new JSON_Result(true, 'Файл в формате HTML сформирован', $RTFFileNameLink);
    return $result;
  }

  function GenerateHTMTableFile($id_object, $columns, $FILTER) {
    global $db_usr;
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    if (!file_exists($_SESSION['UserData'] . $db_usr) || !is_dir($_SESSION['UserData'] . $db_usr)) {
      $result = new JSON_Result(false, ('Каталог "' . $_SESSION['UserData'] . $db_usr . '" не существует или не доступен'), NULL);
      return  $result;
    }

    $rowCount = 0;
    $columnCount = 0;

    $sysname = get_sysname($id_object);
    $sql = "SELECT fieldname from mb_object_field where id_object=$id_object";
    $res = kometa_query($sql);
    $fld_list = '';
    $coma = '';
    while ($row = kometa_fetch_object($res)) {
      $fld_list.=$coma . $row->fieldname;
      $coma = ',';
    }
    $QuerySQL = "SELECT $fld_list from $sysname as t ";
    if (isset($FILTER) && ($FILTER != '')) {
      $QuerySQL .=' where ' . $FILTER;
    }


    $CaptionArray = array();
    $FieldNameArray = array();

    $rowCount = 1;
    $columnCount = 0;
    $h = $h . "<html>" . chr(10);
    $h = $h . "<head>" . chr(10);
    $h = $h . "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />" . chr(10);
    $h = $h . "<title>" . chr(10);
    $h = $h . $_SESSION['ExecuteTask'] . chr(10);
    $h = $h . "</title>" . chr(10);
    $h = $h . "<style>" . chr(10);
    $h = $h . "table {
    border: 1; 
   }
   td {
    border: 1; 
    empty-cells: show;
   }
  </style>" . chr(10);

    $h = $h . "</head>" . chr(10);
    $h = $h . "<body>" . chr(10);
    $h = $h . "<table border=1 cellpadding='0' cellspacing='0'>" . chr(10);
    $h = $h . "<tr>" . chr(10);

    foreach ($columns as $key => $value)
      if ($value->visible) {
        $columnCount++;
        $h = $h . "<td align='center'><b>$value->caption</b></td>" . chr(10);
      };
    $h = $h . "</tr>" . chr(10);
    $HTMFileName = $_SESSION['UserData'] . $db_usr . '/' . session_id() . '.html';
    $fl = fopen($HTMFileName, "w");
    fwrite($fl, $h);

    $res = kometa_query($QuerySQL);
    while ($row = kometa_fetch_object($res)) {
      $s = "<tr>" . chr(10);
      $x = 0;
      foreach ($columns as $Item => $value)
        if ($value->visible) {
          $s = $s . "<td>" . $row->$Item . "</td>" . chr(10);
          $x++;
        }
      $s = $s . "</tr>" . chr(10);
      fwrite($fl, $s);
      $rowCount++;
    }

    $HTMFileNameLink = $_SESSION['URLUserData'] . $db_usr . '/' . session_id() . '.html';
    $f = $f . "</table>" . chr(10);
    $f = $f . "</body>" . chr(10);
    $f = $f . "</html>" . chr(10);
    fwrite($fl, $f);
    fclose($fl);

    $result = new JSON_Result(true, 'Файл в формате HTML сформирован', $HTMFileNameLink);
    return $result;
  }

}
