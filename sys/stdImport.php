<?php


$sapi = php_sapi_name();
if ($sapi == 'cli') {
  require_once("../ProjectPath.php");
  $ProjectRoot = GetPathConsole('ProjectRoot');
  $LIB = GetPathConsole('LIB');
} else {
  $ProjectRoot = $_SESSION['ProjectRoot'];
  $LIB = $_SESSION['LIB'];
}
if ($sapi != 'cli') {
  echo '<HTML>';
  echo '  <HEAD>';
  echo '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
  echo '    <TITLE>Стандартый загрузчик из формата обмена между экземплярами ЕБД</TITLE>';
  echo '  </HEAD>';
  echo '  <BODY>';
}
require_once($ProjectRoot . "2gConnection.php");

require_once($LIB . 'PHP/PEAR/Table.php');
//require_once($ProjectRoot . "sys/common_import.php");
require_once($ProjectRoot . "sys/mb_common.php");

if ($sapi == 'cli')
  $sys = 0;
else {
  if ($_GET['sys'] == 1) {
    $sys = 1;
  } else {
    $sys = 0;
  }
}

$cntins = 0;
$cntupd = 0;
$cntskip = 0;
$cnterr = 0;
$cntwarning = 0;

function working($id_xml) {
  global $sys;
  global $sapi;
  global $cntins; // сколько добавлено
  global $cntupd; // сколько заменено
  global $cnterr; // сколько ошибок
  global $cntskip; // сколько пропущено
  global $cntwarning; //предупреждений
  global $type_login;
//загрузка sql запросами из  mbi_schema в поле substitution
//формат substitution представление массива строками вида:        
//имя поля=>sql запрос с :параметром(имя поля из $id_xml)PHP_EOL
//sql запрос должен возвращать значение в имя поля             select F1 as "имя поля"

  updateUserSessionTtl();
  $structureXML = new DOMDocument('1.0', 'UTF-8');

  if (!file_exists(get_xml_filename($id_xml, $sys))) {
    if ($sapi != 'cli') {
      echo 'Файл не найден';
    } else {
      echo 'File Not Found';
    }
    set_status_xml_filename($id_xml, 4);
    exit;
  }
//получаю ключ по файлу в mbi_schema            
  $sql = "SELECT id_xml, id_xsd, id_source, id_status, file_name, dt_input FROM mbi_xml where id_xml=$id_xml";
  $res = kometa_query($sql);
  if (!isset($res)) {
    die('Ошибка выполнения запроса ' . $sql);
  }
  $row = kometa_fetch_object($res);
  $id_xsd = $row->id_xsd;
  if (isset($id_xsd)) {
//получаю массив substitution         
    $sql = "SELECT id_xsd, id_data_type, id_source, id_periodicity, id_object, full_name, xsd, "
            . "code, loader_name, priority, is_history, substitution, object_row_tag, is_crc, is_std_import "
            . "FROM mbi_schema where id_xsd=$id_xsd";
    $res = kometa_query($sql);
    if (!isset($res)) {
      die('Ошибка выполнения запроса ' . $sql);
    }
    $row = kometa_fetch_object($res);
    $object_row_tag = $row->object_row_tag;
    if (!isset($object_row_tag) || ($object_row_tag == ''))
      $object_row_tag = 'mb_ObjectFields';
    $substitution = $row->substitution;
    if (!isset($row->id_object)) {
      if ($sapi != 'cli')
        echo 'Не зарегистрирован объект, в который необходимо осуществить загрузку информации ';
      ProtWriteInput(1, $id_xml, 'Не зарегистрирован объект, в который необходимо осуществить загрузку информации ', 'Не зарегистрирован объект, в который необходимо осуществить загрузку информации');
      exit(1);
    }
    $sysname = get_sysname($row->id_object);
    $id_object = $row->id_object;
    if (isset($substitution) && ($substitution != '')) {
      $SQLSubstitutionArray = string2KeyedArray($substitution, ';');
    } else {
      $SQLSubstitutionArray = Array();
    }
  }
  // выставляем признак наличия поля id_xml
  $sql = "SELECT  count(*) as cnt FROM  information_schema.COLUMNS c where c.column_name='id_xml'  and table_name='$sysname'";
  $res = kometa_query($sql);
  $row = kometa_fetch_object($res);
  if ($row->cnt > 0)
    $is_id_xml = true;

  if ($sapi == 'cli')
    echo PHP_EOL.'loading file ' . get_xml_filename($id_xml, $sys) . PHP_EOL;
  $structureXML->load(get_xml_filename($id_xml, $sys));
  $node = $structureXML->documentElement;

  $cnterr = 0;

  if (!table_exists($sysname)) {
    ProtWriteInput(1, $id_xml, 'Отсутствует таблица для занесения информации ', 'Не заполнено наименование TableName=' . $sysname);
    exit();
  }

  // формируем список полей и определяем ключевое поле
  $sql = "SELECT mb_object_field.mandatory,mb_object_field.fieldname,is_field_key is_key "
          . " FROM mb_object_field where mb_object_field.id_object=$id_object";
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    if ($sapi != 'cli')
      echo "Ошибка получения списка полей по информационному объекту с кодом $sysname<br>";
    ProtWriteInput(1, $id_xml, 'Ошибка получения списка полей ', $sql . '<br>' . $s_err . '<br>');
    exit;
  }

  $fld_key = null;
  $flds = Array();
  $flds_mandatory = Array();
  $flds_default = Array();
  $x = 0;
  while ($row = kometa_fetch_object($res)) {
    // проверяем определено ли для этого поля значение по умолчанию
    if ($type_login == 3) {
      $sql1 = "SELECT t.name table_name, c.name column_name"
              . ", case when (c.is_identity=1) "
              . " or Exists(select * from sys.default_constraints dc where dc.object_id=t.object_id and dc.parent_column_id=c.column_id) then 1 else 0 end column_default  "
              . "FROM sys.tables as t join sys.all_columns as c on t.object_id=c.object_id "
              . "join sys.schemas as s on s.schema_id=t.schema_id "
              . "where t.type_desc='USER_TABLE' and s.name='dbo' "
              . "and c.name='" . $row->fieldname . "' and t.name='$sysname'";
    } else
      $sql1 = "SELECT  t.table_name, c.column_name, c.column_default "
              . " FROM information_schema.TABLES t JOIN information_schema.COLUMNS c ON t.table_name::text = c.table_name::text "
              . " WHERE t.table_schema::text = 'public'::text AND t.table_catalog::name = current_database() AND "
              . " t.table_type::text = 'BASE TABLE'::text AND NOT \"substring\"(t.table_name::text, 1, 1) = '_'::text"
              . " and c.column_default is  not null and c.column_name='" . $row->fieldname . "' and t.table_name='$sysname'";
    $res1 = kometa_query($sql1);
    $flds_default[$row->fieldname] = (kometa_num_rows($res1) > 0);

    if ($row->mandatory == 1) {
      if ($flds_default[$row->fieldname])
        $flds_mandatory[$row->fieldname] = 0;
      else
        $flds_mandatory[$row->fieldname] = 1;
    } else
      $flds_mandatory[$row->fieldname] = 0;
    if ($row->is_key) {
      $fld_key = $row->fieldname;
    }
    $flds[$row->fieldname] = '';

    $x++;
  }

  $fld_code = get_code_field($sysname);
  $b = array_keys($flds);
