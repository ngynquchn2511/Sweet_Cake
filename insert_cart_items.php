<?php
// Đặt tại root project, chạy: php insert_cart_items.php

$host = '10.141.150.73';
$port = 3306;
$db   = 'sales_db_cakes';
$user = 'root';
$pass = '';

// Parse .env
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, 'DB_HOST='))     $host = trim(explode('=', $line, 2)[1], '"\'');
        if (str_starts_with($line, 'DB_PORT='))     $port = trim(explode('=', $line, 2)[1], '"\'');
        if (str_starts_with($line, 'DB_DATABASE=')) $db   = trim(explode('=', $line, 2)[1], '"\'');
        if (str_starts_with($line, 'DB_USERNAME=')) $user = trim(explode('=', $line, 2)[1], '"\'');
        if (str_starts_with($line, 'DB_PASSWORD=')) $pass = trim(explode('=', $line, 2)[1], '"\'');
    }
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage() . "\n");
}

// Lấy users
$users = $pdo->query("SELECT id FROM users WHERE role = 'customer' LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
if (empty($users)) {
    die("❌ Không có user!\n");
}

// Lấy products
$products = $pdo->query("SELECT id, name, price FROM products LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
if (empty($products)) {
    die("❌ Không có sản phẩm!\n");
}

// Lấy carts có sẵn
$carts = $pdo->query("SELECT id, user_id FROM carts LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
if (empty($carts)) {
    die("❌ Không có cart nào! Chạy: INSERT INTO carts (user_id) SELECT id FROM users WHERE role='customer' LIMIT 20;\n");
}

echo "🛒 Tìm thấy " . count($carts) . " carts\n";

$stmt = $pdo->prepare("
    INSERT INTO cart_items (cart_id, user_id, session_id, product_id, quantity, price, status, purchased_at, abandoned_at, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

echo "🛒 Tạo 30 cart items test data...\n\n";

$statuses = ['active', 'purchased', 'abandoned'];
$statusWeights = [
    'active' => 30,      // 30% - đang trong giỏ
    'purchased' => 40,   // 40% - đã mua
    'abandoned' => 30,   // 30% - bỏ giỏ
];

$count = 0;

for ($i = 0; $i < 30; $i++) {
    // Lấy cart ngẫu nhiên
    $cart = $carts[array_rand($carts)];
    $cartId = $cart['id'];
    $userId = $cart['user_id'];
    
    $product = $products[array_rand($products)];
    $quantity = rand(1, 3);
    $price = $product['price'];
    
    // Random status với weight
    $rand = rand(1, 100);
    if ($rand <= 30) {
        $status = 'active';
    } elseif ($rand <= 70) {
        $status = 'purchased';
    } else {
        $status = 'abandoned';
    }
    
    // Thời gian tạo: 1-60 ngày trước
    $daysAgo = rand(1, 60);
    $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));
    $updatedAt = $createdAt;
    
    // purchased_at và abandoned_at
    $purchasedAt = null;
    $abandonedAt = null;
    
    if ($status === 'purchased') {
        // Mua trong vòng 1-5 ngày sau khi thêm vào giỏ
        $purchasedDaysAfter = rand(1, 5);
        $purchasedAt = date('Y-m-d H:i:s', strtotime($createdAt . " +$purchasedDaysAfter days"));
    } elseif ($status === 'abandoned') {
        // Bỏ giỏ sau 7-30 ngày
        $abandonedDaysAfter = rand(7, 30);
        $abandonedAt = date('Y-m-d H:i:s', strtotime($createdAt . " +$abandonedDaysAfter days"));
    }
    
    // session_id cho guest users (50% chance)
    $sessionId = rand(0, 1) ? 'sess_' . uniqid() : null;
    
    $stmt->execute([
        $cartId,
        $userId,
        $sessionId,
        $product['id'],
        $quantity,
        $price,
        $status,
        $purchasedAt,
        $abandonedAt,
        $createdAt,
        $updatedAt
    ]);
    
    $statusIcon = [
        'active' => '🟢',
        'purchased' => '✅',
        'abandoned' => '🔴'
    ][$status];
    
    $statusLabel = [
        'active' => 'Active',
        'purchased' => 'Purchased',
        'abandoned' => 'Abandoned'
    ][$status];
    
    echo "$statusIcon User #$userId - {$product['name']} x$quantity - $statusLabel\n";
    $count++;
}

// Thống kê
$stats = [
    'active' => $pdo->query("SELECT COUNT(*) FROM cart_items WHERE status = 'active'")->fetchColumn(),
    'purchased' => $pdo->query("SELECT COUNT(*) FROM cart_items WHERE status = 'purchased'")->fetchColumn(),
    'abandoned' => $pdo->query("SELECT COUNT(*) FROM cart_items WHERE status = 'abandoned'")->fetchColumn(),
];

echo "\n📊 Thống kê:\n";
echo "   🟢 Active: {$stats['active']}\n";
echo "   ✅ Purchased: {$stats['purchased']}\n";
echo "   🔴 Abandoned: {$stats['abandoned']}\n";

echo "\n👍 Hoàn thành! Đã tạo $count cart items.\n";
echo "👉 Xóa file này sau khi test.\n";