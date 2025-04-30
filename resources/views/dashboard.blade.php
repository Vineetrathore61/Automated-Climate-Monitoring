<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Climate Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen p-10">

<div class="max-w-6xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">

    <div class="flex justify-end mb-6">
        <button id="theme-toggle"
                class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            🌗 Toggle Dark/Light
        </button>
    </div>

    <h1 class="text-4xl font-bold text-center text-purple-700 dark:text-purple-300 mb-10">
        🌍 Climate Monitoring Dashboard
    </h1>

    <!-- City Search -->
    <div class="mb-6 text-center relative">
        <form id="cityForm">
            @csrf
            <label for="city" class="text-lg font-semibold mr-2 text-gray-700 dark:text-gray-300">🌐 Enter City:</label>
            <input type="text" name="city" id="city" autocomplete="off"
                   class="p-2 w-64 rounded-lg border shadow text-gray-700 dark:text-gray-800"
                   placeholder="e.g. Delhi" value="{{ $city ?? '' }}">
            <button type="submit"
                    class="ml-3 bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition">
                🔍 Get Weather
            </button>
        </form>
        <ul id="suggestions"
            class="absolute z-10 bg-white dark:bg-gray-700 w-64 mt-1 rounded shadow max-h-48 overflow-y-auto hidden text-left mx-auto">
        </ul>
    </div>

    <!-- Data Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">🌡 Temperature</h2>
            <p id="temperature" class="text-3xl font-bold text-blue-600 dark:text-blue-300">-- °C</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">💧 Humidity</h2>
            <p id="humidity" class="text-3xl font-bold text-green-600 dark:text-green-300">-- %</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">☁️ Weather</h2>
            <p id="weather" class="text-3xl font-bold text-yellow-600 dark:text-yellow-300">--</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">🌫 AQI</h2>
            <p id="aqi" class="text-2xl font-bold text-red-500 dark:text-red-300">--</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">🌧 Rain</h2>
            <p id="rain" class="text-2xl font-bold text-blue-700 dark:text-blue-400">--</p>
        </div>
    </div>

    <!-- Suggestion Box -->
    <div id="suggestionCard" class="bg-yellow-100 dark:bg-yellow-300 text-yellow-900 dark:text-yellow-800 p-4 mt-6 rounded-lg shadow hidden">
        🧠 <strong>Suggestion:</strong> <span id="suggestionText"></span>
    </div>

    <!-- Buttons -->
    <div class="mt-10 flex justify-center space-x-4">
        <button onclick="manualRefresh()" type="button"
                class="bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 px-6 rounded-lg transition">
            🔄 Refresh Climate Data
        </button>
        <form method="GET" action="{{ route('dashboard.download') }}">
            <input type="hidden" name="city" id="pdfCity" value="{{ $city ?? 'Delhi' }}">
            <button type="submit"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                📥 Download PDF
            </button>
        </form>
    </div>

    <!-- Charts -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-10">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">📈 Live Climate Graph</h2>
        <canvas id="tempChart" height="100"></canvas>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-10">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">📅 5-Day Forecast</h2>
        <canvas id="forecastChart" height="100"></canvas>
    </div>

    <!-- ✅ Map Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-10">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">🗺️ City Location Map</h2>
        <div id="map" class="w-full h-80 rounded"></div>
    </div>
</div>

