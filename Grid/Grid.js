dhtmlLoadScript(_URLProjectRoot + 'Grid/FilterWindow.js');

function SelectValSlv(Param) { //выбор словарного значения при помощи окна с гридом
  //Param  объект со свойствами параметрами функции
  var sysname = Param.sysname;
  var object_Caption = Param.object_Caption
  var _HelpContext = Param.HelpContext;
  if (_HelpContext == undefined)
    _HelpContext = '';
  var win = findFirstWindow(),
          _width = Math.round(win.window.document.body.clientWidth / 4 * 3),
          _height = Math.round(win.window.document.body.clientHeight / 4 * 3);
  var w = win.myDesktopApp.desktop.checkExist(sysname);
  if (w) {
    win.myDesktopApp.desktop.restoreWindow(w);
    return w;
  }
  var SLVWindow = win.myDesktopApp.desktop.createWindow({
    title: object_Caption,
    object_Caption: object_Caption,
    sysname: sysname,
    width: _width,
    height: _height,
    closable: true,
    HelpContext: 'kometa_select_valslv' + _HelpContext,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    //  autoScroll: true,
    layout: {
      type: 'fit'
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
            itemId: 'BtnSelectValSlvOK',
            text: 'OK',
            handler: function () {
              SLVWindow.SLVGrid.ReturnSelectedValSlv();
              CloseWindow(SLVWindow);
            }
          },
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Отмена',
            handler: function () {
              CloseWindow(SLVWindow);
            }
          }
        ]
      }]
  });
  SLVWindow.removeAll(true);
  CreateSimleGrid(sysname, Param, function (SimpleGrid) {
    SLVWindow.add(SimpleGrid);
    SLVWindow.SLVGrid = SimpleGrid;
    if ((SLVWindow.title == undefined) || (SLVWindow.title == ''))
      SLVWindow.setTitle(SimpleGrid.ObjectInitGrid.ObjectCaption);
    if (SLVWindow.taskButton)
      SLVWindow.taskButton.setText(SimpleGrid.ObjectInitGrid.ObjectCaption);
    var BtnSelectValSlvOK = SLVWindow.down('#BtnSelectValSlvOK');
    SLVWindow.SLVGrid.getStore().on('refresh', function (t, eOpts) {
      if (BtnSelectValSlvOK != undefined)
        BtnSelectValSlvOK.disable();
    });
    SLVWindow.SLVGrid.on('GridSelectRecord', function (t, record, eOpts) {
      if (BtnSelectValSlvOK != undefined) {
        if (record != undefined)
          BtnSelectValSlvOK.enable();
        else
          BtnSelectValSlvOK.disable();
      }
    });
    SLVWindow.SLVGrid.on('GridSelectRecord', function (t, record, eOpts) {
      if (BtnSelectValSlvOK != undefined) {
        if (record != undefined)
          BtnSelectValSlvOK.enable();
        else
          BtnSelectValSlvOK.disable();
      }
    });
    SLVWindow.SLVGrid.on('itemdblclick', function (t, record, eOpts) {
      SLVWindow.SLVGrid.ReturnSelectedValSlv();
      CloseWindow(SLVWindow);
    });

    SLVWindow.SLVGrid.on('cellkeydown', function (grid, td, cellIndex, record, tr, rowIndex, e, eOpts) {
      if (e.getKey() == 13) {
        SLVWindow.SLVGrid.ReturnSelectedValSlv();
        CloseWindow(SLVWindow);
      }
    }, SLVWindow);

    SimpleGrid.getStore().loadPage(1);
  });
  SLVWindow.addEvents('ValSlvSelected');
  SLVWindow.show();
  return SLVWindow;
}

