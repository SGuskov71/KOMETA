//Модули работы с Аналитической панелью
var VisualPanelItemTypes = //типы элементов отображаемые в сегментах аналитических панелей
        [
          {id: 1, Caption: 'Запрос', code_view: 'sv_mb_stored_query'},
          {id: 2, Caption: 'Отчет', code_view: 'sv_mbr_report'},
          {id: 3, Caption: 'График', code_view: 'sv_mbg_diagram_template'},
          {id: 4, Caption: 'Сводная таблица', code_view: 'sv_mb_pivot_storage'},
          {id: 5, Caption: 'Карта', code_view: ''}
        ];

var _URLVisualPanelMakets; //путь к js файлам макетов

dhtmlLoadScript(_URLProjectRoot + 'VisualPanel/SelectVisualPanelContentsWindow.js');
dhtmlLoadScript(_URLProjectRoot + 'sys/ServiceFunction.js');
//dhtmlLoadScript(_URLProjectRoot + 'Params/ParamsFunction.js');

//TestAction.doEcho('Жопа', function (value) {
//    alert(value);
//});
VisualPanel_class.Get_URLVisualPanelMakets(function (result) {
  if ((result.success === false) && (result.result == 're_connect')) {
    alert(result.msg);
    window.onbeforeunload = null;
    findFirstWindow().window.location.href = __first_page;
    return;
  }
  if (result.success == true) {
    _URLVisualPanelMakets = result.result;
    Ext.Loader.setPath({'VisualPanelMakets.view': _URLVisualPanelMakets});

  } else {
    _URLVisualPanelMakets = '';
    Ext.MessageBox.alert('Ошибка', "Ошибка получения _URLVisualPanelMakets: " + result.msg);
  }
});

