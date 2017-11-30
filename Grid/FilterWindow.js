function isGUID(objGuid) {
  var str = (objGuid);
  var reGUID = /^(\{){0,1}[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}(\}){0,1}$/;
  var result;
  if (reGUID.test(str)) {
    result = true;
  }
  else {
    result = false;
  }
  return result;
}

function isCurrency(objCurrency) {
  var str = (objCurrency);
  var reCurrency = /^(\d+)([,.]\d{0,2})?$/;
  var result;
  if (reCurrency.test(str)) {
    result = true;
  }
  else {
    result = false;
  }
  return result;
}

function ShowWindow(FilteredGrid) {
  var btnDeleteFilter = Ext.create("Ext.button.Button", {
    tooltip: 'Удалить фильтр',
    iconCls: 'DeleteFilter',
    tooltipType: 'title',
    handler: function () {
      Ext.Msg.show({title: 'Удаление фильтра',
        msg: 'Удалить фильтр',
        buttons: Ext.Msg.YESNO,
        closable: false,
        fn: function (btn) {
          switch (btn) {
            case "yes":
              GridFilter_class.DeleteFilter(btn.FilterWindow.ObjectFilter.id_ObjectFilter, function (response) {
                if (response != undefined) {
                  if ((response.success === false) && (response.result == 're_connect')) {
                    alert(response.msg);
                    window.onbeforeunload = null;
                    findFirstWindow().window.location.href = __first_page;
                    return false;
                  } else
                  if (response.success == true) {
                    if (response.result === '1') {
                      btn.FilterWindow.CloseFilterWindow();
                      Ext.Msg.alert('Удаление фильтра', 'Фильтр удален');
                      FilteredGrid.ObjectInitGrid.FilterWhereCond = null;
                      if (FilteredGrid.FilterCombo !== undefined) {
                        var combo_store = FilteredGrid.FilterCombo.getStore();
                        var rec = combo_store.getById(FilteredGrid.FilterCombo.getValue());
                        if (rec !== undefined) {
                          combo_store.remove(rec);
                        }
                        FilteredGrid.FilterCombo.setValue('-1');
                      }
                      FilteredGrid.getStore().loadPage(1);
                    }
                    return true;
                  } else {
                    Ext.Msg.alert('Ошибка получения условий фильтра: ' + response.msg);
                    return false;
                  }
                } else {
                  Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения условий фильтра');
                  return false;
                }
              });
              break;
          } // switch
        } // fn
      }); // show()
    }
  });
  var win = findFirstWindow(),
          _width = Math.round(win.window.document.body.clientWidth / 7 * 5),
          _height = Math.round(win.window.document.body.clientHeight / 7 * 5);
  var w = win.myDesktopApp.desktop.checkExist('ObjectGroupWindow');
  if (w) {
    win.myDesktopApp.desktop.restoreWindow(w);
    return w;
  }
  var FilterWindow = win.myDesktopApp.desktop.createWindow({
    // FilterWindow = Ext.create("Ext.Window", {
    title: 'Фильтр',
    code: 'FilterWindow',
    width: _width,
    height: _height,
    closable: true,
    autoScroll: true,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    HelpContext: 'kometa_filter',
    layout: {
      type: 'card'
    },
    constrainHeader: true,
    modal: true,
    dockedItems: [
      {
        xtype: 'container',
        dock: 'bottom',
        layout: {
          align: 'stretch',
          pack: 'end',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            disabled: true,
            minWidth: 100,
            id: 'BtnFilterOK',
            text: 'OK',
            handler: function (btn) {
              var win = btn.up('window');
              GetFilterCaption(FilteredGrid, win);
            }
          },
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Отмена',
            handler: function (btn) {
              var win = btn.up('window');
              win.CloseFilterWindow();
            }
          }
        ]
      },
      {
        xtype: 'container',
//        dock: 'bottom',
        dock: 'top',
        layout: {
          align: 'stretch',
          pack: 'start',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            id: 'BtnAddCondition',
            iconCls: 'InsertCondition',
            minWidth: 100,
            text: 'Добавить условие',
            menu: {}
          },
          {
            xtype: 'checkboxfield',
            flex: 1,
            margins: '3',
            id: 'CheckBoxSave2DB',
            boxLabel: 'Сохранить'
          },
          btnDeleteFilter
        ]
      }],
    CloseFilterWindow: function () {
      var me = this;
      delete me.FilterSettings;
      me.FilterSettings = null;
      me.close();
      Ext.destroy(me);
    }
  });
  FilterWindow.ConditionsContainer = Ext.create('Ext.form.FieldContainer', {});
  FilterWindow.add(FilterWindow.ConditionsContainer);
  FilterWindow.btnDeleteFilter = btnDeleteFilter;
  FilterWindow.btnDeleteFilter.FilterWindow = FilterWindow;
  btnDeleteFilter.hide();
  FilterWindow.show();
  return FilterWindow;
}

