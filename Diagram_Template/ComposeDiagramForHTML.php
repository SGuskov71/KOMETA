<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "Diagram_Template/ComposeDiagram.php");

if ($_POST['ComposeDiagramForHTML']) {
  ComposeDiagramForHTML($_POST['Diagram_Template_Code'], $_POST['ParamValuesArray']);
} else if ($_GET['PreviewComposeDiagramForHTML']) {
  PreviewComposeDiagramForHTML($_GET['Diagram_Template_Code'], $_GET['ParamValuesArray']);
}