function ShowMasterDetailGridWindow(Param) { //показ окна с гридом и подчиненными связанными гридами
//Param  объект со свойствами параметрами функции
  var sysname = Param.sysname;
  var object_Caption = Param.object_Caption
  var _HelpContext = Param.HelpContext;
  if (_HelpContext == undefined)
    _HelpContext = '';
  var win = findFirstWindow(),
          _width = Math.round(win.window.document.body.clientWidth / 6 * 5),
          _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
  var w = win.myDesktopApp.desktop.checkExist(sysname);
  if (w) {
    win.myDesktopApp.desktop.restoreWindow(w);
    return w;
  }
  var MasterDetailWindow = null;

  CreateMasterDetailPanel(sysname, Param, function (MasterDetailPanel) {
    MasterDetailWindow = win.myDesktopApp.desktop.createWindow({
      title: object_Caption,
      object_Caption: object_Caption,
      sysname: sysname,
      code: sysname, //если не находит окно с таким кодом то создает его
      closable: true,
      maximizable: true,
      minimizable: true,
      HelpContext: 'kometa_select_valslv' + _HelpContext,
      width: _width,
      height: _height,
      tools: [{
          type: 'help',
          qtip: 'Справка',
          callback: ShowHelp
        }],
      //  autoScroll: true,
      layout: 'fit',
      bodyBorder: false,
      constrainHeader: true
    });
    MasterDetailWindow.removeAll(true); //если окно уже было то разрушить контент
    MasterDetailWindow.add(MasterDetailPanel);
    MasterDetailWindow.MasterDetailPanel = MasterDetailPanel;
    if ((MasterDetailWindow.title == undefined) || (MasterDetailWindow.title == ''))
      MasterDetailWindow.setTitle(MasterDetailPanel.masterGrid.ObjectInitGrid.ObjectCaption);
    if (MasterDetailWindow.taskButton)
      MasterDetailWindow.taskButton.setText(MasterDetailPanel.masterGrid.ObjectInitGrid.ObjectCaption);
    MasterDetailWindow.show();
  });
  return MasterDetailWindow;
}

function ShowObjectGroupWindow(Param)
{
  if ((Param.param_list) && (Param.param_list.type_view))
    var type_view = Param.param_list.type_view;
  var object_Caption = Param.object_Caption
  var _HelpContext = Param.HelpContext;
  if (_HelpContext == undefined)
    _HelpContext = '';
  var win = findFirstWindow(),
          _width = Math.round(win.window.document.body.clientWidth / 6 * 5),
          _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
  var w = win.myDesktopApp.desktop.checkExist('ObjectGroupWindow' + type_view);
  if (w) {
    win.myDesktopApp.desktop.restoreWindow(w);
    return w;
  }
  var ObjectGroupWindow = win.myDesktopApp.desktop.createWindow({
    title: object_Caption,
    closable: true,
    code: 'ObjectGroupWindow' + type_view,
    maximizable: true,
    minimizable: true,
    HelpContext: 'kometa_select_valslv' + _HelpContext,
    width: _width,
    height: _height,
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      }],
    layout: 'border',
    bodyBorder: false,
    constrainHeader: true,
    onTreeMenuItemClick: function (dataview, record, item, index, e, eOpts) {
      var me = this;
      var sysname = record.raw.sysname;
      if (sysname != undefined) {
        var tab = me.FindTab(sysname);
        if (tab != undefined) {
          me.ObjectsTab.setActiveTab(tab);
        } else {
          CreateMasterDetailPanel(sysname, {}, function (MasterDetailPanel) {
            var cmp = me.ObjectsTab.insert(0, {
              title: record.raw.text,
              iconCls: record.raw.iconCls,
              autoScroll: false,
              closable: true,
              sysname: sysname,
              autoRender: true,
              layout: 'fit',
            });
            cmp.add(MasterDetailPanel);
            cmp.show();
          });
        }
      }
    },
    FindTab: function (sysname) {
      var me = this;
      for (var i = 0; i < me.ObjectsTab.items.items.length; i++) {
        if (me.ObjectsTab.items.items[i].sysname == sysname)
          return i;
      }
      return null;
    }
  });
  ObjectGroup_class.get_groups_Root(type_view, function (response) {
    if (response != undefined) {
      if ((response.success === false) && (response.result == 're_connect')) {
        alert(response.msg);
        findFirstWindow().window.location.href = __first_page;
        return false;
      } else
      if (response.success == true) {
        var GroupRoot = response.result;
        ObjectGroupWindow.menuPanel = Ext.create('Ext.tree.Panel', {
          xtype: 'treepanel',
          region: 'west',
          split: true,
          width: '30%',
          minWidth: 80,
          title: 'Группы объектов',
          root: GroupRoot,
          viewConfig: {
            itemId: 'treeMenu',
            rootVisible: false,
            listeners: {
              itemclick: {
                fn: ObjectGroupWindow.onTreeMenuItemClick,
                scope: ObjectGroupWindow
              }
            }
          }
        });
        ObjectGroupWindow.add(ObjectGroupWindow.menuPanel);
        ObjectGroupWindow.ObjectsTab = Ext.create('Ext.tab.Panel', {
          xtype: 'tabpanel',
          region: 'center',
          activeTab: 0,
          minWidth: 100,
          resizeTabs: true,
          enableTabScroll: true,
          autoScroll: false,
          items: []
        });
        ObjectGroupWindow.add(ObjectGroupWindow.ObjectsTab);
      }
      else {
        Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения условий построения таблицы:' + response.msg);
      }
    } else {
      Ext.Msg.alert('Выполнение прервано', 'Ошибка получения условий построения таблицы');
    }
  });
  ObjectGroupWindow.show();
  return ObjectGroupWindow;
}

