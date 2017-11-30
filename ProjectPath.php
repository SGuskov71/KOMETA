<?php

/*
 * Выставляет в сессию путь к корневому каталогу пректа
 * и считывает из файла настроек в сессию системные пути для работы проекта
 * !!!!!!!!! должен лежать в корню проекта !!!!!!!!!!!!!
 */
$sapi = php_sapi_name();

function GetProjectRoot() {
  global $argv;
  //$path_parts = pathinfo(__FILE__);
  $workdir = $argv[1]; //$path_parts['dirname'];
//  if (isset($workdir)) {
//    chdir($workdir);
//  }
  if (isset($workdir) && (substr($workdir, -1, 1) <> '/'))
    $workdir.='/';
  return $workdir;
}

function GetProjectRootURL() {
  $workdir = GetPathConsole('URLProjectRoot');
  return $workdir;
}

function GetPathURL($PathAlias) {
  $result = '';

  return $result;
}

function GetPathConsole($PathAlias) {
  global $argv;
//  echo $PathAlias . PHP_EOL;
//  echo GetProjectRoot() . PHP_EOL;
  $result = '';
  if (file_exists(GetProjectRoot() . 'ProjectPath.ini')) {
    $ProjectPathINIArray = parse_ini_file(GetProjectRoot() . 'ProjectPath.ini');
    $result = $ProjectPathINIArray[$PathAlias];
    if (isset($result) && (substr($result, -1, 1) <> '/'))
      $result.='/';
  }
  else {
    echo 'Файл не найден' . GetProjectRoot() . 'ProjectPath.ini' . PHP_EOL;
  }
  return $result;
}

if ($sapi == 'cli') {
  
} else {
  $_SESSION['ProjectRoot'] = GetProjectRoot();

  if (file_exists(GetProjectRoot() . 'ProjectPath.ini')) {
    $ProjectPathINIArray = parse_ini_file(GetProjectRoot() . 'ProjectPath.ini');
    foreach ($ProjectPathINIArray as $key => $value) {
      if (isset($value) && (substr($value, -1, 1) <> '/'))
        $value.='/';
      $_SESSION["$key"] = $value;
      if (!file_exists($value)) {
          echo "<script>alert(\"Каталог $value не существует\");<script>";
      }
    }
  }
}
?>