function CreateValueStrFromArray(Array) {//формирует строку из массива значений для отобрвжение в мультивалуе
  if ((Array === undefined) || (Array === null) || (Array === ""))
    return '';
  var s = '';
  var coma = '';
  var length = Array.length;
  for (var j = 0; j < length; j++) {
    s = s + coma + Array[j];
    coma = ",";
  }
  return s;
}

function CloseMultiValueWindow(multi_value_window) {
  multi_value_window.close();
  Ext.destroy(multi_value_window);
}

function CreateMultiValueCondition(c_container, c_input_type, c_value, c_ValidateCondition, c_combo_values_store, c_slv_id_object) {
  if (c_input_type === '0') {
    var InputControl = Ext.create('Ext.form.field.Text', {
      editable: true,
      filter_input_type: c_input_type,
      filter_controltype: 'input',
      filter_ValidateCondition: c_ValidateCondition,
      flex: 1,
      value: c_value,
      hasfocus: true,
      validateOnChange: true,
      validator: function (v) {
        var t = this;
        if (!((t.value === "undefined") || (t.value === null) || (t.value === "") ||
                (this.filter_ValidateCondition === "undefined") || (this.filter_ValidateCondition === null) || (this.filter_ValidateCondition === ""))) {
          var b = eval(this.filter_ValidateCondition);
          if (b) {
            Ext.MessageBox.alert(t.value + ' - Ограничение ввода');
            t.focus();
            return false;
          }
        }
        return true;
      }
    });
  } else
  if (c_input_type === '1') {
    var InputControl = Ext.create('Ext.form.field.ComboBox', {
      filter_input_type: c_input_type,
      filter_controltype: 'input',
      flex: 1,
      matchFieldWidth: false,
      editable: false,
      mode: 'local',
      triggerAction: 'all',
      store: c_combo_values_store,
      valueField: 'value',
      displayField: 'value',
      value: c_value
    });
  } else
  if (c_input_type === '2') {
    var InputControl = Ext.create('Ext.form.DateField', {
      editable: true,
      format: "d.m.Y",
      filter_input_type: c_input_type,
      filter_controltype: 'input',
      flex: 1,
      value: c_value
    });
  } else
  if (c_input_type === '3') {
    var InputControl = Ext.create('Ext.form.field.Trigger', {
      editable: false,
      hideTrigger: false,
      filter_input_type: c_input_type,
      filter_controltype: 'input',
      slv_id_object: c_slv_id_object,
      flex: 1,
      value: c_value,
      triggerCls: 'x-form-search-trigger',
      onTriggerClick: function () {
        // this.triggerEl.elements[0].removeCls('x-form-search-trigger').addCls('x-form-trigger');
        var cx = Math.round(document.body.clientWidth / 2);
        var cy = Math.round(document.body.clientHeight / 2);
        var w = Math.round(document.body.clientWidth / 5 * 4);
        var h = Math.round(document.body.clientHeight / 5 * 4);
        var oParams = {cx: cx, cy: cy, w: w, h: h, menubar: 0, toolbar: 0, location: 0, directories: 0, status: 0};
        var ret;
        ret = window.showModalDialog("ShowObjectExtJS.php?modal=1&OBJ_KEY=" + this.slv_id_object, oParams, "location:no");
        if ((ret !== null) && (ret !== undefined)) {
          this.setValue(ret);
        }
      },
      trigger2Cls: 'x-form-clear-trigger',
      onTrigger2Click: function () {
        this.setValue(null);
      }
    });
  }

  var ConditionItemContainer = Ext.create('Ext.container.Container', {dock: 'top',
    layout: {
      align: 'stretch',
      type: 'hbox'
    },
    items: [
      InputControl,
      {
        xtype: 'button',
        tooltip: 'Удалить условие',
        iconCls: 'DeleteCondition',
        tooltipType: 'title',
        handler: function () {
          var owningTabPanel = this.up('container');
          Ext.destroy(owningTabPanel);
        }
      }
    ]});
  c_container.add(ConditionItemContainer);
  InputControl.focus();
}

