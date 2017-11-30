<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php
    session_start();
    require_once($_SESSION['ProjectRoot'] . "2gConnection.php");

    $path_parts = pathinfo($_SESSION['first_page']);
    $workdir = $path_parts['dirname'] . '/';

    echo "<title>$AppTitle</title>";
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/touch-2.4.2/resources/css/sencha-touch.css\" />";
    echo '<script type="text/javascript" src="' . $_SESSION['URLLIB'] . 'JS/touch-2.4.2/sencha-touch-all-debug.js"></script>';
    echo "<script  src=\"" . $_SESSION['URLProjectRoot'] . "sys/ServiceFunction.js\" type=\"text/javascript\"></script>";
    echo '<script type="text/javascript" src="' . $_SESSION['URLProjectRoot'] . 'Direct/api.php"></script>';
    ?>
    <script type="text/javascript">
      Ext.app.REMOTING_API.enableBuffer = 100;
      Ext.Direct.addProvider(Ext.app.REMOTING_API);
      var _URLProjectRoot = '<?php echo $_SESSION["URLProjectRoot"]; ?>';
      if (!isMobile.SenchaTouchSupported()) {
        findFirstWindow().window.location.href = _URLProjectRoot + "Desktop/desktop.php";
      }

      var _URLLIB = '<?php echo $_SESSION["URLLIB"]; ?>';
      var ID_User = '<?php echo $ID_User; ?>';
      var __first_page = '<?php echo $_SESSION["first_page"]; ?>';
      var isDesktopWindow = true; //по этому параметру будем определять главное окно проекта
      var AppTitle = '<?php echo $AppTitle; ?>';

      dhtmlLoadScript(_URLProjectRoot + 'sys/Base64.js');
      dhtmlLoadScript(_URLProjectRoot + 'Diagram_Template/DiagramTemplate.js');

      Ext.Loader.setPath({
        'Params.view': _URLProjectRoot + 'ArchitectProject/Params/app/view',
        'Report.view': _URLProjectRoot + 'ArchitectProject/Report/app/view',
        'HTMLReport.view': _URLProjectRoot + 'ArchitectProject/HTMLReport/app/view',
        'DiagramTemplate.view': _URLProjectRoot + 'ArchitectProject/DiagramTemplate/app/view',
        'QueryBuilder.view': _URLProjectRoot + 'ArchitectProject/QueryBuilder/app/view',
        'VisualPanelMainContainer.view': _URLProjectRoot + 'ArchitectProject/VisualPanelMainContainer/app/view',
      });


      Ext.application({
        name: AppTitle,
        launch: function () {

          Ext.define('ListItem', {
            extend: 'Ext.data.Model',
            config: {
              fields: [
                {name: 'id', type: 'string'},
                {name: 'text', type: 'string'},
                {name: 'id_operation_kind', type: 'integer'}
              ]
            }
          });
          var treeStore = Ext.create('Ext.data.TreeStore', {
            model: 'ListItem',
            autoLoad: true,
            proxy: {
              type: 'ajax',
              extraParams: {LoadTouchMenu: true},
//                          url: _URLProjectRoot + 'TouchStartMenu/source.json'
              url: _URLProjectRoot + 'TouchStartMenu/TouchBackEnd.php'
            }
          });

          var detailContainer = Ext.create('Ext.Container', {
            layout: 'fit',
            height: '300',
            flex: 1,
            items: [
              {
                xtype: 'container',
                //  html: '<iframe width="560" height="315" src="http://www.sencha.com/products/touch/" frameborder="0" allowfullscreen></iframe>',
                id: 'Idpanel',
                scrollable: 'both',
                hideOnMaskTap: true
              },
            ]
          });

          var nestedList = Ext.create('Ext.NestedList', {
            // fullscreen: true,
            displayField: 'text',
            backText: 'Назад',
            emptyText: 'Меню не загружено',
            loadingText: 'Идет загрузка',
            title: AppTitle,
            store: treeStore,
            detailCard: true,
            height: Ext.getBody().getHeight(),
            listeners: {
              back: function (nestedList, node, lastActiveList, detailCardActive, eOpts) {
                nestedList.setHeight(Ext.getBody().getHeight());
                var p = Ext.getCmp('Idpanel');
                p.setHtml(null);
              },
              leafitemtap: function (nestedList, list, index, target, record) {
                var store = list.getStore(),
                        record = store.getAt(index),
                        detailCard = nestedList.getDetailCard();
                if (record.get('leaf') == true) {
                  list.setMasked({
                    xtype: 'loadmask',
                    message: 'Loading'
                  });
                  Ext.Ajax.request({
                    url: _URLProjectRoot + 'TouchStartMenu/TouchBackEnd.php',
                    params: {LoadMenuObject: true,
                      id: record.get('id'),
                      text: record.get('text')},
                    success: function (response) {
                      var result = Ext.JSON.decode(response.responseText);
                      if ((result.success === false) && (result.result == 're_connect')) {
                        Ext.MessageBox.alert('Подключение', result.msg);
                        window.onbeforeunload = null;
                        window.location.href = __first_page;
                        return;
                      }
                      if (result.success) {
                        var id_operation_kind = record.get('id_operation_kind');
                        if (id_operation_kind == 2) {
                          // обработка HTML-отчета

                          RunFunctionInScript(_URLProjectRoot + 'HTML_Report/HTML_Report.js', 'ComposeHTMLReportByTemplateInteractiveParamInput', record.get('id'));
                        }
                        else if (id_operation_kind == 3) {
                          // обработка ODT-отчета
                          RunFunctionInScript(_URLProjectRoot + 'Report/OTD_Report.js', 'ComposeODTReportByTemplateInteractiveParamInput', record.get('id'));
                        }
                        else if (id_operation_kind == 4) {
                          // обработка отчета
                          RunFunctionInScript(_URLProjectRoot + 'Diagram_Template/DiagramTemplate.js', 'ComposeDiagramByTemplateInteractiveParamInput', record.get('id'));
                        }
                        else if (id_operation_kind == 5) {

                          var url = _URLProjectRoot + record.get('id');
                          nestedList.setHeight(Ext.getBody().getHeight());
                          window.location.href = url;
                        }
                        else if (id_operation_kind == 6) {
                          params = Ext.JSON.decode(MenuObjectItem.Command);
//      RunFunctionInScript(_URLProjectRoot + params.filename, params.callfunction,params.paramfunction);
                        }
                        else if (id_operation_kind == 7) {
// панели отображения
//    RunFunctionInScript(_URLProjectRoot + 'VisualPanel/VisualPanel.js', 'ShowVisualPanel_Run', MenuObjectItem.Command);
                        }
                        else if (id_operation_kind == 8) {
// запрос
                        }
                        else if (MenuObjectItem.id_operation_kind == 9) {
// просмотр информационного объекта
                        }
                        detailCard.setHtml("Открыто новое окно по выходу закроется ");
                      } else {
                        detailCard.setHtml("Ошибка : " + result.msg);
                      }
                      list.unmask();
                    },
                    failure: function () {
                      detailCard.setHtml("Ошибка загрузки.");
                      list.unmask();
                    }
                  });
                }
              }
            }
          });

          Ext.Viewport.add({
            layout: 'vbox',
            items: [
              nestedList,
              detailContainer
            ]
          });
        }
      });
    </script>
  </head>
  <body>
  </body>
</html>
