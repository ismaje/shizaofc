<?php

class Order extends CI_Controller{

	function __construct(){
		parent::__construct();
		$this->load->model('Mcrud');
        $this->load->model('Mproduct');

		$this->load->library("session");
	}

	public function index(){
		if(empty($this->session->userdata('username'))){
			redirect('adminpanel');
		}

        $data = $this->Mcrud->get_all_data('dibeli')->result();
			if($data!=NULL){
				$i = 0;
	            foreach($data as $o){
	                $data['dibeli'][$i] = array(
	                    'id_beli' => $o->id_beli,
	                    'nama' => $this->Mcrud->get_detail('pembeli','id_pembeli',$o->id_pembeli,'nama'),
                        'alamat' => $this->Mcrud->get_detail('pembeli','id_pembeli',$o->id_pembeli,'alamat'),
                        'telepon' => $this->Mcrud->get_detail('pembeli','id_pembeli',$o->id_pembeli,'telepon'),
						'tgl_beli' => $o->tgl_beli,
                        'total_beli' => $o->total_beli,
						'status' => $o->status,
	                );
	                $data['dibeli'][$i] = (object)$data['dibeli'][$i];
	                $i++;
	            }
	        }else{
	        	$data['dibeli']=array();
	        }
		$this->template->load('layout_admin','admin/order/index', $data);
	}

	function callback() {
		$data = file_get_contents("php://input");
		$row = json_decode($data,true);
		
		$order_id = $row['order_id'];
		$status_code = $row['status_code'];
		$gross_amount = $row['gross_amount'];
		$server_key = 'SB-Mid-server-ToXgvk4ZsbyzZkiIQ4W73w2t';

		$sig_key = $row['signature_key'];

		$localKey = hash('sha512', $order_id . $status_code . $gross_amount . $server_key);

		if ($localKey != $sig_key) return;

		$status = $row['transaction_status'];
		
		if ($status == 'pending') {
			return 'pending';
		} elseif ($status == 'capture' || $status == 'settlement') {
			$this->Mproduct->updateStatus($order_id, 'berhasil');
			return 'berhasil';
		} else {
			$this->Mproduct->updateStatus($order_id, 'gagal');
			return 'gagal';
		}

		return 'gagal';
	}

	function sukses() {
		echo 'order berhasil dibayar!';
	}

	function gagal() {
		echo 'order gagal dibayar!';	
	}
}

