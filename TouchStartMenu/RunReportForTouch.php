<HTML>
  <HEAD>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <TITLE>
      Загрузка Мета описания базы из XML-файла
    </TITLE>
  </HEAD>
  <BODY>
    <?php
    session_start();
    require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
//        $path_parts = pathinfo(__FILE__);
//        $workdir = $path_parts['dirname'];
//        if (isset($workdir)) {
//          chdir($workdir);
//        }
    //echo '<link rel="stylesheet" type="text/css" media="screen" href="' . $_SESSION['URLLIB'] . 'JS/extJS/resources/css/ext-all.css" />';
    $theme = get_Param_value('theme', $ID_User);
    if (!isset($theme) || ($theme == ''))
      $s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/css/ext-all.css\" />";
    else {
      if (open_url($_SESSION['URLLIB'] . "JS/extJS/resources/ext-theme-$theme/ext-theme-$theme-all.css")) {
        $s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/ext-theme-$theme/ext-theme-$theme-all.css\" />";
      } else {
        $s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/css/ext-all.css\" />";
      }
    }

    echo '<script type="text/javascript" src="' . $_SESSION['URLLIB'] . 'JS/extJS/ext-all.js"></script>';
    echo '<script type="text/javascript" src="' . $_SESSION['URLLIB'] . 'JS/extJS/locale/ext-lang-ru.js"></script>';
    echo '<script type="text/javascript" src="' . $_SESSION['URLProjectRoot'] . 'Direct/api.php"></script>';
    echo "        <script type='text/javascript'>" . PHP_EOL;
    echo "    Ext.app.REMOTING_API.enableBuffer = 100;" . PHP_EOL;
    echo "    Ext.Direct.addProvider(Ext.app.REMOTING_API);" . PHP_EOL;
    echo "</script>" . PHP_EOL;
    ?>
    <script type="text/javascript">
      var _URLProjectRoot;
      _URLProjectRoot = '<?php echo $_SESSION["URLProjectRoot"]; ?>';

      Ext.onReady(function () {
        w = Ext.create('Ext.container.Container', {
          html: '<div style="overflow:scroll !important; -webkit-overflow-scrolling:touch !important;">     <iframe src="' + '<?php echo $_GET[url] ?>' + '" width="100%" height="100%" ></iframe> </div>'
        });
        Ext.create('Ext.container.Viewport', {
          layout: 'fit',
          items: [
            w
          ]
        });

      }
      );
    </script>
  </BODY>
</HTML>