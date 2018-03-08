<?php

require_once __DIR__ . '/vendor/autoload.php';

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('E2b+JfiYAUe8AyL1xzkRAI2k2ufhTNNi57fiFpswHDkA81DqYUPhr0609xYgcznc9nBKsQnGWuc6+0EK9BkSJuNede+xBAnDLX2P4iR3Pvxbpl+AOIRxhuYrrR9eIJfyJ1whUrP3kIMgq12kbOmnrQdB04t89/1O/w1cDnyilFU=');
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => '43dc7a84c3368d71a88ea81f1a8b5e70']);
$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

//自身
$my_url = "https://fd66196d.ngrok.io/";
$resource = $my_url."resource/";
$obachan_full_path = $resource."obachan_full.jpg";
$obachan_thumb_path = $resource."obachan_thumb.jpg";
$pic_path = $my_url."pictures/";

//raspberry piサーバ
$ras_url = "http://a926adfc.ngrok.io/";
$message_start = "start/";
$message_status = "status/";
$message_pic = "pic/";

$events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
foreach ($events as $event) {

	$profile = $bot->getProfile($event->getUserId())->getJSONDecodedBody();
	$userId = $event->getUserId();

	if ($event instanceof \LINE\LINEBot\Event\MessageEvent) {
		if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
			if($event->getText() === "とって" or $event->getText() === "撮って") {
				$bot->replyMessage($event->getReplyToken(),
					(new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
					->add(new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($obachan_full_path, $obachan_thumb_path))
					->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('オバチャンに任せとき！'))
				);

				$result = file_get_contents($ras_url.$message_start.$userId);
				$exit = False;
				if ($result != null) {
					while($exit == "False") {
						//raspberry piの状態確認
						$status = file_get_contents($ras_url.$message_status.$userId);
						newMessage($bot, $event, $status);

						switch ($status) {
							case 'right':
								newMessage($bot, $event, "もうちょい右や！");
								break;
							case 'left';
								newMessage($bot, $event, "もうちょい左や！");
								break;
							case 'forward':
								newMessage($bot, $event, "もうちょい前や！");
								break;
							case 'back':
								newMessage($bot, $event, "もうちょい下がってや！");
								break;
							case 'ok':
								newMessage($bot, $event, "ええ感じや！　撮るで！");
								//撮影された写真を受け取る
								$image = file_get_contents($ras_url.$message_pic.$userId);
								$ori_path = $pic_path."ori.jpeg";

		       					//キャッシュ回避のためにトークンをファイル名にする
								$token = $event->getReplyToken();
								$thumb_path = $pic_path."$token.jpeg";
								file_put_contents($ori_path, $image);

		       					//サムネイル用にリサイズ
								transform_image_size($ori_path, $thumb_path);

		       					//LINEに写真とそのサムネイルを送信
								$bot->pushMessage($event->getUserId(),
									(new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
									->add(new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($ori_path, $thumb_path))
								);

								//写真とサムネイルを削除
								unlink($ori_path);
								unlink($thumb_path);

								$exit = True;

								break;

								//一秒ごとにリクエスト送信
								sleep(1);
						}
					}
				}
			} else {
				$bot->replyMessage($event->getReplyToken(),
					(new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
					->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('若いもんの言葉はむずかしいわぁ～'))
				);
			}
		}
		continue;
	}
}

function newMessage($bot, $event, $message) {
	$bot->pushMessage($event->getUserId(),
		(new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
		->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message))	
	);
}

function transform_image_size($srcPath, $dstPath)
{
	list($originalWidth, $originalHeight, $type) = getimagesize($srcPath);

	$scale = 0.2;

	$width = $originalWidth * $scale;
	$height = $originalHeight * $scale;

	switch ($type) {
		case IMAGETYPE_JPEG:
		$source = imagecreatefromjpeg($srcPath);
		break;
		case IMAGETYPE_PNG:
		$source = imagecreatefrompng($srcPath);
		break;
		case IMAGETYPE_GIF:
		$source = imagecreatefromgif($srcPath);
		break;
		default:
		throw new RuntimeException("サポートしていない画像形式です: $type");
	}

	$canvas = imagecreatetruecolor($width, $height);
	imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
	imagejpeg($canvas, $dstPath);
	imagedestroy($source);
	imagedestroy($canvas);
}
?>
