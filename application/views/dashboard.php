<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        body {
            overflow-x: hidden;
            margin: 0;
        }
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            background-color: #343a40;
            z-index: 100;
            transition: transform 0.3s ease;
        }
        .sidebar-hidden {
            transform: translateX(-250px);
        }
        .main-content {
            transition: margin-left 0.3s ease;
            padding: 15px;
        }
        .main-content.sidebar-open {
            margin-left: 250px;
        }
        .header {
            background-color: #333;
            color: #fff;
            padding: 15px;
            position: relative;
            z-index: 200;
        }
        .user-info img {
            margin-right: 10px;
        }
        .pagination {
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(-250px);
            }
            .main-content.sidebar-open {
                margin-left: 0;
            }
        }
        .btn-toggler {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 300;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        /* Additional Styles */
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-size: 1.1em;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .image-preview {
            width: 150px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .map-container {
            width: 200px;
            height: 150px;
            margin: 0 auto;
            border-radius: 4px;
        }
        .map-wrapper {
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar bg-dark text-white sidebar-hidden">
        <div class="sidebar-header text-center py-4">
            <img src="<?= base_url('assets/logo.png'); ?>" alt="Logo" class="img-fluid mb-3" width="100">
            <h5>IDMETAFORA</h5>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white" href="#">Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active text-white" href="#">Absensi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="#">Laporan Absensi</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div id="main-content" class="flex-fill main-content bg-light">
        <div class="header">
            <button id="toggle-sidebar" class="btn btn-light btn-toggler d-md-none">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="header-content">
                <h3 class="mb-0">Absensi</h3>
                <div class="user-info">
                    <!-- User info -->
                    <span class="ml-2"><?= htmlspecialchars($user->username); ?></span>
                </div>
            </div>
        </div>

        <div class="content p-4">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?php echo base_url(); ?>index.php/absen" class="btn btn-primary">Isi Absensi</a>
            </div>

            <!-- Tabel Absensi -->
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Username</th>
                        <th>Image Preview</th>
                        <th>Location</th>
                        <th>Delay Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($captured_images) && is_array($captured_images)): ?>
                        <?php foreach($captured_images as $image): ?>
                            <tr>
                                <td><?= htmlspecialchars($image->username); ?></td>
                                <td>
                                    <?php if(!empty($image->image_data)): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($image->image_data); ?>" class="image-preview" alt="Image Preview">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="map-wrapper">
                                        <div class="map-container" id="map-<?= $image->id; ?>"></div>
                                    </div>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function () {
                                            var mapId = 'map-<?= $image->id; ?>';
                                            var mapElement = document.getElementById(mapId);
                                            if (mapElement) {
                                                var lat = parseFloat('<?= $image->location_lat; ?>');
                                                var lng = parseFloat('<?= $image->location_lng; ?>');

                                                var map = L.map(mapId, {
                                                    zoomControl: false
                                                }).setView([lat, lng], 15);

                                                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                                                    attribution: '&copy; <a href="https://carto.com/attributions">CARTO</a>'
                                                }).addTo(map);

                                                L.marker([lat, lng]).addTo(map)
                                                    .bindPopup('Latitude: ' + lat + ', Longitude: ' + lng)
                                                    .openPopup();
                                            }
                                        });
                                    </script>
                                </td>
                                <td><?= htmlspecialchars($image->delay_status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    document.getElementById('toggle-sidebar').addEventListener('click', function() {
        var sidebar = document.getElementById('sidebar');
        var mainContent = document.getElementById('main-content');
        sidebar.classList.toggle('sidebar-hidden');
        mainContent.classList.toggle('sidebar-open');
    });
</script>
</body>
</html>