function ShowListVisualPanel(_HelpContext) { //показывает список сохраненных аналитических панелей
  var store_ListVisualPanelContainer = new Ext.data.Store({
    fields: [{name: 'id_viewpanel', type: 'int'},
      {name: 'description', type: 'string'},
      {name: 'code_help', type: 'string'},
      {name: 'code', type: 'string'},
    ],
    pageSize: 1000000,
    proxy: {
      type: 'direct',
      directFn: 'VisualPanel_class.GetVisualPanelList',
      reader: {
        type: 'json',
        root: 'result'
      }
    },
    autoLoad: true});
  win = findFirstWindow();
  _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
  _height = Math.round(win.window.document.body.clientHeight / 6 * 5);

  var ListVisualPanelWindow = win.myDesktopApp.desktop.createWindow({
    title: 'Список доступных Панелей Визуализации',
    code: 'ListVisualPanelWindow',
    width: _width,
    height: _height,
    closable: false,
    autoScroll: true,
    HelpContext: _HelpContext,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    layout: {
      type: 'fit'
    },
    modal: true,
    dockedItems: [{
        xtype: 'toolbar',
        dock: 'bottom',
        ui: 'footer',
        itemId: 'BtnContainer',
        layout: {
          pack: 'end',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            text: 'Создать',
            handler: function () {
              ShowVisualPanel(ListVisualPanelWindow.down('gridpanel'), null, true, false, null);
            }
          },
          {
            xtype: 'button',
            disabled: true,
            itemId: 'BtnChange',
            text: 'Изменить',
            handler: function () {
              var _grid_ListVisualPanel = ListVisualPanelWindow.down('gridpanel');
              ShowVisualPanel(_grid_ListVisualPanel, _grid_ListVisualPanel.getSelectionModel().getSelection()[0].raw.code,
                      false, true,
                      _grid_ListVisualPanel.getSelectionModel().getSelection()[0].raw.code_help);
            }
          },
          {
            xtype: 'button',
            itemId: 'BtnDelete',
            disabled: true,
            text: 'Удалить',
            handler: function () {
              Ext.MessageBox.wait({
                msg: 'Выполняется операция. Ждите...',
                width: 300,
                wait: true,
                waitConfig: {interval: 100}
              });
              VisualPanel_class.DeleteFromVisualPanel(ListVisualPanelWindow.child('gridpanel').getSelectionModel().getSelection()[0].raw.id_viewpanel,
                      function (result) {
                        Ext.MessageBox.hide();
                        if ((result.success === false) && (result.result == 're_connect')) {
                          alert(result.msg);
                          window.onbeforeunload = null;
                          findFirstWindow().window.location.href = __first_page;
                          return;
                        }
                        if (result.success) {
                          ListVisualPanelWindow.child('gridpanel').getStore().load();
                          Ext.MessageBox.alert("Результат выполнения ", result.msg);
                        } else {
                          Ext.MessageBox.alert("Ошибка удаления: " + result.msg);
                        }
                      });
            }
          },
          {
            xtype: 'button',
            disabled: true,
            itemId: 'BtnRun',
            text: 'Предпросмотр',
            handler: function () {
              var _grid_ListVisualPanel = ListVisualPanelWindow.down('gridpanel');
              ShowVisualPanel(_grid_ListVisualPanel, _grid_ListVisualPanel.getSelectionModel().getSelection()[0].raw.code, false, false,
                      _grid_ListVisualPanel.getSelectionModel().getSelection()[0].raw.code_help);
            }
          },
          {
            xtype: 'button',
            text: 'Закрыть',
            handler: function () {
              CloseWindow(ListVisualPanelWindow);
            }
          }]
      }],
    items: [
      {
        xtype: 'gridpanel',
        flex: 1,
        header: false,
        columns: [
          {
            xtype: 'gridcolumn',
            enableColumnHide: false,
            dataIndex: 'description',
            hideable: false,
            text: 'Описание Панели Визуализации',
            flex: 1
          },
          {
            xtype: 'gridcolumn',
            enableColumnHide: false,
            dataIndex: 'code',
            hideable: false,
            text: 'Код',
            width: '30%'
          }
        ],
        store: store_ListVisualPanelContainer,
        constrainHeader: true,
        listeners: {
          selectionchange: function (view, selections, options) {
            if (selections.length > 0) {
              ListVisualPanelWindow.getComponent('BtnContainer').getComponent('BtnChange').enable();
              ListVisualPanelWindow.getComponent('BtnContainer').getComponent('BtnDelete').enable();
              ListVisualPanelWindow.getComponent('BtnContainer').getComponent('BtnRun').enable();
            } else {
              ListVisualPanelWindow.getComponent('BtnContainer').getComponent('BtnChange').disable();
              ListVisualPanelWindow.getComponent('BtnContainer').getComponent('BtnDelete').disable();
              ListVisualPanelWindow.getComponent('BtnContainer').getComponent('BtnRun').disable();
            }
          },
          itemdblclick: function (view, record, item, index, e, eOpts) {
            ListVisualPanelWindow.getComponent('BtnContainer').getComponent('BtnRun').handler();
          },
          scope: this
        }
      }]
  });
  ListVisualPanelWindow.show();
}

function ShowVisualPanel_New(Params) {
  ShowVisualPanel(null, null, true, true, Params.HelpContext);
}

function ShowVisualPanel_Edit(Params) {
  ShowVisualPanel(null, Params.code, false, true, Params.HelpContext);
}

function ShowVisualPanel_Run(Params) {
  ShowVisualPanel(null, Params.code, false, false, Params.HelpContext);
}

