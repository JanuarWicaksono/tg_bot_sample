<?php
defined('BASEPATH') or exit('No direct script access allowed');

use \React\EventLoop\Factory;
use \unreal4u\TelegramAPI\HttpClientRequestHandler;
use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
use \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
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
        $this->load->model('Report_log_model');
    }

    public function index()
    {
        $input = $this->input->raw_input_stream;

        file_put_contents('result.json', $input);

        $datas = json_decode($input, true);

        if ($this->Tgbot_model->get_chat($datas) == null) {
            $this->unauthorize_chat($datas);
            return;
        }

        // check if condition text contains @bri16_bot
        if (strpos($datas['message']['text'], '@bri16_bot') !== false) {
            $arr = explode("@", $datas['message']['text'], 2);
            $first = $arr[0];
            $datas['message']['text'] = $first;
        }

        // check if user doesnt exists
        $user = $this->Tgbot_model->get_user($datas);
        if (empty($user)) {
            $this->Tgbot_model->set_user($datas);
            if ($datas['message']['text'] == '/start') {
                $this->start($datas);               
            }
            return;
        }

        switch ($datas['message']['text']) {

            case '/start':

                $this->start($datas);

                break;

            case '/help':
                if ($this->Tgbot_model->get_user_started($datas) === null) {

                    $this->Tgbot_model->set_user_start($datas);

                    $this->help($datas);
                    
                }
                break;

            case '/show_report':
                if ($this->Tgbot_model->get_user_started($datas) === null) {

                    $this->Tgbot_model->set_user_steped($datas, 'show_report/show_report_result');

                    $this->show_report($datas);

                }                
                break;

            case '/stop':
                if ($this->Tgbot_model->get_user_started($datas) === null) {
                    $this->stop($datas);

                    break;   
                }
            default:

                $userStep = $this->Tgbot_model->get_user_steped($datas);

                if (isset($userStep)) {

                    switch ($userStep->step) {

                        case 'show_report/show_report_result':

                            $this->show_report_result($datas);

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
        $this->Tgbot_model->set_user_start($datas, null);

        $firstname = $datas['message']['from']['first_name'];
        $lastname = $datas['message']['from']['last_name'];

        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->parse_mode = 'HTML';
        $sendMessage->text = "Hallo `<b>$firstname $lastname</b>`, saya BOT Monitoring BRI yang akan melayani anda dalam memonitoring transaksi, anda dapat melihat fasilitas dan perintah yang dapat dilakukan dengan perintah /help.";

        $tgLog->performApiRequest($sendMessage);

        $loop->run();
    }

    //<Bot Command>
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

    //<Bot Command>
    public function monitoringweb($datas)
    {
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

    //<Bot Command>
    public function show_report($datas)
    {
        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = 'Masukan tanggal report yang ingin dilihat dengan format tahun-bulan-tanggal (ex: 2019-06-24) : ';
        $sendMessage->parse_mode = 'HTML';
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];

        $tgLog->performApiRequest($sendMessage);
        $loop->run();
    }

    //<Bot Command>
    public function stop($datas)
    {
        $firstname = $datas['message']['from']['first_name'];
        $lastname = $datas['message']['from']['last_name'];

        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = "Terimakasih `<b>$firstname $lastname</b>` telah menggunakan BRI Monitoring BOT, untuk menggunakan kembali silahkan lakukan perintah /start.";
        $sendMessage->parse_mode = 'HTML';

        $tgLog->performApiRequest($sendMessage);
        $loop->run();

        // set user to status unstart
        $this->Tgbot_model->set_user_unstart($datas);
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

    public function unauthorize_chat($datas)
    {
        $loop = Factory::create();
        $handler = new HttpClientRequestHandler($loop);
        $tgLog = new TgLog($this->_BOT_TOKEN, $handler);

        $sendMessage = new SendMessage();
        $sendMessage->chat_id = $datas['message']['chat']['id'];
        $sendMessage->reply_to_message_id = $datas['message']['message_id'];
        $sendMessage->text = 'Mohon maaf chat ini tidak terdaftar pada sistem kami, anda tidak dapat menggunakan BRI Monitoring BOT';
        $sendMessage->parse_mode = 'HTML';

        $tgLog->performApiRequest($sendMessage);
        $loop->run();
    }

    public function show_report_result($datas)
    {
         $dataReports = $this->get_report_send_telegram($datas['message']['text']);

         $trx_header = "<strong>Transaksi Internet Banking</strong>\n\r<strong>Tanggal : $dataReports->trx_date </strong>\n\r<strong>Total Transaksi : $dataReports->trx_total </strong>\n\r<strong>Total Transaksi Sukses : $dataReports->trx_success_total </strong>\n\r<strong>Total Transaksi Gagal: $dataReports->trx_fail_total </strong>";
 
         $trx_list = '';
         foreach ($dataReports->trx_data as $item) {
             $trx_list .= "<code>$item->trx_type \n\r - Sukses:$item->trx_total_success | Gagal:$item->trx_total_fail | SR:$item->success_rate%</code> \n\r\n\r";
         }
 
         $trx_list_un = '';
         foreach ($dataReports->trx_data_un as $item) {
             $trx_list_un .= "<code>$item->trx_type \n\r - Sukses : $item->trx_total_success | Gagal : $item->trx_total_fail | SR : $item->success_rate%</code> \n\r\n\r";
         }
         
         $base_url = base_url('ib/Report_log/detail/'.$dataReports->trx_date);
         
         $trx_template = "$trx_header\n\r-------------------------------------------------\n\r<b>Daftar Transaksi</b>\n\r$trx_list\n\r-------------------------------------------------\n\r<b>Transaksi dengan Success Rate &lt; 50</b>\n\r$trx_list_un\n\r-------------------------------------------------\n\r\n\rDetail dapat dilihat pada link berikut : <a href='$base_url'>Click disini</a>";
 
         $loop = Factory::create();
         $handler = new HttpClientRequestHandler($loop);
         $tgLog = new TgLog($this->_BOT_TOKEN, $handler);
 
         $sendMessage = new SendMessage();
         $sendMessage->chat_id = $datas['message']['chat']['id'];
         $sendMessage->reply_to_message_id = $datas['message']['message_id'];
         $sendMessage->text = $trx_template;
         $sendMessage->parse_mode = 'HTML';
 
         $tgLog->performApiRequest($sendMessage);
         $loop->run();
    }

    public function get_report_send_telegram($yesterday = null)
    {

        $dataTypes = $this->Report_log_model->getAll_report_trx_type();

        foreach ($dataTypes as $dataType) {$dataTypex[] = $dataType->trx_type;}

        $dataTrxTot = $this->Report_log_model->get_daily_trx_yesterday_total($yesterday);
    
        $dataTrxTot->trx_success_total = $this->Report_log_model->get_daily_trx_yesterday_total_status($yesterday, 1)->trx_total;
        $dataTrxTot->trx_fail_total = $this->Report_log_model->get_daily_trx_yesterday_total_status($yesterday, 0)->trx_total;
        
        $dataTrxs = $this->Report_log_model->getAll_daily_trx_base($dataTypex, $yesterday);

        foreach ($dataTrxs as $dataTrx) {
            $dataTrxPerStas = $this->Report_log_model->get_daily_trx_by_status($dataTrx->trx_type, $yesterday);

            foreach ($dataTrxPerStas as $dataTrxPerSta) {
                if ($dataTrxPerSta->trx_status == '1') {
                    $dataTrxPerSta->trx_status = 'Sukses';
                } else {
                    $dataTrxPerSta->trx_status = 'Gagal';
                }

            }
            $dataTrx->trx_statusx = $dataTrxPerStas;

            $dataTrx->success_rate = '';
            //
            if (count($dataTrx->trx_statusx) == 1 ) {
                foreach ($dataTrx->trx_statusx as $item) {
                    if ($item->trx_status == 'Sukses') {
                        $dataTrx->success_rate = '100';
                        $dataTrx->trx_total_success = $item->trx_total;
                        $dataTrx->trx_total_fail = '0';

                    } elseif ($item->trx_status == 'Gagal') {
                        $dataTrx->trx_total_fail = $item->trx_total;
                        $dataTrx->trx_total_success = '0';
                        $dataTrx->success_rate = '0';
                    }
                }
            } elseif (count($dataTrx->trx_statusx) == 2 ){
                foreach ($dataTrx->trx_statusx as $item) {
                    if ($item->trx_status == 'Sukses') {
                        $dataTrx->trx_total_success = $item->trx_total;

                        $success = $item->trx_total;
                        $dataTrx->success_rate = round(($success/$dataTrx->trx_total)*100);     
                    } if ($item->trx_status == 'Gagal') {
                        $dataTrx->trx_total_fail = $item->trx_total;
                    }
                }
            }

            foreach ($dataTypes as $dataType) {
                if ($dataTrx->trx_type == $dataType->trx_type) {
                    $dataTrx->trx_type = $dataType->trx_name;
                }
            }

            if ($dataTrx->success_rate < 50) {
                $dataTrxTot->trx_data_un[] = $dataTrx;
            }
        }

        $dataTrxTot->trx_data = $dataTrxs;

        return $dataTrxTot;
    }

}