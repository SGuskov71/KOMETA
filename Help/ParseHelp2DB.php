<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title></title>
  </head>
  <body>
    <?php
    /*
     * Обработка Help файлов представленных в формате html и регистрация их 
     * в базе данных для поиска и отображения
     */
    require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
    require_once($_SESSION['LIB'] . 'PHP/simplehtmldom_1_5/simple_html_dom.php');
//    $path_parts = pathinfo(__FILE__);
//    $workdir = $path_parts['dirname'];
//    if (isset($workdir)) {
//      chdir($workdir);
//    }
    require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

    global $ord_counter;
    $ord_counter = 1;

    function GetHelpIDByName($ParentName) {
      $id = null;
      $sql = "Select mb_help.id_help, mb_help.short_name, mb_help.code_help, mb_help.ord, mb_help.content, mb_help.filename FROM mb_help where code_help='$ParentName'";
      $res = kometa_query($sql);
      $row = kometa_fetch_object($res);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        echo $sql . '<br>' . $s_err . '<br>';
      } else {
        if (kometa_num_rows($res) > 0) {
          $id = $row->id_help;
        }
      }
      return $id;
    }

    function FileExistInDB($FileName) {
      $FileName = my_escape_string($FileName);
      $sql = "SELECT mb_help.id_help, mb_help.short_name, mb_help.code_help, mb_help.ord, mb_help.content, mb_help.filename from mb_help where filename=$FileName";
      $exist = (($res = kometa_query($sql)) && (kometa_num_rows($res) > 0));

      return $exist;
    }

    function ItterateFile($FilePath, $FileName, $codehelp, $short_name, $ParentName = '', $OffsetStr = '', $Write2DB = true) {
      $result = false;
      global $PerformedArray;
      global $ord_counter;

      if (($FileName != '') && file_exists($FilePath . $FileName)) {

        if (file_exists($FilePath . $FileName)) {

          $file_exists = FileExistInDB($FileName);
          if (!$file_exists) {
            $ord_counter++;
            echo $OffsetStr . 'file="' . $FilePath . $FileName . '"' . $ord_counter . '. ' . $short_name . '</br>';
            if ($Write2DB) {
              $contents = strip_tags(file_get_contents($FilePath . $FileName));
              $contents = html_entity_decode(str_replace("'", '"', $contents), ENT_QUOTES, "UTF-8");
              $c = str_replace('  ', ' ', str_replace("\r", ' ', str_replace("\n", ' ', $contents)));
              while ($c != $contents) {
                $contents = $c;
                $c = str_replace('  ', ' ', $contents);
              }
              $contents = trim($contents);
//            $short_name=mb_substr(html_entity_decode($a->plaintext, ENT_QUOTES, "UTF-8"), 0, 100, 'utf-8');
              $short_name1 = mb_substr($short_name, 0, 100, 'utf-8');
              $c = str_replace('  ', ' ', str_replace("\r", ' ', str_replace("\n", ' ', $short_name1)));
              while ($c != $short_name1) {
                $short_name1 = $c;
                $c = str_replace('  ', ' ', $short_name1);
              }
              $short_name1 = trim($short_name1);
              $sql = "insert into mb_help ( short_name, code_help, ord, content,filename)" .
                  " values (" . my_escape_string($short_name1) . ", '$codehelp', $ord_counter," . my_escape_string($contents) . ", " . my_escape_string($FileName) . ")";
              kometa_query($sql);
              $s_err = kometa_last_error();
              if (isset($s_err) && ($s_err != '')) {
                echo $sql . '<br>' . $s_err . '<br>';
              }
            }
          }
          $id_parent = GetHelpIDByName($ParentName); // получили id головного
          $id_child = GetHelpIDByName($codehelp); // получили id текущего
          // определяем есть ли такая связь если нет тодобавляем если есть то пропускаем
          $sql = "SELECT id_help_parent,id_help_child from mb_help_link where id_help_parent=$id_parent and id_help_child=$id_child";

          if (!($res = kometa_query($sql)) || (kometa_num_rows($res) == 0)) {
            if (isset($id_parent) && isset($id_child)) {
              $sql = "insert into mb_help_link (id_help_parent,id_help_child) VALUES($id_parent,$id_child)";
              kometa_query($sql);
            }
            $data = file_get_html($FilePath . $FileName);
            if (($data->innertext != '') && (count($data->find('a')))) {

              foreach ($data->find('a') as $a)
                if (($a->plaintext != 'Следующий') && ($a->plaintext != 'Предыдущий') && ($a->plaintext != 'Глава') && ($a->plaintext != 'Оглавление')) {
                  $LinkFileName = urldecode($a->href);
                  $codehelpLink = str_replace(' ', '_', substr($LinkFileName, 0, strpos($LinkFileName, '.')));
                  ItterateFile($FilePath, $LinkFileName, $codehelpLink, $a->plaintext, $codehelp, $OffsetStr . '-');
                }
            }
            unset($data);
          }
        }
        $result = true;
      }
      return $result;
    }

    $PerformedArray = array();
    if ($type_login == 3) {
      $sql = "delete from mb_help_link ";
      kometa_query($sql);
      $sql = "delete from mb_help";
      kometa_query($sql);
    } else {
      $sql = "truncate table mb_help_link cascade";
      kometa_query($sql);
      $sql = "truncate table mb_help cascade";
      kometa_query($sql);
    }
    if (ItterateFile($_SESSION['help_root'], 'Content.html', 'Content', 'Содержание', '', True)) {
      // анализиреем и добавляем файлы которые не обработаны
      $import_dir = $_SESSION['help_root'];
      $XMLFilesArray = scandir($_SESSION['help_root']);
      foreach ($XMLFilesArray as $file) {
        set_time_limit(700);
        $s = $file;
        if ((strtolower(substr($file, strlen($file) - 5)) == ".html") || (strtolower(substr($file, strlen($file) - 4)) == ".htm")) {
          $i = strrpos($file, "/");
          if ($i > 0) {
            $file = substr($file, $i, 1000);
          }
          $codefile = substr($file, 0, strrpos($file, '.'));
          if (ItterateFile($_SESSION['help_root'], $file, $codefile, $codefile, '', True)) {
            
          }
        }
      }
      echo '</br>Обработка завершена</br>';
    }
    unset($PerformedArray);
    ?>
  </bodu>
</html>