function ShowVisualPanel(_grid_ListVisualPanel, VisualPanelCode, isNew, DesignMode, _HelpContext) {
  if (isNew == true) {
    DesignMode = true
  }
  Ext.MessageBox.wait({
    msg: 'Выполняется операция. Ждите...',
    width: 300,
    wait: true,
    waitConfig: {interval: 100}
  });

  VisualPanel_class.GetVisualPanel_InitObject(VisualPanelCode,
          function (result) {
            Ext.MessageBox.hide();
            if ((result.success === false) && (result.result == 're_connect')) {
              alert(result.msg);
              window.onbeforeunload = null;
              findFirstWindow().window.location.href = __first_page;
              return;
            }
            if (result.success == true) {
              var ConfigObject = result.result;
              // ConfigObject.VisualPanelParams = {};
              //  ConfigObject.isNew = isNew;
              var VisualPanelMainWindow = Ext.create("VisualPanelMainContainer.view.VisualPanelMainContainer", {
                //  title: 'Панель Визуализации -  ' + ConfigObject.description,
                VisualPanelListGrid: _grid_ListVisualPanel,
                is_DesignMode: DesignMode,
                ParamValuesArray: {}, // хранилище для последних значений параметорв
                HelpContext: _HelpContext,
                ConfigObject: ConfigObject,
              });

              win = findFirstWindow().window;
              _width = Math.round(win.document.body.clientWidth / 6 * 5);
              _height = Math.round(win.document.body.clientHeight / 6 * 5);
              // востанавливаем настройки окон
              if (DesignMode) {
                _title = 'Конструктор панели отображения';
                _code = 'DesignVisualPanelWindow';
                _tools = [{
                    type: 'help',
                    qtip: 'Получить справку',
                    callback: ShowHelp
                  }
                  ,
                  {
                    type: 'save',
                    qtip: 'Сохранить',
                    callback: function (panel) {
                      var VisualPanelMainContainer = panel.down('#VisualPanelMainContainer');
                      VisualPanelMainContainer.SaveVisualPanel();
                    }
                  }
                ];
              }
              else {
                _title = VisualPanelMainWindow.ConfigObject.Description;
                _code = VisualPanelMainWindow.ConfigObject.Code;
                _tools = [{
                    type: 'help',
                    qtip: 'Получить справку',
                    callback: ShowHelp
                  }
                  ,
                  {
                    type: 'gear',
                    qtip: 'Задать параметры',
                    callback: function (panel) {
                      var VisualPanelMainContainer = panel.down('#VisualPanelMainContainer');
                      VisualPanelMainContainer.SetVisualPanelProps();
                    }
                  }
                ];
              }

              var DesignVisualPanelWindow = win.myDesktopApp.desktop.createWindow(//Ext.create("Ext.Window", 
                      {
                        title: _title,
                        code: _code,
                        width: _width,
                        height: _height,
                        animCollapse: true,
                        constrainHeader: true,
                        //closable: true,
//                        autoScroll: false,
//                        maximized: false,
//                        maximizable: true,
                        HelpContext: _HelpContext,
                        modal: DesignMode,
                        tools: _tools,
                        layout: {
                          type: 'fit'
                        },
                        constrainHeader: true,
                                modal: false
                      });
//              VisualPanelMainWindow.setTitle(VisualPanelMainWindow.title + VisualPanelMainWindow.ConfigObject.Description);
              VisualPanelMainWindow.DisplayVisualPanelProps();
              DesignVisualPanelWindow.add(VisualPanelMainWindow);
              DesignVisualPanelWindow.show();
              var MaketPanel = Ext.ComponentQuery.query('#MaketPanel', VisualPanelMainWindow);
              MaketPanel = MaketPanel[0];
              if (DesignMode != true) {
//                VisualPanelMainWindow.down('#seperatorSave').hide();
//                VisualPanelMainWindow.down('#btnPreview').hide();
//                VisualPanelMainWindow.down('#btnSave').hide();
                if (MaketPanel.tools[0] != undefined) {
                  MaketPanel.getHeader( ).tools[0].hide();
                  MaketPanel.getHeader( ).hide();
                }
                VisualPanelMainWindow.down('#PropertyPanel').hide();
//              } else {
//                VisualPanelMainWindow.down('#btnSetParam').hide();
              }
              if (ConfigObject) {
                LoadVisualPanelMaket(MaketPanel, ConfigObject.MaketCode, DesignMode,
                        function () {
                          var MaketContainerObject = MaketPanel.down('#MaketContainer');
                          //VisualPanelMainWindow.ParamValuesArray = {};
                          Ext.each(ConfigObject.VisualPanelParams, function (par) {
                            try {
                              par.Value = eval(par.ParamDefaultValue);
                              VisualPanelMainWindow.ParamValuesArray[par.ParamCode] = par.Value;
                            } catch (e) {
                              Ext.MessageBox.alert('Ошибка', e.name)
                            } finally {
                            }
                          });

                          ShowVisualPanelContents(MaketContainerObject, ConfigObject, VisualPanelMainWindow.ParamValuesArray, DesignMode);
                        }
                );
              }
            } else {
              Ext.MessageBox.alert('Ошибка', "Ошибка загрузки: " + result.msg);
            }
          });
}