function SaveMultivalueCondition(multi_value_control, multi_value_window, MultiValueContainer) {
  var ValueArray = [];
  var i, j, cc; //цикл по вложенным контролам ConditionsContainer - это каждое условие фильтра
  var len = MultiValueContainer.items.length;
  for (var i = 0; i < len; i++) {
    var cc = MultiValueContainer.items.items[i];
    var field_value;
    var len2 = cc.items.length;
    for (var j = 0; j < len2; j++) {
      var cctrl = cc.items.items[j];
      if (cctrl.filter_controltype === 'input') {
        if (cctrl.filter_input_type == "2") {
          f = cctrl.getValue()
          field_value = Ext.Date.format(f, 'd/m/Y');
        }
        else
          field_value = cctrl.getValue();
      }
    }
    if (!((field_value === undefined) || (field_value === null) || (field_value === "")))
      ValueArray.push(field_value);
  }
  multi_value_control.VALUES = ValueArray;
  multi_value_control.setValue(CreateValueStrFromArray(multi_value_control.VALUES));
  CloseMultiValueWindow(multi_value_window);
}

function ShowMultiValueInputWindow(multi_value_control, FilterWindow) { //создает окно ввода многозначных
  var MultiValueContainer = Ext.create('Ext.form.FieldContainer', {});
  var MultiValueWindow = Ext.create("Ext.Window", {
    title: multi_value_control.FieldCaption + '- многозначное условие',
    width: FilterWindow.width,
    height: FilterWindow.height,
    closable: false,
    autoScroll: true,
    HelpContext: 'kometa_filter_multi',
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    layout: {
      type: 'card'
    },
    constrainHeader: true,
    modal: true,
    dockedItems: [
      {
        xtype: 'container',
        dock: 'bottom',
        layout: {
          align: 'stretch',
          pack: 'end',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            minWidth: 100,
            text: 'OK',
            handler: function () {
              SaveMultivalueCondition(multi_value_control, MultiValueWindow, MultiValueContainer);
            }
          },
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Отмена',
            handler: function () {
              CloseMultiValueWindow(MultiValueWindow);
            }
          }
        ]
      },
      {
        xtype: 'container',
        dock: 'bottom',
        layout: {
          align: 'stretch',
          pack: 'start',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Добавить условие',
            handler: function () {
              CreateMultiValueCondition(MultiValueContainer, multi_value_control.filter_input_type, null,
                      multi_value_control.filter_ValidateCondition, multi_value_control.combo_values_store, multi_value_control.slv_id_object)
            }
          },
          {
            xtype: 'text',
            margins: '1',
            text: ' - Связка условий по "или".',
            flex: 1
          }
        ]
      }]
  });
  MultiValueWindow.add(MultiValueContainer);
  var Array = multi_value_control.VALUES; //далее добавляю пустое значение если массив значений пуст
  if ((Array === undefined) || (Array === null) || (Array === "")) {
    CreateMultiValueCondition(MultiValueContainer, multi_value_control.filter_input_type, null,
            multi_value_control.filter_ValidateCondition, multi_value_control.combo_values_store, multi_value_control.slv_id_object);
  } else if (Array.length === 0) {
    CreateMultiValueCondition(MultiValueContainer, multi_value_control.filter_input_type, null,
            multi_value_control.filter_ValidateCondition, multi_value_control.combo_values_store, multi_value_control.slv_id_object);
  } else { //по всем значениям строю контролы ввода
    var length = Array.length;
    for (var j = 0; j < length; j++) {
      CreateMultiValueCondition(MultiValueContainer, multi_value_control.filter_input_type, Array[j],
              multi_value_control.filter_ValidateCondition, multi_value_control.combo_values_store, multi_value_control.slv_id_object);
    }
  }
  MultiValueWindow.show();
}

