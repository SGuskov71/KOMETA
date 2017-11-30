Ext.util.Format.thousandSeparator = ' ';
Ext.util.Format.decimalSeparator = '.';
Ext.util.Format.currenyPrecision = 2;
Ext.util.Format.currencySign = '';
Ext.util.Format.currencyAtEnd = false;

Ext.require([
  'Ext.tip.*'
]);
Ext.QuickTips.init();

Ext.define('KOMETA.Operation.Record_operation', {
  New: function (Grid, Operation) {
    Operation.isNew = true;

    this.EditParams(Grid, Operation);

  },
  Edit: function (Grid, Operation) {

    var b = false;
    for (var p in Grid.ObjectInitGrid.key_fld_list) {
      b = true;
    }
    if (b) {
      Operation.isNew = false;
// Определяем ReadOnly
      if (Grid.ObjectInitGrid.readonly_fld != undefined) {
        var sm = Grid.getSelectionModel().getSelection()[0].raw;
        if ((sm == undefined) || (sm == null)) {
          Ext.MessageBox.alert('Не выбрана запись');
        } else {
          Operation.ReadOnly = sm[Grid.ObjectInitGrid.readonly_fld];
          if (Operation.ReadOnly != 1)
            Operation.ReadOnly = 0;
        }
      }
      else {
        Operation.ReadOnly = 0;
      }
      this.EditParams(Grid, Operation);

    }
    else {
      Ext.MessageBox.alert('Ошибка редактирования', 'Не выбрана запись');
    }
  }
  ,
  EditParams: function (Grid, Operation) {
    var InputFormWindow = this.CreateInputFormWindow(Grid, Operation);
    this.InputFormWindow = InputFormWindow;
    this.LoadForms(this.InputFormWindow, function (tabPanel) {
      if (tabPanel != null) {
        Ext.MessageBox.hide();
        InputFormWindow.show();
        InputFormWindow.wasSaveOperation = true;
        if (InputFormWindow.firstcontrol != undefined)
          InputFormWindow.firstcontrol.focus(true);
      } else {
        Ext.MessageBox.hide();
        CloseWindow(this.InputFormWindow);
      }
    });
  },
  CreateInputFormWindow: function (Grid, Operation) {
    var me = this;
    if (Operation.ReadOnly && (Operation.isNew != true)
            /* это условие надо когда добавляем запись при этом readonly не может быть вычислено физически*/
            )
    {
      ButtonSave.hidden = true;
    }
    var icon;
    if (Operation.isNew == true)
      icon = 'ObjectInsert';
    else
      icon = 'ObjectEdit';

    var InitFormObject = {}; //объект начальной инициализации данных формы
    InitFormObject.MandatoryFieldList = {}; //массив полей обязательных к заполнению
    InitFormObject.DefaultValues = {}; //массив значений по умолчанию
    InitFormObject.DialogSLVText = {}; //массив объектов инициализации значений интеррактивных словарей
    InitFormObject.LabelDataFieldList = {};//массив полей для подписей эти поля будут добавлены в хранилище для обработки функций вычислений формы
    InitFormObject.ValueKeyFields = {};// значения ключевых полей
    if (Grid.masterGrid != undefined) {
      var recordMaster = Grid.masterGrid.getSelectionModel().getSelection()[0];
      Ext.each(Grid.masterGrid.ObjectInitGrid.key_fld_list, function (key_fld) {
        InitFormObject.DefaultValues[key_fld] = recordMaster.get(key_fld);
      });
    }
    var record = Grid.getSelectionModel().getSelection()[0];
    if (record) {
      Ext.each(Grid.ObjectInitGrid.key_fld_list, function (key_fld) {
        InitFormObject.ValueKeyFields[key_fld] = record.get(key_fld);
      });
    }
    //ключ массива имя поля данных
    var win = findFirstWindow();
    _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
    _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
    // востанавливаем настройки окон
    WindowTitle = '';
    if (Operation.ReadOnly == 1)
      WindowTitle = 'Просмотр записи';
    else if (Operation.isNew)
      WindowTitle = 'Добавление записи';
    else
      WindowTitle = 'Изменение записи';

    var mainWindow = win.myDesktopApp.desktop.createWindow(
            {
//              id: 'InputFormWindow'+ Grid.ObjectInitGrid.id_object,
//              code: 'InputFormWindow_' + Grid.ObjectInitGrid.id_object,
              modal: true,
              title: WindowTitle,
              EditDataParams: Grid.ObjectInitGrid, //исходные для редактирования параметры
              isNew: Operation.isNew,
              Edt_oper: me,
              DataStore: {}, //объект где хранятся редактируемые данные
              InitFormObject: InitFormObject, //объект начальной инициализации данных формы
              width: _width,
              height: _height,
              maximizable: false,
              closable: false,
              constrainHeader: true,
              HelpContext: 0,
              Grid: Grid,
              iconCls: icon,
              wasSaveOperation: true,
              layout: {type: 'vbox',
                align: 'stretch'},
              listeners: {
                close: function () {

//                  if (wasSaveOperation)
//                  {
                  //  UpdateEditedGrid(EditedGrid);
//                  }
                }
              },
              tools: [
                {type: 'help',
                  qtip: 'Справка',
                  callback: ShowHelp
                },
                {type: 'save',
                  qtip: 'Сохранить',
                  callback: function (w) {
                    me.SaveDataStore2DB(w);
                    wasSaveOperation = true;
                  }
                }

                ,
                {type: 'maximize',
                  qtip: 'Развернуть',
                  callback: function (w) {
                    mainWindow.maximize(true);
                  }
                }

                ,
                {type: 'restore',
                  qtip: 'Востановить',
                  hidden: true,
                  callback: function (w) {
                    mainWindow.restore();
                  }
                },
                {type: 'close',
                  itemId: 'closebtn',
                  qtip: 'Закрыть',
                  callback: function (w) {
                    var tabPanel = w.down('#tabPanel');
                    var ActiveTab = tabPanel.getActiveTab();
                    if (ActiveTab != undefined) {
                      w.Edt_oper.SaveFormData2Store(tabPanel, ActiveTab);
                    }
                    if (w.wasSaveOperation == true) {
                      w.close();
                    }
                    else {
                      var ww = w;
                      Ext.MessageBox.show({
                        title: 'Сохранение',
                        msg: 'В запись были внесены изменения!',
                        buttons: Ext.MessageBox.YESNO,
                        buttonText: {
                          yes: "Сохранить",
                          no: "Не сохранять",
                          cancel: "Отмена"
                        },
                        fn: function (btn) {
                          if (btn == "yes") {
                            me.SaveDataStore2DB(w, function () {
                              if (w.wasSaveOperation == true)
                                w.close();
                            });
                          }
                          else if (btn == "no") {
                            ww.close();
                          }
                        }
                      });

                    }

                  }
                }]
            });
    return mainWindow;
  }
  ,
  PreviewForm: function (Grid, Operation) { //Предпросмотр проектирование формы ввода для подключения в GridFunction

    var id_form;
    if (Operation.param_list)
      id_form = Operation.param_list.id_form;
    if (id_form == undefined) {
      if (Grid == undefined) {
        Ext.MessageBox.alert('Не задани grid');
        return;
      }
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if ((sm == undefined) || (sm == null)) {
        Ext.MessageBox.alert('Не выбрана запись');
        return;
      }
      var id_form = sm['id_form'];
    }


    return this.PreviewInputForm(null, id_form, Operation.HelpContext);
  }
  ,
//загружает массив форм из БД и строит их на таб контрол
//CallBack функция загрузки данных из БД LoadDataStoreFromDB после загрузки форм
  LoadForms: function (EditWindow, callback) {
    var me = this;
    function SetV(FieldName, val) {
      me._SetV(EditWindow, FieldName, val);
    }
    function GetV(FieldName, val) {
      me._GetV(EditWindow, FieldName);
    }
    function SetFieldReadOnly(FieldName, val) {
      me._SetFieldReadOnly(EditWindow, FieldName, val);
    }
    function SetHiddenPanel(FieldName, val) {
      me._SetHiddenPanel(EditWindow, FieldName, val);
    }

    var tabPanel = Ext.create('Ext.tab.Panel', {
      itemId: 'tabPanel',
      flex: 1,
      operation: me,
      autoScroll: true
    });
    tabPanel.addListener('beforetabchange', function (tabPanel, newCard, oldCard, eOpts) {
      if (oldCard != undefined) {
        me.SaveFormData2Store(tabPanel, oldCard);
      }
    });
    tabPanel.addListener('tabchange', function (tabPanel, newCard, oldCard, eOpts) {
      me.LoadFormDataFromStore(tabPanel, newCard);
      if ((newCard.ExpressionBeforeShow != undefined) && (newCard.ExpressionBeforeShow != '')) {
        try {
          var s = newCard.ExpressionBeforeShow;
          eval(s);
        } catch (e) {
          Ext.MessageBox.alert('Ошибка', e.name)
        } finally {
//    alert("готово")
        }

      }
    });
    InputForm_class.GetInputContainerLayouts(function (response, option) {
      var rText = response;
      if ((rText.success === false) && (rText.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', rText.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return null;
      }
      else if (rText.success === false) {
        Ext.MessageBox.alert('Подключение', rText.msg);
        return null;
      }
      var InputContainerLayouts = rText.result; //сюда получили список шаблонов раскладок
      InputForm_class.ReadForms(EditWindow.EditDataParams.id_object, null, function (response, option) {
        var rText = response;
        if ((rText == undefined) || (rText == false)) {
          Ext.MessageBox.alert('Ошибка', 'Ошибка при описании форм ввода');
          return;
        }
        if ((rText.success === false) && (rText.result == 're_connect')) {
          Ext.MessageBox.alert('Подключение', rText.msg);
          window.onbeforeunload = null;
          findFirstWindow().window.location.href = __first_page;
          return;
        }
        else if (rText.success === false) {
          Ext.MessageBox.alert('Подключение', rText.msg);
          return;
        }
        var FormArray = rText.result;
        if ((FormArray == undefined) || (FormArray == false))
        {
          Ext.MessageBox.alert('Ошибка', 'Ошибка при описании форм ввода');
          return;
        }
        var w = 0;
        var h = 0;

        for (var i = 0; i < FormArray.length; i++)
        {
          if ((FormArray[i].form_height != undefined) && (h < FormArray[i].form_height))
            h = FormArray[i].form_height;
          if ((FormArray[i].form_width != undefined) && (w < FormArray[i].form_width))
            w = FormArray[i].form_width;
          if (EditWindow.Grid.masterGrid) {
            var recordMaster = EditWindow.Grid.masterGrid.getSelectionModel().getSelection()[0];
            var JoinsObject = {};
            if (EditWindow.InitFormObject.DefaultValues == undefined)
              EditWindow.InitFormObject.DefaultValues = {};
            if (recordMaster != undefined) {
              Ext.each(EditWindow.Grid.LinkObject.joins, function (joins, index) {
                var MasterKeyValue = recordMaster.raw[joins.master_key_fieldname];
                if (!((MasterKeyValue == null) || (MasterKeyValue == undefined))) {
                  JoinsObject[joins.detail_key_fieldname] = MasterKeyValue;
                  EditWindow.InitFormObject.DefaultValues[joins.detail_key_fieldname] = MasterKeyValue;
                }
              })
            }
          }
          var formPanel = me.ConvertDesignObjectToInputForm(FormArray[i], EditWindow.EditDataParams.ReadOnly, JoinsObject, EditWindow.InitFormObject, InputContainerLayouts);
          tabPanel.add(formPanel);

        }
        EditWindow.add(tabPanel);
        if ((w != 0) && (w < EditWindow.width))
          EditWindow.setWidth(w);
        if ((h != 0) && (h < EditWindow.height))
          EditWindow.setHeight(h);

        me.LoadDataStoreFromDB(EditWindow, function (b) {
          if (b == false) {
            unset(tabPanel);
            tabPanel = null;
          }
          if (callback) {
            callback(tabPanel);
          }
        }
        );
      });
      //return tabPanel;
    });
  }

//загружает данные записи объекта из БД в хранилеще форм ввода
//CallBack функция загрузки данных из хранилища в форму LoadFormDataFromStore после загрузки хранилища данных БД
  ,
  LoadDataStoreFromDB: function (EditWindow, callback) {

    InputForm_class.ReadObject(EditWindow.EditDataParams
            , EditWindow.isNew
            , EditWindow.InitFormObject
            , function (response, option) {
              var rText = response;
              if ((rText.success === false) && (rText.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', rText.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                if (callback) {
                  callback(false);
                }
                ;
              }
              else if (rText.success === false) {
                Ext.MessageBox.alert('Подключение', rText.msg);
                if (callback) {
                  callback(false);
                }
                ;
              } else {
                EditWindow.DataStore = rText.result;
                if (EditWindow.DataStore != undefined) {
                  for (var key in EditWindow.InitFormObject.LabelDataFieldList) { //добавляю поля данных для лэйблов
                    EditWindow.DataStore[key] = EditWindow.InitFormObject.LabelDataFieldList[key];
                  }
//после загрузки данных активируем первую панель
                  var tabPanel = EditWindow.down('#tabPanel');
                  if (tabPanel.items.length > 0) {
                    tabPanel.setActiveTab(0); //активация панели спровоцирует загрузку данных в нее по еванту
                  }
                  if (callback) {
                    callback(true);
                  }
                  ;
                } else {
                  Ext.MessageBox.alert('Ошибка', 'Хранилище данных не проинициализировано!');
                  if (callback) {
                    callback(false);
                  }
                  ;
                }
              }
            });
    //return false

  }
  ,
  LoadContainerDataFromStore: function (Container, DataStore) {
    var me = this;
    var EditWindow = Container.up('window');
    Container.items.each(function (item, idx, len) {
      switch (item.ItemType)
      {
        case 'container':
          me.LoadContainerDataFromStore(item, DataStore);
          break;
        case 'label':
        case 'input_control':
          if (item.xtype == 'pickerfield') {
            me.SetPickerFieldText(item, DataStore);
          } else
            try {
              item.setValue(DataStore[item.name]);
            } catch (e) {
              item.setValue(null);
            }
          if ((EditWindow.firstcontrol == undefined) && (item.ReadOnly != true))
            EditWindow.firstcontrol = item;
          break;
        default:
      }
    });
  }
  ,
  SetPickerFieldText: function (pickerfield, DataStore) {
    if (DataStore['#' + pickerfield.name]) {
      try {
        pickerfield.setValue(DataStore['#' + pickerfield.name]);
      } catch (e) {
        pickerfield.setValue(null);
      }
    }
    else
      pickerfield.setValue(null);
  }
  ,
//загружает значения элементов ввода формы из хранилища формы
  LoadFormDataFromStore: function (tabPanel, newCard) {
// alert('Считать из хранилища');
    var me = this;
    var EditWindow = tabPanel.up('window');
    EditWindow.firstcontrol = null;
    var DataStore = EditWindow.DataStore;
    newCard.items.each(function (item, idx, len) {
      switch (item.ItemType)
      {
        case 'container':
          me.LoadContainerDataFromStore(item, DataStore);
          break;
        case 'label':
        case 'input_control':
          if (item.xtype == 'pickerfield') {
            me.SetPickerFieldText(item, DataStore);
          } else
            try {
              item.setValue(DataStore[item.name]);
            } catch (e) {
              item.setValue(null);
            }
          if ((EditWindow.firstcontrol == undefined) && (item.ReadOnly != true))
            EditWindow.firstcontrol = item;
          break;
        default:
      }
    });
  }
  ,
  SaveContainerData2Store: function (Container, DataStore) {
    var me = this;
    var EditWindow = Container.up('window');
    Container.items.each(function (item, idx, len) {
      switch (item.ItemType)
      {
        case 'container':
          me.SaveContainerData2Store(item, DataStore);
          break;
        case 'input_control':
          if (item.xtype == 'datefield') {
            if (typeof (DataStore[item.name]) == 'object')
              var d = DataStore[item.name];
            else
              var d = new Date(DataStore[item.name]);
            if (Ext.Date.format(d, 'Y-m-d') != Ext.Date.format(item.getValue(), 'Y-m-d')) {
              EditWindow.wasSaveOperation = false;
            }
            DataStore[item.name] = item.getValue();
          }
          else if (item.xtype != 'pickerfield') {
            if (DataStore[item.name] === '')
              var v1 = null
            else
              var v1 = DataStore[item.name];

            if (item.getValue() === '')
              var v2 = null
            else
              var v2 = item.getValue();
            if ((item.xtype == 'checkboxfield') && ((item.getValue() == undefined) || (item.getValue() == false))) {
              v2 = "0";
            }
            if (v1 != v2) {
              EditWindow.wasSaveOperation = false;
            }
            DataStore[item.name] = item.getValue();
          }
          break;
        default:
      }
    });
  }
  ,
//сохраняет в хранилище значения элементов ввода
  SaveFormData2Store: function (tabPanel, oldCard) {
    var me = this;
    var EditWindow = tabPanel.up('window');
    var DataStore = EditWindow.DataStore;
    oldCard.items.each(function (item, idx, len) {
      switch (item.ItemType)
      {
        case 'container':
          me.SaveContainerData2Store(item, DataStore);
          break;
        case 'input_control':
          if (item.xtype == 'datefield') {
            if (typeof (DataStore[item.name]) == 'object')
              var d = DataStore[item.name];
            else
              var d = new Date(DataStore[item.name]);
            if (Ext.Date.format(d, 'Y-m-d') != Ext.Date.format(item.getValue(), 'Y-m-d')) {
              EditWindow.wasSaveOperation = false;
            }
            DataStore[item.name] = item.getValue();
          }
          else if (item.xtype != 'pickerfield') {
            if (DataStore[item.name] == '')
              var v1 = null
            else
              var v1 = DataStore[item.name];

            if (item.getValue() == '')
              var v2 = null
            else
              var v2 = item.getValue();
            if ((item.xtype == 'checkboxfield') && ((item.getValue() == undefined) || (item.getValue() == false))) {
              v2 = "0";
            }
            if (v1 != v2) {
              EditWindow.wasSaveOperation = false;
            }
            DataStore[item.name] = v2;
          }
          break;
        default:
      }
    });
  }
  ,
//проверка правильности заполнения формы
  ValidateForm: function (InputFormWindow) {
    var result = true;
    var tabPanel = InputFormWindow.down('tabpanel');
    function SetV(FieldName, val) {
      me._SetV(InputFormWindow, FieldName, val);
    }
    function GetV(FieldName, val) {
      me._GetV(InputFormWindow, FieldName);
    }
    function SetFieldReadOnly(FieldName, val) {
      me._SetFieldReadOnly(InputFormWindow, FieldName, val);
    }
    function SetHiddenPanel(FieldName, val) {
      me._SetHiddenPanel(InputFormWindow, FieldName, val);
    }

    if (tabPanel) {
      tabPanel.items.each(function (tab) {
        if ((tab.form_validator != undefined) && (trim(tab.form_validator) != '')) {
          result = eval(tab.form_validator);
          if (result != true) {
            Ext.Msg.show({title: 'Не выполнена проверка данных',
              msg: result,
              buttons: Ext.Msg.OK,
              icon: Ext.Msg.WARNING});
            return false;
          }
        }
      });
    }
    return result;
  }
  ,
// проверка на наличие не заполненных обязательных к заполнению полей
  checkNotNullMandatoryFields: function (InputFormWindow) {
    var DataStore = InputFormWindow.DataStore;
    if ((InputFormWindow.InitFormObject != undefined) &&
            (InputFormWindow.InitFormObject.MandatoryFieldList != undefined))
    {
      for (var key in InputFormWindow.InitFormObject.MandatoryFieldList) {
        var val = DataStore[key];
        if ((val == undefined) || (val == null) || (trim(val) == '')) {
          Ext.Msg.show({title: 'Недостаточно данных',
            msg: 'Не заполнено обязательное значение поля ' + InputFormWindow.InitFormObject.MandatoryFieldList[key],
            buttons: Ext.Msg.OK,
            icon: Ext.Msg.WARNING});
          return false;
        }
      }
    }
    return true;
  }
  ,
//сохраняет данные из хранилища формы в БД
  SaveDataStore2DB: function (InputFormWindow, callback) {
    var me = this;
    var tabPanel = InputFormWindow.down('#tabPanel');
    var ActiveTab = tabPanel.getActiveTab();
    if (ActiveTab != undefined) {
      me.SaveFormData2Store(tabPanel, ActiveTab);
      if ((me.ValidateForm(InputFormWindow) == true) && (me.checkNotNullMandatoryFields(InputFormWindow) == true)) {
        var DataStore2Save = {};//надо убрать из хранилища поля лэйблов
        for (var key in InputFormWindow.DataStore) {
          if (InputFormWindow.InitFormObject.LabelDataFieldList[key] == undefined)
            DataStore2Save[key] = InputFormWindow.DataStore[key];
        }
        InputForm_class.SaveRecord(InputFormWindow.EditDataParams
                , DataStore2Save
                , function (response, option) {
                  var rText = response;
                  if ((rText.success === false) && (rText.result == 're_connect')) {
                    Ext.MessageBox.alert('Подключение', rText.msg);
                    window.onbeforeunload = null;
                    findFirstWindow().window.location.href = __first_page;
                    return false;
                  }
                  else if (rText.success === false) {
                    Ext.MessageBox.alert('Подключение', rText.msg);
                    return false;
                  } else if (rText.success == true) {
                    for (key in InputFormWindow.EditDataParams.key_fld_list) {
                      InputFormWindow.DataStore[InputFormWindow.EditDataParams.key_fld_list[key]] = rText.result[InputFormWindow.EditDataParams.key_fld_list[key]];//??
                      InputFormWindow.InitFormObject.ValueKeyFields[InputFormWindow.EditDataParams.key_fld_list[key]] = rText.result[InputFormWindow.EditDataParams.key_fld_list[key]];
                    }
                    SimpleGrid_class.GetIDRecordValue(rText.result,
                            InputFormWindow.EditDataParams.key_fld_list,
                            function (IDRecordValue) {
                              InputFormWindow.Grid.ReloadGrid(IDRecordValue);
                            });
                    //Ext.MessageBox.alert("Результат выполнения ", rText.msg);
                    InputFormWindow.wasSaveOperation = true;
                    if (callback) {
                      callback(tabPanel);
                    }

                  }
                });
      }
      else {
        return false;
      }
    }
  }
  ,
  defineSLVCondition: function (control) {//формирует условие выбора словарного значения в диалоге
    var me = this;
    //var InputFormWindow = Ext.getCmp('InputFormWindow');
    var InputFormWindow = control.up('window');
    var ExtFilterWhereCond = control.ExtFilterWhereCond;
    if ((ExtFilterWhereCond != undefined) && (trim(ExtFilterWhereCond) != '')) {
      var tabPanel = control.up('tabpanel');
      if (tabPanel) {  //сброс данных из формы в хранилище
        var newCard = tabPanel.getActiveTab();
        if (newCard)
          me.SaveFormData2Store(tabPanel, newCard);
      }
      var DataStore = InputFormWindow.DataStore;
      for (var key in DataStore) {
        var val = DataStore[key];
        if ((val != undefined) && (trim(val + '') != '')) {
          var s = ':' + key + ':';
          ExtFilterWhereCond = ExtFilterWhereCond.replace(new RegExp(s, 'g'), val);
        }
        else {
          if (ExtFilterWhereCond.indexOf(':' + key + ':') >= 0) {
//значение не определено значение поля используемого в фильтре
            return '';
          }
        }
      }
      return ExtFilterWhereCond;
    } else
      return '';
  }
  ,
  PreviewInputForm: function (code, id_form, _HelpContext) {
    var me = this;
    Ext.MessageBox.wait({
      msg: 'Выполняется загрузка формы редактирования, ждите... ',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });

    InputForm_class.GetInputContainerLayouts(function (response, option) {
      var rText = response;
      if ((rText.success === false) && (rText.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', rText.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return null;
      }
      else if (rText.success === false) {
        Ext.MessageBox.alert('Подключение', rText.msg);
        return null;
      }
      var InputContainerLayouts = rText.result; //сюда получили список шаблонов раскладок
      InputForm_class.LoadInputForm(code, id_form, function (response, options) {
        Ext.MessageBox.hide();
        var result = response;
        if ((result.success === false) && (result.result == 're_connect')) {
          Ext.MessageBox.hide();
          Ext.MessageBox.alert('Подключение', result.msg);
          findFirstWindow().window.location.href = _URLProjectRoot + 'index.php';
          return;
        }
        if (result.success) {
          var InputFormTemplate = result.result.InputFormTemplate;
          var InputForm = me.ConvertDesignObjectToInputForm(InputFormTemplate, false, null, null, InputContainerLayouts);


          win = findFirstWindow();
          _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
          _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
          // востанавливаем настройки окон


          var PreviewWindow = win.myDesktopApp.desktop.createWindow(//Ext.create("Ext.Window", 
                  {
                    title: 'Предпросмотр формы ввода', //InputFormTemplate.Description,
                    code: 'PreviewWindow_' + code,
                    width: _width,
                    height: _height,
                    closable: true,
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
                    constrainHeader: true,
                    modal: true
                  });
          var tabPanel = Ext.create('Ext.tab.Panel', {
            id: 'tabPanel',
            name: 'tabPanel',
            flex: 1,
            autoScroll: true
          });
          PreviewWindow.add(tabPanel);
          tabPanel.add(InputForm);
          var h = 0;
          var w = 0;

          if ((InputFormTemplate.form_height != undefined) && (h < InputFormTemplate.form_height))
            h = InputFormTemplate.form_height;
          if ((InputFormTemplate.form_width != undefined) && (w < InputFormTemplate.form_width))
            w = InputFormTemplate.form_width;

          if ((w != 0) && (w < PreviewWindow.width))
            PreviewWindow.width = w;
          if ((h != 0) && (h < PreviewWindow.height))
            PreviewWindow.height = h;

          PreviewWindow.doLayout();
          PreviewWindow.show();
        }
      });
    });
  }
  ,
  GetInputContainerLayoutsItem: function (InputContainerLayouts, LayoutCode) {
    var result = null;
    Ext.each(InputContainerLayouts, function (item) {
      if (item['code'] == LayoutCode) {
        result = item;
        return true;
      }
    });
    return result;
  }
  ,
  NewContainer: function (ContainerObject, ReadOnly, JoinsObject, InitFormObject, InputContainerLayouts) {
    var me = this;
    if ((ContainerObject != undefined) &&
            (ContainerObject.children != undefined) && (ContainerObject.children.length > 0)) {
      var ShowHeader = ((ContainerObject.Caption != undefined) && (trim(ContainerObject.Caption) != ''));
      var ConfigObject = {
        ItemType: ContainerObject.ItemType,
        title: ContainerObject.Caption,
        border: ContainerObject.ShowBorder,
        layout: ContainerObject.Layout,
        collapsible: true,
        header: ShowHeader
      };
      if (ContainerObject.ContainerProps != undefined) {//дополнительные свойства
        for (var p in ContainerObject.ContainerProps) {
          ConfigObject[p] = ContainerObject.ContainerProps[p];
        }
      }
      if (ContainerObject.InputContainerLayouts != undefined) {//указан шаблон раскладки
        var InputContainerLayoutsItem = me.GetInputContainerLayoutsItem(InputContainerLayouts, ContainerObject.InputContainerLayouts);
        if (InputContainerLayoutsItem) {
          for (var p in InputContainerLayoutsItem.template) {
            ConfigObject[p] = InputContainerLayoutsItem.template[p];
          }
        }
      }

      //var Container = Ext.widget({xtype: 'fieldset'}, ConfigObject);
      //var Container = Ext.create('Ext.form.Panel', ConfigObject);
      var Container = Ext.create('Ext.form.FieldSet', ConfigObject);
      if (ContainerObject.ContainerProps != undefined) {//дополнительные свойства второй раз умышленно т.к. незарегистрированные свойства при конфигурировании не добавятся
        for (var p in ContainerObject.ContainerProps) {
          Container[p] = ContainerObject.ContainerProps[p];
        }
      }

      Ext.Array.each(ContainerObject.children, function (ChildObject) {
        switch (ChildObject.ItemType) {
          case 'label':
            me.AddLabel(Container, ChildObject, InitFormObject)
            break
          case 'container':
            {
              var cmp = me.NewContainer(ChildObject, ReadOnly, JoinsObject, InitFormObject, InputContainerLayouts);
              if (cmp != undefined) {
                Container.add(cmp);
              }
            }
            break
          case 'input_control':
            me.AddChildObject(Container, ChildObject, ReadOnly, JoinsObject, InitFormObject);
            break
        }
      });
      return Container;
    }
  }
  ,
  AddLabel: function (Container, ChildObject, InitFormObject) {
    var me = this;

    if (InitFormObject != undefined) {
      if ((ChildObject.DataField != undefined) && (trim(ChildObject.DataField) != '')) {
//создаю список полей данных для лэйблов для добавления в хранилище
        InitFormObject.LabelDataFieldList[ChildObject.DataField] = ChildObject.DefaultValue;
      }
    }

    var ConfigObject = {
      ItemType: ChildObject.ItemType,
      value: ChildObject.DefaultValue,
      name: ChildObject.DataField,
    }

    if (ChildObject.LabelProps != undefined) {//дополнительные свойства
      for (var p in ChildObject.LabelProps)
        if ((ChildObject.LabelProps[p] != undefined) && (ChildObject.LabelProps[p] != '')) {
          ConfigObject[p] = ChildObject.LabelProps[p];
        }
    }

    var widget = Ext.widget('displayfield', ConfigObject); //создаю компонент по настройкам

    if (ChildObject.LabelProps != undefined) {//дополнительные свойства второй раз умышленно т.к. незарегистрированные свойства при конфигурировании не добавятся
      for (var p in ChildObject.LabelProps)
        if ((ChildObject.LabelProps[p] != undefined) && (ChildObject.LabelProps[p] != '')) {
          widget[p] = ChildObject.LabelProps[p];
        }
    }

    Container.add(widget);
  }
  ,
  AddChildObject: function (Container, ChildObject, ReadOnly, JoinsObject, InitFormObject) {
    var me = this;
    function SetV(FieldName, val) {
      var InputFormWindow = Container.up('window');
      me._SetV(InputFormWindow, FieldName, val);
    }
    function GetV(FieldName, val) {
      var InputFormWindow = Container.up('window');
      me._GetV(InputFormWindow, FieldName);
    }
    function SetFieldReadOnly(FieldName, val) {
      var InputFormWindow = Container.up('window');
      me._SetFieldReadOnly(InputFormWindow, FieldName, val);
    }
    function SetHiddenPanel(FieldName, val) {
      var InputFormWindow = Container.up('window');
      me._SetHiddenPanel(InputFormWindow, FieldName, val);
    }
    if (ChildObject.ReadOnly == "0") {
      ChildObject.ReadOnly = false
    }
    else if (ChildObject.ReadOnly == "1") {
      ChildObject.ReadOnly = true
    }

    if ((ChildObject.SLVObject4Display == undefined) && (ChildObject.SLVObject != undefined))
      ChildObject.SLVObject4Display = ChildObject.SLVObject;
    if ((ChildObject.ReadOnly == false) && (JoinsObject != undefined)) {
      if ((JoinsObject[ChildObject.DataField] != undefined) && (JoinsObject[ChildObject.DataField] != '')) {
        ChildObject.ReadOnly = true;
      }
    }

    var ConfigObject = {
//   xtype: ChildObject.InputType,
      ItemType: ChildObject.ItemType,
      fieldLabel: ChildObject.Caption,
      name: ChildObject.DataField,
      hidden: ChildObject.hidden,
      SLVObject: ChildObject.SLVObject,
      SLVObjectDescr: ChildObject.SLVObjectDescr,
      SLVObject4Display: ChildObject.SLVObject4Display,
      ExtFilterWhereCond: ChildObject.ExtFilterWhereCond,
      notNull: ChildObject.Mandatory,
      hideLabel: !ChildObject.ShowLabel,
      labelWidth: ChildObject.labelWidth,
      width: ChildObject.ControlWidth,
      validateCondition: ChildObject.ValidateCondition,
      validateConditionMessage: ChildObject.ValidateConditionMessage,
      submitValue: false,
      ReadOnly: ChildObject.ReadOnly,
      Mandatory: ChildObject.Mandatory,
      DefaultValue: ChildObject.DefaultValue,
      OnBlurScript: ChildObject.OnBlurScript
    };
    if (ConfigObject.notNull == true) {
      ConfigObject.afterLabelTextTpl = '<font color=red>*</font>';
    }
    if (ChildObject.ControlProps != undefined) {//дополнительные свойства
      for (var p in ChildObject.ControlProps)
        if ((ChildObject.ControlProps[p] != undefined) && (ChildObject.ControlProps[p] != '')) {
          ConfigObject[p] = ChildObject.ControlProps[p];
        }
    }

    ConfigObject.ReadOnly = ConfigObject.ReadOnly || ReadOnly;
    if (ConfigObject.ReadOnly == true) {
      ConfigObject.readOnly = ConfigObject.ReadOnly;
      ConfigObject.cls = 'x-item-disabled-readonly';
    }

    switch (ChildObject.InputType) //свойства специфичные для определенных типов
    {
      case 'datefield': // Дата
        break;
      case 'combobox': // Простой справочник (ComboBox)
        var ComboStore = Ext.create('Ext.data.ArrayStore', {
          fields: ['ID', 'name'],
          autoLoad: false,
          editable: false
        });
        if (ChildObject.data != undefined)
          ComboStore.loadData(ChildObject.data);
        ConfigObject.valueField = 'ID';
        ConfigObject.displayField = 'name';
        ConfigObject.lastQuery = '';
        ConfigObject.mode = 'local';
        ConfigObject.queryMode = 'local';
        ConfigObject.forceSelection = true;
        ConfigObject.triggerAction = 'all';
        ConfigObject.autoSelect = true;
        ConfigObject.editable = false;
        ConfigObject.store = ComboStore;
        ConfigObject.enableKeyEvents = true;
        ConfigObject.ClearFunction = function () {
          this.clearValue();
          this.applyEmptyText();
        }
        ConfigObject.listeners = {
          el: {//обращаюсь не эвантам контрола а к обработчику событий DOM модели браузера и от него получаю эвант contextmenu
            //из эванта браузера получаю элемент дом модели а потом преобразую его в объект компонентной модели ExtJS
            contextmenu: function (e, component, obj)//контектное меню данного контрола
            {
              if (Ext.getDom(component).name != null)
              {
                e.preventDefault();
                var target = e.getTarget();
                var cmp = findComponentByElement(target);
                var contextMenu = new Ext.menu.Menu({
                  items: [{
                      text: 'Очистить',
                      handler: function (item) {
                        var obj = item.cmp;
                        obj.ClearFunction();
                      },
                      cmp: cmp
                    }]
                });
                contextMenu.showAt(e.getXY());
              }
            }
          },
        };
        break;
      case 'pickerfield': // Сложный справочник (Paginator)
        ConfigObject.triggerCls = 'x-form-search-trigger';
        ConfigObject.autoSelect = true;
        ConfigObject.editable = false;
        ConfigObject.typeAhead = true;
        ConfigObject.enableKeyEvents = true;
        ConfigObject.validateCondition = null;
        ConfigObject.ClearFunction = function () {
          this.setValue(null);
          this.applyEmptyText();
          SetV(this.name, null); //в store значение
          SetV('#' + this.name, null); //в store описание
        }
        ConfigObject.listeners = {
          el: {
            contextmenu: function (e, component, obj)//контектное меню данного контрола
            {
              if (Ext.getDom(component).name != null)
              {
                e.preventDefault();
                var target = e.getTarget();
                var cmp = findComponentByElement(target);
                var contextMenu = new Ext.menu.Menu({
                  items: [{
                      text: 'Очистить',
                      handler: function (item) {
                        var obj = item.cmp;
                        obj.ClearFunction();
                      },
                      cmp: cmp
                    }]
                });
                contextMenu.showAt(e.getXY());
              }
            }
          },
        };
        ConfigObject.onTriggerClick = function (evnt) {
          var edtwin = this.up('window');
          var tabPanel = edtwin.down('tabpanel');
          if (this.ReadOnly != true)
          {
            var wm = this;
            var tabPanel = wm.up('tabpanel');
            var ExtFilterWhereCond = tabPanel.operation.defineSLVCondition(this);
            var win = SelectValSlv({sysname: wm.SLVObject, ExtFilterWhereCond: ExtFilterWhereCond, object_Caption: 'Выбор объекта ' + wm.SLVObjectDescr, HelpContext: ''});
            win.addListener('ValSlvSelected', function (context, SelID, SelDescr)
            {
              wm.setValue(SelDescr); //на экран описание
              edtwin.wasSaveOperation = false;
              //SetV(wm.name, SelID); //в store значение
              for (var prop in SelID)
              {
                SetV(wm.name, SelID[prop]);
              }
              ;
              SetV('#' + wm.name, SelDescr); //в store описание
              if ((wm.OnBlurScript != undefined) && (trim(wm.OnBlurScript) != '')) {
                var tabPanel = wm.up('tabpanel');
                if (tabPanel) {
                  var newCard = tabPanel.getActiveTab();
                  if (newCard)
                    SaveFormData2Store(tabPanel, newCard);
                  var b = eval(wm.OnBlurScript);
                  if (b == true) {
                    LoadFormDataFromStore(tabPanel, newCard);
                  }
                }
//                edtwin.focus();
//                tabPanel.focus();
//                wm.focus();
              }
            });
          }
        };
        break;
      case 'checkboxfield': // Флаг - (CheckBox)
        break;
      case 'fileuploadfield': // файл
        break;
    }

    if (InitFormObject != undefined) {
      if (ChildObject.Mandatory == true) {//создаю список обязательных к заполнению полей
        InitFormObject.MandatoryFieldList[ChildObject.DataField] = ChildObject.Caption;
      }
      if ((ChildObject.InputType == 'pickerfield') &&
              (ChildObject.SLVObject4Display != undefined) && (trim(ChildObject.SLVObject4Display) != '')) {
//создаю список словарных объектов для полей ввода словаря из диалога для заполнения начальных значений
        InitFormObject.DialogSLVText[ChildObject.DataField] = ChildObject.SLVObject4Display;
      }
      if ((ChildObject.DefaultValue != undefined) && (trim(ChildObject.DefaultValue) != '')) {
//создаю список значений по умолчанию
        InitFormObject.DefaultValues[ChildObject.DataField] = ChildObject.DefaultValue;
      }
    }

    var widget = Ext.widget(ChildObject.InputType, ConfigObject); //создаю компонент по настройкам

    if (ChildObject.ControlProps != undefined) {//дополнительные свойства второй раз умышленно т.к. незарегистрированные свойства при конфигурировании не добавятся
      for (var p in ChildObject.ControlProps)
        if ((ChildObject.ControlProps[p] != undefined) && (ChildObject.ControlProps[p] != '')) {
          widget[p] = ChildObject.ControlProps[p];
        }
    }

    widget.addListener("blur", function (me) {
      if ((me.OnBlurScript != undefined) && (trim(me.OnBlurScript) != '')) {
        var tabPanel = me.up('tabpanel');
        var edtwin = me.up('window');
        if (tabPanel) {
          var newCard = tabPanel.getActiveTab();
          if (newCard)
            SaveFormData2Store(tabPanel, newCard);
          var b = eval(me.OnBlurScript);
          if (b == true) {
            LoadFormDataFromStore(tabPanel, newCard);
          }
        }
      }
    });
    if (trim(widget.validateCondition) != '') {
      widget.validateOnChange = true;
      widget.validator = function (v) {
        var t = this;
        if (!((t.value == undefined) || (t.value == "") ||
                (this.validateCondition == undefined) || (this.validateCondition == ""))) {
          var edtwin = t.up('window');
          var tabPanel = edtwin.down('tabpanel');
          var b = eval(this.validateCondition);
          if (b) {
            t.focus();
            return this.validateConditionMessage;
          }
        }
        return true;
      };
    }

    Container.add(widget);
  }
  ,
  ConvertDesignObjectToInputForm: function (DesignObject, ReadOnly, JoinsObject, InitFormObject, InputContainerLayouts) {
    var me = this;
    if (DesignObject != undefined) {
      var formPanelProps = {title: DesignObject.Description, autoScroll: true};
      if (DesignObject.Layout != undefined) {
        formPanelProps.layout = DesignObject.Layout;
      }
      if (DesignObject.ExpressionBeforeShow != undefined) {
        formPanelProps.ExpressionBeforeShow = DesignObject.ExpressionBeforeShow;
      }
      if (DesignObject.form_validator != undefined) {
        formPanelProps.form_validator = DesignObject.form_validator;
      }
      if (DesignObject.FormLayouts != undefined) {//указан шаблон раскладки
        var FormLayoutsItem = me.GetInputContainerLayoutsItem(InputContainerLayouts, DesignObject.FormLayouts);
        if (FormLayoutsItem) {
          for (var p in FormLayoutsItem.template) {
            formPanelProps[p] = FormLayoutsItem.template[p];
          }
        }
      }

      var formPanel = Ext.create('Ext.form.Panel', formPanelProps);
      if (DesignObject.ContainerProps != undefined) {//дополнительные свойства второй раз умышленно т.к. незарегистрированные свойства при конфигурировании не добавятся
        for (var p in DesignObject.ContainerProps) {
          formPanel[p] = DesignObject.ContainerProps[p];
        }
      }

      Ext.Array.each(DesignObject.children, function (ChildObject) {
        switch (ChildObject.ItemType) {
          case 'label':
            me.AddLabel(formPanel, ChildObject, InitFormObject)
            break
          case 'container':
            {
              var cmp = me.NewContainer(ChildObject, ReadOnly, JoinsObject, InitFormObject, InputContainerLayouts);
              if (cmp != undefined) {
                formPanel.add(cmp);
              }
            }
            break
          case 'input_control':
            me.AddChildObject(formPanel, ChildObject, ReadOnly, JoinsObject, InitFormObject);
            break
        }
      });
      return formPanel;
    }
  }
  ,
  _SetHiddenPanel: function (InputFormWindow, Name, val) {
    var elems = Ext.ComponentQuery.query('[name=' + Name + ']', InputFormWindow);
    for (var i = 0; i < elems.length; i++)
    {
      var el = elems[i];
      if (val == true)
        el.hide();
      else
        el.show();
    }
  }
  ,
  _SetFieldReadOnly: function (InputFormWindow, Name, val) {
    var elems = Ext.ComponentQuery.query('[name=' + Name + ']', InputFormWindow);
    for (var i = 0; i < elems.length; i++)
    {
      var el = elems[i];
      el.setReadOnly(val);
    }
  }
  ,
  _GetV: function (InputFormWindow, FieldName) { //функция считывания значения поля FieldName из хранилища данных формы для обработки в скрипте ValidateForm
    if (InputFormWindow) {
      var res = InputFormWindow.DataStore[FieldName];
      if (res == '')
        res = undefined;
      return res;
    } else
      return undefined;
  }
  ,
  _SetV: function (InputFormWindow, FieldName, val) { //функция записи значения поля FieldName в хранилище данных формы для обработки в скрипте ValidateForm
    if (InputFormWindow) {
      InputFormWindow.DataStore[FieldName] = val;
      return true;
    }
  }
  ,
  Duplicate: function (Grid, Operation) {
    // запрашиваю имя кодового поля и его текущее значение для формирования вопроса на новое значение
    var recordMaster = Grid.getSelectionModel().getSelection()[0];
    var cur_value = recordMaster.get(Grid.ObjectInitGrid.code_fld);// текущее значение кодового поля
    if ((cur_value == '') || (cur_value == undefined)) {
      Ext.MessageBox.alert("Не выбрана запись ", '');
    }
    // определение имени кодового поля
    var code_fld_name = '';
    Grid.ObjectInitGrid.field_list.forEach(function (item) {
      if (item.fieldname == Grid.ObjectInitGrid.code_fld)
        code_fld_name = item.short_name;

    })

    Ext.MessageBox.prompt(
            'Создание копии информационного объекта',
            'Ввод нового значения для поля \'' + code_fld_name + '\':',
            function (btn, text) {
              if (btn === 'ok') {
                Ext.MessageBox.wait({
                  msg: 'Выполняется дублирование, ждите...',
                  width: 300,
                  wait: true,
                  waitConfig: {interval: 100}
                });
                ValueKeyFields = {};

                Ext.each(Grid.ObjectInitGrid.key_fld_list, function (key_fld) {
                  ValueKeyFields[key_fld] = recordMaster.get(key_fld);
                });
                Common_class.DuplicateRecord(Grid.ObjectInitGrid.id_object, ValueKeyFields, text, function (response, options) {
                  Ext.MessageBox.hide();
                  var result = response;
                  if ((result.success === false) && (result.result == 're_connect')) {
                    Ext.MessageBox.alert(result.msg);
                    window.onbeforeunload = null;
                    findFirstWindow().window.location.href = __first_page;
                    return;
                  }
                  if (result.success === false)
                    Ext.MessageBox.alert("Ошибка дублирования: ", result.msg);
                  else {
                    Grid.ReloadGrid();
                    Ext.MessageBox.alert("Дублирование прошло успешно: ", result.msg);

                  }
                });
              }
            },
            this,
            false,
            cur_value
            );

  }
  ,
  Delete: function (Grid, Operation) {
    this._DeleteObject(Grid, false);
  }
  ,
  DeleteCascade: function (Grid, Operation) {
    this._DeleteObject(Grid, true);
  }
  ,
  _DeleteObject: function (Grid, cascade) { //удаление объекта вызов из грида
    var title = 'Удалить запись?';
    if (cascade)
      title = 'Удалить запись и все связаннные объекты?'
    var win = Ext.Msg.show({title: 'Удаление объекта',
      msg: title,
      buttons: Ext.Msg.YESNO,
      closable: false,
      fn: function (btn) {
        var request = new XMLHttpRequest();
        var data = new FormData();
        if ((cascade == undefined) || ((cascade != true) && (cascade != 'true')))
          cascade = 'false';
        switch (btn) {
          case "yes":
          {
            var recordMaster = Grid.getSelectionModel().getSelection()[0];
            var ValueKeyFields = {};
            Ext.each(Grid.ObjectInitGrid.key_fld_list, function (key_fld) {
              ValueKeyFields[key_fld] = recordMaster.get(key_fld);
            });
            Common_class.DeleteRecord(Grid.ObjectInitGrid.id_object, ValueKeyFields, cascade, function (response) {
              Ext.MessageBox.hide();
              var result = response;
              if ((result.success === false) && (result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', result.msg);
                findFirstWindow().window.location.href = _URLProjectRoot + 'index.php';
                return;
              }
              if (result.success) {
                if ((result.msg != undefined) && (result.msg != ''))
                  Ext.MessageBox.alert("Результат выполнения ", result.msg);
                Grid.ReloadGrid();
              } else {
                if ((result.msg != undefined) && (result.msg != ''))
                  Ext.MessageBox.alert("Ошибка удаления: ", result.msg);
              }
            }
            );
            break;
          }
        } // switch
      } // fn
    }); // show

    return win;
  }
  ,
  ExportToHTML: function (Grid, Operation) {
    this.Save2File(Grid.ObjectInitGrid.id_object, Grid.ObjectInitGrid.FilterWhereCond, 'html', Grid);
  }
  ,
  ExportToRTF: function (Grid, Operation) {
    this.Save2File(Grid.ObjectInitGrid.id_object, Grid.ObjectInitGrid.FilterWhereCond, 'rtf', Grid);
  }
  ,
  ExportToXLS: function (Grid, Operation) {
    this.Save2File(Grid.ObjectInitGrid.id_object, Grid.ObjectInitGrid.FilterWhereCond, 'xls', Grid);
  }
  ,
  Save2File: function (id_object, ext_filter, type_file, _grid)
  {

    var SourceArray = [];

    var aGenScript = [];

    aGenScript['html'] = 'GenerateHTMTableFile';
    aGenScript['rtf'] = 'GenerateRTFTableFile';
    aGenScript['xls'] = 'GenerateExcelFile';

    var cols = _grid.view.getGridColumns();
//  _grid.columns.forEach(function(col) {
    cols.forEach(function (col) {
      re = /<br>/gi;
      SourceArray.push([col.dataIndex, col.text.replace(re, ' '), !col.hidden]);
    });
    var ScriptName = _URLProjectRoot + 'sys/CheckList.js';
    Ext.Loader.loadScript({url: ScriptName
      , onLoad: function () {
        var w = new CheckListWindow({ArrayData: SourceArray, _Caption: 'Выбор полей для выгрузки', HelpContext: 'kometa_exp_objects'});
        w.addListener('OK_result', function (context, resultObj) {
          SourceArray.length = 0;
          var _GridSettings = new Object();
          resultObj.forEach(function (col) {
            _GridSettings[col[0]] = new Object();
            _GridSettings[col[0]].visible = col[2];
            _GridSettings[col[0]].caption = col[1];
          });
          resultObj.length = 0;
          var GridSettingsJSONStr = Ext.JSON.encode(_GridSettings);
          //Ext.destroy(_GridSettings);

          if (type_file == 'xls') {
            ExportToFile_class.GenerateExcelFile(id_object, _GridSettings, ext_filter, function (response) {
              var JSON_Result = response;
              if ((JSON_Result.success === false) && (JSON_Result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', JSON_Result.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                return;
              }
              else if (JSON_Result.success === false)
                Ext.MessageBox.alert('Подключение', JSON_Result.msg);
              else {
                window.open(JSON_Result.result, '_self');
              }

            });
          }
          else if (type_file == 'rtf') {
            ExportToFile_class.GenerateRTFTableFile(id_object, _GridSettings, ext_filter, function (response) {
              var JSON_Result = response;
              if ((JSON_Result.success === false) && (JSON_Result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', JSON_Result.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                return;
              }
              else if (JSON_Result.success === false)
                Ext.MessageBox.alert('Подключение', JSON_Result.msg);
              else {
                window.open(JSON_Result.result, '_self');
              }

            });
          }
          else if (type_file == 'html') {
            ExportToFile_class.GenerateHTMTableFile(id_object, _GridSettings, ext_filter, function (response) {
              var JSON_Result = response;
              if ((JSON_Result.success === false) && (JSON_Result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение', JSON_Result.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                return;
              }
              else if (JSON_Result.success === false)
                Ext.MessageBox.alert('Подключение', JSON_Result.msg);
              else {
                win = findFirstWindow();
                _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
                _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
                buildW_desktop(JSON_Result.result, 'Результат выгрузки в HTML файл', true, _width, _height, false, 'kometa_result_html_export');
              }
            });
          }
        });
        win = findFirstWindow().window;
        w.width = Math.round(win.document.body.clientWidth / 6 * 5);
        w.height = Math.round(win.document.body.clientHeight / 6 * 5);
        w.show();
      }, onError: function () {
        Ext.MessageBox.alert('Ошибка', "Ошибка загрузки файла: " + ScriptName);
      }});
  }


}

);



