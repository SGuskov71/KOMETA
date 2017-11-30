/*
 * File: app/view/EditParams.js
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

Ext.define('Params.view.EditParams', {
  extend: 'Ext.window.Window',

  requires: [
    'Ext.grid.Panel',
    'Ext.grid.column.Column',
    'Ext.grid.View',
    'Ext.toolbar.Toolbar',
    'Ext.button.Button',
    'Ext.form.FieldSet',
    'Ext.form.field.ComboBox',
    'Ext.form.field.Checkbox',
    'Ext.panel.Tool'
  ],

  height: 429,
  width: 606,
  layout: 'fit',
  title: 'Редактор параметров',
  maximizable: true,
  modal: true,

  initComponent: function() {
    var me = this;

    Ext.applyIf(me, {
      items: [
        {
          xtype: 'container',
          itemId: '',
          layout: 'border',
          items: [
            {
              xtype: 'gridpanel',
              region: 'west',
              split: true,
              itemId: 'GridParamList',
              width: 150,
              title: 'Список параметров',
              hideHeaders: true,
              columns: [
                {
                  xtype: 'gridcolumn',
                  draggable: false,
                  resizable: false,
                  detachOnRemove: false,
                  enableColumnHide: false,
                  dataIndex: 'ParamCode',
                  hideable: false,
                  text: 'Код параметра',
                  flex: 1
                }
              ],
              viewConfig: {
                itemId: ''
              },
              listeners: {
                selectionchange: {
                  fn: me.onGridParamListSelectionChange,
                  scope: me
                }
              },
              dockedItems: [
                {
                  xtype: 'toolbar',
                  dock: 'top',
                  items: [
                    {
                      xtype: 'button',
                      handler: function(button, e) {
                        var grd=button.findParentByType('gridpanel');
                        var count = grd.getStore().getCount();
                        var newIndex = count+1;
                        var inst= grd.getStore().add({ParamCode:'Param'+ newIndex.toString(), ParamDescr:'Параметр '+ newIndex.toString(),
                            ParamTypeInput:0, ParamSlv: null, ParamSlvDescr: '',
                            ParamInterractive:true,  ParamMandatory:true,
                          ParamCheckInput:false, ParamCheckInputExpression:'', ParamDefaultValue:''});
                        grd.getSelectionModel().select(inst, true, false);
                        grd.getStore().sync();
                      },
                      itemId: 'Btn_Add',
                      iconCls: 'cls_add',
                      text: '',
                      tooltip: 'Добавить'
                    },
                    {
                      xtype: 'button',
                      handler: function(button, e) {
                        var GridParamList=button.findParentByType('gridpanel');

                        var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
                        if (selection) {
                          GridParamList.getStore().remove(selection);
                        }
                      },
                      disabled: true,
                      itemId: 'Btn_Delete',
                      iconCls: 'cls_del',
                      text: '',
                      tooltip: 'Удалить'
                    }
                  ]
                }
              ]
            },
            {
              xtype: 'fieldset',
              region: 'center',
              border: 1,
              itemId: 'ContainerParamProps',
              autoScroll: true,
              title: 'Свойства параметра',
              layout: {
                type: 'vbox',
                align: 'stretch',
                defaultMargins: {
                  top: 0,
                  right: 0,
                  bottom: 0,
                  left: 0
                }
              },
              items: [
                {
                  xtype: 'textfield',
                  itemId: 'Edt_ParamCode',
                  fieldLabel: 'Код параметра',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamCodeChange,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'textfield',
                  itemId: 'Edt_ParamDescr',
                  fieldLabel: 'Описание',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamCodeChange1,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'combobox',
                  itemId: 'Edt_ParamTypeInput',
                  fieldLabel: 'Тип ввода параметра',
                  editable: false,
                  queryMode: 'local',
                  store: [
                    [
                      0,
                      'простой'
                    ],
                    [
                      1,
                      'дата'
                    ],
                    [
                      2,
                      'словарь выпадающий'
                    ],
                    [
                      3,
                      'число'
                    ],
                    [
                      6,
                      'словарь в отдельном окне'
                    ]
                  ],
                  valueField: 'id',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamTypeInputChange,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'combobox',
                  onTriggerClick: function(evnt) {
                    var me = this;
                    var win = SelectValSlv({sysname: 'sv_mb_object_select', ExtFilterWhereCond: ' and id_object_type=1 ', object_Caption: 'Выбор объекта для создания формы ввода', HelpContext: ''});
                    win.addListener('ValSlvSelected', function (context, SelID, SelDescr) {
                      Common_class.get_sysname(SelID.id_object, function (response, option) {
                        var sysname = response;
                        var win = me.findParentByType('window');
                        var GridParamList = win.down('#GridParamList');
                        var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
                        if (selection) {
                          selection.set('ParamSlv', sysname);
                          selection.set('ParamSlvDescr', SelDescr);
                          selection.commit();
                        }
                        me.setValue(SelDescr);
                      });
                    });

                  },
                  itemId: 'Edt_ParamSlv',
                  fieldLabel: 'Словарь',
                  editable: false,
                  triggerCls: 'x-form-search-trigger'
                },
                {
                  xtype: 'checkboxfield',
                  itemId: 'Edt_ParamInterractive',
                  fieldLabel: 'Интеррактивный',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamInterractiveChange,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'checkboxfield',
                  itemId: 'Edt_ParamMandatory',
                  fieldLabel: 'Обязательный',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamMandatoryChange,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'checkboxfield',
                  itemId: 'Edt_ParamCheckInput',
                  fieldLabel: 'Проверка при вводе',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamCheckInputChange,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'textfield',
                  itemId: 'Edt_ParamCheckInputExpression',
                  fieldLabel: 'Выражение для проверки',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamCheckInputExpressionChange,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'textfield',
                  itemId: 'Edt_ParamDefaultValue',
                  fieldLabel: 'Значение по умолчанию',
                  listeners: {
                    change: {
                      fn: me.onEdt_ParamDefaultValueChange,
                      scope: me
                    }
                  }
                }
              ]
            }
          ]
        }
      ],
      listeners: {
        show: {
          fn: me.onWindowShow,
          scope: me
        }
      },
      tools: [
        {
          xtype: 'tool',
          tooltip: 'Схранить и выйти',
          type: 'save',
          listeners: {
            click: {
              fn: me.onToolClick,
              scope: me
            }
          }
        }
      ]
    });

    me.callParent(arguments);
  },

  onGridParamListSelectionChange: function(model, selected, eOpts) {
    var win=model.view.panel.findParentByType('window');
    var ContainerParamProps = win.down('#ContainerParamProps');
    var Btn_Delete = model.view.panel.down('#Btn_Delete');
    if (selected.length > 0) {
        Btn_Delete.enable();
        ContainerParamProps.show();
     //выставляю значения компонентов
        win.down('#Edt_ParamCode').setValue(selected[0].data.ParamCode);
        win.down('#Edt_ParamDescr').setValue(selected[0].data.ParamDescr);
        win.down('#Edt_ParamTypeInput').setValue(selected[0].data.ParamTypeInput);
        win.down('#Edt_ParamSlv').setValue(selected[0].data.ParamSlvDescr);
        win.down('#Edt_ParamInterractive').setValue(selected[0].data.ParamInterractive);
        win.down('#Edt_ParamMandatory').setValue(selected[0].data.ParamMandatory);
        win.down('#Edt_ParamCheckInput').setValue(selected[0].data.ParamCheckInput);
        win.down('#Edt_ParamCheckInputExpression').setValue(selected[0].data.ParamCheckInputExpression);
        win.down('#Edt_ParamDefaultValue').setValue(selected[0].data.ParamDefaultValue);
    } else {
        Btn_Delete.disable();
        ContainerParamProps.hide();
    }
  },

  onEdt_ParamCodeChange: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
        selection.set('ParamCode', newValue);
        selection.commit();
    }
  },

  onEdt_ParamCodeChange1: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
        selection.set('ParamDescr', newValue);
        selection.commit();
    }
  },

  onEdt_ParamTypeInputChange: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
        selection.set('ParamTypeInput', newValue);
        selection.commit();
    }
  },

  onEdt_ParamInterractiveChange: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
        selection.set('ParamInterractive', newValue);
        selection.commit();
    }
  },

  onEdt_ParamMandatoryChange: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
        selection.set('ParamMandatory', newValue);
        selection.commit();
    }
  },

  onEdt_ParamCheckInputChange: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
      selection.set('ParamCheckInput', newValue);
      selection.commit();
    }
  },

  onEdt_ParamCheckInputExpressionChange: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
        selection.set('ParamCheckInputExpression', newValue);
        selection.commit();
    }
  },

  onEdt_ParamDefaultValueChange: function(field, newValue, oldValue, eOpts) {
    var win=field.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var selection = GridParamList.getView().getSelectionModel().getSelection()[0];
    if (selection) {
        selection.set('ParamDefaultValue', newValue);
        selection.commit();
    }
  },

  onWindowShow: function(component, eOpts) {
    var ContainerParamProps = component.down('#ContainerParamProps');
    ContainerParamProps.hide();

    //var Edt_ParamTypeInput = component.down('#Edt_ParamTypeInput');
    //Edt_ParamTypeInput.store= component.mainContainer.Store_ParamTypeInput;
    //  Edt_ParamTypeInput.bindStore(component.mainContainer.Store_ParamTypeInput);

    var store = Ext.create('Ext.data.Store', {
      fields: ['ParamCode', 'ParamDescr',
               'ParamTypeInput', 'ParamSlv', 'ParamSlvDescr',
               'ParamInterractive','ParamMandatory','ParamCheckInput', 'ParamCheckInputExpression', 'ParamDefaultValue'],
      data: component.Params
    });

    var GridParamList = component.down('#GridParamList');
    GridParamList.store=store;
    GridParamList.bindStore(store);
  },

  onToolClick: function(tool, e, eOpts) {
    var win=tool.findParentByType('window');
    var GridParamList = win.down('#GridParamList');
    var datastore= GridParamList.getStore();
    new_data = [];
    Ext.each(datastore.getRange(), function (rec) {
      rec.commit();
      new_data.push(rec.data);
    });
    win.fireEvent('BtnOk', new_data);
  }

});