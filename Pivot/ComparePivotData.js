function SavePivotDataForCompare(_id_pivot_storage, _PivotCaption) {
  Ext.MessageBox.wait({
    msg: 'Выполняется операция. Ждите...',
    width: 300,
    wait: true,
    waitConfig: {interval: 100}
  });
  Ext.Ajax.request({
    url: _URLProjectRoot + 'objectdata/pivot/ComparePivotBackEnd.php',
    method: 'POST',
    params: {SavePivotData: true,
      id_pivot_storage: _id_pivot_storage},
    success: function (response, options) {
      Ext.MessageBox.hide();
      var Pivot_Object = Ext.JSON.decode(response.responseText);
      if (Pivot_Object.success == true) {
        Ext.MessageBox.alert('Сохранение', Pivot_Object.msg);
        return true;
      } else {
        Ext.MessageBox.alert('Сохранение', "Ошибка сохранения: " + Pivot_Object.msg);
        return false;
      }
    },
    failure: function (response, options) {
      Ext.MessageBox.hide();
      Ext.MessageBox.alert('Сохранение', "Ошибка сохранения: " + response.statusText);
      return false;
    }
  });
}

function RunPivotCompare(_id_pivot_time_slice_1, _id_pivot_time_slice_2, _operation, _PivotCaption, _container, _HelpContext) {
  Ext.MessageBox.wait({
    msg: 'Выполняется операция. Ждите...',
    width: 300,
    wait: true,
    waitConfig: {interval: 100}
  });
  Ext.Ajax.request({
    url: _URLProjectRoot + 'objectdata/pivot/ComparePivotBackEnd.php',
    method: 'POST',
    params: {GetPivotCoparedData: true,
      id_pivot_time_slice_1: _id_pivot_time_slice_1,
      id_pivot_time_slice_2: _id_pivot_time_slice_2,
      operation: _operation},
    success: function (response, options) {
      Ext.MessageBox.hide();
      var Pivot_Object = Ext.JSON.decode(response.responseText);
      if ((Pivot_Object.success === false) && (Pivot_Object.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', Pivot_Object.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (Pivot_Object.success == true) {
        Pivot_Object.result.pivot_name = _PivotCaption;
        ShowPivotResultWindow(Pivot_Object, _HelpContext, true, _container);
      } else {
        Ext.MessageBox.alert('Ошибка', "Ошибка получения настроек: " + Pivot_Object.msg);
        return false;
      }
    },
    failure: function (response, options) {
      Ext.MessageBox.hide();
      Ext.MessageBox.alert('Ошибка', "Ошибка получения настроек: " + response.statusText);
      return false;
    }
  });
}

function ComparePivotData(_selected, _wListPivot) {//показывает окно списка сохраненных пивотов
  var _id_pivot_storage = _selected.get('id_pivot_storage');
  var _PivotCaption = _selected.get('short_name');
  var win = findFirstWindow();
  var ListPivotDataWindow = win.myDesktopApp.desktop.createWindow({
    title: 'Список сохраненных данных для сводной таблицы - ' + _PivotCaption,
    code: 'ListPivotDataWindow',
    width: Math.round(win.window.document.body.clientWidth / 2),
    height: Math.round(win.window.document.body.clientHeight / 2),
    id_pivot_storage: _id_pivot_storage,
    selected: _selected,
    wListPivot: _wListPivot,
    closable: false,
    autoScroll: true,
    HelpContext: 'kometa_compare_pivot',
    PivotCaption: _PivotCaption,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    layout: {
      type: 'fit'
    },
    constrainHeader: true,
    modal: true
  });
  var ListPivotDataContainer = Ext.create('ListPivotDataContainer', {_Parent: ListPivotDataWindow});
  ListPivotDataWindow.add(ListPivotDataContainer);
  ListPivotDataWindow.show();
}

function ComparePivotDataByPivotCodeInWindow(Params) {//показывает окно со комбо выбора первого и второго сохраненного по коду пивота
  var win = findFirstWindow();
  var ListPivotDataWindow = win.myDesktopApp.desktop.createWindow({
    title: '',
    code: 'ComparePivotDataByPivotCodeInWindow',
    width: Math.round(win.window.document.body.clientWidth / 2),
    height: Math.round(win.window.document.body.clientHeight / 2),
    closable: false,
    autoScroll: true,
    HelpContext: Params.HelpContext,
    PivotCaption: _PivotCaption,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    layout: {
      type: 'fit'
    },
    constrainHeader: true,
    modal: true
  });
  ComparePivotDataByPivotCode(Params.Code, ListPivotDataWindow, Params.HelpContext);
  ListPivotDataWindow.show();
}

function ComparePivotDataByPivotCode(_PivotCode, _Container, _HelpContext) {// комбо выбора первого и второго сохраненного в контейнер
//пивота и операции сравнения и там же выдает резкльтат сравнения
//_Container - это контайнер куда все это выводиться, если null то создаю окно

  Ext.MessageBox.wait({
    msg: 'Выполняется операция. Ждите...',
    width: 300,
    wait: true,
    waitConfig: {interval: 100}
  });
  Ext.Ajax.request({
    url: _URLProjectRoot + 'objectdata/pivot/ComparePivotBackEnd.php',
    method: 'POST',
    params: {GetPivotCompareByCode: true,
      pivot_code: _PivotCode},
    success: function (response, options) {
      Ext.MessageBox.hide();
      var Pivot_Object = Ext.JSON.decode(response.responseText);
      if ((Pivot_Object.success === false) && (Pivot_Object.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', Pivot_Object.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (Pivot_Object.success == true) {
        var _id_pivot_storage = Pivot_Object.result.id_pivot_storage;
        var _PivotCaption = Pivot_Object.result.PivotCaption;
        var combo_data = Pivot_Object.result.combo_data;
        var ComparePivotDataContainer = new Ext.Container({
          layout: {
            type: 'border'
          },
          items: [{
              xtype: 'form',
              region: 'north',
              itemId: 'BtnContainer',
              bodyStyle: 'padding:5px 5px 0',
              fieldDefaults: {
                labelAlign: 'top',
                msgTarget: 'side'
              },
              defaults: {
                border: false,
                xtype: 'panel',
                flex: 1,
                layout: 'fit'
              },
              layout: 'hbox',
              items: [
                {
                  xtype: 'combobox',
                  itemId: 'combobox1',
                  fieldLabel: 'Объект сравнения 1',
                  matchFieldWidth: false,
                  editable: false,
                  mode: 'local',
                  triggerAction: 'all',
                  store: new Ext.data.Store({
                    fields: ['id_pivot_time_slice', 'dt_building'],
                    data: combo_data
                  }),
                  valueField: 'id_pivot_time_slice',
                  displayField: 'dt_building',
                  value: null
                },
                {
                  xtype: 'combobox',
                  itemId: 'combobox2',
                  fieldLabel: 'Объект сравнения 2',
                  matchFieldWidth: false,
                  editable: false,
                  mode: 'local',
                  triggerAction: 'all',
                  store: new Ext.data.Store({
                    fields: ['id_pivot_time_slice', 'dt_building'],
                    data: combo_data
                  }),
                  valueField: 'id_pivot_time_slice',
                  displayField: 'dt_building',
                  value: null
                },
                {
                  xtype: 'combobox',
                  itemId: 'combobox3',
                  fieldLabel: 'Операция сравнения',
                  matchFieldWidth: false,
                  editable: false,
                  mode: 'local',
                  triggerAction: 'all',
                  store: new Ext.data.Store({
                    fields: ['id', 'name'],
                    data: [{id: 1, name: 'Разница'}, {id: 2, name: 'Доля'}]
                  }),
                  valueField: 'id',
                  displayField: 'name',
                  value: 1
                }
              ],
              buttons: ['->',
                {
                  text: 'Сравнить',
                  handler: function (button) {
                    var id_pivot_time_slice_1 = button.up('form').child('#combobox1').getValue();
                    var id_pivot_time_slice_2 = button.up('form').child('#combobox2').getValue();
                    var operation = button.up('form').child('#combobox3').getValue();
                    if ((id_pivot_time_slice_1 == undefined) || (id_pivot_time_slice_2 == undefined) || (operation == undefined)) {
                      Ext.MessageBox.alert('Продолжения не будет', "Заполните все значения");
                    } else {
                      var container = button.up('form').up('container').child('#ResultContainer');
                      RunPivotCompare(id_pivot_time_slice_1, id_pivot_time_slice_2, operation, _PivotCaption, container, _HelpContext);
                    }
                  }
                }]
            },
            {
              xtype: 'container',
              region: 'center',
              itemId: 'ResultContainer',
              layout: {
                type: 'fit'
              }
            }]
        });
        if (_Container == undefined) {
          var pw = findFirstWindow();
          var win1 = pw.myDesktopApp.desktop.createWindow({
            title: 'Сравнение сохраненных данных сводных таблиц ' + _PivotCaption,
            code: 'ComparePivotDataByPivotCode',
            width: Math.round(pw.window.document.body.clientWidth / 6 * 5),
            height: Math.round(pw.window.document.body.clientHeight / 6 * 5),
            closable: true,
            maximizable: true,
            HelpContext: 'kometa_pivot_result,' + _HelpContext,
            tools: [{
                type: 'help',
                qtip: 'Cправка',
                callback: ShowHelp
              }
            ],
            layout: {
              type: 'fit'
            }
          });
          win1.add(ComparePivotDataContainer);
          win1.show();
          return;
        } else {
          _Container.add(ComparePivotDataContainer);
        }

      } else {
        Ext.MessageBox.alert('Ошибка', "Ошибка получения настроек: " + Pivot_Object.msg);
        return false;
      }
    },
    failure: function (response, options) {
      Ext.MessageBox.hide();
      Ext.MessageBox.alert('Ошибка', "Ошибка получения настроек: " + response.statusText);
      return false;
    }
  });
}