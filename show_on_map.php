<?php
require_once 'auth_check.php';
requireLogin();

// 1. Get and CLEAN parameters
$rawDest = $_GET['destination'] ?? '';
$destination = trim(urldecode($rawDest)); 
$packageName = $_GET['package_name'] ?? '';

// 2. Database of Locations (Expanded & Mapped)
$destinationCoordinates = [
    // --- Major Cities ---
    'Addis Ababa' => [9.0054, 38.7636],
    'Lalibela' => [12.0331, 39.0475],
    'Axum' => [14.1211, 38.7233],
    'Gondar' => [12.6030, 37.4521],
    'Bahir Dar' => [11.5742, 37.3614],
    'Harar' => [9.3126, 42.1222],
    'Arba Minch' => [6.0440, 37.5619],
    'Hawassa' => [7.0476, 38.4912],
    'Jimma' => [7.6667, 36.8333],
    'Mekele' => [13.4963, 39.4758],
    'Dire Dawa' => [9.6009, 41.8501],

    // --- Natural Sites ---
    'Semien Mountains' => [13.2508, 38.3161],
    'Bale Mountains' => [6.8167, 39.7833],
    'Danakil Depression' => [14.2417, 40.3000],
    'Blue Nile Falls' => [11.4833, 37.5833],
    'Erta Ale Volcano' => [13.6000, 40.6667],
    'Babile Elephant Sanctuary' => [9.2167, 42.3333], 
    'Sof Omar Caves' => [6.9000, 40.8500],
    'Awash National Park' => [9.0833, 40.0000],
    
    // --- MISSING LOCATIONS (Added These) ---
    'Omo Valley' => [5.6500, 36.5667],      // Jinka coordinates
    'Konso' => [5.3333, 37.4333],            // Cultural Landscape
    'Gambella National Park' => [8.2500, 34.5833],
    // --- Addis Ababa Parks & Museums ---
    'National Palace Museum' => [9.0165, 38.7605], // (Jubilee Palace)
    'Entoto Park' => [9.0866, 38.7624],            // (Entoto Natural Park Entrance)
    'Shager Park' => [9.0210, 38.7620],            // (Friendship Park/Square)
    'Addis Ababa Ethiopian' => [9.0333, 38.7522], 
    'Lalibela Rock-Hewn Churches Tour' => [12.0331, 39.0475],
    'Simien Mountains Trekking Adventure' => [13.2508, 38.3161],
    'Danakil Depression Expedition' => [14.2417, 40.3000],
    'Axum Ancient Kingdom Tour' => [14.1211, 38.7233],
    'Bahir Dar & Lake Tana' => [11.5742, 37.3614],
    'Harar Ancient Walled City' => [9.3126, 42.1222],
    'Bale Mountains National Park' => [6.8167, 39.7833],
    'Gondar  (The Camelot of Africa)' => [12.6030, 37.4521], // Matches your CSV spacing

    // --- REGION MAPPING ---
    'Southern Ethiopia' => [6.0440, 37.5619], 
    'Northern Ethiopia' => [12.0331, 39.0475], 
    'Eastern Ethiopia' => [9.3126, 42.1222], 
    'Western Ethiopia' => [7.6667, 36.8333] 
];

// 3. Smart Search (Case-Insensitive)
$destinationCoords = null;
foreach ($destinationCoordinates as $name => $coords) {
    if (strtolower($name) === strtolower($destination)) {
        $destinationCoords = $coords;
        $destination = $name; // Auto-correct capitalization
        break;
    }
}

// Fallback: If not found, default to Addis
$mapCenter = $destinationCoords ? $destinationCoords : [9.0054, 38.7636]; 
$zoomLevel = $destinationCoords ? 12 : 6;

