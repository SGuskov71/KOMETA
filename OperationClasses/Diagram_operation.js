//dhtmlLoadScript(_URLProjectRoot + 'Params/ParamsFunction.js');
dhtmlLoadScript(_URLProjectRoot + 'sys/SQLEditor.js');
dhtmlLoadScript(_URLProjectRoot + 'Diagram_Template/DrawChart.js');

Ext.define('KOMETA.Operation.Diagram_operation', {
  SaveDiagramChilNodesToObject: function (_Node, _result) {//служебная функция для SaveNodeToJson
    var me = this;
    _result.children = [];
    for (var i = 0; i < _Node.childNodes.length; i++) {
      var ChildData = {};
      var ChildNode = _Node.childNodes[i];
      for (var prop in ChildNode.raw) {
        ChildData[prop] = ChildNode.raw[prop];
      }
      var n = _result.children.push(ChildData);
      me.SaveDiagramChilNodesToObject(ChildNode, _result.children[n - 1]);
    }
  }
  ,
  SaveDiagramNodeToJson: function (_Node) {  //служебная функция проходит по дереву и сохраняет рау дата в древовидный объект для дальнейшего сохранения в БД
    var me = this;
    var result = {};
    for (var prop in _Node.raw) {
      result[prop] = _Node.raw[prop];
    }
    me.SaveDiagramChilNodesToObject(_Node, result);
    return result;
  }
  ,
  CloseDesignDiagramTemplateWindow: function (_Win) {
    var DesignDiagramMainContainer = _Win.down('#DesignDiagramMainContainer');
    Ext.Array.each(DesignDiagramMainContainer.ArrayOfReportObjectTypes, function (value) {
      Ext.destroy(DesignDiagramMainContainer.ArrayOfReportObjectTypes[value]);
    });
    Ext.destroy(DesignDiagramMainContainer.ArrayOfReportObjectTypes);
    _Win.close();
    Ext.destroy(_Win);
  }
  ,
  New: function (Grid, Operation) { //создание шаблонов 
    var me = this;
    if (Operation.param_list == undefined)
      Operation.param_list = {};
    Operation.param_list.code = null;
    me.DesignDiagramTemplate(Grid, Operation);
  }
  ,
  Edit: function (Grid, Operation) { //проектирование шаблонов 
    var me = this;
    me.DesignDiagramTemplate(Grid, Operation);
  }
  ,
  DesignDiagramTemplate: function (Grid, Operation) { //проектирование шаблонов 
    var me = this;
    var _grid_ListDiagramTemplate = Grid;
    if (Operation.param_list != undefined) {
      var _codeDiagramTemplate = Operation.param_list.code;
      var _HelpContext = Operation.param_list._HelpContext;
    }

    if ((Operation.param_list == undefined) || (Operation.param_list.code != undefined)) {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      var _id_Diagram = sm['id_diagram_template'];
      var _codeDiagramTemplate = sm['code'];
    }

    win = findFirstWindow().window;
    _width = Math.round(win.document.body.clientWidth / 6 * 5);
    _height = Math.round(win.document.body.clientHeight / 6 * 5);
    // востанавливаем настройки окон

    var DesignDiagramWindow = win.myDesktopApp.desktop.createWindow(
            {
              title: 'Конструктор шаблонов диаграмм',
              code: 'DesignDiagramWindow',
              width: _width,
              height: _height,
              closable: false,
              autoScroll: true,
              maximized: false,
              maximizable: false,
              HelpContext: _HelpContext,
              Grid: Grid,
              tools: [{
                  type: 'help',
                  qtip: 'Получить справку',
                  callback: me.ShowHelp
                },
                {
                  type: 'save',
                  qtip: 'Сохранить',
                  callback: function (panel) {
                    var DesignDiagramMainContainer = panel.down('#DesignDiagramMainContainer');
                    DesignDiagramMainContainer.SaveDiagramTemplate();
                    if (DesignDiagramWindow.Saved)
                      DesignDiagramWindow.Grid.ReloadGrid();
                  }
                }
                ,
                {type: 'maximize',
                  qtip: 'Развернуть',
                  callback: function (w) {
                    DesignDiagramWindow.maximize(true);
                  }
                }
                ,
                {type: 'restore',
                  qtip: 'Востановить',
                  hidden: true,
                  callback: function (w) {
                    DesignDiagramWindow.restore();
                  }
                },
                {type: 'close',
                  itemId: 'closebtn',
                  qtip: 'Закрыть',
                  callback: function (w) {
                    var DesignDiagramMainContainer = w.down('#DesignDiagramMainContainer');

                    if (DesignDiagramMainContainer.modified == false) {
                      w.close();
                    }
                    else {
                      var ww = w;
                      Ext.MessageBox.show({
                        title: 'Сохранение',
                        msg: 'Были внесены изменения!',
                        buttons: Ext.MessageBox.YESNO,
                        buttonText: {
                          yes: "Сохранить",
                          no: "Не сохранять",
                          cancel: "Отмена"
                        },
                        fn: function (btn) {
                          if (btn == "yes") {
                            var DesignDiagramMainContainer = w.down('#DesignDiagramMainContainer');
                            DesignDiagramMainContainer.SaveDiagramTemplate();
                            if (DesignDiagramWindow.Saved)
                              DesignDiagramWindow.Grid.ReloadGrid();
                            w.close();
                          }
                          else if (btn == "no") {
                            ww.close();
                          }
                        }
                      });

                    }

                  }
                }
              ],
              layout: {
                type: 'fit'
              },
              constrainHeader: true,
              modal: true
            });

    var ArrayOfDiagramObjectTypes = []; //массив типов объектов отчета и их форм ввода
    ArrayOfDiagramObjectTypes['diagram'] = Ext.create('DiagramTemplate.view.diagram_PropertyForm');
    ArrayOfDiagramObjectTypes['chart'] = Ext.create('DiagramTemplate.view.chart_PropertyForm');
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    DiagramTemplate_class.InitObject(_codeDiagramTemplate, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
                                window.onbeforeunload = null;    
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var Root = result.result.DiagramTemplate;
        var DesignDiagramMainContainer = me.CreateDesignDiagramMainContainer(DesignDiagramWindow, Root, ArrayOfDiagramObjectTypes,
                _grid_ListDiagramTemplate, _codeDiagramTemplate, _id_Diagram);
        DesignDiagramWindow.add(DesignDiagramMainContainer);
      } else {
        Ext.MessageBox.alert("Ошибка получения данных: ", result.msg);
      }
    });
    //DesignDiagramWindow.addEvents('GridRefresh');
    DesignDiagramWindow.show();
    return DesignDiagramWindow;
  }
  ,
  CreateDesignDiagramMainContainer: function (_DesignDiagramWindow, _Root, _ArrayOfDiagramObjectTypes,
          _grid_ListDiagramTemplate, _codeDiagramTemplate, _id_Diagram) {
    var me = this;
    // передаю параметры в форму для использования их в методах формы
    var DesignDiagramMainContainer = Ext.create('DiagramTemplate.view.DesignDiagramMainContainer',
            {ParentWindow: _DesignDiagramWindow,
              DiagramObject: me,
              modified: false,
              ArrayOfReportObjectTypes: _ArrayOfDiagramObjectTypes, //массив типов объектов отчета и их форм ввода//массив типов объектов отчета и их форм ввода
              grid_ListDiagramTemplate: _grid_ListDiagramTemplate,
              codeDiagramTemplate: _codeDiagramTemplate,
              id_Diagram: _id_Diagram});
    var StructureTree = DesignDiagramMainContainer.down('#StructureTree');
    StructureTree.store.setRootNode(_Root);
    StructureTree.getSelectionModel().on('select', function (selModel, record) {//обработчик перемещений по дереву
      var PropertyContainer = DesignDiagramMainContainer.down('#PropPanel').down('#PropertyContainer');
      var PropPanel = PropertyContainer.getComponent(0);
      if (PropPanel != undefined) {
        PropertyContainer.remove(PropPanel, false);
      }
      var CurObj = DesignDiagramMainContainer.ArrayOfReportObjectTypes[record.raw.ItemType];
      if (CurObj != undefined) {
        PropertyContainer.add(CurObj);
        if (Ext.isFunction(CurObj.LoadNodeValues) == true) {
          var save_modified = DesignDiagramMainContainer.modified;
          CurObj.LoadNodeValues(record.raw, record);
          DesignDiagramMainContainer.modified = save_modified;
        }
      }
      me.SetDesignDiagramMainContainerBtnAndMenuState(selModel, record.raw.ItemType, DesignDiagramMainContainer); // доступность кнопок
    });
    StructureTree.getSelectionModel().on('focuschange', function (selModel, oldFocused, newFocused, eOpts) {//обработчик перемещений по дереву
      if (newFocused == undefined) {
        var PropertyContainer = DesignDiagramMainContainer.down('#PropPanel').down('#PropertyContainer');
        var PropPanel = PropertyContainer.getComponent(0);
        if (PropPanel != undefined) {
          PropertyContainer.remove(PropPanel, false);
        }
        me.SetDesignDiagramMainContainerBtnAndMenuState(selModel, null, DesignDiagramMainContainer); // доступность кнопок
      }
    });
    return DesignDiagramMainContainer;
  }
  ,
  SetDesignDiagramMainContainerBtnAndMenuState: function (selModel, _ItemType, DesignDiagramMainContainer) {// доступность кнопок и меню

    if (selModel.getSelection().length > 0) {
      DesignDiagramMainContainer.down('#BtnPaste').enable();
      DesignDiagramMainContainer.down('#BtnCopy').enable();
      DesignDiagramMainContainer.down('#BtnCut').enable();
      DesignDiagramMainContainer.down('#BtnDel').enable();
    } else {
      DesignDiagramMainContainer.down('#BtnPaste').disable();
      DesignDiagramMainContainer.down('#BtnCopy').disable();
      DesignDiagramMainContainer.down('#BtnCut').disable();
      DesignDiagramMainContainer.down('#BtnDel').disable();
    }
    var StructureTree = DesignDiagramMainContainer.down('#StructureTree');
    if (selModel.getSelection()[0] == StructureTree.getRootNode()) {
      DesignDiagramMainContainer.down('#BtnCopy').disable();
      DesignDiagramMainContainer.down('#BtnCut').disable();
      DesignDiagramMainContainer.down('#BtnDel').disable();
    }
    if (DesignDiagramMainContainer.ClipboardItem == undefined) { //определяю условия отключения кнопки вставить
      DesignDiagramMainContainer.down('#BtnPaste').disable();
    }
  }
  ,
