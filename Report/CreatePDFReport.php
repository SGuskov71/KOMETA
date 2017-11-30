<?php

/*
 * С„РѕСЂРјРёСЂРѕРІР°РЅРёРµ ODT РґРѕРєСѓРјРµРЅС‚Р° РїРѕ С€Р°Р±Р»РѕРЅСѓ
 */
session_start();
require_once($_SESSION['ProjectRoot'] . 'Lib/myPDF/pdf.php');
//require_once($_SESSION['LIB'] . 'PHP/pdf/fpdf.php');

require_once($_SESSION['ProjectRoot'] . "Report/Report_classes.php");
require_once($_SESSION['ProjectRoot'] . "sys/mb_common.php");
require_once($_SESSION['ProjectRoot'] . "FileUpload/FileUploadBackEndFunction.php");

function BuildPDFReportBody($pdf, $PDF_Template, $ParamValuesArray, &$SubReportItterationArray, $ThisIsSubReport = false) { //С„РѕСЂРјРёСЂРѕРІР°РЅРёРµ СЃР°РјРѕРіРѕ С‚РµР»Р° РѕС‚С‡РµС‚Р° РїРѕ С€Р°Р±Р»РѕРЅСѓ
  global $style_font;
  $maxfontsize = 0;
  $style_font = array();

  function hex2rgb($hex) {
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
    $rgb = "$r,$g,$b";
    //return implode(",", $rgb); // returns the rgb values separated by commas
    return $rgb; // returns an array with the rgb values
  }

  function LoadCellStylesObject($pdf, $CellStylesFileName) {// Р·Р°РіСЂСѓР¶Р°РµС‚СЃСЏ РѕР±СЉРµРєС‚ СЃРѕРґРµСЂР¶Р°С‰РёР№ РѕРїСЂРµРґРµР»РµРЅРёСЏ СЃС‚РёР»РµР№ СЏС‡РµРµРє С‚Р°Р±Р»РёС†С‹
    global $style_font;
    $s2 = file_get_contents($_SESSION['APP_INI_DIR'] . $CellStylesFileName);
    $result1 = unserialize($s2);
    foreach ($result1 as $value) {
      $style = '';
      if ($value->Underline == true) {
        $style .= 'U';
      }
      if ($value->Bold == true)
        $style .= 'B';
      if ($value->Italic == true)
        $style .= 'I';

      if ($style == '')
        $style = 'N';
      $align = 'J';
      switch ($value->TextAlign) {
        case 'RB_LEFT':$align = 'L';

          break;
        case 'RB_CENTER':$align = 'С';

          break;
        case 'RB_RIGHT':$align = 'R';

          break;
        case 'RB_JUSTIFY':$align = 'J';
          break;
      }

      $value->TextAlignment = $align;

      $value->FontColor = hex2rgb($value->FontColor);
      $value->BackgroundColor = hex2rgb($value->BackgroundColor);
      $value->style = $style;

      $style_font[$value->StyleName] = $value;
      $pdf->SetStyle($value->StyleName, "courier", $style, $value->FontSize, $value->FontColor, $value->BackgroundColor);
    }
    $style_font['default'] = $value;
    $value->FontSize = 12;
    $value->FontColor = '255,255,255';
    $value->BackgroundColor = '0,0,0';
    $value->TextAlignment = 'J';
    $style = '';
    $pdf->SetStyle('default', "courier", $style, $value->FontSize, $value->FontColor, $value->BackgroundColor);
  }

  function writeParagraph($pdf, $Props, $ReportQueryRow, $ParamValues) {
    global $maxfontsize;
    $pdf->SetStyle("p", "courier", "N", 12, "255,0,0", "255,255,255", $Props->TextIdent);
//РѕС‚СЃС‚СѓРїС‹ СЃ РєСЂР°РµРІ
    $LeftMargin = $Props->LeftMargin;
    $RightMargin = $Props->RightMargin;

    $align = 'J';
    if (isset($Props->TextAlignment)) {
      switch ($Props->TextAlignment) {
        case 'RB_LEFT':$align = 'L';

          break;
        case 'RB_CENTER':$align = 'С';

          break;
        case 'RB_RIGHT':$align = 'R';

          break;
        case 'RB_JUSTIFY':$align = 'J';
          break;
      }
    }

    $p = '<p>'; // РЅР°С‡Р°Р»Рѕ С„РѕСЂРјРёСЂРѕРІР°РЅРёСЏ Р°Р±Р·Р°С†Р°
    $maxfontsize = 0;
    if (isset($Props->children)) {
      foreach ($Props->children as $reportItem) {
        switch ($reportItem->ItemType) {
          case 'text':$p.=writeText($reportItem, $ReportQueryRow, $ParamValues, $TextStylesArray);
            break;
          case 'hyperlink': $p.=writeHyperlink($reportItem, $ReportQueryRow, $ParamValues);
            break;
          case 'image': $p.=writeImage($reportItem, $ReportQueryRow, $ParamValues);
            break;
          case 'linebreak':
            //$p->addLineBreak();
            break;
        }
      }
      //$pdf->MultiCell(0, 12*1.5*0.0352777778, iconv('utf-8','windows-1251',$p), 0, $align,0, $Props->TextIdent);
    }
    $p.='</p>';
    $pdf->WriteTag(0, $maxfontsize * 1.5 * 0.0352777778, iconv('utf-8', 'windows-1251', $p), 0, $align, 1, 0);
//    $pdf->WriteTag(0, $maxfontsize * 1.5 * 0.0352777778, $p, 0, $align, 0, 0);
  }

  function writeText($Props, $ReportQueryRow, $ParamValues, $TextStylesArray) {
    global $maxfontsize;
    global $style_font;

    $textStyleCode = $Props->TextStyle;

    $text = '';
    switch ($Props->DataSource) {
      case 'RB_Text':
        foreach ($ParamValues as $key => $value) {
          $Props->TextBlock = str_replace(':' . $key . ':', $value, $Props->TextBlock);
        }
        foreach ($ReportQueryRow as $key => $value) {
          $Props->TextBlock = str_replace(':' . $key . ':', $value, $Props->TextBlock);
        }
        $text = $Props->TextBlock;
        break;
      case 'RB_SQL': {
          $DataField = $Props->DataField;
          $text = $ReportQueryRow->$DataField;
        }
        break;
    }
    if (isset($textStyleCode) && ($textStyleCode != '')) {
      if ($maxfontsize < $style_font[$textStyleCode]->FontSize)
        $maxfontsize = $style_font[$textStyleCode]->FontSize;
      return "<$textStyleCode>$text</$textStyleCode>";
    } else
      return $text;
  }

  function writeHyperlink($Props, $ReportQueryRow, $ParamValues) {
    global $style_font;
    global $maxfontsize;
    $textStyleCode = $Props->TextStyle;
    if (!isset($textStyleCode) || ($textStyleCode == ''))
      $textStyleCode = 'a';
    switch ($Props->DataSource) {
      case 'RB_Text':
        $text = "<$textStyleCode href=\"$Props->LinkURL\">$Props->LinkText</$textStyleCode>";
        break;
      case 'RB_SQL': {
          $DataField1 = $Props->DBLinkText;
          $DataField2 = $Props->DBLinkURL;
          if ($maxfontsize < $style_font[$textStyleCode]->FontSize)
            $maxfontsize = $style_font[$textStyleCode]->FontSize;
          $text = "<$textStyleCode href=\"$ReportQueryRow->$DataField2\">$ReportQueryRow->$DataField1</$textStyleCode>";
          break;
        }
    }
    return $text;
  }

  function writeImage($Props, $ReportQueryRow, $ParamValues) {
//    $imgWidth = $Props->imgWidth . 'mm'; //The dimensions can be expressed with percentage "%" or one og these units ("mm", "cm", "in" (2.54cm), "pt" (1/72in)).
//    $imgHeight = $Props->imgHeight . 'mm';
//    switch ($Props->DataSource) {
//      case 'RB_Text': if (file_exists($Props->ImagePath)) {
//          $Paragraph->addImage($Props->ImagePath, $imgWidth, $imgHeight);
//        }
//        break;
//      case 'RB_SQL': {
//          $DataField = $Props->DataField;
//          $md5FilePath = Get_md5FilePath($ReportQueryRow->$DataField);
//          $serverFileName = $md5FilePath . $ReportQueryRow->$DataField;
//          if (file_exists($serverFileName)) {
//            $Paragraph->addImage($serverFileName, $imgWidth, $imgHeight);
//          }
//        }
//        break;
//    }
  }

  function writeTable($pdf, $Props, $ReportQueryRow, $ParamValues) {
    global $style_font;

    $pdf->Ln();

    $sql = $Props->SQL;
    foreach ($ParamValues as $key => $value) {
      $sql = str_replace(':' . $key . ':', $value, $sql);
    }
    foreach ($ReportQueryRow as $key => $value) {
      $sql = str_replace(':' . $key . ':', $value, $sql);
    }

    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if (isset($s_err) && ($s_err != '')) {
      if ($Props->ShowIfEmptyData != true) { //РµСЃР»Рё РЅРµ СѓРєР°Р·Р°РЅРѕ РІС‹РІРѕРґРёС‚СЊ С‚Р°Р±Р»РёС†Сѓ СЃ РїСѓСЃС‚С‹Рј РЅР°Р±РѕСЂРѕРј С‚Рѕ РІС‹С…РѕР¶Сѓ
        return false;
      } else {
//РµСЃР»Рё РѕР±СЉРµРєС‚ СЂРµР·СѓР»СЊС‚Р°С‚Р° Р·Р°РїСЂРѕСЃР° РЅРµ СЃРѕР·РґР°РЅ С‚Рѕ СЃРѕР·РґР°СЋ РїСѓСЃС‚РѕР№ С‡С‚РѕР± РґР°Р»СЊС€Рµ РЅРµР±С‹Р»Рѕ РѕР±СЂР°С‰РµРЅРёР№ Рє РїСѓСЃС‚РѕС‚Рµ РІ С„СѓРЅРєС†РёСЏС…
        if ($Props->ShowError = true) {
          $ErrMessage = my_escape_string('РћС€РёР±РєР° РІС‹РїРѕР»РЅРµРЅРёСЏ Р·Р°РїСЂРѕСЃР° ' . PHP_EOL . $sql . PHP_EOL . $s_err . PHP_EOL);
          writeErrorString($ErrMessage);
        }
      }
    } else {
      if (!(kometa_num_rows($res) > 0)) {
        if ($Props->ShowIfEmptyData != true) { //РµСЃР»Рё РЅРµ СѓРєР°Р·Р°РЅРѕ РІС‹РІРѕРґРёС‚СЊ С‚Р°Р±Р»РёС†Сѓ СЃ РїСѓСЃС‚С‹Рј РЅР°Р±РѕСЂРѕРј С‚Рѕ РІС‹С…РѕР¶Сѓ
          return false;
        } else {
// РЅР°РґРѕ Р»Рё РїСЂРѕРІРµСЂСЏС‚СЊ Рё СѓРІРµРґРѕРјР»СЏС‚СЊ С‡С‚Рѕ Р·Р°РїСЂРѕСЃ С‡С‚Рѕ С‚Рѕ РІРµСЂРЅСѓР»?
          if ($Props->ShowEmptyMessage == true) {
            $ErrMessage = my_escape_string('Р—Р°РїСЂРѕСЃ РІРµСЂРЅСѓР» РїСѓСЃС‚РѕР№ РЅР°Р±РѕСЂ РґР°РЅРЅС‹С…' . PHP_EOL);
            writeErrorString($ErrMessage);
          }
        }
      }
    }

    $TabelWidth = $Props->TabelWidth;

    if ($Props->ShowTableHeader == true) { //надо выводить заголовок
      // определяем не распределенную ширину и количество колонок на которое это надо распределить
      $w = $TabelWidth;
      $cnt = 0;
      foreach ($Props->children as $key => $value) {
        if (!isset($value->HeaderStyle) || ($value->HeaderStyle == ''))
          $value->HeaderStyle = 'default';

        if (!isset($value->WidthColumn) || ($value->WidthColumn == 0) || ($value->WidthColumn == ''))
          $cnt++;
        else
          $w = $w - $value->WidthColumn;

        if (!isset($value->LabelColumn))
          $value->LabelColumn = '';
      }
      if ($w < 0) {
        // общаяя сумма ширин кролонок больше ширины таблицы
        foreach ($Props->children as $key => $value) {
          $value->WidthColumn = $TabelWidth / count($Props->children);
        }
      } else {
        foreach ($Props->children as $key => $value)
          if (!isset($value->WidthColumn) || ($value->WidthColumn == 0) || ($value->WidthColumn == '')) {
            $value->WidthColumn = $w / $cnt;
          }
      }
// определяем высоту ячейки
      $h = 0;
      foreach ($Props->children as $key => $value) {
          $pdf->SetFontSize($style_font[$value->HeaderStyle]->FontSize);
        $h = max($h, $pdf->NbLines($value->WidthColumn, iconv('utf-8', 'windows-1251', $value->LabelColumn)) * ($style_font[$value->HeaderStyle]->FontSize * 1.5 * 0.0352777778));
      }
      //Issue a page break first if needed
      $pdf->CheckPageBreak($h);
      foreach ($Props->children as $key => $value) {

        $w = $value->WidthColumn;
        $a = $style_font[$value->HeaderStyle]->TextAlignment;
        //Save the current position
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        //Draw the border

        $fillcolor = $style_font[$value->HeaderStyle]->BackgroundColor;
        $tab = explode(",", $fillcolor);

        $pdf->SetFillColor($tab[0], $tab[1], $tab[2]);
        $fillcolor = $style_font[$value->HeaderStyle]->BackgroundColor;

        $tab = explode(",", "0,0,0");
        $pdf->SetDrawColor($tab[0], $tab[1], $tab[2]);

        $pdf->Rect($x, $y, $w, $h, 'F');

        $tab = explode(",", $style_font[$value->HeaderStyle]->FontColor);
        $pdf->SetTextColor($tab[0], $tab[1], $tab[2]);
        $pdf->MultiCell($w, $style_font[$value->HeaderStyle]->FontSize * 1.5 * 0.0352777778, iconv('utf-8', 'windows-1251', $value->LabelColumn), 0, $a, true);
        //Put the position to the right of the cell
        if ($Props->ShowTableBorder)
          $pdf->Rect($x, $y, $w, $h, 'D');
        $pdf->SetXY($x + $w, $y);
      }
    }
    $pdf->Ln($h);
    if (kometa_num_rows($res) > 0) {
      while ($row = kometa_fetch_object($res)) {
// определяем высоту ячейки
        foreach ($Props->children as $key => $value) {
          if (isset($value->StyleFieldColumn)) {
            $fld = $value->StyleFieldColumn;
            $TextStyle = $row->$fld;
          } else
            $TextStyle = 'default';
          if (isset($value->DataFieldColumn)) {
            $fld = $value->DataFieldColumn;
            $TextData = iconv('utf-8', 'windows-1251', $row->$fld);
          } else
            $TextData = '';
        }
        $h = 0;
        foreach ($Props->children as $key => $value) {
          if (isset($value->StyleFieldColumn)) {
            $fld = $value->StyleFieldColumn;
            $TextStyle = $row->$fld;
          } else
            $TextStyle = 'default';
          if (isset($value->DataFieldColumn)) {
            $fld = $value->DataFieldColumn;
            $TextData = iconv('utf-8', 'windows-1251', $row->$fld);
          } else
            $TextData = '';
          $pdf->SetFontSize($style_font[$TextStyle]->FontSize);
          $nl = $pdf->NbLines($value->WidthColumn, $TextData);
          $h = max($h, $nl * ($style_font[$TextStyle]->FontSize * 1.5 * 0.0352777778));
        }

        //Issue a page break first if needed
        $pdf->CheckPageBreak($h);
        foreach ($Props->children as $key => $value) {
          if (isset($value->StyleFieldColumn)) {
            $fld = $value->StyleFieldColumn;
            $TextStyle = $row->$fld;
          } else
            $TextStyle = 'default';
          if (isset($value->DataFieldColumn)) {
            $fld = $value->DataFieldColumn;
            $TextData = iconv('utf-8', 'windows-1251', $row->$fld);
          } else
            $TextData = '';
          $w = $value->WidthColumn;
          $a = $style_font[$TextStyle]->TextAlignment;
          //Save the current position
          $x = $pdf->GetX();
          $y = $pdf->GetY();
          //Draw the border
          $fillcolor = $style_font[$TextStyle]->BackgroundColor;
          $tab = explode(",", $fillcolor);

          $pdf->SetFillColor($tab[0], $tab[1], $tab[2]);
          $fillcolor = $style_font[$TextStyle]->BackgroundColor;

          $tab = explode(",", "0,0,0");
          $pdf->SetDrawColor($tab[0], $tab[1], $tab[2]);

          $pdf->Rect($x, $y, $w, $h, 'F');
          //Print the text

          $tab = explode(",", $style_font[$TextStyle]->FontColor);
          $pdf->SetTextColor($tab[0], $tab[1], $tab[2]);
          $pdf->MultiCell($w, $style_font[$TextStyle]->FontSize * 1.5 * 0.0352777778, $TextData, 0, $a, true);
          //Put the position to the right of the cell
          if ($Props->ShowTableBorder)
            $pdf->Rect($x, $y, $w, $h, 'D');
          $pdf->SetXY($x + $w, $y);
        }
        $pdf->Ln($h);
      }
    }
  }

  function writeList($pdf, $Props, $ReportQueryRow, $ParamValues, $TextStylesArray) { //СЃС‚СЂРѕРёС‚ СЌР»РµРјРµРЅС‚ РѕС‚С‡РµС‚Р° СЃРїРёСЃРѕРє
    global $style_font;
    $pdf->Ln();
    $StyleName = $Props->TextStyle;
    switch ($Props->DataSource) {
      case 'RB_Text': {
          $ListItems = explode("\n", $Props->ListValues);
        }
        break;
      case 'RB_SQL': {
//СЃС„РѕСЂРјРёСЂРѕРІР°С‚СЊ Р·Р°РїСЂРѕСЃ $PDF_Template->SQL + $PDF_Template->SQLConditions
          if (isset($Props->SQLConditions) && ($Props->SQLConditions != '')) {
// Р·Р°РјРµРЅРёС‚СЊ РїР°СЂР°РјРµС‚СЂС‹ РїРѕ РєРѕРґСѓ РЅР° Р·РЅР°С‡РµРЅРёРµ РІ  $PDF_Template->SQLConditions
            foreach ($ParamValues as $key => $value) {
              $Props->SQLConditions = str_replace(':' . $key . ':', $value, $Props->SQLConditions);
            }
            foreach ($ReportQueryRow as $key => $value) {
              $Props->SQLConditions = str_replace(':' . $key . ':', $value, $Props->SQLConditions);
            }
            $sql = $Props->SQL . ' where 1=1 and ' . $Props->SQLConditions;
          } else {
            $sql = $Props->SQL;
          }

          $res = kometa_query($sql);
          $s_err = kometa_last_error();
          if (isset($s_err) && ($s_err != '')) {
            if ($Props->ShowError == true) {
              $ErrMessage = my_escape_string('РћС€РёР±РєР° РІС‹РїРѕР»РЅРµРЅРёСЏ Р·Р°РїСЂРѕСЃР° ' . PHP_EOL . $sql . PHP_EOL . $s_err . PHP_EOL);
              writeErrorString($ErrMessage);
            }
            return false;
          } else {
            if (!(kometa_num_rows($res) > 0)) {
// РЅР°РґРѕ Р»Рё РїСЂРѕРІРµСЂСЏС‚СЊ Рё СѓРІРµРґРѕРјР»СЏС‚СЊ С‡С‚Рѕ Р·Р°РїСЂРѕСЃ С‡С‚Рѕ С‚Рѕ РІРµСЂРЅСѓР»?
              if ($Props->ShowEmptyMessage == true) {
                $ErrMessage = my_escape_string('Р—Р°РїСЂРѕСЃ РІРµСЂРЅСѓР» РїСѓСЃС‚РѕР№ РЅР°Р±РѕСЂ РґР°РЅРЅС‹С…' . PHP_EOL);
                writeErrorString($ErrMessage);
              }
              return false;
            }
          }
          if (kometa_num_rows($res) > 0) {
            $listField = $Props->DBLinkText;
            $ListItems = array();
            while ($row = kometa_fetch_object($res)) {
              array_push($ListItems, $row->$listField);
            }
          }
        }
        break;
    }
    $blt = '-->'; //chr(149);

    $i1 = $pdf->GetStringWidth($blt);
    $i2 = $pdf->GetcMargin();
    $i3 = $i2 * 2;
    $blt_width = $i1 + $i3;

    //Save x
    $bak_x = $pdf->GetX();

    $h = $style_font[$StyleName]->FontSize * 1.5 * 0.0352777778;
    //Output bullet
    //Output text
    $fillcolor = $style_font[$StyleName]->BackgroundColor;
    $tab = explode(",", $fillcolor);

    $pdf->SetFillColor($tab[0], $tab[1], $tab[2]);

    $tab = explode(",", $style_font[$StyleName]->FontColor);
    $pdf->SetTextColor($tab[0], $tab[1], $tab[2]);

    $w = $pdf->GetPageWidth();

    foreach ($ListItems as $key => $txt) {
      $pdf->Cell($blt_width, $h, iconv('utf-8', 'windows-1251', $blt), 0, 0, '', true);
      $pdf->MultiCell($w - $blt_width, $h, iconv('utf-8', 'windows-1251', $txt), 0, $style_font[$StyleName]->TextAlignment, true);
    }
  }

  function writeSubReport($pdfObject, $reportItem, $row, $ParamValues, &$SubReportItterationArray) {// СЃС‚СЂРѕРёС‚ РІР»РѕР¶РµРЅРЅС‹Р№ РѕС‚С‡РµС‚
//    $b = $SubReportItterationArray[$reportItem->PDF_Template_Code];
//    if (($b != null) || ($b == true)) {//РїСЂРµРґРѕС‚РІСЂР°С‰Р°СЋ СЂРµРєСѓСЂСЃРёСЋ РІР»РѕР¶РµРЅРЅС‹С… РѕС‚С‡РµС‚РѕРІ
//      if ($reportItem->ShowError) {
//        writeErrorString('Р’Р»РѕР¶РµРЅРЅС‹Р№ РѕС‚С‡РµС‚ ' . $reportItem->PDF_Template_Descr . " СѓР¶Рµ Р±С‹Р» СЃС„РѕСЂРјРёСЂРѕРІР°РЅ РІ СЂР°РјРєР°С… РѕСЃРЅРѕРІРЅРѕРіРѕ" . PHP_EOL .
//                'Р”Р»СЏ РїСЂРµРґРѕС‚РІСЂР°С‰РµРЅРёСЏ Р·Р°С†РёРєР»РёРІР°РЅРёСЏ РѕРЅ Р±РѕР»СЊС€Рµ РЅРµ С„РѕСЂРјРёСЂСѓРµС‚СЃСЏ!');
//      }
//      return;
//    } else {
//      $result = LoadTemplate($reportItem->PDF_Template_Code);
//      if ($result->success == true) {
////С‡С‚Рѕ РґРµР»Р°С‚СЊ СЃ РЅР°Р±РѕСЂРѕРј РґР°РЅРЅС‹С… $row СЂРѕРґРёС‚РµР»СЊСЃРєРѕРіРѕ РѕС‚С‡РµС‚Р° РЅР°РґРѕ Р»Рё РµРіРѕ РїРѕРґСЃС‚Р°РІР»СЏС‚СЊ РІРѕ РІР»РѕР¶РµРЅРЅС‹Р№
//        $result = BuildODTReportBody($odtObject, json_decode($result->result), $ParamValues, $SubReportItterationArray, true);
//        if ($result != true) {
//          if ($reportItem->ShowError) {
//            writeErrorString('РћС€РёР±РєР° С„РѕСЂРјРёСЂРѕРІР°РЅРёСЏ РІР»РѕР¶РµРЅРЅРѕРіРѕ РѕС‚С‡РµС‚Р° ' . $reportItem->PDF_Template_Descr . " СЃ СЃРѕРѕР±С‰РµРЅРёРµРј " . $result);
//          }
//        }
//      } else {
//        if ($reportItem->ShowError) {
//          writeErrorString('РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё РІР»РѕР¶РµРЅРЅРѕРіРѕ РѕС‚С‡РµС‚Р° ' . $reportItem->PDF_Template_Descr . " СЃ СЃРѕРѕР±С‰РµРЅРёРµРј " . $result->msg);
//        }
//      }
//    }
  }

  function writeErrorString($Text) { //СЃРѕР·РґР°РµС‚ Р°Р±Р·Р°С† СЃ С„РѕСЂРјР°С‚РёСЂРѕРІР°РЅРЅС‹Рј С‚РµРєСЃС‚РѕРІС‹Рј СЃРѕРѕР±С‰РµРЅРёРµРј
//    $textStyle = new TextStyle();
//    $textStyle->setTextBackgroundColor('#FF0000');
//    $pStyle = new ParagraphStyle('myPStyle');
//    $pStyle->setTextAlign(StyleConstants::CENTER);
//
//    $p = new Paragraph($pStyle);
//    $p->addText($Text, $textStyle);
  }

  if ($ThisIsSubReport == true) {//РµСЃР»Рё СЌС‚Рѕ РїРѕРґРѕС‚С‡РµС‚ С‚Рѕ РїР°СЂР°РјРµС‚СЂС‹ РїРµСЂРµРґР°РЅС‹ РёР· СЂРѕРґРёС‚РµР»СЏ Рё РѕС„РѕСЂРјР»РµРЅРёРµ СЃРІРѕР№СЃС‚РІ РѕС‚С‡РµС‚Р° РЅРµ РїСЂРѕРёР·РІРѕРґРёС‚СЃСЏ
    $ParamValues = $ParamValuesArray;
  } else {
// Р·Р°РіСЂСѓР¶Р°РµС‚СЃСЏ С„СѓРЅРєС†РёРµР№ LoadCellStylesObject  РёР· С„Р°Р№Р»Р° ODTReportConstants::CellStylesFileName
    LoadCellStylesObject($pdf, ReportConstants::CellStylesFileName); // Р·Р°РіСЂСѓР¶Р°РµС‚СЃСЏ РѕР±СЉРµРєС‚ СЃРѕРґРµСЂР¶Р°С‰РёР№ РѕРїСЂРµРґРµР»РµРЅРёСЏ СЃС‚РёР»РµР№ СЏС‡РµРµРє С‚Р°Р±Р»РёС†С‹
//Р·Р°РґР°С‚СЊ С„РѕСЂРјР°С‚ СЃС‚СЂР°РЅРёС† РёР· $PDF_Template
//--    $pageStyle = new PageStyle('myPageStyle');
//    if ($PDF_Template->PageStartNumber != null)
    if ($PDF_Template->ShowPAGE_NUMBER == true) {
      if ($PDF_Template->FirstPageNumber == true) {
//--        $pageStyle->setFooterContent(StyleConstants::PAGE_NUMBER);
      } else {
//--        $pageStyle->setPageNumber(StyleConstants::FOOTER, StyleConstants::PAGENUMDISPLAY_PREVIOUS, 1);
      }
    }
//    $pageStyle->setVerticalMargin($MarginTop, $MarginBottom);

    $ParamValues = array(); //Р·РґРµСЃСЊ Р±СѓРґСѓС‚ Р»РµР¶Р°С‚СЊ Р·РЅР°С‡РµРЅРёСЏ РґР»СЏ РїРѕРґСЃС‚Р°РЅРѕРІРєРё РІ Р·Р°РїСЂРѕСЃС‹
//СЌС‚РѕС‚ РјР°СЃСЃРёРІ СЃРІСЏР·Р°РЅ РїРѕ РєР»СЋС‡Р°Рј СЃ РЅР°Р±РѕСЂРѕРј РЅР°СЃС‚СЂРѕРµРЅРЅС‹С… РїР°СЂР°РјРµС‚СЂРѕРІ, РЅРѕ РјРѕР¶РµС‚ Р±С‹С‚СЊ РґРїРѕР»РЅРµРЅ РЅРµСЃСѓС‰РµСЃС‚РІСѓСЋС‰РёРјРё
//РїР°СЂР°РјРµС‚СЂР°РјРё РёР· СЃРїРёСЃРєР° РІС…РѕРґРЅС‹С… РїР°СЂР°РјРµС‚СЂРѕРІ
    foreach ($PDF_Template->ReportParams as $reportParam) { //Р·РЅР°С‡РµРЅРёСЏ РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ Р±РµСЂСѓ РєР°Рє С‚РµРєСѓС‰РёРµ
      $ParamValues[$reportParam->ParamCode] = $reportParam->ParamDefaultValue;
    }
    foreach ($ParamValuesArray as $key => $value) { //Р·РЅР°С‡РµРЅРёСЏ РІС…РѕРґРЅС‹С… РїР°СЂР°РјРµС‚СЂРѕРІ РїРѕРґСЃС‚Р°РІР»СЏСЋ РєР°Рє С‚РµРєСѓС‰РёРµ
      $ParamValues[$key] = $value;
    }
  }

//РѕС‚С„РѕСЂРјР°С‚РёСЂРѕРІР°С‚СЊ Р·РЅР°С‡РµРЅРёСЏ РїР°СЂР°РјРµС‚СЂРѕРІ СЃРѕРѕР±СЂР°Р·РЅРѕ С‚РёРїР°Рј -  РІРµР·РґРµ РїРѕРґСЃС‚Р°РІР»СЏСЋ РєР°Рє СЃС‚СЂРѕРєСѓ
//СЃС„РѕСЂРјРёСЂРѕРІР°С‚СЊ Р·Р°РїСЂРѕСЃ $PDF_Template->SQL + $PDF_Template->SQLConditions
  if (isset($PDF_Template->SQLConditions) && ($PDF_Template->SQLConditions != '')) {
// Р·Р°РјРµРЅРёС‚СЊ РїР°СЂР°РјРµС‚СЂС‹ РїРѕ РєРѕРґСѓ РЅР° Р·РЅР°С‡РµРЅРёРµ РІ  $PDF_Template->SQLConditions
    foreach ($ParamValues as $key => $value) {
      $PDF_Template->SQLConditions = str_replace(':' . $key . ':', $value, $PDF_Template->SQLConditions);
    }
    $sql = $PDF_Template->SQL . ' where 1=1 and ' . $PDF_Template->SQLConditions;
  } else {
    $sql = $PDF_Template->SQL;
  }
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if (isset($s_err) && ($s_err != '')) {
    $row = array();
//РµСЃР»Рё РѕР±СЉРµРєС‚ СЂРµР·СѓР»СЊС‚Р°С‚Р° Р·Р°РїСЂРѕСЃР° РЅРµ СЃРѕР·РґР°РЅ С‚Рѕ СЃРѕР·РґР°СЋ РїСѓСЃС‚РѕР№ С‡С‚РѕР± РґР°Р»СЊС€Рµ РЅРµР±С‹Р»Рѕ РѕР±СЂР°С‰РµРЅРёР№ Рє РїСѓСЃС‚РѕС‚Рµ РІ С„СѓРЅРєС†РёСЏС…
    if ($PDF_Template->ShowError == true) {
      $ErrMessage = my_escape_string('РћС€РёР±РєР° РІС‹РїРѕР»РЅРµРЅРёСЏ Р·Р°РїСЂРѕСЃР° ' . PHP_EOL . $sql . PHP_EOL . $s_err . PHP_EOL);
      writeErrorString($ErrMessage);
    }
  } else {
    if (kometa_num_rows($res) > 0) {
      $row = kometa_fetch_object($res);
    } else {
      $row = array();
// РЅР°РґРѕ Р»Рё РїСЂРѕРІРµСЂСЏС‚СЊ Рё СѓРІРµРґРѕРјР»СЏС‚СЊ С‡С‚Рѕ Р·Р°РїСЂРѕСЃ С‡С‚Рѕ С‚Рѕ РІРµСЂРЅСѓР»?
      if ($PDF_Template->ShowEmptyMessage == true) {
        $ErrMessage = my_escape_string('Р—Р°РїСЂРѕСЃ РІРµСЂРЅСѓР» РїСѓСЃС‚РѕР№ РЅР°Р±РѕСЂ РґР°РЅРЅС‹С…' . PHP_EOL);
        writeErrorString($ErrMessage);
      }
    }
  }

  if (isset($PDF_Template->children))
    foreach ($PDF_Template->children as $reportItem) {
      switch ($reportItem->ItemType) {
        case 'paragraph': writeParagraph($pdf, $reportItem, $row, $ParamValues, $CellStylesArray);
          break;
        case 'table':
          writeTable($pdf, $reportItem, $row, $ParamValues);
          break;
        case 'list': writeList($pdf, $reportItem, $row, $ParamValues, $CellStylesArray);
          break;
//        case 'embedded_report': writeSubReport($odtObject, $reportItem, $row, $ParamValues, $SubReportItterationArray);
//          break;
      }
    }
  return true;
}
