/*
 * File: app/view/list_PropertyForm.js
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

Ext.define('Report.view.list_PropertyForm', {
    extend: 'Ext.form.Panel',

    requires: [
        'Ext.form.RadioGroup',
        'Ext.form.field.Radio',
        'Ext.panel.Panel',
        'Ext.form.field.TextArea',
        'Ext.panel.Tool',
        'Ext.form.field.ComboBox'
    ],

    height: 538,
    itemId: 'list_PropertyForm',
    width: 761,
    bodyPadding: 10,
    title: 'Свойства списка',

    layout: {
        type: 'vbox',
        align: 'stretch'
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'radiogroup',
                    itemId: 'RG_DataSource1',
                    maxWidth: 400,
                    fieldLabel: 'Источник данных',
                    labelWidth: 110,
                    items: [
                        {
                            xtype: 'radiofield',
                            itemId: 'RB_Text',
                            boxLabel: 'Текст',
                            checked: true,
                            listeners: {
                                change: {
                                    fn: me.onRB_TextChange11,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'radiofield',
                            itemId: 'RB_SQL',
                            margin: '0 0 0 -50',
                            boxLabel: 'Поле SQL запроса',
                            listeners: {
                                change: {
                                    fn: me.onRB_SQLChange11,
                                    scope: me
                                }
                            }
                        }
                    ]
                },
                {
                    xtype: 'fieldcontainer',
                    flex: 1,
                    hidden: true,
                    itemId: 'ContainerSQLFields',
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    items: [
                        {
                            xtype: 'panel',
                            flex: 1,
                            itemId: 'PanelSQL',
                            layout: 'fit',
                            title: 'Текст SQL-запроса',
                            items: [
                                {
                                    xtype: 'textareafield',
                                    itemId: 'EdtSQL',
                                    rows: 6,
                                    listeners: {
                                        blur: {
                                            fn: me.onEdtSQLBlur,
                                            scope: me
                                        }
                                    }
                                }
                            ],
                            tools: [
                                {
                                    xtype: 'tool',
                                    tooltip: 'Открыть текст запроса в отдельном окне',
                                    type: 'maximize',
                                    listeners: {
                                        click: {
                                            fn: me.onToolClick,
                                            scope: me
                                        }
                                    }
                                }
                            ]
                        },
                        {
                            xtype: 'textareafield',
                            itemId: 'EdtSQLConditions',
                            fieldLabel: 'Дополнительные условия запроса',
                            labelWidth: 110,
                            listeners: {
                                change: {
                                    fn: me.onEdtSQLConditionsChange,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'combobox',
                            itemId: 'ComboFieldListLinkText',
                            fieldLabel: 'Поле данных значений списка',
                            labelWidth: 140,
                            editable: false,
                            displayField: 'id',
                            forceSelection: true,
                            queryMode: 'local',
                            valueField: 'id',
                            listeners: {
                                change: {
                                    fn: me.onComboReportFieldListChange112,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'checkboxfield',
                            itemId: 'ChkShowError',
                            fieldLabel: '',
                            boxLabel: 'Выводить сообщение об ошибке выполнения запроса',
                            listeners: {
                                change: {
                                    fn: me.onChkShowErrorChange1,
                                    scope: me
                                }
                            }
                        },
                        {
                            xtype: 'checkboxfield',
                            itemId: 'ChkShowEmptyMessage',
                            fieldLabel: '',
                            boxLabel: 'Выводить сообщение о пустом результате выполнения запроса',
                            listeners: {
                                change: {
                                    fn: me.onChkShowEmptyMessageChange1,
                                    scope: me
                                }
                            }
                        }
                    ]
                },
                {
                    xtype: 'fieldcontainer',
                    flex: 1,
                    itemId: 'ContainerTextListValues',
                    layout: 'fit',
                    fieldLabel: '',
                    items: [
                        {
                            xtype: 'textareafield',
                            itemId: 'EdtListValues',
                            fieldLabel: 'Список элементов (разделитель - Enter)',
                            labelWidth: 135,
                            rows: 7,
                            listeners: {
                                change: {
                                    fn: me.onEdtSQLChange111,
                                    scope: me
                                }
                            }
                        }
                    ]
                },
                {
                    xtype: 'container',
                    layout: {
                        type: 'vbox',
                        align: 'stretch',
                        pack: 'end'
                    },
                    items: [
                        {
                            xtype: 'combobox',
                            flex: 1,
                            itemId: 'ComboTextSyle',
                            fieldLabel: 'Стиль текста',
                            labelWidth: 85,
                            displayField: 'id',
                            forceSelection: true,
                            queryMode: 'local',
                            valueField: 'id',
                            listeners: {
                                change: {
                                    fn: me.onComboReportFieldListChange11,
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

    onRB_TextChange11: function(field, newValue, oldValue, eOpts) {
        if(newValue==true)
        {
            this.down('#ContainerTextListValues').show();
            this.down('#ContainerSQLFields').hide();
            field.ownerCt.down('#RB_SQL').setValue(false);
            this.rawData.DataSource=field.itemId;
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

        }

    },

    onRB_SQLChange11: function(field, newValue, oldValue, eOpts) {
        if(newValue==true)
        {
            this.down('#ContainerTextListValues').hide();
            this.down('#ContainerSQLFields').show();
            field.ownerCt.down('#RB_Text').setValue(false);
            this.rawData.DataSource=field.itemId;
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

        }
    },

    onEdtSQLBlur: function(component, e, eOpts) {
        var SQLText=component.value;
        Report_class.GetSQLFieldsList(SQLText, function(response, options) {
            Ext.MessageBox.hide();
            var result = response;
            if ((result.success === false) && (result.result == 're_connect')) {
                Ext.MessageBox.alert('Подключение',result.msg);
                window.onbeforeunload = null;
                findFirstWindow().window.location.href = __first_page;
                return;
            }
            if (result.success) {
                var ComboReportFieldListStoreData= result.result;// сохраняю список полей отчета для выбора из комбо
                var list_PropertyForm=component.findParentByType('#list_PropertyForm');

                list_PropertyForm.down('#EdtSQL').setValue(SQLText);
                list_PropertyForm.rawData.SQL=SQLText;
                var Combo = list_PropertyForm.down('#ComboFieldListLinkText');
                Combo.getStore().removeAll();
                Ext.Array.each(ComboReportFieldListStoreData, function(value) {
                    Combo.store.add({id: value});
                });
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

                CloseWindow(wSQL);
            } else {
                Ext.MessageBox.alert("Ошибка выполнения SQL запроса : " , result.msg);
            }
        });

    },

    onToolClick: function(tool, e, eOpts) {
        var list_PropertyForm=tool.findParentByType('#list_PropertyForm');

        SQLEditor(list_PropertyForm.down('#EdtSQL').getValue(),function(SQLText)
                  {
                      list_PropertyForm.down('#EdtSQL').setValue(SQLText);
            var mainContainer = tool.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

                      //table_PropertyForm.rawData.SQL=SQLText;
                  }
                 );

    },

    onEdtSQLConditionsChange: function(field, newValue, oldValue, eOpts) {
        this.rawData.SQLConditions=newValue;
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

    },

    onComboReportFieldListChange112: function(field, newValue, oldValue, eOpts) {
        this.rawData.DBLinkText=newValue;
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

    },

    onChkShowErrorChange1: function(field, newValue, oldValue, eOpts) {
        this.rawData.ShowError=newValue;
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

    },

    onChkShowEmptyMessageChange1: function(field, newValue, oldValue, eOpts) {
        this.rawData.ShowEmptyMessage=newValue;
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

    },

    onEdtSQLChange111: function(field, newValue, oldValue, eOpts) {
        this.rawData.ListValues=newValue;
            var mainContainer = field.findParentByType('#DesignReportMainContainer');
            mainContainer.modified = true;

    },

    onComboReportFieldListChange11: function(field, newValue, oldValue, eOpts) {
        this.rawData.TextStyle=newValue;
        var mainContainer = field.findParentByType('#DesignReportMainContainer');
        mainContainer.modified = true;


    },

    LoadNodeValues: function(rawData) {
        this.rawData=rawData;
        this.down('#EdtSQL').setValue(rawData.SQL);
        this.down('#EdtSQLConditions').setValue(rawData.SQLConditions);
        this.down('#EdtListValues').setValue(rawData.ListValues);
        this.down('#ChkShowError').setValue(rawData.ShowError);
        this.down('#ChkShowEmptyMessage').setValue(rawData.ShowEmptyMessage);
        this.down('#RB_Text').setValue(false);
        this.down('#RB_SQL').setValue(false);
        if((this.rawData.DataSource!=undefined)&&(this.rawData.DataSource!='')){
            this.down('#'+this.rawData.DataSource).setValue(true);
        }else{
            this.down('#RB_Text').setValue(true);
        }
        var me=this;
        //надо получить список SQL полей если есть SQL
        if((rawData.SQL!=undefined)&&(rawData.SQL!='')){
            Report_class.GetSQLFieldsList(rawData.SQL, function(response, options) {
                    var result = response;
                    if (result.success) {
                        var ComboReportFieldListStoreData= result.result;// сохраняю список полей отчета для выбора из комбо
                        var Combo = me.down('#ComboFieldListLinkText');
                        Combo.getStore().removeAll();
                        Ext.Array.each(ComboReportFieldListStoreData, function(value) {
                            Combo.store.add({id: value});
                        });
                        Combo.setValue(rawData.DBLinkText);
                    }
            });
        }

        var ComboTextSyle = this.down('#ComboTextSyle');
        ComboTextSyle.store = new Ext.data.ArrayStore({
            fields: ['id'],
            data: []
        });
        this.FillStyleCombo(ComboTextSyle, rawData.TextStyle);
    },

    FillStyleCombo: function(StyleCombo, value) {
        Report_class.GetCellStylesArray(function(response, options) {
                var result = response;
                if ((result.success === false) && (result.result == 're_connect')) {
                    Ext.MessageBox.alert('Подключение',result.msg);
                    window.onbeforeunload = null;
                    findFirstWindow().window.location.href = __first_page;
                    return;
                }
                if (result.success) {
                    var arr=result.result;
                    var arrkeys= Object.keys(arr);
                    StyleCombo.getStore().removeAll();
                    Ext.Array.each(arrkeys, function(value) {
                        StyleCombo.store.add({id: value});
                    });
                    StyleCombo.setValue(value);
                }
        });
        StyleCombo.setValue(value);
    }

});