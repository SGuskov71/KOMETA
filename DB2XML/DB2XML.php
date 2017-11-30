<?php
$sapi = php_sapi_name();
if ($sapi == 'cli') {
  require_once("../ProjectPath.php");
  $ProjectRoot = GetPathConsole('ProjectRoot');
  $LIB = GetPathConsole('LIB');
    $export_dir = GetPathConsole('export_dir');

} else {
  $ProjectRoot = $_SESSION['ProjectRoot'];
  $LIB = $_SESSION['LIB'];
  $export_dir=$_SESSION['export_dir'];
}
if ($sapi != 'cli') {
  echo '<HTML>';
  echo '  <HEAD>';
  echo '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
  echo '    <TITLE>Стандартый загрузчик из формата обмена между экземплярами </TITLE>';
  echo '  </HEAD>';
  echo '  <BODY>';
}
set_time_limit(70000);
session_start();
require_once($ProjectRoot . "2gConnection.php");

require_once($ProjectRoot . "DB2XML/UploadCRC.php");
require_once($ProjectRoot . "sys/mb_common.php");

global $f; // Файл для сохранения езультатов выгрузки

function GetQueryBySettingsNode2XML(&$_Out, $CurNode, $ParentQuery, $LogFileName, $ParentROWTAG) {
  global $f; // Файл для сохранения
  global $type_login;
  global $export_dir;
  $result = 1;
  if (isset($CurNode)) {
    $SQL = $CurNode->getAttribute('SQLTEXT');
    if (isset($SQL) && ($SQL != '')) {
      if (isset($ParentQuery)) {
        $ParamsNode = $CurNode->childNodes; //getElementsByTagName('PARAMS');
        if (isset($ParamsNode)) {
          $ParamNodes = $ParamsNode->item(1)->childNodes;
          if (isset($ParamNodes)) {
            foreach ($ParamNodes as $ParamNode) {
              if (is_a($ParamNode, 'DOMElement') && ($ParamNode->tagName == 'PARAM')) {
                $PARAMNAME = $ParamNode->getAttribute('PARAMNAME');
                $MASTERKEYFIELDNAME = $ParamNode->getAttribute('MASTERKEYFIELDNAME');
                $PARAMValue = $ParentQuery->$MASTERKEYFIELDNAME;
                $SQL = str_replace(':' . $PARAMNAME, $PARAMValue, $SQL);
              }
            }
          }
        }
      }
      try {
        $res = kometa_query($SQL);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          echo $SQL . '<br>' . $s_err . '<br>';
        }
      } catch (Exception$e) {
//            WriteLogStr($LogFileName, $e->getMessage());
        return 0;
      };
      while ($row = kometa_fetch_object($res)) {
        set_time_limit(7000);
        if (isset($ParentROWTAG) && ($ParentROWTAG != ''))
          $_Out .= "<$ParentROWTAG>";
        //$needCloseTag = 1;
        foreach ($row as $fieldname => $fieldvalue) {
          $_Out .= "<$fieldname>" . htmlspecialchars($fieldvalue, ENT_QUOTES, 'UTF-8') . "</$fieldname>";
        }
        $QUERYNodes = $CurNode->childNodes;
        if (isset($QUERYNodes)) {
          foreach ($QUERYNodes as $QUERYNode) {
            if (is_a($QUERYNode, 'DOMElement') && ($QUERYNode->tagName == 'QUERY')) {
              $ROWSTAG = $QUERYNode->getAttribute('ROWSTAG');
              $ROWTAG = $QUERYNode->getAttribute('ROWTAG');
              if (isset($ROWSTAG) && ($ROWSTAG != '')) {
                //$needCloseTag = 0;
                $_Out .= PHP_EOL . '<' . $ROWSTAG . '>' . PHP_EOL;
              }
              $_Out1 = '';
              fwrite($f, $_Out);
              $_Out = '';
              $result = GetQueryBySettingsNode2XML($_Out1, $QUERYNode, $row, $LogFileName, $ROWTAG);
              $_Out .= $_Out1;
              if (isset($ROWSTAG) && ($ROWSTAG != '')) {
                $_Out .= '</' . $ROWSTAG . '>' . PHP_EOL;
                fwrite($f, $_Out);
                $_Out = '';
              }
            }
          }
        }
        //if ($needCloseTag) {
        if (isset($ParentROWTAG) && ($ParentROWTAG != ''))
          $_Out .= "</$ParentROWTAG>" . PHP_EOL;
        //}
        fwrite($f, $_Out);
        $_Out = '';
      }
    }
  }
  return $result;
}

