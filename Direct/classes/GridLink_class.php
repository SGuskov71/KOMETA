<?php

require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

class GridLink_class {

//статические свойства  объекта считанные из метабазы
  public $sysname; //код объекта
  public $id_object; //ID объекта
  public $id_object_parent; //
  public $short_name; //
  public $full_name; //
  public $code; //
  public $joins; //
  public $id_link; //

  function GetLinkArray($id_object) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);

      $LinkArray = array();

      $sql = "SELECT o_lnk.id_link,o_lnk.code, o_lnk.id_object_child ,o_lnk.short_name, o_lnk.full_name "
      . " FROM mb_object_link o_lnk inner join mb_object o_child on o_child.id_object=o_lnk.id_object_child "
      . " where exists(select * from mba_grant_object where mba_grant_object.id_group in (" . get_id_user_groups() . ") and mba_grant_object.id_object=o_lnk.id_object_child) "
      . " and o_lnk.id_object_parent=$id_object "
      . " order by o_lnk.sort_order ";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        return $result = new JSON_Result(false, $s_err, NULL);
      }
      while (($row = kometa_fetch_object($res))) {
        $Link = new GridLink_class;
        $Link->code = $row->code;
        $Link->id_object_parent = $id_object;
        $Link->id_object = $row->id_object_child;
        $Link->sysname = get_sysname($Link->id_object);
        $Link->short_name = $row->short_name;
        $Link->full_name = $row->full_name;
        $Link->id_link = $row->id_link;
        $Link->joins = array();
        $sql = "SELECT p.fieldname master_key_fieldname,c.fieldname detail_key_fieldname "
        . " FROM mb_object_link_field lnk inner join mb_object_field p on lnk.id_field_parent=p.id_field "
        . " inner join mb_object_field c on lnk.id_field_child=c.id_field "
        . " where  id_link=" . $row->id_link;
        $res_f = kometa_query($sql);
        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          return $result = new JSON_Result(false, $s_err, NULL);
        }
        while (($row_f = kometa_fetch_object($res_f))) {
          array_push($Link->joins, $row_f);
        }
        array_push($LinkArray, $Link);
        unset($Link);
      }
      return $result = new JSON_Result(true, '', $LinkArray);
    } else {
      return $result;
    }
  }

}
