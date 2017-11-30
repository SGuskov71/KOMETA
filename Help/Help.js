dhtmlLoadScript(_URLProjectRoot + 'help/HelpFormContainer.js');

var URLhelp_root;

function CloseWindow(_Win) {
  _Win.close();
  Ext.destroy(_Win);
}

var ShowHelpWindow = function (_HelpContext) {
  Ext.MessageBox.wait({
    msg: 'Подготовка структуры помощи, ждите... ждите...',
    width: 300,
    wait: true,
    waitConfig: {interval: 100}
  });
  function trim1(str) {
    return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
  }
  var HelpContextArray = [];
  var HelpContextStr = '';
  var coma = '';
  if (_HelpContext != undefined) {
    _HelpContext = _HelpContext.toString();
    HelpContextArray = _HelpContext.split(',');
    var length = HelpContextArray.length;
    for (var i = 0; i < length; i++) {
      HelpContextArray[i] = trim1(HelpContextArray[i]);
      HelpContextStr = HelpContextStr + coma + "\'" + trim1(HelpContextArray[i]) + "\'";
      coma = ',';
    }
  }
  Help_class.GetHelpContent_root(function (response, options) {
    Ext.MessageBox.hide();
    var JSON_Result = response;
    if ((JSON_Result.success === false) && (JSON_Result.result == 're_connect')) {
      Ext.MessageBox.alert('Подключение', JSON_Result.msg);
      window.onbeforeunload = null;
      findFirstWindow().window.location.href = __first_page;
      return;
    }
    else if (JSON_Result.success) {
      URLhelp_root = JSON_Result.result.URLhelp_root;
      win = findFirstWindow();
      HelpWindow = win.myDesktopApp.desktop.createWindow({
        code: 'HelpWindow',
        title: 'Справка',
        HelpContext: 'kometa_help',
        HelpContextStr: HelpContextStr,
        HelpContextArray: HelpContextArray,
        width: win.window.document.body.clientWidth / 9 * 8,
        height: win.window.document.body.clientHeight / 9 * 8,
        autoScroll: true,
        autoHeight: true,
        maximizable: true,
        closeAction: 'close',
        tools: [
          {
            type: 'help',
            qtip: 'Справка',
            callback: ShowHelp
          }],
        layout: {
          type: 'fit'
        },
        //    modal: true,
        help_history: []
      });
      var HelpContainer = HelpWindow.getChildByElement('id_HelpFormContainer');
      if (HelpContainer == undefined) {
        HelpContainer = Ext.create('HelpFormContainer', {id: 'id_HelpFormContainer', _Parent: HelpWindow, HelpContent_root: JSON_Result.result.TreeRoot});
        HelpWindow.add(HelpContainer);
      }
      HelpWindow.show();
      var p = HelpContainer.getComponent('ContentContainer').getComponent('ContentPanel');
      var s = '<iframe id=help_frame name=help_frame src="' + _URLProjectRoot + 'Help/FrameItemArticle.php?KEYWORD=' + encodeURI(HelpContextStr) + '" width="100%" height="100%" onload="insert_to_history(this.contentWindow);"></iframe>';
      p.update(s);
    } else {
      Ext.MessageBox.alert("Ошибка получения настроек справки: " + JSON_Result.msg);
      return false;
    }
  });

}

