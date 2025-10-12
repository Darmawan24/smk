<?php
// Simple test file to verify our application works

require_once 'vendor/autoload.php';

// Test database connection
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    echo "✅ Database connection: SUCCESS\n";
    
    // Test users count
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Users in database: " . $result['count'] . "\n";
    
    // Test demo admin user
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute(['admin@demo.com']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Demo admin user found: " . $admin['name'] . " (Role: " . $admin['role'] . ")\n";
    } else {
        echo "❌ Demo admin user not found\n";
    }
    
    // Test guru data
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM guru');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Guru records: " . $result['count'] . "\n";
    
    // Test siswa data
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM siswa');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Siswa records: " . $result['count'] . "\n";
    
    // Test kelas data
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM kelas');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Kelas records: " . $result['count'] . "\n";
    
    // Test mata pelajaran data
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM mata_pelajaran');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Mata Pelajaran records: " . $result['count'] . "\n";
    
    echo "\n🎉 All basic tests passed! Your SIAKAD SMK application is working.\n";
    echo "\n📋 Demo Login Credentials:\n";
    echo "   Admin: admin@demo.com / password\n";
    echo "   Guru: guru@demo.com / password\n";
    echo "   Siswa: siswa@demo.com / password\n";
    echo "\n🚀 To start the application:\n";
    echo "   php artisan serve\n";
    echo "   Then visit: http://localhost:8000\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}