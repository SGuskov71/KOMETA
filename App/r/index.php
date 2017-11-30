 
<html>
    <head>
        <?php
        session_start();
            $_SESSION['first_page'] = $_SERVER['PHP_SELF'];
          $_SESSION['ImportMetaDefinition']=null;
        $_SESSION['reopen_sesion'] = true;
        $workdir = pathinfo(__FILE__);
        $workdir = $workdir['dirname'];
        if (isset($workdir) && (substr($workdir, -1, 1) <> '/'))
          $workdir.='/';

        $_SESSION['APP_INI_DIR'] = $workdir;
        if (file_exists($_SESSION['APP_INI_DIR'] . 'ProjectPath.ini')) {
          $ProjectPathINIArray = parse_ini_file($_SESSION['APP_INI_DIR'] . 'ProjectPath.ini');
          foreach ($ProjectPathINIArray as $key => $value) {
            if (isset($value) && (substr($value, -1, 1) <> '/'))
              $value.='/';
            $_SESSION["$key"] = $value;
          }
        }
        echo '<SCRIPT TYPE="text/javascript">';
        
        echo " function start() {document.location.href='" . $_SESSION['URLProjectRoot'] . "start.php?root=".urlencode($_SESSION['APP_INI_DIR'])."'}";
        echo "</SCRIPT>";
                     $INIfilename = $_SESSION['APP_INI_DIR'] . 'Connection.ini';
            if (!file_exists($_SESSION['APP_INI_DIR'] . 'Connection.ini')) {
              echo 'Отсутствует Connection.ini';
            } else {
              $ConnectionINIArray = parse_ini_file($_SESSION['APP_INI_DIR'] . 'Connection.ini');
              $logo_img = $ConnectionINIArray['logo_img'];
              $AppTitle = $ConnectionINIArray['AppTitle'];
              echo '<title>'.$AppTitle.'</title>';
            }
            ?>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body onload="start()">
    </body>

                </HTML>
