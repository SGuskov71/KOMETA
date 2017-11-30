<?php

global $n_chart;
$n_chart = 1;

function _ComposeDiagram($Diagram_Template_Code, $ParamValuesArray) {
  if (!isset($Diagram_Template_Code)) {
    $result = new JSON_Result(false, 'Не определено код диаграммы', NULL);
    return $result;
  } else {
    if (isset($ParamValuesArray) && !is_object($ParamValuesArray))
      $ParamValuesArray = json_decode($ParamValuesArray);
    $sql = "Select description, contents FROM mbg_diagram_template where code='$Diagram_Template_Code'";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      $result = new JSON_Result(false, my_escape_string($sql . '<br>' . $s_err . '<br>'), NULL);
      return $result;
    } else {
      $row = kometa_fetch_object($res);
      if (!isset($row)) {
        $result = new JSON_Result(false, "Диаграмма с кодом $Diagram_Template_Code не найдена", NULL);
        return $result;
      }

      // преобразуем contents  в объект

      $diagram = json_decode($row->contents);

      $result = '{';

      $result.= "\"id_chart_type\" : $diagram->diagram_type";
      $result.= ",\"label_rotate\" : \"$diagram->label_rotate\"";
      $result.= ",\"label_type_visible\" : \"$diagram->label_type_visible\"";

      foreach ($ParamValuesArray as $p => $v) {
        $diagram->Description = str_replace(':' . $p . ':', str_replace('"', '\"', $v), $diagram->Description);
      }

      $result.= ",\"label_chart\" : \"$diagram->Description\"";
      $result.= ",\"orientation\" :\"$diagram->orientation\"";
      $result.= ",\"legend_position\" :\"$diagram->legend_position\"";
      $result.= ",\"legend_font_size\" :\"$diagram->legend_font_size\"";
      $result.= ",\"axes_font_size\" :\"$diagram->axes_font_size\"";
      $result.= ",\"axes_label_font_size\" :\"$diagram->axes_label_font_size\"";
      $result.= ",\"label_font_size\" :\"$diagram->label_font_size\"";
      $result.= ",\"ShowGrid_x\" :\"$diagram->ShowGrid_x\"";
      $result.= ",\"ShowGrid_y\" :\"$diagram->ShowGrid_y\"";
      $result.= ",\"gutter\" :\"$diagram->gutter\"";
      $result.= ",\"groupGutter\" :\"$diagram->groupGutter\"";

      $result.= ",\"func_class_name\" :\"$diagram->func_class_name\"";
      $result.= ",\"func_name\" :\"$diagram->func_name\"";
      $result.= ",\"param_list\" :\"$diagram->param_list\"";

      $result.= ",\"label_color\" : \"$diagram->label_color\"";
      $result.= ",\"info_label_color\" : \"$diagram->info_label_color\"";
      $result.= ",\"axes_color\" : \"$diagram->label_axes\"";
      $result.= ",\"bgcolor\" : \"$diagram->bg_color\"";
      $result.= ",\"label_x_axis\" : \"" . str_replace('"', '\"', $diagram->x_name) . "\"";
      $result.= ",\"label_y_axis\" :\"" . str_replace('"', '\"', $diagram->y_name) . "\"";
      $result.= ",\"diagram_width\" :\"$diagram->diagram_width\"";
      $result.= ",\"diagram_height\" :\"$diagram->diagram_height\"";

      $result.= ",\"axes_label_font_size\" :\"$diagram->axes_label_font_size\"";
      $result.= ",\"axes_label_font_size\" :\"$diagram->axes_label_font_size\"";
      $result.= ",\"diagram_theme\" :\"$diagram->diagram_theme\"";
      $result.= ",\"label_display\" :\"$diagram->label_display\"";
      $result.= ",\"legend_box_color\" :\"$diagram->legend_box_color\"";
      $result.= ",\"legend_label_color\" :\"$diagram->legend_label_color\"";

      $result.= ",\"header_text_color\" :\"$diagram->header_text_color\"";
      $result.= ",\"header_fill_color\" :\"$diagram->header_fill_color\"";
      $result.= ",\"header_font_size\" :\"$diagram->header_font_size\"";




// а теперь формируем сами данные для графика
      $i = 0;
      $h = "{\"fields\":[\"X\",";
      $comma = '';
      $d_minimum = 0;
      $d_maximum = 0;

      $fcolor = '';

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

        $color_graph = $graph->graph_color;
        $ref_graph = $graph->graph_ref;

        $selRez = kometa_query($select_graph);

        $s_err = kometa_last_error();
        if (isset($s_err) && ($s_err != '')) {
          $result = new JSON_Result(false, my_escape_string($select_graph . '<br>' . $s_err . '<br>'), NULL);
          return $result;
        }
        $h = $h . $comma . " \"Y$i\", \"func_class_nameY$i\",\"func_nameY$i\",\"param_listY$i\"";
        $fs = $fs . $comma . " \"Y$i\"";

        $fcolors .= $comma . " \"" . $graph->chart_color . "\"";
        $t = $t . $comma . " \"$label_graph\"";

        $dh = "\"data\":[\n";


        while ($data = kometa_fetch_object($selRez)) {
          $data->x = trim($data->x);
          $s_x = str_replace('\\', '\\\\', $data->x);
          $s_x = str_replace('"', '\"', $s_x);
          if (!isset($d[trim($data->x)])) {
            $d[$data->x] = "{\"X\":\"" . $s_x . "\"";
          }

          if (isset($data->y) && is_numeric($data->y)) {
            if (substr($data->y, 0, 1) == '.')
              $data->y = floatval('0' . $data->y);
            $d[$data->x] .= ", \"Y$i\": $data->y, \"func_class_nameY$i\": \"" . $data->func_class_name . "\",\"func_nameY$i\":\"" . $data->func_name . "\",\"param_listY$i\":\"" . $data->param_list . "\"";
            if (!isset($d_minimum) || ($d_minimum > $data->y))
              $d_minimum = $data->y;
            if (!isset($d_maximum) || ($d_maximum < $data->y))
              $d_maximum = $data->y;
          }
        }

        $i++;
        $comma = ',';
      }

      unset($k);
      foreach ($d as $key => $value) {
        if (isset($k))
          $d[$k] = $d[$k] . "}," . chr(10);
        $k = $key;
      }
      if (isset($k) && is_array($d) && array_count_values($d) > 0)
        $d[$k] = $d[$k] . "}" . chr(10);

      $h = $h . "]," . chr(10) . $dh;

      foreach ($d as $key => $value)
        $h = $h . $d[$key] . chr(10);

      $h = $h . "]" . chr(10);
      $h = $h . chr(10) . "}" . chr(10);

      $result.=",\"store_data\":" . $h;
      $result.=",\"fields\":[$fs]";
      $result.=",\"colors\":[$fcolors]";

      $result.=",\"title\":[$t]";
      $result.= ",\"minimum\" :\"$d_minimum\"";
      $result.= ",\"maximum\" :\"$d_maximum\"";
// строим для series
    }
    $result.='}';

    $r = json_decode($result);
    switch (json_last_error()) {
      case JSON_ERROR_NONE:
        $res = new JSON_Result(true, '', $r);
        return $res;
      case JSON_ERROR_DEPTH:
        $s_err = ' - Достигнута максимальная глубина стека';
        break;
      case JSON_ERROR_STATE_MISMATCH:
        $s_err = ' - Некорректные разряды или не совпадение режимов';
        break;
      case JSON_ERROR_CTRL_CHAR:
        $s_err = ' - Некорректный управляющий символ';
        break;
      case JSON_ERROR_SYNTAX:
        $s_err = ' - Синтаксическая ошибка, не корректный JSON';
        break;
      case JSON_ERROR_UTF8:
        $s_err = ' - Некорректные символы UTF-8, возможно неверная кодировка';
        break;
      default:
        $s_err = ' - Неизвестная ошибка';
        break;
    }
    $res = new JSON_Result(false, $s_err, NULL);

    return $res;
  }
}

