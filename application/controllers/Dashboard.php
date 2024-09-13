<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load the required models
        $this->load->model('Absensi_model');
        $this->load->model('M_user'); // Add this line to load the M_user model

        // Check session login
        if($this->session->userdata("id_user") == "") {
            redirect('/login');
        }
    }

    public function index() {
        $data['captured_images'] = $this->Absensi_model->get_all_captured_images();
        $user_id = $this->session->userdata('id_user');
        $data['user'] = $this->M_user->get_user_by_id($user_id);

        // Load the dashboard view
        $this->load->view('dashboard', $data);
    }

    public function logout() {
        // Destroy the session and redirect to login
        $this->session->sess_destroy();
        redirect('/login');
    }

}
