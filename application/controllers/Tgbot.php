<?php
defined('BASEPATH') or exit('No direct script access allowed');

use \React\EventLoop\Factory;
use \unreal4u\TelegramAPI\HttpClientRequestHandler;
use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use \unreal4u\TelegramAPI\TgLog;
use unreal4u\TelegramAPI\Telegram\Methods\SendPhoto;
use unreal4u\TelegramAPI\Telegram\Types\Custom\InputFile;

class Tgbot extends CI_Controller
{

    private $_BOT_TOKEN = '';
    private $_BOT_USERNAME = '';
    private $_NGROK = '';

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->database('ibank_monitoring');

        $this->load->model('Tgbot_model');
    }

    public function index()
    {
        $input = $this->input->raw_input_stream;

        file_put_contents('result.json', $input);
        $datas = json_decode($input, true);

        // check if user doesnt exists
        $user = $this->Tgbot_model->get_user($datas);
        if (empty($user)) {
            if ($datas['message']['text'] == '/start') {
                $this->Tgbot_model->set_user($datas);
                $this->start($datas);               
            } else {
                $this->Tgbot_model->set_user($datas);
            }
            return;
        }

        switch ($datas['message']['text']) {

            case '/start':
                $this->Tgbot_model->set_user_start($datas, null);

                $this->start($datas);

                break;

            case '/help':
                if ($this->Tgbot_model->get_user_started($datas) === null) {

                    $this->Tgbot_model->set_user_start($datas);

                    $this->help($datas);
                    
                }
                break;

            case '/showreport':
                if ($this->Tgbot_model->get_user_started($datas) === null) {

                    $this->Tgbot_model->set_user_steped($datas, 'showreport/resultreport');

                    $this->showreport($datas);
                }                
                break;

            default:

                $userStep = $this->Tgbot_model->get_user_steped($datas);

                if (isset($userStep)) {

                    switch ($userStep->step) {

                        case 'showreport/resultreport':

                            $this->showreport_result($datas);
                            $this->stop($datas);
                            break;

                        default:

                            break;

                    }
                }

                break;
        }

    }

    public function set_webhook()
    {
        $loop = Factory::create();
        $setWebhook = new SetWebhook();
        $setWebhook->url = 'https://3ccfbf31.ngrok.io';
        $tgLog = new TgLog($this->_BOT_TOKEN, new HttpClientRequestHandler($loop));
        $tgLog->performApiRequest($setWebhook);
        $loop->run();
    }

    //<Bot Command>
    public function start($datas)
    {   
        $firstname = $datas['message']['from']['first_name'];
        $lastname = $datas['message']['from']['last_name'];

        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = "Hallo $firstname $lastname, saya BOT Monitoring BRI yang akan membantu anda dalam hal memonitoring, anda dapat melihat fasilitas dan perintah yang dapat dilakukan dengan perintah /help.";

        $tgLog->performApiRequest($sendMessage);
        $loop->run();
    }

    public function help($datas)
    {
        $commands = $this->Tgbot_model->get_commands();

        $templates = '';
        foreach ($commands as $command) {
            $templates .= "$command->command - $command->description \n\r";
        }

        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = "BOT ini dibuat untuk membantu karyawan Bank Rakyat Indonesia (BRI) untuk melakukan Monitoring terhadapat Transaksi\n\r\n\rBerikut merupakan daftar perintah yang dapat dilakukan :\n\r\n\r$templates";
        $sendMessage->parse_mode = 'HTML';

        $tgLog->performApiRequest($sendMessage);
        $loop->run();

    }

    public function monitoringweb($datas)
    {
        // $this->Tgbot_model->set_user_start($datas);

        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = 'Berikut merupakan Aplikasi Monitoring Internet Banking <a href="http://10.35.65.136/MonitoringIBank">Klik Disini</a>';
        $sendMessage->parse_mode = 'HTML';

        $tgLog->performApiRequest($sendMessage);
        $loop->run();
    }

    public function showreport($datas)
    {
        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = 'Masukan tanggal report  yang ingin dilihat (ex: 2019-06-24) : ';
        $sendMessage->parse_mode = 'HTML';
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];

        $tgLog->performApiRequest($sendMessage);
        $loop->run();
    }

    // </Bot Command>

    public function elsetype($datas)
    {
        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = 'Masukan perintah /help untuk melihat daftar perintah yang dapat dilakukan BRI Monitoring';
        $sendMessage->parse_mode = 'HTML';

        $tgLog->performApiRequest($sendMessage);
        $loop->run();
    }

    public function showreport_result($datas)
    {
        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = 'HASIL REPORT TGL xxxx/xx/xx :';
        $sendMessage->parse_mode = 'HTML';

        $tgLog->performApiRequest($sendMessage);
        $loop->run();
    }

    public function stop($datas)
    {
        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = 'Terimakasih Telah menggunakan BRI Monitoring BOT, untuk menggunakan kembali silahkan lakukan perintah /start.';
        $sendMessage->parse_mode = 'HTML';

        $tgLog->performApiRequest($sendMessage);
        $loop->run();

        // set user to status unstart
        $this->Tgbot_model->set_user_unstart($datas);
    }

    // public function detailbot($datas)
    // {

    // }
}