function ShowMaximizeDetalPanel(_ItemContainer, ownerPanel) {//показывает в модальном окне выбранную панель
  var pw = findFirstWindow();
  var MaximizeDetalPanel_Window = pw.myDesktopApp.desktop.createWindow(
          /*Ext.create("Ext.Window",*/
                  {title: ownerPanel.title,
                    code: 'ShowMaximizeDetalPanel',
                    width: Math.round(pw.window.document.body.clientWidth),
                    height: Math.round(pw.window.document.body.clientHeight),
                    closable: false,
                    autoScroll: false,
                    maximized: true,
                    maximizable: false,
                    draggable: false,
                    HelpContext: ownerPanel.HelpContext,
                    layout: {
                      type: 'fit'
                    },
                    listeners: {
                      move: function (me, x, y, eOpts) {
                        if (y < 1) {
                          me.y = 1;
                        }
                      }
                    },
                    modal: true,
                    tools: [
                      {
                        type: 'help',
                        qtip: 'Справка',
                        callback: ShowHelp
                      },
                      {
                        xtype: 'tool',
                        tooltip: 'Вернуть в панель',
                        type: 'pin',
                        handler: function (event, toolEl, owner, tool) {
                          MaximizeDetalPanel_Window.hide(ownerPanel, function () {
                            ownerPanel.autoScroll = false;
                            var w = ownerPanel.getSize();
                            var w1 = _ItemContainer.getSize();
                            ownerPanel.add(_ItemContainer);
                            var w = ownerPanel.getSize();
                            var w1 = _ItemContainer.getSize();

                            MaximizeDetalPanel_Window.destroy();
                            //    ownerPanel.win.fireEvent('redraw', this);
                          });
                          // CloseWindow(MaximizeDetalPanel_Window);
                        }
                      }
                    ]
                  }
          );
          MaximizeDetalPanel_Window.show(ownerPanel, function () {
            var w = ownerPanel.getSize();
            var w1 = _ItemContainer.getSize();
            MaximizeDetalPanel_Window.add(_ItemContainer);
          });
        }

