<?php

/**
 * resize_image.inc.php
 *
 * 画像リサイズプラグイン
 *
 * @author		オヤジ戦隊ダジャレンジャー <red@dajya-ranger.com>
 * @copyright	Copyright © 2019, dajya-ranger.com
 * @link		https://dajya-ranger.com/pukiwiki/lazy-resize-image-plugin/
 * @example		&resize_image(画像ファイル名,[幅,[高さ,[画像品質,[アスペクト比維持＝TRUE]]]]);
 * @example		@linkの内容を参照
 * @license		Apache License 2.0
 * @version		0.2.0
 * @since 		0.2.0 2019/11/13 暫定初公開（独自拡張）
 * @since 		1.21  2007/11/21 resizeimage.inc.php ioio氏（元プログラム）
 *
 */

// 画像幅初期値（未設定でオリジナルサイズ）
define('PLUGIN_RESIZE_IMAGE_WIDTH', '');
// 画像高さ初期値（未設定でオリジナルサイズ）
define('PLUGIN_RESIZE_IMAGE_HEIGHT', '');
// 画像品質初期値（JPEG 70％）
define('PLUGIN_RESIZE_IMAGE_QUALITY', '70');
// リサイズ画像キャッシュディレクトリ
define('PLUGIN_RESIZE_IMAGE_CACHE', DATA_HOME . 'resize_cache/');
// ブラウザキャッシュ有効期限（初期値として1日を設定）
define('PLUGIN_RESIZE_IMAGE_CACHE_SECONDS', '86400');

function plugin_resize_image_inline() {
	global $vars;

	// ページチェック
	if (isset($vars['page'])) {
		$page = $vars['page'];
	} else {
		// ページが設定されていない？
		return '&resize_image: ページが取得できません';
	}

	// 引数セット
	$args = func_get_args();
	// 画像ファル名チェック
	$file = array_shift($args);
	if (preg_match('/^(.+)/([^/]+)$/', $file, $matches)) {
		// 画像ファイル名にページ名（ページ参照パス）がある場合
		if ($matches[1] == '.' || $matches[1] == '..') {
			// ページ名部分が相対パス指定の場合
			$matches[1] .= '/';
		}
		// 画像ファイル名セット
		$file = $matches[2];
		// 絶対パスでページ名をセット
		$page = get_fullname(strip_bracket($matches[1]), $page);
	 }

	// 出力用に整形
	$page = '&amp;page=' . rawurlencode($page);
	$file = '&amp;image=' . rawurlencode($file);
	$width = isset($args[0]) ? array_shift($args) : '';
	$width = ($width === '') ? '' : '&amp;w=' . $width;
	$height = isset($args[0]) ? array_shift($args) : '';
	$height = ($height === '') ? '' : '&amp;h=' . $height;
	$quality = isset($args[0]) ? array_shift($args) : '';
	$quality = ($quality === '') ? '' : '&amp;q=' . $quality;
	$keep = isset($args[0]) ? (array_shift($args) ? '&amp;k' : '') : '';

	// 出力後plugin_resize_image_actionがコールされる
	return '<img src="' . get_script_uri() . '?plugin=resize_image'
			. $page . $file . $width . $height . $quality . $keep . '" />';
}

function plugin_resize_image_action() {
	global $vars;

	$page = isset($vars['page']) ? $vars['page'] : '';
	$file = isset($vars['image']) ? $vars['image'] : '';
	$width = isset($vars['w']) ? $vars['w'] : (isset($vars['width']) ? $vars['width'] : PLUGIN_RESIZE_IMAGE_WIDTH);
	$height = isset($vars['h']) ? $vars['h'] : (isset($vars['height']) ? $vars['height'] : PLUGIN_RESIZE_IMAGE_HEIGHT);
	$quality = isset($vars['q']) ? $vars['q'] : (isset($vars['quality']) ? $vars['quality'] : PLUGIN_RESIZE_IMAGE_QUALITY);
	$keep = isset($vars['k']) || isset($vars['keepaspectratio']) || isset($vars['keepaspect']);

	// ページ閲覧チェック（※チェック時にユーザ認証を確認しない）
	if (check_readable($page, FALSE, FALSE)) {
		// 画像ファイル編集（attachフォルダ画像）
		$image = UPLOAD_DIR
			. encode($page) . '_' . encode(preg_replace('/^.*\//', '', $file));
		// キャッシュ編集（リサイズ画像キャッシュディレクトリ＋ファイル名）
		// ※「ファイル名_幅_高さ_画像品質」が最終的なファイル名になる
		$cache = PLUGIN_RESIZE_IMAGE_CACHE
			. encode($page) . '_' . encode(preg_replace('/^.*\//', '', $file));
		// 画像をリサイズしてJPEGで出力
		resize_image_to_jpg($image, $cache, $file, $width, $height, $quality, $keep);
	} else {
		// ページが閲覧状態ではない場合
		output_jpeg_image(make_image_from_text("Not readable."), $quality);
	}

	exit;
}

