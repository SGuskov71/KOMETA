<?php

//это файл настроек дополнительных свойств контролов ввода
//таких файлов можно создать несколько и разложить по задачам вызывая нужный из профиля задачи

$InputControlProps->xtype = 'textfield';
$InputControlProps->Caption = 'Строка текста';
$InputControlProps->Props->border = '';
$InputControlProps->Props->columnWidth = 0;
$InputControlProps->Props->enforceMaxLength = false;
$InputControlProps->Props->grow = false;
$InputControlProps->Props->growAppend = 'Ш';
$InputControlProps->Props->growMax = ''; //800;
$InputControlProps->Props->growMin = ''; //30;
$InputControlProps->Props->hideEmptyLabel = true;
$InputControlProps->Props->invalidText = 'Неверное значение поля';
$InputControlProps->Props->labelAlign = ''; //'left';
$InputControlProps->Props->labelPad = 5; // расстояние в пикселах между именем поля и его значением
$InputControlProps->Props->labelSeparator = ':';
$InputControlProps->Props->margin = '';
$InputControlProps->Props->maskRe = '';
$InputControlProps->Props->maxHeight = ''; // В пикселах
$InputControlProps->Props->maxLength = ''; // Defaults to Number.MAX_VALUE
$InputControlProps->Props->maxLengthText = ''; // Может содержать текст сообщения об ошибке при вводе текста, превышающего максимальную длину maxLength
$InputControlProps->Props->maxWidth = ''; //1000;
$InputControlProps->Props->minLength = ''; //0;
$InputControlProps->Props->minLengthText = ''; // Может содержать текст сообщения об ошибке при вводе текста, меньше минимальной длины minLength
$InputControlProps->Props->minWidth = ''; //0;
$InputControlProps->Props->padding = '';
array_push($InputControlPropsArray, $InputControlProps);
unset($InputControlProps);

$InputControlProps->xtype = 'combobox';
$InputControlProps->Caption = 'Выпадающий список';
$InputControlProps->Props->border = '';
$InputControlProps->Props->columnWidth = 0;
$InputControlProps->Props->delimiter = ', ';
$InputControlProps->Props->editable = ''; //true;
$InputControlProps->Props->enforceMaxLength = ''; //false;
$InputControlProps->Props->growAppend = 'Ш'; //'W';
$InputControlProps->Props->growToLongestValue = ''; //true;
$InputControlProps->Props->hideEmptyLabel = true;
$InputControlProps->Props->invalidText = 'Неверное значение поля';
$InputControlProps->Props->labelAlign = ''; //"left";
$InputControlProps->Props->labelPad = 5;
$InputControlProps->Props->labelSeparator = ':';
$InputControlProps->Props->margin = '';
$InputControlProps->Props->maxHeight = '' ;
$InputControlProps->Props->maxLength = ''; //5000;
$InputControlProps->Props->maxLengthText = '';
$InputControlProps->Props->maxWidth = ''; //1000;
$InputControlProps->Props->minLength = ''; //0;
$InputControlProps->Props->minLengthText = '';
$InputControlProps->Props->minWidth = ''; //0;
$InputControlProps->Props->padding = ''; //'';
$InputControlProps->Props->pickerAlign = ''; //'tl-bl?';
array_push($InputControlPropsArray, $InputControlProps);
unset($InputControlProps);


$InputControlProps->xtype = 'textareafield';
$InputControlProps->Caption = 'Многострочный текст';
$InputControlProps->Props->border = '';
$InputControlProps->Props->columnWidth = ''; //0;
$InputControlProps->Props->enforceMaxLength = ''; //false;
$InputControlProps->Props->grow = ''; //false;
$InputControlProps->Props->growAppend = 'Ш'; //'W';
$InputControlProps->Props->growMax = ''; //800;
$InputControlProps->Props->growMin = ''; //30;
$InputControlProps->Props->hideEmptyLabel = true;
$InputControlProps->Props->invalidText = 'Неверное значение поля';
$InputControlProps->Props->labelAlign = ''; //"left";
$InputControlProps->Props->labelPad = 5;
$InputControlProps->Props->labelSeparator = ':';
$InputControlProps->Props->margin = '';
$InputControlProps->Props->maxHeight = '';
$InputControlProps->Props->maxLength = ''; //5000;
$InputControlProps->Props->maxLengthText = '';
$InputControlProps->Props->maxWidth = ''; //1000;
$InputControlProps->Props->minLength = ''; //0;
$InputControlProps->Props->minLengthText = '';
$InputControlProps->Props->minWidth = 0;
$InputControlProps->Props->padding = '';
$InputControlProps->Props->rows = 3;
array_push($InputControlPropsArray, $InputControlProps);
unset($InputControlProps);