//повсем записям загружаемого файла
  $field_history = get_history_field($sysname);
  $exists_is_history = isset($field_history);

  foreach ($node->getElementsByTagName($object_row_tag) AS $domNode) {
    set_time_limit(7000);
    // Зачистка значений полей
    foreach ($flds as $key1 => $value1) {
      $flds[$key1] = '';
    }
//заполняем в масиив все значения XML файла

    foreach ($domNode->childNodes AS $aNode) {
      $a = $aNode->tagName;

      $flds[strtolower($a)] = trim($domNode->getElementsByTagName($a)->item(0)->nodeValue);
      $n = $domNode->getElementsByTagName($a)->item(0);
      if (isset($n)) {
        $atr = $n->getAttribute('type');
        if (isset($atr) && ($atr == 'sql_query')) {
          $res_1 = kometa_query($flds[strtolower($a)]);
          $row_1 = kometa_fetch_row($res_1);
          $flds[strtolower($a)] = $row_1[0];
        }
      }
    }
    //заполняю массив значений по $SQLSubstitutionArray               
    $SubstitutionValueArray = Array();
    foreach ($SQLSubstitutionArray as $key => $value) {
      $sql = $value;
      //echo '------<br>' . $sql . '<br>';
      ////замещаю в sql параметры значениями из массива значений XML                    
      foreach ($flds as $key1 => $value1)
        if (isset($key1) && ($key1 != '') && isset($value1) && ($value1 != '')) {
          // echo $key1 . ' ' . $value1 . '<br> ';
          $sql = str_replace(":$key1:", kometa_escape_string($value1), $sql);
        }
      //???
      //echo $sql . '<br>';
      if ($sql != $value) {
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
//          if ($sapi != 'cli')
//            echo 'Ошибка подготовки данных для загрузки<br>';
          ProtWriteInput(1, $id_xml, 'Ошибка подготовки данных для загрузки ', $sql . '<br>' . $s_err . '<br>');
          // exit;
        }
        if ($res) {
          $row = kometa_fetch_object($res);
          if ((!$row) || (!isset($row->$key))) {
            // if ($sapi != 'cli')
            //echo 'Не найдено соответствие для поля ' . 'Запрос: ' . $sql . '<br>';
            ProtWriteInput(3, $id_xml, 'Не найдено соответствие для поля ' . $key, 'Не найдено соответствие для поля ' . $key . 'SQL:: ' . $sql);
            $cntwarning++;
          }
          $SubstitutionValueArray[$key] = $row->$key;
          //???
          //     echo $row->$key . '<br>';
        }
      }
    }
