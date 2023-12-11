<?php
date_default_timezone_set('Asia/Jakarta');
class Checkout extends CI_Controller{
    function  __construct(){
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->helper('form');
        $this->load->library('cart');
        $this->load->model('Mproduct');
        $this->load->model('Mcrud');
        $this->controller = 'checkout';
    }
    function index(){
        $custData = $data = array();
        $submit = $this->input->post('placeOrder');
        if(isset($submit)){
            $this->form_validation->set_rules('name', 'Name', 'required');
            $this->form_validation->set_rules('address', 'Address', 'required');
            $this->form_validation->set_rules('phone', 'Phone', 'required');
            $custData = array(
                'nama'     => strip_tags($this->input->post('name')),
                'alamat'     => strip_tags($this->input->post('address')),
                'telepon'     => strip_tags($this->input->post('phone'))
            );
            if($this->form_validation->run() == true){
                $insert = $this->Mproduct->insertCustomer($custData);
                if($insert){
                    $order = $this->placeOrder($insert, $custData);
                    $order = json_decode($order, true);
                    return header('location:' . $order['redirect_url']);
                    // echo $order['redirect_url']; exit();
                    // redirect($order['redirect_url']);
                    // redirect($this->controller.'/orderSuccess/'.$order);
                }
            }
        }
        $data['custData'] = $custData;
        $data['cartItems'] = $this->cart->contents();
        $this->template->load('layout_user','user/checkout',$data);
    }
    function placeOrder($custID, $custData){
        $ordData = array(
            'id_pembeli' => $custID,
            'total_beli' => $this->cart->total()
        );
        $insertOrder = $this->Mproduct->insertOrder($ordData);
        if($insertOrder){
            $cartItems = $this->cart->contents();
            $ordItemData = array();
            $i=0;
            foreach($cartItems as $item){
                $ordItemData[$i]['id_beli']     = $insertOrder;
                $ordItemData[$i]['id_produk']     = $item['id'];
                $ordItemData[$i]['jml_beli']     = $item['qty'];
                $ordItemData[$i]['sub_total']     = $item["subtotal"];
                $i++;
            }            
            if(!empty($ordItemData)){
                $insertOrderItems = $this->Mproduct->insertOrderItems($ordItemData);            
                if($insertOrderItems){
                    $this->cart->destroy();

                    $data = [
                        'transaction_details' => [
                            'order_id' => $insertOrder,
                            'gross_amount' => $ordData['total_beli']
                        ],
                        'customer_details' => [
                            'first_name' => $custData['nama'],
                            'phone' => $custData['telepon']
                        ]
                    ];

                    $process = $this->postMidtrans($data);
                    return $process;
                    // return $insertOrder;
                }
            }
        }
        return false;
    }    
    function orderSuccess($ordID){
        $data['order'] = $this->Mproduct->getOrder($ordID);
        $this->template->load('layout_user','user/order_success',$data);
    }    

    function postMidtrans($data) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,'https://app.sandbox.midtrans.com/snap/v1/transactions');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode('SB-Mid-server-ToXgvk4ZsbyzZkiIQ4W73w2t:'),
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $output = curl_exec($ch);

        curl_close($ch);

        return $output;
    }
}

