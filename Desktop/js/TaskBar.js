/*!
 * Ext JS Library 4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

/**
 * @class Ext.ux.desktop.TaskBar
 * @extends Ext.toolbar.Toolbar
 */
Ext.define('Ext.ux.Desktop.TaskBar', {
  // This must be a toolbar. we rely on acquired toolbar classes and inherited toolbar methods for our
  // child items to instantiate and render correctly.
  extend: 'Ext.toolbar.Toolbar',
  requires: [
    'Ext.button.Button',
    'Ext.resizer.Splitter',
    'Ext.menu.Menu',
    'Ext.ux.Desktop.StartMenu'
  ],
  alias: 'widget.taskbar',
  cls: 'ux-taskbar',
  /**
   * @cfg {String} startBtnText
   * The text for the Start Button.
   */
  startBtnText: 'Меню',
  initComponent: function () {
    var me = this;

    me.startMenu = new Ext.ux.Desktop.StartMenu(me.startConfig);


    // me.quickStart = new Ext.toolbar.Toolbar(me.getQuickStart());

    me.windowBar = new Ext.toolbar.Toolbar(me.getWindowBarConfig());

    me.tray = new Ext.toolbar.Toolbar(me.getTrayConfig());


    me.items = [
      {
        xtype: 'button',
        id: 'StartButton',
        cls: 'ux-start-button',
        iconCls: 'start-button-icon',
        menu: me.startMenu,
        menuAlign: 'bl-tl',
        text: me.startBtnText
      },
      //        me.quickStart,
      {
        xtype: 'splitter', html: '&#160;',
        height: 14, width: 2, // TODO - there should be a CSS way here
        cls: 'x-toolbar-separator x-toolbar-separator-horizontal'
      },
      me.windowBar,
      '-',
      me.tray
    ];

    me.callParent();
  },
  afterLayout: function () {
    var me = this;
    me.callParent();
    me.windowBar.el.on('contextmenu', me.onButtonContextMenu, me);
  },
  /**
   * This method returns the configuration object for the Quick Start toolbar. A derived
   * class can override this method, call the base version to build the config and
   * then modify the returned object before returning it.
   */
//  getQuickStart: function () {
//    var me = this, ret = {
//      minWidth: 20,
//      width: Ext.themeName === 'neptune' ? 70 : 60,
//      items: [],
//      enableOverflow: true
//    };
//
//    Ext.each(this.quickStart, function (item) {
//      ret.items.push({
//        tooltip: {text: item.name, align: 'bl-tl'},
//        //tooltip: item.name,
//        overflowText: item.name,
//        iconCls: item.iconCls,
//        module: item.module,
//        handler: me.onQuickStartClick,
//        scope: me
//      });
//    }
//            );
//
//    return ret;
//  },
  /**
   * This method returns the configuration object for the Tray toolbar. A derived
   * class can override this method, call the base version to build the config and
   * then modify the returned object before returning it.
   */
  getTrayConfig: function () {
    var ret = {
      items: this.trayItems
    };
    delete this.trayItems;
    return ret;
  },
  getWindowBarConfig: function () {
    return {
      flex: 1,
      cls: 'ux-desktop-windowbar',
      items: ['&#160;'],
      layout: {overflowHandler: 'Scroller'}
    };
  },
  getWindowBtnFromEl: function (el) {
    var c = this.windowBar.getChildByElement(el);
    return c || null;
  },
//  onQuickStartClick: function (btn) {
//    var module = this.app.getModule(btn.module),
//            window;
//
//    if (module) {
//      window = module.createWindow();
//      window.show();
//    }
//  },
  onButtonContextMenu: function (e) {

    var me = this, t = e.getTarget(), btn = me.getWindowBtnFromEl(t);
    if (btn) {
      e.stopEvent();
      me.windowMenu.theWin = btn.win;
      me.windowMenu.showBy(t);
    }
  },
  onWindowBtnClick: function (btn) {
    var me_d = myDesktopApp.desktop, activeWindow = me_d.getActiveWindow();
    if (activeWindow && (activeWindow.modal === true)) {
      return;
    }
    var win = btn.win;

    if (win.minimized || win.hidden) {
      btn.disable();
      win.show(null, function () {
        btn.enable();
      });
    } else if (win.active) {
      btn.disable();
      win.on('hide', function () {
        btn.enable();
      }, null, {single: true});
      win.minimize();
    } else {
      win.toFront();
    }
  },
  addTaskButton: function (win) {
    var config = {
      iconCls: win.iconCls,
      enableToggle: true,
      toggleGroup: 'all',
      width: 140,
      margins: '0 2 0 3',
      text: Ext.util.Format.ellipsis(win.title, 20),
      tooltip: win.title,
      listeners: {
        click: this.onWindowBtnClick,
        scope: this
      },
      win: win
    };

    var cmp = this.windowBar.add(config);
    cmp.toggle(true);
    return cmp;
  },
  removeTaskButton: function (btn) {
    var found, me = this;
    me.windowBar.items.each(function (item) {
      if (item === btn) {
        found = item;
      }
      return !found;
    });
    if (found) {
      me.windowBar.remove(found);
    }
    return found;
  },
  setActiveButton: function (btn) {
    if (btn) {
      btn.toggle(true);
    } else {
      this.windowBar.items.each(function (item) {
        if (item.isButton) {
          item.toggle(false);
        }
      });
    }
  }
});

