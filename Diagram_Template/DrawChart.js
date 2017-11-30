// инвертировать цветf
function invertColor(hexTripletColor) {
  var color = hexTripletColor;
  color = color.substring(1);           // remove #
  color = parseInt(color, 16);          // convert to integer
  color = 0xFFFFFF ^ color;             // invert three bytes
  color = color.toString(16);           // convert to hex
  color = ("000000" + color).slice(-6); // pad with leading zeros
  color = "#" + color;                  // prepend #
  return color;
}

/* 
 * создание  на панели
 */
function draw_chart(diagram, _ParamValuesArray)
{
  // запрашиваем описание графика
  Ext.suspendLayouts();
  var store1 = new Ext.data.JsonStore(diagram.store_data);
  var wchart = null;
  var orient_x = 'left';
  var orient_y = 'bottom';
  var orient_type = 'column'
  if (diagram.orientation == '1') {
    orient_y = 'left';
    orient_x = 'bottom';
    orient_type = 'bar';
  }

  if ((diagram.legend_box_color == undefined) || (diagram.legend_box_color == ''))
    diagram.legend_box_color = '#ffffff';
  else
    diagram.legend_box_color = '#' + diagram.legend_box_color;
  if ((diagram.legend_label_color == undefined) || (diagram.legend_label_color == ''))
    diagram.legend_label_color = invertColor(diagram.legend_box_color);
  else
    diagram.legend_label_color = '#' + diagram.legend_label_color;

  if ((diagram.legend_font_size == undefined) || (diagram.legend_font_size == ''))
    diagram.legend_font_size = '12px';

  if ((diagram.gutter == undefined) || (diagram.gutter == ''))
    diagram.gutter = '20';
  if ((diagram.groupGutter == undefined) || (diagram.groupGutter == ''))
    diagram.groupGutter = '20';

  if ((diagram.axes_font_size == undefined) || (diagram.axes_font_size == ''))
    diagram.axes_font_size = '12px';

  if ((diagram.axes_label_font_size == undefined) || (diagram.axes_label_font_size == ''))
    diagram.axes_label_font_size = '12px';

  if ((diagram.label_font_size == undefined) || (diagram.label_font_size == ''))
    diagram.label_font_size = '12px';

  if ((diagram.header_font_size == undefined) || (diagram.header_font_size == ''))
    diagram.header_font_size = '12px';

  switch (diagram.legend_position) {
    case '1':
      legend_position = {position: 'bottom', labelFont: diagram.legend_font_size, boxFill: diagram.legend_box_color, labelColor: diagram.legend_label_color};
      break;
    case '2':
      legend_position = {position: 'left', labelFont: diagram.legend_font_size, boxFill: diagram.legend_box_color, labelColor: diagram.legend_label_color};
      break;
    case '3':
      legend_position = {position: 'right', labelFont: diagram.legend_size, boxFill: diagram.legend_box_color, labelColor: diagram.legend_label_color};
      break;
    case '4':
      legend_position = {position: 'top', labelFont: diagram.legend_font_size, boxFill: diagram.legend_box_color, labelColor: diagram.legend_label_color};
      break;
    default:
      legend_position = false;
  }
  var bgcolor = '#ffffff';
  if ((diagram.bgcolor != undefined) && (diagram.bgcolor != ''))
    bgcolor = '#' + diagram.bgcolor;

  if ((diagram.label_color == undefined) || (diagram.label_color == '')) {
    diagram.label_color = invertColor(bgcolor);
  }
  else
    diagram.label_color = '#' + diagram.label_color;

  if ((diagram.diagram_theme == undefined) || (diagram.diagram_theme == ''))
    diagram.diagram_theme = 'Base';

  var _grid_x = false;
  if (diagram.ShowGrid_x == true) {
    _grid_x = true;
  }

  var _grid_y = false;
  if (diagram.ShowGrid_y == true) {
    _grid_y = true;
  }

  var oTheme = Ext.create("Ext.chart.theme." + diagram.diagram_theme);
  var oColors = oTheme.colors;
  for (var i = 0; i < diagram.colors.length; i++) {
    if (diagram.colors[i] != '') {
      oColors[i] = '#' + diagram.colors[i];
    }
  }

  Ext.define('Ext.chart.theme.diagram' + diagram.num, {
    extend: 'Ext.chart.theme.' + diagram.diagram_theme,
    constructor: function (config) {
      this.callParent([Ext.apply({
          colors: oColors
        }, config)]);
    }
  });

  switch (diagram.id_chart_type) {
    case 1: // Круговая диаграмма
      var wchart = [];
      for (var i = 0; i < diagram.fields.length; i++) {
        char_conf = {
          type: 'pie',
          field: diagram.fields[i],
          //              autoSize: true,
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
              this.setTitle(storeItem.get('X') + ': ' + storeItem.get(item.series.field));
            }
          },
          listeners: {
            itemmousedown: chart_itemmousedown
          },
          title: diagram.title
        };

        cc = Ext.create('Ext.container.Container', {
          flex: 1,
          margin: 0,
          padding: 0,
          layout: {
            type: 'vbox',
            align: 'stretch',
            padding: 0,
            margin: 0
          },
          border: 1,
          style: {
            boderColor: '#000000',
            borderStyle: 'solid',
            borderWidth: '0px'
          },
          items: [{
              xtype: 'displayfield',
              border: false,
              margin: '0 0 0 0',
              padding: 0,
              value: diagram.title[i],
              fieldStyle: {"text-align": 'center', font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color, 'background-color': bgcolor},
              fieldLabel: '',
              hideLabel: true
            }, {
              flex: 1,
              border: false,
              xtype: 'chart',
              func_class_name: diagram.func_class_name,
              func_name: diagram.func_name,
              param_list: diagram.param_list,
              listeners: {
                click: chart_click
              },
              background: {
                fill: bgcolor
              },
              animate: true,
              shadow: true,
              insetPadding: 25,
              theme: 'diagram' + diagram.num, //diagram.diagram_theme,
              store: store1,
              legend: false,
              series: [char_conf]
            }
          ]
        });

        wchart.push(cc);
      }
      ;
      break;
    case 6: // Линейный график с областями
      wchart = Ext.create('Ext.chart.Chart', {
        ParamValuesArray: _ParamValuesArray,
        PreviewMode: diagram.PreviewMode,
        flex: 1,
        background: {
          fill: bgcolor

        },
        animate: true,
        theme: 'diagram' + diagram.num, //diagram.diagram_theme,
        store: store1,
        legend: legend_position,
        func_class_name: diagram.func_class_name,
        func_name: diagram.func_name,
        param_list: diagram.param_list,
        listeners: {
          click: chart_click
        },
        axes: [{
            type: 'Numeric',
            position: 'left',
            labelTitle: {font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
            fields: diagram.fields,
            grid: _grid_x,
            minimum: diagram.minimum,
            title: diagram.label_y_axis,
            label: {
              font: diagram.axes_label_font_size + ' sans-serif', fill: diagram.label_color}
          }, {
            grid: _grid_y,
            type: 'Category',
            position: 'bottom',
            labelTitle: {font: diagram.axes_font_size + ' sans-serif'},
            font: diagram.axes_label_font_size + ' sans-serif',
            fields: ['X'],
            title: diagram.label_x_axis,
            label: {rotate: {degrees: diagram.label_rotate},
              font: diagram.axes_label_font, fill: diagram.label_color}
          }],
        series: [{
            type: 'area',
            highlight: false,
            axis: 'left',
            stacked: true,
            xField: 'X',
            yField: diagram.fields,
            style: {
              opacity: 0.93
            },
            listeners: {
              itemmousedown: chart_itemmousedown
            },
            title: diagram.title
          }]
      });


      break;
    case 2: // Гистограмма (раздельные столбцы)
    case 3: // Гистограмма (общие столбцы)
      wchart = Ext.create('Ext.chart.Chart', {
        ParamValuesArray: _ParamValuesArray,
        PreviewMode: diagram.PreviewMode,
        flex: 1,
        background: {
          fill: bgcolor

        },
        animate: true,
        theme: 'diagram' + diagram.num, //diagram.diagram_theme, //'Fancy',
        store: store1,
        legend: legend_position,
        func_class_name: diagram.func_class_name,
        func_name: diagram.func_name,
        param_list: diagram.param_list,
        listeners: {
          click: chart_click
        },
        axes: [{
            type: 'Numeric',
            position: orient_x,
            labelTitle: {font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
            fields: diagram.fields,
            grid: _grid_x,
            minimum: diagram.minimum,
            title: diagram.label_y_axis,
            label: {
              font: diagram.axes_label_font_size + ' sans-serif', fill: diagram.label_color}
          }, {
            type: 'Category',
            grid: _grid_y,
            position: orient_y,
            labelTitle: {font: diagram.axes_font_size + ' sans-serif'},
            font: diagram.axes_label_font_size + ' sans-serif',
            fields: ['X'],
            title: diagram.label_x_axis,
            label: {rotate: {degrees: diagram.label_rotate},
              font: diagram.axes_label_font_size + ' sans-serif', fill: diagram.label_color}
          }],
        series: [{
            gutter: diagram.gutter,
            groupGutter: diagram.groupGutter,
            type: orient_type,
            stacked: (diagram.id_chart_type == 3),
            maxBarWidth: 50,
            axis: orient_x,
            xField: 'X',
            yField: diagram.fields,
            listeners: {
              itemmousedown: chart_itemmousedown
            },
            title: diagram.title
          }]

      });


      // если этот кусок есть то есть подписи с числами 
      if ((diagram.label_display == undefined) || (diagram.label_display == ''))
        diagram.label_display = 'insideEnd';
      wchart.series.items[0].label = {
        //  contrast: true,
        display: diagram.label_display,
        field: diagram.fields,
        font: diagram.label_font_size + ' sans-serif',
        color: '#' + diagram.info_label_color,
        orientation: diagram.label_type_visible,
        'text-anchor': 'middle'
      };

      break;
    case 4: // Линейный график
      var ser = [];
      for (var i = 0; i < diagram.fields.length; i++) {
        ser.push({
          type: 'line',
//                    autoSize: true,
          highlight: {
            size: 7,
            radius: 7
          },
          axis: 'bottom',
          xField: 'X',
          yField: diagram.fields[i],
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
              this.update(String(item.value[1]));
            }
          },
          listeners: {
            itemmousedown: chart_itemmousedown
          },
          title: diagram.title[i]
        });
      }
      ;
      wchart = Ext.create('Ext.chart.Chart', {
        ParamValuesArray: _ParamValuesArray,
        PreviewMode: diagram.PreviewMode,
        flex: 1,
        func_class_name: diagram.func_class_name,
        func_name: diagram.func_name,
        param_list: diagram.param_list,
        shadow: true,
        //              autoSize: true,
        background: {
          fill: bgcolor

        },
        animate: true,
        theme: 'diagram' + diagram.num, //diagram.diagram_theme,
        store: store1,
        legend: legend_position,
        axes: [{
            type: 'Numeric',
            position: 'left',
            fields: diagram.fields,
            grid: _grid_x,
            minimum: 0,
            labelTitle: {font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
            minimum: diagram.minimum,
                    label: {
                      font: diagram.axes_label_font_size + ' sans-serif', fill: diagram.label_color},
            title: diagram.label_y_axis
          }, {
            type: 'Category',
            grid: _grid_y,
            position: 'bottom',
            fields: ['X'],
            title: diagram.label_x_axis,
            labelTitle: {font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
            label: {
              font: diagram.axes_label_font_size + ' sans-serif', fill: diagram.label_color},
            label: {
              rotate: {
                degrees: diagram.label_rotate
              },
              font: diagram.axes_label_font_size + ' sans-serif', fill: diagram.label_color
            }
          }],
        series: ser
      }
      );

      break;
    case 5: // Радар график c заливкой
    case 7: // Радар график без заливки
      var ser = [];
      if (diagram.id_chart_type == 5) {
        _style = {opacity: 0.4}
      }
      else {
        _style = {
          'stroke-width': 2,
          fill: 'none'
        }
      }
      for (var i = 0; i < diagram.fields.length; i++) {
        ser.push({
          showInLegend: true,
          //                autoSize: true,
          type: 'radar',
          xField: 'X',
          yField: diagram.fields[i],
          style: _style,
          tips: {
            trackMouse: true,
            width: 200,
            height: 50,
            renderer: function (storeItem, item) {
              this.setTitle(storeItem.get('X'));
              this.update(String(item.series.title + '=' + item.series.radius));
            }
          },
          listeners: {
            itemmousedown: chart_itemmousedown
          },
          title: diagram.title[i]
        });
      }
      ;
      wchart = Ext.create('Ext.chart.Chart', {
        ParamValuesArray: _ParamValuesArray,
        PreviewMode: diagram.PreviewMode,
        flex: 1,
        background: {
          fill: bgcolor

        },
        animate: true,
        theme: 'diagram' + diagram.num, //diagram.diagram_theme,
        store: store1,
        legend: legend_position,
        shadow: true,
        insetPadding: 20,
        func_class_name: diagram.func_class_name,
        func_name: diagram.func_name,
        param_list: diagram.param_list,
        axes: [{
            grid: _grid_x,
            type: 'Radial',
            position: 'radial',
            labelTitle: {font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
            label: {
              font: diagram.axes_label_font_size + ' sans-serif', fill: diagram.label_color}
          }],
        series: ser
      }
      );
      break;
    case 8: // Датчик
      var store = [];

      var wchart = [];
      var w_x = [];
      cc = Ext.create('Ext.form.field.Display', {
        xtype: 'textfield',
        border: false,
        height: diagram.axes_font_size,
        value: '',
        fieldStyle: {"text-align": 'center', font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
        fieldLabel: '',
        hideLabel: true
      });
      w_x.push(cc);
      for (var i = 0; i < diagram.fields.length; i++) {
        cc = Ext.create('Ext.form.field.Display', {
          xtype: 'textfield',
          flex: 1,
          border: false,
          value: diagram.title[i],
          fieldStyle: {"text-align": 'right', font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
          fieldLabel: '',
          hideLabel: true
        });
        w_x.push(cc);

      }
      c_x = Ext.create('Ext.container.Container', {
        flex: 1,
        layout: {
          type: 'vbox',
          align: 'stretch'
        },
        border: false,
        style: {
          boderColor: '#000000',
          borderStyle: 'solid',
          borderWidth: '0px'
        },
        items: w_x});
      wchart.push(c_x);
      for (j = 0; j < store1.count(); j++) {
        store[j] = new Ext.data.JsonStore(diagram.store_data);
        store[j].removeAt(j + 1);
        store[j].removeAt(0, j);

        w_x = [];
        cc = Ext.create('Ext.form.field.Display', {
          xtype: 'textfield',
          border: false,
          height: diagram.axes_font_size,
          value: store[j].first().get('X'),
          fieldStyle: {"text-align": 'center', font: diagram.axes_font_size + ' sans-serif', color: diagram.axes_color},
          fieldLabel: '',
          hideLabel: true
        });
        w_x.push(cc);
        for (var i = 0; i < diagram.fields.length; i++) {
          cc = Ext.create('Ext.chart.Chart', {
            ParamValuesArray: _ParamValuesArray,
            PreviewMode: diagram.PreviewMode,
            flex: 1,
            border: false,
            xtype: 'chart',
            style: 'background:#fff',
            animate: {
              easing: 'bounceOut',
              duration: 500
            },
            store: store[j],
            insetPadding: 25,
            axes: [{
                type: 'gauge',
                grid: _grid_x,
                position: 'gauge',
                minimum: 0, //diagram.minimum,
                maximum: diagram.maximum,
                steps: 5,
                //margin: 7
              }],
            series: [{
                grid: _grid_y,
                type: 'gauge',
                field: diagram.fields[i],
                //    donut: 80,
                colorSet: [oColors[i], '#ddd']
              }]

          });

          w_x.push(cc);
        }//i
        c_x = Ext.create('Ext.container.Container', {
          flex: 1,
          layout: {
            type: 'vbox',
            align: 'stretch'
          },
          border: false,
          style: {
            boderColor: '#000000',
            borderStyle: 'solid',
            borderWidth: '0px'
          },
          items: w_x});
        wchart.push(c_x);
      }//j
      ;
      break;
  }
//wchart.hidden= true;
//var ObjectTab=Ext.create('Ext.tab.Panel', {
//  xtype: 'tabpanel',
//                region: 'center',
//                activeTab: 0,
//                id: 'ObjectsTab'+diagram.num,
//                resizeTabs: false,
//                enableTabScroll: false,
//                autoScroll: false,
//                
//  items:wchart
//});
//
//  return ObjectTab;
  Ext.resumeLayouts();

  return wchart;
}

function chart_click(obj) {
  Run_operation(null, this);
}

function chart_itemmousedown(obj) {
  chart_click(obj);
  var yField = obj.yField;
  if (yField == undefined)
    yField = obj.storeField;
  if (yField == undefined) {
    if (Array.isArray(obj.series.yField))
      yField = obj.series.field;
    else
      yField = obj.series.yField;

  }
  var cur_ser = yField.substr(1, 100);
  var serTitle;
  if (Array.isArray(obj.series.title))
    serTitle = obj.series.title[cur_ser];
  else
    serTitle = obj.series.title;

  var ref = obj.storeItem.data['param_list' + yField];
  ref.replace(new RegExp(':X:', "g"), obj.storeItem.data['X']);
  ref = Ext.JSON.decode(ref);
  if (ref.ParamValuesArray == undefined)
    ref.ParamValuesArray = {};
  for (var p in this.chart.ParamValuesArray) {
    if (ref.ParamValuesArray[p] == undefined)
      ref.ParamValuesArray[p] = this.chart.ParamValuesArray[p]
  }
  ref.ParamValuesArray.X = obj.storeItem.data['X'];
  ref.ParamValuesArray.S = serTitle;
  ref.ParamValuesArray.Y = obj.storeItem.data[yField];

  ref.PreviewMode = this.chart.PreviewMode;

  var operation = {func_class_name: obj.storeItem.data['func_class_name' + yField],
    func_name: obj.storeItem.data['func_name' + yField],
    param_list: ref};
  Run_operation(null, operation);

}