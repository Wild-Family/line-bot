<?php

require_once __DIR__ . '/vendor/autoload.php';

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient('E2b+JfiYAUe8AyL1xzkRAI2k2ufhTNNi57fiFpswHDkA81DqYUPhr0609xYgcznc9nBKsQnGWuc6+0EK9BkSJuNede+xBAnDLX2P4iR3Pvxbpl+AOIRxhuYrrR9eIJfyJ1whUrP3kIMgq12kbOmnrQdB04t89/1O/w1cDnyilFU=');
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => '43dc7a84c3368d71a88ea81f1a8b5e70']);
$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

//raspberry piサーバ
$ras_url = "";
$message_start = "start";

$events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
foreach ($events as $event) {

  $profile = $bot->getProfile($event->getUserId())->getJSONDecodedBody();
  $displayName = $profile['displayName'];

  if ($event instanceof \LINE\LINEBot\Event\MessageEvent) {
    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
      if($event->getText() === 'とって') {
        $bot->replyMessage($event->getReplyToken(),
          (new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
            ->add(new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder(1, 17))
            ->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('オバチャンに任せとき！'))
        );
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
?>
