<?php

// ======================================================================
// Solilog : 投稿がある年月を配列で返す
// 
// ex. ["2020-01", "2020-02"]
// ======================================================================


// グローバル変数
// ======================================================================

// 設定ファイルのパス
$CONFIG_FILE_PATH = './solilog-config.json';


// メイン処理
// ======================================================================

date_default_timezone_set('Asia/Tokyo');
header('Content-Type: application/json; charset=UTF-8');

// 設定ファイルを読み込む・読み込めなかった場合は NULL が返る・関数内でエラーレスポンスを出力しているので終了する
$config = loadConfig();
if(empty($config)) { exit(); }

// ファイル一覧を取得しレスポンスして終了する
getList($config['postDirectoryPath'], $config['postFileNamePrefix']);
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

/** 投稿を返す */
function getList($postDirectoryPath, $postFileNamePrefix) {
  // ディレクトリ配下の投稿ファイルを取得する (なければ空配列になる)
  $filePaths = glob($postDirectoryPath . '/' . $postFileNamePrefix . '[0-9]*.json');
  
  // 年月部分のみを抽出する
  $list = array_map(function($filePath) {
    preg_match('/[0-9]{4}-[0-9]{2}/', $filePath, $match);
    return $match[0];
  }, $filePaths);
  // NULL などの不正値を取り除く
  $list = array_filter($list, function($filePath) {
    return !isEmpty($filePath);
  });
  // 新しい年月から順に並べる
  rsort($list);
  
  // 配列を返す
  echo json_encode(array('list' => $list));
}

?>