function ComposeDiagramForHTML($Diagram_Template_Code, $ParamValuesArray) {
  global $n_chart;
  global $ID_User;
  $diagram = _ComposeDiagram($Diagram_Template_Code, $ParamValuesArray);
  if (!($diagram->success)) {
    echo 'Ошибка построения диаграммы ' . my_escape_string(json_encode($diagram));
    exit;
  }
  $lib_path = $_SESSION['URLLIB'] . 'JS/extJS';
  $s = "        <script type='text/javascript'>" . PHP_EOL;
  $s.= "<script type=\"text/javascript\" src=\"$lib_path/ext-all.js\"></script>" . PHP_EOL;
  $s.= '<script type="text/javascript" src="' . $_SESSION['URLLIB'] . 'JS/extJS/locale/ext-lang-ru.js"></script>';
  $s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "Diagram_Template/DrawChart.js\"></script>" . PHP_EOL;
  $s.= "<script  src=\"" . $_SESSION['URLProjectRoot'] . "sys/ServiceFunction.js\" type=\"text/javascript\"></script>";
  $s.= "        <script type='text/javascript'>" . PHP_EOL;
  $s.= "    Ext.app.REMOTING_API.enableBuffer = 100;" . PHP_EOL;
  $s.= "    Ext.Direct.addProvider(Ext.app.REMOTING_API);" . PHP_EOL;
  $s.= "_URLProjectRoot = '" . $_SESSION["URLProjectRoot"] . "'; " . PHP_EOL;
  $s.="ID_User =$ID_User;" . PHP_EOL;
  $s.= "var diagram$n_chart = " . json_encode($diagram->result) . ";" . PHP_EOL;
  $s.=" diagram$n_chart.PreviewMode =1;";
  $s.= "Ext.onReady(function () {" . PHP_EOL;

  $s.=" if ((diagram$n_chart.diagram_width == undefined) || (diagram$n_chart.diagram_width=='') || (diagram$n_chart.diagram_width<20))" . PHP_EOL;
  $s.=" _width=Math.round(document.body.clientWidth -20);" . PHP_EOL;
  $s.="else" . PHP_EOL;
  $s.=" _width=diagram$n_chart.diagram_width;" . PHP_EOL;

  $s.=" if ((diagram$n_chart.diagram_height == undefined) "
  . "|| (diagram$n_chart.diagram_height=='') "
  . "|| (diagram$n_chart.diagram_height<20))" . PHP_EOL;
  $s.=" _height=Math.round(document.body.clientHeight -50);" . PHP_EOL;
  $s.="else" . PHP_EOL;
  $s.=" _height=diagram$n_chart.diagram_height;" . PHP_EOL;

  $s.= "var w = draw_chart(diagram$n_chart,$ParamValuesArray);" . PHP_EOL;
  $s.= "Ext.create('Ext.container.Container', {
    id:'container$n_chart'
    layout: 'fit',"
  . "width: _width,"
  . "height: _height,"
  . "renderTo: Ext.Element.get('chart$n_chart'), //Ext.getBody(),
    border: 1,
    style: {
        boderColor: '#000000',
        borderStyle: 'solid',
        borderWidth: '0px'
    },
    items: w
});" . PHP_EOL;
  $s.= "});" . PHP_EOL;
  $s.= "</script>" . PHP_EOL;
  $s.="<div id='chart$n_chart' align='center'> </div>" . PHP_EOL;
  $n_chart = $n_chart + 1;
  return $s;
}

