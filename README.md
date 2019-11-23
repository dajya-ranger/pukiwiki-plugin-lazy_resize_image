# pukiwiki-plugin-lazy_resize_image
PukiWiki用画像遅延読み込み・画像リサイズプラグイン

- 暫定公開版です（[PukiWiki1.5.2](https://pukiwiki.osdn.jp/?PukiWiki/Download/1.5.2)で動作確認済）
- 本プラグインは次のファイルで構成されています
	- imageフォルダ：[PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)の当該フォルダにファイル内容をコピーする
		- spacer.png：imgタグ用ダミー画像（画像遅延読み込みのためのスペーサー画像）
	- resize_cacheフォルダ：[PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)のルートにこのフォルダを作成する（resize_imageプラグイン用のキャッシュフォルダで中身はない）
	- skinフォルダ：[PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)の当該フォルダにファイル内容をコピーする
		- lazysizes.min.js：画像遅延読み込みを実現するJavaScript（GitHub [aFarKas/lazysizes](https://github.com/aFarkas/lazysizes)）
	- ref.inc.php：[PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)標準refプラグインに画像遅延読み込みと画像リサイズ処理を組み込んで改造したプラグイン
	- resize_image.inc.php：画像リサイズプラグイン
- 本プラグイン一式を導入することで、次の効果が得られます
	- 添付画像を変更することなくページ表示上の画像を適度にリサイズ（画像・容量）することで、複数画像があるページの通信トラフィックを軽減できる
	- [PukiWiki](https://ja.wikipedia.org/wiki/PukiWiki)標準refプラグインでの画像リンクは今まで通りリサイズされていない元の添付画像となる
	- 複数画像があるページの画像遅延読み込みが可能（ページ表示の体感速度アップ）
	- resize_imageプラグインで作成したリサイズ画像（デフォルトでJPEG品質値70）はresize_cashフォルダに自動でキャッシュされる
	- プラグイン一式の導入のみで既存ページの修正は一切なし（refプラグインが画像遅延読み込み用imgタグを自動で生成し、resize_imageプラグインを自動で呼び出す仕組みのため）
- resize_imageプラグインを単体のインライン・プラグインとして利用することも可能です
- refプラグインの設定を変更することで、resize_imageプラグインを利用せずに画像遅延読み込みだけを利用することも可能です
- 設置と設定に関しては自サイトの記事「[PukiWikiに画像遅延読込・画像リサイズプラグインを導入して画像の読み込みを高速化する！](https://dajya-ranger.com/pukiwiki/lazy-resize-image/)」を参照して下さい
