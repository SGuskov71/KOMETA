<?php

require __DIR__ . '/FileUploadBackEndFunction.php';

if (!isset($ID_User)) {
  // соединение посрочено необходимо переподключение
  $result = new JSON_Result(false, 'Соединение разоравано. Требуется переподключение.', 're_connect');
  echo json_encode($result);
  //exit();
} else {
//переадресация комманд на функции
  if ($_POST['DownloadFileByMd5Name']) {
    $result = DownloadFileByMd5Name($_POST['FileMd5Name']);
    echo json_encode($result);
  } else if ($_POST['DownloadFile']) {
    DownloadFile($_POST['serverFileName'], $_POST['filename'], $_POST['filetype']);
  }
}

function DownloadFile($serverFileName, $filename, $filetype) {
  $filesize = filesize($serverFileName);
  if (!isset($filetype)) {
    $filetype = GetFile_MIME_Type($serverFileName);
  }
  header('Content-type: ' . $filetype);
  header('Content-Length: ' . $filesize);
  header("Content-Disposition: attachment; filename='$filename'");

  readfile($serverFileName);
}
