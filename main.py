import datetime
import logging
from flask import Flask, render_template, Response, request, jsonify, send_file, url_for
import cv2
import time
from ultralytics import YOLO
import pymysql
import os
from flask_cors import CORS
import base64
from io import BytesIO
from PIL import Image
import numpy as np

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}}) 
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # Max 16MB

model = YOLO('yolov8_trained_model.pt')

UPLOAD_FOLDER = os.path.join('static', 'captured_images')
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# Database connection function
def get_db_connection():
    connection = pymysql.connect(
        host='localhost',
        user='root',
        password='',  
        db='absensi',
        cursorclass=pymysql.cursors.DictCursor
    )
    return connection

logo_detected_start = None
logo_persistent_detected = False
capture_completed = False
captured_image_url = None
last_captured_file_name = None

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/detect', methods=['POST'])
def detect():
    global logo_detected_start, logo_persistent_detected, captured_image_url, last_captured_file_name

    data = request.get_json()

    if 'image' not in data:
        return jsonify({"error": "No image data provided"}), 400

    image_data = data['image'].split(',')[1]
    image_bytes = BytesIO(base64.b64decode(image_data))
    pil_image = Image.open(image_bytes)
    frame = np.array(pil_image)

    results = model(frame)
    detections = results[0].boxes.data

    detected_objects = []
    logo_detected = False

    for detection in detections:
        label = model.names[int(detection[5])]
        confidence = detection[4].item()
        x_min, y_min, x_max, y_max = map(int, detection[:4].tolist())

        detected_objects.append({
            "label": label,
            "confidence": confidence,
            "box": [x_min, y_min, x_max, y_max]
        })

        if label == "logo" and confidence >= 0.60:
            logo_detected = True
            logo_detected_start = time.time()

    logo_persistent_detected = logo_detected

    return jsonify({
        "logo_detected": logo_persistent_detected,
        "objects": detected_objects,
        "image_url": captured_image_url if capture_completed else None
    })

@app.route('/manual_capture', methods=['POST'])
def manual_capture():
    global capture_completed, captured_image_url, last_captured_file_name

    data = request.get_json()

    if 'image' not in data:
        return jsonify({"error": "No image data provided"}), 400

    try:
        image_data = data['image'].split(',')[1]
        image_bytes = BytesIO(base64.b64decode(image_data))
        pil_image = Image.open(image_bytes)
        frame = np.array(pil_image)
    except Exception as e:
        logging.error(f"Error processing image: {e}")
        return jsonify({"error": "Failed to process image"}), 500

    # Jalankan deteksi logo
    results = model(frame)
    detections = results[0].boxes.data

    logo_detected = False
    for detection in detections:
        label = model.names[int(detection[5])]
        confidence = detection[4].item()

        if label == "logo" and confidence >= 0.60:
            logo_detected = True

    if not logo_detected:
        return jsonify({"error": "No logo detected with required confidence"}), 400

    # Simpan gambar
    file_name = f'captured_image_{int(time.time())}.jpg'
    save_path = os.path.join(UPLOAD_FOLDER, file_name)

    try:
        pil_image.save(save_path)
    except Exception as e:
        logging.error(f"Error saving image: {e}")
        return jsonify({"error": "Failed to save image"}), 500

    # Buat URL untuk gambar
    captured_image_url = url_for('static', filename=f'captured_images/{file_name}', _external=True)
    last_captured_file_name = file_name
    capture_completed = True

    return jsonify({
        "capture_completed": True,
        "captured_image_url": captured_image_url
    })
@app.route('/save', methods=['POST'])
def save_captured_image():
    logging.info("Save API hit")
    data = request.get_json()

    if not data or 'file_name' not in data or 'capture_time' not in data or 'user_id' not in data or 'username' not in data:
        return jsonify({"error": "Missing data"}), 400

    file_name = data['file_name']
    capture_time_str = data['capture_time']
    location_lat = data.get('latitude', 0.0)
    location_lng = data.get('longitude', 0.0)
    delay_status = data.get('delay_status', 'Unknown')
    user_id = data['user_id']
    username = data['username']

    # Validasi waktu tangkapan
    try:
        capture_time = datetime.datetime.fromisoformat(capture_time_str)
    except ValueError:
        return jsonify({"error": "Invalid capture_time format"}), 400

    # Cek status keterlambatan
    current_time = datetime.datetime.now().time()
    if current_time < datetime.datetime.strptime('08:00:00', '%H:%M:%S').time():
        delay_status = 'Tepat Waktu'
    else:
        delay_seconds = (current_time.hour * 3600 + current_time.minute * 60 + current_time.second) - 28800
        delay_status = f'Terlambat {delay_seconds // 3600} jam {delay_seconds % 3600 // 60} menit {delay_seconds % 60} detik'

    # Simpan gambar di database
    image_path = os.path.join(UPLOAD_FOLDER, file_name)
    if not os.path.exists(image_path):
        return jsonify({"error": "File not found"}), 404

    try:
        with open(image_path, 'rb') as image_file:
            image_binary_data = image_file.read()
    except Exception as e:
        logging.error(f"Error reading image file: {e}")
        return jsonify({"error": "Failed to read the image file"}), 500

    # Insert ke database
    try:
        connection = get_db_connection()
        cursor = connection.cursor()

        cursor.execute("""
            INSERT INTO captured_images (file_name, file_url, capture_time, location_lat, location_lng, delay_status, image_data, user_id, username)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
        """, (file_name, captured_image_url, capture_time, location_lat, location_lng, delay_status, image_binary_data, user_id, username))
        connection.commit()
    except Exception as e:
        logging.error(f"Database insert error: {e}")
        return jsonify({"error": "Failed to save capture to database"}), 500
    finally:
        cursor.close()
        connection.close()

    # Hapus file gambar setelah disimpan
    try:
        os.remove(image_path)
    except Exception as e:
        logging.error(f"Error deleting file: {e}")
        return jsonify({"error": "Failed to delete the image after saving"}), 500

    return jsonify({"status": "Capture successfully saved to database and file deleted", "image_url": captured_image_url})



@app.route('/cancel', methods=['POST'])
def cancel_detection():
    global logo_detected_start, logo_persistent_detected, capture_completed, captured_image_url, last_captured_file_name

    logo_detected_start = None
    logo_persistent_detected = False
    capture_completed = False
    captured_image_url = None

    # Reset detection and keep the last image if canceled
    if last_captured_file_name:
        os.remove(os.path.join(UPLOAD_FOLDER, last_captured_file_name))
        last_captured_file_name = None

    return jsonify({"status": "Detection successfully canceled and reset."})

@app.route('/data')
def view_data():
    connection = get_db_connection()
    cursor = connection.cursor()

    cursor.execute("SELECT * FROM captured_images ORDER BY capture_time DESC")
    data = cursor.fetchall()

    cursor.close()
    connection.close()
    

    return render_template('data.html', data=data)

@app.route('/image/<int:image_id>')
def serve_image(image_id):
    connection = get_db_connection()
    cursor = connection.cursor()

    cursor.execute("SELECT image_data FROM captured_images WHERE id = %s", (image_id,))
    image_data = cursor.fetchone()
    
    cursor.close()
    connection.close()

    if image_data:
        image_binary_data = image_data['image_data']
        return send_file(BytesIO(image_binary_data), mimetype='image/jpeg')  
    else:
        return "Image not found", 404

if __name__ == "__main__":
    app.run(debug=True, host='0.0.0.0', port=5000)
