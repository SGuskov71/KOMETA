<?php

require_once($_SESSION['ProjectRoot'] . "FileUpload/FileUploadBackEndFunction.php");

if (isset($_GET["img_code"])) {
    $ImgName = $_GET["img_code"];
    $md5FilePath = Get_md5FilePath($ImgName);
    $serverFileName = $md5FilePath . $ImgName;
    if (file_exists($serverFileName)) {
        header("Content-type: image/*");
// И передаем сам файл
        echo file_get_contents($serverFileName);
    }
}

