
Ext.define('pivot.CrosstableFormContainer', {
    extend: 'Ext.container.Container',
    itemId: 'CrosstableFormContainer',
    layout: {
        type: 'border'
    },
    initComponent: function () {
        var me = this;
        var combo_data_ = [];
        
        var field_gr_oper_list = me.PivotObject.pivot_field.field_gr_oper;
        if (!(field_gr_oper_list == undefined)) {
            var length = field_gr_oper_list.length;
            for (var j = 0; j < length; j++) {
                combo_data_.push([field_gr_oper_list[j].fieldname, field_gr_oper_list[j].short_name]);
            }
        }

        var field_pivot = me.PivotObject.pivot_field.field_pivot;

        var temp_field_pivot = [];
        var length = field_pivot.length;
        for (var i = 0; i < length; i++) {
            temp_field_pivot.push({short_name: field_pivot[i].short_name, fieldname: field_pivot[i].fieldname});
        }

        var LeftTreeRoot = {text: 'Поля', expanded: true, children: [], iconCls: 'no-leaf-icons'};
        var RightTreeRoot = {text: 'Поля', expanded: true, children: [], iconCls: 'no-leaf-icons'};
        var CentreTreeRoot = {text: 'Поля', expanded: true, children: [], iconCls: 'no-leaf-icons'};
        var store_gr_oper_data = [];

        if (!me.isNew) {
          
            var PivotArrayObject = me.PivotObject.description;
            if (PivotArrayObject != undefined) {
                var length = PivotArrayObject.border_field_array.length;
                for (var i = 0; i < length; i++) {
                    var n = getIndexOf_field_pivot(temp_field_pivot, PivotArrayObject.border_field_array[i]);
                    if (n > -1) {
                        LeftTreeRoot.children.push({text: temp_field_pivot[n].short_name, expanded: false, iconCls: 'no-leaf-icons',
                            fieldname: temp_field_pivot[n].fieldname, children: null, expandable: false, leaf: true});
                        temp_field_pivot.splice(n, 1);
                    }
                }
                var length = PivotArrayObject.top_field_array.length;
                for (var i = 0; i < length; i++) {
                    var n = getIndexOf_field_pivot(temp_field_pivot, PivotArrayObject.top_field_array[i]);
                    if (n > -1) {
                        RightTreeRoot.children.push({text: temp_field_pivot[n].short_name, expanded: false, iconCls: 'no-leaf-icons',
                            fieldname: temp_field_pivot[n].fieldname, children: null, expandable: false, leaf: true});
                        temp_field_pivot.splice(n, 1);
                    }
                }
                store_gr_oper_data = PivotArrayObject.group_field_array;
            }
        }

        var length = temp_field_pivot.length;
        for (var i = 0; i < length; i++) {
            CentreTreeRoot.children.push({text: temp_field_pivot[i].short_name, expanded: false, iconCls: 'no-leaf-icons',
                fieldname: temp_field_pivot[i].fieldname, children: null, expandable: false, leaf: true});
        }

        createCSSSelector('.no-leaf-icons', 'display: none'); // гружу стиль для дерева без иконок

        var store_gr_oper = new Ext.data.ArrayStore({
            fields: ['gr_operName', 'gr_operID', 'gr_operFieldCaption', 'gr_operFieldName', 'gr_operLabel'],
            data: store_gr_oper_data
        });

        Ext.applyIf(me, {
            items: [
                {
                    xtype: 'panel',
                    itemId: 'Pan2',
                    region: 'south',
                    split: true,
                    height: 200,
                    layout: {
                        type: 'border'
                    },
                    title: 'Групповые операции',
                    items: [
                        {
                            xtype: 'container',
                            autoScroll: true,
                            itemId: 'Cont1',
                            region: 'east',
                            split: true,
                            width: 400,
                            layout: {
                                align: 'stretch',
                                type: 'vbox'
                            },
                            items: [
                                {
                                    xtype: 'combobox',
                                    width: 300,
                                    labelWidth: 150,
                                    fieldLabel: 'Операция по полю',
                                    matchFieldWidth: false,
                                    editable: false,
                                    disabled: true,
                                    mode: 'local',
                                    triggerAction: 'all',
                                    store: new Ext.data.ArrayStore({
                                        fields: ['code', 'text'],
                                        data: combo_data_}),
                                    valueField: 'code',
                                    displayField: 'text',
                                    listeners: {change: function (combo, newValue, oldValue)
                                        {
                                            var n = me.getIndexOf_field_gr_oper(me.PivotObject.pivot_field.field_gr_oper, newValue);
                                            if (n > -1) {
                                                var op_arr = me.PivotObject.pivot_field.field_gr_oper[n].gr_oper;
                                                combo.ownerCt.child('radiogroup').items.each(function (item, index, len) {
                                                    var numstr = index + 1;
                                                    numstr = numstr.toString();
                                                    var m = op_arr.indexOf(numstr);
                                                    if (m > -1)
                                                        item.enable();
                                                    else
                                                        item.disable();
                                                });
                                            }

                                        }
                                    }
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Описание операции',
                                    itemId: 'gr_operLabel',
                                    disabled: true,
                                    labelWidth: 150,
                                },
                                {
                                    xtype: 'radiogroup',
                                    flex: 1,
                                    width: 400,
                                    fieldLabel: '',
                                    submitValue: false,
                                    validateOnChange: false,
                                    //allowBlank: false,
                                    disabled: true,
                                    columns: 2,
                                    items: [
                                        {
                                            xtype: 'radiofield',
                                            boxLabel: 'Сумма',
                                            disabled: true,
                                            name: 'rb', inputValue: 1
                                        },
                                        {
                                            xtype: 'radiofield',
                                            boxLabel: 'Среднее',
                                            disabled: true,
                                            name: 'rb', inputValue: 2
                                        },
                                        {
                                            xtype: 'radiofield',
                                            boxLabel: 'Минимум',
                                            disabled: true,
                                            name: 'rb', inputValue: 3
                                        },
                                        {
                                            xtype: 'radiofield',
                                            boxLabel: 'Максимум',
                                            disabled: true,
                                            name: 'rb', inputValue: 4
                                        },
                                        {
                                            xtype: 'radiofield',
                                            boxLabel: 'Количество по группам',
                                            name: 'rb', inputValue: 5, checked: true}
                                    ]
                                },
                                {
                                    xtype: 'container',
                                    itemId: 'ContBtn1',
                                    border: false,
                                    height: 24,
                                    layout: {
                                        align: 'stretch',
                                        pack: 'center',
                                        type: 'hbox'
                                    },
                                    items: [
                                        {
                                            xtype: 'button',
                                            text: 'Новое',
                                            handler: function (button, e) {
                                                var val = {rb: 5};
                                                button.ownerCt.ownerCt.child('radiogroup').setValue(val);
                                                button.ownerCt.ownerCt.child('radiogroup').enable();
                                                button.ownerCt.ownerCt.child('combobox').setValue(null);
                                                button.ownerCt.ownerCt.child('combobox').enable();
                                                button.ownerCt.ownerCt.child('#gr_operLabel').setValue(null);
                                                button.ownerCt.ownerCt.child('#gr_operLabel').enable();
                                                button.ownerCt.getComponent('BtnAdd').enable();
                                                button.ownerCt.ownerCt.child('radiogroup').items.each(function (item, index, len) {
                                                    if (index != 4)
                                                        item.disable();
                                                });
                                            }
                                        },
                                        {
                                            xtype: 'tbspacer',
                                            width: 2
                                        },
                                        {
                                            xtype: 'button',
                                            disabled: true,
                                            itemId: 'BtnAdd',
                                            text: 'Добавить',
                                            handler: function (button, e) {
                                                var _rad_g = button.ownerCt.ownerCt.child('radiogroup');
                                                var _ind = _rad_g.getValue().rb;
                                                var _indradobj = _rad_g.items.get(_ind - 1);
                                                var _comb = button.ownerCt.ownerCt.child('combobox');
                                                var combovalue = _comb.getValue();
                                                if (combovalue != undefined) {
                                                    var record = _comb.findRecordByValue(combovalue);
                                                    var Combotext = record.data.text;
                                                } else {
                                                    Combotext = null;
                                                }
                                                var _grd = button.ownerCt.ownerCt.ownerCt.child('gridpanel');
                                                var _stor = _grd.getStore();
                                                _stor.add({
                                                    gr_operName: _indradobj.boxLabel
                                                    , gr_operID: _ind
                                                    , gr_operFieldCaption: Combotext
                                                    , gr_operFieldName: combovalue
                                                    , gr_operLabel: button.ownerCt.ownerCt.child('#gr_operLabel').getValue()
                                                });
                                                _rad_g.disable();
                                                button.ownerCt.ownerCt.child('#gr_operLabel').disable();
                                                _comb.disable();
                                                button.disable();
                                            }
                                        },
                                        {
                                            xtype: 'tbspacer',
                                            width: 2
                                        },
                                        {
                                            xtype: 'button',
                                            itemId: 'BtnDelete',
                                            disabled: true,
                                            text: 'Удалить',
                                            handler: function (button, e) {
                                                var _grd = button.ownerCt.ownerCt.ownerCt.child('gridpanel');
                                                var _stor = _grd.getStore();
                                                var _rec = _grd.getSelectionModel().getSelection()[0];
                                                if (_rec != undefined) {
                                                    _stor.remove(_rec);
                                                    _stor.sync();
                                                }
                                            }
                                        }
                                    ]
                                }
                            ]
                        },
                        {
                            xtype: 'gridpanel',
                            region: 'center',
                            split: true,
                            header: false,
                            store: store_gr_oper,
                            columns: [
                                {
                                    xtype: 'gridcolumn',
                                    draggable: false,
                                    detachOnRemove: false,
                                    enableColumnHide: false,
                                    dataIndex: 'gr_operName',
                                    hideable: false,
                                    text: 'Операция',
                                    flex: 1
                                },
                                {
                                    xtype: 'gridcolumn',
                                    draggable: false,
                                    detachOnRemove: false,
                                    enableColumnHide: false,
                                    dataIndex: 'gr_operLabel',
                                    hideable: false,
                                    text: 'Описание операции',
                                    flex: 1
                                },
                                {
                                    xtype: 'gridcolumn',
                                    draggable: false,
                                    detachOnRemove: false,
                                    enableColumnHide: false,
                                    dataIndex: 'gr_operFieldCaption',
                                    hideable: false,
                                    text: 'По полю',
                                    flex: 1
                                }
                            ],
                            listeners: {
                                selectionchange: function (view, selections, options) {
                                    if (selections.length > 0) {
                                        me.getComponent('Pan2').getComponent('Cont1').getComponent('ContBtn1').getComponent('BtnDelete').enable();
                                        me.getComponent('Pan2').getComponent('Cont1').getComponent('ContBtn1').getComponent('BtnAdd').disable();
                                        me.getComponent('Pan2').getComponent('Cont1').child('combobox').setValue(selections[0].data.gr_operFieldName);
                                        me.getComponent('Pan2').getComponent('Cont1').child('combobox').disable();
                                        me.getComponent('Pan2').getComponent('Cont1').child('#gr_operLabel').setValue(selections[0].data.gr_operLabel);
                                        me.getComponent('Pan2').getComponent('Cont1').child('#gr_operLabel').disable();
                                        var val = {rb: selections[0].data.gr_operID};
                                        me.getComponent('Pan2').getComponent('Cont1').child('radiogroup').setValue(val);
                                        me.getComponent('Pan2').getComponent('Cont1').child('radiogroup').disable();
                                    } else {
                                        me.getComponent('Pan2').getComponent('Cont1').getComponent('ContBtn1').getComponent('BtnDelete').disable();
                                    }
                                }
                            }
                        }
                    ]
                },
                {
                    xtype: 'panel',
                    region: 'center',
                    layout: {
                        type: 'border'
                    },
                    header: false,
                    title: 'My Panel',
                    items: [
                        {
                            xtype: 'treepanel',
                            id: 'TreeRight',
                            region: 'east',
                            split: true,
                            width: 221,
                            title: 'Шапка',
                            hideHeaders: true,
                            rowLines: true,
                            lines: false,
                            rootVisible: false,
                            viewConfig: {
                                stripeRows: true
                            },
                            root: RightTreeRoot,
                            listeners: {
                                itemclick: function (view, record, item, index, event) {
                                    if (record != undefined) {
                                        Ext.getCmp('buttonRC').enable();
                                    } else {
                                        Ext.getCmp('buttonRC').disable();
                                    }
                                }
                            }
                        },
                        {
                            xtype: 'treepanel',
                            id: 'TreeLeft',
                            region: 'west',
                            split: true,
                            width: 221,
                            title: 'Боковина',
                            rowLines: true,
                            lines: false,
                            rootVisible: false,
                            viewConfig: {
                                stripeRows: true
                            },
                            root: LeftTreeRoot,
                            listeners: {
                                itemclick: function (view, record, item, index, event) {
                                    if (record != undefined) {
                                        Ext.getCmp('buttonLC').enable();
                                    } else {
                                        Ext.getCmp('buttonLC').disable();
                                    }
                                }
                            }
                        },
                        {
                            xtype: 'panel',
                            region: 'center',
                            layout: {
                                type: 'border'
                            },
                            title: 'Доступные поля',
                            dockedItems: [
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    dock: 'right',
                                    layout: {
                                        align: 'stretch',
                                        pack: 'center',
                                        type: 'vbox'
                                    },
                                    items: [
                                        {
                                            xtype: 'button',
                                            id: 'buttonCR',
                                            margins: '2',
                                            border: '',
                                            disabled: true,
                                            text: '-->',
                                            handler: function (thisBtn, event) {
                                                var TV = Ext.getCmp('TreeCenter');
                                                if (TV.getSelectionModel().hasSelection()) {
                                                    var node = TV.getSelectionModel().getSelection()[0];
                                                    if (node != undefined) {
                                                        var DestTree = Ext.getCmp('TreeRight');
                                                        var RootNode = DestTree.getRootNode();
                                                        RootNode.appendChild({text: node.raw.text, fieldname: node.raw.fieldname, iconCls: node.raw.iconCls,
                                                            leaf: true, expanded: false, children: null, expandable: false});
                                                        node.remove(true);
                                                        TV.getStore().sync();
                                                    }
                                                }
                                                thisBtn.disable();
                                                Ext.getCmp('buttonCL').disable();
                                            }
                                        },
                                        {
                                            xtype: 'button',
                                            id: 'buttonRC',
                                            margins: '2',
                                            border: '',
                                            disabled: true,
                                            text: '<--',
                                            handler: function (thisBtn, event) {
                                                var TV = Ext.getCmp('TreeRight');
                                                if (TV.getSelectionModel().hasSelection()) {
                                                    var node = TV.getSelectionModel().getSelection()[0];
                                                    if (node != undefined) {
                                                        var DestTree = Ext.getCmp('TreeCenter');
                                                        var RootNode = DestTree.getRootNode();
                                                        RootNode.appendChild({text: node.raw.text, fieldname: node.raw.fieldname, iconCls: node.raw.iconCls,
                                                            leaf: true, expanded: false, children: null, expandable: false});
                                                        node.remove(true);
                                                        TV.getStore().sync();
                                                    }
                                                }
                                                thisBtn.disable();
                                            }
                                        }
                                    ]
                                },
                                {
                                    xtype: 'container',
                                    flex: 1,
                                    dock: 'left',
                                    layout: {
                                        align: 'center',
                                        pack: 'center',
                                        type: 'vbox'
                                    },
                                    items: [
                                        {
                                            xtype: 'button',
                                            id: 'buttonCL',
                                            margins: '2',
                                            disabled: true,
                                            text: '<--',
                                            handler: function (thisBtn, event) {
                                                var TV = Ext.getCmp('TreeCenter');
                                                if (TV.getSelectionModel().hasSelection()) {
                                                    var node = TV.getSelectionModel().getSelection()[0];
                                                    if (node != undefined) {
                                                        var DestTree = Ext.getCmp('TreeLeft');
                                                        var RootNode = DestTree.getRootNode();
                                                        RootNode.appendChild({text: node.raw.text, fieldname: node.raw.fieldname, iconCls: node.raw.iconCls,
                                                            leaf: true, expanded: false, children: null, expandable: false});
                                                        node.remove(true);
                                                        TV.getStore().sync();
                                                    }
                                                }
                                                thisBtn.disable();
                                                Ext.getCmp('buttonCR').disable();
                                            }
                                        },
                                        {
                                            xtype: 'button',
                                            id: 'buttonLC',
                                            margins: '2',
                                            disabled: true,
                                            text: '-->',
                                            handler: function (thisBtn, event) {
                                                var TV = Ext.getCmp('TreeLeft');
                                                if (TV.getSelectionModel().hasSelection()) {
                                                    var node = TV.getSelectionModel().getSelection()[0];
                                                    if (node != undefined) {
                                                        var DestTree = Ext.getCmp('TreeCenter');
                                                        var RootNode = DestTree.getRootNode();
                                                        RootNode.appendChild({text: node.raw.text, fieldname: node.raw.fieldname, iconCls: node.raw.iconCls,
                                                            leaf: true, expanded: false, children: null, expandable: false});
                                                        node.remove(true);
                                                        TV.getStore().sync();
                                                    }
                                                }
                                                thisBtn.disable();
                                            }
                                        }
                                    ]
                                }
                            ],
                            items: [
                                {
                                    xtype: 'treepanel',
                                    region: 'center',
                                    id: 'TreeCenter',
                                    header: false,
                                    title: 'My Tree Panel',
                                    rowLines: true,
                                    lines: false,
                                    rootVisible: false,
                                    viewConfig: {
                                        stripeRows: true,
                                        rootVisible: false
                                    },
                                    root: CentreTreeRoot,
                                    listeners: {
                                        itemclick: function (view, record, item, index, event) {
                                            if (record != undefined) {
                                                Ext.getCmp('buttonCL').enable();
                                                Ext.getCmp('buttonCR').enable();
                                            } else {
                                                Ext.getCmp('buttonCL').disable();
                                                Ext.getCmp('buttonCR').disable();
                                            }
                                        }
                                    }
                                }
                            ]
                        }
                    ]
                },
                {
                    xtype: 'container',
                    itemId: 'Cont2',
                    region: 'north',
                    height: 28,
                    layout: {
                        type: 'column'
                    },
                    items: [
                        {
                            xtype: 'textfield',
                            columnWidth: 0.6,
                            fieldLabel: ' Наименование настройки',
                            itemId: 'CrossSettings_Caption',
                            //labelStyle: 'color: blue;font-weight: bold;',
                            labelWidth: 200,
                            value: me.CrossSettings_Caption
                        },
                        {
                            xtype: 'textfield',
                            columnWidth: 0.38,
                            fieldLabel: 'Код',
                            labelWidth: 100,
                            itemId: 'CrossSettings_Code',
                            value: me.code
                        }
                    ]
                }
            ]
        });
        me.callParent(arguments);
    },
    getIndexOf_field_gr_oper: function (arr, k) {
        for (var i = 0; i < arr.length; i++) {
            if (arr[i].fieldname === k) {
                return i;
            }
        }
        return -1;
    },
    GetPivotObject: function () {
        var result = {};
        result.border_field_array = [];
        result.top_field_array = [];
        result.group_field_array = [];

        var _grd = this.getComponent('Pan2').child('gridpanel');
        var _stor = _grd.getStore();
        Ext.each(_stor.getRange(), function (item, idx, a) {
            result.group_field_array.push([item.data.gr_operName, item.data.gr_operID, item.data.gr_operFieldCaption, item.data.gr_operFieldName, item.data.gr_operLabel]);
        });

        var _tree = Ext.getCmp('TreeLeft');
        var _rootnode = _tree.getRootNode();
        Ext.each(_rootnode.childNodes, function (item, idx, a) {
            result.border_field_array.push(item.raw.fieldname);
        });

        var _tree = Ext.getCmp('TreeRight');
        var _rootnode = _tree.getRootNode();
        Ext.each(_rootnode.childNodes, function (item, idx, a) {
            result.top_field_array.push(item.raw.fieldname);
        });

        return result;
    },
    GetPivotCaption: function () {
        var result = this.getComponent('Cont2').child('#CrossSettings_Caption').getValue();
        return result;
    },
    GetPivotCode: function () {
        var result = this.getComponent('Cont2').child('#CrossSettings_Code').getValue();
        return result;
    }
});

function getIndexOf_field_pivot(arr, k) {
    for (var i = 0; i < arr.length; i++) {
        if (arr[i].fieldname === k) {
            return i;
        }
    }
    return -1;
}
