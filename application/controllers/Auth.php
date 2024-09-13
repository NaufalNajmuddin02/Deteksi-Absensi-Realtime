<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function login() {
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        
        // Proses verifikasi login (misalnya memeriksa database)
        // Jika autentikasi berhasil:
        $this->session->set_userdata('username', $username); // Simpan username ke session
        redirect('absen');  // Redirect ke halaman absensi
    }

    public function logout() {
        $this->session->unset_userdata('username');  // Hapus session saat logout
        redirect('auth/login');
    }
}
