<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Climate Dashboard PDF</title>
    <style>
        body {
            font-family: sans-serif;
            color: #333;
            padding: 20px;
        }
        h1 {
            color: #4F46E5;
        }
        .card {
            border: 1px solid #ddd;
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <h1>🌍 Climate Monitoring Report</h1>

    <p><strong>City:</strong> {{ $weather['city'] }}</p>

    <div class="card">
        <h2>🌡 Temperature</h2>
        <p>{{ $weather['temperature'] }} °C</p>
    </div>

    <div class="card">
        <h2>💧 Humidity</h2>
        <p>{{ $weather['humidity'] }} %</p>
    </div>

    <div class="card">
        <h2>☁️ Weather</h2>
        <p>{{ $weather['weather'] }}</p>
    </div>

    <div class="card">
        <h2>🌫 AQI</h2>
        <p>
            @php
                function aqiLabel($val) {
                    return match($val) {
                        1 => 'Good',
                        2 => 'Fair',
                        3 => 'Moderate',
                        4 => 'Poor',
                        5 => 'Very Poor',
                        default => 'N/A'
                    };
                }
            @endphp
            AQI Index: {{ $weather['aqi'] }} ({{ aqiLabel($weather['aqi']) }})
        </p>
    </div>

    <p style="margin-top: 20px;">
        Generated on: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
    </p>
</body>
</html>