//формирует отчет на стороне php, ParamValuesArray- массив параметров отчета типа {Param1: 'Значение1', Param3: 'Значение3',...}
//PreviewMode - способ формирования в виде HTML для отчета или standalone Ext object
  ComposeDiagramByTemplate: function (Params) {
    var me = this;
    if (Params.PreviewMode == undefined)
      Params.PreviewMode = 0;
    var _codeDiagramTemplate = Params.code,
            ParamValuesArray = Params.ParamValuesArray,
            PreviewMode = parseInt(Params.PreviewMode),
            ModalWindow = Params.ModalWindow;

    if (ModalWindow == undefined)
      ModalWindow = false;

    if (!isMobile.SenchaTouchSupported()) {
      Ext.MessageBox.wait({
        msg: 'Выполняется операция. Ждите...',
        width: 300,
        wait: true,
        waitConfig: {interval: 100}
      });
    }
    if (((PreviewMode != undefined) && (PreviewMode == 1)) || (isMobile.SenchaTouchSupported())) {
//??
      if (ParamValuesArray == undefined) {
        ParamValuesArray = {};
      }
      DiagramTemplate_class.GetDiagramCaption(_codeDiagramTemplate, function (response, options) {
        if (!isMobile.SenchaTouchSupported()) {
          Ext.MessageBox.hide();
        }
        var result = response;
        if ((result.success === false) && (result.result == 're_connect')) {
          if (!isMobile.SenchaTouchSupported()) {
            Ext.MessageBox.alert(result.msg);
                                window.onbeforeunload = null;    
            findFirstWindow().window.location.href = __first_page;
          }
          else {
            Ext.MessageBox.alert('Подключение', result.msg);
                                window.onbeforeunload = null;    
            location.href = __first_page;
          }
          return;
        }
        if (result.success) {
          for (var p in ParamValuesArray) {
            result.result = result.result.replace(new RegExp(':' + p + ':', "g"), ParamValuesArray[p]);

          }
          buildW_desktop(_URLProjectRoot + 'Diagram_Template/ComposeDiagramForHTML.php?PreviewComposeDiagramForHTML=true'
                  + '&ParamValuesArray=' + encodeURIComponent(Ext.JSON.encode(ParamValuesArray))
                  + '&Diagram_Template_Code=' + _codeDiagramTemplate
                  , result.result);
        }

      });
    }
    else if ((PreviewMode == undefined) || (PreviewMode == 0)) {
      // Это надо сделать отдельной кнопкой
      // создания окна и диаграммы на нем
      DiagramTemplate_class.ComposeDiagram(_codeDiagramTemplate, ParamValuesArray, function (response, options) {
        Ext.MessageBox.hide();
        var result = response;
        if ((result.success === false) && (result.result == 're_connect')) {
          Ext.MessageBox.alert('Подключение', result.msg);
                                window.onbeforeunload = null;    
          findFirstWindow().window.location.href = __first_page;
          return;
        }
        if (result.success) {
          var pw = findFirstWindow();
          var chartWindow =
                  pw.myDesktopApp.desktop.createWindow({
                    //Ext.create("Ext.window.Window",{
                    title: 'График ' + result.result.label_chart,
                    code: _codeDiagramTemplate + Ext.JSON.encode(ParamValuesArray),
                    width: Math.round(pw.window.document.body.clientWidth / 6 * 5),
                    height: Math.round(pw.window.document.body.clientHeight / 6 * 5),
                    closable: true,
                    maximizable: true,
                    maximized: true,
                    layout: 'fit',
                    HelpContext: 'PreviewDiagram',
                    tools: [{
                        type: 'help',
                        qtip: 'Cправка',
                        callback: ShowHelp
                      }
                    ]
                    , layout: {
                      type: 'fit'
                    }
                    , modal: ModalWindow
                  });

          var w = draw_chart(result.result, ParamValuesArray);
          chartWindow.add(w);
          chartWindow.show();
        } else {
          Ext.MessageBox.alert("Ошибка формирования: ", result.msg);
        }
      });
    }
    else if ((PreviewMode != undefined) && (PreviewMode == 2)) {
      if (ParamValuesArray == undefined) {
        ParamValuesArray = {};
      }
      DiagramTemplate_class.GetDiagramCaption(_codeDiagramTemplate, function (response, options) {
        if (!isMobile.SenchaTouchSupported()) {
          Ext.MessageBox.hide();
        }
        var result = response;
        if ((result.success === false) && (result.result == 're_connect')) {
          if (!isMobile.SenchaTouchSupported()) {
            Ext.MessageBox.alert(result.msg);
                                window.onbeforeunload = null;    
            findFirstWindow().window.location.href = __first_page;
          }
          else {
            Ext.MessageBox.alert('Подключение', result.msg);
                                window.onbeforeunload = null;    
            location.href = __first_page;
          }
          return;
        }
        if (result.success) {
          for (var p in ParamValuesArray) {
            result.result = result.result.replace(new RegExp(':' + p + ':', "g"), ParamValuesArray[p]);
          }
          buildW_desktop(_URLProjectRoot + 'Diagram_Template/ComposeDiagram_pChart.php?'
                  + 'ParamValuesArray=' + encodeURIComponent(Ext.JSON.encode(ParamValuesArray))
                  + '&Diagram_Template_Code=' + _codeDiagramTemplate
                  , result.result);
        }

      });
    }
  }
  ,
  ComposeDiagramFromGrid_HTML: function (Grid, Operation) {
// обертка ComposeDiagramByTemplateInteractiveParamInput для вызова из ShowObjectExtJS формирует HTML для отчета
    var me = this;
    Params.PreviewMode = null;
    me.ComposeDiagramByTemplateInteractiveParamInput(null, Operation);
  }
  ,
