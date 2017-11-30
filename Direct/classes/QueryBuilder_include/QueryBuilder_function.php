<?php

  function _GetStoredQueryParamList($codeStoredQuery) {
    if (!isset($codeStoredQuery)) {
      $result = new JSON_Result(false, 'Не определено ключевое поле', NULL);
      return $result;
    } else {
      $sql = "SELECT content FROM mb_stored_query where code='$codeStoredQuery'";
      $res = kometa_query($sql);
      $s_err = kometa_last_error();
      if ($s_err != '') {
        $result = new JSON_Result(false, $s_err, NULL);
        return $result;
      } else {
        if (kometa_num_rows($res) == 0) {
          $result = new JSON_Result(false, 'Запрос не найден', NULL);
          return $result;
        } else {
          $row = kometa_fetch_object($res);
          $QueryTemplate = json_decode($row->content);
          $Params = $QueryTemplate->QueryParams;
          $InteractiveParams = Array();
          foreach ($Params as $par) {
            array_push($InteractiveParams, $par);
          }
          $result = new JSON_Result(true, '', $InteractiveParams);
          return $result;
        }
      }
    }
  }