<script>
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlTag = document.documentElement;

    if (localStorage.getItem('theme') === 'dark') htmlTag.classList.add('dark');

    themeToggleBtn.addEventListener('click', () => {
        htmlTag.classList.toggle('dark');
        localStorage.setItem('theme', htmlTag.classList.contains('dark') ? 'dark' : 'light');
    });

    let map, marker;

    const ctx = document.getElementById('tempChart').getContext('2d');
    const tempChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Temperature (°C)',
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.2)',
                    data: [],
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Humidity (%)',
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    data: [],
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'AQI',
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.2)',
                    data: [],
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: { responsive: true }
    });

    const forecastChart = new Chart(document.getElementById('forecastChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Forecast (°C)',
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                data: [],
                tension: 0.4,
                fill: true
            }]
        },
        options: { responsive: true }
    });

    function addData(temp, humidity, aqi) {
        const now = new Date().toLocaleTimeString();
        if (tempChart.data.labels.length > 10) {
            tempChart.data.labels.shift();
            tempChart.data.datasets.forEach(ds => ds.data.shift());
        }
        tempChart.data.labels.push(now);
        tempChart.data.datasets[0].data.push(temp);
        tempChart.data.datasets[1].data.push(humidity);
        tempChart.data.datasets[2].data.push(aqi);
        tempChart.update();
    }

    function updateForecastChart(forecastData) {
        const labels = forecastData.map(i => i.datetime.split(' ')[1].slice(0, 5));
        const temps = forecastData.map(i => i.temp);
        forecastChart.data.labels = labels;
        forecastChart.data.datasets[0].data = temps;
        forecastChart.update();
    }

    function showSuggestion(temp, aqi, humidity) {
        let message = "Weather is fine today.";
        if (aqi > 4 && temp > 40) message = "Very poor air & extreme heat. Stay indoors.";
        else if (aqi > 4) message = "Poor air quality. Use mask outside.";
        else if (temp > 40) message = "Extreme heat. Stay cool & hydrated.";
        else if (humidity > 90) message = "High humidity. May feel uncomfortable.";
        document.getElementById('suggestionText').innerText = message;
        document.getElementById('suggestionCard').classList.remove('hidden');
    }

    async function fetchClimateData() {
        const city = document.getElementById('city').value;
        document.getElementById('pdfCity').value = city;
        const token = document.querySelector('input[name="_token"]').value;

        const response = await fetch("{{ route('fetch.climate.data') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ city })
        });

        const data = await response.json();

        document.getElementById('temperature').innerText = data.temperature + " °C";
        document.getElementById('humidity').innerText = data.humidity + " %";
        document.getElementById('weather').innerText = data.weather;
        document.getElementById('aqi').innerText = data.aqi;
        document.getElementById('rain').innerText = data.rain;

        if (!isNaN(data.temperature)) {
            addData(parseFloat(data.temperature), parseFloat(data.humidity), parseFloat(data.aqi));
        }

        updateForecastChart(data.forecast);
        showSuggestion(parseFloat(data.temperature), parseInt(data.aqi), parseInt(data.humidity));
        suggestions.innerHTML = '';
        suggestions.classList.add('hidden');

        // ✅ Map update
        if (map) {
            map.setView([data.lat, data.lon], 9);
            marker.setLatLng([data.lat, data.lon])
                .bindPopup(`🌍 ${city}<br>🌡 ${data.temperature}°C<br>💧 ${data.humidity}%<br>🌫 AQI: ${data.aqi}<br>🌧 Rain: ${data.rain}`)
                .openPopup();
        } else {
            map = L.map('map').setView([data.lat, data.lon], 9);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18
            }).addTo(map);
            marker = L.marker([data.lat, data.lon]).addTo(map)
                .bindPopup(`🌍 ${city}<br>🌡 ${data.temperature}°C<br>💧 ${data.humidity}%<br>🌫 AQI: ${data.aqi}<br>🌧 Rain: ${data.rain}`)
                .openPopup();
        }
    }

    function manualRefresh() {
        fetchClimateData();
    }

    // Autocomplete
    const cityInput = document.getElementById('city');
    const suggestions = document.getElementById('suggestions');
    let debounceTimer;
    let cityCache = {};

    cityInput.addEventListener('input', () => {
        const query = cityInput.value.toLowerCase();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            if (query.length < 2) return suggestions.classList.add('hidden');
            if (cityCache[query]) return showSuggestions(cityCache[query]);

            const res = await fetch(`/autocomplete-city?query=${query}`);
            const cities = await res.json();
            cityCache[query] = cities;
            showSuggestions(cities);
        }, 300);
    });

    function showSuggestions(cities) {
        suggestions.innerHTML = cities.map(city =>
            `<li class="px-4 py-2 hover:bg-gray-200 dark:hover:bg-gray-600 cursor-pointer" onclick="selectCity('${city}')">${city}</li>`
        ).join('');
        suggestions.classList.remove('hidden');
    }

    function selectCity(city) {
        cityInput.value = city;
        suggestions.innerHTML = '';
        suggestions.classList.add('hidden');
    }

    document.getElementById('cityForm').addEventListener('submit', e => {
        e.preventDefault();
        fetchClimateData();
    });

    // 🎙 Voice Assistant
    const synth = window.speechSynthesis;
    const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
    recognition.lang = 'en-US';

    function startListening() {
        recognition.start();
    }

    recognition.onresult = function(event) {
        const command = event.results[0][0].transcript.toLowerCase();
        if (command.includes("temperature")) speak("The temperature is " + document.getElementById('temperature').innerText);
        else if (command.includes("aqi")) speak("The air quality index is " + document.getElementById('aqi').innerText);
        else if (command.includes("rain")) speak("Rain status: " + document.getElementById('rain').innerText);
        else speak("Sorry, I did not understand that.");
    }

    function speak(text) {
        const utterance = new SpeechSynthesisUtterance(text);
        synth.speak(utterance);
    }

    // Auto-refresh every 30 seconds
    setInterval(fetchClimateData, 30000);
    fetchClimateData();
</script>
</body>
</html>
