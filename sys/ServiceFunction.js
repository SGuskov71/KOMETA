// инвертировать цвет
function invert(rgb) {
  //rgb = Array.prototype.join.call(arguments).match(/(-?[0-9\.]+)/g);
  var result = '';
  for (var i = 0; i < rgb.length; i++) {
    hexValue = 15 - parseInt(rgb[i], 16);
    result = result + hexValue.toString(16);
  }
  return result;
}

function Run_operation(Grid, Operation, _Left, _Top, _width, _height) {
  var FuncName = Operation.func_name;
  if (Operation.object_Caption == undefined)
    Operation.object_Caption = Operation.Caption;
  var FuncClassName = 'KOMETA.Operation.' + Operation.func_class_name;
  if (FuncName && Operation.func_class_name) {
    var Scrpt = Ext.create(FuncClassName, {});
    if (Scrpt) {
      if (Scrpt[FuncName])
        Scrpt[FuncName](Grid, Operation);
      else
        Ext.MessageBox.alert('Функция ' + FuncClassName + '.' + FuncName + ' не определена');
    }
    else
      Ext.MessageBox.alert('Класс операций ' + FuncClassName + ' не определен');
  }


}

function dhtmlLoadScript(url) //динамическая загрузка скрипта
{
  var e = document.createElement("script");
  e.src = url;
  e.type = "text/javascript";
  document.getElementsByTagName("head")[0].appendChild(e);
}

function CSSLoad(file) {//динамическая загрузка файла стилей
  var link = document.createElement("link");
  link.setAttribute("rel", "stylesheet");
  link.setAttribute("type", "text/css");
  link.setAttribute("href", file);
  document.getElementsByTagName("head")[0].appendChild(link)
}

function internalRunFunctionInScript(FuncText, ReturnObj) {
  var FuncReturn = eval(FuncText);
  ReturnObj.fireEvent('RunFunctionInScript_Return', FuncReturn);
}
function RunFunctionInScript(ScriptName, FuncName, FuncParam) { //динамически загружает скрипт и вызывает из него функцию с параметрами
//FuncParam объект, полями которого являются параметы вызываеиой функции
  var ReturnObj = Ext.create("Ext.util.Observable", {}); //возвращает объект с ожиданием событи выполнения функции куда передается возвращаемое значение функции
  ReturnObj.addEvents('RunFunctionInScript_Return'); //предотвращает повторную загрузку скрипта
  var FuncText = FuncName + '(' + FuncParam + ')';
  if (window[FuncName] == undefined) {
    Ext.Loader.loadScriptFile(ScriptName
            , function () {
              // internalRunFunctionInScript(FuncText, ReturnObj);
              Ext.callback(internalRunFunctionInScript, this, [FuncText, ReturnObj], 10);
            }, function () {
      Ext.MessageBox.alert('Ошибка', "Ошибка загрузки файла: " + ScriptName);
    });
  } else {
    Ext.callback(internalRunFunctionInScript, this, [FuncText, ReturnObj], 10);
  }
  return ReturnObj;
}

function findFirstWindow()
{
  var w = window;
  while ((w.isDesktopWindow != true) && (w.parent != undefined) && (w.parent != w)) {
    w = w.parent;
  }
  if ((w != undefined) && (w.buildW != undefined))
    return w;
  else
    return window;
}

