<?php

function GetCRC_Str($SourceUploadFile, $SchemaUpload = null) {
    $result = null;
    if (file_exists($SourceUploadFile)) {
        $s2 = md5_file($SourceUploadFile);
    } else {
        return $result;
    }
    if (isset($SchemaUpload)) {
        $s1 = md5($SchemaUpload);
    } else {
        $s1 = '';
    }
    $s = $s1 . $s2;
    return $s;
}

function GetCRC_FileName($SourceUploadFile) {
    $ext = pathinfo($SourceUploadFile, PATHINFO_EXTENSION);
    if (isset($ext)) {
        $SourceUploadCRCFile = str_replace('.' . $ext, '.CRC', $SourceUploadFile);
    } else {
        $SourceUploadCRCFile = $SourceUploadCRCFile . '.CRC';
    }
    return $SourceUploadCRCFile;
}

function CreateUploadCRCFile($SourceUploadFile, $SchemaUpload = null) {
    $result = False;
    $s = GetCRC_Str($SourceUploadFile, $SchemaUpload);
    if (!isset($s)) {
        return $result;
    }
    $SourceUploadCRCFile = GetCRC_FileName($SourceUploadFile);
    if (file_put_contents($SourceUploadCRCFile, $s) > 0) {
        $result = True;
    }
    return $result;
}

function CheckUploadCRCFile($SourceUploadFile, $SchemaUpload = null) {
    $result = False;
    $CRCStrCalc = GetCRC_Str($SourceUploadFile, $SchemaUpload);
    if (!isset($CRCStrCalc)) {
        return $result;
    }
    $SourceUploadCRCFile = GetCRC_FileName($SourceUploadFile);
    $CRCStrRead = file_get_contents($SourceUploadCRCFile);
    if ($CRCStrRead==FALSE) {
        return $result;
    } else {
        if ($CRCStrCalc == $CRCStrRead) {
            $result = True;
        }
    }
    return $result;
}

?>
