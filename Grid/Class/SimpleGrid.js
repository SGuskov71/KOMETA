
Ext.define('KOMETA.Grid.SimpleGrid', {
  extend: 'Ext.grid.Panel',
  alias: 'widget.simplegrid',
  SelectFirstRecordOnLoad: true, //надо ли после загрузки данных выделять первую запись надо отключать у подчиненных гридов иначе на них прыгает фокус ввода
  initComponent: function (config) {
    var me = this;
    if (!this.ObjectInitGrid) {
      alert('Отсутствует объект настройки');
      return null;
    }
    var StoreFildsSet = [];
    var JSModelGridColumns = [];
    var GridFieldsStyleArray = [];
    var n = 0;
    this.ObjectInitGrid.field_list.forEach(function (_field) {
      GridFieldsStyleArray[_field.fieldname] = _field.code_field_style;
      if (_field.create_grid_column === true) {
        JSModelGridColumns[n] = {};
        switch (_field.type_field_EXTJS) {
          case 'INTEGER', 'FLOAT':
            JSModelGridColumns[n].xtype = 'numbercolumn';
            JSModelGridColumns[n].align = 'right';
            break;
          case 'DATE':
            JSModelGridColumns[n].xtype = 'datecolumn';
            JSModelGridColumns[n].align = 'right';
            break;
          case 'BOOLEAN':
            JSModelGridColumns[n].xtype = 'booleancolumn';
            JSModelGridColumns[n].align = 'right';
            break;
          default:
            JSModelGridColumns[n].xtype = 'gridcolumn';
            JSModelGridColumns[n].wordWrapAllow = true;
        }
        JSModelGridColumns[n].renderer = me.GridRenderer;
        JSModelGridColumns[n].RendererType = _field.render_type;
        JSModelGridColumns[n].RendererMask = _field.render_mask;
        JSModelGridColumns[n].hidden = !(_field.visible_in_grid);
        JSModelGridColumns[n].header = _field.short_name;
        JSModelGridColumns[n].sortable = true;
        JSModelGridColumns[n].dataIndex = _field.fieldname;
        JSModelGridColumns[n].code_field_style = _field.code_field_style;
        if ((_field.column_width) && (trim(_field.column_width) != '')) {
          JSModelGridColumns[n].width = _field.column_width;
        } else {
          JSModelGridColumns[n].flex = 1;
        }
        n++;
      }
      if (_field.type_field_EXTJS === 'date')//надо отформатировать данные для понимания даты
        StoreFildsSet.push(new Ext.data.Field({name: _field.fieldname, type: {type: _field.type_field_EXTJS, dateFormat: _field.render_mask}}));
      else
        StoreFildsSet.push(new Ext.data.Field({name: _field.fieldname, type: {type: _field.type_field_EXTJS}}));
    });
    var pageSize = 10;
    if ((this.ObjectInitGrid.pageSize != undefined) && (this.ObjectInitGrid.pageSize > 0)) {
      pageSize = this.ObjectInitGrid.pageSize;
    }

    this.store = new Ext.data.Store({
      pageSize: pageSize,
      //  model: _model,
      fields: StoreFildsSet,
      proxy: {
        type: 'direct',
        directFn: 'SimpleGrid_class.GetGridData',
        reader: {
          type: 'json',
          root: 'results',
          totalProperty: 'total'
        },
        listeners: {
          exception: function (proxy, response, options) {
            if (response && proxy) {
              try {
                if (response.result && response.result.result && (response.result.result == 're_connect')) {
                  alert(response.result.msg);
                  findFirstWindow().window.location.href = __first_page;
                  return;
                } else {
                  var responseData = proxy.reader.getResponseData(response);
                  if (responseData.message) {
                    Ext.MessageBox.show({
                      title: 'Ошибка получения данных от сервера',
                      msg: response.result.msg, //response.statusText + ' ' + responseData.message,
                      buttons: Ext.MessageBox.OK,
                      icon: Ext.MessageBox.ERROR
                    });
                  } else {
                    Ext.MessageBox.show({
                      title: 'Ошибка получения данных от сервера',
                      msg: 'Ошибка не установлена',
                      buttons: Ext.MessageBox.OK,
                      icon: Ext.MessageBox.ERROR
                    });
                  }
                }
              } catch (err) {
                console.log(err);
              }
            }
          }
        }
      },
      autoLoad: false,
      remoteSort: true});
    this.FilterCombo = new Ext.form.ComboBox({
//      itemId: 'FilterCombo',
      store: new Ext.data.ArrayStore({
        fields: ['id', 'value'],
        data: me.ObjectInitGrid.FilterComboData
      }),
      mode: 'local',
      lastQuery: '',
      queryMode: 'local',
      value: '-1',
      triggerAction: 'all',
      matchFieldWidth: false,
      displayField: 'value',
      valueField: 'id',
      editable: false,
      forceSelection: true,
      listeners: {
        select: function (combo, record) {
          delete me.ObjectFilter;
          me.ObjectFilter = null;
          var _id_ObjectFilter = parseInt(combo.getValue(), 10);
          GridFilter_class.GetFilterSQLWhereStr(_id_ObjectFilter, function (response) {
            if (response != undefined) {
              if ((response.success === false) && (response.result == 're_connect')) {
                alert(response.msg);
                findFirstWindow().window.location.href = __first_page;
                return false;
              } else
              if (response.success == true) {
                var FilterSQLWhereStr = response.result;
                me.ObjectInitGrid.FilterWhereCond = FilterSQLWhereStr;
                me.getStore().loadPage(1);
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
        }
      }
    });
    this.columns = JSModelGridColumns;
    this.firstRowSelectionOnRefresh = true; //признак выделять первуюстроку после загрузки
    this.SLVGrid_FieldStyle = GridFieldsStyleArray;
    this.autoScroll = true;
    this.collapsible = false;
    this.columnLines = true; // показывать вертикальные линии
    this.viewConfig = {stripeRows: true};
    this.deferEmptyText = false;
    this.emptyText = "Нет данных.";
    this.ObjectFilter = null;
    this.ExecuteDefaultBtn = true;//по двойному клику отработать кнопку по умолчанию если есть
    if (me.ObjectInitGrid.list_operation.length > 0) {
      this.tbar = new Ext.toolbar.Toolbar({});
      Ext.each(me.ObjectInitGrid.list_operation, function (operation, index) {
        var btn = me.AddTBarBtn(operation);
        if (operation.list_operation && (operation.list_operation.length > 0)) {//подменю
          btn.menu = Ext.create('Ext.menu.Menu', {
            items: []
          });
          //  btn.disabled = false;
          var operation_list_operation = operation.list_operation;
          Ext.each(operation_list_operation, function (oper_item, index) {
            btn.menu.add({
              //    xtype: 'menuitem',
              text: oper_item.short_name,
              tooltip: oper_item.full_name,
              tooltipType: 'title',
              iconCls: oper_item.op_style, //реакция на выделение записи только у кнопок первого уровня
              //  disabled: !(oper_item.is_available == 1),
              operationObject: oper_item,
              handler: function (button) {
                me.ExecuteOperation(button.operationObject);
              }
            });
          });
        } else {
          if (operation.is_default_operation === 1)//кнопка по умолчанию
            me.DefaultBtn = btn;
          btn.handler = function (button) {
            me.ExecuteOperation(button.operationObject);
          }
        }
      });
    }

    this.bbar = {
      xtype: 'pagingtoolbar',
      // itemId: 'bbar',
      store: me.getStore(),
      displayInfo: true,
      autoScroll: true,
      overflowX: 'scroll',
      overflowY: 'scroll',
      beforePageText: 'Страница',
      firstText: 'Первая',
      afterPageText: 'из {0}',
      lastText: 'Последняя',
      nextText: 'Следующая',
      prevText: 'Предыдущая',
      refreshText: 'Обновить',
      displayMsg: 'Показано строк {0} - {1} из {2}',
      emptyMsg: 'Нет данных',
      items: [
        '-',
            'Показ по: ',
            {
          // itemId: 'ComboPerPage',
          xtype: 'combobox',
          width: 45,
          store: new Ext.data.ArrayStore({
            fields: ['id'],
            data: [[10], [25], [50], [100]]
          }),
          mode: 'local',
          lastQuery: '',
          queryMode: 'local',
          value: me.getStore().pageSize,
          listWidth: 40,
          triggerAction: 'all',
          displayField: 'id',
          valueField: 'id',
          editable: false,
          forceSelection: true,
          listeners: {
            select: function (combo, record) {
              me.getStore().pageSize = parseInt(combo.value, 10);
              if (me.getStore().getCount() > 0) {
                me.getStore().loadPage(me.getStore().currentPage)
              } else
                me.getStore().loadPage(1);
            }
          }
        },
        '->',
        'Фильтр',
        me.FilterCombo,
        {xtype: 'button',
          //        itemId: 'btnChangeFilter',
          iconCls: 'ChangeFilter',
          tooltipType: 'title',
          tooltip: 'Создать/изменить фильтр',
          handler: function () {
            ShowFilter(me);
          }},
        '-',
        {xtype: 'button',
          iconCls: 'ClearFilter',
          //  itemId: 'btnClearFilter',
          tooltipType: 'title',
          tooltip: 'Очистить фильтр',
          handler: function (button, e) {
            me.ClearFilter();
          }},
        {xtype: 'tbseparator', width: 30},
        {xtype: 'button',
          iconCls: 'SaveSetting',
          tooltipType: 'title',
          tooltip: 'Сохранить настройки',
          handler: function (button, e) {
            me.SaveGridSettings();
          }},
        {xtype: 'tbseparator', width: 30},
        {xtype: 'checkbox',
          boxLabel: 'Перенос',
          handler: function (checkbox, checked) {
            me.EnableCellWordWrap(checked);
          }
        }
      ]
    };
    this.getStore().SimpleGrid = me;
    this.getStore().getProxy().setExtraParam('SimpleGridObject', me.ObjectInitGrid);
    //пернос в заголовках колонок
    createCSSSelector('#' + me.getId() + ' .x-column-header-inner .x-column-header-text', 'white-space: normal');
    createCSSSelector('#' + me.getId() + ' .x-column-header-inner',
            'line-height: normal; padding-top: 3px !important; line-height: normal; padding-bottom: 3px !important; text-align: center; top: 20%;');
    this.callParent(config);
    this.addEvents('GridDblClickRecord');
    this.addEvents('GridSelectRecord');
    this.addEvents('GridUnSelectRecord');
    this.addEvents('ReturnSelectedrecord');
    // if (this.firstRowSelectionOnRefresh)
    this.mon(this.getStore(), {load: this.StoreLoadPreserveSelectionRoutine, scope: me});
//    if (this.firstRowSelectionOnRefresh)
//      this.mon(this.getStore(), {refresh: this.StoreLoadPreserveSelectionRoutine, scope: me});
    this.on('select', me.GridSelectRecord, this);
    this.on('itemdblclick', me.GridDblClickRecord, this);
    //   this.on('itemclick', me.GridSelectRecord, this);
    this.on('cellkeydown', function (grid, td, cellIndex, record, tr, rowIndex, e, eOpts) {
      if (e.getKey() == 13) {
        me.GridDblClickRecord();
      }
    }, this);
//зачищаю отЪиметые свойства
    delete me.ObjectInitGrid.pageSize;
    delete me.ObjectInitGrid.FilterComboData;
    delete me.ObjectInitGrid.list_operation;
  },
  AddTBarBtn: function (operation) {
    var me = this;
    return me.tbar.add({xtype: 'button',
      text: operation.short_name,
      tooltip: operation.full_name,
      tooltipType: 'title',
      iconCls: operation.op_style,
      disabled: operation.is_available != 1,
      operationObject: operation
    });
  },
  GridRenderer: function (value, meta, record, rowIndex, colIndex, store, view) {
    var ThisGrid = this;
    var ColName = meta.column.dataIndex;
    var st = ThisGrid.SLVGrid_FieldStyle;
    if (!(st == undefined)) {
      var fs = st[ColName];
      if (fs == '')
        fs = undefined;
      if (!((fs == undefined))) {
        var _styleName = meta.record.get(fs);
        if (!((_styleName == undefined) || (_styleName == null) || (_styleName == ''))) {
          meta.tdCls = _styleName;
        }
      }
    }
    if (meta.column.RendererType == 2) {
      if (fs == undefined) {
        if ((value == 1))
          meta.tdCls = 'default_check';
        else
          meta.tdCls = 'default_uncheck';
      }
      return '';
    } else if (meta.column.RendererType == 4) {
      try {
        var dat = new Date(value);
        if (dat instanceof Date && !isNaN(dat.valueOf())) {
          if (meta.column.RendererMask == undefined) {
            meta.column.RendererMask = 'd/m/Y g:i a';
          }
          var str = Ext.Date.format(dat, meta.column.RendererMask);
        }
      }
      catch (e) {
        var str = undefined;
      }
      return str;
    } else if (meta.column.RendererType == 3) {
      return Ext.util.Format.number(value, meta.column.RendererMask).replace(/\./g, ' ').replace(/,/g, '.');
    } else {
      return value;
    }
  },
  StoreLoadPreserveSelectionRoutine: function (store, records, successful, eOpts) {
    var me = this;
    var Grid = store.SimpleGrid;
    if (store.getCount() > 0) {
      var sm = this.getSelectionModel();
      if (me.SelectFirstRecordOnLoad == true) {
        sm.selectRange(0, 0);
      }
      else {
        Grid.GridUnSelectRecord();
      }
    } else {
      Grid.GridUnSelectRecord();
    }
    Grid.fireEvent('itemclick', Grid);
  },
  SaveGridSettings: function () {
    var me = this;
    var GridSettingsObject = new Object();
    GridSettingsObject.GridSettings = new Object();
    GridSettingsObject.GridSettings.columns = [];
    GridSettingsObject.sysname = me.ObjectInitGrid.sysname;
    GridSettingsObject.GridSettings.pageSize = me.getStore().pageSize;
    me.columns.forEach(function (col) {
      var colsettings = new Object();
      colsettings.dataIndex = col.dataIndex;
      colsettings.VisibleIndex = col.getVisibleIndex();
      colsettings.visible = !col.hidden;
      colsettings.width = col.getWidth();
      GridSettingsObject.GridSettings.columns.push(colsettings);
    });
    SimpleGrid_class.SaveGridSettings(GridSettingsObject, function (result) {
      if ((result.success === false) && (result.result == 're_connect')) {
        alert(result.msg);
        findFirstWindow().window.location.href = __first_page;
        return;
      } else if (result.success === false) {
        alert(result.msg);
        return;
      }
      if ((result.result == '1') || (result.result == '2')) {// 1- первое сохранение 2- обновление сохраненных
        Ext.Msg.alert('Настройки просмотра объекта', 'Настройки сохранены');
      } else
      {
        Ext.Msg.alert('Настройки просмотра объекта', 'Ошибка сохранения настроек');
      }
    });
  },
  EnableCellWordWrap: function (_enable) {
    var me = this;
    me.columns.forEach(function (col) {
      if (col.wordWrapAllow == true) {
        if (_enable == true) {
          col.rend_ = col.renderer;
          col.renderer = function (val, meta, record, rowIndex, colIndex, store) {
            return '<div style="white-space:normal !important;">' + val + '</div>';
          };
        } else {
          if (col.rend_ != undefined)
            col.renderer = col.rend_;
          else
            col.renderer = null;
        }
      }
    });
    me.getStore().load();
  },
  ClearFilter: function () {
    var me = this;
    delete  me.ObjectFilter;
    me.ObjectFilter = null;
    me.ObjectInitGrid.FilterWhereCond = null;
    me.FilterCombo.setValue('-1');
    me.getStore().loadPage(1);
  },
  ExecuteOperation: function (Operation) {//  вызвать обработку операций 
    Run_operation(this, Operation);
  },
  GridDblClickRecord: function () {
    var me = this;
    if ((me.ExecuteDefaultBtn) && (me.DefaultBtn))//если надо обработать кнопку по умолчанию
      ExecuteOperation(me.DefaultBtn.operationObject);
    var record = me.getSelectionModel().getSelection()[0];
    me.fireEvent('GridDblClickRecord', me, record);
  },
  GridSelectRecord: function () {
    var me = this;
    me.SetStateOperationBtn(true);
    var Selection = me.getSelectionModel().getSelection();
    me.fireEvent('GridSelectRecord', me, Selection);
  },
  GridUnSelectRecord: function () {
    var me = this;
    me.SetStateOperationBtn(false);
    me.fireEvent('GridUnSelectRecord', me);
  },
  SetStateOperationBtn: function (Enable) {
    var me = this;
    var _enable = true;
    if (me.masterGrid && me.LinkObject) {
      var recordMaster = me.masterGrid.getSelectionModel().getSelection()[0];
      if (recordMaster == undefined) {
        _enable = false;
      }
    }
    var tbar = me.getDockedItems('toolbar[dock="top"]')[0];
    if (tbar) {
      var btns = Ext.ComponentQuery.query('button', tbar);
      Ext.each(btns, function (btn, index) {
        if ((btn.operationObject) && (((btn.operationObject.is_available == 1) && (_enable == true)) || (Enable === true)))
          btn.enable();
        else
          btn.disable();
      });
    }
  },
  getDetalJoins: function () {
    var me = this;
    var joins_str = '';
    if (me.masterGrid && me.LinkObject) {
      var recordMaster = me.masterGrid.getSelectionModel().getSelection()[0];
      if (recordMaster != undefined) {
        Ext.each(me.LinkObject.joins, function (joins, index) {
          var MasterKeyValue = recordMaster.raw[joins.master_key_fieldname];
          if (!((MasterKeyValue == null) || (MasterKeyValue == undefined))) {
            joins_str = ' and ' + joins.detail_key_fieldname + '= \'' + MasterKeyValue + '\' ';
          }
        })
      } else {
        joins_str = ' and 0>1 ';
      }
    }
    return joins_str;
  },
  getDescrSelRecord: function () {//возвращает описание выбранной записи грида 
    var me = this;
    var recordMaster = me.getSelectionModel().getSelection()[0];
    var ItmCptn = '';
    if (me.descr_fld_connector == undefined)
      me.descr_fld_connector = ' ';

    for (var j = 0; j < me.ObjectInitGrid.descr_fld_list.length; j++) {
      var dddddd = me.ObjectInitGrid.descr_fld_list[j];
      var sss = Ext.String.htmlDecode(recordMaster.raw[dddddd]);
      if (sss != undefined) {
        sss = String(sss);
        if (j > 0) {
          ItmCptn = ItmCptn + me.descr_fld_connector + sss;
        } else {
          ItmCptn = ItmCptn + sss;
        }
      }
    }
    return ItmCptn;
  },
  ReturnSelectedrecord: function () {//возвращает выбранную запись грида целиком
    var me = this;
    var record = me.getSelectionModel().getSelection()[0];
    me.fireEvent('ReturnSelectedrecord', me, record);
  },
  ReloadGrid: function (KeyID) {//перегружает данные грида и пытается сфокусироваться на записи которая была выделена если параметр KeyID пуст
    //или на переданном параметре KeyID ключа
    var me = this;
    var SelectedKey;
    var store = me.getStore();
    if (Ext.isEmpty(KeyID)) {
      var recordMaster = me.getSelectionModel().getSelection()[0];
      if (!Ext.isEmpty(recordMaster)) {
        SelectedKey = recordMaster.getId();
      } else {
        SelectedKey = null;
      }
    } else
      SelectedKey = KeyID;
    store.load({
      scope: this,
      callback: function (records, operation, success) {
        if (success) {
          var record = store.getById(SelectedKey);
          if (!Ext.isEmpty(record)) {
            me.getSelectionModel().select(record);
          }
        }
      }
    });
  },
  ReturnSelectedValSlv: function () {//при работе в качестве выбора из словаря возвращает через систему сообщений
    //значение ключевого поля и описание выбранной записи
    var me = this;
    var recordMaster = me.getSelectionModel().getSelection()[0];
    var key_fld_list_values = {};
    Ext.each(me.ObjectInitGrid.key_fld_list, function (key_fld) {
      key_fld_list_values[key_fld] = recordMaster.get(key_fld);
    });
    var SelDescr = me.getDescrSelRecord();
    var ParentWindow = me.up('window');
    if (ParentWindow)
      ParentWindow.fireEvent('ValSlvSelected', ParentWindow, key_fld_list_values, SelDescr);
  }
});