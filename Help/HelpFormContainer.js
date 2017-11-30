Ext.define('HelpFormContainer', {
  extend: 'Ext.container.Container',
  height: 491,
  width: 676,
  layout: {
    type: 'border'
  },
  initComponent: function() {
    var me = this;
    Ext.applyIf(me, {
      ParentWindow: me._Parent,
      items: [
        {
          xtype: 'panel',
          region: 'west',
          split: true,
          width: 250,
          layout: {
            type: 'fit'
          },
          title: 'Содержание справки',
          items: [
            {
              xtype: 'treepanel',
              root: me.HelpContent_root,
              listeners: {
                itemclick: function(tree, record, item, index) {
                  var p = tree.ownerCt.ownerCt.ownerCt.getComponent('ContentContainer').getComponent('ContentPanel');
                  p.update('<iframe id=help_frame name=help_frame id=help_frame name=help_frame src="' + record.raw.link_href + '" width="100%" height="100%" onload="insert_to_history(this.contentWindow);"></iframe>');
                }
              }
            }
          ]
        },
        {
          xtype: 'container',
          itemId: 'ContentContainer',
          region: 'center',
          layout: {
            type: 'border'
          },
          items: [
            {
              xtype: 'panel',
              region: 'north',
              split: false,
              splitterResize: false,
              height: 53,
              layout: {
                align: 'stretch',
                pack: 'end',
                type: 'hbox'
              },
              title: 'Поиск в справке',
              items: [
                {
                  xtype: 'textfield',
                  flex: 1,
                  fieldLabel: '',
                  hideLabel: true
                },
                {
                  xtype: 'button',
                  text: 'Искать',
                  handler: function(button, e) {
                    var p = button.ownerCt.ownerCt.getComponent('ContentPanel');
                    var edt = button.ownerCt.child('textfield');
                    p.update('<iframe id=help_frame name=help_frame src="' + _URLProjectRoot + 'Help/FrameItemArticle.php?CONTEXT=' + edt.getValue() + '" width="100%" height="100%" onload="insert_to_history(this.contentWindow);"></iframe>');
                  }
                },
                {
                  xtype: 'button',
                  text: 'Оглавление',
                  handler: function(button, e) {
                    var p = button.ownerCt.ownerCt.getComponent('ContentPanel');
                    p.update('<iframe id=help_frame name=help_frame src="' + URLhelp_root + 'Content.html' + '" width="100%" height="100%" onload="insert_to_history(this.contentWindow);"></iframe>');
                  }
                },
                {
                  xtype: 'button',
                  text: 'Назад',
                  handler: function(button, e) {
                    var p = button.ownerCt.ownerCt.getComponent('ContentPanel');
                    var last=button.ownerCt.ownerCt.ownerCt.ownerCt.help_history.pop();
                    var last=button.ownerCt.ownerCt.ownerCt.ownerCt.help_history.pop();
                    if (last==undefined)
                      last=URLhelp_root + 'Content.html';
                    p.update('<iframe id=help_frame name=help_frame src="' + last + '" width="100%" height="100%" onload="insert_to_history(this.contentWindow);"></iframe>');
                  }
                }
              ]
            },
            {
              xtype: 'panel',
              itemId: 'ContentPanel',
              region: 'center',
              title: 'Статьи справки'
            }
          ]
        }
      ]
    });
    me.callParent(arguments);
  }

});

function insert_to_history(href) {
// Запись help в массив 
  if (href.parent.HelpWindow.help_history == undefined) {
    href.parent.HelpWindow.help_history = [];
  }
  href.parent.HelpWindow.help_history.push(href.location.href);

}