Ext.Loader.setPath({
  'pivot': _URLProjectRoot + 'Pivot',
});
Ext.define('KOMETA.Operation.CrossTable_operation', {
  New: function (Grid, Operation) {
// Новая сводная таблица (для вызова из списка сводных таблиц)
    var me = this;
    var win = SelectValSlv({sysname: 'sv_mb_object_select', ExtFilterWhereCond: '', object_Caption: 'Выбор объекта для создания шаблона сводной таблицы', HelpContext: ''});
    win.addListener('ValSlvSelected', function (context, SelID, SelDescr) {
      Operation.id_object = SelID.id_object;
      var w = me.CreateEditPivotWindow(true, Grid, Operation);
    });
  }
  ,
  Edit: function (Grid, Operation) { //показывает список сохраненных сводных таблиц
    var me = this;
    var w = me.CreateEditPivotWindow(false, Grid, Operation);
  }
  ,
  CreateEditPivotWindow: function (_isNew, Grid, Operation) {
    var CrossSettings_Caption, CrossSettings_Code;
    if (_isNew) {

      CrossSettings_Caption = 'Новая';
      CrossSettings_Code = '';
    } else {
      CrossSettings_Caption = Grid.getSelectionModel().getSelection()[0].raw.short_name;
      CrossSettings_Code = Grid.getSelectionModel().getSelection()[0].raw.code;
    }

    Pivot_class.GetPivotObject(CrossSettings_Code, Operation.id_object, function (response) {
      if ((response.success === false) && (response.result == 're_connect')) {
        Ext.MessageBox.alert('Подключение', response.msg);
        window.onbeforeunload = null;
        findFirstWindow().window.location.href = __first_page;
        return;
      }
      if (response.success === true) {

        var crossTableContainer = Ext.create('pivot.CrosstableFormContainer', {
          isNew: _isNew,
          code: CrossSettings_Code,
          CrossSettings_Caption: CrossSettings_Caption,
          PivotObject: response.result,
          PivotListGrid: Grid,
        });
        win = findFirstWindow();
        _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
        _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
        var CrossWindow = win.myDesktopApp.desktop.createWindow({
          title: 'Конструктор шаблонов сводных таблиц ', //+ CrossSettings_Caption,
          code: 'CrossWindow',
          width: _width,
          height: _height,
          closable: true,
          autoScroll: true,
          HelpContext: 'kometa_pivot_setting',
          tools: [{
              type: 'help',
              qtip: 'Справка',
              callback: ShowHelp
            },
            {
              type: 'save',
              qtip: 'сохранить',
              callback: function () {
                var PivotObject = crossTableContainer.GetPivotObject();
                var PivotCaption = crossTableContainer.GetPivotCaption();
                var PivotCode = crossTableContainer.GetPivotCode();
                Ext.MessageBox.wait({
                  msg: 'Выполняется операция. Ждите...',
                  width: 300,
                  wait: true,
                  waitConfig: {interval: 100}
                });
                Pivot_class.SavePivotList(crossTableContainer.PivotObject.id_object, PivotCaption,
                        Ext.JSON.encode(PivotObject), PivotCode,
                        function (response) {
                          Ext.MessageBox.hide();
                          var result = response;
                          if ((result.success === false) && (result.result == 're_connect')) {
                            Ext.MessageBox.alert('Подключение', result.msg);
                            window.onbeforeunload = null;
                            findFirstWindow().window.location.href = __first_page;
                            return;
                          }
                          if (result.success == true) {
                            Grid.ReloadGrid();
                            Ext.MessageBox.alert('Сообщение', result.msg);
                          } else {
                            Ext.MessageBox.alert('Ошибка', "Ошибка сохранения: " + result.msg);
                          }
                        }
                );
              }

            }
          ],
          layout: {
            type: 'fit'
          },
          constrainHeader: true,
          modal: true,
        });
        CrossWindow.add(crossTableContainer);
        CrossWindow.show();
        return CrossWindow;
      }
      else {
        Ext.MessageBox.alert('Ошибка получения списка полей для формирования шаблона сводной таблицы', response.msg);
      }
    });
  }
//------------
  ,
  ShowPivotResultWindow: function (Pivot_Object, needRender, _container) {
    var me = this;
    //var pw = findFirstWindow();
    var jPivot_Object = Ext.JSON.encode(Pivot_Object);// сохранение объекта в строку
    function SavePivot() {
      var Pivot_Object = Ext.JSON.decode(jPivot_Object);
      Ext.MessageBox.wait({
        msg: 'Выполняется операция. Ждите...',
        width: 300,
        wait: true,
        waitConfig: {interval: 100}
      });
      Pivot_class.GetPivotHTML(Pivot_Object.result.pivot_code, function (response) {
        Ext.MessageBox.hide();
        if ((response.success === false) && (response.result == 're_connect')) {
          Ext.MessageBox.alert('Подключение', response.msg);
          window.onbeforeunload = null;
          findFirstWindow().window.location.href = __first_page;
          return;
        }
        if (response.success == true) {
          buildW_desktop_report_print(Pivot_Object.result.pivot_code, response.result, 'Сводная таблица: ' + Pivot_Object.result.pivot_name, '', '');
        } else {
          Ext.MessageBox.alert('Ошибка', "Ошибка построения сводной таблицы: " + response.msg);
        }

      });
    }

    if (_container == undefined) {
      win = findFirstWindow();
      _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
      _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
      var PivotWindow =
              win.myDesktopApp.desktop.createWindow({
                title: 'Сводная таблица: ' + Pivot_Object.result.pivot_name,
                code: 'ShowPivotResultWindow_' + Pivot_Object.result.id_pivot_storage,
                width: _width,
                height: _height,
                closable: true,
                maximizable: true,
                HelpContext: 'kometa_pivot_result',
                tools: [{
                    type: 'help',
                    qtip: 'Cправка',
                    callback: ShowHelp
                  },
                  {
                    type: 'save',
                    qtip: 'Сохранить',
                    callback: SavePivot
                  },
                  {
                    type: 'gear',
                    qtip: 'Графики',
                    callback: function (panel) {
                      var Pivot_Object = Ext.JSON.decode(jPivot_Object);
                      if (Pivot_Object.result.border_cnt != 1) {
                        Ext.MessageBox.alert('Подключение', 'График не может быть построен. Полей группировки по боковине должно быть строго 1');
                        return;
                      }
                      if (Pivot_Object.result.group_field_array.length != 1) {
                        Ext.MessageBox.alert('Подключение', 'График не может быть построен. Групповая операция должна быть строго 1');
                        return;
                      }
                      win = findFirstWindow();
                      _width = Math.round(win.window.document.body.clientWidth / 6 * 5);
                      _height = Math.round(win.window.document.body.clientHeight / 6 * 5);
                      var chartWindow =
                              win.myDesktopApp.desktop.createWindow({
                                title: 'График ' + Pivot_Object.result.pivot_name,
                                code: 'Graf_ShowPivotResultWindow_' + Pivot_Object.result.id_pivot_storage,
                                width: _width,
                                height: _height,
                                closable: true,
                                maximizable: true,
                                layout: 'fit',
                                HelpContext: 'kometa_pivot_result_graf',
                                tools: [{
                                    type: 'save',
                                    qtip: 'Сохранить',
                                    callback: function (panel) {

                                    }
                                  }
                                  , {
                                    type: 'help',
                                    qtip: 'Cправка',
                                    callback: ShowHelp
                                  }
                                ]
                                , layout: {
                                  type: 'fit'
                                }
                                , modal: true
                              });
                      var type_graf = Ext.create('Ext.data.Store', {
                        fields: ['id', 'name'],
                        data: [
                          {"id": 1, "name": "Круговая диаграмма"},
                          {"id": 2, "name": "Гистограмма (раздельные столбцы)"},
                          {"id": 3, "name": "Гистограмма (общие столбцы)"},
                          {"id": 4, "name": "Линейный график"},
                          {"id": 5, "name": "Радар график"}
                        ]
                      });
                      elem = Ext.create('Ext.form.field.ComboBox', {
                        fieldLabel: 'Тип графика',
                        name: 'Graf_ShowPivotResultWindow_' + Pivot_Object.result.id_pivot_storage + 'panel_chart',
                        lastQuery: '',
                        queryMode: 'local',
                        store: type_graf,
                        displayField: 'name',
                        valueField: 'id',
                        autoSelect: true,
                        editable: false
                      });
                      chartWindow.addDocked(elem);
                      //store1 = new Ext.data.JsonStore();
                      title = '';
                      comma = '';
                      st_d_fields = "\"X\"";
                      fields = "";
                      comma = '';
                      for (var i = 1; i < Pivot_Object.result.GridColumnModel.length; i++) {
                        if ((Pivot_Object.result.GridColumnModel[i].text == undefined) || (Pivot_Object.result.GridColumnModel[i].text == ''))
                          title = title + comma + "\"-\"";
                        else
                          title = title + comma + "\"" + Pivot_Object.result.GridColumnModel[i].text + "\"";
                        st_d_fields = st_d_fields + ",\"Y" + i + "\", \"func_class_nameY" + i + "\",\"func_nameY" + i + "\",\"param_listY" + i + "\"";
                        fields = fields + comma + "\"Y" + i + "\"";
                        comma = ',';
                      }
                      data = '';
                      comma = '';
                      for (var j = 0; j < Pivot_Object.result.GridData.children.length; j++) {
                        data = data + comma + "{\"X\":\"" + Pivot_Object.result.GridData.children[j].treecolumn + "\"";
                        for (var i = 1; i < Pivot_Object.result.GridColumnModel.length; i++) {
                          fld = Pivot_Object.result.GridColumnModel[i].columns[0].dataIndex;
                          if (Pivot_Object.result.GridData.children[j][fld] == undefined) {
                          }
                          else {
                            val = Pivot_Object.result.GridData.children[j][fld];
                            data = data + ",\"Y" + i + "\": " + val + " ,\"func_clas_nameY" + i + "\":\"\", \"func_nameY" + i + "\":\"\", \"param_listY" + i + "\":\"{}\"";
                          }
                        }
                        data = data + "}";
                        comma = ',';
                      }

                      var chart_text = "{\"success\" :true,\"id_chart\":1,\"width\":900,\"height\":900,\"isGrid\":1,\"id_chart_type\" : 2,"
                              + "\"style_chart\" : \"ThreeD\",\"label_chart\" : \"<br>\",\"orientation\" :\"0\",\"bgcolor\" : \"#FFF\","
                              + "\"label_x_axis\" : \"" + Pivot_Object.result.border_field_descr[0]
                              + "\",\"label_y_axis\" :\"" + Pivot_Object.result.group_field_array[0][0] + "\","
                              + "\"label_z_axis\" :\"" + '' + "\","
                              + "\"store_data\":{\"fields\":[" + st_d_fields + "],"
                              + "\"data\":["
                              + data
                              + "]"
                              + "}"
                              + ",\"fields\":[ " + fields + "]"
                              + ",\"title\":[ " + title + "]}";
                      chart = Ext.decode(chart_text);
                      chartWindow.store1 = new Ext.data.JsonStore(chart.store_data);
                      elem.addListener("change", function (me, newValue, oldValue) {
                        if (chartWindow.items.length > 0)
                          chartWindow.remove(wchart);
                        wchart = null;
                        switch (me.value) {
                          case 1: // Круговая диаграмма
                            var ser = [];
                            wchart = Ext.create('Ext.tab.Panel', {
                              id: 'tabPanel',
                              name: 'tabPanel',
                              flex: 1,
                              autoScroll: true});
                            ;
                            for (var i = 0; i < chart.fields.length; i++) {
                              ser.push({
                                type: 'pie',
                                field: chart.fields[i],
                                autoSize: true,
                                showInLegend: true,
                                highlight: {segment: {margin: 20}},
                                label: {
                                  field: 'X',
                                  display: 'rotate',
                                  contrast: true
                                },
                                tips: {
                                  trackMouse: true,
                                  width: 200,
                                  height: 50,
                                  renderer: function (storeItem, item) {
                                    this.setTitle(storeItem.get('X') + ': ' + storeItem.get('Y$i'));
                                  }
                                },
                                listeners: {
                                  itemmousedown: function (obj) {
                                    window.location.href = obj.storeItem.data['refY$i'];
                                  }
                                }
//                              , title: chart.fields
                              });
                              var formPanel = Ext.create('Ext.form.Panel', {title: chart.title[i], layout: 'fit'});
                              wchart1 = Ext.create('Ext.chart.Chart', {
                                xtype: 'chart',
                                style: 'background:#fff',
                                animate: true,
                                shadow: true,
                                insetPadding: 30,
                                theme: 'Base:gradients',
                                autoSize: true,
                                store: chartWindow.store1,
                                legend: {position: 'bottom'},
                                series: ser
                              }
                              );
                              formPanel.add(wchart1);
                              wchart.add(formPanel);
                            }
                            ;
                            break;
                          case 2: // Гистограмма (раздельные столбцы)
                            wchart = Ext.create('Ext.chart.Chart', {
                              style: 'background:#fff',
                              animate: true,
                              store: chartWindow.store1,
                              autoSize: true,
                              legend: {position: 'bottom'},
                              axes: [{
                                  type: 'Numeric',
                                  position: 'left',
                                  fields: chart.fields,
                                  grid: true,
                                  title: chart.label_y_axis
                                }, {
                                  type: 'Category',
                                  position: 'bottom',
                                  fields: ['X'],
                                  title: chart.label_x_axis,
                                  label: {rotate: {degrees: 270}}
                                }],
                              series: [{
                                  type: 'column',
                                  axis: 'left',
                                  label: {
                                    contrast: true,
                                    display: 'insideEnd',
                                    field: chart.fields,
                                    color: '#000',
                                    orientation: 'vertical',
                                    'text-anchor': 'middle'
                                  },
                                  xField: 'X',
                                  yField: chart.fields,
                                  title: chart.title
                                }]
                            });
                            break;
                          case 3: // Гистограмма (общие столбцы)
                            wchart = Ext.create('Ext.chart.Chart', {
                              style: 'background:#fff',
                              animate: true,
                              shadow: true,
                              store: chartWindow.store1,
                              autoSize: true,
                              legend: {position: 'bottom'},
                              axes: [{
                                  type: 'Numeric',
                                  position: 'bottom',
                                  fields: chart.fields,
                                  title: false,
                                  grid: true,
                                }, {
                                  type: 'Category',
                                  position: 'left',
                                  fields: ['X'],
                                  title: false
                                }],
                              series: [{
                                  type: 'bar',
                                  axis: 'bottom',
                                  // gutter: 80,
                                  stacked: true,
                                  xField: 'X',
                                  yField: chart.fields,
                                  title: chart.title
                                }]
                            });
                            break;
                          case 4: // Линейный график
                            var ser = [];
                            for (var i = 0; i < chart.fields.length; i++) {
                              ser.push({
                                type: 'line',
                                autoSize: true,
                                highlight: {
                                  size: 7,
                                  radius: 7
                                },
                                axis: 'left',
                                xField: 'X',
                                yField: chart.fields[i],
                                markerConfig: {
                                  type: 'cross',
                                  size: 4,
                                  radius: 4,
                                  'stroke-width': 0
                                },
                                tips: {
                                  trackMouse: true,
                                  width: 200,
                                  height: 50,
                                  renderer: function (storeItem, item) {
                                    this.setTitle(storeItem.get('X'));
                                    var k = item.series.seriesIdx + 1;
                                    this.update(storeItem.get('Y' + k));
                                  }
                                },
                                title: chart.title[i]
                              });
                            }
                            ;
                            wchart =
                                    Ext.create('Ext.chart.Chart', {
                                      style: 'background:#fff',
                                      animate: true,
                                      shadow: true,
                                      autoSize: true,
                                      store: chartWindow.store1,
                                      legend: {
                                        position: 'bottom'
                                      },
                                      axes: [{
                                          type: 'Numeric',
                                          position: 'left',
                                          fields: chart.fields,
                                          minimum: 0,
                                          grid: {
                                            odd: {
                                              opacity: 1,
                                              fill: '#ddd',
                                              stroke: '#bbb',
                                              'stroke-width': 0.5
                                            }
                                          },
                                          title: chart.label_y_axis
                                        }, {
                                          type: 'Category',
                                          position: 'bottom',
                                          fields: ['X'],
                                          title: chart.label_x_axis,
                                          label: {
                                            rotate: {
                                              degrees: 270
                                            }
                                          }
                                        }],
                                      series: ser
                                    }
                                    );
                            break;
                          case 5: // Радар график
                            var ser = [];
                            for (var i = 0; i < chart.fields.length; i++) {
                              ser.push({
                                showInLegend: true,
                                autoSize: true,
                                type: 'radar',
                                xField: 'X',
                                yField: chart.fields[i],
                                style: {opacity: 0.4},
                                tips: {
                                  trackMouse: true,
                                  width: 200,
                                  height: 50,
                                  renderer: function (storeItem, item) {
                                    this.setTitle(storeItem.get('X'));
                                    this.update(String(item.value[1]));
                                  }
                                },
                                title: chart.title[i]
                              });
                            }
                            ;
                            wchart = Ext.create('Ext.chart.Chart', {
                              style: 'background:#fff',
                              animate: true,
                              shadow: true,
                              height: 200,
                              insetPadding: 20,
                              store: chartWindow.store1,
                              legend: {
                                position: 'bottom'
                              },
                              axes: [{
                                  type: 'Radial',
                                  position: 'radial',
                                  label: {
                                    display: true
                                  }
                                }],
                              series: ser
                            }
                            );
                            break;
                        }

                        chartWindow.add(wchart);
                      });
                      elem.setValue(1);
                      chartWindow.show();
                    }
                  }
                ]
                , layout: 'fit'

                , modal: true
              });
    } else {
      PivotWindow = _container
    }

    var PivotGridStore = Ext.create('Ext.data.TreeStore', {
      model: 'Task',
      fields: Pivot_Object.result.GridModel,
      defaultRootProperty: "children",
      root: Pivot_Object.result.GridData,
      folderSort: true
    });
    function RenderCell(val, m, r) {
      if ((val != undefined) && (val != '')) {
        var arr = val.split('#');
        if ((arr != undefined) && (arr.length > 0))
          return arr[0] + '<br>' + arr[1] + '<br>' + arr[2];
        else
          return null;
      } else
        return null;
    }
    function recurceNode(_Nodes) {
      var length = _Nodes.length;
      for (var j = 0; j < length; j++) {
        var _Node = _Nodes[j];
        _Node.renderer = RenderCell;
        if ((_Node.columns != undefined) && (_Node.columns.length > 0))
          recurceNode(_Node.columns);
      }
    }
    if (needRender == true) {
      var length = Pivot_Object.result.GridColumnModel.length;
      for (var j = 1; j < length; j++) {
        var _Node = Pivot_Object.result.GridColumnModel[j];
        _Node.renderer = RenderCell;
        if ((_Node.columns != undefined) && (_Node.columns.length > 0))
          recurceNode(_Node.columns);
      }
    }

    me.set_id_columns(Pivot_Object.result.GridColumnModel, PivotWindow.id);
    var PivotGrid = Ext.create('Ext.tree.Panel', {
      id: PivotWindow.id + '_pivot',
      renderTo: Ext.getBody(),
      useArrows: true,
      rootVisible: false,
      columnLines: true,
      autoScroll: true,
      rowLines: true,
      store: PivotGridStore,
      multiSelect: false,
      columns: Pivot_Object.result.GridColumnModel
    });
    me.set_columns_width(Pivot_Object.result.GridColumnModel, null);
    PivotGrid.removeAll(true);
    Ext.destroy(PivotGrid);
    PivotGrid = Ext.create('Ext.tree.Panel', {
      id: PivotWindow.id + '_pivot',
      renderTo: Ext.getBody(),
      useArrows: true,
      rootVisible: false,
      columnLines: true,
      autoScroll: true,
      rowLines: true,
      store: PivotGridStore,
      multiSelect: false,
      columns: Pivot_Object.result.GridColumnModel
    });
    PivotWindow.add(PivotGrid);
    PivotWindow.show();
    return PivotWindow;
  }

  ,
  RunPivot: function (pivot_code, _container) {
    var me = this;
    Ext.MessageBox.wait({
      msg: 'Выполняется операция. Ждите...',
      width: 300,
      wait: true,
      waitConfig: {interval: 100}
    });
    Pivot_class.GetPivotObjectForRun(pivot_code, function (response, options) {
      Ext.MessageBox.hide();
      var Pivot_Object = response;
      if (Pivot_Object.success == true) {
        me.ShowPivotResultWindow(Pivot_Object, false, _container);
      } else {
        Ext.MessageBox.alert('Ошибка', "Ошибка получения настроек: " + Pivot_Object.msg);
        return false;
      }
    });
  }
  ,
  Execute: function (Grid, Operation) {
    var me = this;
    if ((Operation.param_list != undefined) && (Operation.param_list.code != undefined))
      var pivot_code = Operation.param_list.code;
    else {
      var sm = Grid.getSelectionModel().getSelection()[0].raw;
      if ((sm == undefined) || (sm == null)) {
        Ext.MessageBox.alert('Не выбрана запись');
      }

      var pivot_code = sm['code'];
    }
    if ((pivot_code == undefined) || (pivot_code == '')) {
      Ext.MessageBox.alert('Не определен код сводной таблицы');
      return;
    }
    this.RunPivot(pivot_code);
  }
  ,
  set_id_columns: function (cols, prefix) {
    var me = this;
    var i = 0;
    var length = cols.length;
    for (i = 0; i < length; i++)
      if (i < length) {
        cols[i].id = prefix + '_' + i;
        if (cols[i].columns != undefined)
          me.set_id_columns(cols[i].columns, cols[i].id);
      }
  }
  ,
// Установить размеры колонок по умолчанию
  set_id_columns_width: function (PivotGrid, cols, ww) {
    var me = this;
    var i = 0;
    var wc = null;
    var el;
    var k = 1;
    var length = cols.length;
    // подсчет общей длины всех колонок
    var www = 0;
    for (i = 0; i < length; i++) {
      el = document.getElementById(cols[i].id);
      cols[i].width = el.offsetWidth;
      www += el.offsetWidth;
    }
    if (ww != undefined) {
      wc = Math.round(ww / length);
      // вычисляем коэф. перерасчета длины
      if (www < ww)
        k = ww / www;
      else
        k = 1;
    }
    for (i = 0; i < length; i++)
      if (cols[i].columns != undefined) {
        if (k > 1)
          me.set_id_columns_width(PivotGrid, cols[i].columns, cols[i].width * k);
        else
          me.set_id_columns_width(PivotGrid, cols[i].columns, null);
      }
      else if (ww != undefined) {
//поиск колонки по номеру
        var gcols = PivotGrid.view.getGridColumns();
        for (j = 0; j < gcols.length; j++) {
          if (gcols[j].id == cols[i].id) {
            gcols[j].setWidth(Math.ceil(gcols[j].getWidth() * k));
            break;
          }
        }
      }
  }
  ,
  set_columns_width: function (cols, ww) {
    var i = 0;
    var me = this;
    var el;
    var k = 1;
    var length = cols.length;
    // попсчет общей длины всех колонок
    var www = 0;
    for (i = 0; i < length; i++) {
      el = document.getElementById(cols[i].id);
      cols[i].width = el.offsetWidth;
      www += el.offsetWidth;
    }
    if (ww != undefined) {
// вычисляем коэф. перерасчета длины
      if (www < ww)
        k = ww / www;
      else
        k = 1;
    }
    else
      k = 1;
    // пересчитываю колонки нижнего уровня
    for (i = 0; i < length; i++) {
      cols[i].width = Math.ceil(cols[i].width * k);
      cols[i].flex = null;
      if (cols[i].columns != undefined) {
        me.set_columns_width(cols[i].columns, cols[i].width);
        cols[i].flex = null;
      }
    }
  }

});