function CreateMasterDetailPanel(sysname, Param, callback) {
  CreateSimleGrid(sysname, Param, function (SimpleGrid) {
    var MasterDetailPanel = Ext.create("Ext.panel.Panel", {
      object_Caption: SimpleGrid.ObjectInitGrid.ObjectCaption,
      sysname: SimpleGrid.ObjectInitGrid.sysname,
      layout: 'border',
      bodyBorder: false,
      preventHeader: true, //убрать title
      defaults: {
        collapsible: true,
        split: true,
        bodyPadding: 0
      },
      ReloadDetailGrids: function () {
        var me = this;
        if ((me.masterGrid.getStore() != undefined) && (me.masterGrid.getStore().getCount() == 0)
                ) {
          if (me.tabpanelDetailGrids) {
            me.tabpanelDetailGrids.items.each(function (tab_, index) {
              if (tab_.DetailGrid) {
                //tab_.DetailGrid.ObjectInitGrid.joins = tab_.DetailGrid.getDetalJoins();
                tab_.DetailGrid.getStore().removeAll();
                tab_.DetailGrid.SetStateOperationBtn(false);
              }
            });
          }
        } else
        if ((me.masterGrid.getStore() != undefined) && (me.masterGrid.getStore().getCount() > 0)
                ) {
          if (me.tabpanelDetailGrids) {
            var activeTab = me.tabpanelDetailGrids.getActiveTab();
            if (activeTab) {
              var DetailGrid = activeTab.DetailGrid;
              if (DetailGrid) {
                DetailGrid.ObjectInitGrid.joins = DetailGrid.getDetalJoins();
                DetailGrid.getStore().loadPage(1);
              }
            }
          }
        }
      }
    });
    SimpleGrid.region = 'center';
    SimpleGrid.minHeight = 200;
    MasterDetailPanel.masterGrid = SimpleGrid;
    MasterDetailPanel.add(SimpleGrid);
    // MasterDetailPanel.setTitle(SimpleGrid.ObjectInitGrid.ObjectCaption);
    MasterDetailPanel.masterGrid.on('itemclick', function (t, record, eOpts) {
      MasterDetailPanel.ReloadDetailGrids();
    });
    MasterDetailPanel.masterGrid.on('GridDblClickRecord', function (t, record, eOpts) {
      MasterDetailPanel.ReloadDetailGrids();
    });
    MasterDetailPanel.masterGrid.on('GridUnSelectRecord', function (t, record, eOpts) {
      MasterDetailPanel.ReloadDetailGrids();
    });
    GridLink_class.GetLinkArray(SimpleGrid.ObjectInitGrid.id_object, function (response) {
      if (response != undefined) {
        if ((response.success === false) && (response.result == 're_connect')) {
          alert(response.msg);
          findFirstWindow().window.location.href = __first_page;
          return false;
        } else
        if (response.success == true) {
          var LinkArray = response.result;
          if (LinkArray.length > 0) {
//            var tabpanelDetailGrids = new Ext.tab.Panel({activeTab: 0, title: 'Связи объекта', region: 'south', height: Math.round(MasterDetailWindow.getHeight() / 2),
            MasterDetailPanel.tabpanelDetailGrids = new Ext.tab.Panel({activeTab: 0, title: 'Связи объекта', header: false, region: 'south', height: '50%',
//            MasterDetailPanel.tabpanelDetailGrids = new Ext.tab.Panel({activeTab: 0, header:false,region: 'south', height: '50%',
              minHeight: 200});
            MasterDetailPanel.tabpanelDetailGrids.on('tabchange', function (tabPanel, newCard, oldCard, eOpts) {
//перегрузить данные грида в этой табе
              MasterDetailPanel.ReloadDetailGrids();
            }, MasterDetailPanel);
            MasterDetailPanel.add(MasterDetailPanel.tabpanelDetailGrids);
            Ext.each(LinkArray, function (Link, index) {
              var cLink = Link;
              CreateSimleGrid(Link.sysname, {}, function (LinkGrid) {
                LinkGrid.LinkObject = cLink;
                LinkGrid.SelectFirstRecordOnLoad = false;//надо отключать у подчиненных гридов иначе на них прыгает фокус ввода
                LinkGrid.masterGrid = MasterDetailPanel.masterGrid;
                LinkGrid.ObjectInitGrid.joins = LinkGrid.getDetalJoins(); //что б по дефолту ничего не показывало пока в мастер грид не выбрана запись
                var tab = MasterDetailPanel.tabpanelDetailGrids.add({xtype: 'panel', layout: 'fit', title: cLink.short_name, items: [LinkGrid]});
                tab.DetailGrid = LinkGrid;
                var BtnShow = new Ext.Button({
                  text: 'Открыть в новом окне',
                  LinkGrid: LinkGrid,
                  handler: function (button) {
                    var _Param = {};
                    _Param.ExtFilterWhereCond = button.LinkGrid.getDetalJoins();
                    _Param.sysname = button.LinkGrid.ObjectInitGrid.sysname;
                    _Param.object_Caption = button.LinkGrid.ObjectInitGrid.ObjectCaption
                    _Param.HelpContext = Param.HelpContext;
                    ShowMasterDetailGridWindow(_Param);
                  }});
                var bbar = LinkGrid.getDockedItems('toolbar[dock="bottom"]')[0];
                if (bbar) {
                  bbar.insert(15, BtnShow);
                  bbar.insert(16, '-');
                }
                if ((MasterDetailPanel.tabpanelDetailGrids.items.length > 0) && (!MasterDetailPanel.tabpanelDetailGrids.getActiveTab())) {
                  MasterDetailPanel.tabpanelDetailGrids.setActiveTab(0);
                }
              });
            });
          }
          if (callback)
            callback(MasterDetailPanel);
        }
        else {
          Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения условий построения таблицы:' + response.msg);
        }
      } else {
        Ext.Msg.alert('Выполнение прервано', 'Ошибка получения условий построения таблицы');
      }
    });
    SimpleGrid.getStore().loadPage(1);
  });
}

