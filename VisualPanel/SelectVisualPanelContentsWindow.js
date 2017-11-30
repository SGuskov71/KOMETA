function SelectVisualPanelContents(event, toolEl, owner, tool) { //выбор содержимого элемента макета аналитической панели
  var controlContainer = owner.up('panel');
  var SelectVisualPanelContentsWindow = Ext.create('SelectVisualPanelContentsWindow', {_controlContainer: controlContainer});
  SelectVisualPanelContentsWindow.show();
}

Ext.define('SelectVisualPanelContentsWindow', {
  extend: 'Ext.window.Window',
  height: 140,
  width: 480,
  closable: true,
  autoScroll: true,
  modal: true,
  layout: {
    align: 'stretch',
    type: 'vbox'
  },
  constrainHeader: true,
  initComponent: function () {
    var me = this;

    Ext.applyIf(me, {
      title: 'Выбор отображения элемента панели визуализации - ' + me._controlContainer.itemId,
      items: [
        {
          xtype: 'combobox',
//                    dock: 'top',
          fieldLabel: 'Выберите вид панели',
          itemId: 'comboVisualPanelItemTypes',
          labelWidth: 180,
          matchFieldWidth: false,
          editable: false,
          mode: 'local',
          triggerAction: 'all',
          store: new Ext.data.Store({
            fields: ['id', 'Caption'],
            data: VisualPanelItemTypes}),
          valueField: 'id',
          displayField: 'Caption',
          value: me._controlContainer.VisualPanelItemTypes,
          listeners: {change: function (combo, newValue, oldValue)
            {
              me.code_view = null;
              me.name_type_view = null;
              me.code_object = null;
              var pickerVisualPanelItemObjectCode = Ext.ComponentQuery.query('#pickerVisualPanelItemObjectCode', me);
              pickerVisualPanelItemObjectCode = pickerVisualPanelItemObjectCode[0];
              pickerVisualPanelItemObjectCode.setValue(null);
              me.Set_btnSave_State();
            }
          }
        },
        {
          xtype: 'triggerfield',
          fieldLabel: 'Выберите объект для отображения',
          itemId: 'pickerVisualPanelItemObjectCode',
          labelWidth: 180,
          triggerCls: 'x-form-search-trigger',
          autoSelect: true,
          editable: false,
          typeAhead: true,
          enableKeyEvents: true,
          value: me._controlContainer.code_object_Descr,
          onTriggerClick: function (evnt) {
            var combo = me.down('#comboVisualPanelItemTypes');
            var Sel_view = combo.findRecordByValue(combo.getValue());
            if (Sel_view) {
              var Sel_code_view = Sel_view.raw.code_view;
              if (Sel_code_view) {
                me.code_view = Sel_code_view;
                me.name_type_view = Sel_view.raw.Caption;
              }
            }
            if (me.code_view == undefined) {
              Ext.MessageBox.alert('Предупреждение', "Надо выбрать тип представления");
              return;
            }
            var cmp = this;
            var ExtFilterWhereCond = '';
            cmp.setValue(null); //на экран описание
            me.code_object = null;
            me.code_object_Descr = null;
            var Obj = RunFunctionInScript(_URLProjectRoot + 'objectdata/js/SelectValSLVWindow.js',
                    "SelectValSlv",
                    "{sysname:'" + me.code_view + "', ExtFilterWhereCond:'" + ExtFilterWhereCond + "', object_Caption:'Выбор объекта " + me.name_type_view + "', HelpContext:''}");
            Obj.addListener('RunFunctionInScript_Return', function (ReturnVal) {
              ReturnVal.addListener('ValSlvSelected', function (context, SelID, SelDescr) {
                VisualPanel_class.get_code_value(me.code_view, SelID,
                        function (result) {
                          if ((result.success === false) && (result.result == 're_connect')) {
                            alert(result.msg);
                            window.onbeforeunload = null;
                            findFirstWindow().window.location.href = __first_page;
                            return;
                          }
                          if (result.success == true) {
                            if (trim(result.result) !== '') {
                              cmp.setValue(SelDescr); //на экран описание
                              me.code_object = result.result;
                              me.code_object_Descr = SelDescr;
                            }
                            me.Set_btnSave_State();
                          }
                        });
              });
            });
          }
        }
      ],
      dockedItems: [
        {
          xtype: 'toolbar',
          dock: 'bottom',
          items: [
            {
              xtype: 'tbfill'
            },
            {
              xtype: 'button',
              disabled: true,
              itemId: 'btnSave',
              text: 'Сохранить',
              handler: function () {
                var ItemContainer = Ext.ComponentQuery.query('#ItemContainer', me._controlContainer);//один общий контейнер для разных способов отображения
                ItemContainer = ItemContainer[0];
                if (ItemContainer != undefined) {
                  Ext.destroy(ItemContainer); //разрушаю прежнее содержимое панели
                }
                //обязательно нужен один общий контейнер для разных способов отображения
                //например если в панели вывели html то потом туда другие контролы херово ставятся
                //да и зачищать содержимое при замене контрола удобнее зная itemId: 'ItemContainer'
                var ItemContainer = Ext.create('Ext.container.Container', {itemId: 'ItemContainer', layout: 'fit'});
                me._controlContainer.add(ItemContainer);
                var comboVisualPanelItemTypes = Ext.ComponentQuery.query('#comboVisualPanelItemTypes', me);
                comboVisualPanelItemTypes = comboVisualPanelItemTypes[0];
                me._controlContainer.VisualPanelItemTypes = comboVisualPanelItemTypes.getValue();
                me._controlContainer.keyObjectId = me.code_object;
                me._controlContainer.name_type_view = me.name_type_view;
                me._controlContainer.code_object_Descr = me.code_object_Descr;
                me._controlContainer.setTitle(me.name_type_view + ' - ' + me.code_object_Descr);
                CloseWindow(me);
              }
            },
            {
              xtype: 'button',
              text: 'Отмена',
              handler: function () {
                CloseWindow(me);
              }
            }
          ]
        }
      ]
    });
    me.callParent(arguments);
  },
  Set_btnSave_State: function () {
    var me = this;
    var btnSave = Ext.ComponentQuery.query('#btnSave', me);
    btnSave = btnSave[0];
    if ((me.code_view) && (me.code_object)) {
      btnSave.enable();
    } else {
      btnSave.disable();
    }
  },
});