<?php

/**
 * Example processing of raw PUT/POST uploaded files.
 * File metadata may be sent through appropriate HTTP headers:
 *   - file name - the 'X-File-Name' proprietary header
 *   - file size - the standard 'Content-Length' header or the 'X-File-Size' proprietary header
 *   - file type - the standard 'Content-Type' header or the 'X-File-Type' proprietary header
 *
 * Raw data are read from the standard input.
 * The response should be a JSON encoded string with these items:
 *   - success (boolean) - if the upload has been successful
 *   - message (string) - optional message, useful in case of error
 */
require __DIR__ . '/FileUploadBackEndFunction.php';

if (!isset($ID_User)) {
// соединение посрочено необходимо переподключение
  $result = new JSON_Result(false, 'Соединение разоравано. Требуется переподключение.', 're_connect');
  echo json_encode($result);
  exit();
}

// проверяю доступность файловых каталогов
global $upload_dir_root;
if (CheckDirPermission($upload_dir_root) != true) {
  _error('Нет доступа к корню файлового хранилища.');
}

global $Temp_dir;
if (CheckDirPermission($Temp_dir) != true) {
  _error('Нет доступа к временному каталогу загрузки файлов.');
}

/*
 * You should check these values for XSS or SQL injection.
 */
if (!isset($_SERVER['HTTP_X_FILE_NAME'])) {
  _error('Unknown file name');
}
$fileName = $_SERVER['HTTP_X_FILE_NAME'];
if (isset($_SERVER['HTTP_X_FILENAME_ENCODER']) && 'base64' == $_SERVER['HTTP_X_FILENAME_ENCODER']) {
  $fileName = base64_decode($fileName);
}
$fileName = htmlspecialchars($fileName);
$fileExt = GetFileExt($fileName);

$mimeType = htmlspecialchars($_SERVER['HTTP_X_FILE_TYPE']);
$size = intval($_SERVER['HTTP_X_FILE_SIZE']);

$inputStream = fopen('php://input', 'r');
$outputFilename = $Temp_dir . md5(rand()) . ".tmp";
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
      _error('Ошибка записи данных в файл');
    }
    $realSize += $bytesWritten;
  }

  fclose($outputStream);
} else {
  _error('Ошибка чтения передаваемого потока');
}

if ($realSize != $size) {
  _error('Переданный размер отличается от заявленного в заголовке');
} else {
  $md5FileName = md5_file($outputFilename);
  $md5FileName .= '.' . $fileExt;
//проверить в БД на наличие такого файла
  $needSave = false;
  $sql = "SELECT * FROM mbf_store  ";
  $sql .=" where code='$md5FileName' and id_user=" . $ID_User;
  $res = kometa_query($sql);
  $s_err = kometa_last_error();
  if ($s_err != '') {
    $result = new JSON_Result(false, $s_err, NULL);
    return $result;
  }
  if (kometa_num_rows($res) == 0) {
    $result = false;
    $needSave = true;
    //файл не найден в БД для этого пользователя проверяем для всех
    $sql = "SELECT * FROM mbf_store  ";
    $sql .=" where code='$md5FileName' ";
    $res = kometa_query($sql);
    if (kometa_num_rows($res) == 0) {//надо скопировать и зарегестрировать в БД
      $result = false;
      $needSave = true;
    } else { //файл уже есть надо только зарегестрировать для текущего пользователя
      $result = true;
      $needSave = false;
      try {
        unlink($outputFilename);
      } catch (Exception $e) {

      }
    }
  } else {//файл найден в БД для этого пользователя
    $result = false;
    $needSave = false;
    try {
      unlink($outputFilename);
    } catch (Exception $e) {

    }
    _error('Файл уже зарегестрирован на сервере');
  }

  if ($needSave) {
    $md5FilePath = Get_md5FilePath($md5FileName);

    //проверить наличие директории
    if (!file_exists($md5FilePath)) {
      $oldumask = umask(0);
      $result = mkdir($md5FilePath, 0777, true);
      umask($oldumask);
      if (!$result) {
        _error('Не удалось создать директорию... ' . $md5FilePath);
      }
    }
    $result = rename($outputFilename, $md5FilePath . $md5FileName);
  }
  if ($result) {
    global $ID_User;
    $mime_type_id = Get_MIME_Type_id($mimeType, $fileExt);
//зарегистрировать в БД
    $sql = "INSERT INTO mbf_store(code, filename, id_user, id_type, size_file, short_name, full_name)"
    . "VALUES ("
    . "'" . $md5FileName . "', "
    . "'" . $fileName . "', "
    . $ID_User . ", "
    . $mime_type_id . ", "
    . $size .
    ", '', '')";
    $res = kometa_query($sql);
    $s_err = kometa_last_error();
    if ($s_err != '') {
      try {
        unlink($md5FilePath . $md5FileName);
      } catch (Exception $e) {

      }
      _error($s_err);
    } else {
      _response(true, 'Успешно добавлено');
    }
  } else {
    try {
      unlink($outputFilename);
    } catch (Exception $e) {

    }
    _error('Файл не удалось переместить на сервере');
  }
}

//_log(sprintf("[raw] Uploaded %s, %s, %d byte(s)", $fileName, $mimeType, $realSize));
?>