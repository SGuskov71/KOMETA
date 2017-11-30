<html>
  <head>
    <meta charset="UTF-8">
    <title></title>
  </head>
  <body>
    <?php
    require_once("2gConnection.php");
    if (($cConn === false)) {
      exit();
    }
    // put your code here
    $sqlOut = "UPDATE SET is_closed=1 mba_sessions WHERE code_session='" . session_id() . "';";
    $res = kometa_query($sqlOut);
    $_GET['ret'] = $_SESSION['first_page'];
    echo '<SCRIPT TYPE="text/javascript">';
    echo "window.location.href='" . $_SESSION['first_page']."'";
    echo "</SCRIPT>";
    ?>
  </body>
</html>
