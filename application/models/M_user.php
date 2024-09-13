<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_user extends CI_Model {

    // Function to save registration data
    public function simpan_register($data) {
        return $this->db->insert("tbl_users", $data);
    }

    // Function to check login
    public function cek_login($username, $password) {
        $this->db->select("*");
        $this->db->from("tbl_users");
        $this->db->where("username", $username);
        $query = $this->db->get();
        $user = $query->row();
        /**
         * Check password
         */
        if (!empty($user)) {
            if (password_verify($password, $user->password)) {
                return $query->result();
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    // Add this function to get user by ID
    public function get_user_by_id($user_id) {
        $this->db->where('id_user', $user_id); // 'id' is the column name for user ID
        $query = $this->db->get('tbl_users'); // 'tbl_users' is the table name
        return $query->row(); // Return the user row
    }
}
