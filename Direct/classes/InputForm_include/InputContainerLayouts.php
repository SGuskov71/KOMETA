<?php

//это файл настроек раскладок контейнера ввода
//таких файлов можно создать несколько и разложить по задачам вызывая нужный из профиля задачи

$InputContainerTemplate->code = 'LayoutTemplate0';
$InputContainerTemplate->caption = 'Не выбран';
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate1';
$InputContainerTemplate->caption = 'type = "column", reserveScrollbar = true';
$InputContainerTemplate->template->layout->type = "column";
$InputContainerTemplate->template->layout->reserveScrollbar = true; // резервирует место для вертикального скроллбара
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate2';
$InputContainerTemplate->caption = 'type = "hbox"';
$InputContainerTemplate->template->layout->type = "hbox";
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate3';
$InputContainerTemplate->caption = 'type = "vbox", align = "stretch", padding = "15"';
$InputContainerTemplate->template->layout->type = "vbox";
$InputContainerTemplate->template->layout->align = 'stretch';
$InputContainerTemplate->template->layout->padding = '15';
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate4';
$InputContainerTemplate->caption = 'type = "vbox", align = "stretch", padding = "0"';
$InputContainerTemplate->template->layout->type = "vbox";
$InputContainerTemplate->template->layout->align = 'stretch';
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate5';
$InputContainerTemplate->caption = 'type = "vbox", align = "stretchmax", padding = "15"';
$InputContainerTemplate->template->layout->type = "vbox";
$InputContainerTemplate->template->layout->align = 'stretchmax';
$InputContainerTemplate->template->layout->padding = '15';
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate6';
$InputContainerTemplate->caption = 'type = "vbox", align = "stretchmax", padding = "0"';
$InputContainerTemplate->template->layout->type = "vbox";
$InputContainerTemplate->template->layout->align = 'stretchmax';
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate7';
$InputContainerTemplate->caption = 'type = "vbox", padding = "15"';
$InputContainerTemplate->template->layout->type = "vbox";
$InputContainerTemplate->template->layout->padding = '15';
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

$InputContainerTemplate->code = 'LayoutTemplate8';
$InputContainerTemplate->caption = 'type = "vbox"';
$InputContainerTemplate->template->layout->type = "vbox";
array_push($InputContainerLayouts, $InputContainerTemplate);
unset($InputContainerTemplate);

//$InputContainerTemplate->code = 'LayoutTemplate2';
//$InputContainerTemplate->caption = 'Панель с колонками 2';
//$InputContainerTemplate->template->fieldDefaults->labelAlign = "top";
//$InputContainerTemplate->template->fieldDefaults->labelWidth = 250;
//$InputContainerTemplate->template->fieldDefaults->labelStyle = "text-align:right;display:block;";
//$InputContainerTemplate->template->layout->type = "vbox";
//$InputContainerTemplate->template->layout->align = "stretch";
//array_push($InputContainerLayouts, $InputContainerTemplate);
//unset($InputContainerTemplate);
