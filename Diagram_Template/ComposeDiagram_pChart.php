<?php

require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

include($_SESSION['LIB'] . "PHP/pChart2.1.4/class/pData.class.php");
include($_SESSION['LIB'] . "PHP/pChart2.1.4/class/pDraw.class.php");
include($_SESSION['LIB'] . "PHP/pChart2.1.4/class/pImage.class.php");


_ComposeDiagram_pChart($_GET['Diagram_Template_Code'], $_GET['ParamValuesArray']);

function _ComposeDiagram_pChart($Diagram_Template_Code, $ParamValuesArray) {

  $font=$_SESSION['LIB'] . "PHP/pChart2.1.4/fonts/arial.ttf";
  function hex2rgb($keys, $hex) {
    $hex = str_replace("#", "", $hex);

    if (strlen($hex) == 3) {
      $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
      $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
      $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }
    $rgb = array($keys[0] => $r, $keys[1] => $g, $keys[2] => $b);
    //return implode(",", $rgb); // returns the rgb values separated by commas
    return $rgb; // returns an array with the rgb values
  }

  if (!isset($Diagram_Template_Code)) {
    $result = new JSON_Result(false, 'РќРµ РѕРїСЂРµРґРµР»РµРЅРѕ РєРѕРґ РґРёР°РіСЂР°РјРјС‹', NULL);
    return $result;
  }

  if (isset($ParamValuesArray) && !is_object($ParamValuesArray))
    $ParamValuesArray = json_decode($ParamValuesArray);

  $sql = "Select description, contents FROM mbg_diagram_template where code='$Diagram_Template_Code'";
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    $result = new JSON_Result(false, my_escape_string($sql . '<br>' . $s_err . '<br>'), NULL);
    return $result;
  }
  $row = kometa_fetch_object($res);
  if (!isset($row)) {
    $result = new JSON_Result(false, "Диаграмма с кодом $Diagram_Template_Code не найдена", NULL);
    return $result;
  }

  // преобразуем contents  в объект
  $diagram = json_decode($row->contents);

  if (!isset($diagram->diagram_width) || ($diagram->diagram_width == 0))
    $diagram->diagram_width = 800;
  if (!isset($diagram->diagram_height) || ($diagram->diagram_height == 0))
    $diagram->diagram_height = 480;

  if (($diagram->legend_font_size==0)||isset($diagram->legend_font_size))
    $diagram->legend_font_size='12px';
  if (($diagram->header_font_size==0)||isset($diagram->header_font_size))
    $diagram->header_font_size='12px';
  if (($diagram->label_font_size==0)||isset($diagram->label_font_size))
    $diagram->label_font_size='12px';

  foreach ($ParamValuesArray as $p => $v) {
    $diagram->Description = str_replace(':' . $p . ':', str_replace('"', '\"', $v), $diagram->Description);
  }