function resize_image_to_jpg($imagefile = '', $cache = '', $filename = '',
							$width = PLUGIN_RESIZE_IMAGE_WIDTH,
							$height = PLUGIN_RESIZE_IMAGE_HEIGHT,
							$quality = PLUGIN_RESIZE_IMAGE_QUALITY,
							$keep_aspect = FALSE) {

	// 画像ファイルチェック
	if (file_exists($imagefile)) {
		list($image_width, $image_height, $image_type, $image_size) = @getimagesize($imagefile);
	} else {
		// 画像ファイルが存在しない場合
		output_jpeg_image(make_image_from_text('File not found.'), $quality);
		return;
	}

	if ($width == 0) {
		// 画像の幅指定がない場合はオリジナル画像の幅をセット
		$width = $image_width;
	}
	if ($height == 0) {
		// 画像の高さ指定がない場合はオリジナル画像の高さをセット
		$height = $image_height;
	}

	// 出力画像サイズ（幅・高さ）計算
	if ($keep_aspect) {
		// アスペクト比維持の場合
		$width_ratio = $width / $image_width;
		$height_ratio = $height / $image_height;
		if($width_ratio > $height_ratio) {
			$width = ($image_width * $height) / $image_height;
		} else {
			$height = ($image_height * $width) / $image_width;
		}
	}

	// 画像の幅・高さチェック
	if (!$image_width || !$image_height) {
		// 画像ファイルの幅・高さのどちらかが0の場合
		output_jpeg_header();
		output_jpeg_image(make_image_from_text('Image file load error.'), $quality);
		return;
	}
	if (!$width || !$height) {
		// 出力画像の幅・高さのどちらかが0の場合
		output_jpeg_header();
		output_jpeg_image(make_image_from_text('Illegal size(0) directed.'), $quality);
		return;
	}

	// ヘッダに出力するファイル名作成
	// Care for Japanese-character-included file name
	$legacy_filename = mb_convert_encoding($filename, 'UTF-8', SOURCE_ENCODING);
	if (LANG == 'ja') {
		switch (UA_NAME . '/' . UA_PROFILE) {
		case 'MSIE/default':
			$legacy_filename = mb_convert_encoding($filename, 'SJIS', SOURCE_ENCODING);
			break;
		}
	}
	$utf8filename = mb_convert_encoding($filename, 'UTF-8', SOURCE_ENCODING);

	// 拡張子をjpgに置き換える
	$pos = strrpos($filename, '.');
	if ($pos != 0) {
		$filename = substr($filename, 0, $pos);
	}
	$filename .= '.jpg';

	// キャッシュファイル名生成
	$cachefile = $cache . '_' . $width . '_' . $height . '_' . $quality;
	if (!file_exists($cachefile)) {
		// キャッシュが存在しなければ作成
		// 画像ファイル読み込み
		$readimage = @get_image($imagefile, $image_type);
		// 画像出力準備
		$image = @imagecreatetruecolor($width, $height);
		// 画像リサイズ＆コピー
		if ($readimage) {
			$successed = imagecopyresampled($image, $readimage, 0, 0, 0, 0, $width, $height, $image_width, $image_height);
		} else {
			$successed = FALSE;
		}
		// イメージデータ破棄
		if (isset($readimage)) {
			imagedestroy($readimage);
		}

		if (!$successed) {
			// イメージ作成に失敗した場合
			$image = make_image_from_text('Error loding image.');
			// エラーイメージ出力
			output_jpeg_header();
			output_jpeg_image($image, $quality);
			// イメージデータ破棄
			imagedestroy($image);
			return;
		} else {
			// キャッシュファイル作成
			$successed = @imagejpeg($image, $cachefile, $quality);
			if (!$successed) {
				// キャッシュの作成に失敗したら直接出力する
				output_jpeg_header();
				header('Content-Disposition: inline; filename="' . $legacy_filename
					. '"; filename*=utf-8\'\'' . rawurlencode($utf8filename));
				// イメージ出力
				output_jpeg_image($image, $quality);
				// イメージデータ破棄
				if (isset($image)) {
					imagedestroy($image);
				}
				return;
			}
			// イメージデータ破棄
			if (isset($image)) {
				imagedestroy($image);
			}
		}
	}

	// キャッシュファイル出力
	output_jpeg_header();
	$filesize = filesize($cachefile);
	if ($filesize) {
		header('Content-Length: ' . $filesize);
	}
	header('Content-Disposition: attachment; filename="'. $filename .'"');
	$handle = fopen($cachefile, 'rb');
	while (!feof($handle)) {
		echo fread($handle, 4096);
		flush();
	}
	fclose($handle);
	return;
}

function get_image($imagefile, $image_type) {
	$gdinfo = @gd_info();
	$readimage = '';
	switch ($image_type) {
	case IMAGETYPE_GIF:
		if ($gdinfo['GIF Read Support']) {
			$readimage = @imagecreatefromgif($imagefile);
		}
		break;
	case IMAGETYPE_JPEG:
		if ($gdinfo['JPG Support'] || $gdinfo['JPEG Support']) {
			$readimage = imagecreatefromjpeg($imagefile);
		}
		break;
	case IMAGETYPE_PNG:
		if ($gdinfo['PNG Support']) {
			$readimage = imagecreatefrompng($imagefile);
		}
		break;
	default:
	}
	return $readimage;
}

function make_image_from_text($string) {
	$length = strlen($string);
	$width = $length * 8 + 10;
	$height = 20;
	$image = imagecreatetruecolor($width, $height);
	$bgc = imagecolorallocate($image, 255, 255, 255);
	$tc  = imagecolorallocate($image, 0, 0, 0);
	imagefilledrectangle($image, 0, 0, $width, $height, $bgc);
	imagestring($image, 1, 5, 5, $string, $tc);
	return $image;
}

function output_jpeg_image($image, $quality) {
	// JPEGヘッダ出力
	output_jpeg_header();
	// JPEGイメージ出力
	imagejpeg($image, '', $quality);
}

function output_jpeg_header() {
	// content type出力
	header('Content-type: image/jpeg');
	header('Pragma: public');
	header('Cache-Control: max-age=' . PLUGIN_RESIZE_IMAGE_CACHE_SECONDS);
}

?>