function CreateCondition(c_field_code, c_field_descr, c_condition_list, c_input_type, c_condition_value, c_value, c_ValidateCondition,
        c_combo_values_array, c_slv_id_object, c_multi_value, c_id_link, c_sysname, FilterWindow) {
  var data_ = [];
  var values = Ext.Object.getValues(c_condition_list);
  var keys = Ext.Object.getKeys(c_condition_list);
  var length = keys.length;
  for (var j = 0; j < length; j++) {
    data_.push([keys[j], values[j]]);
  }
  var combo_data_ = [];
  if (!((c_combo_values_array === undefined) || (c_combo_values_array === null))) {
    var length = c_combo_values_array.length;
    for (var j = 0; j < length; j++) {
      combo_data_.push([c_combo_values_array[j]]);
    }
  }
  if (c_multi_value) {
    var InputControl = Ext.create('Ext.form.field.Trigger', {
      editable: false,
      hideTrigger: false,
      filter_input_type: c_input_type,
      filter_controltype: 'input',
      multi_value: true,
      FieldCaption: c_field_descr,
      slv_id_object: c_slv_id_object,
      filter_ValidateCondition: c_ValidateCondition,
      combo_values_store: new Ext.data.ArrayStore({
        fields: ['value'],
        data: combo_data_}),
      VALUES: c_value,
      value: CreateValueStrFromArray(c_value),
      flex: 1,
      triggerCls: 'x-form-search-trigger',
      id_link: c_id_link,
      sysname: c_sysname,
      onTriggerClick: function () {
        Ext.getCmp('BtnFilterOK').enable();
        ShowMultiValueInputWindow(this, FilterWindow);
      },
      trigger2Cls: 'x-form-clear-trigger',
      onTrigger2Click: function () {
        Ext.getCmp('BtnFilterOK').enable();
        this.setValue(null);
        this.VALUES = [];
      }
    });
  } else if (c_input_type === '0') {
//  var InputControlName = 'Input_' + c_field_code + '_' + ConditionsContainer.items.length;
    var InputControl = Ext.create('Ext.form.field.Text', {
      editable: true,
      //    name: InputControlName,
      filter_input_type: c_input_type,
      multi_value: false,
      filter_controltype: 'input',
      filter_ValidateCondition: c_ValidateCondition,
      flex: 1,
      value: c_value,
      hasfocus: true,
      validateOnChange: true,
      id_link: c_id_link,
      sysname: c_sysname,
      validator: function (v) {
        Ext.getCmp('BtnFilterOK').enable();
        var t = this;
        if (!((t.value === "undefined") || (t.value === null) || (t.value === "") ||
                (this.filter_ValidateCondition === "undefined") || (this.filter_ValidateCondition === null) || (this.filter_ValidateCondition === ""))) {
          var b = eval(this.filter_ValidateCondition);
          if (b) {
            Ext.MessageBox.alert(t.value + ' - Ограничение ввода');
            t.focus();
            return false;
          }
        }
        return true;
      }
    });
  } else
  if (c_input_type === '1') {
    var InputControl = Ext.create('Ext.form.field.ComboBox', {
      filter_input_type: c_input_type,
      multi_value: false,
      filter_controltype: 'input',
      flex: 1,
      matchFieldWidth: false,
      editable: false,
      mode: 'local',
      triggerAction: 'all',
      store: new Ext.data.ArrayStore({
        fields: ['value'],
        data: combo_data_}),
      valueField: 'value',
      displayField: 'value',
      value: c_value,
      validateOnChange: true,
      id_link: c_id_link,
      sysname: c_sysname,
      validator: function (v) {
        Ext.getCmp('BtnFilterOK').enable();
      }
    });
  } else
  if (c_input_type === '2') {
    var InputControl = Ext.create('Ext.form.DateField', {
      editable: true,
      format: "d.m.Y",
      filter_input_type: c_input_type,
      multi_value: false,
      filter_controltype: 'input',
      flex: 1,
      value: c_value,
      validateOnChange: true,
      id_link: c_id_link,
      sysname: c_sysname,
      validator: function (v) {
        Ext.getCmp('BtnFilterOK').enable();
      }
    });
  } else
  if (c_input_type === '3') {
    var InputControl = Ext.create('Ext.form.field.Trigger', {
      editable: false,
      hideTrigger: false,
      filter_input_type: c_input_type,
      multi_value: false,
      filter_controltype: 'input',
      slv_id_object: c_slv_id_object,
      flex: 1,
      value: c_value,
      triggerCls: 'x-form-search-trigger',
      id_link: c_id_link,
      sysname: c_sysname,
      onTriggerClick: function () {
        Ext.getCmp('BtnFilterOK').enable();
        // this.triggerEl.elements[0].removeCls('x-form-search-trigger').addCls('x-form-trigger');
        var cx = Math.round(document.body.clientWidth / 2);
        var cy = Math.round(document.body.clientHeight / 2);
        var w = Math.round(document.body.clientWidth / 5 * 4);
        var h = Math.round(document.body.clientHeight / 5 * 4);
        var oParams = {cx: cx, cy: cy, w: w, h: h};
        var ret;
        ret = window.showModalDialog("ShowObjectExtJS.php?modal=1&OBJ_KEY=" + this.slv_id_object, oParams, "location:no");
        if ((ret !== null) && (ret !== undefined)) {
          this.setValue(ret);
        }
      },
      trigger2Cls: 'x-form-clear-trigger',
      onTrigger2Click: function () {
        this.setValue(null);
      }
    });
  } else
  if (c_input_type === '4') {
    if (c_value)
      c_value = 1;
    else
      c_value = 0;
    var InputControl = Ext.create('Ext.form.field.Checkbox', {
      editable: true,
      filter_input_type: c_input_type,
      filter_controltype: 'input',
      flex: 1,
      value: c_value,
      hasfocus: true,
      inputValue: '1',
      uncheckedValue: '0',
      checked: (c_value == 1),
      validateOnChange: false,
      margin: '0 0 0 20',
      id_link: c_id_link,
      sysname: c_sysname,
      validateOnChange: true,
              validator: function (v) {
                Ext.getCmp('BtnFilterOK').enable();
              }

    });
  }
  if ((c_condition_value === null) || (c_condition_value === undefined) || (c_condition_value === '')) {
    if (data_.length > 0)
      c_condition_value = data_[0];
  }
  var ConditionItemContainer = Ext.create('Ext.container.Container', {dock: 'top',
    layout: {
      align: 'stretch',
      type: 'hbox'
    },
    items: [
      {
        xtype: 'text',
        text: c_field_descr,
        flex: 1
      },
      {
        xtype: 'combobox',
//                fieldLabel: c_field_descr,
        filter_field_code: c_field_code,
        filter_controltype: 'condition',
        growToLongestValue: true,
        editable: false,
        //  width: 400,
        mode: 'local',
        triggerAction: 'all',
        store: new Ext.data.ArrayStore({
          fields: ['key', 'value'],
          data: data_}),
        valueField: 'key',
        displayField: 'value',
        id_link: c_id_link,
        sysname: c_sysname,
        value: c_condition_value
      },
      InputControl,
      {
        xtype: 'button',
        tooltip: 'Удалить условие',
        iconCls: 'DeleteCondition',
        tooltipType: 'title',
        id_link: c_id_link,
        sysname: c_sysname,
        handler: function () {
          var owningTabPanel = this.up('container');
          Ext.destroy(owningTabPanel);
          Ext.getCmp('BtnFilterOK').enable();
        }
      }
    ]});
  FilterWindow.ConditionsContainer.add(ConditionItemContainer);
  InputControl.focus();
}

