<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

class Desktop_class {

  function GetTaskMenu($id_parent = NULL) {
    global $LOGON_CAPTION;
    $result = array();
    if (isset($id_parent))
      $w = "id_parent=$id_parent  ";
    else
      $w = " id_parent is NULL ";
    $sqlOut = "select id_task,code, short_name,full_name,style,code_help, func_class_name, func_name, param_list, false as modal"
            . " from mb_task where exists(select * from  mba_grant_task "
            . " where mba_grant_task.id_task=mb_task.id_task"
            . " and mba_grant_task.id_group in (" . get_id_user_groups() . ") and  $w ) order by ord,short_name";
    $res = kometa_query($sqlOut);
    while ($row = kometa_fetch_object($res)) {
      $n = array_push($result, new CTaskMenuItem($row->code, $row->short_name, $row->style, $row->code_help, $row->func_class_name, $row->func_name, $row->param_list, $row->modal));
      $newItem = $result[$n - 1];
      $newItem->ChildMenu = $this->GetTaskMenu($row->id_task);
    }
    return $result;
  }

  function GetDesctopSettings() {
    global $LOGON_CAPTION;
    $DesctopSettingsObject = new CDesktopSettings;
    $result = get_Param_value('DesctopShortcut', get_id_user());
    if (!isset($result))
      $result = array();
    else
      $result = json_decode($result);
    // проверка прав доступа на ярлыки и обновление по ним функционала
    $DesctopSettingsObject->ShortcutObject = array();
    foreach ($result as $key => $value) {
      $w = " code=" . my_escape_string($value->code);
      $sqlOut = "select id_task,code, short_name,full_name,style,code_help, func_class_name, func_name, param_list, false as modal"
              . " from mb_task where exists(select * from  mba_grant_task "
              . " where mba_grant_task.id_task=mb_task.id_task"
              . " and mba_grant_task.id_group in (" . get_id_user_groups() . ") and  $w )";
      $res = kometa_query($sqlOut);
      $row = kometa_fetch_object($res);
      if ($row) {
        $value->text = $row->short_name;

        $value->Caption = $row->full_name;
        $value->iconCls = $row->style;
        $value->func_name = $row->func_name;
        $value->func_class_name = $row->func_class_name;
        $value->param_list = json_decode($row->param_list);
        $value->code_help = code_help;
        array_push($DesctopSettingsObject->ShortcutObject, $value);
      }
    }
//    $DesctopSettingsObject->ShortcutObject = $result;
    $result = get_Param_value('AutorunItems', get_id_user());
    if (!isset($result))
      $result = array();
    else
      $result = json_decode($result);
    $DesctopSettingsObject->AutorunItems = $result;
    $DesctopSettingsObject->MenuObject = $this->GetTaskMenu();
    $DesctopSettingsObject->theme = get_Param_value('theme', get_id_user());
    $DesctopSettingsObject->Autorun = get_Param_value('Autorun', get_id_user());
    if (!isset($DesctopSettingsObject->Autorun))
      $DesctopSettingsObject->Autorun = false;
    $DesctopSettingsObject->Autorun = $DesctopSettingsObject->Autorun == 1;
    $DesctopSettingsObject->wallpaperStretch = get_Param_value('wallpaperStretch', get_id_user());
    if (!isset($DesctopSettingsObject->wallpaperStretch))
      $DesctopSettingsObject->wallpaperStretch = false;
    $DesctopSettingsObject->wallpaperStretch = $DesctopSettingsObject->wallpaperStretch == 1;
    $DesctopSettingsObject->show_date_time = get_Param_value('show_date_time', get_id_user());
    $DesctopSettingsObject->show_date_time = $DesctopSettingsObject->show_date_time == 1;
    $wallpaper = get_Param_value('wallpaper', get_id_user());
    if (isset($wallpaper) && (trim($wallpaper) != ''))
      $wallpaper = json_decode($wallpaper);
    else
      $wallpaper = null;
    $DesctopSettingsObject->wallpaper = $wallpaper;
    $DesctopSettingsObject->UserName = $LOGON_CAPTION;
    return $DesctopSettingsObject;
  }

