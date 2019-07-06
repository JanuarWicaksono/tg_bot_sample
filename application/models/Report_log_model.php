<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Report_log_model extends CI_Model
{
    public $tbl_daily_transaction = 'tbl_daily_transaction';
    public $column_order = [null, 'trx_date', 'trx_type', 'trx_status', 'response_code', 'total']; //field from table
    public $column_search = ['trx_date', 'total']; //field for searching
    public $order = array('trx_date' => 'desc'); //default order

    public $column_search_report_detail = ['trx_type'];
    public $column_order_report_detail = [null, 'trx_date', 'trx_type', 'trx_status', 'response_code']; //field from table
    public $order_report_detail = array('trx_type' => 'desc'); //default order

    public $table_dashboard = 'tbl_dashboard_parameter';
    public $table_trx_type = 'tbl_trx_type';
    public $table_trx_log = 'tbl_trx_log';
    public $tbl_report_trx_type = 'tbl_report_trx_type';
    public $id_log = 'id';
    public $id_log_ib = '10';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function getAll_report_trx_type()
    {
        $this->db->select('*');
        return $this->db->get($this->tbl_report_trx_type)->result();
    }

    public function check_report_generate_isset($yesterday)
    {
        $this->db->where('trx_date', $yesterday);
        $query = $this->db->get($this->tbl_daily_transaction);
        if ($query->num_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function check_report_generate_empty($yesterday)
    {
        $this->db->where('trx_date', $yesterday);
        $query = $this->db->get($this->tbl_daily_transaction);
        if ($query->num_rows() == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getAll_daily_trx_base($dataTypex, $yesterday)
    {
        $this->db->select('trx_type, SUM(total) as trx_total');
        $this->db->like('trx_date', $yesterday);
        // $this->db->where_in('trx_type', $dataTypex);
        $this->db->group_by('trx_type');
        return $this->db->get($this->tbl_daily_transaction)->result();
    }

    public function get_daily_trx_yesterday_total($yesterday)
    {
        $this->db->select('trx_date, SUM(total) as trx_total');
        if(isset($yesterday)){
            $this->db->where('trx_date', $yesterday);
        }
        return $this->db->get($this->tbl_daily_transaction)->row();
    }

    public function get_daily_trx_yesterday_total_status($yesterday, $status)
    {
        $this->db->select('trx_status, SUM(total) as trx_total');
        if (isset($yesterday)) {
            $this->db->where(['trx_date' => $yesterday, 'trx_status' => $status]);            
        } else {
            $this->db->where('trx_status', $status);
        }
        return $this->db->get($this->tbl_daily_transaction)->row();
    }

    public function get_daily_trx_by_status($dataTrx, $yesterday)
    {
        $this->db->select('trx_status, SUM(total) as trx_total');
        $this->db->where(['trx_type' => $dataTrx, 'trx_date' => $yesterday]);
        $this->db->group_by('trx_status');

        return $this->db->get($this->tbl_daily_transaction)->result();
    }

}