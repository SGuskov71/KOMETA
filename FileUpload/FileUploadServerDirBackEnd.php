<?php

require __DIR__ . '/FileUploadBackEndFunction.php';

if (!isset($ID_User)) {
// соединение посрочено необходимо переподключение
  $result = new JSON_Result(false, 'Соединение разоравано. Требуется переподключение.', 're_connect');
  echo json_encode($result);
  exit();
}

if (!isset($_SERVER['HTTP_X_FILE_NAME'])) {
  _error('Unknown file name');
}
$fileName = $_SERVER['HTTP_X_FILE_NAME'];
if (isset($_SERVER['HTTP_X_FILENAME_ENCODER']) && 'base64' == $_SERVER['HTTP_X_FILENAME_ENCODER']) {
  $fileName = base64_decode($fileName);
}
$fileName = htmlspecialchars($fileName);
$size = intval($_SERVER['HTTP_X_FILE_SIZE']);

$ServerDir = $_GET['ServerDir'];
$ServerDir = $_SESSION[$ServerDir];
//проверить директорию на доступность
if (CheckDirPermission($ServerDir) != true) {
  _error('Нет доступа к каталогу загрузки файлов.');
}

$outputFilename = $ServerDir . $fileName;
if (file_exists($outputFilename)) {
  _error('Такой файл уже есть на сервере');
}

$inputStream = fopen('php://input', 'r');
$realSize = 0;
$data = '';

if ($inputStream) {
  $outputStream = fopen($outputFilename, 'w');
  if (!$outputStream) {
    _error('Ошибка создания файла на сервере');
  }

  while (!feof($inputStream)) {
    $bytesWritten = 0;
    $data = fread($inputStream, 1024);

    $bytesWritten = fwrite($outputStream, $data);

    if (false === $bytesWritten) {
      try {
        unlink($outputFilename);
      } catch (Exception $e) {

      }
      _error('Ошибка записи данных в файл');
    }
    $realSize += $bytesWritten;
  }

  fclose($outputStream);
} else {
  _error('Ошибка чтения передаваемого потока');
}

if ($realSize != $size) {
  try {
    unlink($outputFilename);
  } catch (Exception $e) {

  }
  _error('Переданный размер отличается от заявленного в заголовке');
} else {//сохранить файл в указанную директорию
  _response(true, 'Успешно отгружено');
}