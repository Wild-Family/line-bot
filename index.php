<?php

require_once __DIR__ . '/vendor/autoload.php';

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('E2b+JfiYAUe8AyL1xzkRAI2k2ufhTNNi57fiFpswHDkA81DqYUPhr0609xYgcznc9nBKsQnGWuc6+0EK9BkSJuNede+xBAnDLX2P4iR3Pvxbpl+AOIRxhuYrrR9eIJfyJ1whUrP3kIMgq12kbOmnrQdB04t89/1O/w1cDnyilFU=');
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => '43dc7a84c3368d71a88ea81f1a8b5e70']);
$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

//自身
$my_url = "https://fd66196d.ngrok.io/";

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
      if($event->getText() === 'とって') {
        $bot->replyMessage($event->getReplyToken(),
          (new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
            ->add(new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder(1, 17))
            ->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('オバチャンに任せとき！'))
        );
        
       	$result = file_get_contents($ras_url.$message_start.$userId);
       	if ($result != null) {
       		$status = file_get_contents($ras_url.$message_status.$userId);
       		newMessage($bot, $event, $status);
       		$image = file_get_contents($ras_url.$message_pic.$userId);
       		$ori_path = "ori.jpeg";
       		$token = $event->getReplyToken();
       		$thumb_path = "$token.jpeg";
       		file_put_contents($ori_path, $image);
       		$thumb = transform_image_size($ori_path, $thumb_path, 200, 200);
       		$bot->pushMessage($event->getUserId(),
       			(new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
            		->add(new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($my_url.$ori_path, $my_url.$thumb_path))
       		);
       		unlink($ori_path);
       		unlink($thumb_path);
       	}
      } else {
        $bot->replyMessage($event->getReplyToken(),
          (new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
            ->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('「こんにちは」と呼びかけて下さいね！'))
            ->add(new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder(1, 4))
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

function transform_image_size($srcPath, $dstPath, $width, $height)
{
    list($originalWidth, $originalHeight, $type) = getimagesize($srcPath);
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
    $thumb = file_get_contents($dstPath);
}
?>