// а теперь формируем сами данные для графика
  $fcolor = '';

  $array_series = array();
  $color_series = array();

  foreach ($diagram->children as $key_graph => $graph) {

    $label_graph = $graph->Description;

    foreach ($ParamValuesArray as $key => $value) {
      $label_graph = str_replace(":" . $key . ":", $value, $label_graph);
    }

    $select_graph = $graph->SQL;
// изменяем запрос в зависимости от переданных параметров
    foreach ($ParamValuesArray as $key => $value) {
      $select_graph = str_replace('":' . $key . ':"', '"' . str_replace('"', '\"', $value) . '"', $select_graph);
      $select_graph = str_replace(":" . $key . ":", $value, $select_graph);
    }

    array_push($array_series, $label_graph);
    array_push($color_series, $graph->chart_color);

    $color_graph = $graph->graph_color;
    $ref_graph = $graph->graph_ref;

    $selRez = kometa_query($select_graph);

    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, my_escape_string($select_graph . '<br>' . $s_err . '<br>'), NULL);
      return $result;
    }

    while ($data = kometa_fetch_object($selRez)) {
      $data->x = trim($data->x);

      if (isset($data->y) && is_numeric($data->y)) {
        if (substr($data->y, 0, 1) == '.')
          $data->y = floatval('0' . $data->y);
        $d[$data->x][$label_graph] = $data->y;
      }
    }
  }

  $array_X = array();
  $myData = new pData();
  $width_legeng=0;
  $heigth_bottom_scale=0;

  foreach ($array_series as $s_k => $s_val) {
    $array_Y = array();
    foreach ($d as $key => $value) {
      array_push($array_Y, $value[$s_val]);
    }
    $myData->addPoints($array_Y, "Serie" . $s_k);
    $myData->setSerieDescription("Serie" . $s_k, $s_val);
    $myData->setSerieOnAxis("Serie" . $s_k, 0);
    $rgb1 = hex2rgb(Array('R', 'G', 'B'), $color_series[$s_k]);
    $myData->setPalette("Serie" . $s_k, $rgb1);
    
    $legend_box=imageftbbox($diagram->legend_font_size, 0, $font, $s_val);
    if ($width_legeng<$legend_box[2])
      $width_legeng=$legend_box[2];
  }

  $width_legeng=$width_legeng+10;
  
  foreach ($d as $key => $value) {
    array_push($array_X, $key);
    $box=imageftbbox($diagram->label_font_size, $diagram->label_rotate, $font, $key);
    if ($box[2]>$heigth_bottom_scale)
      $heigth_bottom_scale=sin(deg2rad($diagram->label_rotate))*$box[2];
      
  }
  $heigth_bottom_scale=$heigth_bottom_scale+10;
  $myData->addPoints($array_X, "Absissa");
  $myData->setAbscissa("Absissa");

  $myData->setAxisPosition(0, AXIS_POSITION_LEFT);
  $myData->setAxisName(0, $diagram->y_name);
  $myData->setAxisUnit(0, "");

  $myPicture = new pImage($diagram->diagram_width, $diagram->diagram_height, $myData);

  $rgb1 = hex2rgb(Array('R', 'G', 'B'), $diagram->bg_color);
  $rgb2 = hex2rgb(Array("DashR", "DashG", "DashB"), $diagram->header_text_color);

  $Settings = $rgb1 + $rgb2;
  $myPicture->drawFilledRectangle(0, 0, $diagram->diagram_width, $diagram->diagram_height, $Settings);

  $rgb1 = hex2rgb(Array("StartR", "StartG", "StartB"), $diagram->bg_color);
  $rgb2 = hex2rgb(Array("EndR", "EndG", "EndB"), $diagram->bg_color);

  $Settings = $rgb1 + $rgb2;
  $Settings["Alpha"] = 0; //50; РїСЂРѕР·СЂР°С‡РЅРѕСЃС‚СЊ С„РѕРЅР°
  $myPicture->drawGradientArea(0, 0, $diagram->diagram_width, $diagram->diagram_height, DIRECTION_VERTICAL, $Settings);

  $myPicture->drawRectangle(0, 0, $diagram->diagram_width - 1, $diagram->diagram_height - 1, array("R" => 0, "G" => 0, "B" => 0));

  //$myPicture->setShadow(TRUE, array("X" => 1, "Y" => 1, "R" => 50, "G" => 50, "B" => 50, "Alpha" => 20));

  $myPicture->setFontProperties(array("FontName" => $font, "FontSize" => $diagram->header_font_size));

  $rgb1 = hex2rgb(Array('R', 'G', 'B'), $diagram->header_text_color);
  $rgb1["Align"] = TEXT_ALIGN_MIDDLEMIDDLE;
  $TextSettings = $rgb1;
  $myPicture->drawText($diagram->diagram_width / 2, 25, $diagram->Description, $TextSettings);

  switch ($diagram->legend_position) {
    case 1://внизу
      $myPicture->setGraphArea(
              50 + ($diagram->legend_font_size + 4)
              , $diagram->header_font_size + 20
              , $diagram->diagram_width - ($diagram->header_font_size + 4) 
              , $diagram->diagram_height - 20 -$heigth_bottom_scale- ($diagram->header_font_size + 4) - ($diagram->legend_font_size + 4));
      break;
    case 2:// слева
      $myPicture->setGraphArea(50 + $width_legeng, 
              $diagram->header_font_size + 4+20,
              $diagram->diagram_width - ($diagram->header_font_size + 4) - $width_legeng, 
              $diagram->diagram_height - 20-$heigth_bottom_scale);

      break;
    case 3:// справа
      $myPicture->setGraphArea(50, 
              $diagram->header_font_size + 4+20, 
              $diagram->diagram_width -50-$width_legeng, 
              $diagram->diagram_height -20-  ($diagram->header_font_size + 4) -$heigth_bottom_scale);

      break;

    case 4://сверху
      $myPicture->setGraphArea(
              50 + ($diagram->legend_font_size + 4)
              , $diagram->header_font_size + 20 + ($diagram->legend_font_size + 4)
              , $diagram->diagram_width - ($diagram->header_font_size + 4) - 20
              , $diagram->diagram_height - ($diagram->header_font_size + 4) -$heigth_bottom_scale);

      break;
    default:
      $myPicture->setGraphArea(
              50 
              , $diagram->header_font_size + 4+20
              , $diagram->diagram_width - ($diagram->header_font_size + 4)
              , $diagram->diagram_height - ($diagram->header_font_size + 4) -$heigth_bottom_scale);
      break;
  }

  $rgb1 = hex2rgb(Array('R', 'G', 'B'), $diagram->info_label_color);

  $myPicture->setFontProperties($rgb1 + array("FontName" => $font, "FontSize" => $diagram->label_font_size));

  $rgb1 = hex2rgb(Array("GridR", "GridG", "GridB"), $diagram->info_label_color);
  $rgb2 = hex2rgb(Array("TickR", "TickG", "TickB"), $diagram->info_label_color);
  $rgb3 = hex2rgb(Array("SubTickR", "SubTickG", "SubTickB"), $diagram->info_label_color);

  $Settings = array("Pos" => SCALE_POS_LEFTRIGHT
      , "Mode" => SCALE_MODE_FLOATING
      , "LabelingMethod" => LABELING_ALL
      , "GridAlpha" => 50, "TickAlpha" => 50, "LabelRotation" => $diagram->label_rotate,
      "CycleBackground" => 1, "DrawXLines" => 1, "DrawSubTicks" => 0,
      "SubTickAlpha" => 50, "DrawYLines" => ALL) + $rgb1 + $rgb2 + $rgb3;
  $myPicture->drawScale($Settings);

  //$myPicture->setShadow(TRUE, array("X" => 1, "Y" => 1, "R" => 50, "G" => 50, "B" => 50, "Alpha" => 10));

  $Config = array("AroundZero" => 1);

  if ($diagram->label_display != 'none')
    $Config["DisplayValues"] = 1;

  switch ($diagram->diagram_type) {
    case '1'://Круговая
      {
        break;
      }
    case '2':// Гистограмма (раздельные столбцы)
      {
        $myPicture->drawBarChart($Config);
        break;
      }
    case '3': //Гистограмма (общие столбцы)
      {
        $myPicture->drawStackedBarChart($Config);

        break;
      }
    case '4': //Линейный график
      {
        $myPicture->drawLineChart($Config);
        break;
      }
    case '5':// График-радар (с заливкой)
      {
        //        $myPicture->setGraphArea(350, 25, 690, 225);
        //        $Options = array("Layout" => RADAR_LAYOUT_CIRCLE, "LabelPos" => RADAR_LABELS_HORIZONTAL,
        //            "BackgroundGradient" => array("StartR" => 255, "StartG" => 255, "StartB" => 255, "StartAlpha" => 50, "EndR" => 32, "EndG" => 109, "EndB" => 174, "EndAlpha" => 30))
        //        ;
        //        $SplitChart->drawRadar($myPicture, $MyData, $Options);
        break;
      }
    case '6':// Линейный с областями
      {
        $myPicture->drawLineChart($Config);
        break;
      }
    case '7'://График-радар (без заливки)
      {
        //$myPicture->drawLineChart($Config);
        break;
      }
    case '8':// Датчик
      {
        break;
      }
  }

  switch ($diagram->legend_position) {
    case 1://внизу
      $Config = array("FontR" => 0, "FontG" => 0, "FontB" => 0, "FontName" => $font, "FontSize" => $diagram->legend_font_size, "Margin" => 6, "Alpha" => 0, "BoxSize" => 5, "Style" => LEGEND_NOBORDER
          , "Mode" => LEGEND_HORIZONTAL
      );
      $myPicture->drawLegend($diagram->diagram_width / 3, $diagram->diagram_height - 25, $Config);


      break;
    case 2:// слева
      $Config = array("FontR" => 0, "FontG" => 0, "FontB" => 0, "FontName" => $font, "FontSize" => $diagram->legend_font_size, "Margin" => 6, "Alpha" => 0, "BoxSize" => 5, "Style" => LEGEND_NOBORDER
          , "Mode" => LEGEND_VERTICAL
      );
      $myPicture->drawLegend(0, $diagram->diagram_height / 3, $Config);


      break;
    case 3:// справа
      $Config = array("FontR" => 0, "FontG" => 0, "FontB" => 0, "FontName" => $font, "FontSize" => $diagram->legend_font_size, "Margin" => 6, "Alpha" => 0, "BoxSize" => 5, "Style" => LEGEND_NOBORDER
          , "Mode" => LEGEND_VERTICAL
      );
      $myPicture->drawLegend($diagram->diagram_width - 50-$width_legeng, $diagram->diagram_height / 3, $Config);


      break;

    case 4://сверху
      $Config = array("FontR" => 0, "FontG" => 0, "FontB" => 0, "FontName" => $font, "FontSize" => $diagram->legend_font_size, "Margin" => 6, "Alpha" => 0, "BoxSize" => 5, "Style" => LEGEND_NOBORDER
          , "Mode" => LEGEND_HORIZONTAL
      );
      $myPicture->drawLegend($diagram->diagram_width / 3, 25 + $diagram->header_font_size + 4, $Config);


      break;
    default:
      break;
  }

  $myPicture->stroke();
}