function getIndexOfFilterSettings(arr, k) {
  for (var i = 0; i < arr.length; i++) {
    if (arr[i].field_code === k) {
      return i;
    }
  }
  return -1;
}

function workFilterSetting(FilterWindow) {
  var length = FilterWindow.FilterSettings.length;
  var id_link;
  id_link = '-1';
  _iconCls = '';
  for (var i = 0; i < length; i++) {
    var id_lnk;
    if (FilterWindow.FilterSettings[i].id_link != undefined) {
      id_lnk = FilterWindow.FilterSettings[i].id_link;//field_code.substr(0, FilterSettings[i].field_code.indexOf('.'));
    }
    else
      id_lnk = '-1';
    if (id_link != id_lnk) {
      id_link = id_lnk;
      separator = Ext.create('Ext.menu.Separator');
      Ext.getCmp('BtnAddCondition').menu.add(separator);
      _iconCls = 'link_object_filter';
    }
    var menuItem = Ext.create('Ext.menu.Item', {text: FilterWindow.FilterSettings[i].field_descr
      , iconCls: _iconCls
      , handler: function (x) {
        return function () { //обхожу замыкание переменной
          CreateCondition(FilterWindow.FilterSettings[x].field_code, FilterWindow.FilterSettings[x].field_descr, FilterWindow.FilterSettings[x].condition_list,
                  FilterWindow.FilterSettings[x].input_type, null, null, FilterWindow.FilterSettings[x].ValidateCondition, FilterWindow.FilterSettings[x].combo_values_array,
                  FilterWindow.FilterSettings[x].slv_id_object, FilterWindow.FilterSettings[x].multivalue, FilterWindow.FilterSettings[x].id_link, FilterWindow.FilterSettings[x].sysname, FilterWindow);
          Ext.getCmp('BtnFilterOK').enable();
        };
      }(i)
    });
    Ext.getCmp('BtnAddCondition').menu.add(menuItem);
  }
}