// 4. Attractions Data (Expanded)
$touristAttractions = [
    'National Museum' => [9.0220, 38.7633],
    'Holy Trinity Cathedral' => [9.0333, 38.7586],
    'Rock-Hewn Churches' => [12.0316, 39.0415],
    'Fasil Ghebbi' => [12.6075, 37.4664],
    'Lake Tana' => [11.6500, 37.3333],
    'Blue Nile Falls' => [11.4833, 37.5833],
    'Sof Omar Caves' => [6.9000, 40.8500],
    'Babile Elephant Sanctuary' => [9.2167, 42.3333]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $destination ? "Map - " . htmlspecialchars($destination) : "Interactive Map"; ?> - Ghion Travel</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js" defer></script>
    
    <style>
        /* Dark Mode Styles */
        :root {
            --dark-bg: #121212;
            --dark-card: #1e1e1e;
            --dark-text: #e0e0e0;
            --dark-border: #333;
            --dark-accent: #3498db;
            --dark-secondary: #2c3e50;
            --dark-hover: #2a2a2a;
        }

        body.dark-mode {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        body.dark-mode .header {
            background-color: var(--dark-card);
            border-bottom: 1px solid var(--dark-border);
        }

        body.dark-mode .logo {
            color: var(--dark-text);
        }

        body.dark-mode .logo span {
            color: var(--dark-accent);
        }

        body.dark-mode .nav a {
            color: var(--dark-text);
        }

        body.dark-mode .nav a:hover {
            color: var(--dark-accent);
            background-color: var(--dark-hover);
        }

        body.dark-mode .btn-outline {
            border-color: var(--dark-accent);
            color: var(--dark-accent);
        }

        body.dark-mode .btn-outline:hover {
            background-color: var(--dark-accent);
            color: white;
        }

        body.dark-mode .map-container {
            background-color: var(--dark-card);
        }

        body.dark-mode .map-header {
            background-color: var(--dark-card);
            border-bottom: 1px solid var(--dark-border);
        }

        body.dark-mode .map-header h1 {
            color: var(--dark-text);
        }

        body.dark-mode .map-control-btn {
            background-color: var(--dark-secondary);
            color: var(--dark-text);
            border: 1px solid var(--dark-border);
        }

        body.dark-mode .map-control-btn:hover {
            background-color: var(--dark-accent);
            color: white;
        }

        body.dark-mode .map-control-btn.secondary {
            background-color: #444;
        }

        body.dark-mode .map-control-btn.success {
            background-color: #27ae60;
            color: white;
        }

        body.dark-mode .map-sidebar {
            background-color: var(--dark-card);
            border-left: 1px solid var(--dark-border);
        }

        body.dark-mode .sidebar-section h3 {
            color: var(--dark-text);
            border-bottom: 2px solid var(--dark-accent);
        }

        body.dark-mode .attraction-list {
            background-color: var(--dark-bg);
        }

        body.dark-mode .attraction-item {
            color: var(--dark-text);
            border-bottom: 1px solid var(--dark-border);
        }

        body.dark-mode .attraction-item:hover {
            background-color: var(--dark-hover);
        }

        body.dark-mode .status-bar {
            background-color: var(--dark-secondary);
            border-top: 1px solid var(--dark-border);
            color: var(--dark-text);
        }

        /* Dark mode for map controls */
        body.dark-mode .leaflet-control-container .leaflet-bar {
            background-color: var(--dark-card);
        }

        body.dark-mode .leaflet-control-container .leaflet-bar a {
            background-color: var(--dark-card);
            color: var(--dark-text);
            border-bottom: 1px solid var(--dark-border);
        }

        body.dark-mode .leaflet-control-container .leaflet-bar a:hover {
            background-color: var(--dark-hover);
        }

        /* Dark mode for routing control */
        body.dark-mode .leaflet-routing-container {
            background-color: var(--dark-card);
            color: var(--dark-text);
        }

        body.dark-mode .leaflet-routing-alt {
            background-color: var(--dark-card);
            color: var(--dark-text);
        }

        body.dark-mode .leaflet-routing-alt table tr:hover {
            background-color: var(--dark-hover);
        }

        /* Dark mode toggle button */
        .dark-mode-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--dark-secondary);
            color: var(--dark-text);
            border: none;
            cursor: pointer;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        body.dark-mode .dark-mode-toggle {
            background-color: var(--dark-accent);
            color: white;
        }

        .dark-mode-toggle:hover {
            transform: scale(1.1);
        }

        /* Dark mode for popups */
        body.dark-mode .leaflet-popup-content-wrapper {
            background-color: var(--dark-card);
            color: var(--dark-text);
        }

        body.dark-mode .leaflet-popup-tip {
            background-color: var(--dark-card);
        }

        /* Dark mode for attribution */
        body.dark-mode .leaflet-control-attribution {
            background-color: rgba(30, 30, 30, 0.8);
            color: var(--dark-text);
        }

        /* Dark mode for routing instructions */
        body.dark-mode .leaflet-routing-instruction-icon {
            background-color: var(--dark-secondary);
        }

        /* Smooth transitions */
        body, .header, .map-container, .map-sidebar, .map-control-btn,
        .nav a, .logo, .sidebar-section h3, .attraction-item,
        .leaflet-popup-content-wrapper, .leaflet-control-attribution {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        /* System dark mode detection */
        @media (prefers-color-scheme: dark) {
            body:not(.light-mode) {
                background-color: var(--dark-bg);
                color: var(--dark-text);
            }
            
            body:not(.light-mode) .header {
                background-color: var(--dark-card);
            }
            
            body:not(.light-mode) .map-container {
                background-color: var(--dark-card);
            }
        }
    </style>
</head>
<body>
   <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">Tour <span>Travel</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="packages_mongo.php"class="active">Tour Packages</a>
                <a href="car.php">Car</a>
                 <a href="hotel.php">Hotel</a>
                <a href="about.php">About Us</a>
                <a href="contact.php">Contact & feedback</a>
                <a href="my_bookings_mongo.php">My Bookings</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                  <span style="color: orange;">Hello, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                  
                  <button id="darkModeToggle" class="dark-toggle">
                    <i class="fas fa-moon"></i> Dark Mode
                  </button>
                  <a href="logout.php" class="btn">Logout</a>
                <?php else: ?>
                    <button id="darkModeToggle" class="dark-toggle">
                        <i class="fas fa-moon"></i> Dark Mode
                    </button>
                    <a href="login.php" class="btn-outline">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="map-container">
        <div class="map-header">
            <div>
                <h1><i class="fas fa-map-marked-alt"></i> Tourist Map <?php if ($destination) echo "- " . htmlspecialchars($destination); ?></h1>
            </div>
            <div class="map-controls">
                <button class="map-control-btn" onclick="locateUser()" id="locationBtn">
                    <i class="fas fa-location-arrow"></i> My Location
                </button>
                <button class="map-control-btn secondary" onclick="findNearbyAttractions()">
                    <i class="fas fa-search-location"></i> Nearby
                </button>
                <button class="map-control-btn success" onclick="showAllDestinations()">
                    <i class="fas fa-globe-africa"></i> All Sites
                </button>
            </div>
        </div>

        <div id="mainMap"></div>

        <div class="map-sidebar">
            <div class="sidebar-section">
                <h3><i class="fas fa-landmark"></i> Points of Interest</h3>
                <ul class="attraction-list" id="attractionsList"></ul>
            </div>
            
            <div class="status-bar">
                <div id="mapStatus">
                    <?php if ($destination && $destinationCoords): ?>
                        <i class="fas fa-check-circle" style="color: #27ae60;"></i> Location found: <?php echo htmlspecialchars($destination); ?>
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i> Map loaded.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Dark Mode Toggle Button -->
    <button class="dark-mode-toggle" id="darkModeToggle" title="Toggle Dark Mode">
        <i class="fas fa-moon" id="darkModeIcon"></i>
    </button>

    <script>
        // Dark Mode Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkModeIcon = document.getElementById('darkModeIcon');
            const body = document.body;
            
            // Check for saved theme preference or respect OS preference
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
            const savedTheme = localStorage.getItem('theme');
            
            // Set initial theme
            if (savedTheme === 'dark' || (!savedTheme && prefersDarkScheme.matches)) {
                enableDarkMode();
            } else if (savedTheme === 'light') {
                disableDarkMode();
            }
            
            // Toggle dark mode
            darkModeToggle.addEventListener('click', function() {
                if (body.classList.contains('dark-mode')) {
                    disableDarkMode();
                } else {
                    enableDarkMode();
                }
            });
            
            // Listen for OS theme changes
            prefersDarkScheme.addEventListener('change', function(e) {
                if (!localStorage.getItem('theme')) {
                    if (e.matches) {
                        enableDarkMode();
                    } else {
                        disableDarkMode();
                    }
                }
            });
            
            function enableDarkMode() {
                body.classList.add('dark-mode');
                darkModeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
                updateMapTilesForDarkMode();
                updateSidebarForDarkMode();
            }
            
            function disableDarkMode() {
                body.classList.remove('dark-mode');
                darkModeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
                updateMapTilesForLightMode();
                updateSidebarForLightMode();
            }
            
            function updateMapTilesForDarkMode() {
                if (typeof map !== 'undefined' && map) {
                    // Use a dark-themed map tile layer
                    map.eachLayer(function(layer) {
                        if (layer._url && layer._url.includes('openstreetmap')) {
                            map.removeLayer(layer);
                        }
                    });
                    
                    // Add dark map tiles (CartoDB Dark)
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                        subdomains: 'abcd',
                        maxZoom: 20
                    }).addTo(map);
                }
            }
            
            function updateMapTilesForLightMode() {
                if (typeof map !== 'undefined' && map) {
                    // Use standard map tile layer
                    map.eachLayer(function(layer) {
                        if (layer._url && layer._url.includes('cartocdn')) {
                            map.removeLayer(layer);
                        }
                    });
                    
                    // Add standard map tiles
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);
                }
            }
            
            function updateSidebarForDarkMode() {
                const attractionItems = document.querySelectorAll('.attraction-item');
                attractionItems.forEach(item => {
                    item.style.color = '#e0e0e0';
                    item.style.borderBottom = '1px solid #333';
                    item.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = '#2a2a2a';
                    });
                    item.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = '';
                    });
                });
            }
            
            function updateSidebarForLightMode() {
                const attractionItems = document.querySelectorAll('.attraction-item');
                attractionItems.forEach(item => {
                    item.style.color = '';
                    item.style.borderBottom = '1px solid #eee';
                    item.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = '#f5f5f5';
                    });
                    item.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = '';
                    });
                });
            }
        });

        // Wait for Leaflet to load
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L !== 'undefined') {
                initApp();
            } else {
                window.onload = initApp;
            }
        });

        // Global Variables
        let map;
        let routingControl = null;
        let currentMarkers = [];
        
        // PHP Data to JS
        const destCoords = <?php echo json_encode($destinationCoordinates); ?>;
        const initialCenter = <?php echo json_encode($mapCenter); ?>;
        const initialZoom = <?php echo $zoomLevel; ?>;
        const targetDest = <?php echo json_encode($destination); ?>;
        const attractions = <?php echo json_encode($touristAttractions); ?>;
        let isDarkMode = document.body.classList.contains('dark-mode');

        function initApp() {
            // 1. Initialize Map with appropriate tiles based on theme
            map = L.map('mainMap').setView(initialCenter, initialZoom);
            
            // Use dark tiles if dark mode is enabled
            if (isDarkMode) {
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                    subdomains: 'abcd',
                    maxZoom: 20
                }).addTo(map);
            } else {
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
            }

            // 2. Load Initial Destination if exists
            if (targetDest && destCoords[targetDest]) {
                addMarker(destCoords[targetDest], targetDest, 'destination');
            }

            // 3. Load Sidebar List
            loadSidebar();
        }

        // --- OPTIMIZED MARKER CREATION (Updated for Dark Mode) ---
        function createCustomIcon(type) {
            let color = '#3498db'; // Default Blue
            let icon = 'map-marker-alt';
            let size = 24;
            let borderColor = isDarkMode ? '#333' : 'white';

            if (type === 'destination') {
                color = '#e74c3c'; // Red
                icon = 'star';
                size = 32;
            } else if (type === 'user') {
                color = '#f39c12'; // Orange
                icon = 'user';
            } else if (type === 'attraction') {
                color = '#9b59b6'; // Purple for attractions
            }

            return L.divIcon({
                html: `<div style="background: ${color}; width: ${size}px; height: ${size}px; border-radius: 50%; border: 3px solid ${borderColor}; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white;">
                        <i class="fas fa-${icon}" style="font-size: ${size/2}px"></i>
                       </div>`,
                className: 'custom-marker',
                iconSize: [size + 6, size + 6],
                iconAnchor: [(size+6)/2, size+6]
            });
        }

        function addMarker(coords, title, type) {
            const marker = L.marker(coords, {
                icon: createCustomIcon(type),
                title: title
            }).addTo(map);

            const popupContent = `
                <div style="text-align:center; ${isDarkMode ? 'color: #e0e0e0;' : ''}">
                    <h4 style="margin: 0 0 10px 0;">${title}</h4>
                    <button onclick="calculateRoute([${coords}])" style="background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; margin-top:5px; font-size:14px;">
                        <i class="fas fa-directions"></i> Get Directions
                    </button>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            if(type === 'destination') marker.openPopup();
            currentMarkers.push(marker);
            return marker;
        }

        // --- ROBUST ROUTING (Fixes "Unable to retrieve" bug) ---
        function calculateRoute(destinationLatLng) {
            // 1. Check if browser supports it
            if (!navigator.geolocation) {
                document.getElementById('mapStatus').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#e74c3c"></i> Geolocation not supported.';
                return;
            }

            // 2. Show Loading Status
            const statusBox = document.getElementById('mapStatus');
            statusBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finding your location...';

            // 3. Define the Success Handler (What happens when we find you)
            const onLocationFound = (position) => {
                const userLatLng = [position.coords.latitude, position.coords.longitude];

                // Remove old route & markers
                if (routingControl) map.removeControl(routingControl);
                currentMarkers.forEach(m => { 
                    if(m.options.title === 'You are here') map.removeLayer(m); 
                });

                // Add User Marker
                addMarker(userLatLng, "You are here", "user");

                // Draw Route
                statusBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculating road path...';
                
                routingControl = L.Routing.control({
                    waypoints: [
                        L.latLng(userLatLng),
                        L.latLng(destinationLatLng)
                    ],
                    lineOptions: { 
                        styles: [
                            {
                                color: isDarkMode ? '#4CAF50' : '#2e7d32', 
                                weight: 6,
                                opacity: 0.8
                            }
                        ] 
                    },
                    createMarker: function() { return null; }, // No extra markers
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: true,
                    routeWhileDragging: false
                }).addTo(map);

                // Handle Route Success
                routingControl.on('routesfound', function(e) {
                    const routes = e.routes;
                    const summary = routes[0].summary;
                    const dist = (summary.totalDistance / 1000).toFixed(1);
                    const time = Math.round(summary.totalTime / 60);
                    statusBox.innerHTML = `<i class="fas fa-car"></i> Distance: ${dist} km | Time: ${time} mins`;
                });
                
                // Handle Routing Errors (e.g. No road found)
                routingControl.on('routingerror', function() {
                    statusBox.innerHTML = '<i class="fas fa-road" style="color:#e74c3c"></i> No road route found to this location.';
                });
            };

            // 4. Define Error Handler (Retry with lower accuracy if GPS fails)
            const onLocationError = (error) => {
                console.warn("High accuracy failed, trying low accuracy...");
                
                // Fallback: Try again with Low Accuracy (Wifi/Cell) - It rarely fails
                navigator.geolocation.getCurrentPosition(
                    onLocationFound, 
                    function(finalError) {
                        statusBox.innerHTML = '<i class="fas fa-exclamation-circle" style="color:#e74c3c"></i> Could not access your location. Please check browser permissions.';
                    },
                    { enableHighAccuracy: false, timeout: 10000, maximumAge: 0 }
                );
            };

            // 5. EXECUTE: Try High Accuracy GPS first
            navigator.geolocation.getCurrentPosition(
                onLocationFound, 
                onLocationError, 
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        }

        // --- HELPER FUNCTIONS ---
        function locateUser() {
            navigator.geolocation.getCurrentPosition(pos => {
                const coords = [pos.coords.latitude, pos.coords.longitude];
                map.setView(coords, 13);
                addMarker(coords, "You are here", "user");
            });
        }

        function showAllDestinations() {
            // Remove routing
            if (routingControl) map.removeControl(routingControl);
            
            // Add all markers
            Object.keys(destCoords).forEach(name => {
                if (destCoords[name]) {
                    addMarker(destCoords[name], name, 'destination');
                }
            });
            // Fit bounds
            map.fitBounds(currentMarkers.map(m => m.getLatLng()));
        }

        function findNearbyAttractions() {
            const center = map.getCenter();
            let foundCount = 0;

            // Use Leaflet's native distanceTo (Optimized)
            Object.keys(attractions).forEach(name => {
                const attrLoc = L.latLng(attractions[name]);
                const dist = center.distanceTo(attrLoc) / 1000; // Meters to KM

                if (dist < 100) { // Within 100km
                    addMarker(attractions[name], name + ` (${dist.toFixed(1)}km)`, 'attraction');
                    foundCount++;
                }
            });
            
            if(foundCount === 0) {
                alert("No attractions found nearby (within 100km).");
            }
        }

        function loadSidebar() {
            const list = document.getElementById('attractionsList');
            Object.keys(attractions).forEach(name => {
                const li = document.createElement('li');
                li.innerHTML = `<i class="fas fa-map-pin"></i> ${name}`;
                li.className = 'attraction-item';
                li.style.cursor = 'pointer';
                li.style.padding = '10px';
                li.style.borderBottom = isDarkMode ? '1px solid #333' : '1px solid #eee';
                li.style.color = isDarkMode ? '#e0e0e0' : '#333';
                
                li.onclick = () => {
                    map.setView(attractions[name], 14);
                    addMarker(attractions[name], name, 'attraction').openPopup();
                };
                
                // Add hover effect
                li.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = isDarkMode ? '#2a2a2a' : '#f5f5f5';
                });
                li.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
                
                list.appendChild(li);
            });
        }
    </script>
</body>
</html>