//формирует отчет на стороне php, c интерактивным вводом параметров
// ParamValuesArray- массив параметров отчета типа {Param1: 'Значение1', Param3: 'Значение3',...}
// при вызве из грида ParamValuesArray содержит значения всех  полей текужей записи грида
//PreviewMode - способ формирования в виде HTML для отчета или standalone Ext object
  Execute: function (Grid, Operation) {
    var me = this;
    me.ComposeDiagramByTemplateInteractiveParamInput(Grid, Operation);
  }
  ,
  ComposeDiagramByTemplateInteractiveParamInput: function (Grid, Operation) {
    var me = this;
    if (Operation.param_list != undefined) {
      var _codeDiagramTemplate = Operation.param_list.code;
      var _HelpContext = Operation.param_list._HelpContext;
      var ParamValuesArray = Operation.param_list.ParamValuesArray;
      var PreviewMode = Operation.param_list.PreviewMode;
    }

    if (((Operation.param_list == undefined) || (Operation.param_list.code != undefined)) && (Grid != undefined)) {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if (sm['id_diagram_template'] != undefined)
        var _codeDiagramTemplate = sm['code'];
      var ParamValuesArray = null;
      var PreviewMode = null;
    }

    if (!isMobile.SenchaTouchSupported()) {
      Ext.MessageBox.wait({
        msg: 'Выполняется операция. Ждите...',
        width: 300,
        wait: true,
        waitConfig: {interval: 100}
      });
    }
    DiagramTemplate_class.GetDiagramParamList(_codeDiagramTemplate, function (response) {
      if (!isMobile.SenchaTouchSupported()) {
        Ext.MessageBox.hide();
      }
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
                                window.onbeforeunload = null;    
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var ArrayParam = result.result;
        var Params = {code: _codeDiagramTemplate, ParamValuesArray: ParamValuesArray, PreviewMode: PreviewMode};
        SetParamValuesAndRun(ArrayParam, ParamValuesArray, _codeDiagramTemplate, me.ComposeDiagramByTemplate, Params);
      } else {
        Ext.MessageBox.alert("Ошибка получения параметров ", response.msg);
      }
    });
  }

});