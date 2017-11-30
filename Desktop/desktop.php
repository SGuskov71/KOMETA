<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="css/desktop.css" />
    <?php
    session_start();
    require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
//        require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

    $path_parts = pathinfo($_SESSION['first_page']);
    $workdir = $path_parts['dirname'] . '/';
    if (isset($css_user_desktop) && (file_exists($_SESSION['APP_INI_DIR'] . $css_user_desktop))) {
      echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . str_replace('\\', "/", $workdir) . $css_user_desktop . "\" />";
    }


    $theme='classic';
    //$theme='access';
    echo "<title>$AppTitle</title>";
    //$theme = get_Param_value('theme', $ID_User);
    if (!isset($theme) || ($theme == ''))
      echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/css/ext-all.css\" />";
    else {
      if (open_url($_SESSION['URLLIB'] . "JS/extJS/resources/ext-theme-$theme/")) {
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/ext-theme-$theme/ext-theme-$theme-all.css\" />";
      } else {
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/css/ext-all.css\" />";
      }
    }
    echo "<script src=\"" . $_SESSION['URLLIB'] . "JS/extJS/ext-all-debug-w-comments.js\" type=\"text/javascript\"></script>";
    //echo '<script type="text/javascript" src="' . $_SESSION['URLLIB'] . 'JS/extJS/ext-all.js"></script>';
    echo "<script src=\"" . $_SESSION['URLLIB'] . "JS/extJS/locale/ext-lang-ru.js\" type=\"text/javascript\"></script>";

//        echo "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "Desktop/js/include-ext.js\"></script>";
//        echo "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "Desktop/js/options-toolbar.js\"></script>";

    echo "<script  src=\"" . $_SESSION['URLProjectRoot'] . "sys/ServiceFunction.js\" type=\"text/javascript\"></script>";
    echo '<script type="text/javascript" src="' . $_SESSION['URLProjectRoot'] . 'Direct/api.php"></script>';
    ?>
    <script type="text/javascript">

      var _URLProjectRoot = '<?php echo $_SESSION["URLProjectRoot"]; ?>';
      Ext.themeName = 'gray';
      if (isMobile.SenchaTouchSupported()) {
        findFirstWindow().window.location.href = _URLProjectRoot + "TouchStartMenu/TouchStartMenu.php";
      }

      var _URLLIB = '<?php echo $_SESSION["URLLIB"]; ?>';
      var ID_User = '<?php echo $ID_User; ?>';
      var __first_page = '<?php echo $_SESSION["first_page"]; ?>';
      var loadMetabase = '<?php echo $_SESSION['ImportMetaDefinition']; ?>';
      var loadSysTask = '<?php echo $_SESSION['loadSysTask']; ?>';
      var isDesktopWindow = true; //по этому параметру будем определять главное окно проекта

      dhtmlLoadScript(_URLProjectRoot + 'sys/Base64.js');
      dhtmlLoadScript(_URLProjectRoot + 'Grid/Grid.js');

      Ext.Loader.setPath({
        'Params.view': _URLProjectRoot + 'ArchitectProject/Params/app/view',
        'Report.view': _URLProjectRoot + 'ArchitectProject/Report/app/view',
        'DiagramTemplate.view': _URLProjectRoot + 'ArchitectProject/DiagramTemplate/app/view',
        'SQLEditor.view': _URLProjectRoot + 'ArchitectProject/SQLEditor/app/view',
        'QueryBuilder.view': _URLProjectRoot + 'ArchitectProject/QueryBuilder/app/view',
        'VisualPanelMainContainer.view': _URLProjectRoot + 'ArchitectProject/VisualPanelMainContainer/app/view',
        'KOMETA.Grid': _URLProjectRoot + 'Grid/Class',
        'KOMETA.Operation': _URLProjectRoot + 'OperationClasses',
        'Ext.ux.Desktop': 'js',
        MyDesktop: ''
      });
      Ext.require('MyDesktop.App');
      var myDesktopApp;
      var DesctopSettingsObject;
      var ConnectionINIObject;

      Ext.onReady(function () {

        Ext.app.REMOTING_API.enableBuffer = 100;
        Ext.app.REMOTING_API.timeout = 240000;
        Ext.Direct.addProvider(Ext.app.REMOTING_API);

        Ext.MessageBox.wait({
          msg: 'Выполняется операция. Ждите...',
          width: 300,
          wait: true,
          waitConfig: {interval: 100},
          animateTarget: 'StartButton'
        });
        ConnectionINIObject_class.get_ConnectionINIObject(function (response, options) {
          if (response.success == true) {
            ConnectionINIObject = response.result;
            if (ConnectionINIObject.DisableBrowserBack == 1) {
              window.onbeforeunload = function (e) {
                return 'Собираетесь покинуть приложение??';
              };
            }
          }
        });
        Desktop_class.GetDesctopSettings(function (response, options) {
          DesctopSettingsObject = response; //здесь сидят настройки рабочего стола
          if (loadMetabase == 1) {
            DesctopSettingsObject.ShortcutObject = [];
            DesctopSettingsObject.MenuObject = [];
          }

          if ((DesctopSettingsObject.ShortcutObject.length > 0) && (DesctopSettingsObject.ShortcutObject[0] == undefined))
            DesctopSettingsObject.ShortcutObject = [];
          Ext.onReady(function () {
            myDesktopApp = new MyDesktop.App();
            myDesktopApp.on('ready', function () {
              Ext.MessageBox.hide();
              if (loadMetabase == 1) {
                Run_operation(null,{func_name:'ImportMetaDefinition'
                            ,func_class_name:'Metabase_operation'
                            ,param_list:{
                            }
                          })

                return;
              }
              if (loadSysTask == 1) {
                Run_operation(null,{func_name:'ImportSysTask'
                            ,func_class_name:'Metabase_operation'
                            ,param_list:{
                            }
                          })

                return;
              }
              ;
              if (DesctopSettingsObject.Autorun && (DesctopSettingsObject.AutorunItems != undefined) &&
                      (DesctopSettingsObject.AutorunItems.length > 0)) {//запускаю авторан
                var length = DesctopSettingsObject.AutorunItems.length;
                win = findFirstWindow().window;
                w = Math.round(win.document.body.clientWidth / 6 * 5);
                h = Math.round(win.document.body.clientHeight / 6 * 5);
                x = win.document.body.clientWidth - w,
                        y = 0;
                for (var i = 0; i < length; i++)
                  if (DesctopSettingsObject.AutorunItems[i] != undefined) {
                    var win = TaskMenuCreateWindow(this.modules[0], DesctopSettingsObject.AutorunItems[i], x, y, w, h);
                    x -= 20;
                    y += 20;
                  }
              }
              Ext.getCmp('StartButton').getEl().dom.click();
            });
          });
        });
      });

    </script>
  </head>
  <body>
  </body>
</html>
