<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Direct/classes/ObjectFilterClasses/ObjectFilterClasses.php");

class GridFilter_class {

  function GetFilterSettings($sysname) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $id_object = get_id_object($sysname);
      if ((isset($id_object)) && ($id_object != '')) {
        $ObjectFilter = new CObjectFilter($id_object);
        return $result = new JSON_Result(true, '', $ObjectFilter->GetFilterSettingsJSON());
      } else
        return $result = new JSON_Result(false, 'Нет id_object', NULL);
    } else {
      return $result;
    }
  }

  function GetFilterConditions($sysname, $id_ObjectFilter) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $id_object = get_id_object($sysname);
      if ((isset($id_object)) && ($id_object != '')) {
        if ((isset($id_ObjectFilter)) && ($id_ObjectFilter != '')) {
          $ObjectFilter = new CObjectFilter($id_object);
          $ObjectFilter->ReadFilterConditionsDB($id_ObjectFilter);
          return $result = new JSON_Result(true, '', $ObjectFilter->GetFilterConditionsJSON());
        } else
          return $result = new JSON_Result(false, 'Нет $id_ObjectFilter', NULL);
      } else
        return $result = new JSON_Result(false, 'Нет id_object', NULL);
    } else {
      return $result;
    }
  }

  function ReadFilterConditions($JSONString) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $ObjectFilter = new CObjectFilter(null);
      $ObjectFilter->ReadFilterConditionsJSON($JSONString);
      if ($ObjectFilter->Save2DB) {
        $ObjectFilter->SaveFilterConditionsDB();
      }
      $bildFilterSQLWhereStr = $ObjectFilter->BildFilterSQLWhereStr();
      return $result = new JSON_Result(true, '', $ObjectFilter->GetFilterConditionsJSON());
    } else {
      return $result;
    }
  }

  function GetFilterSQLWhereStr($id_ObjectFilter) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $ObjectFilter = new CObjectFilter(null);
      $ObjectFilter->ReadFilterConditionsDB($id_ObjectFilter);
      $ObjectFilter->BildFilterSQLWhereStr();
      return $result = new JSON_Result(true, '', $ObjectFilter->FilterSQLWhereStr);
    } else {
      return $result;
    }
  }

  function DeleteFilter($id_ObjectFilter) {
    $result = CheckConnection();
    if ($result->success === true) {
      unset($result);
      $sql = "delete from mb_filter_storage where id_filter_storage=$id_ObjectFilter";
      kometa_query($sql);
      $s_err = kometa_last_error();
      if (isset($s_err) && ($s_err != '')) {
        return $result = new JSON_Result(false, $_err, NULL);
      } else {
        return $result = new JSON_Result(true, 'Фильтр успешно удален', '1');
      }
    } else {
      return $result;
    }
  }
  
  function GetFieldList($sysname, $for_query) {
    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);
    $id_object = get_id_object($sysname);
    if ((isset($id_object)) && ($id_object != '')) {
      $ObjectFilter = new CObjectFilter($id_object, $for_query);
      return $result = new JSON_Result(true, '', $ObjectFilter->GetFieldListJSON());
    } else
      return $result = new JSON_Result(false, 'Нет id_object', '');
  }

}
