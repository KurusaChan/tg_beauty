<?php

namespace App;

use App\Commands\MainMenu;
use App\Commands\Price;
use App\TgHelpers\TelegramApi;
use http\Client\Curl\User;

class WebhookController {

    public function handle()
    {
        $update = \json_decode(file_get_contents('php://input'), TRUE);
        $isCallback = !array_key_exists('message', $update);
        $response = $isCallback ? $update['callback_query'] : $update;
        $chat_id = $response['message']['chat']['id'];

        $unknownCommand = true;
        if ($isCallback) {
            $config = include('config/callback_commands.php');
            $action = \json_decode($response['data'], true)['a'];

            if (isset($config[$action])) {
                (new $config[$action]($response))->handle($response);
            }

            $tg = new TelegramApi();
            $tg->answerCallbackQuery($response['id']);
        } else {
            // checking commands -> keyboard commands -> mode -> exit
            if ($update['message']['text']) {
                $text = $update['message']['text'];
                $key = $text;

                if (strpos($text, '/') === 0) {
                    $handlers = include('config/slash_commands.php');
                } elseif (strpos($text, 'Прайс') === 0) {
                    (new Price())->handle($update);
                    exit;
                } else {
                    $handlers = include('config/keyboard_сommands.php');
                }

                if (isset($handlers[$key])) {
                    (new $handlers[$key]($update))->handle($update);
                    exit;
                } else {
                    $handlers = include('config/mode_сommands.php');
                    $user = \App\Models\User::where('chat_id', $chat_id)->first();

                    if ($user && $handlers[$user->status]) {
                        (new $handlers[$user->status]($update))->handle($update);
                        exit;
                    }
                }
            }
        }

        if ($unknownCommand) {
            (new MainMenu())->handle($update);
        }
    }

}