function DatasetToXML($XMLSettings, $OutFileName) {
  global $f; // Файл для сохранения езультатов выгрузки
  global $type_login;
  //set_error_handler(null); //выключаю запись ошибок в файл  из DM
  $result = 0;
  $LogFileName = $OutFileName . '.log';
  $Out = null;
  $resultItteration = 0;
  $XMLDoc = new DOMDocument();
  $XMLDoc->loadXML($XMLSettings);
  $XMLHEADER_nodes = $XMLDoc->getElementsByTagName("XMLHEADER");
  if (file_exists($OutFileName . ".prev")) {
    unlink($OutFileName . ".prev");
  }

  if (file_exists($OutFileName)) {
    rename($OutFileName, $OutFileName . ".prev");
  }
  $f = fopen($OutFileName, 'x');
  if (!empty($f)) {
    flock($f, LOCK_EX); //блокировка
    foreach ($XMLHEADER_nodes as $XMLHEADER_node) {
      foreach ($XMLHEADER_node->childNodes as $child) {
        if ($child->nodeType == XML_CDATA_SECTION_NODE) {
          $XMLHEADER = $child->textContent;
        }
      }
    }
    $COMMENT_nodes = $XMLDoc->getElementsByTagName("COMMENT");
    foreach ($COMMENT_nodes as $COMMENT_node) {
      foreach ($COMMENT_node->childNodes as $child) {
        if ($child->nodeType == XML_CDATA_SECTION_NODE) {
          $COMMENT = $child->textContent;
        }
      }
    }
    $QUERYDATA = $XMLDoc->documentElement;
    if (isset($QUERYDATA) && $QUERYDATA->hasChildNodes()) {
      $XMLDATATAG = $QUERYDATA->getAttribute('XMLDATATAG');
      $sysname = $QUERYDATA->getAttribute('sysname');
      $SCHEMA_ID = $QUERYDATA->getAttribute('SCHEMA_ID');
      $QUERYNodes = $QUERYDATA->childNodes;
      if (isset($QUERYNodes)) {
        $Out = $XMLHEADER . PHP_EOL;
        $Out .= '<!--' . $COMMENT . ' -->' . PHP_EOL;
        $Out .= "<$XMLDATATAG sysname=\"$sysname\">" . PHP_EOL;
        if (trim($SCHEMA_ID) <> '') {
          $Out .= '<Id>' . $SCHEMA_ID . '</Id>' . PHP_EOL;
        }
        foreach ($QUERYNodes as $QUERYNode) {
          if (is_a($QUERYNode, 'DOMElement') && ($QUERYNode->tagName == 'QUERY')) {
            $ROWSTAG = $QUERYNode->getAttribute('ROWSTAG');
            $ROWTAG = $QUERYNode->getAttribute('ROWTAG');
            if (isset($ROWSTAG) && isset($ROWTAG)) {
              if (trim($ROWSTAG) <> '') {
                $Out .= '<' . $ROWSTAG . '>' . PHP_EOL;
              }
              fwrite($f, $Out);
              $Out = '';
              $resultItteration = GetQueryBySettingsNode2XML($Out, $QUERYNode, null, $LogFileName, $ROWTAG);
              if (trim($ROWSTAG) <> '') {
                $Out .= '</' . $ROWSTAG . '>' . PHP_EOL;
              }
              fwrite($f, $Out);
              $Out = '';
            }
          }
        }
        $Out .= "</$XMLDATATAG>";
        $result = 1;
      }
    }
    /*
      if ($result == 1) {
      if (file_exists($OutFileName . ".prev")) {
      unlink($OutFileName . ".prev");
      }

      if (file_exists($OutFileName)) {
      rename($OutFileName, $OutFileName . ".prev");
      }
     */
    fwrite($f, $Out);
    flock($f, LOCK_UN);
    fclose($f);
  } else
    $result = -1;
  return $result;
//      }
}

function UploadDatasetToXML($id_shema_export) {
  global $f; // Файл для сохранения
  global $type_login;
  global $export_dir;
  $result = -1;
  $sqlOut = 'select mbo_schema.template,mbo_schema.code,mbo_schema.query_after'
          . ',mbs_receive.code as receive  from mbo_schema '
          . 'left join mbs_receive on mbs_receive.id_receiver=mbo_schema.id_receiver'
          . ' where id_xsd=' . $id_shema_export;
  $res = kometa_query($sqlOut);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    echo $sqlOut . '<br>' . $s_err . '<br>';
  }
  if ($row = kometa_fetch_object($res)) {
    set_time_limit(7000);
    $Settings_xml = $row->template;
    //      $dir = get_Param_value('export_dir');
    $dir = $export_dir;
    if (isset($row->receive))
      $dir = $dir . $row->receive;

    if (!file_exists($dir)) {
      mkdir($dir);
    }


    $file_name = $row->code . date('Ymd') . '.xml';
    $filename = $dir . '/' . $file_name;
    $result = DatasetToXML($Settings_xml, $filename);
    if ($result > 0) {
      if (!CreateUploadCRCFile($filename, $Settings_xml)) {
        print('Контрольная сумма не сформирована');
      };
      print('Данные выгружены в файл  ' . $filename);
      // производим регистрацию выгрузки в файле
      $file_name = my_escape_string($file_name);
      if ($type_login == 3)
        $sqlOut = "INSERT INTO mbo_xml(id_xsd, file_name, dt_output)  VALUES ($id_shema_export, $file_name, getdate())";
      else
        $sqlOut = "INSERT INTO mbo_xml(id_xsd, file_name, dt_output)  VALUES ($id_shema_export, $file_name, CURRENT_DATE)";
      $res = kometa_query($sqlOut);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        echo $sqlOut . '<br>' . $s_err . '<br>';
      }
      // Выполняем команду после выгрузки
      kometa_query($row->query_after);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        if ($sapi != 'cli')
          echo $row->query_after . '<br>' . $s_err . '<br>';
      }
    } else {
      if ($sapi != 'cli')
        print('Обработка не выполнена');
      if ($result == -1) {
        if ($sapi != 'cli')
          print(' Ошибка открытия файла <br>');
      }
    };
  }
  return($result);
}

$sapi = php_sapi_name();
//echo $sapi;
if ($sapi == 'cli') {//если это консольный запуск то параметры из командной строки
  if (isset($argv[2]) && (intval($argv[2]) > 0)) {
    if (UploadDatasetToXML(intval($argv[2])) > 0) {
      print(' Выгружен ' . $argv[2]);
    }
  } else
    print('Не передан параметр id_xsd ');
} else {
  if ((isset($_GET["id_xsd"])) && (intval($_GET["id_xsd"]) > 0)) {
    if (UploadDatasetToXML(intval($_GET["id_xsd"])) > 0) {
      
    }
  } else
    print('Не передан параметр id_xsd= "' . $_GET["SID"] . '"');
}
if ($sapi == 'cli') {
  echo '</body></html>';
}
?>