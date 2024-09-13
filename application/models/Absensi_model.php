<?php
class Absensi_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database(); // Explicitly use the 'remote' group
    }

    public function get_all_captured_images() {
        
        $query = $this->db->get('captured_images');
        return $query->result(); // Should return an array of objects
    }
}