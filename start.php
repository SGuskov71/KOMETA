<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script type="text/javascript">
      var isDesktopWindow = true; //по этому параметру будем определять главное окно проекта
    </script>
    <?php
    echo "<script  src=\"" . $_SESSION['URLProjectRoot'] . "sys/ServiceFunction.js\" type=\"text/javascript\"></script>";
    ?>
  </head>
  <body>
    <?php
    session_start();
    if (!isset($_SESSION['APP_INI_DIR'])) {
      $_SESSION['APP_INI_DIR'] = pathinfo(__FILE__);
      if (isset($_SESSION['APP_INI_DIR']) && (substr($_SESSION['APP_INI_DIR'], -1, 1) <> '/'))
        $_SESSION['APP_INI_DIR'].='/';
    }

    require_once('2gConnection.php');
    $restart = false;
    $_SESSION['loadSysTask'] = null;
    $_SESSION['ImportMetaDefinition'] = null;
    if ($ID_User == $ID_User_sys) {
      require_once("sys/mb_common.php");
      //проверяем есть ли пункты в системном меню если нет, то загружаем
      $sql = "SELECT id_object FROM mb_object ";
      if (($res = kometa_query($sql)) && (kometa_num_rows($res) == 0)) {
        //$restart = true;
        $_SESSION['ImportMetaDefinition'] = 1;
//        echo '<SCRIPT TYPE="text/javascript">';
//        echo "window.location.href='" . $_SESSION['URLProjectRoot'] . "sys/ImportMetaDefinition.php?ret=" . urlencode($_SERVER['REQUEST_URI']) . "'";
//        echo "</SCRIPT>";
      } else {
        $sql = "SELECT id_task,short_name,full_name FROM mb_task where is_sys=1";
        if (($res = kometa_query($sql)) && (kometa_num_rows($res) == 0)) {
          $_SESSION['loadSysTask'] = 1;
//              $restart = true;
//              echo '<SCRIPT TYPE="text/javascript">';
//              echo "window.location.href='" . $_SESSION['URLProjectRoot'] . "sys/ImportSysTask.php?ret=" . urlencode($_SERVER['REQUEST_URI'])."&root=".urlencode($_GET['root']). "'";
//              echo "</SCRIPT>";
        }
      }
    }


    if (($ID_User >= $ID_User_sys) && (!$restart)) {
      echo '<SCRIPT TYPE="text/javascript">';
      echo 'if (isMobile.SenchaTouchSupported()) {';
      echo "window.location.href='" . $_SESSION['URLProjectRoot'] . "TouchStartMenu/TouchStartMenu.php?root=" . urlencode($_GET['root']) . "'";
      echo '}else{';
      echo "window.location.href='" . $_SESSION['URLProjectRoot'] . "Desktop/desktop.php?root=" . urlencode($_GET['root']) . "'}";
      echo "</SCRIPT>";
    }
    ?>
  </body>
</html>