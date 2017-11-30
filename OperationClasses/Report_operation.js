//dhtmlLoadScript(_URLProjectRoot + 'Params/ParamsFunction.js');
dhtmlLoadScript(_URLProjectRoot + 'sys/SQLEditor.js');

Ext.define('KOMETA.Operation.Report_operation', {
  SaveReportChilNodesToObject: function (_Node, _result) {//служебная функция для SaveNodeToJson
    _result.children = [];
    for (var i = 0; i < _Node.childNodes.length; i++) {
      var ChildData = {};
      var ChildNode = _Node.childNodes[i];
      for (var prop in ChildNode.raw) {
        ChildData[prop] = ChildNode.raw[prop];
      }
      var n = _result.children.push(ChildData);
      this.SaveReportChilNodesToObject(ChildNode, _result.children[n - 1]);
    }
  }
  ,
  SaveReportNodeToJson: function (_Node) {  //служебная функция проходит по дереву и сохраняет рау дата в древовидный объект для дальнейшего сохранения в БД
    var result = {};
    for (var prop in _Node.raw) {
      result[prop] = _Node.raw[prop];
    }
    this.SaveReportChilNodesToObject(_Node, result);
//    return Ext.JSON.encode(result);
    return result;
  }
  ,
  New: function (Grid, Operation) { //создание шаблонов отчетов
    Operation.isNew = true;
    this.DesignReportTemplate(Grid, Operation);
  }
  ,
  Edit: function (Grid, Operation) { //проектирование шаблонов отчетов
    var me = this;
    Operation.isNew = false;
    me.DesignReportTemplate(Grid, Operation);
  },
  DesignReportTemplate: function (Grid, Operation) { //проектирование шаблонов отчетов
    var isNew = Operation.isNew;

    var _HelpContext = Grid.ObjectInitGrid.HelpContext;
    var me = this;
    if (isNew) {
      isNew = true;
    } else {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if ((sm == undefined) || (sm == null)) {
        Ext.MessageBox.alert('Не выбрана запись');
        return;
      }
      var _codeReportTemplate = sm['code'];
    }
    win = findFirstWindow();
    _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
    _height = Math.round(win.window.document.body.clientHeight / 6 * 5);

    var DesignReportWindow = win.myDesktopApp.desktop.createWindow(
            {
              title: 'Конструктор шаблонов отчетов',
              code: 'DesignReportWindow',
              width: _width,
              height: _height,
              closable: false,
              autoScroll: true,
              maximized: false,
              maximizable: false,
              HelpContext: _HelpContext,
              Grid: Grid,
              Saved: false,
              tools: [{
                  type: 'help',
                  qtip: 'Справка',
                  callback: ShowHelp
                },
                {
                  type: 'save',
                  qtip: 'Сохранить',
                  callback: function (panel) {
                    var DesignReportMainContainer = panel.down('#DesignReportMainContainer');
                    DesignReportMainContainer.SaveReportTemplate();
                    if (DesignReportWindow.Saved)
                      Grid.ReloadGrid();
                  }
                }
                ,
                {type: 'maximize',
                  qtip: 'Развернуть',
                  callback: function (w) {
                    DesignReportWindow.maximize(true);
                  }
                }
                ,
                {type: 'restore',
                  qtip: 'Востановить',
                  hidden: true,
                  callback: function (w) {
                    DesignWindow.restore();
                  }
                },
                {type: 'close',
                  itemId: 'closebtn',
                  qtip: 'Закрыть',
                  callback: function (w) {
                    var mainContainer = w.down('#DesignReportMainContainer');

                    if (mainContainer.modified == false) {
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
                            var DesignReportMainContainer = w.down('#DesignReportMainContainer');
                            DesignReportMainContainer.SaveReportTemplate();
                            if (DesignReportWindow.Saved)
                              Grid.ReloadGrid();
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
    var ArrayOfReportObjectTypes = []; //массив типов объектов отчета и их форм ввода
    //устанавливает завмсимость с php массивом $ItemType_array
    ArrayOfReportObjectTypes['report'] = Ext.create('Report.view.report_PropertyForm');
    ArrayOfReportObjectTypes['paragraph'] = Ext.create('Report.view.paragraph_PropertyForm');
    ArrayOfReportObjectTypes['text'] = Ext.create('Report.view.text_PropertyForm');
    var Combo = ArrayOfReportObjectTypes['text'].down('#ComboReportFieldList');
    Combo.store = new Ext.data.ArrayStore({
      fields: ['id'],
      data: []
    });
    ArrayOfReportObjectTypes['hyperlink'] = Ext.create('Report.view.hyperlink_PropertyForm');
    ArrayOfReportObjectTypes['image'] = Ext.create('Report.view.images_PropertyForm');
    ArrayOfReportObjectTypes['linebreak'] = Ext.create('Report.view.linebreak_PropertyForm');
    ArrayOfReportObjectTypes['table'] = Ext.create('Report.view.table_PropertyForm');
    ArrayOfReportObjectTypes['tableColumn'] = Ext.create('Report.view.tableColumn_PropertyForm');

    ArrayOfReportObjectTypes['list'] = Ext.create('Report.view.list_PropertyForm');
    ArrayOfReportObjectTypes['embedded_report'] = Ext.create('Report.view.embedded_report_PropertyForm');
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Report_class.InitObject(_codeReportTemplate, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var Root = result.result.ReportTemplate;
        var ComboReportFieldListStoreData = result.result.ComboReportFieldListStoreData;
        var ComboColorListStoreData = [];
        var _Obj = result.result.ComboColorListStoreData;
        for (var Key in _Obj)
          ComboColorListStoreData.push({id: Key, name: _Obj[Key]});


        var ComboImageListStoreData = [];
        var _Obj = result.result.ComboImageListStoreData;
        for (var Key in _Obj)
          ComboImageListStoreData.push({id: _Obj[Key], name: _Obj[Key]});

        var ComboDiagramStoreData = [];
        var _Obj = result.result.ComboDiagramStoreData;
        for (var Key in _Obj)
          ComboDiagramStoreData.push({id: _Obj[Key].code, name: _Obj[Key].description});

        var DesignReportMainContainer = me.CreateDesignReportMainContainer(DesignReportWindow, Root, isNew, ArrayOfReportObjectTypes, // ArrayOfParamTypeInput,
                Grid, _codeReportTemplate, ComboReportFieldListStoreData, ComboColorListStoreData, ComboImageListStoreData, ComboDiagramStoreData);
        DesignReportWindow.add(DesignReportMainContainer);
      } else {
        Ext.MessageBox.alert("Ошибка получения данных: ", result.msg);
      }
    });
    DesignReportWindow.show();
    return DesignReportWindow;
  }
  ,
  SetDesignReportMainContainerBtnAndMenuState: function (selModel, _ItemType, DesignReportMainContainer) {// доступность кнопок и меню
    if (selModel.getSelection().length > 0) {
      DesignReportMainContainer.down('#BtnPaste').enable();
      DesignReportMainContainer.down('#BtnCopy').enable();
      DesignReportMainContainer.down('#BtnCut').enable();
      DesignReportMainContainer.down('#BtnDel').enable();
      if (_ItemType == 'table') {
        DesignReportMainContainer.down('#MI_tableColumn').enable();
      } else {
        DesignReportMainContainer.down('#MI_tableColumn').disable();
      }

      if (_ItemType == 'paragraph') {
        DesignReportMainContainer.down('#MI_linebreak').enable();
        DesignReportMainContainer.down('#MI_images').enable();
        DesignReportMainContainer.down('#MI_hyperlink').enable();
        DesignReportMainContainer.down('#MI_text').enable();
      } else {
        DesignReportMainContainer.down('#MI_linebreak').disable();
        DesignReportMainContainer.down('#MI_images').disable();
        DesignReportMainContainer.down('#MI_hyperlink').disable();
        DesignReportMainContainer.down('#MI_text').disable();
      }
    } else {
      DesignReportMainContainer.down('#BtnPaste').disable();
      DesignReportMainContainer.down('#BtnCopy').disable();
      DesignReportMainContainer.down('#BtnCut').disable();
      DesignReportMainContainer.down('#BtnDel').disable();
      DesignReportMainContainer.down('#MI_linebreak').disable();
      DesignReportMainContainer.down('#MI_images').disable();
      DesignReportMainContainer.down('#MI_hyperlink').disable();
      DesignReportMainContainer.down('#MI_text').disable();
    }
    var StructureTree = DesignReportMainContainer.down('#StructureTree');
    if (selModel.getSelection()[0] == StructureTree.getRootNode()) {
      DesignReportMainContainer.down('#BtnCopy').disable();
      DesignReportMainContainer.down('#BtnCut').disable();
      DesignReportMainContainer.down('#BtnDel').disable();
    }
    if (DesignReportMainContainer.ClipboardItem == undefined) { //определяю условия отключения кнопки вставить 
      DesignReportMainContainer.down('#BtnPaste').disable();
    } else {
      var Node = selModel.getSelection()[0];
      if (Node) {
        if (((DesignReportMainContainer.ClipboardItem.ItemType == 'text') ||
                (DesignReportMainContainer.ClipboardItem.ItemType == 'hyperlink') ||
                (DesignReportMainContainer.ClipboardItem.ItemType == 'image') ||
                (DesignReportMainContainer.ClipboardItem.ItemType == 'linebreak'))
                && ((Node.raw.ItemType == 'embedded_report') ||
                        (Node.raw.ItemType == 'report') ||
                        (Node.raw.ItemType == 'table') ||
                        (Node.raw.ItemType == 'list'))) {
          DesignReportMainContainer.down('#BtnPaste').disable();
        }
      }
    }
  }
  ,
//формирует ODT отчет на стороне php, ParamArray- массив параметров отчета типа {Param1: 'Значение1', Param3: 'Значение3',...}
  ComposeODTReportByTemplate: function (Operation) {
    var _codeReportTemplate = Operation.code, ParamValuesArray = Operation.ParamValuesArray;
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Report_class.CreateODTReport(_codeReportTemplate, ParamValuesArray,
            function (response, options) {
              Ext.MessageBox.hide();
              var result = response;
              if ((result.success === false) && (result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', result.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                return;
              }
              if (result.success) {
                Ext.MessageBox.alert("Результат выполнения ", result.msg + '\n' + result.result);
                window.open(result.result, '_self');
              } else {
                Ext.MessageBox.alert("Ошибка формирования: ", result.msg);
              }
            });
  }
  ,
  ComposeHTMLReportByTemplate: function (Operation) {
    var _codeReportTemplate = Operation.code, ParamValuesArray = Operation.ParamValuesArray;
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Report_class.CreateHTMLReport(_codeReportTemplate, ParamValuesArray,
            function (response, options) {
              Ext.MessageBox.hide();
              var result = response;
              if ((result.success === false) && (result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', result.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                return;
              }
              if (result.success) {
                Ext.MessageBox.alert("Результат выполнения ", result.msg + '\n' + result.result);
                buildW_desktop(result.result, 'html', null, 800, 600, false, null);
              } else {
                Ext.MessageBox.alert("Ошибка формирования: ", result.msg);
              }
            });
  }
  ,
//формирует Pdf отчет на стороне php, ParamArray- массив параметров отчета типа {Param1: 'Значение1', Param3: 'Значение3',...}
  ComposePDFReportByTemplate: function (Operation) {
    var _codeReportTemplate = Operation.code, ParamValuesArray = Operation.ParamValuesArray;
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Report_class.CreatePDFReport(_codeReportTemplate, ParamValuesArray,
            function (response, options) {
              Ext.MessageBox.hide();
              var result = response;
              if ((result.success === false) && (result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', result.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                return;
              }
              if (result.success) {
                Ext.MessageBox.alert("Результат выполнения ", result.msg + '\n' + result.result);
                buildW_desktop(result.result, 'pdf', null, 800, 600, false, null);
              } else {
                Ext.MessageBox.alert("Ошибка формирования: ", result.msg);
              }
            });
  }
  ,
//формирует отчет на стороне php, c интерактивным вводом параметров 
// ParamArray- массив параметров отчета типа {Param1: 'Значение1', Param3: 'Значение3',...}
// при вызве из грида ParamArray содержит значения всех  полей текужей записи грида
  ExecuteODT: function (Grid, Operation) {
    var me = this;
    if (Operation.param_list) {
      var _codeReportTemplate = Operation.param_list.code, ParamValuesArray = Operation.param_list.ParamValuesArray;
    }
    else {
      var _codeReportTemplate, ParamValuesArray;
    }
    if ((_codeReportTemplate == undefined) && (Grid != undefined)) {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if ((sm == undefined) || (sm == null)) {
        Ext.MessageBox.alert('Не задан код отчета');
      } else {
        _codeReportTemplate = sm['code'];
      }

    }
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Report_class.GetReportParamList(_codeReportTemplate, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var ArrayParam = result.result;
        SetParamValuesAndRun(ArrayParam, ParamValuesArray, _codeReportTemplate, me.ComposeODTReportByTemplate);
      } else {
        Ext.MessageBox.alert("Ошибка получения параметров ", response.statusText);
      }
    });
  }
  ,
  ExecuteHTML: function (Grid, Operation) {
    var me = this;
    if (Operation.param_list) {
      var _codeReportTemplate = Operation.param_list.code, ParamValuesArray = Operation.param_list.ParamValuesArray;
    }
    else {
      var _codeReportTemplate, ParamValuesArray;
    }
    if ((_codeReportTemplate == undefined) && (Grid != undefined)) {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if ((sm == undefined) || (sm == null)) {
        Ext.MessageBox.alert('Не задан код отчета');
      } else {
        _codeReportTemplate = sm['code'];
      }

    }
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Report_class.GetReportParamList(_codeReportTemplate, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var ArrayParam = result.result;
        SetParamValuesAndRun(ArrayParam, ParamValuesArray, _codeReportTemplate, me.ComposeHTMLReportByTemplate);
      } else {
        Ext.MessageBox.alert("Ошибка получения параметров ", response.statusText);
      }
    });
  }
  ,
  ExecutePDF: function (Grid, Operation) {
    var me = this;
    if (Operation.param_list) {
      var _codeReportTemplate = Operation.param_list.code, ParamValuesArray = Operation.param_list.ParamValuesArray;
    }
    else {
      var _codeReportTemplate, ParamValuesArray;
    }
    if ((_codeReportTemplate == undefined) && (Grid != undefined)) {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if ((sm == undefined) || (sm == null)) {
        Ext.MessageBox.alert('Не задан код отчета');
      } else {
        _codeReportTemplate = sm['code'];
      }

    }
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Report_class.GetReportParamList(_codeReportTemplate, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var ArrayParam = result.result;
        SetParamValuesAndRun(ArrayParam, ParamValuesArray, _codeReportTemplate, me.ComposePDFReportByTemplate);
      } else {
        Ext.MessageBox.alert("Ошибка получения параметров ", response.statusText);
      }
    });
  }
  ,
  CreateDesignReportMainContainer: function (_DesignReportWindow, _Root, _isNew, _ArrayOfReportObjectTypes, // _ArrayOfParamTypeInput,
          _grid_ListReportTemplate, _codeReportTemplate, _ComboReportFieldListStoreData, _ComboColorListStoreData, ComboImageListStoreData, ComboDiagramStoreData) {
    var me = this;
    var DesignReportMainContainer = Ext.create('Report.view.DesignReportMainContainer',
            // передаю параметры в форму для использования их в методах формы
                    {ParentWindow: _DesignReportWindow,
                      ReportObject: me,
                      isNew: _isNew,
                      modified: false,
                      ArrayOfReportObjectTypes: _ArrayOfReportObjectTypes, //массив типов объектов отчета и их форм ввода//массив типов объектов отчета и их форм ввода
                      grid_ListReportTemplate: _grid_ListReportTemplate,
                      ComboReportFieldListStoreData: _ComboReportFieldListStoreData, // список полей отчета для выбора из комбобокса
                      ComboColorListStoreData: _ComboColorListStoreData,
                      ComboImageListStoreData: ComboImageListStoreData,
                      codeReportTemplate: _codeReportTemplate,
                      ComboDiagramStoreData: ComboDiagramStoreData
                    });
            var StructureTree = DesignReportMainContainer.down('#StructureTree');
            StructureTree.store.setRootNode(_Root);
            StructureTree.getSelectionModel().on('select', function (selModel, record) {//обработчик перемещений по дереву
              var PropertyContainer = DesignReportMainContainer.down('#PropPanel').down('#PropertyContainer');
              var PropPanel = PropertyContainer.getComponent(0);
              if (PropPanel != undefined) {
                PropertyContainer.remove(PropPanel, false);
              }
              var CurObj = DesignReportMainContainer.ArrayOfReportObjectTypes[record.raw.ItemType];
              if (CurObj != undefined) {
                PropertyContainer.add(CurObj);
                if (Ext.isFunction(CurObj.LoadNodeValues) == true) {
                  var save_modified = DesignReportMainContainer.modified;
                  CurObj.LoadNodeValues(record.raw, record);
                  DesignReportMainContainer.modified = save_modified;
                }
              }
              me.SetDesignReportMainContainerBtnAndMenuState(selModel, record.raw.ItemType, DesignReportMainContainer); // доступность кнопок
            });
            StructureTree.getSelectionModel().on('focuschange', function (selModel, oldFocused, newFocused, eOpts) {//обработчик перемещений по дереву
              if (newFocused == undefined) {
                var PropertyContainer = DesignReportMainContainer.down('#PropPanel').down('#PropertyContainer');
                var PropPanel = PropertyContainer.getComponent(0);
                if (PropPanel != undefined) {
                  PropertyContainer.remove(PropPanel, false);
                }
                me.SetDesignReportMainContainerBtnAndMenuState(selModel, '', DesignReportMainContainer); // доступность кнопок
              }
            });
            return DesignReportMainContainer;
          }

});