Ext.define('KOMETA.Operation.URL_operation', {
  OpenURL: function (Grid, Operation) {
    var win = findFirstWindow();
    var _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
    var _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
    var url;
    if (typeof Operation.param_list == 'string') {
      Operation.param_list = Ext.JSON.decode(Operation.param_list);
    }

    if (Operation.param_list.absolute_path!=true)
      url=_URLProjectRoot+Operation.param_list.URL
    else
      url=Operation.param_list.URL
    buildW_desktop(url, Operation.short_name, Operation.style, _width, _height, false, 0);
  }
});