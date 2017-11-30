<?php

session_start();
require_once($_SESSION['ProjectRoot'] . "2gConnection.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");

class Field_class { //объект свойства поля объекта

  public $fieldname; //код поля
  public $type_field; //тип поля в понимании доменов метабазы
  public $type_field_EXTJS;  //тип поля в понимании EXJS
  public $seachable; //поисковое
  public $code_field_style; //имя поля для стиля
  public $show_order; //порядок отображения колонок
  public $sort_flag; //признак сортировки
  public $create_grid_column; //создавать колонку грида
  public $visible_in_grid; //видимость в гриде
  public $short_name; //краткое наим
  public $full_name; //полное наим
  // public $slv_id_Obj; //id_Obj словарного объекта
//  public $multi_select; //множественный выбор
  public $column_width; //ширина колонки
 // public $width_unit; //единица измерения шир. кол.
  public $render_type; //ключ типа рендера формата поля из таблицы mbs_field_dtype
  public $render_mask; //маска форматирования значения в колонке

}
