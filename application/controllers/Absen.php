<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Absen extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load any required libraries, models, or helpers here
        $this->load->helper('url');  // Agar bisa menggunakan base_url
    }

    public function index() {
        $this->load->view('isi_absensi'); // Ensure this matches the view file name
    }
    
    public function detect() {
        // Fungsi ini akan menerima gambar dari AJAX dan memprosesnya
        $input_data = json_decode(file_get_contents('php://input'), true);

        // Proses deteksi objek di sini menggunakan YOLO atau API deteksi lain
        // Misalnya, panggil model deteksi object detection YOLOv8
        
        $response = [
            'capture_completed' => false,
            'objects' => [
                [
                    'box' => [50, 50, 200, 200],  // x_min, y_min, x_max, y_max
                    'label' => 'Person',
                    'confidence' => 0.98
                ],
                [
                    'box' => [300, 150, 400, 250],
                    'label' => 'Logo',
                    'confidence' => 0.95
                ]
            ],
            'logo_detected' => true,  // contoh jika logo terdeteksi
        ];

        // Ubah capture_completed menjadi true jika deteksi sudah selesai
        echo json_encode($response);
    }

    public function save() {
        $input_data = json_decode(file_get_contents('php://input'), true);
    
        $username = $this->session->userdata('username');  // Ambil nama pengguna dari session
    
        // Tambahkan nama pengguna ke data yang akan dikirim ke API Flask
        $input_data['username'] = $username;
    
        // Kirim ke API Flask untuk menyimpan gambar dan informasi pengguna
        $ch = curl_init('http://192.168.43.249:5000/save');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
        $response = curl_exec($ch);
        curl_close($ch);
    
        echo $response;  // Tampilkan respons dari API Flask
    }
    

    public function cancel() {
        // Handle cancel action
        $response = ['status' => 'detection canceled'];
        echo json_encode($response);
    }
}