function loadObjectFilter(FilteredGrid, FilterWindow) {
  FilterWindow.ObjectFilter = FilteredGrid.ObjectFilter;
  FilterWindow.FilterSettings = FilteredGrid.FilterSettings;
  var _id_ObjectFilter = parseInt(FilteredGrid.FilterCombo.getValue(), 10);
  if ((FilterWindow.ObjectFilter == undefined) || (FilterWindow.ObjectFilter.FilterConditions == undefined) || (FilterWindow.ObjectFilter.FilterConditions.length == 0)) {
    GridFilter_class.GetFilterConditions(FilteredGrid.ObjectInitGrid.sysname, _id_ObjectFilter, function (response) {
      if (response != undefined) {
        if ((response.success === false) && (response.result == 're_connect')) {
          alert(response.msg);
          window.onbeforeunload = null;
          findFirstWindow().window.location.href = __first_page;
          return false;
        } else
        if (response.success == true) {
          FilterWindow.ObjectFilter = response.result;
          FilteredGrid.ObjectFilter = FilterWindow.ObjectFilter;
          if ((FilterWindow.ObjectFilter.id_user == undefined) && (FilterWindow.ObjectFilter.id_ObjectFilter > 0)) {
            CheckBoxSave2DB.hidden = true;
            FilterWindow.btnDeleteFilter.hidden = false;
          }
          else if (FilterWindow.ObjectFilter.id_ObjectFilter > 0)
            FilterWindow.btnDeleteFilter.show();
          FilterWindow.setTitle(FilterWindow.ObjectFilter.FilterCaption);
          var length = FilterWindow.ObjectFilter.FilterConditions.length;
          for (var i = 0; i < length; i++) {
            var tmp_fld_code = FilterWindow.ObjectFilter.FilterConditions[i].field_code;
            var idx = getIndexOfFilterSettings(FilterWindow.FilterSettings, tmp_fld_code);
            if (idx > -1) {
              var tmp_FilterSettings = FilterWindow.FilterSettings[idx];
              CreateCondition(tmp_fld_code, tmp_FilterSettings.field_descr, tmp_FilterSettings.condition_list, tmp_FilterSettings.input_type,
                      FilterWindow.ObjectFilter.FilterConditions[i].field_condition, FilterWindow.ObjectFilter.FilterConditions[i].field_value,
                      tmp_FilterSettings.ValidateCondition, tmp_FilterSettings.combo_values_array,
                      tmp_FilterSettings.slv_id_object, tmp_FilterSettings.multivalue, tmp_FilterSettings.id_link, tmp_FilterSettings.sysname, FilterWindow);
            }
          }
          Ext.getCmp('BtnFilterOK').disable();
          return true;
        } else {
          Ext.Msg.alert("Ошибка получения условий фильтра: ", response.msg);
          return false;
        }
      } else {
        Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения условий фильтра');
        return false;
      }
    })
  }
  else {
    if ((FilterWindow.ObjectFilter.id_user == undefined) && (FilterWindow.ObjectFilter.id_ObjectFilter > 0)) {
      CheckBoxSave2DB.hidden = true;
      FilterWindow.btnDeleteFilter.hidden = false;
    }
    else if (FilterWindow.ObjectFilter.id_ObjectFilter > 0)
      FilterWindow.btnDeleteFilter.show();
    FilterWindow.setTitle(FilterWindow.ObjectFilter.FilterCaption);
    var length = FilterWindow.ObjectFilter.FilterConditions.length;
    for (var i = 0; i < length; i++) {
      var tmp_fld_code = FilterWindow.ObjectFilter.FilterConditions[i].field_code;
      var idx = getIndexOfFilterSettings(FilterWindow.FilterSettings, tmp_fld_code);
      if (idx > -1) {
        var tmp_FilterSettings = FilterWindow.FilterSettings[idx];
        CreateCondition(tmp_fld_code, tmp_FilterSettings.field_descr, tmp_FilterSettings.condition_list, tmp_FilterSettings.input_type,
                FilterWindow.ObjectFilter.FilterConditions[i].field_condition, FilterWindow.ObjectFilter.FilterConditions[i].field_value,
                tmp_FilterSettings.ValidateCondition, tmp_FilterSettings.combo_values_array,
                tmp_FilterSettings.slv_id_object, tmp_FilterSettings.multivalue, tmp_FilterSettings.id_link, tmp_FilterSettings.sysname, FilterWindow);
      }
    }
    Ext.getCmp('BtnFilterOK').enable();
  }
}

