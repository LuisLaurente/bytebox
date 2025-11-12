<?php
// public/debug.php
echo "<h1>Debug del Sistema</h1>";

echo "<h2>URL Helper Test:</h2>";
echo "url('home/busqueda'): " . url('home/busqueda') . "<br>";
echo "asset('css/styles.css'): " . asset('css/styles.css') . "<br>";

echo "<h2>Server Variables:</h2>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? '') . "<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "<br>";

echo "<h2>Enlaces de prueba:</h2>";
echo '<a href="' . url('home/busqueda') . '">Home Búsqueda</a><br>';
echo '<a href="/bytebox/public/home/busqueda">Home Búsqueda (directo)</a><br>';
?>