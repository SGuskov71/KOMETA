<?php

require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

class ObjectGroup_class {

  function get_groups_Root($type_view) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $GroupRoot = new stdClass();
      $GroupRoot->text = 'Меню';
      $GroupRoot->expanded = true;
      $GroupRoot->children = $this->get_groups($type_view, NULL);

      return $result = new JSON_Result(true, '', $GroupRoot);
    } else {
      return $result;
    }
  }

  function get_groups($type_view, $id_parent) {
    $result = array();
    if (isset($id_parent))
      $w = "id_parent='$id_parent'";
    else
      $w = "id_parent is null";

    switch ($type_view) {
      case 1:
//        $wdt = "and o.id_object_dtype=1";
        break;
      case 2:
//        $wdt = "and o.id_object_dtype in (1,2,3)";
        break;
      case 3:
        $wdt = //"and o.id_object_dtype =1 ".
              "and ((g.code like 'mb%') or (g.code like 'sv_mb%') )";
        $w.=" and code like 'mb%'";
        break;
      case 4:
        $wdt = //"and o.id_object_dtype =1 " . 
              "and not ((g.code like 'mb%') or (g.code like 'sv_mb%') )";
        $w.=" and not (code like 'mb%')";
        break;
      default:
        $wdt = "";
        break;
    }

    $sql1 = "SELECT id_group, id_parent, code, short_name, full_name from mb_object_group as g where $w "
    . "and (Exists(Select * "
    . "from mb_object as o,mba_grant_object ga where ga.id_object=o.id_object and "
    . "ga.id_group in (" . get_id_user_groups() . ") and"
    . " g.id_group=o.id_group $wdt ) "
    . "or Exists(Select * from mb_object_group as o where g.id_group=o.id_parent)) order by short_name";
    $res1 = kometa_query($sql1);
    while ($row = kometa_fetch_object($res1)) {
      $cur_gr = new stdClass();
      $cur_gr->text = $row->short_name;
      $cur_gr->expanded = false;
      $cur_gr->children = $this->get_groups($type_view, $row->id_group);
      //  array_merge($result, $gr);
      $sql = "Select o.id_object, o.id_object_type, o.id_edit_object, o.sysname, o.short_name, "
      . "o.full_name, o.id_group, o.code_help, o.connector, o.add_where"
      . " from mb_object as o,mba_grant_object ga,mb_object_group as g where ga.id_object=o.id_object and o.id_group=g.id_group "
      . "and ga.id_group in (" . get_id_user_groups() . ") and o.id_group=" . $row->id_group . " $wdt ";
      $res_e = kometa_query($sql);
      while ($row_e = kometa_fetch_object($res_e)) {
        switch ($row_e->id_object_type) {
          case 1:
            $iconCls = 'table_object';
            break;
          case 2:
            $iconCls = 'view_object';
            break;
          case 3:
            $iconCls = 'proc_object';
            break;
          default:
            $iconCls = 'proc_object';
            break;
        }
        $obj_items = new stdClass();
        $obj_items->text = $row_e->short_name;
        $obj_items->leaf = true;
        $obj_items->expanded = false;
        $obj_items->id_object = $row_e->id_object;
        $obj_items->sysname = $row_e->sysname;
        $obj_items->iconCls = $iconCls;
        $obj_items->children = NULL;
        array_push($cur_gr->children, $obj_items);
      }
      array_push($result, $cur_gr);
    }
    return $result;
  }

}
