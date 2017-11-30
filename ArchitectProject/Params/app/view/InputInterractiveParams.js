/*
 * File: app/view/InputInterractiveParams.js
 *
 * This file was generated by Sencha Architect version 3.5.0.
 * http://www.sencha.com/products/architect/
 *
 * This file requires use of the Ext JS 4.2.x library, under independent license.
 * License of Sencha Architect does not include license for Ext JS 4.2.x. For more
 * details see http://www.sencha.com/license or contact license@sencha.com.
 *
 * This file will be auto-generated each and everytime you save your project.
 *
 * Do NOT hand edit this file.
 */

Ext.define('Params.view.InputInterractiveParams', {
  extend: 'Ext.panel.Panel',

  requires: [
    'Ext.toolbar.Toolbar',
    'Ext.container.ButtonGroup',
    'Ext.button.Button',
    'Ext.form.FieldSet'
  ],

  height: 325,
  width: 555,
  layout: 'fit',
  header: false,
  manageHeight: false,
  title: 'Ввод параметров',

  initComponent: function() {
    var me = this;

    Ext.applyIf(me, {
      dockedItems: [
        {
          xtype: 'toolbar',
          dock: 'bottom',
          layout: {
            type: 'hbox',
            pack: 'end'
          },
          items: [
            {
              xtype: 'buttongroup',
              title: '',
              columns: 2,
              items: [
                {
                  xtype: 'button',
                  handler: function(button, e) {
                    var win=button.findParentByType('panel').findParentByType('panel');
                    var ControlContainer= win.down('#ControlContainer');
                    var controls= ControlContainer.query('*');
                    var ResultArrayParamValues={};
                    var CanClose=true;
                    Ext.each(controls, function(control) {
                      var key=control.ParamCode;
                      if(control.ParamTypeInput==1){
                        ResultArrayParamValues[key]= Ext.Date.format(control.getValue(), "Y-m-d");
                      }else{
                      ResultArrayParamValues[key]= control.getValue();}
                      if(((ResultArrayParamValues[key]==null)||(ResultArrayParamValues[key]==''))&&(control.ParamMandatory==true)){
                        control.focus(false, 1000);
                        control.markInvalid('Не заполнено обязательное значение');
                        CanClose=false;
                        return 0;
                      }
                      if(control.isValid()!=true){
                        control.focus(false, 1000);
                        control.markInvalid('Не вернные значения');
                        CanClose=false;
                        return 0;
                      }
                    });
                    if(CanClose==true)
                    win.fireEvent('BtnOk', ResultArrayParamValues);
                  },
                  text: 'Применить'
                },
                {
                  xtype: 'button',
                  handler: function(button, e) {
                    CloseWindow(button.findParentByType('window'));
                  },
                  text: 'Отмена'
                }
              ]
            }
          ]
        }
      ],
      items: [
        {
          xtype: 'fieldset',
          itemId: 'ControlContainer',
          autoScroll: true,
          title: '',
          layout: {
            type: 'vbox',
            align: 'stretch'
          }
        }
      ],
      listeners: {
        render: {
          fn: me.onWindowShow,
          scope: me
        }
      }
    });

    me.callParent(arguments);
  },

  onWindowShow: function(component, eOpts) {
    var ControlContainer = component.down('#ControlContainer');
    Ext.each(this.ArrayInterractiveParam, function (par) {
      if (par.ParamInterractive == true) {
        if (par.ParamMandatory == true) {
          par.ParamDescr = par.ParamDescr + " (*)";
        }
        var control = null;
        switch (par.ParamTypeInput) {
          case 0:
            control = Ext.create('Ext.form.field.Text', {fieldLabel: par.ParamDescr});
            control.ParamCheckInput = par.ParamCheckInput;
            control.ParamCheckInputExpression = par.ParamCheckInputExpression;
            if (control.ParamCheckInput == true) {
              control.validateOnChange = true;
              control.validateOnBlur = true;
              control.validator = function (v) {
                var t = this;
                if (!((t.value == undefined) || (t.value == "") ||
                      (t.ParamCheckInputExpression == undefined) || (t.ParamCheckInputExpression == ""))) {
                  var b = eval(t.ParamCheckInputExpression);
                  if (b) {
                    return 'Не вернные значения';
                  } else
                    return true;
                } else
                  return true;
              };
            }
            ;
            break;
          case 1:
            par.Value = Ext.Date.parse(par.Value, "d.m.Y", false);
            control = Ext.create('Ext.form.field.Date', {fieldLabel: par.ParamDescr, format: "d.m.Y"});
            break;
          case 2:
            var store = Ext.create('Ext.data.SimpleStore', {
              fields: [
                {
                  name: 'id',
                  type: 'string'
                },
                {
                  name: 'name',
                  type: 'string'
                }
              ],
              autoLoad: false,
              proxy: {
                type: 'memory',
                reader: {
                  type: 'json'
                }
              }
            });
            Common_class.GetComboBoxStore(par.ParamSlv, function (response) {
              store.loadData(response.result);
              control = Ext.create('Ext.form.field.ComboBox', {fieldLabel: par.ParamDescr,
                                                               valueField: 'id',
                                                               displayField: 'name',
                                                               store: store, queryMode: 'local',
                                                               autoSelect: true,
                                                               editable: false,
                                                               enableKeyEvents: true,
                                                               typeAhead: true,
                                                               listeners: {
                                                                 keydown: function (obj, e) {
                                                                   if ((e.getCharCode() == e.BACKSPACE) && (e.ctrlKey)) {
                                                                     e.preventDefault();
                                                                     obj.clearValue();
                                                                     obj.applyEmptyText();
                                                                   }
                                                                 }
                                                               }
                                                              });
              control.ParamTypeInput = par.ParamTypeInput;
              control.ParamCode = par.ParamCode;
              control.ParamMandatory = par.ParamMandatory;
              control.setValue(par.Value);
              ControlContainer.add(control);

            });
            return;
          case 3:
            control = Ext.create('Ext.form.field.Number', {fieldLabel: par.ParamDescr});
            break;
          case 6:
            control = Ext.create('Ext.form.field.ComboBox',
                                 {fieldLabel: par.ParamDescr,
                                  onTriggerClick: function (evnt) {
                                    var me = this;

                                    var win = SelectValSlv({sysname: par.ParamSlv, ExtFilterWhereCond: '', object_Caption: 'Выбор объекта', HelpContext: ''});
                                    win.addListener('ValSlvSelected', function (context, SelID, SelDescr) {
                                      Ext.each(SelID, function (val) {
                                        me.setValue(val);
                                      });
                                    });
                                  },
                                  editable: false,
                                  triggerCls: 'x-form-search-trigger',
                                  enableKeyEvents: true,
                                  listeners: {
                                    keydown: function (obj, e) {
                                      if ((e.getCharCode() == e.BACKSPACE) && (e.ctrlKey)) {
                                        e.preventDefault();
                                        obj.setValue(null);
                                        obj.applyEmptyText();
                                      }
                                    }}
                                 });
            break;
        }
        if (control) {
          control.ParamTypeInput = par.ParamTypeInput;
          control.ParamCode = par.ParamCode;
          control.ParamMandatory = par.ParamMandatory;
          control.setValue(par.Value);
          ControlContainer.add(control);
        }
      }
    });
  }

});