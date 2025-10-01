<?php
// This file initializes the database
// Run this file once to create all tables and populate with sample data

include 'database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f0f0;}";
echo ".success{color:green;font-weight:bold;padding:10px;background:#d4edda;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;padding:10px;background:#d1ecf1;border-radius:5px;margin:10px 0;}";
echo ".warning{color:#856404;padding:10px;background:#fff3cd;border-radius:5px;margin:10px 0;}";
echo "a{display:inline-block;margin-top:20px;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;}";
echo "a:hover{background:#0056b3;}";
echo ".test-section{margin-top:30px;padding:20px;background:white;border-radius:5px;}";
echo ".test-btn{display:inline-block;margin:10px 10px 10px 0;padding:10px 15px;background:#28a745;color:white;text-decoration:none;border-radius:5px;cursor:pointer;border:none;}";
echo ".test-btn:hover{background:#218838;}";
echo "</style>";
echo "</head><body>";
echo "<h1>Database Setup Complete!</h1>";
echo "<div class='success'>✓ Database 'base_designer' created successfully</div>";
echo "<div class='info'>The following tables have been created and populated with sample data:</div>";
echo "<ul>";
echo "<li><strong>items</strong> - Contains inventory items (Sword, Shield, Potion, etc.)</li>";
echo "<li><strong>tiles</strong> - Contains floor tiles (Grass, Stone, Wood, etc.)</li>";
echo "<li><strong>storage</strong> - Contains storage containers (Small Chest, Medium Chest, etc.)</li>";
echo "<li><strong>decorations</strong> - Contains decorative items (Flower, Tree, etc.)</li>";
echo "<li><strong>grid_items</strong> - Stores saved grid layouts</li>";
echo "</ul>";

echo "<div class='test-section'>";
echo "<h2>Test Database Connection</h2>";
echo "<button class='test-btn' onclick='testItems()'>Test Items API</button>";
echo "<button class='test-btn' onclick='testTiles()'>Test Tiles API</button>";
echo "<button class='test-btn' onclick='testStorage()'>Test Storage API</button>";
echo "<button class='test-btn' onclick='testDecorations()'>Test Decorations API</button>";
echo "<div id='test-results' style='margin-top:20px;padding:10px;background:#f8f9fa;border-radius:5px;display:none;'></div>";
echo "</div>";

echo "<div class='warning'>⚠️ Make sure to access this application through a web server (not file://)</div>";
echo "<a href='index.html'>Go to Base Designer Application →</a>";

echo "<script>";
echo "async function testItems() {";
echo "  const res = await fetch('getItems.php?type=items');";
echo "  const data = await res.json();";
echo "  document.getElementById('test-results').style.display='block';";
echo "  document.getElementById('test-results').innerHTML = '<strong>Items loaded:</strong> ' + data.length + ' items<br><pre>' + JSON.stringify(data, null, 2) + '</pre>';";
echo "}";
echo "async function testTiles() {";
echo "  const res = await fetch('getItems.php?type=tiles');";
echo "  const data = await res.json();";
echo "  document.getElementById('test-results').style.display='block';";
echo "  document.getElementById('test-results').innerHTML = '<strong>Tiles loaded:</strong> ' + data.length + ' tiles<br><pre>' + JSON.stringify(data, null, 2) + '</pre>';";
echo "}";
echo "async function testStorage() {";
echo "  const res = await fetch('getItems.php?type=storage');";
echo "  const data = await res.json();";
echo "  document.getElementById('test-results').style.display='block';";
echo "  document.getElementById('test-results').innerHTML = '<strong>Storage loaded:</strong> ' + data.length + ' items<br><pre>' + JSON.stringify(data, null, 2) + '</pre>';";
echo "}";
echo "async function testDecorations() {";
echo "  const res = await fetch('getItems.php?type=decorations');";
echo "  const data = await res.json();";
echo "  document.getElementById('test-results').style.display='block';";
echo "  document.getElementById('test-results').innerHTML = '<strong>Decorations loaded:</strong> ' + data.length + ' items<br><pre>' + JSON.stringify(data, null, 2) + '</pre>';";
echo "}";
echo "</script>";

echo "</body></html>";
?>