function ShowList_viewpanel_maket(_HelpContext) { //показывает список сохраненных макетов аналитических панелей
  var store_List_viewpanel_maket = new Ext.data.Store({
    fields: [
      {name: 'description', type: 'string'},
      {name: 'code', type: 'string'}
    ],
    pageSize: 1000000,
    proxy: {
      type: 'direct',
      directFn: 'VisualPanel_class.Get_viewpanel_maket_List',
      //  extraParams:,
      reader: {
        type: 'json',
        root: 'result'
      }
    },
    autoLoad: true});
  win = findFirstWindow();
  _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
  _height = Math.round(win.window.document.body.clientHeight / 6 * 5);

  var List_viewpanel_maket_Window = win.myDesktopApp.desktop.createWindow({
    title: 'Список доступных макетов Панелей Визуализации ',
    code: 'List_viewpanel_maket_Window',
    width: _width,
    height: _height,
    closable: false,
    autoScroll: true,
    HelpContext: _HelpContext,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    layout: {
      type: 'fit'
    },
    listeners: {
      move: function (me, x, y, eOpts) {
        if (y < 1) {
          me.y = 1;
        }
      }
    },
    modal: true,
    dockedItems: [{
        xtype: 'toolbar',
        dock: 'bottom',
        ui: 'footer',
        itemId: 'BtnContainer',
        layout: {
          pack: 'end',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            disabled: true,
            itemId: 'BtnOk',
            text: 'Выбрать',
            handler: function (button, e) {
              var grd = List_viewpanel_maket_Window.down('gridpanel');
              var recordMaster = grd.getSelectionModel().getSelection()[0];
              var MaketCode = recordMaster.get('code');
              List_viewpanel_maket_Window.fireEvent('viewpanel_maket_Select', MaketCode);
              CloseWindow(List_viewpanel_maket_Window);
            }
          },
          {
            xtype: 'button',
            text: 'Закрыть',
            handler: function () {
              CloseWindow(List_viewpanel_maket_Window);
            }
          }]
      }],
    items: [
      {
        xtype: 'gridpanel',
        flex: 1,
        header: false,
        columns: [
          {
            xtype: 'gridcolumn',
            enableColumnHide: false,
            dataIndex: 'description',
            hideable: false,
            text: 'Описание макета Панели Визуализации',
            flex: 1
          },
          {
            xtype: 'gridcolumn',
            enableColumnHide: false,
            dataIndex: 'code',
            hideable: false,
            text: 'Код',
            width: '30%'
          }
        ],
        store: store_List_viewpanel_maket,
        listeners: {
          selectionchange: function (view, selections, options) {
            if (selections.length > 0) {
              List_viewpanel_maket_Window.getComponent('BtnContainer').getComponent('BtnOk').enable();
            } else {
              List_viewpanel_maket_Window.getComponent('BtnContainer').getComponent('BtnOk').disable();
            }
          },
          itemdblclick: function (view, record, item, index, e, eOpts) {
            List_viewpanel_maket_Window.getComponent('BtnContainer').getComponent('BtnOk').handler();
          },
          scope: this
        }
      }]});
  List_viewpanel_maket_Window.show();
  List_viewpanel_maket_Window.addEvents('viewpanel_maket_Select');
  return List_viewpanel_maket_Window;
}

function Add_SelectVisualPanelContents_Tools(MaketContainerObject) {//добавляет кнопки(tool) выбора представления в элементы макета панели отображения
  var panelList = Ext.ComponentQuery.query('panel[VisualPanelItem= true]', MaketContainerObject);
  Ext.Array.each(panelList, function (_Panel) {
    _Panel.addTool({
      type: 'search',
      handler: SelectVisualPanelContents,
      qtip: 'Выбор представления'
    });
  });
}

function Add_MaximizeDetalPanel_Tools(MaketContainerObject) {//добавляет кнопки(tool) полноразменрного просмотра в элементы макета панели отображения
  var panelList = Ext.ComponentQuery.query('panel[VisualPanelItem= true]', MaketContainerObject);
  Ext.Array.each(panelList, function (_Panel) {
    _Panel.addTool({
      type: 'unpin',
      qtip: 'Детальный просмотр',
      handler: function (event, toolEl, owner, tool) {
        var p = owner.up('panel');
        var ItemContainer = Ext.ComponentQuery.query('#ItemContainer', p);
        ItemContainer = ItemContainer[0];
        if (ItemContainer != undefined) {
          ShowMaximizeDetalPanel(ItemContainer, p);
        } else
          Ext.Msg.alert('Выполнение невозможно', 'Панель пуста');
      }});
  });
}

function LoadVisualPanelMaket(MaketPanel, MaketCode, DesignMode, CallBackFunc) {
  if ((MaketCode) && (MaketPanel) && (trim(MaketCode) != '') && (MaketPanel != undefined)) {
    var childOfPanel = MaketPanel.getComponent('MaketContainer');
    var c = childOfPanel.down('container');
    if (c != undefined) {
      Ext.destroy(c); //разрушаю прежнее содержимое панели
    }
    var ScriptName = _URLVisualPanelMakets + MaketCode + '.js';
    Ext.Loader.loadScript({url: ScriptName
      , onLoad: function () {
        var VisualPanelTestContainer = Ext.create('VisualPanelMakets.view.' + MaketCode, {_Parent: childOfPanel});
        childOfPanel.add(VisualPanelTestContainer);
        if (DesignMode === true) {
          Add_SelectVisualPanelContents_Tools(VisualPanelTestContainer);
        } else {
          Add_MaximizeDetalPanel_Tools(VisualPanelTestContainer);
        }
        if (CallBackFunc) {
          CallBackFunc();
        }
      }, onError: function () {
        Ext.MessageBox.alert('Ошибка', "Ошибка загрузки файла: " + ScriptName);
      }});
  }
}