$InputControlProps->xtype = 'numberfield';
$InputControlProps->Caption = 'Число';
$InputControlProps->Props->allowDecimals = true;
$InputControlProps->Props->allowExponential = true;
$InputControlProps->Props->border = '';
$InputControlProps->Props->columnWidth = 0;
$InputControlProps->Props->decimalPrecision = 2;
$InputControlProps->Props->decimalSeparator = '.';
$InputControlProps->Props->enforceMaxLength = false;
$InputControlProps->Props->growAppend = ''; //'W';
$InputControlProps->Props->hideEmptyLabel = true;
$InputControlProps->Props->invalidText = 'Неверное значение поля';
$InputControlProps->Props->labelAlign = ''; //"left";
$InputControlProps->Props->labelPad = 5;
$InputControlProps->Props->labelSeparator = ':';
$InputControlProps->Props->margin = '';
$InputControlProps->Props->maxHeight = '';
$InputControlProps->Props->maxLength = ''; //5000;
$InputControlProps->Props->maxLengthText = '';
$InputControlProps->Props->maxText = '';
$InputControlProps->Props->maxValue = ''; // Defaults to Number.MAX_VALUE.
$InputControlProps->Props->maxWidth = ''; //1000;
$InputControlProps->Props->minLength = ''; //0;
$InputControlProps->Props->minLengthText = '';
$InputControlProps->Props->minText = '';
$InputControlProps->Props->minValue = ''; // Defaults to Number.NEGATIVE_INFINITY
$InputControlProps->Props->minWidth = 0;
$InputControlProps->Props->negativeText = ''; // сообщение при вводе отрицательного числа
$InputControlProps->Props->padding = '';
$InputControlProps->Props->step = 1; // шаг изменения
array_push($InputControlPropsArray, $InputControlProps);
unset($InputControlProps);

$InputControlProps->xtype = 'datefield';
$InputControlProps->Caption = 'Дата';
$InputControlProps->Props->format = 'd.m.Y';
$InputControlProps->Props->submitFormat = 'Y-m-d';
$InputControlProps->Props->border = '';
$InputControlProps->Props->columnWidth = 0;
$InputControlProps->Props->disabledDates = ''; // см пример в документации
$InputControlProps->Props->disabledDatesText = '';
$InputControlProps->Props->disabledDays = '';
$InputControlProps->Props->disabledDaysText = '';
$InputControlProps->Props->enforceMaxLength = false;
$InputControlProps->Props->growAppend = 'Ш'; //'W';
$InputControlProps->Props->hideEmptyLabel = true;
$InputControlProps->Props->invalidText = 'Неверное значение поля';
$InputControlProps->Props->labelAlign = "left";
$InputControlProps->Props->labelPad = 5;
$InputControlProps->Props->labelSeparator = ':';
$InputControlProps->Props->margin = '';
$InputControlProps->Props->maxHeight = '';
$InputControlProps->Props->maxLengthText = '';
$InputControlProps->Props->maxText = '';
$InputControlProps->Props->maxValue = '';
$InputControlProps->Props->maxWidth = ''; //1000;
$InputControlProps->Props->minLength = 0;
$InputControlProps->Props->minLengthText = '';
$InputControlProps->Props->minText = '';
$InputControlProps->Props->minValue = '';
$InputControlProps->Props->minWidth = 0;
$InputControlProps->Props->padding = '';
$InputControlProps->Props->pickerAlign = ''; //'tl-bl?';
$InputControlProps->Props->startDay = 1; //номер дня с которого начинается неделя. По умолчанию = 0
array_push($InputControlPropsArray, $InputControlProps);
unset($InputControlProps);

$InputControlProps->xtype = 'checkboxfield';
$InputControlProps->Caption = 'Чекбокс';
$InputControlProps->Props->border = '';
$InputControlProps->Props->boxLabelAlign = 'after';
$InputControlProps->Props->checked = false;
$InputControlProps->Props->columnWidth = 0; //0;
$InputControlProps->Props->hideEmptyLabel = true;
$InputControlProps->Props->invalidText = 'Неверное значение поля';
$InputControlProps->Props->labelAlign = ''; //"left";
$InputControlProps->Props->labelPad = 5;
$InputControlProps->Props->labelSeparator = ':';
$InputControlProps->Props->margin = '';
$InputControlProps->Props->maxHeight = '';
$InputControlProps->Props->maxWidth = ''; // 1000;
$InputControlProps->Props->minWidth = ''; //0;
$InputControlProps->Props->padding = '';
array_push($InputControlPropsArray, $InputControlProps);
unset($InputControlProps);

$InputControlProps->xtype = 'pickerfield';
$InputControlProps->Caption = 'Выбор из словаря';
$InputControlProps->Props->border = '';
$InputControlProps->Props->columnWidth = 0;
$InputControlProps->Props->enforceMaxLength = false;
$InputControlProps->Props->growAppend = 'Ш'; //'W';
$InputControlProps->Props->hideEmptyLabel = true;
$InputControlProps->Props->invalidText = 'Неверное значение поля';
$InputControlProps->Props->labelAlign = ''; //"left";
$InputControlProps->Props->labelPad = 5;
$InputControlProps->Props->labelSeparator = ':';
$InputControlProps->Props->margin = '';
$InputControlProps->Props->maxHeight = '';
$InputControlProps->Props->maxLength = 5000;
$InputControlProps->Props->maxLengthText = '';
$InputControlProps->Props->maxWidth = ''; //1000;
$InputControlProps->Props->minLength = 0;
$InputControlProps->Props->minLengthText = '';
$InputControlProps->Props->minWidth = 0;
$InputControlProps->Props->padding = '';
$InputControlProps->Props->pickerAlign = ''; //'tl-bl?';
array_push($InputControlPropsArray, $InputControlProps);
unset($InputControlProps);

