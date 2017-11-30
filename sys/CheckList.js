//модуль классов для создания контрола чек лист группа

Ext.define('CheckList', {
  extend: 'Ext.DataView',
  itemSelector: 'input.x-checklist',
  overflowY: 'scroll',
  autoScroll: true,
  modifyCheckOnAll: function (boolValue) {
    var me = this;
    me.store.each(
            function (record) {
              record.data[me.checkField] = boolValue ? me.trueValue : me.falseValue;
            });
    var compositeEl = Ext.select(me.itemSelector);
    if (compositeEl) {
      compositeEl.each(function (el) {
        el.dom.checked = boolValue;
      });
    }
  },
  checkAll: function () {
    this.modifyCheckOnAll(true);
  },
  uncheckAll: function () {
    this.modifyCheckOnAll(false);
  },
  initComponent: function () {
    this.tpl = new Ext.XTemplate(
            '<tpl for="."><div><input type="checkbox" class="x-checklist" style="margin-right:10px;" {[ values.'
            + this.checkField + '==' + this.trueValue + '? "checked" : ""]} />{' + this.displayField + '}</div></tpl>',
            {
              checkField: this.checkField,
              trueValue: this.trueValue,
              displayField: this.displayField
            });
    this.callParent();
  }
});
Ext.define('CheckListWindow', {
  extend: 'Ext.window.Window',
  layout: 'fit',
  title: 'Отметить для выбора',
      constrainHeader: true,
  modal: true,
  width: 640,
  height: 480,
  initComponent: function () {
    var me = this;
    if (me._Caption)
      me.setTitle(me._Caption);
    me.checkField = 'checked';
    me.displayField = 'caption';
    me.codeField = 'code';
    me.addEvents('OK_result');
    me.DView = me.createDView();
    me.items = [me.DView];
    me.dockedItems = [
      {
        xtype: 'container',
        dock: 'bottom',
        layout: {
          align: 'stretch',
          pack: 'end',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            minWidth: 100,
            text: 'OK',
            handler: me.onOK, scope: me
          },
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Отмена',
            handler: me.onCancel, scope: me
          }
        ]
      }, {
        xtype: 'container',
        dock: 'bottom',
        layout: {
          align: 'stretch',
          pack: 'start',
          type: 'hbox'
        },
        items: [
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Выделить все',
            handler: function () {
              me.DView.checkAll();
            }
          },
          {
            xtype: 'button',
            minWidth: 100,
            text: 'Снять выделение',
            handler: function () {
              me.DView.uncheckAll();
            }
          }
        ]
      }];
    me.callParent();
  },
  createDView: function () {
    var me = this;
    var ds = new Ext.data.Store({
      reader: {
        type: "array"
      },
      fields: [{name: me.codeField}, {name: me.displayField}, {name: me.checkField}]
    });
    ds.loadData(me.ArrayData);
    var myCheckList = new CheckList({
      store: ds,
      mode: 'local',
      checkField: me.checkField,
      displayField: me.displayField,
      trueValue: true,
      falseValue: false,
      border: 5,
      style: {
        borderColor: 'white',
        borderStyle: 'solid'
      },
      listeners: {
        itemclick: function (view, record, item, index, e, eOpts) {
          var r = view.store.getAt(index);
          r.set(me.checkField, item.checked);
          r.commit();
        },
        scope: me
      }
    });
    return myCheckList;
  },
  onOK: function () {
    var me = this;
    var ReturnArray = [];
    me.DView.store.each(function (record) {
      ReturnArray.push([record.get(me.codeField), record.get(me.displayField), record.get(me.checkField)]);
    });
    me.fireEvent('OK_result', me, ReturnArray);
    me.CloseWindow();
  },
  onCancel: function () {
    var me = this;
    me.CloseWindow();
  },
  CloseWindow: function () {
    var me = this;
    me.close();
    Ext.destroy(me);
  }
});
