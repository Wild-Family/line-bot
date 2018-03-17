<?php

require_once __DIR__ . '/vendor/autoload.php';

//作成したチャネルのアクセストークンを記述
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('E2b+JfiYAUe8AyL1xzkRAI2k2ufhTNNi57fiFpswHDkA81DqYUPhr0609xYgcznc9nBKsQnGWuc6+0EK9BkSJuNede+xBAnDLX2P4iR3Pvxbpl+AOIRxhuYrrR9eIJfyJ1whUrP3kIMgq12kbOmnrQdB04t89/1O/w1cDnyilFU=');
//同チャネルのChannel Secretを記述
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => '43dc7a84c3368d71a88ea81f1a8b5e70']);
$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

//BotサーバのURL 末尾のスラッシュまで記述
$my_url = "https://obachan-bot.herokuapp.com/";
$resource = $my_url."resource/";
$obachan_full_path = $resource."obachan_full.jpg";
$obachan_thumb_path = $resource."obachan_thumb.jpg";
//$pic_path = $my_url."pictures/";
$pic_path = "pictures/";

//raspberry piサーバURL 末尾のスラッシュまで記述
$ras_url = "http://0da3e1b9.ngrok.io/";
$message_start = "start/";
$message_status = "status/";
$message_pic = "pic/";
$message_end = "end/";
$message_smile = "smile/";
$message_angry = "angry/";

//写真を保存するディレクトリを作成
if (!file_exists($pic_path)) {
	mkdir($pic_path);
}

//タイムアウト設定
$ctx = stream_context_create(array('http'=>
    array(
        'timeout' => 200,
    )
));

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

				$start = file_get_contents($ras_url.$message_start.$userId);
				$exit = False;
				$taked = False;
				$count = 0;
				if ($start != False && $start != "wait") {
					while(!$exit) {
						//raspberry piの状態確認
						$status = file_get_contents($ras_url.$message_status.$userId);
						//newMessage($bot, $event, $status);

						switch ($status) {
							case 'right':
								newMessage($bot, $event, "もうちょい右や！");
								break;
							case 'right again':
								newMessage($bot, $event, "右言うとるやろ！");
								break;
							case 'left';
								newMessage($bot, $event, "もうちょい左や！");
								break;
							case 'left again':
								newMessage($bot, $event, "左言うとるやろ！");
								break;
							case 'forward':
								newMessage($bot, $event, "もうちょい前や！");
								break;
							case 'forward again':
								newMessage($bot, $event, "前言うとるやろ！");
								break;
							case 'forwards':
								newMessage($bot, $event, "みんなもっと前や！");
								break;
							case 'forwards again':
								newMessage($bot, $event, "みんなもっと前言うとるやろ！");
								break;
							case 'back':
								newMessage($bot, $event, "もうちょい下がってや！");
								break;
							case 'back again':
								newMessage($bot, $event, "下がれ言うとるやろ！");
								break;
							case 'backs':
								newMessage($bot, $event, "みんな下がってや！");
								break;
							case 'backs':
								newMessage($bot, $event, "みんな下がれや！");
								break;
							case 'center':
								newMessage($bot, $event, "もっと真ん中に寄ってや！");
								break;
							case 'center again';
								newMessage($bot, $event, "真ん中に寄れ言うとるやろ！");
								break;
							case 'nobody':
								newMessage($bot, $event, "誰もおらんやないかい！");
								break;
							case 'smile':
								newMessage($bot, $event, "もうちょい笑ってや！");
								break;
							case 'smile again':
								newMessage($bot, $event, "笑顔が足りんで！");
								break;
							case 'smiles':
								newMessage($bot, $event, "みんな笑ってや！");
								break;
							case 'smiles again':
								newMessage($bot, $event, "みんな笑えや！");
								break;
							case 'ok':
								newMessage($bot, $event, "ええ感じや！　撮るで！");

								//キャッシュ回避のためにトークンをファイル名にする
								$token = $event->getReplyToken().microtime(true);

								//撮影された写真を受け取る
								//$image = file_get_contents($ras_url.$message_pic.$userId, false, $ctx);
								$curl = curl_init();
								curl_setopt($curl, CURLOPT_URL, $ras_url.$message_pic.$userId);
								curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
								curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

								$image = curl_exec($curl);

								curl_close($curl);

								//受け取り失敗
								if ($image == false) {
									pushMessage($bot, $event, "オバチャンちょっと調子悪いみたいや！　ホンマごめんな！");
									$exit = true;
									break;
								}

								$ori_path = $pic_path."${token}_ori.jpeg";
								file_put_contents($ori_path, $image);

								$thumb_path = $pic_path."${token}_thumb.jpeg";

		       					//サムネイル用にリサイズ
								transform_image_size($ori_path, $thumb_path);

								$end = file_get_contents($ras_url.$message_end.$userId);

		       					//LINEに写真とそのサムネイルを送信
								$bot->pushMessage($event->getUserId(),
									(new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
									->add(new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($my_url.$ori_path, $my_url.$thumb_path))
									->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('楽しんでや～！'))
								);
								
								$taked = True;
								$exit = True;

								break;
							default:
								newMessage($bot, $event, "オバチャンちょっと調子悪いみたいや！　ホンマごめんな！");
								$exit = True;
						}

						if (!$taked) {
							$count++;
						}
						if ($count == 4) {
							sleep(2);
							$angry = file_get_contents($ras_url.$message_angry.$userId);
							newMessage($bot, $event, "ええ加減にしいや！　最初からやり直しや！");
							$exit = True;
						}

						//一秒ごとにリクエスト送信
						sleep(1);

						//if ($taked) {
							//写真とサムネイルを削除
							//unlink($ori_path);
							//unlink($thumb_path);
						//}
					}
				} else if ($start == "wait") {
					newMessage($bot, $event, "ちょっと待ってな！");
				} else {
					newMessage($bot, $event, "オバチャンちょっと調子悪いみたいや！　ホンマごめんな！");
					//デモ
					$bot->pushMessage($event->getUserId(),
						(new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
						->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("このオバチャンはデモらしいで！"))
						->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("https://github.com/Wild-Family/2018trank"))
						->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("くわしいことはここを見たってや！"))
					);
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
