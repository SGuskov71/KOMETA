<?php

/*
 * Это нужно для Touch
 */
session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");

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

$ArrayInterractiveParam = $_GET['ArrayInterractiveParam'];
$s.= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$lib_path/resources/css/ext-all.css\">" . PHP_EOL;
$s.= "        <script type='text/javascript'>" . PHP_EOL;
$s.= "_URLProjectRoot = '" . $_SESSION["URLProjectRoot"] . "'; " . PHP_EOL;
$s.="ID_User =$ID_User;" . PHP_EOL;
$s.= "</script>" . PHP_EOL;
$s.= "<script type=\"text/javascript\" src=\"$lib_path/ext-all.js\"></script>" . PHP_EOL;
$s.= '<script type="text/javascript" src="' . $_SESSION['URLLIB'] . 'JS/extJS/locale/ext-lang-ru.js"></script>';
$s.= "<script  src=\"" . $_SESSION['URLProjectRoot'] . "sys/ServiceFunction.js\" type=\"text/javascript\"></script>";
$s.= '<script type="text/javascript" src="' . $_SESSION['URLProjectRoot'] . 'Direct/api.php"></script>' . PHP_EOL;
$s.= "        <script type='text/javascript'>" . PHP_EOL;
$s.= "    Ext.app.REMOTING_API.enableBuffer = 100;" . PHP_EOL;
$s.="Ext.app.REMOTING_API.timeout = 120000;".PHP_EOL;
$s.= "    Ext.Direct.addProvider(Ext.app.REMOTING_API);" . PHP_EOL;
$s.= "</script>" . PHP_EOL;

$s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "HTML_Report/HTML_Report.js\"></script>" . PHP_EOL;
$s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "Diagram_Template/DrawChart.js\"></script>" . PHP_EOL;
$s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "Diagram_Template/DiagramTemplate.js\"></script>" . PHP_EOL;
$s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "VisualPanel/VisualPanel.js\"></script>" . PHP_EOL;
$s.= "<script type=\"text/javascript\" src=\"" . $_SESSION['URLProjectRoot'] . "QueryBuilder/QueryBuilderFunction.js\"></script>" . PHP_EOL;
$s.= "        <script type='text/javascript'>" . PHP_EOL;

$s.="  Ext.Loader.setPath({
            'Params.view': _URLProjectRoot + 'ArchitectProject/Params/app/view',
            'QueryBuilder.view': _URLProjectRoot + 'ArchitectProject/QueryBuilder/app/view',
            'VisualPanelMainContainer.view': _URLProjectRoot + 'ArchitectProject/VisualPanelMainContainer/app/view'
          });
";
$s.= "Ext.onReady(function () {" . PHP_EOL;
$s.="var _width=Math.round(document.body.clientWidth);" . PHP_EOL;
$s.="var _height=Math.round(document.body.clientHeight);" . PHP_EOL;
$s.= "var ObjCode='" . $_GET['ObjCode'] . "';" . PHP_EOL;
$s.= "var ParamObject=" . $_GET['ParamObject'] . ";" . PHP_EOL;
$s.= "var FuncName='" . $_GET['FunctionName'] . "';" . PHP_EOL;

$s.= "    var ArrayParam= $ArrayInterractiveParam; "
 . "var wp = Ext.create(\"Params.view.InputInterractiveParams\", {ArrayInterractiveParam: ArrayParam});
      wp.addEvents('BtnOk');
      wp.addListener('BtnOk', function (ParamArray) {
         var CallBackParam = ParamObject;
         Ext.each(ArrayParam, function (par) { //обработка параметризованных параметров
          if (par.ParamInterractive != true) {
            Ext.each(ArrayParam, function (cur_par) {
              if ((cur_par.ParamInterractive != true) && (cur_par.ParamCode != par.ParamCode)) {
                par.ParamDefaultValue = par.ParamDefaultValue.replace(new RegExp(':' + cur_par.ParamCode + ':', 'g'), cur_par.ParamDefaultValue);
              }
            });
            for (var prop in ParamArray) {
              par.ParamDefaultValue = par.ParamDefaultValue.replace(new RegExp(':' + prop + ':', 'g'), ParamArray[prop]);
            }
            try {
              par.ParamDefaultValue = eval(par.ParamDefaultValue);
            } catch (e) {
              alert(e.name)
            } finally {
            }
            ParamArray[par.ParamCode] = par.ParamDefaultValue;
          }
        });
        if (CallBackParam == undefined) {
          CallBackParam = {}
        }
        CallBackParam.code = ObjCode;
        CallBackParam.ParamValuesArray = ParamArray;
        var ReturnObj = Ext.create(\"Ext.util.Observable\", {}); //возвращает объект с ожиданием событи выполнения функции куда передается возвращаемое значение функции
        ReturnObj.addEvents('RunFunctionInScript_Return'); //предотвращает повторную загрузку скрипта
        var FuncText = FuncName + '(' + Ext.JSON.encode(CallBackParam) + ')';

        Ext.callback(internalRunFunctionInScript, this, [FuncText, ReturnObj], 10);

      });




cc=Ext.create('Ext.container.Container', {
    id:'GetParams',
    layout: {
      type: 'fit',
  //    align: 'stretch'
    },"
 . "width: _width,"
 . "height: _height,"
 . "renderTo: Ext.Element.get('GetParams'), //Ext.getBody(),
    border: 1,
    style: {
        boderColor: '#000000',
        borderStyle: 'solid',
        borderWidth: '0px'
    },
    items: wp
});" . PHP_EOL;

$s.= "});" . PHP_EOL;

$s.= "</script>" . PHP_EOL;

$s.="<div id='GetParams' align='center'> </div>" . PHP_EOL;
$s.='</body></html>';
echo $s;
