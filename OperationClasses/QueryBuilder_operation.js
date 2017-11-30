Ext.define('KOMETA.Operation.QueryBuilder_operation', {
  CloseDesignQueryWindow: function (_Win) {
    var DesignContainer = _Win.down('#DesignMainContainer');
    Ext.Array.each(DesignContainer.ArrayOfObjectTypes, function (value) {
      Ext.destroy(DesignContainer.ArrayOfObjectTypes[value]);
    });
    _Win.close();
    Ext.destroy(_Win);
  }
  ,
  SaveQueryChilNodesToObject: function (_Node, _result) {//служебная функция для SaveNodeToJson
    var me = this;
    _result.children = [];
    for (var i = 0; i < _Node.childNodes.length; i++) {
      var ChildData = {};
      var ChildNode = _Node.childNodes[i];
      for (var prop in ChildNode.raw) {
        ChildData[prop] = ChildNode.raw[prop];
      }
      var n = _result.children.push(ChildData);
      me.SaveQueryChilNodesToObject(ChildNode, _result.children[n - 1]);
    }
  }
  ,
  SaveQueryNodeToJson: function (_Node) {  //служебная функция проходит по дереву и сохраняет рау дата в древовидный объект для дальнейшего сохранения в БД
    var me = this;
    var result = {};
    for (var prop in _Node.raw) {
      result[prop] = _Node.raw[prop];
    }
    me.SaveQueryChilNodesToObject(_Node, result);
    return result;
  }
  ,
  New: function (Grid, Operation) { // выбор объекта для формы и дальше проектирование формы ввода
    var me = this;
    if ((joins = Grid.ObjectInitGrid.joins == undefined) || (joins = Grid.ObjectInitGrid.joins == '')) {
      var win = SelectValSlv({sysname: 'sv_mb_object_select', ExtFilterWhereCond: '', object_Caption: 'Выбор объекта для создания формы ввода', HelpContext: ''});
      win.addListener('ValSlvSelected', function (context, SelID, SelDescr) {
        Operation.id_object = SelID.id_object;
        var w = me.DesignStoredQuery(Grid, Operation, true);
      });
    }
    else {
      Operation.id_object = null;
      var w = me.DesignStoredQuery(Grid, Operation, true);
    }
  }
  ,
  Edit: function (Grid, Operation, isNew) { //проектирование формы ввода
    var me = this;
    me.DesignStoredQuery(Grid, Operation);
  }
  ,
  DesignStoredQuery: function (Grid, Operation, isNew) { //проектирование формы ввода
    var me = this;
    if (isNew) {
      id_stored_query = null;

      Operation.Readonly = 0;
      if (Operation.id_object == undefined) {
        var recordMaster = Grid.masterGrid.getSelectionModel().getSelection()[0];
        if (recordMaster != undefined) {
          Ext.each(Grid.LinkObject.joins, function (joins, index) {
            var MasterKeyValue = recordMaster.raw[joins.master_key_fieldname];
            if (!((MasterKeyValue == null) || (MasterKeyValue == undefined))) {
              Operation[joins.detail_key_fieldname] = MasterKeyValue;
            }
          })
        }
      }
    }
    else {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if ((sm == undefined) || (sm == null)) {
        Ext.MessageBox.alert('Не выбрана запись');
      }
      if (Grid.ObjectInitGrid.readonly_fld != undefined) {
        Operation.ReadOnly = sm[Grid.ObjectInitGrid.readonly_fld];
        if (Operation.ReadOnly != 1)
          Operation.ReadOnly = 0;
      }
      else {
        Operation.Readonly = 0;
      }


      var id_stored_query = sm['id_stored_query'];
    }

    var id_object = Operation.id_object;


    win = findFirstWindow();
    _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
    _height = Math.round(win.window.document.body.clientHeight / 6 * 5);

    var DesignWindow = win.myDesktopApp.desktop.createWindow({
      title: 'Конструктор запросов',
      code: 'DesignStoredQuery',
      width: _width,
      height: _height,
      closable: false,
      autoScroll: true,
      maximized: false,
      maximizable: false,
      //HelpContext: _HelpContext,
      tools: [{
          type: 'help',
          qtip: 'Справка',
          callback: ShowHelp
        },
        {
          type: 'save',
          qtip: 'Сохранить',
          callback: function (panel) {
            var mainContainer = panel.down('#DesignMainContainer');
            mainContainer.SaveForm();
          }
        }
        ,
        {type: 'maximize',
          qtip: 'Развернуть',
          callback: function (w) {
            DesignWindow.maximize(true);
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
            var mainContainer = w.down('#DesignMainContainer');

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
                    mainContainer.SaveForm();
//                    if (DesignWindow.Saved)
//                      DesignWindow.Grid.ReloadGrid();
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
    var ArrayOfObjectTypes = []; //массив типов объектов 
    ArrayOfObjectTypes['GroupParam'] = Ext.create('QueryBuilder.view.GroupParam_PropertyForm');
    ArrayOfObjectTypes['Link'] = Ext.create('QueryBuilder.view.Link_PropertyForm');
    ArrayOfObjectTypes['FieldCondition'] = Ext.create('QueryBuilder.view.FieldCondition_PropertyForm');
    ArrayOfObjectTypes['Query'] = Ext.create('QueryBuilder.view.Query_PropertyForm');
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    QueryBuilder_class.InitObject(id_stored_query, id_object, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        findFirstWindow().window.location.href = _URLProjectRoot + 'index.php';
        return;
      }
      if (result.success) {
        var Root = result.result.QueryTemplate;
        var ParamsObject = {};
        //ParamsObject.DesignWindow = DesignWindow;
        ParamsObject.Root = Root;
        ParamsObject.id_stored_query = id_stored_query;
        ParamsObject.ReadOnly = Operation.Readonly;
        ParamsObject.ArrayOfObjectTypes = ArrayOfObjectTypes;
        ParamsObject.id_object = result.result.QueryTemplate.id_object;
        ParamsObject.Grid = Grid;
        var DesignMainContainer = me.CreateDesignInputFormMainContainer(ParamsObject);
        DesignWindow.add(DesignMainContainer);
      } else {
        Ext.MessageBox.alert("Ошибка получения данных: ", result.msg);
      }
    });

    DesignWindow.show();
    return DesignWindow;
  }
  ,
  CreateDesignInputFormMainContainer: function (ParamsObject) {
    var me = this;
    var DesignInputFormMainContainer = Ext.create('QueryBuilder.view.DesignMainContainer',
            // передаю параметры в форму для использования их в методах формы
                    {//ParentWindow: ParamsObject.DesignWindow,
                      ArrayOfObjectTypes: ParamsObject.ArrayOfObjectTypes, //массив типов объектов
                      ReadOnly: ParamsObject.ReadOnly,
                      modified: false,
                      id_stored_query: ParamsObject.id_stored_query,
                      Grid: ParamsObject.Grid,
                      id_object: ParamsObject.id_object,
                      query_class: me});
            if (DesignInputFormMainContainer.ReadOnly == true) {
              DesignInputFormMainContainer.down('#SaveBtn').hide();
            }
            var StructureTree = DesignInputFormMainContainer.down('#StructureTree');
            StructureTree.store.setRootNode(ParamsObject.Root);
            StructureTree.getSelectionModel().on('select', function (selModel, record) {//обработчик перемещений по дереву
              var PropertyContainer = DesignInputFormMainContainer.down('#PropPanel').down('#PropertyContainer');
              var PropPanel = PropertyContainer.getComponent(0);
              if (PropPanel != undefined) {
                PropertyContainer.remove(PropPanel, false);
              }
              var CurObj = DesignInputFormMainContainer.ArrayOfObjectTypes[record.raw.ItemType];
              if (CurObj != undefined) {
                PropertyContainer.add(CurObj);
                if (Ext.isFunction(CurObj.LoadNodeValues) == true) {
                  var save_modified = DesignInputFormMainContainer.modified;
                  CurObj.LoadNodeValues(record.raw, record);
                  DesignInputFormMainContainer.modified = save_modified;
                }
              }
              me.SetDesignInputFormMainContainerBtnAndMenuState(selModel, record.raw.ItemType, DesignInputFormMainContainer);// доступность кнопок
            });
            StructureTree.getSelectionModel().on('focuschange', function (selModel, oldFocused, newFocused, eOpts) {//обработчик перемещений по дереву
              if (newFocused == undefined) {
                var PropertyContainer = DesignInputFormMainContainer.down('#PropPanel').down('#PropertyContainer');
                var PropPanel = PropertyContainer.getComponent(0);
                if (PropPanel != undefined) {
                  PropertyContainer.remove(PropPanel, false);
                }
                me.SetDesignInputFormMainContainerBtnAndMenuState(selModel, '', DesignInputFormMainContainer);// доступность кнопок
              }
            });
            return DesignInputFormMainContainer;
          }
  ,
  SetDesignInputFormMainContainerBtnAndMenuState: function (selModel, ItemType, DesignInputFormMainContainer) {// доступность кнопок и меню
    if (selModel.getSelection( ).length > 0) {
      DesignInputFormMainContainer.down('#BtnDel').enable();
    } else {
      DesignInputFormMainContainer.down('#BtnDel').disable();
    }
    var StructureTree = DesignInputFormMainContainer.down('#StructureTree');
    if (selModel.getSelection( )[0] == StructureTree.getRootNode()) {
      DesignInputFormMainContainer.down('#BtnDel').disable();
    }
  }
  ,
  Execute: function (Grid, Operation) {
    var me = this;
    var codeStoredQuery = Operation.param_list.code, ParamValuesArray = Operation.param_list.ParamValuesArray;
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    QueryBuilder_class.GetStoredQueryParamList(codeStoredQuery, function (response, options) {
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
        SetParamValuesAndRun(ArrayParam, ParamValuesArray, codeStoredQuery, me.GetStoredQueryConditionSilent);
      } else {
        Ext.MessageBox.alert("Ошибка получения параметров ", response.statusText);
      }
    });
  }
  ,
  GetStoredQueryConditionSilent: function (param_list) {
    var codeStoredQuery = param_list.code, ParamValuesArray = param_list.ParamValuesArray;
    var ReturnObj = Ext.create("Ext.util.Observable", {});//возвращает объект с ожиданием событи выполнения функции куда передается возвращаемое значение функции
    ReturnObj.addEvents('GetStoredQueryCondition_Return');
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });

    QueryBuilder_class.GetStoredQueryCondition(codeStoredQuery, ParamValuesArray, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var ResultCondition = result.result.StoredQueryCondition;
        var ResultSysName = result.result.sysname;
        var Description = result.result.Description;
        Run_operation(null
                , {func_name: 'ShowMasterDetailGridWindow'
                  , func_class_name: 'Grid_operation'
                  , param_list: {sysname: ResultSysName
                    , ExtFilterWhereCond: ' and ' + ResultCondition
                    , Caption: 'Результат запроса - ' + Description
                  }
                });

      } else {
        Ext.MessageBox.alert("Ошибка формирования условия отбора", result.msg);
      }
    });

    return ReturnObj;
  }


});