function ShowVisualPanelContents(MaketContainerObject, ConfigObject, ParamArray, DesignMode) {
//  var panelList = Ext.ComponentQuery.query('panel[VisualPanelItem= true]', MaketContainerObject);
//    Ext.Array.each(panelList, function (_Panel) {
  Ext.Array.each(ConfigObject.MaketContainerObject, function (PanelItemObj) {
    var Panel = MaketContainerObject.down('#' + PanelItemObj.itemId);
    if (Panel) {
      var ItemContainer = Ext.ComponentQuery.query('#ItemContainer', Panel); //один общий контейнер для разных способов отображения
      ItemContainer = ItemContainer[0];
      if (ItemContainer != undefined) {
        Ext.destroy(ItemContainer); //разрушаю прежнее содержимое панели
      }
//обязательно нужен один общий контейнер для разных способов отображения
//например если в панели вывели html то потом туда другие контролы херово ставятся
//да и зачищать содержимое при замене контрола удобнее зная itemId: 'ItemContainer'
      var ItemContainer = Ext.create('Ext.container.Container', {itemId: 'ItemContainer', layout: 'fit', autoScroll: false});
      Panel.add(ItemContainer);
      Panel.setTitle(PanelItemObj.title);
      Panel.VisualPanelItemTypes = PanelItemObj.VisualPanelItemTypes;
      Panel.name_type_view = PanelItemObj.name_type_view;
      Panel.keyObjectId = PanelItemObj.keyObjectId;
      Panel.code_object_Descr = PanelItemObj.code_object_Descr;
      if (DesignMode !== true) {
        switch (PanelItemObj.VisualPanelItemTypes) {
          case 1:
            QueryBuilder_class.GetStoredQueryCondition(PanelItemObj.keyObjectId, ParamArray, function (response, options) {
              var result = response;
              if (result.success == true) {
                LoadGridIntoContainer(ItemContainer, null, result.result.sysname, result.result.StoredQueryCondition, result.result.Description);
              }
            });
            break;
          case 2:
            var url = _URLProjectRoot + 'HTML_Report/ComposeHTMLReport.php?ParamValuesArray='
                    + encodeURIComponent(Ext.JSON.encode(ParamArray))
                    + '&code=' + PanelItemObj.keyObjectId;
            var html_text = '<iframe src="' + url + '" width="100%" height="100%" ></iframe>';
            ItemContainer.update(html_text);
            break;
          case 3:
            DiagramTemplate_class.ComposeDiagram(PanelItemObj.keyObjectId, ParamArray, function (response, options) {
              var result = response;
              if (result.success == true) {
                var w = draw_chart(result.result, ParamArray);
                ItemContainer.add(w);
              }
            });
            break;
          case 4:
            Ext.Ajax.request({
              url: _URLProjectRoot + 'objectdata/pivot/RunPivotBackEnd.php',
              method: 'POST',
              params: {
                GetPivotObject: true,
                pivot_code: PanelItemObj.keyObjectId,
              },
              success: function (response, options) {
                var result = Ext.JSON.decode(response.responseText);
                if (result.success == true) {
                  //   ShowPivotResultWindow(result, '', false, ItemContainer);
                  // ComparePivotDataByPivotCode(PanelItemObj.keyObjectId, ItemContainer, '');
                }
              }
            });
            break;
          case 5:
            break;
        }
      }
    }
  });
}