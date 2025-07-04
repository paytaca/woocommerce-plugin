<?php
require_once 'C:/xampp/htdocs/wordpress/wp-load.php';

$order_id   = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_successful = false;

if ($order_id > 0) {
    $order = wc_get_order($order_id);
    if ($order) {
        if ($order->get_status() !== 'completed') {
            $order->update_status('completed', '✅ Payment verified via Paytaca.');
            
            // Reduce stock manually
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $qty     = $item->get_quantity();
                if ($product && $product->managing_stock()) {
                    $old_stock = $product->get_stock_quantity();
                    $product->decrease_stock($qty);
                    wc_delete_product_transients($product->get_id());
                    error_log("[Paytaca] 🔻 Reduced stock for Product {$product->get_id()} from $old_stock to " . $product->get_stock_quantity());
                }
            }

            do_action('woocommerce_order_status_completed', $order->get_id());
            error_log("[Paytaca] ✅ Order marked completed manually with stock reduction.");
        }

        // ✅ Clear the cart to reflect that the order is done
        if (function_exists('WC')) {
            WC()->cart->empty_cart();
            error_log("[Paytaca] 🛒 Cart cleared after order completion.");
        }
        $payment_successful = true;
    } else {
        error_log("[Paytaca Success Page] ⚠️ Order not found.");
    }
} else {
    error_log("[Paytaca Success Page] ⚠️ Missing order_id or invoice_id.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
    <style>
        :root {
            --bg-color: #f2f5f9;
            --card-bg: #ffffff;
            --primary-color: #0057ff;
            --accent-color: #e53935;
            --text-color: #2c3e50;
            --button-bg: var(--primary-color);
            --button-hover: #0040cc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .confetti {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }

        .confetti-piece {
            position: absolute;
            width: 8px;
            height: 8px;
            background-color: var(--accent-color);
            opacity: 0.8;
            animation: fall 3s linear infinite;
        }

        .confetti-piece:nth-child(odd) {
            background-color: var(--primary-color);
        }

        @keyframes fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        .success-box {
            background: var(--card-bg);
            padding: 50px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            text-align: center;
            max-width: 600px;
            width: 100%;
            z-index: 1;
        }

        .icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            animation: bounce 1s ease;
        }

        .logo {
            width: 60px;
            height: auto;
        }

        .emoji {
            font-size: 40px;
        }

        @keyframes bounce {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .message {
            font-size: 1.1rem;
            margin: 12px 0;
        }

        .reference {
            font-weight: bold;
            color: var(--accent-color);
        }

        .thank-you {
            font-size: 1rem;
            color: #555;
            margin-top: 20px;
        }

        .home-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 28px;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background-color: var(--button-bg);
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .home-btn:hover {
            background-color: var(--button-hover);
        }

        @media (max-width: 600px) {
            .success-box {
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .message, .thank-you {
                font-size: 1rem;
            }

            .logo {
                width: 50px;
            }

            .emoji {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="icon-wrapper">
            <span class="emoji">🎉</span>
            <img src="../assets/paytaca-icon.png" alt="Paytaca Logo" class="logo">
            <span class="emoji">🎉</span>
        </div>
        <h1><?php echo $payment_successful ? 'Payment Successful!' : 'Payment Pending'; ?></h1>
        <p class="message">
            <?php
                if ($order_id > 0) {
                    echo '<strong class="reference">Reference:</strong> Order #' . $order_id . '<br>';
                }

                echo $payment_successful
                    ? 'Thank you for your purchase. Your order has been completed.'
                    : 'We couldn’t confirm the payment yet. Please wait or contact support.';
            ?>
        </p>
        <p class="thank-you">We appreciate your trust in Paytaca.</p>
        <a href="<?php echo esc_url(home_url()); ?>" class="home-btn">Return to Homepage</a>
    </div>
</body>
</html>
