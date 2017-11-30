/*!
 * Ext JS Library 4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

Ext.define('MyDesktop.Settings', {
  extend: 'Ext.window.Window',
  requires: [
    'Ext.tree.*',
    'Ext.data.*',
    'Ext.layout.container.HBox'
  ],
  uses: [
    'Ext.tree.Panel',
    'Ext.tree.View',
    'Ext.form.field.Checkbox',
    'Ext.layout.container.Anchor',
    'Ext.layout.container.Border',
    'Ext.ux.Desktop.Wallpaper',
    'MyDesktop.WallpaperModel'
  ],
  layout: 'fit',
  title: 'Настройки',
  HelpContext: 'kometa_setting_desktop',
  constrainHeader: true,
  modal: true,
  width: 740,
  height: 480,
  border: false,
  tools: [{
      type: 'help',
      qtip: 'Cправка',
      callback: ShowHelp
    }],
  initComponent: function () {
    var me = this;
    var group1 = this.id + '-ddgroup1';
    var group2 = this.id + '-ddgroup2';
    me.selected = me.desktop.getWallpaper();
    me.stretch = me.desktop.wallpaper.stretch;
    me.show_date_time = DesctopSettingsObject.show_date_time;
    me.AutorunShortcut = DesctopSettingsObject.Autorun;
    me.preview = Ext.create('widget.wallpaper');
    me.preview.setWallpaper(me.selected);
    //me.tree = 
    me.createTree(function (_tree) {
      var w = me.down('#DesktopWallpaper');
      w.add(_tree);
      //me.tree = _tree
    });
    me.buttons = [
      {text: 'OK', handler: me.onOK, scope: me},
      {text: 'Отмена', handler: me.close, scope: me}
    ];
    me.MenuTreeData = me.createMenuTreeData();
    me.ShortcutTreeData = me.createShortcutTreeData();
    me.MenuAutorunTreeData = me.createMenuTreeData();
    me.AutorunShortcutTreeData = me.createAutorunShortcutTreeData();
    me.items = [
      {
        xtype: 'tabpanel',
        activeTab: 0,
        defaults: {
          bodyPadding: 10
        },
        items: [{
            title: 'Оформление рабочего стола',
            layout: 'anchor',
            items: [{
                anchor: '0 -30',
                border: false,
                layout: 'border',
                itemId: 'DesktopWallpaper',
                items: [//me.tree,
                  {
                    xtype: 'panel',
                    title: 'Предварительный просмотр',
                    region: 'center',
                    layout: 'fit',
                    items: [me.preview]
                  }
                ]
              },
              {xtype: 'container',
                layout: 'hbox',
                items: [
                  {
                    xtype: 'checkbox',
                    boxLabel: 'Растянуть',
                    id: 'chkWallpaperStretch',
                    checked: me.stretch,
                    listeners: {
                      change: function (comp) {
                        me.stretch = comp.checked;
                      }
                    }
                  },
                  {
                    xtype: 'checkbox',
                    boxLabel: 'Показывать дату-время',
                    id: 'show_date_time',
                    checked: me.show_date_time,
                    listeners: {
                      change: function (comp) {
                        me.show_date_time = comp.checked;
                      }
                    }
                  }
                  //                , me.themes
                ]}
            ]
          },
          {
            title: 'Ярлыки',
            layout: {
              type: 'hbox',
              align: 'stretch'
            },
            items: [
              {
                title: 'Пункты меню',
                xtype: 'treepanel',
                itemId: 'ShortcutTreeAll',
                dockedItems: [
                  {
                    xtype: 'container',
                    dock: 'bottom',
                    width: 100,
                    title: '',
                    layout: {
                      type: 'fit',
                      align: 'stretch'
                    },
                    items: [
                      {
                        xtype: 'button',
                        itemId: 'btnAdd2Desktop',
                        disabled: true,
                        handler: function (button, e) {
                          var ShortcutTreeAll = me.down('#ShortcutTreeAll');
                          var ShortcutTree = me.down('#ShortcutTree');
                          var Node = ShortcutTreeAll.getSelectionModel().getSelection();
                          if (Node != undefined) {
                            Node = Node[0];
                            if ((Node != undefined) && (Node != ShortcutTreeAll.getRootNode()) &&
                                    (Node.raw.func_name != undefined) && (Node.raw.func_class_name != undefined)) {
                              var ConfObj = CopyObjectProps(Node.raw);
                              ShortcutTree.getRootNode().insertChild(0, ConfObj);
                            }
                            ShortcutTree.store.sync();
                          }
                        },
                        text: 'Добавить на рабочий стол'
                      }]
                  }],
                root: me.MenuTreeData,
                margin: '0 15 0 0',
                flex: 1,
                selModel: {listeners: {
                    selectionchange: function (model, record, eOpts) {
                      if ((record != undefined) && (record.length > 0) && (record[0].raw.func_name != undefined) && (record[0].raw.func_class_name != undefined))
                        me.down('#btnAdd2Desktop').enable()
                      else
                        me.down('#btnAdd2Desktop').disable();
                    }}},
                viewConfig: {
                  plugins: {
                    ptype: 'treeviewdragdrop',
                    ddGroup: group1,
                    appendOnly: false,
                    containerScroll: true
                  }
                }
              }, {
                title: 'Элементы рабочего стола',
                xtype: 'treepanel',
                id: 'ShortcutTree',
                dockedItems: [
                  {
                    xtype: 'container',
                    dock: 'bottom',
                    width: 100,
                    title: '',
                    layout: {
                      type: 'fit',
                      align: 'stretch'
                    },
                    items: [
                      {
                        xtype: 'button',
                        itemId: 'btnRemoveFromDesktop',
                        disabled: true,
                        handler: function (button, e) {
                          var ShortcutTree = me.down('#ShortcutTree');
                          var Node = ShortcutTree.getSelectionModel().getSelection();
                          if (Node != undefined) {
                            Node = Node[0];
                            if ((Node != undefined) && (Node != ShortcutTree.getRootNode())) {
                              var Node2Select = Node.previousSibling;
                              if (Node2Select == undefined) {
                                Node2Select = Node.parentNode;
                              }
                              try {
                                removeAllChildContainers(Node);
                                Node.removeAll(true);
                              } catch (e) {
                              } finally {
                                try {
                                  Node.remove(true);
                                } catch (e) {
                                }
                              }
                              ShortcutTree.store.sync();
                              ShortcutTree.getSelectionModel().select(Node2Select);
                            }
                          }
                        },
                        text: 'Удалить с рабочего стола'
                      }]
                  }],
                root: me.ShortcutTreeData,
                flex: 1,
                selModel: {listeners: {
                    selectionchange: function (model, record, eOpts) {
                      if ((record != undefined) && (record.length > 0) && (record[0].raw.func_name != undefined) && (record[0].raw.func_class_name != undefined))
                        me.down('#btnRemoveFromDesktop').enable()
                      else
                        me.down('#btnRemoveFromDesktop').disable();
                    }}},
                viewConfig: {
                  plugins: {
                    ptype: 'treeviewdragdrop',
                    ddGroup: group1,
                    appendOnly: true,
                    allowContainerDrop: false,
                    containerScroll: true
                  },
                  listeners: {
                    beforedrop: function (node, data, overModel, dropPosition, dropHandlers) {
                      if ((data != undefined) && (data.records.length > 0) && (data.records[0].raw != undefined) &&
                              (data.records[0].raw.func_name != undefined) && (data.records[0].raw.func_class_name != undefined)        
                              && (node.id.indexOf('record-root') >= 1)) {
                        dropHandlers.processDrop();
                      } else {
                        dropHandlers.cancelDrop();
                      }
                    }}
                }
              }
            ]
          },
          {
            title: 'Автозапуск',
            layout: 'anchor',
            items: [{
                anchor: '0 -30',
                layout: {
                  type: 'hbox',
                  align: 'stretch'
                },
                border: false,
                items: [
                  {
                    title: 'Пункты меню',
                    xtype: 'treepanel',
                    itemId: 'AutorunShortcutTreeAll',
                    dockedItems: [
                      {
                        xtype: 'container',
                        dock: 'bottom',
                        width: 100,
                        title: '',
                        layout: {
                          type: 'fit',
                          align: 'stretch'
                        },
                        items: [
                          {
                            xtype: 'button',
                            itemId: 'btnAdd2Autorun',
                            disabled: true,
                            handler: function (button, e) {
                              var AutorunShortcutTreeAll = me.down('#AutorunShortcutTreeAll');
                              var AutorunShortcutTree = me.down('#AutorunShortcutTree');
                              var Node = AutorunShortcutTreeAll.getSelectionModel().getSelection();
                              if (Node != undefined) {
                                Node = Node[0];
                                if ((Node != undefined) && (Node != AutorunShortcutTreeAll.getRootNode()) &&
                                        //(Node.raw.Command != undefined) &&
                                        (Node.raw.func_name != undefined) && (Node.raw.func_class_name != undefined)) {
                                  var ConfObj = CopyObjectProps(Node.raw);
                                  AutorunShortcutTree.getRootNode().insertChild(0, ConfObj);
                                }
                                AutorunShortcutTree.store.sync();
                              }
                            },
                            text: 'Добавить в автозапуск'
                          }]
                      }],
                    root: me.MenuAutorunTreeData,
                    margin: '0 15 0 0',
                    flex: 1,
                    selModel: {listeners: {
                        selectionchange: function (model, record, eOpts) {
                          if ((record != undefined) && (record.length > 0) && (record[0].raw.func_name != undefined) && (record[0].raw.func_class_name != undefined))
                            me.down('#btnAdd2Autorun').enable()
                          else
                            me.down('#btnAdd2Autorun').disable();
                        }}},
                    viewConfig: {
                      plugins: {
                        ptype: 'treeviewdragdrop',
                        ddGroup: group2,
                        appendOnly: false,
                        containerScroll: true
                      }
                    }
                  }, {
                    title: 'Элементы автозапуска',
                    xtype: 'treepanel',
                    id: 'AutorunShortcutTree',
                    dockedItems: [
                      {
                        xtype: 'container',
                        dock: 'bottom',
                        width: 100,
                        title: '',
                        layout: {
                          type: 'fit',
                          align: 'stretch'
                        },
                        items: [
                          {
                            xtype: 'button',
                            itemId: 'btnRemoveFromAutorun',
                            disabled: true,
                            handler: function (button, e) {
                              var AutorunShortcutTree = me.down('#AutorunShortcutTree');
                              var Node = AutorunShortcutTree.getSelectionModel().getSelection();
                              if (Node != undefined) {
                                Node = Node[0];
                                if ((Node != undefined) && (Node != AutorunShortcutTree.getRootNode())) {
                                  var Node2Select = Node.previousSibling;
                                  if (Node2Select == undefined) {
                                    Node2Select = Node.parentNode;
                                  }
                                  try {
                                    removeAllChildContainers(Node);
                                    Node.removeAll(true);
                                  } catch (e) {
                                  } finally {
                                    try {
                                      Node.remove(true);
                                    } catch (e) {
                                    }
                                  }
                                  AutorunShortcutTree.store.sync();
                                  AutorunShortcutTree.getSelectionModel().select(Node2Select);
                                }
                              }
                            },
                            text: 'Удалить из автозапуска'
                          }]
                      }],
                    root: me.AutorunShortcutTreeData,
                    flex: 1,
                    selModel: {listeners: {
                        selectionchange: function (model, record, eOpts) {
                          if ((record != undefined) && (record.length > 0) && (record[0].raw.func_name != undefined) && (record[0].raw.func_class_name != undefined))
                            me.down('#btnRemoveFromAutorun').enable()
                          else
                            me.down('#btnRemoveFromAutorun').disable();
                        }}},
                    viewConfig: {
                      plugins: {
                        ptype: 'treeviewdragdrop',
                        ddGroup: group2,
                        appendOnly: true,
                        allowContainerDrop: false,
                        containerScroll: true
                      },
                      listeners: {
                        beforedrop: function (node, data, overModel, dropPosition, dropHandlers) {
                          if ((data != undefined) && (data.records.length > 0) && (data.records[0].raw != undefined) &&
//                                  (data.records[0].raw.Command != undefined) && (node.dataset.recordid == 'root')) {
                                   (data.raw.func_name != undefined) && (data.raw.func_class_name != undefined) && (node.id.indexOf('record-root') >= 1)) {
                            dropHandlers.processDrop();
                          } else {
                            dropHandlers.cancelDrop();
                          }
                        }}
                    }
                  }
                ]
              }, {
                xtype: 'checkbox',
                boxLabel: 'Автозапуск',
                id: 'chkAutorun',
                checked: me.AutorunShortcut,
                listeners: {
                  change: function (comp) {
                    me.AutorunShortcut = comp.checked;
                  }
                }
              }
            ]
          }]
      }];
    me.callParent();
//    me.themes = me.createThemeList(DesctopSettingsObject.theme);

  },
  createSubMenuTreeData: function (MenuObjectItem, MenuTreeDataItem) {
    var me = this;
    var length = MenuObjectItem.length;
    for (var i = 0; i < length; i++) {
      var n = MenuTreeDataItem.children.push({text: MenuObjectItem[i].Caption,Caption: MenuObjectItem[i].Caption, expanded: false
        , func_name: MenuObjectItem[i].func_name, code: MenuObjectItem[i].code,func_class_name: MenuObjectItem[i].func_class_name,param_list: MenuObjectItem[i].param_list
        , iconCls: MenuObjectItem[i].iconCls 
        , code_help: MenuObjectItem[i].code_help, children: []});
      if ((MenuObjectItem[i].ChildMenu != undefined) && (MenuObjectItem[i].ChildMenu.length > 0)) {
        me.createSubMenuTreeData(MenuObjectItem[i].ChildMenu, MenuTreeDataItem.children[n - 1]);
      }
    }
  },
  createMenuTreeData: function () {
    var me = this;
    var MenuObject = DesctopSettingsObject.MenuObject;
    var result = {text: 'Меню', expanded: true, children: []};
    var length = MenuObject.length;
    for (var i = 0; i < length; i++) {
      var n = result.children.push({text: MenuObject[i].Caption, expanded: false
        , code: MenuObject[i].code
        , Caption: MenuObject[i].Caption
        , iconCls: MenuObject[i].iconCls
        , func_name: MenuObject[i].func_name,func_class_name: MenuObject[i].func_class_name,param_list: MenuObject[i].param_list
        , code_help: MenuObject[i].code_help
        , children: []});
      if ((MenuObject[i].ChildMenu != undefined) && (MenuObject[i].ChildMenu.length > 0)) {
        me.createSubMenuTreeData(MenuObject[i].ChildMenu, result.children[n - 1]);
      }
    }
    return result;
  },
  createShortcutTreeData: function () {
    var me = this;
    var ShortcutObject = DesctopSettingsObject.ShortcutObject;
    var result = {text: 'Ярлыки', expanded: true, children: []};
    if (ShortcutObject != undefined) {
      var length = ShortcutObject.length;
      for (var i = 0; i < length; i++)
        if (ShortcutObject[i] != undefined) {
          result.children.push({text: ShortcutObject[i].Caption
            , expanded: false, Caption: ShortcutObject[i].Caption, code: ShortcutObject[i].code
            , iconCls: ShortcutObject[i].iconCls, func_name: ShortcutObject[i].func_name
            , func_class_name: ShortcutObject[i].func_class_name
            , param_list: ShortcutObject[i].param_list
            , code_help: ShortcutObject[i].code_help
            , children: []});
        }
    }
    return result;
  },
  createAutorunShortcutTreeData: function () {
    var me = this;
    var AutorunItems = DesctopSettingsObject.AutorunItems;
    var result = {text: 'Элементы автозапуска', expanded: true, children: []};
    if (AutorunItems != undefined) {
      var length = AutorunItems.length;
      for (var i = 0; i < length; i++)
        if (AutorunItems[i] != undefined) {
          var n = result.children.push({text: AutorunItems[i].Caption, expanded: false
            , Caption: AutorunItems[i].Caption, code: AutorunItems[i].code, iconCls: AutorunItems[i].iconCls
            , func_name: AutorunItems[i].func_name
            , func_class_name: AutorunItems[i].func_class_name
            , param_list: AutorunItems[i].param_list
            , code_help: AutorunItems[i].code_help
            , children: []});
        }
    }
    return result;
  },
  createTree: function (callback) {
    var me = this;
    function child(img) {
      return {img: img, text: me.getTextOfWallpaper(img), iconCls: '', leaf: true};
    }
    Desktop_class.getDesktopWallpapers(function (response, option) {
      var Wallpaper = response;
      //            var tree = new Ext.tree.Panel({
      me.tree = new Ext.tree.Panel({
        title: 'Фон рабочего стола',
        rootVisible: false,
        lines: false,
        autoScroll: true,
        width: 170,
        region: 'west',
        split: true,
        minWidth: 100,
        listeners: {
          afterrender: {fn: me.setInitialSelection, delay: 100},
          select: me.onSelect,
          scope: me
        },
        store: new Ext.data.TreeStore({
          model: 'MyDesktop.WallpaperModel',
          root: {
            text: 'Wallpaper',
            expanded: true,
            children: Wallpaper
          }
        })
      });
      if (callback)
        callback(me.tree);
    });
  },
//  createThemeList: function (val) {
//    var me = this;
//
//    if (val == undefined)
//      val = 'classic';
//
//    var request = new XMLHttpRequest();
//    request.open('GET', _URLProjectRoot + 'Desktop/getDesktopThemes.php', false);
//    request.send(null);
//    if (request.status === 200) {
//      var Data = Ext.decode(request.responseText);
//    }
//    var ComboStore = Ext.create('Ext.data.ArrayStore', {
//      fields: ['id', 'name'],
//      autoLoad: false,
//      editable: false
//    });
//    if (Data != undefined)
//      ComboStore.loadData(Data);
//
//    var themes = new Ext.form.ComboBox({
//      padding: '0 10 0 10',
//      itemId: 'ThemesList',
//      fieldLabel: 'Тема',
//      editable: false,
//      displayField: 'name',
//      queryMode: 'local',
//      valueField: 'id',
//      value: val,
//      store: ComboStore
//    });
//    return themes;
//  },
  getTextOfWallpaper: function (path) {
    var text = path, slash = path.lastIndexOf('/');
    if (slash >= 0) {
      text = text.substring(slash + 1);
    }
    var dot = text.lastIndexOf('.');
    text = Ext.String.capitalize(text.substring(0, dot));
    text = text.replace(/[-]/g, ' ');
    return text;
  },
  onOK: function () {
    var me = this;
    if (me.selected) {
      me.desktop.setWallpaper(me.selected, me.stretch);
    }
    DesctopSettingsObject.wallpaper = me.selected;
    DesctopSettingsObject.wallpaper = me.selected;
    DesctopSettingsObject.wallpaperStretch = me.stretch;
    DesctopSettingsObject.show_date_time = me.show_date_time;
    DesctopSettingsObject.Autorun = me.AutorunShortcut;
//    DesctopSettingsObject.theme = me.themes.value;
    var _tree = Ext.getCmp('ShortcutTree');
    if (DesctopSettingsObject.ShortcutObject == undefined)
      DesctopSettingsObject.ShortcutObject = [];
    else
      DesctopSettingsObject.ShortcutObject.length = 0;
    var length = _tree.store.tree.root.childNodes.length;
    for (var i = 0; i < length; i++) {
      _tree.store.tree.root.childNodes[i].raw.iconClsLarge = _tree.store.tree.root.childNodes[i].raw.iconCls + '-large';
      _tree.store.tree.root.childNodes[i].raw.id = 'Shortcut_' + i;
      _tree.store.tree.root.childNodes[i].raw.isMenuItem = false;
      DesctopSettingsObject.ShortcutObject.push(_tree.store.tree.root.childNodes[i].raw);
    }
    var _tree = Ext.getCmp('AutorunShortcutTree');
    if (DesctopSettingsObject.AutorunItems == undefined)
      DesctopSettingsObject.AutorunItems = [];
    else
      DesctopSettingsObject.AutorunItems.length = 0;
    var length = _tree.store.tree.root.childNodes.length;
    for (var i = 0; i < length; i++) {
      var n = DesctopSettingsObject.AutorunItems.push(_tree.store.tree.root.childNodes[i].raw);
      DesctopSettingsObject.AutorunItems[n - 1].isMenuItem = false;
    }

    //сохраняю
    Desktop_class.SaveDesctopSettingsObject(DesctopSettingsObject, function (response, options) {
      var JSON_Result = response;
      if ((JSON_Result.success === false) && (JSON_Result.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', JSON_Result.msg);
                                window.onbeforeunload = null;    
        findFirstWindow().window.location.href = __first_page;
        return;
      }
    });
    me.destroy();
  },
  onSelect: function (tree, record) {
    var me = this;
    if (record.data.img) {
      var app_path = __first_page.substring(0, __first_page.lastIndexOf('/') + 1);
      me.selected = app_path + 'wallpapers/' + record.data.img;
    } else {
      me.selected = Ext.BLANK_IMAGE_URL;
    }

    me.preview.setWallpaper(me.selected);
  },
  setInitialSelection: function () {
    var s = this.desktop.getWallpaper();
    if (s) {
      var path = '/Wallpaper/' + this.getTextOfWallpaper(s);
      this.tree.selectPath(path, 'text');
    }
  }
});
