<?php

// ======================================================================
// Solilog : 投稿を検索する
// ======================================================================


// グローバル変数
// ======================================================================

// 設定ファイルのパス
$CONFIG_FILE_PATH = '../solilog-config.json';


// メイン処理
// ======================================================================

date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');

// 設定ファイルを読み込む・読み込めなかった場合は NULL が返る・関数内でエラーレスポンスを出力しているので終了する
$config = loadConfig();
if(empty($config)) { exit(); }

// 検索しレスポンスして終了する
search($config['postDirectoryPath'], $config['postFileNamePrefix']);
exit();


// 関数
// ================================================================================

/** 設定ファイルを読み込む */
function loadConfig() {
  if(!file_exists($GLOBALS['CONFIG_FILE_PATH'])) {
    responseError('Config File Does Not Exist');
    return NULL;
  }
  
  // 空ファイル・不正な形式などの場合は以下の項目チェックでエラーとなる
  $configFile = file_get_contents($GLOBALS['CONFIG_FILE_PATH']);
  $configObject = json_decode($configFile, true);
  
  // 必要な項目がなければエラーとする
  if(isEmpty($configObject['postDirectoryPath']) || isEmpty($configObject['postFileNamePrefix'])) {
    responseError('Invalid Config File');
    return NULL;
  }
  
  return $configObject;
}

/** エラーレスポンスを返す */
function responseError($errorMessage) {
  echo json_encode(array('error' => $errorMessage));
}

/** 引数が空値かどうか判定する */
function isEmpty($value) {
  return !isset($value) || trim($value) === '';
}

/** 引数が配列として要素を持っているかどうか確認する */
function isEmptyArray($array) {
  return empty($array) || empty($array[0]);
}

/** POST か GET から指定のパラメータを取得する (POST 優先・trim 済の値を返す) */
function getParameter($parameterName) {
  // JSON 文字列を POST された場合
  $jsonValues = json_decode(file_get_contents('php://input'), true);
  $jsonValue = trim($jsonValues[$parameterName]);
  if(!isEmpty($jsonValue)) { return $jsonValue; }
  
  if(!empty($_POST[$parameterName])) {
    $postValue = trim($_POST[$parameterName]);
    if(!isEmpty($postValue)) { return $postValue; }
  }
  
  if(!empty($_GET[$parameterName])) {
    $getValue = trim($_GET[$parameterName]);
    if(!isEmpty($getValue)) { return $getValue; }
  }
  
  return '';
}

/** 検索する */
function search($postDirectoryPath, $postFileNamePrefix) {
  $q = getParameter('q');
  
  if(isEmpty($q)) {
    echo json_encode(array('error' => 'Please Input Query'));
    return;
  }
  
  exec("grep -i -B 1 '$q' $postDirectoryPath/$postFileNamePrefix* | sed 's@$postDirectoryPath/$postFileNamePrefix@@'", $cmdOutput);
  echo json_encode(array(
    'query' => $q,
    'results' => $cmdOutput
  ));
}

?>
