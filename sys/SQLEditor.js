function SQLEditor(value, _callback) {
  win = findFirstWindow();
  _width = Math.round(win.window.document.body.clientWidth) - 40;
  _height = Math.round(win.window.document.body.clientHeight) - 40;
  var EdtMainContainer = Ext.create('SQLEditor.view.EditSQLWindow');
  var EdtSQL = EdtMainContainer.down('#EdtSQL');
  EdtSQL.setValue(value);
  var _HelpContext = 'SQLEditor';
  var EdtWindow = win.myDesktopApp.desktop.createWindow({
    title: 'Радактор SQL запроса',
    code: 'EdtMainContainer',
    width: _width,
    height: _height,
    closable: true,
    autoScroll: false,
    HelpContext: _HelpContext,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      },
      {
        type: 'save',
        qtip: 'Сохранить',
        callback: function (panel) {
          var EdtSQL = panel.down('#EdtSQL');
          if (_callback) {
            _callback(EdtSQL.getValue(value));
            //CloseWindow(panel);
          }
        }
      },
      {
        type: 'gear',
        qtip: 'Форматировать',
        callback: function (panel) {
          var EdtSQL = panel.down('#EdtSQL');
          Common_class.SqlFormatter(EdtSQL.getValue(), function (result) {
            if (result.success) {
              EdtSQL.setValue(result.result);
            } else {
              Ext.MessageBox.alert("Ошибка: " + result.msg);
            }

          });
        }
      }],
    layout: {
      type: 'fit'
    },
    constrainHeader: true,
    modal: true
  });
  EdtWindow.add(EdtMainContainer)
  EdtWindow.show();
}