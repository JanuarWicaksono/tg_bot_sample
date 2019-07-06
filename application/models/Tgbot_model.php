<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Tgbot_model extends CI_Model {

    public function set_user_steped($datas, $stepNext)
    {

        if ($this->_check_user($datas)) {

            $this->db->update('tbl_tg_session', [
                'step' => $stepNext,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['user_id' => $datas['message']['from']['id']]);

        }

    }

    private function _check_user($datas)
    {
        $query = $this->db->get_where('tbl_tg_session', [
            'user_id' => $datas['message']['from']['id'],
            'chat_id' => $datas['message']['chat']['id']
        ]);
        

        if ($query->num_rows() > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function get_user($datas)
    {
        $query = $this->db->get_where('tbl_tg_session', [
            'user_id' => $datas['message']['from']['id'],
            'chat_id' => $datas['message']['chat']['id'],
        ]);

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return;
        }
    }

    public function get_user_start($datas)
    {
        $query = $this->db->get_where('tbl_tg_session', [
            'user_id' => $datas['message']['from']['id'],
            'user_id' => $datas['message']['chat']['id'],
            'status' => 1,
            'step' => null
        ]);

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return;
        }
    }

    public function get_user_unstart($datas)
    {
        $query = $this->db->get_where('tbl_tg_session', [
            'user_id' => $datas['message']['from']['id'],
            'chat_id' => $datas['message']['chat']['id'],
            'status' => 0
        ]);

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return;
        }
    }

    public function get_user_started($datas)
    {
        $this->db->where([
            'user_id' => $datas['message']['from']['id'],
            'chat_id' => $datas['message']['chat']['id'],
            'status' => 0
        ]);
        $query = $this->db->get('tbl_tg_session');

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return;
        }
    }

    public function get_user_steped($datas)
    {
        $this->db->where([
            'user_id' => $datas['message']['from']['id'],
            'chat_id' => $datas['message']['chat']['id'],
            'status' => 1
        ]);
        $this->db->where('step IS NOT NULL');
        $query = $this->db->get('tbl_tg_session');

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return;
        }
    }

    public function get_chat($datas)
    {
        $query = $this->db->get_where('tbl_tg_list_chat',[
            'chat_id' => $datas['message']['chat']['id']
        ]);

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return;
        }
    }

    public function set_user($datas)
    {
        $this->db->insert('tbl_tg_session', [
            'user_id' => $datas['message']['from']['id'],
            'chat_id' => $datas['message']['chat']['id'],
            'firstname' => $datas['message']['from']['first_name'],
            'lastname' => $datas['message']['from']['last_name'],
            'username' => $datas['message']['from']['username'],
            'step' => null,
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function set_user_start($datas, $step)
    {
        if ($this->_check_user($datas)) {

            $this->db->update('tbl_tg_session', [
                'step' =>  (isset($step)) ? $step : null,
                'status' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['user_id' => $datas['message']['from']['id']]);

        }
    }

    public function set_user_unstart($datas, $step)
    {
        if ($this->_check_user($datas)) {

            $this->db->update('tbl_tg_session', [
                'step' =>  (isset($step)) ? $step : null,
                'status' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['user_id' => $datas['message']['from']['id']]);
        }
    }

    public function get_commands()
    {
        return $this->db->get('tbl_tg_command')->result();
    }

    

    

}

/* End of file ModelName.php */
