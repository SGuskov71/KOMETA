/*!
 * Ext JS Library 4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */

/**
 * @class Ext.ux.desktop.ShortcutModel
 * @extends Ext.data.Model
 * This model defines the minimal set of fields for desktop shortcuts.
 */
Ext.define('Ext.ux.Desktop.ShortcutModel', {
  extend: 'Ext.data.Model',
  fields: [
    {name: 'Caption'},
    {name: 'iconCls'},
    {name: 'id'},
    {name: 'iconClsLarge'},
    {name: 'func_name'},
    {name: 'func_class_name'},
    {name: 'param_list'},
    {name: 'code'},
    {code_help: 'code_help'}
  ]
});
