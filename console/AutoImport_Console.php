<?php

$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}
require_once("../2gConnection.php");
//echo '1';
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}
//echo '2';
require_once("../ProjectPath.php");
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}
//echo '3';
require_once("../DB2XML/UploadCRC.php");
$path_parts = pathinfo(__FILE__);
$workdir = $path_parts['dirname'];
if (isset($workdir)) {
  chdir($workdir);
}
//echo '4';
//require_once("../sys/common_import.php");

// считываем параметры определяющие пути необходимые для загрузки информации
//$loader_dir = get_Param_value('loader_dir');
//echo '5';
$loader_dir = GetPathConsole('loader_dir');
//$import_dir = get_Param_value('import_dir');
//echo '6';
$import_dir = GetPathConsole('import_dir');

echo 'import_dir=' . $import_dir . PHP_EOL;
$XMLFilesArray = scandir("$import_dir");
$structureXML = new DOMDocument('1.0', 'UTF-8');
foreach ($XMLFilesArray as $file) {
  set_time_limit(700);

  if (strtolower(substr($file, strlen($file) - 4)) == ".xml") {
    // если расширение файла xml 
    // открываем файл и смитрим какой шаблон в нем прописан
    try {
      $s = $import_dir . $file;
      echo 'import file=' . $s . PHP_EOL;
      if (!$structureXML->load($s)) {
        // перенести в каталог плохих файлов
        //echo '1' . PHP_EOL;
        set_bad_file($file);
        continue;
      }
    } catch (DOMException $e) {
      echo '2' . PHP_EOL;
      set_bad_file($file);
      continue;
    };
    $node = $structureXML->documentElement;
    $id = null;

    foreach ($node->childNodes AS $item)
      if (strtolower($item->nodeName) == "id") {
        $id = strtolower(trim($item->nodeValue));
        //echo $id.PHP_EOL;
        break;
      }

    if (isset($id)) {
      // схема загрузки определена ищем а есть ли такая схема
      $res = kometa_query("select id_xsd, id_data_type, id_source, id_periodicity, id_object, full_name, xsd, "
              . "code, loader_name, priority, is_history, substitution, object_row_tag, is_crc, is_std_import "
              . "from mbi_schema where lower(code)='$id' and is_history=0");
      if ($row = kometa_fetch_object($res)) {
        // счема найдена добавляем файл в список загрузки
        //!!!!! Здесь надо добавить еще проверку на соответствие XML-схеме
        // 
        // echo $id_xsd.PHP_EOL;
        $id_xsd = $row->id_xsd;
        // проверка на соответствие xsd
        $xsd = $row->xsd;

        if (isset($xsd) && ($xsd != '') && (!CheckUploadCRCFile($s, $xsd))) {// проверка CRC суммы
          // перенести в каталог плохих файлов
          set_bad_file($file);
          continue;
        } else {
          $id_xml = null;
          // Определеяем зарегистрирован ли этот файл в "Поступающие данные в формате XML" со статусом не загружался
          $res = kometa_query("select id_xml, id_xsd, id_source, id_status, file_name, dt_input from mbi_xml where file_name='$file' and id_status=1");
          if ($row = kometa_fetch_object($res)) {
            $id_xml = $row->id_xml;
          } else {
            // файл не найден, добавляем его в список для загрузки
            $sql = "INSERT INTO mbi_xml(id_xsd, id_status, file_name)  VALUES ( $id_xsd, 1, '$file');";
            kometa_query($sql);
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              echo $sql . '<br>' . $s_err . '<br>';
            }
          }
        }
      } else {
        // переносим файл в хранилище неизвестных
      }
    }
  }
}

//ключ массива - имя файла - оно уникально
$ResArray = Array();

// строим список загрузчиков
$s = "''";
chdir($loader_dir);
echo getcwd() . PHP_EOL;
$res = kometa_query('select mbi_schema.loader_name,mbi_xml.id_xml from mbi_xml,mbi_schema where mbi_xml.id_xsd=mbi_schema.id_xsd  and id_status=1 order by  mbi_schema.priority,mbi_xml.dt_input,mbi_xml.file_name');
while ($row = kometa_fetch_object($res)) {
  $s = "php " . $loader_dir . $row->loader_name . " " . $argv[1] . " " . $row->id_xml;
  echo $s . PHP_EOL;
  $output = null;
  exec($s, $output) . PHP_EOL;
  foreach ($output as $value) {
    echo $value . PHP_EOL;
  }
}
?>