  function SaveDesctopSettingsObject($DesctopSettingsObject) {
    global $LOGON_CAPTION;
    $s_err = '';
    if (isset($DesctopSettingsObject)) {
      if (isset($DesctopSettingsObject->ShortcutObject)) {
        $JSONString = json_encode($DesctopSettingsObject->ShortcutObject);
        set_Param_value('DesctopShortcut', 'Ярлыки рабочего стола', $JSONString, get_id_user());
        $s_err.= kometa_last_error();
      }
      if (isset($DesctopSettingsObject->AutorunItems)) {
        $JSONString = json_encode($DesctopSettingsObject->AutorunItems);
        set_Param_value('AutorunItems', 'Ярлыки автозапуска', $JSONString, get_id_user());
        $s_err.= kometa_last_error();
      }
      if (isset($DesctopSettingsObject->wallpaper)) {
        $JSONString = json_encode($DesctopSettingsObject->wallpaper);
        set_Param_value('wallpaper', 'Картинка рабочего стола', $JSONString, get_id_user());
        $s_err.= kometa_last_error();
      }
      if (isset($DesctopSettingsObject->Autorun)) {
        if ($DesctopSettingsObject->Autorun)
          $num = 1;
        else
          $num = 0;
        set_Param_value('Autorun', 'Автозапуск', $num, get_id_user());
        $s_err.= kometa_last_error();
      }
      if (isset($DesctopSettingsObject->theme)) {

        set_Param_value('theme', 'Тема', $DesctopSettingsObject->theme, get_id_user());
        $s_err.= kometa_last_error();
      }

      if (isset($DesctopSettingsObject->wallpaperStretch)) {
        if ($DesctopSettingsObject->wallpaperStretch)
          $num = 1;
        else
          $num = 0;
        $result = set_Param_value('wallpaperStretch', 'wallpaperStretch', $num, get_id_user());
        $s_err.= kometa_last_error();
      }
      if (isset($DesctopSettingsObject->show_date_time)) {
        if ($DesctopSettingsObject->show_date_time)
          $num = 1;
        else
          $num = 0;
        $result = set_Param_value('show_date_time', 'show_date_time', $num, get_id_user());
        $s_err.= kometa_last_error();
      }
      if ($s_err != '') {
        $result = new JSON_Result(false, my_escape_string($s_err), '');
        return $result;
      } else {
        $result = new JSON_Result(true, '', '');
        return $result;
      }
    }
  }

  function SaveWindowPosition($codewindow, $winpos) {
    global $ID_User;
    set_Param_value('winpos_' . $codewindow, 'Сохранение настроек окна по URL' . $codewindow, $winpos, $ID_User);
  }

  function GetWindowPosition($codewindow) {
    global $ID_User;
    $wp = get_Param_value('winpos_' . $codewindow, $ID_User);
    if (!isset($wp) || ($wp == '')) {
      $wp = '{"x": -1, "y": -1, "width": 0, "height": 0}';
    }
    $wp = json_decode($wp);
    $result = new JSON_Result(true, '', $wp);
    return $result;
  }

  function getDesktopWallpapers() {
    $dir = $_SESSION['APP_INI_DIR'] . "wallpapers";
    $FilesArray = scandir($dir);
//$ss=error_get_last();
    $a = array();
    $s['img'] = $f_info['basename'];
    $s['text'] = "Не задан";
    $s['iconCls'] = '';
    $s['leaf'] = true;
    array_push($a, $s);
    foreach ($FilesArray as $file) {
      $f_info = pathinfo($file);
      if ((strtolower($f_info['extension']) == "png") || (strtolower($f_info['extension']) == "jpg") || (strtolower($f_info['extension']) == "gif") || (strtolower($f_info['extension']) == "bmp") || (strtolower($f_info['extension']) == "tif")) {
        $s['img'] = $f_info['basename'];
        $s['text'] = $f_info['basename'];
        $s['iconCls'] = '';
        $s['leaf'] = true;
        array_push($a, $s);
      }
    }
    return $a;
  }

  function ChangePassword($old_password, $new_password) {
    global $ID_User;
    global $type_login;

    $result = CheckConnection();
    if ($result->success === false) {
      return $result;
    }
    unset($result);

    $op = $old_password;
    if ($type_login == 3)
      $sql = "SELECT pwd FROM mba_user where id_user=$ID_User";
    else
      $sql = "SELECT pwd::text FROM mba_user where id_user=$ID_User";
    $res = kometa_query($sql);
    if ($row = kometa_fetch_object($res)) {
      if ($row->pwd != $op) {
        $result = new JSON_Result(false, ('Не верно определен старый пароль'), '');
      } else {
        $np = my_escape_string($new_password);
        $sql = "update mba_user set pwd=$np where id_user=$ID_User";
        $res = kometa_query($sql);
        $s_err = kometa_last_error();
        if ($s_err != '') {
          $result = new JSON_Result(false, ('Ошибка изменения пароля'), '');
        } else {
          $result = new JSON_Result(true, ('Пароль упешно  изменен'), '');
        }
      }
    } else{
      $result = new JSON_Result(false, ('Пользователь не найден'), '');
    }
    return $result;
  }

}

Class CTaskMenuItem {

  public $Caption;
  public $iconCls;
  public $func_class_name;
  public $param_list;
  public $func_name;
  public $isMenuItem;
  public $ChildMenu;
  public $code_help;
  public $modal;
  public $code;

  function __construct($code, $Caption, $iconCls, $code_help, $func_class_name, $func_name, $param_list, $modal) {
    $this->code = $code;
    $this->Caption = $Caption;
    $this->iconCls = $iconCls;
    $this->modal = $modal;
    $this->func_class_name = $func_class_name;
    $this->func_name = $func_name;
    try {
      $this->param_list = json_decode($param_list);
    } catch (Exception $e) {
      $this->param_list = new stdClass();
    }
    if (!isset($this->param_list))
      $this->param_list = new stdClass();
    $this->code_help = $code_help;
    $this->ChildMenu = null;
    $this->isMenuItem = true;
  }

}

Class CDesktopSettings {

  public $MenuObject; //многомерный массив элементов меню
  public $ShortcutObject; //массив шоткатов рабочего стола
  public $AutorunItems; //массив элементов автозапуска
  public $Autorun; // автозауск задействован
  public $wallpaper; //картинка десктопа
  public $wallpaperStretch; //растягивать картинку десктопа
  public $UserName;

}
