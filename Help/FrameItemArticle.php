<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title></title>
  </head>
  <body>
    <?php
    session_start();
    require_once($_SESSION['ProjectRoot'] . "2gConnection.php");

    $HELP_FILES_DIR = $_SESSION['help_root'];
    $HELP_URL_FILES_DIR = $_SESSION['URLhelp_root'];

    function WriteContentRedirect() {
      global $HELP_URL_FILES_DIR;
  echo '       <script type="text/javascript">';
      echo "         location.replace('" . $HELP_URL_FILES_DIR . "Content.html');";
  echo '       </script>';
    }

    if (isset($_GET['KEYWORD'])) {
      if ($_GET['KEYWORD'] == 'NOTFOUND') {
        echo 'Поиск не дал результатов';
      } else {
//                echo 'Поиск по ключевым словам "' . $_GET['KEYWORD'] . '"<br>' . PHP_EOL;
        echo '</b>Результат поиска:</b><br>' . PHP_EOL;
        $res = kometa_query("SELECT id_help, short_name, code_help, ord, content, filename from mb_help where code_help in (" . $_GET['KEYWORD'] . ")  order by ord");
        if (kometa_num_rows($res) == 1) {
          $row = kometa_fetch_object($res);
          echo ' <script type="text/javascript">';
          echo '     location.replace("' . $HELP_URL_FILES_DIR . $row->filename . '");';
          echo ' </script>';
        } else if (kometa_num_rows($res) > 1) {
          while ($row = kometa_fetch_object($res)) {
            set_time_limit(7000);
            echo "<a href = \"$HELP_URL_FILES_DIR$row->filename\">$row->short_name</a><br>" . PHP_EOL;
          }
        } else {
          echo 'Поиск по ключевым словам "' . $_GET['KEYWORD'] . '"<br> Результата не дал! <br>' . PHP_EOL;
        }
      }
    } else if (isset($_GET['CONTEXT'])) {
      $res = kometa_query("SELECT id_help, short_name, code_help, ord, content, filename from mb_help where content like '%" . urldecode($_GET['CONTEXT']) . "%'  order by ord");
  if (kometa_num_rows($res) > 1) {
        echo 'Поиск по контексту дал более одного результата. Контекст "' . urldecode($_GET['CONTEXT']) . '"<br>' . PHP_EOL;
        while ($row = kometa_fetch_object($res)) {
          set_time_limit(7000);
          echo "<a href = \"$HELP_URL_FILES_DIR$row->filename\">$row->short_name</a><br>" . PHP_EOL;
        }
      } else if (kometa_num_rows($res) == 1) {
        $row = kometa_fetch_object($res);
        echo ' <script type="text/javascript">';
        echo '     location.replace("' . $HELP_URL_FILES_DIR . $row->filename . '");';
        echo ' </script>';
      } else if (kometa_num_rows($res) == 0) {
        WriteContentRedirect();
      }
    } else {
      WriteContentRedirect();
    }
    ?>
  </body>
</html>  
