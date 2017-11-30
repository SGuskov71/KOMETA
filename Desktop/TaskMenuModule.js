
function PushMenuItems(_Menu, _ItemsObject, _Context) {
  var length = _ItemsObject.length;
  for (var i = 0; i < length; i++) {
    if ((_ItemsObject[i].ChildMenu != undefined) && (_ItemsObject[i].ChildMenu.length > 0)) {
      _Menu.items.push({
        text: _ItemsObject[i].Caption,
        iconCls: _ItemsObject[i].iconCls,
        handler: function () {
          return false;
        },
        menu: {
          items: []
        }
      });
      PushMenuItems(_Menu.items[i].menu, _ItemsObject[i].ChildMenu, _Context);
    } else {
      _Menu.items.push({
        text: _ItemsObject[i].Caption,
        iconCls: _ItemsObject[i].iconCls,
        handler: function (x) {
          return function () {
            win = findFirstWindow();
            activeW = win.myDesktopApp.desktop.getActiveWindow();
            var xx = win.document.body.clientWidth;
            var yy = 0;
            if (activeW != undefined) {
              xx = activeW.x;
              yy = activeW.y;
              xx -= 20;
              yy += 20;
            }

            return TaskMenuCreateWindow(_Context, _ItemsObject[x], xx, yy);
          };
        }(i)
      });
    }
  }
}

function TaskMenuCreateWindow(_Context, MenuObjectItem, _Left, _Top, _width, _height) {
  win = findFirstWindow();
  activeW = win.myDesktopApp.desktop.getActiveWindow();
  if ((activeW != undefined) && (activeW.modal == true)) {
    Ext.MessageBox.alert('Выполнение операции', 'Для выполнения операции закройте модальное окно');
    return;
  }
  Run_operation(null, MenuObjectItem, _Left, _Top, _width, _height);
}

Ext.define('MyDesktop.TaskMenuModule', {
  extend: 'Ext.ux.Desktop.Module',
  init: function () {
    var me = this;
    var MenuObject = CurModuleObj;
    if ((MenuObject.isMenuItem != undefined) && MenuObject.isMenuItem) {
      if (MenuObject != undefined) {
        if ((MenuObject.ChildMenu != undefined) && (MenuObject.ChildMenu.length > 0)) {
          this.launcher = {
            text: MenuObject.Caption,
            iconCls: MenuObject.iconCls,
            handler: function () {
              return false;
            },
            menu: {
              items: []
            }
          };
          PushMenuItems(me.launcher.menu, MenuObject.ChildMenu, me);
        } else {
          this.launcher = {
            text: MenuObject.Caption,
            iconCls: MenuObject.iconCls,
            handler: function () {
              return TaskMenuCreateWindow(me, MenuObject);
            }
          };
        }
      }
    }
  }});