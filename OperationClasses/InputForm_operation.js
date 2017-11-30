//dhtmlLoadScript(_URLProjectRoot + 'OperationClasses/InputFormDesigner/ShowInputForm.js');

Ext.Loader.setPath({
  'InputFormDesigner.view': _URLProjectRoot + 'ArchitectProject/InputFormDesigner/app/view',
  InputFormDesigner: _URLProjectRoot + 'InputFormDesigner'
});


Ext.define('KOMETA.Operation.InputForm_operation', {
  SaveChilNodesToObject: function (_Node, _result) {//служебная функция для SaveNodeToJson
    me = this;
    _result.children = [];
    for (var i = 0; i < _Node.childNodes.length; i++) {
      var ChildData = {};
      var ChildNode = _Node.childNodes[i];
      for (var prop in ChildNode.raw) {
        if (prop == 'ControlProps') {//копирование ветки доп свойств
          ChildData.ControlProps = {};
          for (var p in ChildNode.raw.ControlProps) {
            ChildData.ControlProps[p] = ChildNode.raw.ControlProps[p];
          }
        } else
        if (prop == 'ContainerProps') {//копирование ветки доп свойств
          ChildData.ContainerProps = {};
          for (var p in ChildNode.raw.ContainerProps) {
            ChildData.ContainerProps[p] = ChildNode.raw.ContainerProps[p];
          }
        } else {
          ChildData[prop] = ChildNode.raw[prop];
        }
      }
      var n = _result.children.push(ChildData);
      me.SaveChilNodesToObject(ChildNode, _result.children[n - 1]);
    }
  }
  ,
  SaveNodeToJson: function (_Node) {  //служебная функция проходит по дереву и сохраняет рау дата в древовидный объект для дальнейшего сохранения в БД
    var result = {};
    for (var prop in _Node.raw) {
      result[prop] = _Node.raw[prop];
    }
    this.SaveChilNodesToObject(_Node, result);
    //  return Ext.JSON.encode(result);
    return result;
  }
  ,
  CloseDesignWindow: function (_Win) {
    var DesignContainer = _Win.down('#DesignMainContainer');
    Ext.Array.each(DesignContainer.ArrayOfObjectTypes, function (value) {
      Ext.destroy(DesignContainer.ArrayOfObjectTypes[value]);
    });
    _Win.close();
    Ext.destroy(_Win);
  }
  ,
  New: function (Grid, Operation) { //Новая проектирование формы ввода для подключения в GridFunction
    var me = this;
    if ((joins = Grid.ObjectInitGrid.joins == undefined) || (joins = Grid.ObjectInitGrid.joins == '')) {
      var win = SelectValSlv({sysname: 'sv_mb_object_select', ExtFilterWhereCond: ' and id_object_type=1 ', object_Caption: 'Выбор объекта для создания формы ввода', HelpContext: ''});
      win.addListener('ValSlvSelected', function (context, SelID, SelDescr) {
        Operation.id_object = SelID.id_object;
        var w = me.DesignInputForm(Grid, Operation, true);
      });
    }
    else {
      Operation.id_object = null;
      this.DesignInputForm(Grid, Operation, true);
    }
  }
  ,
  Edit: function (Grid, Operation) { //Редактирование проектирование формы ввода для подключения в GridFunction
    this.DesignInputForm(Grid, Operation, false);
  }

  ,
  DesignInputForm: function (Grid, Operation, isNew) { //проектирование формы ввода
    me = this;
// Определяем ReadOnly
    if (isNew) {
      id_form = null;

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


      var id_form = sm['id_form'];
    }

    var id_object = Operation.id_object;

    win = findFirstWindow();
    _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
    _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
    var DesignWindow = win.myDesktopApp.desktop.createWindow(
            {
              title: 'Конструктор форм ввода',
              code: 'DesignWindow',
              width: _width,
              height: _height,
              closable: false,
              autoScroll: true,
              HelpContext: Grid.ObjectInitGrid.HelpContext,
              maximized: false,
              maximizable: false,
              tools: [{
                  type: 'help',
                  qtip: 'Справка',
                  callback: me.ShowHelp
                },
                {
                  type: 'save',
                  qtip: 'Сохранить',
                  callback: function (panel) {
                    var DesignMainContainer = panel.down('#DesignMainContainer');
                    DesignMainContainer.SaveForm();
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
                    var DesignMainContainer = w.down('#DesignMainContainer');

                    if (DesignMainContainer.modified == false) {
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
                            var DesignMainContainer = w.down('#DesignMainContainer');
                            DesignMainContainer.SaveForm();
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
    var ArrayOfObjectTypes = []; //массив типов объектов отчета и их форм ввода
    ArrayOfObjectTypes['form'] = Ext.create('InputFormDesigner.view.form_PropertyForm');
    ArrayOfObjectTypes['container'] = Ext.create('InputFormDesigner.view.container_PropertyForm');
    ArrayOfObjectTypes['input_control'] = Ext.create('InputFormDesigner.view.input_control_PropertyForm');
    ArrayOfObjectTypes['label'] = Ext.create('InputFormDesigner.view.label_PropertyForm');
    Ext.MessageBox.wait({
      msg: 'Выполняется загрузка формы редактирования, ждите... ',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });

    InputForm_class.InitObject(id_form, Operation.id_object, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var Root = result.result.InputFormTemplate;
        var ParamsObject = {};
        ParamsObject.DesignWindow = DesignWindow;
        ParamsObject.Root = Root;
        ParamsObject.id_form = id_form;
        ParamsObject.ReadOnly = Operation.Readonly;
        ParamsObject.ArrayOfObjectTypes = ArrayOfObjectTypes;
        ParamsObject.grid_List = Grid;
        ParamsObject.InputControlPropsArray = result.result.InputControlPropsArray;
        ParamsObject.InputContainerProps = result.result.InputContainerProps;
        ParamsObject.labelProps = result.result.labelProps;
        var ComboInputType = ArrayOfObjectTypes['input_control'].down('#ComboInputType');
        ComboInputType.store = new Ext.data.Store({
          fields: ['xtype', 'Caption'],
          data: null
        });
        Ext.Array.each(ParamsObject.InputControlPropsArray, function (value) {
          ComboInputType.store.add({xtype: value.xtype, Caption: value.Caption});
          ComboInputType.store.commitChanges( );
        });

        var InputContainerLayouts = result.result.InputContainerLayouts;
        var ComboInputContainerLayouts = ArrayOfObjectTypes['container'].down('#ComboInputContainerLayouts');
        ComboInputContainerLayouts.store = new Ext.data.Store({
          fields: ['code', 'caption'],
          data: null
        });
        var ComboFormLayouts = ArrayOfObjectTypes['form'].down('#ComboFormLayouts');
        ComboFormLayouts.store = new Ext.data.Store({
          fields: ['code', 'caption'],
          data: null
        });
        Ext.Array.each(InputContainerLayouts, function (value) {
          ComboInputContainerLayouts.store.add({code: value.code, caption: value.caption});
          ComboInputContainerLayouts.store.commitChanges( );
          ComboFormLayouts.store.add({code: value.code, caption: value.caption});
          ComboFormLayouts.store.commitChanges( );
        });

        var DesignMainContainer = me.CreateDesignInputFormMainContainer(ParamsObject);
        DesignWindow.add(DesignMainContainer);
      } else {
        Ext.MessageBox.alert("Ошибка получения данных: ", result.msg);
      }
    });
    DesignWindow.show();
  }
  ,
  CreateDesignInputFormMainContainer: function (ParamsObject) {
    var me = this;
    var DesignInputFormMainContainer = Ext.create('InputFormDesigner.view.DesignMainContainer',
            // передаю параметры в форму для использования их в методах формы
                    {ParentWindow: ParamsObject.DesignWindow,
                      FormObject: me,
                      ArrayOfObjectTypes: ParamsObject.ArrayOfObjectTypes, //массив типов объектов отчета и их форм ввода
                      ReadOnly: ParamsObject.ReadOnly,
                      modified: false,
                      id_form: ParamsObject.id_form,
                      InputControlPropsArray: ParamsObject.InputControlPropsArray,
                      AdditionalContainerDefaultProps: ParamsObject.InputContainerProps,
                      AdditionalLabelDefaultProps: ParamsObject.labelProps,
                      grid_List: ParamsObject.grid_List});
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
                  var save_modifed = DesignInputFormMainContainer.modified;
                  CurObj.LoadNodeValues(record.raw, record);
                  DesignInputFormMainContainer.modified = save_modifed;
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
      DesignInputFormMainContainer.down('#BtnPaste').enable();
      DesignInputFormMainContainer.down('#BtnCopy').enable();
      DesignInputFormMainContainer.down('#BtnCut').enable();
      DesignInputFormMainContainer.down('#BtnDel').enable();
    } else {
      DesignInputFormMainContainer.down('#BtnPaste').disable();
      DesignInputFormMainContainer.down('#BtnCopy').disable();
      DesignInputFormMainContainer.down('#BtnCut').disable();
      DesignInputFormMainContainer.down('#BtnDel').disable();
    }
    var StructureTree = DesignInputFormMainContainer.down('#StructureTree');
    if (selModel.getSelection( )[0] == StructureTree.getRootNode()) {
      DesignInputFormMainContainer.down('#BtnCopy').disable();
      DesignInputFormMainContainer.down('#BtnCut').disable();
      DesignInputFormMainContainer.down('#BtnDel').disable();
    }
    if (DesignInputFormMainContainer.ClipboardItem == undefined) { //определяю условия отключения кнопки вставить
      DesignInputFormMainContainer.down('#BtnPaste').disable();
    }
  }
});