<?php
require_once("2gConnection.php");
if (($cConn === false)) {
  exit();
}

function relocateFromLoginPage($url) {
    echo "<html> <head><title>Вход пользователя</title>";
    echo '<SCRIPT TYPE="text/javascript">';
    echo "window.location.href='" . $url . "'";
    echo "</SCRIPT>";
    echo "</head><body></body></html>";
}

$err = '';

if (($type_login == 0) || ($type_login == 3)) {

    if (isset($_POST['GO']) && $_POST['GO'] == "Вход" && isset($_POST['pwd']) && isset($_POST['log'])) {

        // $sqlOut = "SELECT id_user FROM mba_user where login='" . $_POST['log'] . "' AND pwd='" . md5($_POST['pwd']) . "';";
        $sqlOut = "SELECT id_user FROM mba_user where login='" . $_POST['log'] . "' AND pwd='" . $_POST['pwd'] . "';";
        $res = kometa_query($sqlOut);

        $ID_User = NULL;

        if (($res) && ($row = kometa_fetch_object($res))) {
            $ID_User = $row->id_user;
            $LOGIN_User = $_POST['log'];
        } else {
            $err = 'Неправильное имя пользователя или пароль';
            relocateFromLoginPage($_SESSION['URLProjectRoot'] . "login.php?ret=" . urldecode($_GET['ret']) . "&err=" . urlencode($err));
            kometa_close();
        }

        if (($err == '') && isset($ID_User)) {
            // регистрация сессии
      $sqlOut = "Update mba_sessions set is_closed=1 WHERE code_session='" . session_id() . "' or id_user=$ID_User;";
      $res = kometa_query($sqlOut);
      if ($type_login == 3) {  //способ подключения
        //MSSQL
        $sqlOut = "INSERT INTO mba_sessions (code_session, id_user, user_ip, session_ttl) VALUES ('" . session_id() . "', " . $ID_User . ", '" . $_SERVER['REMOTE_ADDR'] . "', CONVERT(datetime, '" . date("Y-m-d\TH:i:s", dateAdd('n', 30, time())) . "', 126));";
      } else
        $sqlOut = "INSERT INTO mba_sessions (code_session, id_user, user_ip, session_ttl) VALUES ('" . session_id() . "', " . $ID_User . ", '" . $_SERVER['REMOTE_ADDR'] . "', current_timestamp +INTERVAL '30 MINUTES');";
      $res = kometa_query($sqlOut);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        echo 'Ошибка регистрации сессии ' . $sql . '<br>' . $s_err . '<br>';
        echo '<SCRIPT TYPE="text/javascript">';
        echo "alert '" . 'Ошибка регистрации сессии ' . $sql . ' ' . $s_err . "'";
        echo "</SCRIPT>";
        exit;
      } else
      if (!isset($_GET['ret']) || ($_GET['ret'] == ''))
        $_GET['ret'] = $_SESSION['URLProjectRoot'] . "start.php";
            relocateFromLoginPage(urldecode($_GET['ret']));
        }
        
    } else {
        // формируем форму
        
        // определяем обработчик формы
        $actionUrl = 'login.php';
        if (!isset($_GET['ret']) || ($_GET['ret'] == '')) {
            $_GET['ret'] = $_SESSION['URLProjectRoot'] . "index.php";
        }
        if (isset($_GET['ret'])) {
            $actionUrl .= '?ret=' . urlencode($_GET['ret']);
        }
        // определяем сообщение об ошибке
        $errorMessage = '';
        if (isset($_GET['err'])) {
            $errorMessage = $_GET['err'];
        }
        
        // поазать форму
        require($_SESSION['APP_INI_DIR'].'login_form_template.php');

    }
} else {
    //считываем пользователя

    $sqlOut = "DELETE FROM mba_sessions WHERE id_session='" . session_id() . "';";
    $res = kometa_query($sqlOut);

    $sqlOut = "SELECT id_user FROM mba_user where login='$db_usr'";
    $res = kometa_query($sqlOut);

    $ID_User = NULL;

    if (($res) && ($row = kometa_fetch_object($res))) {
        $ID_User = $row->id_user;
    } else {
        $err = 'Неправильное имя пользователя или пароль';
        kometa_close();
    }

    if (($err == '') && !isset($ID_User)) {
        // регистрация сессии
    if ($type_login == 3) {  //способ подключения
      //MSSQL
      $sqlOut = "INSERT INTO mba_sessions (code_session, id_user, user_ip, session_ttl) VALUES ('" . session_id() . "', " . $ID_User . ", '" . $_SERVER['REMOTE_ADDR'] . "', CONVERT(datetime, '" . date("Y-m-d\TH:i:s", dateAdd('n', 30, time())) . "', 126));";
    } else
      $sqlOut = "INSERT INTO mba_sessions (code_session, id_user, user_ip, session_ttl) VALUES ('" . session_id() . "', " . $ID_User . ", '" . $_SERVER['REMOTE_ADDR'] . "', current_timestamp +INTERVAL '30 MINUTES');";
    $res = kometa_query($sqlOut);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      echo 'Ошибка регистрации сессии ' . $sql . '<br>' . $s_err . '<br>';
      echo '<SCRIPT TYPE="text/javascript">';
      echo "alert '" . 'Ошибка регистрации сессии ' . $sql . ' ' . $s_err . "'";
      echo "</SCRIPT>";
      exit;
    } else
    if (!isset($_GET['ret']) || ($_GET['ret'] == ''))
      $_GET['ret'] = $_SESSION['URLProjectRoot'] . "index.php";
        relocateFromLoginPage($_GET['ret']);
    }
}
