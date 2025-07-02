<?php
require_once 'C:/xampp/htdocs/wordpress/wp-load.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 60px;
            background-color: #f9f9f9;
        }
        .success-box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 40px;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: green;
        }
        .home-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 25px;
            background: #21759b;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>🎉 Payment Successful!</h1>
        <p>Thank you for your purchase.</p>
        <p><strong>Reference:</strong> <?php echo $order_id > 0 ? 'Order #' . $order_id : 'Unknown'; ?></p>
        <a href="<?php echo home_url(); ?>" class="home-btn">Return to Homepage</a>
    </div>
</body>
</html>
