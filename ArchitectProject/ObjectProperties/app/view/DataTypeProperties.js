/*
 * File: app/view/DataTypeProperties.js
 *
 * This file was generated by Sencha Architect version 3.2.0.
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

Ext.define('ObjectProperties.view.DataTypeProperties', {
    extend: 'Ext.form.Panel',

    requires: [
        'Ext.form.field.ComboBox',
        'Ext.form.field.Checkbox',
        'Ext.form.FieldSet',
        'Ext.form.CheckboxGroup'
    ],

    height: 274,
    width: 606,
    bodyPadding: 10,
    title: 'Типы данных',

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'textfield',
                    anchor: '100%',
                    fieldLabel: 'Условие проверки при вводе',
                    labelWidth: 120
                },
                {
                    xtype: 'combobox',
                    anchor: '100%',
                    fieldLabel: 'Тип данных ExtJS',
                    labelWidth: 120
                },
                {
                    xtype: 'checkboxfield',
                    anchor: '100%',
                    itemId: 'IsAvailableGroupOperation',
                    boxLabel: 'Доступность групповой операции'
                },
                {
                    xtype: 'fieldset',
                    title: 'Операции для фильра',
                    items: [
                        {
                            xtype: 'checkboxgroup',
                            items: [
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'Equal',
                                    boxLabel: 'Равно'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'NotEqual',
                                    boxLabel: 'Не равно'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'Empty',
                                    boxLabel: 'Пусто'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'NotEmpty',
                                    boxLabel: 'Не пусто'
                                }
                            ]
                        },
                        {
                            xtype: 'checkboxgroup',
                            itemId: 'More',
                            items: [
                                {
                                    xtype: 'checkboxfield',
                                    boxLabel: 'Больше'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'MoreOrEqual',
                                    boxLabel: 'Больше или равно'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'Less',
                                    boxLabel: 'Меньше'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'LessOrEqual',
                                    boxLabel: 'Меньше или равно'
                                }
                            ]
                        },
                        {
                            xtype: 'checkboxgroup',
                            items: [
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'Like',
                                    boxLabel: 'Содержит'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'NotLike',
                                    boxLabel: 'Не содержит'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'Begin',
                                    boxLabel: 'Начинается с'
                                },
                                {
                                    xtype: 'checkboxfield',
                                    itemId: 'NotBegin',
                                    boxLabel: 'Не начинается с'
                                }
                            ]
                        }
                    ]
                }
            ]
        });

        me.callParent(arguments);
    }

});