function CreateSimleGrid(sysname, Param, callback) {
  //Ext.require('KOMETA.Grid.SimpleGrid');
  SimpleGrid_class.Create(sysname, Param, function (response) {
    if (response != undefined) {
      if ((response.success === false) && (response.result == 're_connect')) {
        alert(response.msg);
        findFirstWindow().window.location.href = __first_page;
        return false;
      } else
      if (response.success == true) {
        var ObjectInitGrid = response.result;
        Param.ObjectInitGrid = ObjectInitGrid;
        var SLVGrid = Ext.create('KOMETA.Grid.SimpleGrid', Param);


        if (callback)
          callback(SLVGrid);
        return true;
      } else {
        Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения условий построения таблицы:' + response.msg);
        return false;
      }
    } else {
      Ext.Msg.alert('Выполнение прервано ', 'Ошибка получения условий построения таблицы');
      return false;
    }
  });
}

function CreateCheckBoxGridWindow(Param) { //строит окно с одиночным гридом и чекбоксами для выбора нескольких значений
  //имя системного для store поля id задается в модели свойством idProperty по умолчанию id в нашем случае модели нет и имя этого поля поменять нельзя
  //поэтому договоримся что id резервированное имя
  //поиск записи в хранилище выполняется функциями getById() по полю заданному idProperty(id) и findRecord( fieldName, value, [startIndex], [anyMatch], [caseSensitive], [exactMatch] ) по любому полю
  //системное поле id формируется на сервере по принципу 'имя_ключево_гополя1:значение_клячевого_поля1;имя_ключево_гополя2:значение_клячевого_поля2' разделитель ';'
  //выбранные ключи лежат в гриде в объекте под названием selectedID
  //вида 'id_object:21':1
  //'id_object:25':1
  //'id_object:61':1
  //такую форму записи сделал для удобства обращения по ключам
  //ключами элементов этого объекта являются значения поля id хранилища грида
  //для сброса списка выделений надо присвоить selectedID={} пустой объект
  //Param  объект со свойствами параметрами функции
  var sysname = Param.sysname;
  var object_Caption = Param.object_Caption;
  var _HelpContext = Param.HelpContext;
  if (_HelpContext == undefined)
    _HelpContext = '';
  var win = findFirstWindow(),
          _width = Math.round(win.window.document.body.clientWidth / 6 * 5),
          _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
  var w = win.myDesktopApp.desktop.checkExist(sysname);
  if (w) {
    win.myDesktopApp.desktop.restoreWindow(w);
    return w;
  }
  var SLVWindow = win.myDesktopApp.desktop.createWindow({
    title: object_Caption,
    object_Caption: object_Caption,
    sysname: sysname,
    width: _width,
    height: _height,
    closable: true,
    HelpContext: 'kometa_select_valslv' + _HelpContext,
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
  SLVWindow.removeAll(true);
  Param.selType = 'checkboxmodel';
  Param.multiSelect = true;
  Param.selectedID = {};
  Param.SelectFirstRecordOnLoad = false;
  Param.Loaded_Flag = false;
  CreateSimleGrid(sysname, Param, function (SimpleGrid) {
    SLVWindow.add(SimpleGrid);
    SLVWindow.SLVGrid = SimpleGrid;
    SimpleGrid.rememberSelection = function (selModel, selectedRecords) {
      var me = this;
      if (!this.rendered || Ext.isEmpty(this.el) || !this.Loaded_Flag) {
        return;
      }
//сначала надо из списка выбранных убрать весь текущий набор данных
      var TempSelectedRecords = {};
      for (var i = 0; i < Object.keys(me.selectedID).length; i++) {
        var recordKey = Object.keys(me.selectedID)[i];
        var record = me.getStore().getById(recordKey);
        if (Ext.isEmpty(record)) { //такая не найдена
          TempSelectedRecords[recordKey] = me.selectedID[recordKey];
        }
      }
      delete this.selectedID;
      this.selectedID = TempSelectedRecords;
      //теперь в список выбранных добавить помеченные
      var selection = this.getSelectionModel().getSelection();
      for (var i = 0; i < selection.length; i++) {
        this.selectedID[selection[i].raw.id] = 1;
      }
      this.getView().saveScrollState();
    };
    SimpleGrid.refreshSelection = function () {
      var me = this;
      if (0 >= Object.keys(this.selectedID).length) {
        return;
      }
      var newRecordsToSelect = [];
      for (var i = 0; i < Object.keys(me.selectedID).length; i++) {
        var record = me.getStore().getById(Object.keys(me.selectedID)[i]);
        if (!Ext.isEmpty(record)) {
          newRecordsToSelect.push(record);
        }
      }
      this.getSelectionModel().select(newRecordsToSelect);
      //Ext.defer(me.getView().restoreScrollState(), 30, me); //выполнить через некоторое время
      this.getView().restoreScrollState();
    };
    SimpleGrid.LoadEvent = function () {
      this.Loaded_Flag = true;
    }
    SimpleGrid.beforeloadEvent = function (selModel, selectedRecords) {
      this.Loaded_Flag = false;
      this.rememberSelection(selModel, selectedRecords);
    }
    SimpleGrid.getStore().on('load', SimpleGrid.LoadEvent, SimpleGrid);
    SimpleGrid.getStore().on('beforeload', SimpleGrid.beforeloadEvent, SimpleGrid);
    SimpleGrid.getSelectionModel().on('selectionchange', SimpleGrid.rememberSelection, SimpleGrid);
    SimpleGrid.getView().on('refresh', SimpleGrid.refreshSelection, SimpleGrid);
    //SLVWindow.setTitle(SimpleGrid.ObjectInitGrid.ObjectCaption);
    if (SLVWindow.taskButton)
      SLVWindow.taskButton.setText(SimpleGrid.ObjectInitGrid.ObjectCaption);
    SimpleGrid.getStore().loadPage(1);
  });
  SLVWindow.addEvents('ValSlvSelected');
  SLVWindow.show();
  return SLVWindow;
}