//объединяю массивы значений sql и xml
    $flds['id_xml'] = $id_xml;
    foreach ($SubstitutionValueArray as $key => $value) {
      $flds[$key] = $value;
    }
    $s_mandatory = '';

    // Проверяем заполнены ли обязательные поля
    foreach ($flds_mandatory as $a => $value) {
      if ((!isset($flds[$a]) || ($flds[$a] == '')) && ($value == 1)) {
        $s_mandatory .= "Не заполнено обязательное поле \"$a\" <br>";
      }
    }

    if ($s_mandatory != '') {
      ProtWriteInput(2, $id_xml, 'Не заполнено одно из обязательных полей ', $s_mandatory);
      if ($sapi != 'cli')
      // echo $s_mandatory . '<b>Запись пропущена</b><br>';
        $cntskip++;
      continue;
    }

//заполняем в масиив все значения найденных в физ. таблице полей

    if ((isset($fld_code)) && ($fld_code != '')) {
      // ищем соответствующую запись по ключу
      if ($exists_is_history)
        $sql = "SELECT $fld_code FROM $sysname where $fld_code='$flds[$fld_code]' and is_history=0";
      else
        $sql = "SELECT $fld_code FROM $sysname where $fld_code='$flds[$fld_code]' ";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        if ($sapi != 'cli')
        //  echo $sql . '<br>' . $s_err . '<br>';
          ProtWriteInput(2, $id_xml, 'Ошибка поиска записи по коду ', $sql . '<br>' . $s_err . '<br>');
        $cnterr++;
//            exit;
      }
    }
    if (!((isset($fld_code)) && ($fld_code != '')) || (kometa_num_rows($res) == 0)) {
      // Если отсутствует ключевое поле или значение ключевого поля не определено или не существует записи с заданным ключом
      // запись отсутствует необходимо формировать инструкцию insert
      // если есть поле id_xml то добавляем его в список для   
// ищем есть ли такая  запись,если да до изменяем если нет то добавляем
      $sql = "SELECT count(*) as cnt FROM $sysname where $fld_key='$flds[$fld_key]'";
      $res = kometa_query($sql);
      $row = kometa_fetch_object($res);
      if ($row->cnt > 0) {
        $lst_fld = '';
        foreach ($b as $a)
          if (($a != $fld_key) && isset($flds[$a]) && ($flds[$a] != '')) {
            if ($lst_fld != '')
              $lst_fld.=',';
            $lst_fld .=$a . '=' . my_escape_string($flds[$a]);
          }
        if ($is_id_xml)
          $lst_fld.='id_xml=' . $id_xml;

        $sql = "UPDATE $sysname set $lst_fld where $fld_key='$flds[$fld_key]'";
        $res = kometa_query($sql);
        //echo $sql . '<br>';
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
          $cnterr++;
        } else {
          if (substr($sql, 0, 1) == 'U')
            $cntupd++;
          else
            $cntins++;
        }
      }
      else {
        $lst_fld = '';
        $lst_val = '';
        foreach ($b as $a) {
          if (/* (($a != $fld_key) || (!$flds_default[$a])) && */ (isset($flds[$a]) && ($flds[$a] != ''))) {
            if ($lst_fld != '')
              $lst_fld.=',';
            $lst_fld .=$a;
            if ($lst_val != '')
              $lst_val.=',';
            $lst_val .= my_escape_string($flds[$a]);
          }
        }
        if ($is_id_xml) {
          $lst_fld.=',id_xml';
          $lst_val.=",$id_xml";
        }
        if (($type_login == 3) && (isset($flds[$fld_key])) && ($flds[$fld_key] != '')) {
          $sql = "SET IDENTITY_INSERT $sysname ON";
          $res = kometa_query($sql);
          //echo $sql . '<br>';
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
          }
        }
        $sql = "INSERT INTO $sysname ($lst_fld) VALUES ($lst_val)";
        $res = kometa_query($sql);
        //echo $sql . '<br>';
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
          $cnterr++;
        } else {
          if (substr($sql, 0, 1) == 'U')
            $cntupd++;
          else
            $cntins++;
        }
        if (($type_login == 3) && (isset($flds[$fld_key])) && ($flds[$fld_key] != '')) {
          $sql = "SET IDENTITY_INSERT $sysname OFF";
          $res = kometa_query($sql);
          //echo $sql . '<br>';
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            ProtWriteInput(2, $id_xml, 'Ошибка загрузки записи', $sql . '<br>' . $s_err . '<br>');
          }
        }
      }
    } else {
      // проверяем есть ли поле is_history
      if (!$exists_is_history) {
        // Если такого поля нет, то заменяем
        $lst_fld = '';
        foreach ($b as $a)
          if (($a != $fld_key) && isset($flds[$a]) && ($flds[$a] != '')) {
            if ($lst_fld != '')
              $lst_fld.=',';
            $lst_fld .=$a . '=' . my_escape_string($flds[$a]);
          }
        $sql = "UPDATE $sysname set $lst_fld where $fld_code='$flds[$fld_code]'";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          if ($sapi != 'cli')
            echo 'Ошибка изменения записи<br>';
          ProtWriteInput(2, $id_xml, 'Ошибка изменения записи', $sql . '<br>' . $s_err . '<br>');
          $cnterr++;
        } else {
          $cntupd++;
        }
      } else {
        // запись присутствует необходимо формировать инструкцию update
        $NeedUpdate = False;
        //надо проверить все ли поля совпадают, если не все то апдейтим, а старую делаем архивной
        $sql = "SELECT fieldname from mb_object_field where id_object=$id_object";
        $res = kometa_query($sql);
        $fld_list = '';
        $coma = '';
        while ($row = kometa_fetch_object($res)) {
          $fld_list.=$coma . $row->fieldname;
          $coma = ',';
        }
        $sql = "SELECT $fld_list FROM $sysname where $fld_code='$flds[$fld_code]' and is_history=0";
        $res = kometa_query($sql);
        $row = kometa_fetch_object($res);
        foreach ($b as $a)
          if ($a != $fld_key) {
            if (($flds[$a] != $row->$a) && ($a != 'id_xml') && ($a != 'is_history') && ($flds_default[$a] == 0)) {
              //echo '"' . $flds[$a] . '"' . ' ' . '"' . $row->$a . '"';
              $NeedUpdate = True;
              // if ($sapi != 'cli')
              //   echo "$fld_code='" . $flds[$fld_code] . "' различие по полю '$a' значения старое='" . $row->$a . "' новое='" . $flds[$a] . "'<br>";
              ProtWriteInput(3, $id_xml, 'Предупреждение: изменено зачение поля', 'Предупреждение: ' . "$fld_code='" . $flds[$fld_code] . "' различие по полю '$a' значения старое='" . $row->$a . "' новое='" . $flds[$a]);
              $cntwarning++;
              break;
            }
          }

        if ($NeedUpdate) {
          $sql = "UPDATE $sysname set is_history=1 where $fld_code='$flds[$fld_code]'";
          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            // if ($sapi != 'cli')
            //  echo 'Ошибка перевода записи в архив<br>';
            ProtWriteInput(2, $id_xml, 'Ошибка записи is_history=1 по коду', $sql . '<br>' . $s_err . '<br>');
            $cnterr++;
          } else {
            $lst_fld = '';
            $lst_val = '';
            foreach ($b as $a)
              if (($a != $fld_key) && isset($flds[$a]) && ($flds[$a] != '')) {
                if ($lst_fld != '')
                  $lst_fld.=',';
                $lst_fld .=$a;
                if ($lst_val != '')
                  $lst_val.=',';
                $lst_val .= my_escape_string($flds[$a]);
              }
            // если есть поле id_xml то добавляем его в список для   
            $sql = "SELECT  count(*) as cnt FROM  information_schema.COLUMNS c where c.column_name='id_xml'  and table_name='$sysname'";
            $res = kometa_query($sql);
            $row = kometa_fetch_object($res);
            $sql = "INSERT INTO $sysname ($lst_fld) VALUES ($lst_val)";
            //echo $sql . '<br>';
            $res = kometa_query($sql);
            $s_err = kometa_last_error();
            if (isset($s_err) && ($s_err != '')) {
              // if ($sapi != 'cli')
              //   echo 'Ошибка добавления записи<br>';
              ProtWriteInput(2, $id_xml, 'Ошибка добавления записи', $sql . '<br>' . $s_err . '<br>');
              $cnterr++;
            } else {
              $cntupd++;
            }
          }
        } else {
          $cntskip++;
        }
      }
    }
  }
  $id_status = 1;
  if ($cnterr == 0)
    $id_status = 2;
  else
    $id_status = 3;

  $res = kometa_query("Select count(*) as cnt from $sysname");

  $row = kometa_fetch_object($res);

  $table = new HTML_Table();
  $table->setAttribute('border', 1);
  $table->setAttribute('width', '100%');
  $table->setAttribute('align', 'center');
  $table->setAttribute('cellspacing', 0);
  $table->setAttribute('cellpadding', 0);

  $table->setCellContents(0, 0, 'Всего терминов');
  $table->setCellContents(0, 1, 'Загружено новых');
  $table->setCellContents(0, 2, 'Заменено');
  $table->setCellContents(0, 3, 'Пропущено');
  $table->setCellContents(0, 4, 'Ошибок');
  $table->setCellContents(0, 5, 'Предупреждения');

  $table->setCellContents(1, 0, $row->cnt);
  $table->setCellContents(1, 1, $cntins);
  $table->setCellContents(1, 2, $cntupd);
  $table->setCellContents(1, 3, $cntskip);
  $table->setCellContents(1, 4, $cnterr);
  $table->setCellContents(1, 5, $cntwarning);



  $s = "Загружено <br>" . $table->toHtml();
  if ($sapi != 'cli') {
    echo $s;
  } else {
    echo "All    =" . $row->cnt . PHP_EOL;
    echo "New    =" . $cntins . PHP_EOL;
    echo "Updated=" . $cntupd . PHP_EOL;
    echo "Skip   =" . $cntskip . PHP_EOL;
    echo "Error  =" . $cnterr . PHP_EOL;
  }
  ProtWriteInput(5, $id_xml, "Итоги загрузки", $s);
  set_status_xml_filename($id_xml, $id_status);
}

if ($sapi == 'cli') {
  if (isset($argv[2]) && (intval($argv[2]) > 0)) {
    working(intval($argv[2]));
  } else
  if ($sapi != 'cli')
    print('Не передан параметр id_xml ');
  else
    echo 'id_xml not defined';
} else {
  if (isset($_GET['id_xml'])) {
    if ($sapi != 'cli')
      echo 'Начало загрузки: ' . date('d M Y H:i:s') . '<br>';
    working($_GET['id_xml']);
    if ($sapi != 'cli')
      echo 'Загрузка завершена: ' . date('d M Y H:i:s') . '<br>';
  }
}
if ($sapi != 'cli') {
  echo '</body></html>';
}
updateUserSessionTtl();
//kometa_query('COMMIT');
?>
