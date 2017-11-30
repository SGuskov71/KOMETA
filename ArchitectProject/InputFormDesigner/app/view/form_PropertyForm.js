/*
 * File: app/view/form_PropertyForm.js
 *
 * This file was generated by Sencha Architect
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

Ext.define('InputFormDesigner.view.form_PropertyForm', {
  extend: 'Ext.form.Panel',

  requires: [
    'Ext.form.Panel',
    'Ext.form.field.Display',
    'Ext.form.field.Number',
    'Ext.form.field.ComboBox',
    'Ext.form.field.TextArea',
    'Ext.grid.property.Grid'
  ],

  height: 499,
  id: 'form_PropertyForm',
  itemId: 'form_PropertyForm',
  width: 785,
  title: 'Свойства формы',

  layout: {
    type: 'vbox',
    align: 'stretch'
  },

  initComponent: function() {
    var me = this;

    Ext.applyIf(me, {
      items: [
        {
          xtype: 'form',
          autoScroll: true,
          bodyPadding: 7,
          layout: {
            type: 'vbox',
            align: 'stretch'
          },
          items: [
            {
              xtype: 'displayfield',
              itemId: 'id_objectDescription',
              fieldLabel: 'Объект',
              labelWidth: 95
            },
            {
              xtype: 'textfield',
              itemId: 'Description',
              fieldLabel: 'Наименование',
              labelWidth: 95,
              listeners: {
                change: {
                  fn: me.onEdt_TextBlockChange,
                  scope: me
                }
              }
            },
            {
              xtype: 'textfield',
              itemId: 'Code',
              fieldLabel: 'Код формы',
              labelWidth: 95,
              listeners: {
                change: {
                  fn: me.onEdt_TextBlockChange2,
                  scope: me
                }
              }
            },
            {
              xtype: 'panel',
              border: false,
              title: '',
              layout: {
                type: 'hbox',
                align: 'stretchmax'
              },
              items: [
                {
                  xtype: 'numberfield',
                  itemId: 'ShowOrder',
                  margin: '3 3 3 0',
                  fieldLabel: 'Порядок отображения формы',
                  labelWidth: 180,
                  listeners: {
                    change: {
                      fn: me.onEdt_TextBlockChange21,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'numberfield',
                  itemId: 'form_width',
                  margin: 3,
                  fieldLabel: 'Ширина формы',
                  labelWidth: 98,
                  listeners: {
                    change: {
                      fn: me.onEdt_TextBlockChange211,
                      scope: me
                    }
                  }
                },
                {
                  xtype: 'numberfield',
                  itemId: 'form_height',
                  margin: 3,
                  fieldLabel: 'Высота формы',
                  labelWidth: 98,
                  listeners: {
                    change: {
                      fn: me.onEdt_TextBlockChange2111,
                      scope: me
                    }
                  }
                }
              ]
            },
            {
              xtype: 'combobox',
              hidden: true,
              itemId: 'Layout',
              fieldLabel: 'Способ размещения объектов внутри контейнера',
              labelWidth: 200,
              readOnly: true,
              readOnlyCls: 'x-item-disabled-readonly',
              editable: false,
              store: [
                'auto',
                'border',
                'column',
                'fit',
                'hbox',
                'vbox'
              ],
              listeners: {
                change: {
                  fn: me.onLayoutChange1,
                  scope: me
                }
              }
            },
            {
              xtype: 'combobox',
              itemId: 'ComboFormLayouts',
              width: 150,
              fieldLabel: 'Шаблон размещения объектов внутри контейнера',
              labelWidth: 200,
              editable: false,
              displayField: 'caption',
              queryMode: 'local',
              valueField: 'code',
              listeners: {
                change: {
                  fn: me.onLayoutChange11,
                  scope: me
                }
              }
            },
            {
              xtype: 'textareafield',
              itemId: 'form_validator',
              fieldLabel: 'Выражение для проверки правильности заполнения формы',
              labelWidth: 150,
              rows: 3,
              listeners: {
                change: {
                  fn: me.onEdt_TextBlockChange1,
                  scope: me
                }
              }
            },
            {
              xtype: 'textareafield',
              itemId: 'ExpressionBeforeShow',
              fieldLabel: 'Вычисление перед отображением формы',
              labelWidth: 150,
              rows: 3,
              listeners: {
                change: {
                  fn: me.onEdt_TextBlockChange11,
                  scope: me
                }
              }
            }
          ]
        },
        {
          xtype: 'propertygrid',
          flex: 1,
          height: 194,
          itemId: 'ContainerProps',
          autoScroll: true,
          bodyBorder: false,
          title: 'Дополнительные свойства контейнера',
          nameColumnWidth: 200,
          listeners: {
            beforerender: {
              fn: me.onPropertygridBeforeRender11,
              scope: me
            },
            propertychange: {
              fn: me.onControlPropsPropertyChange11,
              scope: me
            }
          }
        }
      ]
    });

    me.callParent(arguments);
  },

  onEdt_TextBlockChange: function(field, newValue, oldValue, eOpts) {
    this.rawData.Description=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

    if(this.needUpdateTree==true){
      this.record.set('text', this.rawData.Description);
      this.record.commit();
      this.rawData.text=this.rawData.Description;
    }
  },

  onEdt_TextBlockChange2: function(field, newValue, oldValue, eOpts) {
    this.rawData.Code=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onEdt_TextBlockChange21: function(field, newValue, oldValue, eOpts) {
    this.rawData.ShowOrder=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onEdt_TextBlockChange211: function(field, newValue, oldValue, eOpts) {
    this.rawData.form_width=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onEdt_TextBlockChange2111: function(field, newValue, oldValue, eOpts) {
    this.rawData.form_height=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onLayoutChange1: function(field, newValue, oldValue, eOpts) {
        this.rawData.Layout=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onLayoutChange11: function(field, newValue, oldValue, eOpts) {
        this.rawData.FormLayouts=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onEdt_TextBlockChange1: function(field, newValue, oldValue, eOpts) {
    this.rawData.form_validator=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onEdt_TextBlockChange11: function(field, newValue, oldValue, eOpts) {
    this.rawData.ExpressionBeforeShow=newValue;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  onPropertygridBeforeRender11: function(component, eOpts) {
    var cols = component.getView().getHeaderCt().getGridColumns();
    cols[0].setText("Свойство");
    cols[1].setText("Значение");
  },

  onControlPropsPropertyChange11: function(source, recordId, value, oldValue, eOpts) {
    this.rawData.ContainerProps[recordId]=value;
    var mainContainer = field.ownerCt.findParentByType('#DesignMainContainer');
    mainContainer.modified=true;

  },

  LoadNodeValues: function(rawData, record) {
    this.needUpdateTree=false; //нужно для отключения переписывания текста в дереве при присвоении значений текста
    this.rawData = rawData;
    this.record = record;
    var mainContainer = this.findParentByType('#DesignMainContainer');
    this.down('#Description').setValue(rawData.Description);
    this.down('#id_objectDescription').setValue(rawData.id_objectDescription);
    this.down('#Layout').setValue(rawData.Layout);
    this.down('#Code').setValue(rawData.Code);
    this.down('#ShowOrder').setValue(rawData.ShowOrder);
    this.down('#form_width').setValue(rawData.form_width);
    this.down('#form_height').setValue(rawData.form_height);
    this.down('#form_validator').setValue(rawData.form_validator);
    this.down('#ExpressionBeforeShow').setValue(rawData.ExpressionBeforeShow);
    this.down('#ComboFormLayouts').setValue(rawData.FormLayouts);
    this.needUpdateTree=true;
    var ContainerProps=mainContainer.down('#ContainerProps');
    if((rawData.ContainerProps!=undefined)||(mainContainer.AdditionalContainerDefaultProps!=undefined)){
      //При открытии формы ввода добавить в "Дополнительные свойства" те которые определены для этого элемента ввода и удалить те которых нет.
      var TempObj={};
      for(var key in mainContainer.AdditionalContainerDefaultProps){
        if((rawData.ContainerProps!=undefined)&&(rawData.ContainerProps[key]!=undefined)){
          TempObj[key]=rawData.ContainerProps[key];
        }else{
          TempObj[key]=mainContainer.AdditionalContainerDefaultProps[key];
        }
      }
      rawData.ContainerProps=TempObj;
      ContainerProps.setSource(rawData.ContainerProps);
    }else{
      ContainerProps.setSource({});
    }
  }

});