function ShowFilter(FilteredGrid) {
  var FilterWindow = ShowWindow(FilteredGrid);
  if (FilteredGrid.FilterSettings == undefined) {
    Ext.MessageBox.wait({
      msg: 'Загрузка настроек фильтра ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    GridFilter_class.GetFilterSettings(FilteredGrid.ObjectInitGrid.sysname, function (response) {
      if (response != undefined) {
        if ((response.success === false) && (response.result == 're_connect')) {
          alert(response.msg);
          window.onbeforeunload = null;
          findFirstWindow().window.location.href = __first_page;
          return false;
        } else
        if (response.success == true) {
          FilterWindow.FilterSettings = response.result;
          workFilterSetting(FilterWindow);
          Ext.MessageBox.hide();
          FilteredGrid.FilterSettings = FilterWindow.FilterSettings;
          loadObjectFilter(FilteredGrid, FilterWindow);
          return true;
        } else {
          Ext.MessageBox.alert("Ошибка получения настроек фильтра: ", response.statusText);
          return false;
        }
      } else {
        Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения  настроек фильтра');
        return false;
      }
    });
  }
  else {
    FilterWindow.FilterSettings = FilteredGrid.FilterSettings;
    workFilterSetting(FilterWindow);
    loadObjectFilter(FilteredGrid, FilterWindow);
  }
  return true;
}