/**
 * @class Ext.ux.desktop.TrayClock
 * @extends Ext.toolbar.TextItem
 * This class displays a clock on the toolbar.
 */
Ext.define('Ext.ux.Desktop.TrayClock', {
  extend: 'Ext.toolbar.TextItem',
  alias: 'widget.trayclock',
  cls: 'ux-desktop-trayclock',
  html: '&#160;',
//    timeFormat: 'g:i A',
  timeFormat: 'd/m/Y H:i',
  tpl: "{time}",
  initComponent: function () {
    var me = this;

    me.callParent();

    if (typeof (me.tpl) == 'string') {
      me.tpl = new Ext.XTemplate(me.tpl);
    }
  },
  afterRender: function () {
    var me = this;
    Ext.Function.defer(me.updateTime, 100, me);
    me.callParent();
  },
  onDestroy: function () {
    var me = this;

    if (me.timer) {
      window.clearTimeout(me.timer);
      me.timer = null;
    }

    me.callParent();
  },
  updateTime: function () {
    var me = this, time = Ext.Date.format(new Date(), me.timeFormat);
    var cls = 'traffic_light_green';
    Ext.Ajax.request({
      url: _URLProjectRoot + 'Desktop/GetTimeElapsedBackEnd.php',
      success: function (response, opts) {
        var txt = '';
        if (parseFloat(response.responseText) > 15) {
//          txt = '<span style=\'color: black; background:lightgreen\' >' + text + '</span>';
          cls = 'traffic_light_green';
        }
        else if (parseFloat(response.responseText) > 5) {
//          txt = '<span style=\'color: black; background:yellow\'>' + text + '</span>';
          cls = 'traffic_light_yellow';

        }

        else if (parseFloat(response.responseText) > 0) {
//          txt = '<span style=\'color: black; background:red\'>' + text + '</span>';
          cls = 'traffic_light_red';
        }
        else {
          window.onbeforeunload = null;
          findFirstWindow().window.location.href = __first_page;
        }
        var text = me.tpl.apply({time: time});
        if (me.lastText != text) {
//          me.setText(text + '(' + state + ')');
          me.lastText = text;
          var win = findFirstWindow();
          if (win.DesctopSettingsObject.show_date_time) {
            me.setText(text);
          }
          else {
            me.setText('');
          }
          win.myDesktopApp.traffic_light.setIconCls(cls);
        }
        me.timer = Ext.Function.defer(me.updateTime, 30000, me);

      },
      failure: function (response, opts) {
        me.timer = Ext.Function.defer(me.updateTime, 30000, me);
        return null;
      }
    });

  }
});
