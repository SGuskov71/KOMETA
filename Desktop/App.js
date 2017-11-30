var CurModuleObj;
Ext.define('MyDesktop.App', {
  extend: 'Ext.ux.Desktop.App',
  requires: [
    'Ext.window.MessageBox',
    'Ext.ux.Desktop.ShortcutModel',
    'MyDesktop.TaskMenuModule',
    'MyDesktop.Settings'
  ],
  init: function () {
    // custom logic before getXYZ methods get called...

    this.callParent();
    // now ready...
  },
  getModules: function () {
    var res = [];
    if ((DesctopSettingsObject.AutorunItems != undefined) &&
            (DesctopSettingsObject.AutorunItems.length > 0)) {
      CurModuleObj = DesctopSettingsObject.AutorunItems[0];
      res.push(new MyDesktop.TaskMenuModule());
    }
    var length = DesctopSettingsObject.MenuObject.length;
    for (var i = 0; i < length; i++) {
      CurModuleObj = DesctopSettingsObject.MenuObject[i];
      res.push(new MyDesktop.TaskMenuModule());
    }
    return res;
  },
  getDesktopConfig: function () {
    var me = this, ret = me.callParent();
    var _wallpapers;
    var app_path = __first_page.substring(0, __first_page.lastIndexOf('/') + 1);
    //alert(app_path);
    if (DesctopSettingsObject.wallpaper != undefined)
      _wallpapers = DesctopSettingsObject.wallpaper;
    else
      _wallpapers = 'wallpapers/logo.png';
    return Ext.apply(ret, {
      //cls: 'ux-desktop-black',

      contextMenuItems: [
        {text: 'Изменить настройки', handler: me.onSettings, scope: me}
      ],
      shortcuts: Ext.create('Ext.data.Store', {
        model: 'Ext.ux.Desktop.ShortcutModel',
        data: DesctopSettingsObject.ShortcutObject
      }),
      //        wallpaper: 'wallpapers/Blue-Sencha.jpg',
      wallpaper: _wallpapers,
      wallpaperStretch: DesctopSettingsObject.wallpaperStretch,
      show_date_time: DesctopSettingsObject.show_date_time
    });
  },
  // config for the start menu
  getStartConfig: function () {
    var me = this, ret = me.callParent();
    return Ext.apply(ret, {
      //     title: 'Don Griffin',
      title: DesctopSettingsObject.UserName,
      iconCls: 'user',
      //height: 300,
      toolConfig: {
        //  width: 160,
        items: [
          '-',
//                    {
//                        text: 'Настройки',
//                        iconCls: 'settings',
//                        handler: me.onSettings,
//                        scope: me
//                    },
//                    '-',
          {
            text: 'Изменение пароля',
            iconCls: 'change_pwd',
            handler: changeUserPassword,
            scope: me
          },
          '-',
//                    {
//                        text: 'О системе',
//                        iconCls: 'info',
//                        handler: aboutSystem
//                    },
          {
            text: 'Выход из системы',
            iconCls: 'logout',
            handler: me.onLogout,
            scope: me
          }
//??
//          ,
//          '-',
//          {
//            text: 'Не трогать',
//            iconCls: 'logout',
//            handler: function () {
//              //RunFunctionInScript(_URLProjectRoot + 'Grid/Grid.js', 'ShowObjectGroupWindow', "{}");
//              RunFunctionInScript(_URLProjectRoot + 'Grid/Grid.js', 'ShowMasterDetailGridWindow', "{sysname:'sv_mb_object'}");
//              //RunFunctionInScript(_URLProjectRoot + 'Grid/Grid.js', 'SelectValSlv', "{sysname:'sv_mbr_report_template'}");
//            }
//          }
//??----
        ]
      }
    });
  },
  getTaskbarConfig: function () {
    var ret = this.callParent();
    var me = this;
    me.traffic_light = Ext.create('Ext.Button', {
      xtype: 'button',
      itemid: 'traffic_light',
      tooltip: 'Состояние сессии',
      iconCls: 'help',
      handler: function () {
        Ext.Ajax.request({
          url: _URLProjectRoot + '2gConnection.php',
          method: 'POST',
          success: function (response, options) {
          },
          failure: function (response, options) {
          }
        });
      }
      , flex: 1
    });
    return Ext.apply(ret, {
//      quickStart: [
//                {name: 'Accordion Window', iconCls: 'accordion', module: 'acc-win'},
//                {name: 'Grid Window', iconCls: 'icon-grid', module: 'grid-win'}
//      ],
      trayItems: [
        {
          xtype: 'button',
          tooltip: 'Настройки',
          iconCls: 'settings',
          handler: me.onSettings,
          scope: me
          , flex: 1
        },
        '-',
        {
          xtype: 'button',
          tooltip: 'Справка',
          iconCls: 'help',
          HelpContext: 'Content',
          handler: ShowHelp
          , flex: 1
        },
        me.traffic_light,
        '-',
        {xtype: 'trayclock', flex: 1}
      ]
    });
  },
  onLogout: function () {
    Ext.Msg.confirm('Выход из системы', 'Вы действительно хотите выйти из системы?', function (btn, text) {
      if (btn === 'yes') {
        window.onbeforeunload = null;
        window.location.href = '../logout.php';
      } else
        return false;
    });
  },
  onSettings: function () {
    var dlg = new MyDesktop.Settings({
      desktop: this.desktop
    });
    dlg.show();
  }
});
function CloseChangePasswordWindow() {

  wChangePassword.close();
  Ext.destroy(wChangePassword);
}

function changeUserPassword() {
  win = findFirstWindow();
  wChangePassword = win.myDesktopApp.desktop.createWindow({
    title: 'Изменение пароля',
    code: 'changeUserPassword',
    width: 350,
    height: 200,
    closable: false,
    autoScroll: true,
    constrainHeader: true,
    modal: true,
    items: [
      {
        xtype: 'textfield',
        name: 'password_old',
        padding: 10,
        inputType: 'password',
        fieldLabel: 'Старый пароль'
      },
      {
        xtype: 'textfield',
        name: 'password_new',
        padding: 10,
        inputType: 'password',
        fieldLabel: 'Новый пароль'
      },
      {
        xtype: 'textfield',
        name: 'password_new_rep',
        padding: 10,
        inputType: 'password',
        fieldLabel: 'Новый пароль (повтор)'
      }
    ],
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
            disabled: false,
            minWidth: 100,
            id: 'BtnRunReportOK',
            text: 'Изменить',
            handler: function () {
              // вызов процедуры изменения пароля
              if (wChangePassword.items.items[1].value != wChangePassword.items.items[2].value) {
                Ext.MessageBox.hide();
                Ext.MessageBox.alert("Ошибка: новый пароль не совпадет с повтором");
              }
              else if (wChangePassword.items.items[0].value == wChangePassword.items.items[1].value) {
                Ext.MessageBox.hide();
                Ext.MessageBox.alert("Ошибка: новый пароль совпадет со старым");
              }
              else
                Desktop_class.ChangePassword(wChangePassword.items.items[0].value
                        , wChangePassword.items.items[1].value, function (response) {
                  CloseChangePasswordWindow();
                  Ext.MessageBox.hide();
                  Ext.MessageBox.alert(response.msg);
                });
            }
          },
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Отмена',
            handler: function () {
              CloseChangePasswordWindow();
            }
          }
        ]
      }]
  });
  wChangePassword.show();
}

function aboutSystem() {
  var app_path = __first_page.substring(0, __first_page.lastIndexOf('/') + 1);
  buildW(app_path + 'about.html', 'О системе', true);
}