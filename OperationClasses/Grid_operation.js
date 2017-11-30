Ext.define('KOMETA.Operation.Grid_operation', {
  ShowObjectGroupWindow: function (Grid, Operation) {
    ShowObjectGroupWindow(Operation);
  }
  ,
  ShowMasterDetailGridWindow: function (Grid, Operation) {
    //Operation.ParamList={sysname:'sv_mb_object'};
    Operation.param_list.object_Caption=Operation.object_Caption;
    ShowMasterDetailGridWindow(Operation.param_list);
  }
  ,
  ShowCheckBoxGridWindow: function (Grid, Operation) {
    //Operation.ParamList={sysname:'sv_mb_object'};
    Operation.param_list.object_Caption=Operation.object_Caption;
    CreateCheckBoxGridWindow(Operation.param_list);
  }

});