function PreviewComposeDiagramForHTML($Diagram_Template_Code, $ParamValuesArray) {
  global $n_chart;
  global $ID_User;
  $lib_path = $_SESSION['URLLIB'] . 'JS/extJS';
  $s = '<HTML>
    <HEAD>
        <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
        <TITLE>
            Просмотр данных
        </TITLE>
    </HEAD>
<body>';


  //$s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$lib_path/resources/css/ext-all.css\">" . PHP_EOL;
  $theme = get_Param_value('theme', $ID_User);
  if (!isset($theme) || ($theme == ''))
    $s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/css/ext-all.css\" />";
  else {
    if (open_url($_SESSION['URLLIB'] . "JS/extJS/resources/ext-theme-$theme/ext-theme-$theme-all.css")) {
      $s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/ext-theme-$theme/ext-theme-$theme-all.css\" />";
    } else {
      $s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $_SESSION['URLLIB'] . "JS/extJS/resources/css/ext-all.css\" />";
    }
  }

  $s.= "        <script type='text/javascript'>" . PHP_EOL;
  $s.= "_URLProjectRoot = '" . $_SESSION["URLProjectRoot"] . "'; " . PHP_EOL;
  $s.="ID_User =$ID_User;" . PHP_EOL;
  $s.= "</script>" . PHP_EOL;
  $s.= "<script type=\"text/javascript\" src=\"$lib_path/ext-all.js\"></script>" . PHP_EOL;
  $s.= '<script type="text/javascript" src="' . $_SESSION['URLLIB'] . 'JS/extJS/locale/ext-lang-ru.js"></script>';
  $s.= "<script  src=\"" . $_SESSION['URLProjectRoot'] . "sys/ServiceFunction.js\" type=\"text/javascript\"></script>";
  $s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "HTML_Report/HTML_Report.js\"></script>" . PHP_EOL;
  $s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "Diagram_Template/DrawChart.js\"></script>" . PHP_EOL;
  $s.= '<script type="text/javascript" src="' . $_SESSION['URLProjectRoot'] . 'Direct/api.php"></script>';
  $s.= "        <script type='text/javascript'>" . PHP_EOL;
  $s.= "    Ext.app.REMOTING_API.enableBuffer = 100;" . PHP_EOL;
  $s.= "    Ext.Direct.addProvider(Ext.app.REMOTING_API);" . PHP_EOL;

  $diagram = _ComposeDiagram($Diagram_Template_Code, $ParamValuesArray);
  if (!($diagram->success)) {
    echo "Ext.MessageBox.alert(\"Ошибка подготовки данных для отображения графика: \" , \"" . $diagram->msg . "\");";
    exit;
  } else {
    $s.= "var diagram$n_chart = " . json_encode($diagram->result) . ";" . PHP_EOL;
    $s.= "diagram$n_chart.num = \"$n_chart\";" . PHP_EOL;
    $s.=" diagram$n_chart.PreviewMode =1;";
    $s.= "Ext.onReady(function () {" . PHP_EOL;

    $s.="wh=document.getElementById ('header$n_chart');" . PHP_EOL;
    $s.="h=wh.clientHeight;" . PHP_EOL;
    $s.=" if ((diagram$n_chart.diagram_width == undefined) || (diagram$n_chart.diagram_width=='') || (diagram$n_chart.diagram_width<20))" . PHP_EOL;
    $s.=" _width=Math.round(document.body.clientWidth);" . PHP_EOL;
    $s.="else" . PHP_EOL;
    $s.=" _width=diagram$n_chart.diagram_width;" . PHP_EOL;

    $s.=" if ((diagram$n_chart.diagram_height == undefined) "
    . "|| (diagram$n_chart.diagram_height=='') "
    . "|| (diagram$n_chart.diagram_height<20))" . PHP_EOL;
    $s.=" _height=Math.round(document.body.clientHeight -h);" . PHP_EOL;
    $s.="else" . PHP_EOL;
    $s.=" _height=diagram$n_chart.diagram_height;" . PHP_EOL;

    if (!isset($ParamValuesArray))
      $ParamValuesArray = '{}';
    $s.= "var w = draw_chart(diagram$n_chart,$ParamValuesArray);" . PHP_EOL;
    $s.= "cc=Ext.create('Ext.container.Container', {
    id:'cont$n_chart',
    layout: {
      type: 'hbox',
      align: 'stretch'
    },"
    . "width: _width,"
    . "height: _height,"
    . "renderTo: Ext.Element.get('chart$n_chart'), //Ext.getBody(),
    border: 1,
    style: {
        boderColor: '#000000',
        borderStyle: 'solid',
        borderWidth: '0px'
    },
    items: w
});" . PHP_EOL;

    $s.= "});" . PHP_EOL;
  }
  $s.= "</script>" . PHP_EOL;
  $s.="<div id='header$n_chart' align='center' style=\"background-color: #"
  . $diagram->result->header_fill_color . "; color:#"
  . $diagram->result->header_text_color . "; font-size: " . $diagram->result->header_font_size
  . "; line-height: normal; font-family: sans-serif;\" >"
  . $diagram->result->label_chart . "</div>";
  $s.="<div id='chart$n_chart' align='center'> </div>" . PHP_EOL;
  $n_chart = $n_chart + 1;
  $s.='</body></html>';
  echo $s;
}
