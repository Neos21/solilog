# Solilog

PHP 製のオレオレ・マイクロ・ブログ。[Neo's PHP Micro Blog](https://github.com/Neos21/neos-php-micro-blog) の焼き直し。

__[Enter Demo Site](https://neos21-oci.cf/solilog)__

- 特徴
    - パスワード認証により自分だけが投稿できる、オリジナルの簡易マイクロ・ブログ
    - 投稿は月ごとに生成するテキストファイルに保存する
- [Neo's PHP Micro Blog](https://github.com/Neos21/neos-php-micro-blog) との違い
    - 単一の PHP ファイルで作成せず、フロントエンドの HTML ファイルと、機能・操作ごとに API 分割した PHP ファイルで構成した → フロントエンドのデザイン変更を容易にした
    - 過去の投稿を表示する時や投稿・削除処理の際に非同期通信で再描画することで、画面遷移しないようにした
    - 投稿ファイルは TSV ではなく JSON 形式で保存するようにした
- 構成
    - `solilog.html` : フロントエンド。各 API にアクセスする。API へのアクセスパスを `const` で定義してある
    - `solilog-config.json` : 各 API が参照する設定ファイル。パスワード、投稿ファイルの保管先などを指定する
    - `solilog-list.json` : 投稿ファイルがある年月の一覧を返す API。各 API 用の PHP ファイルは冒頭で `solilog-config.json` へのパスを定義してある
    - `solilog-show.json` : 指定の年月の投稿ファイルを返す API
    - `solilog-admin-post.json` : 投稿処理を行う管理者用の API
    - `solilog-admin-remove.json` : 指定の投稿を削除する管理者用の API
- 導入
    - PHP が動作するサーバに上のファイル群を配置する
    - `solilog-config.json` の `credential` を任意の文字列に変更する (管理者用パスワード)
    - `solilog-config.json` の `postDirectoryPath` は、投稿ファイル (デフォルトだと `solilog-2020-10.json` のようなファイル名) が配置されるディレクトリを指定する
    - `http://example.com/solilog.html` にアクセスすれば表示専用の通常モードになる
    - `http://example.com/solilog.html?credential=【設定した管理者用パスワード】` にアクセスすれば投稿フォームが表示され、管理者が投稿・削除を行える管理者モードになる


## Links

- [Neo's World](https://neos21.net/)
