/*
 * File: app/view/FieldCondition_PropertyForm.js
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

Ext.define('QueryBuilder.view.FieldCondition_PropertyForm', {
    extend: 'Ext.form.Panel',

    requires: [
        'Ext.form.Panel',
        'Ext.form.field.Display',
        'Ext.form.field.ComboBox',
        'Ext.form.RadioGroup',
        'Ext.form.field.Radio'
    ],

    height: 509,
    itemId: 'FieldCondition_PropertyForm',
    width: 636,
    autoScroll: true,
    layout: 'fit',
    bodyBorder: false,
    title: 'Свойства элемента запроса',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'form',
                    autoScroll: true,
                    bodyPadding: 16,
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    items: [
                        {
                            xtype: 'displayfield',
                            itemId: 'DataField',
                            fieldLabel: 'Код поля',
                            labelWidth: 65
                        },
                        {
                            xtype: 'textfield',
                            itemId: 'Caption',
                            margin: '0 0 10 0',
                            maxWidth: 600,
                            fieldLabel: 'Наименование',
                            listeners: {
                                change: {
                                    fn: me.onCaptionChange,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'combobox',
                            itemId: 'Operation',
                            maxWidth: 600,
                            fieldLabel: 'Операция сравнения',
                            labelWidth: 130,
                            editable: false,
                            displayField: 'name',
                            forceSelection: true,
                            queryMode: 'local',
                            typeAhead: true,
                            valueField: 'id',
                            listeners: {
                                change: {
                                    fn: me.onOperationChange,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'radiogroup',
                            itemId: 'RG_DataSource',
                            maxWidth: 350,
                            fieldLabel: 'Выбрать для сравнения',
                            labelWidth: 150,
                            items: [
                                {
                                    xtype: 'radiofield',
                                    itemId: 'RB_Value',
                                    boxLabel: 'Значение',
                                    checked: true,
                                    listeners: {
                                        change: {
                                            fn: me.onRB_TextChange,
                                            scope: me
                                        }
                                    }
                                },
                                {
                                    xtype: 'radiofield',
                                    itemId: 'RB_Param',
                                    boxLabel: 'Параметр',
                                    listeners: {
                                        change: {
                                            fn: me.onRB_SQLChange,
                                            scope: me
                                        }
                                    }
                                }
                            ]
                        },
                        {
                            xtype: 'combobox',
                            itemId: 'ParamCode',
                            maxWidth: 600,
                            fieldLabel: 'Параметр',
                            labelWidth: 70,
                            editable: false,
                            displayField: 'ParamDescr',
                            forceSelection: true,
                            queryMode: 'local',
                            typeAhead: true,
                            valueField: 'ParamCode',
                            listeners: {
                                change: {
                                    fn: me.onComboReportFieldListChange,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'textfield',
                            itemId: 'Value',
                            maxWidth: 600,
                            fieldLabel: 'Значение',
                            labelWidth: 70,
                            listeners: {
                                change: {
                                    fn: me.onValidateConditionChange2,
                                    scope: me
                                }
                            }
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    },

    onCaptionChange: function(field, newValue, oldValue, eOpts) {
        this.rawData.Caption=newValue;
        if(this.needUpdateTree==true){
            this.record.set('text', this.rawData.Caption);
            this.record.commit();
            this.rawData.text=this.rawData.Caption;
        var DesignMainContainer=field. findParentByType('#DesignMainContainer');
                    DesignMainContainer.modified=true;
        }
    },

    onOperationChange: function(field, newValue, oldValue, eOpts) {
        this.rawData.Operation=newValue;
        var DesignMainContainer=field. findParentByType('#DesignMainContainer');
                    DesignMainContainer.modified=true;

    },

    onRB_TextChange: function(field, newValue, oldValue, eOpts) {
        if(newValue==true)
        {
            field.ownerCt.down('#RB_Param').setValue(false);
            this.rawData.DataSource=field.itemId;
            var FieldCondition_PropertyForm = field.findParentByType('#FieldCondition_PropertyForm');
            FieldCondition_PropertyForm.SetVisibleFields();
            var DesignMainContainer=field. findParentByType('#DesignMainContainer');
                    DesignMainContainer.modified=true;

        }

    },

    onRB_SQLChange: function(field, newValue, oldValue, eOpts) {
        if(newValue==true)
        {
            field.ownerCt.down('#RB_Value').setValue(false);
            this.rawData.DataSource=field.itemId;
            var FieldCondition_PropertyForm = field.findParentByType('#FieldCondition_PropertyForm');
            FieldCondition_PropertyForm.SetVisibleFields();
            var DesignMainContainer=field. findParentByType('#DesignMainContainer');
                    DesignMainContainer.modified=true;

        }
    },

    onComboReportFieldListChange: function(field, newValue, oldValue, eOpts) {
        this.rawData.ParamCode=newValue;
        var DesignMainContainer=field. findParentByType('#DesignMainContainer');
        DesignMainContainer.modified=true;

    },

    onValidateConditionChange2: function(field, newValue, oldValue, eOpts) {
        this.rawData.Value=newValue;
        var DesignMainContainer=field. findParentByType('#DesignMainContainer');
                    DesignMainContainer.modified=true;

    },

    LoadNodeValues: function(rawData, record) {
        this.needUpdateTree=false; //нужно для отключения переписывания текста в дереве при присвоении значений текста
        this.rawData = rawData;
        this.record = record;
        var mainContainer = this.findParentByType('#DesignMainContainer');
        this.down('#Caption').setValue(rawData.Caption);
        var OperationStoreData=[];
        var tempData=mainContainer.GetItemConditionListStoreData(mainContainer.GetMasterObjectNode(record), rawData.DataField);
        var values = Ext.Object.getValues(tempData);
        var keys = Ext.Object.getKeys(tempData);
        var length = keys.length;
        for (var j = 0; j < length; j++) {
            OperationStoreData.push({'id':keys[j], 'name':values[j]});
        }
        var store = Ext.create('Ext.data.Store', {
            fields: ['id', 'name'],
            data : OperationStoreData
        });
        this.down('#Operation').getStore().destroy();
        this.down('#Operation').bindStore(store);
        this.down('#Operation').setValue(rawData.Operation);
        this.down('#DataField').setValue(rawData.DataField);
        this.down('#Value').setValue(rawData.Value);
        this.down('#ParamCode').getStore().destroy();
        var store1 = Ext.create('Ext.data.Store', {
            fields: ['ParamCode', 'ParamDescr'],
            data: mainContainer.down('#StructureTree').getRootNode().raw.QueryParams
        });
        //this.down('#ParamCode').store=store1;
        this.down('#ParamCode').bindStore(store1);
        this.down('#ParamCode').setValue(rawData.ParamCode);
        this.down('#RB_Value').setValue(false);
        this.down('#RB_Param').setValue(false);
        if((this.rawData.DataSource!=undefined)&&(this.rawData.DataSource!='')){
            this.down('#'+this.rawData.DataSource).setValue(true);
        }else{
            this.down('#RB_Value').setValue(true);
        }

        //this.down('#').setValue(rawData.);

        this.SetVisibleFields();
        this.needUpdateTree=true;
    },

    SetVisibleFields: function() {
        this.down('#ParamCode').hide();
        this.down('#Value').hide();

        if(this.rawData.DataSource=='RB_Value')
            this.down('#Value').show();
        else if(this.rawData.DataSource=='RB_Param')
            this.down('#ParamCode').show();
    }

});