var ShowHelp = function (panel, tool, event) {
  win = findFirstWindow();
  var ScriptName = _URLProjectRoot + 'Help/Help.js';
  Ext.Loader.loadScript({url: ScriptName
    , onLoad: function () {
      win.ShowHelpWindow(panel.HelpContext);
    }, onError: function () {
      Ext.MessageBox.alert('Ошибка', "Ошибка загрузки файла: " + ScriptName);
    }});
};
function buildW_desktop(url, wtitle, _wconCls, _width, _height, _WindowModal, _HelpContext) {
  if (isMobile.SenchaTouchSupported()) {
    window.location.href = url;
  }
  else {
    var win = findFirstWindow();
    if (_HelpContext == undefined)
      _HelpContext = -1;
    if (_width == undefined)
      _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
    if (_height == undefined)
      _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
    // востанавливаем настройки окон

    Ext.MessageBox.wait({
      msg: 'Пожалуйста, подождите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Desktop_class.GetWindowPosition(url, function (response, options) {
      Ext.MessageBox.hide();
      var result = response;
      if ((result.success === false) && (result.result == 're_connect')) {
        Ext.MessageBox.alert(result.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (result.success) {
        var winpos = result.result;
        if ((winpos.x < 0) || (winpos.x + winpos.width > win.document.body.clientWidth)) {
          winpos.x = 10;
          winpos.width = _width;
        }
        if ((winpos.y < 0) || ((winpos.y + winpos.height) > win.document.body.clientHeight) || ((winpos.height) < (_height / 5))) {
          winpos.y = 0;
          winpos.height = _height;
        }
        win = findFirstWindow().myDesktopApp.desktop.createWindow({
          HelpContext: _HelpContext,
          code: url,
          title: wtitle,
          x: winpos.x,
          y: winpos.y,
          width: winpos.width,
          height: winpos.height,
          html: '<iframe src="' + url + '" width="100%" height="100%" style="background-color: white;" ></iframe>',
          iconCls: _wconCls,
          animCollapse: false,
          constrainHeader: true,
          modal: _WindowModal,
          tools: [
            {
              type: 'help',
              qtip: 'Справка',
              callback: ShowHelp
            },
            {
              type: 'refresh',
              qtip: 'Обновить',
              callback: function (panel, tool, event) {
                panel.update('<iframe src="' + panel.code + '" width="100%" height="100%"  style="background-color: white;"></iframe>');
              }
            }
          ]
        });
        win.show();
        return win;
      }
    });
  }
}

function buildW_desktop_print(url, wtitle, _wconCls, _width, _height, _HelpContext) {
  if (_HelpContext == undefined)
    _HelpContext = -1;
  win = findFirstWindow();
  if (_width == undefined)
    _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
  if (_height == undefined)
    _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
// востанавливаем настройки окон
  Ext.MessageBox.wait({
    msg: 'Пожалуйста, подождите...',
    width: 300,
    wait: true,
    waitConfig: {interval: 100}
  });
  Desktop_class.GetWindowPosition(url, function (response, options) {
    Ext.MessageBox.hide();
    var result = Ext.JSON.decode(response.responseText);
    if (result.success) {
      var winpos = Ext.JSON.decode(result.result);
      if ((winpos.x < 0) || (winpos.x + winpos.width > win.document.body.clientWidth)) {
        winpos.x = 10;
        winpos.width = _width;
      }
      if ((winpos.y < 0) || ((winpos.y + winpos.height) > win.document.body.clientHeight) || ((winpos.height) < (_height / 5))) {
        winpos.y = 0;
        winpos.height = _height;
      }
      win = findFirstWindow().myDesktopApp.desktop.createWindow({
        HelpContext: _HelpContext,
        code: url,
        title: wtitle,
        x: winpos.x,
        y: winpos.y,
        width: winpos.width,
        height: winpos.height,
        html: '<iframe src="' + url + '" width="100%" height="100%"  style="background-color: white;"></iframe>',
        iconCls: _wconCls,
        animCollapse: false,
        constrainHeader: true,
        tools: [
          {
            type: 'help',
            qtip: 'Справка',
            callback: ShowHelp
          },
          {
            type: 'refresh',
            qtip: 'Обновить',
            callback: function (panel, tool, event) {
              panel.update('<iframe src="' + panel.code + '" width="100%" height="100%" ></iframe>');
            }
          }
        ]
      });
      win.show();
      return win;
    }
  });
}

function buildW_desktop_report_print(code, _html, wtitle, _wconCls, _HelpContext, callback) {
  if (_HelpContext == undefined)
    _HelpContext = -1;
  var modal = false;
  if (callback)
    modal = true;
  win = findFirstWindow();
  _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
  _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
// востанавливаем настройки окон
  Desktop_class.GetWindowPosition(code, function (response, options) {
    var result = response;
    if (result.success) {
      var winpos = result.result;
      if ((winpos.x < 0) || (winpos.x + winpos.width > win.document.body.clientWidth)) {
        winpos.x = 10;
        winpos.width = _width;
      }
      if ((winpos.y < 0) || ((winpos.y + winpos.height) > win.document.body.clientHeight) || ((winpos.height) < (_height / 5))) {
        winpos.y = 0;
        winpos.height = _height;
      }
      win = findFirstWindow().myDesktopApp.desktop.createWindow({
        HelpContext: _HelpContext,
        code: code,
        title: wtitle,
        modal: modal,
        x: winpos.x,
        y: winpos.y,
        width: winpos.width,
        height: winpos.height,
        html: _html,
        autoScroll: true,
        iconCls: _wconCls,
        animCollapse: false,
        constrainHeader: true,
        tools: [
          {
            type: 'print',
            qtip: 'Печать',
            callback: function (panel) {
              panel.layout.innerCt.dom.firstChild.contentWindow.print();
            }},
          {
            type: 'help',
            qtip: 'Справка',
            callback: ShowHelp
          }],
        listeners: {
          destroy: function (panel, eOpts) {
            if (callback)
              callback();
          }
        }
      });
      win.show();
      return win;
    }
  });
}

function buildW(url, wtitle, wmodal, _width, _height, _HelpContext) {
  win = findFirstWindow();
  if (_width == undefined)
    _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
  if (_height == undefined)
    _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
  win.myDesktopApp.desktop.createWindow({
    HelpContext: _HelpContext,
    title: wtitle,
    modal: wmodal,
    html: '<iframe src="' + url + '" width="100%" height="100%" ></iframe>',
    x: 0,
    y: 0,
    width: _width,
    height: _height,
    autoHeight: true,
    autoScroll: false,
    maximizable: true,
    //  bodyPadding: '10px',
    bodyStyle: 'background-color:#fff',
    closeAction: 'close',
    tools: [{
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      },
      {
        type: 'refresh',
        qtip: 'Обновить',
        callback: function (panel, tool, event) {
          panel.update('<iframe src="' + panel.code + '" width="100%" height="100%" ></iframe>');
        }
      }]
  }).show();
}

function buildW_print(url, wtitle, wmodal, _width, _height, _HelpContext) {
  win = findFirstWindow();
  if (_width == undefined)
    _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
  if (_height == undefined)
    _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
  win.myDesktopApp.desktop.createWindow({
    HelpContext: _HelpContext,
    title: wtitle,
    modal: wmodal,
    html: '<iframe src="' + url + '" width="100%" height="100%" ></iframe>',
    x: 0,
    y: 0,
    width: _width,
    height: _height,
    autoHeight: true,
    autoScroll: false,
    maximizable: true,
    //  bodyPadding: '10px',
    bodyStyle: 'background-color:#fff',
    closeAction: 'close',
    tools: [{
        type: 'print',
        qtip: 'Печать',
        callback: function (panel) {
          panel.layout.innerCt.dom.firstChild.contentWindow.print();
        }},
      {
        type: 'help',
        qtip: 'Справка',
        callback: ShowHelp
      },
      {
        type: 'refresh',
        qtip: 'Обновить',
        callback: function (panel, tool, event) {
          panel.update('<iframe src="' + panel.code + '" width="100%" height="100%" ></iframe>');
        }
      }]
  }).show();
}

function createCSSSelector(selector, style) { //динамически подгружает новый селектор стилей в дом
  if (!document.styleSheets) {
    return;
  }
  if (document.getElementsByTagName("head").length == 0) {
    return;
  }
  var styleSheet;
  var mediaType;
  if (document.styleSheets.length > 0) {
    for (i = 0; i < document.styleSheets.length; i++) {
      if (document.styleSheets[i].disabled) {
        continue;
      }
      var media = document.styleSheets[i].media;
      mediaType = typeof media;
      if (mediaType == "string") {
        if (media == "" || (media.indexOf("screen") != -1)) {
          styleSheet = document.styleSheets[i];
        }
      } else if (mediaType == "object") {
        if ((media.mediaText == undefined) || (media.mediaText == "") || (media.mediaText.indexOf("screen") != -1)) {
          styleSheet = document.styleSheets[i];
        }
      }
      if (typeof styleSheet != "undefined") {
        break;
      }
    }
  }
  if (typeof styleSheet == "undefined") {
    var styleSheetElement = document.createElement("style");
    styleSheetElement.type = "text/css";
    document.getElementsByTagName("head")[0].appendChild(styleSheetElement);
    for (i = 0; i < document.styleSheets.length; i++) {
      if (document.styleSheets[i].disabled) {
        continue;
      }
      styleSheet = document.styleSheets[i];
    }
    var media = styleSheet.media;
    mediaType = typeof media;
  }
  if (mediaType == "string") {
    for (i = 0; i < styleSheet.rules.length; i++) {
      if (styleSheet.rules[i].selectorText &&
              styleSheet.rules[i].selectorText.toLowerCase() == selector.toLowerCase()) {
        styleSheet.rules[i].style.cssText = style;
        return;
      }
    }
    styleSheet.addRule(selector, style);
  } else if (mediaType == "object") {
    for (i = 0; i < styleSheet.cssRules.length; i++) {
      if (styleSheet.cssRules[i].selectorText &&
              styleSheet.cssRules[i].selectorText.toLowerCase() == selector.toLowerCase()) {
        styleSheet.cssRules[i].style.cssText = style;
        return;
      }
    }

    var ruleSet = styleSheet.cssRules || styleSheet.rules,
            index = ruleSet.length;
    if (styleSheet.insertRule)
      styleSheet.insertRule(selector + "{" + style + "}", index);
    else if (styleSheet.addRule)
      styleSheet.addRule(selector, style);
  }
}

function htmlspecialchars(html) {
  if (html == undefined)
    html = '';
  // Сначала необходимо заменить & 
  html = html.replace(/&/g, "&amp;");
  // А затем всё остальное в любой последовательности 
  html = html.replace(/</g, "&lt;");
  html = html.replace(/>/g, "&gt;");
  html = html.replace(/"/g, "&quot;");
  // Возвращаем полученное значение 
  return html;
}

function CloseWindow(_Win) {
  _Win.close();
  Ext.destroy(_Win);
}

function trim(str, chr) {
  if (str != undefined) {
    var rgxtrim = (!chr) ? new RegExp('^\\s+|\\s+$', 'g') : new RegExp('^' + chr + '+|' + chr + '+$', 'g');
    str = str + '';
    return str.replace(rgxtrim, '');
  } else
    return '';
}

function isArray(o) { //проверяет тип объекта на тип массив
  return Object.prototype.toString.call(o) === '[object Array]';
}

function CopyObjectProps(Obj) {// копирует объект со всеми вложенными объектными свойствами и массивами
  var result = null;
  if (isArray(Obj)) {
    result = [];
    Ext.each(Obj, function (item) {
      if (typeof item == "object") {
        result.push(CopyObjectProps(item));
      } else {
        result.push(item);
      }
    });
  } else {
    result = new Object();
    for (var prop in Obj) {
      if (Obj[prop] == undefined) {
        result[prop] = null;
      } else
      if (typeof Obj[prop] == "object") {
        result[prop] = CopyObjectProps(Obj[prop]);
      } else {
        result[prop] = Obj[prop];
      }
    }
  }
  return result;
}

function findComponentByElement(el) { //должна находить среди объектов  Ext.ComponentManager соотвествующий объект модели браузера DOM т.е. преобразует Дом объект в компонент ExtJS
  var topmost = document.body,
          target = Ext.getDom(el),
          cmp;
  while (target && target.nodeType === 1 && target !== topmost) {
    cmp = Ext.getCmp(target.id);
    if (cmp) {
      return cmp;
    }
    target = target.parentNode;
  }
  return null;
}

//var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

var isMobile = {
  Android: function () {
    return navigator.userAgent.match(/Android/i);
  },
  BlackBerry: function () {
    return navigator.userAgent.match(/BlackBerry/i);
  },
  iOS: function () {
    return navigator.userAgent.match(/iPhone|iPad|iPod/i);
  },
  Opera: function () {
    return navigator.userAgent.match(/Opera Mini/i);
  },
  Windows: function () {
    return navigator.userAgent.match(/IEMobile/i) || navigator.userAgent.match(/WPDesktop/i);
  },
  any: function () {
    return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
  },
  SenchaTouchSupported: function () {
    //return true;
    return (isMobile.Android() || isMobile.iOS());
  }
};

function SetParamValuesAndRun(ArrayParam, ParamValuesArray, ObjCode, CallBackFunction, ParamObject) {
  var CallBackParam = ParamObject;
  if ((ArrayParam != undefined) && (ArrayParam.length > 0)) {
    //в значения парам ставлю сначала знач по умолчанию а потом из переданного массива
    var ExistsInterractiveParam = false;
    Ext.each(ArrayParam, function (par) {
      try {
        par.Value = eval(par.ParamDefaultValue);
      } catch (e) {
        Ext.MessageBox.alert('Ошибка', e.name)
      } finally {
      }

      if (par.ParamInterractive == true)
        ExistsInterractiveParam = true;
      for (var key in ParamValuesArray) {
        if (par.ParamCode == key) {
          par.Value = ParamValuesArray[key];
          break;
        }
      }
    });
    //диалог ввода параметров
    if (ExistsInterractiveParam == true) {
      if (!isMobile.SenchaTouchSupported()) {
        var wp = Ext.create("Params.view.InputInterractiveParams", {ArrayInterractiveParam: ArrayParam});
        wp.addEvents('BtnOk');
        wp.addListener('BtnOk', function (ParamArray) {
          CloseWindow(w);
          Ext.each(ArrayParam, function (par) { //обработка параметризованных параметров
            if (par.ParamInterractive != true) {
              Ext.each(ArrayParam, function (cur_par) {
                if ((cur_par.ParamInterractive != true) && (cur_par.ParamCode != par.ParamCode)) {
                  par.ParamDefaultValue = par.ParamDefaultValue.replace(new RegExp(':' + cur_par.ParamCode + ':', 'g'), cur_par.ParamDefaultValue);
                }
              });
              for (var prop in ParamArray) {
                par.ParamDefaultValue = par.ParamDefaultValue.replace(new RegExp(':' + prop + ':', 'g'), ParamArray[prop]);
              }
              try {
                par.ParamDefaultValue = eval(par.ParamDefaultValue);
              } catch (e) {
                Ext.MessageBox.alert('Ошибка', e.name)
              } finally {
              }
              ParamArray[par.ParamCode] = par.ParamDefaultValue;
            }
          });
          if (CallBackParam == undefined) {
            CallBackParam = {}
          }
          CallBackParam.code = ObjCode;
          CallBackParam.ParamValuesArray = ParamArray;
          CallBackFunction(CallBackParam);
        });

        win = findFirstWindow();
        _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
        _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
        w = win.myDesktopApp.desktop.createWindow(
                {
                  title: 'Определение значений параметров',
                  code: 'Params',
                  width: _width,
                  height: _height,
                  closable: true,
                  autoScroll: true,
                  maximized: false,
                  maximizable: true,
                  HelpContext: 'defineParams',
                  tools: [{
                      type: 'help',
                      qtip: 'Получить справку',
                      callback: ShowHelp
                    }],
                  layout: {
                    type: 'fit'
                  },
                  constrainHeader: true,
                  modal: true,
                  items: wp
                });
        w.show();
        //                    w.addListener('BtnOk', function (InterractiveParamValuesArray) {
      }
      else {
        // мобильная версия
        buildW_desktop(_URLProjectRoot + 'Params/ParamsFunction.php?'
                + 'ArrayInterractiveParam=' + encodeURIComponent(Ext.JSON.encode(ArrayParam))
                + '&FunctionName=' + CallBackFunction.name
                + '&ObjCode=' + ObjCode
                + '&ParamObject=' + encodeURIComponent(Ext.JSON.encode(ParamObject))
                , 'Определение значений параметров');
      }
    } else {
      var ParamArray = {};
      Ext.each(ArrayParam, function (par) { //обработка параметризованных параметров
        if (par.ParamInterractive != true) {
          Ext.each(ArrayParam, function (cur_par) {
            if ((cur_par.ParamInterractive != true) && (cur_par.ParamCode != par.ParamCode)) {
              par.ParamDefaultValue = par.ParamDefaultValue.replace(new RegExp(':' + cur_par.ParamCode + ':', 'g'), cur_par.ParamDefaultValue);
            }
          });
          try {
            par.ParamDefaultValue = eval(par.ParamDefaultValue);
          } catch (e) {
            Ext.MessageBox.alert('Ошибка', e.name)
          } finally {
          }
          ParamArray[par.ParamCode] = par.ParamDefaultValue;
        }
      });
      if (CallBackParam == undefined) {
        CallBackParam = {}
      }
      CallBackParam.code = ObjCode;
      CallBackParam.ParamValuesArray = ParamArray;
      CallBackFunction(CallBackParam);
    }
  } else {
    if (CallBackParam == undefined) {
      CallBackParam = {}
    }
    CallBackParam.code = ObjCode;
    CallBackParam.ParamValuesArray = ParamValuesArray;
    CallBackFunction(CallBackParam);
  }
}