function GetFilterCaption(FilteredGrid, FilterWindow) {
  FilterWindow.ObjectFilter.Save2DB = Ext.getCmp('CheckBoxSave2DB').getValue();
  if ((FilterWindow.ObjectFilter.id_ObjectFilter < 1) && (FilterWindow.ObjectFilter.Save2DB === true)) { //запросить имя фильтра
    Ext.Msg.prompt('Описание фильтра', 'Введите описание нового фильтра:', function (btn, text) {
      if (btn === 'ok') {
        FilterWindow.ObjectFilter.FilterCaption = text;
        SaveFilterConditions(FilteredGrid, true, FilterWindow);
      } else
        return false;
    }, window, false, FilterWindow.ObjectFilter.FilterCaption);
  } else if ((FilterWindow.ObjectFilter.id_ObjectFilter > 1) && (FilterWindow.ObjectFilter.Save2DB === false)) { //запросить имя фильтра
    FilterWindow.ObjectFilter.id_ObjectFilter = -1;
    SaveFilterConditions(FilteredGrid, false, FilterWindow);
  } else
    SaveFilterConditions(FilteredGrid, false, FilterWindow);
}

function SaveFilterConditions(FilteredGrid, isAppend, FilterWindow) {
  FilterWindow.ObjectFilter.FilterConditions.length = 0;
  var i, j, cc; //цикл по вложенным контролам ConditionsContainer - это каждое условие фильтра
  var len = FilterWindow.ConditionsContainer.items.length;
  for (var i = 0; i < len; i++) {
    var cc = FilterWindow.ConditionsContainer.items.items[i];
    var field_code, field_condition, field_value, multivalue;
    var len2 = cc.items.length;
    for (var j = 0; j < len2; j++) {
      var cctrl = cc.items.items[j];
      if (cctrl.filter_controltype === 'condition') {
        field_code = cctrl.filter_field_code;
        id_link = cctrl.id_link;
        sysname = cctrl.sysname;
        id_object = cctrl.id_object;
        field_condition = cctrl.getValue();
      } else if (cctrl.filter_controltype === 'input') {
        multivalue = cctrl.multi_value;
        id_link = cctrl.id_link;
        sysname = cctrl.sysname;
        if (multivalue)
          field_value = cctrl.VALUES;
        else
          field_value = cctrl.getValue();
      }
    }
    var n = FilterWindow.ObjectFilter.FilterConditions.push({'field_code': field_code, 'field_condition': field_condition, 'multivalue': multivalue, 'id_user': ID_User, 'id_link': id_link, 'sysname': sysname});
    FilterWindow.ObjectFilter.FilterConditions[n - 1].field_value = field_value;
  }
  var encodedObjectFilter = Ext.JSON.encode(FilterWindow.ObjectFilter);
  encodedObjectFilter.id_ObjectFilter = null;

  GridFilter_class.ReadFilterConditions(encodedObjectFilter, function (response) {
    if (response != undefined) {
      if ((response.success === false) && (response.result == 're_connect')) {
        alert(response.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return false;
      } else
      if (response.success == true) {
        FilterWindow.ObjectFilter = null;
        FilterWindow.ObjectFilter = response.result;
        FilteredGrid.ObjectInitGrid.FilterWhereCond = FilterWindow.ObjectFilter.FilterSQLWhereStr;
        if (FilteredGrid.FilterCombo !== undefined) {
          if (isAppend === true) {
            var combo_store = FilteredGrid.FilterCombo.getStore();
            combo_store.add({id: FilterWindow.ObjectFilter.id_ObjectFilter, value: FilterWindow.ObjectFilter.FilterCaption});
            FilteredGrid.FilterCombo.bindStore(combo_store);
            combo_store.commitChanges();
//          FilterCombo.reset();
            FilteredGrid.FilterCombo.setValue(FilterWindow.ObjectFilter.id_ObjectFilter);
          }
          else {
            if (FilterWindow.ObjectFilter.id_ObjectFilter > 0)
              FilteredGrid.FilterCombo.setValue(FilterWindow.ObjectFilter.id_ObjectFilter);
            else {
              FilteredGrid.FilterCombo.setValue(null);
              FilterWindow.ObjectFilter.id_ObjectFilter = -1;
            }
          }
        }
        FilterWindow.CloseFilterWindow();
        FilteredGrid.getStore().load();
        return true;
      } else {
        Ext.Msg.alert("Ошибка получения условий фильтра: ", response.msg);
        return false;
      }
    } else {
      Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения условий фильтра');
      return false;
    }
  });
}