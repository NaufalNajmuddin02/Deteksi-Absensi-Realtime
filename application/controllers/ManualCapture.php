<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ManualCapture extends CI_Controller {

    public function manual_capture() {
        // Pastikan ini adalah request POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Mendapatkan data gambar dari permintaan (base64 encoded)
            $postData = json_decode(file_get_contents('php://input'), true);
            $imageData = $postData['image'];

            // Menghilangkan bagian "data:image/jpeg;base64," jika ada
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);

            // Decode base64 menjadi binary
            $decodedImage = base64_decode($imageData);

            // Tentukan path untuk menyimpan gambar
            $imageFileName = 'manual_capture_' . time() . '.jpg';
            $imagePath = FCPATH . 'uploads/' . $imageFileName;

            // Simpan gambar di folder uploads
            file_put_contents($imagePath, $decodedImage);

            // Kirimkan respon dengan URL gambar
            $response = array(
                'capture_completed' => true,
                'captured_image_url' => base_url('uploads/' . $imageFileName)
            );
            echo json_encode($response);
        } else {
            // Jika bukan POST request, tampilkan pesan error
            echo json_encode(array('error' => 'Invalid request method.'));
        }
    }
}
