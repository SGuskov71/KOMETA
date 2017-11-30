<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

class Help_class {

  function GetHelpContent_root() {
    $HELP_FILES_DIR = $_SESSION['help_root'];
    $HELP_URL_FILES_DIR = $_SESSION['URLhelp_root'];

    $iii = 0;

    function RecurceChild($id_parent, &$destArray, $pathArray) {
      global $HELP_URL_FILES_DIR;
      global $iii;
      $iii = $iii + 1;
      set_time_limit(700000);
      $res = kometa_query("SELECT mb_help.id_help, mb_help.short_name, mb_help.code_help, mb_help.ord, mb_help.content, mb_help.filename,mb_help_link.id_help_parent as id_parent,  "
              . " (SELECT count(*) from mb_help_link h where mb_help.id_help=h.id_help_parent) as cnt "
              . " from mb_help left join mb_help_link on mb_help.id_help=mb_help_link.id_help_child where mb_help_link.id_help_parent = $id_parent  order by mb_help.ord");
      while ($row = kometa_fetch_object($res)) {
        if (array_search($row->id_help, $pathArray) === false) {
          $b = true;
          foreach ($destArray as $key => $vnode) {
            if ($vnode->link_href == ($HELP_URL_FILES_DIR . $row->filename)) {
              $b = false;
              break;
            }
          }
          if ($b) {
            $node = new CNodeItemObject($row->short_name, $HELP_URL_FILES_DIR . $row->filename);
            array_push($pathArray, $row->id_help);
            if (($row->cnt > 0))
              RecurceChild($row->id_help, $node->children, $pathArray);
            if (count($node->children) === 0) {
              $node->children = null;
              $node->expanded = false;
              $node->expandable = false;
            }
            $n = array_push($destArray, $node);
          }
        }
      }
    }

    set_time_limit(700000);

    global $HELP_URL_FILES_DIR;
    $Content_root = new CNodeItemObject('Оглавление', $HELP_URL_FILES_DIR . "Content.html");
    $res = kometa_query("SELECT mb_help.id_help, mb_help.short_name, mb_help.code_help, mb_help.ord, mb_help.content, mb_help.filename,mb_help_link.id_help_parent as id_parent  from mb_help left join mb_help_link on mb_help.id_help=mb_help_link.id_help_child where mb_help_link.id_help_parent is null  order by mb_help.ord");
    while ($row = kometa_fetch_object($res)) {
      set_time_limit(700000);
      $node = new CNodeItemObject($row->short_name, $HELP_URL_FILES_DIR . $row->filename);
      $pathArray = array();
      RecurceChild($row->id_help, $node->children, $pathArray);
      if (count($node->children) === 0) {
        $node->children = null;
        $node->expanded = false;
        $node->expandable = false;
      }
      $n = array_push($Content_root->children, $node);
    }
    if (count($Content_root->children) === 0) {
      $Content_root->children = null;
      $Content_root->expanded = false;
      $Content_root->expandable = false;
    }
    $resRoot->TreeRoot = $Content_root;
    $resRoot->URLhelp_root = $HELP_URL_FILES_DIR;
    $result = new JSON_Result(true, $s_err, $resRoot);
    return $result;
  }

}

Class CNodeItemObject {

  public $text;
  public $expanded;
  public $children;
  public $link_href;

  function __construct($text, $link_href) {
    $this->text = $text;
    $this->expanded = true;
    $this->children = array();
    $this->link_href = $link_href;
  }

}
