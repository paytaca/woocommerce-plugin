<?php
if (!defined('ABSPATH')) {
    $dir = __DIR__;
    while ($dir !== dirname($dir)) {
        $wp_load = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';
        if (file_exists($wp_load)) {
            require_once $wp_load;
            break;
        }
        $dir = dirname($dir);
    }

    if (!defined('ABSPATH')) {
        // WordPress not loaded — abort
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        error_log("[Paytaca Webhook] Error: wp-load.php not found.");
        exit('Critical Error: Could not load WordPress.');
    }
}

$order_id = absint($_GET['order_id'] ?? 0);
$status   = $_GET['status'] ?? '';

$order = wc_get_order($order_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- ✅ Make layout responsive -->
    <title>
        <?php
            if ($status === 'expired') {
                echo 'Invoice Expired';
            } elseif ($status === 'paid') {
                echo 'Payment Successful';
            } else {
                echo 'Invalid Access';
            }
        ?>
    </title>
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
            background: linear-gradient(135deg, #e53935, #0057ff);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .success-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .logo {
            width: 80px;
        }

        h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 12px;
        }

        .error-message {
            color: var(--accent-color);
        }

        .message {
            font-size: 1.1rem;
            margin: 12px 0;
            line-height: 1.5;
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

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .success-box {
                padding: 24px 16px;
            }

            .logo {
                width: 50px;
            }

            h1 {
                font-size: 1.4rem;
            }

            .message,
            .thank-you {
                font-size: 0.95rem;
            }

            .home-btn {
                font-size: 0.95rem;
                padding: 10px 20px;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="icon-wrapper">
            <img src="../assets/paytaca-icon.png" alt="Paytaca Logo" class="logo">
        </div>

        <?php if ($status === 'paid'): ?>
            <h1>Payment Successful!</h1>
            <p class="message">
                <?php
                    if ($order_id > 0) {
                        echo '<strong class="reference">Reference:</strong> Order #' . $order_id . '<br>';
                    }
                    echo 'Thank you for your purchase. Your order has been paid.';
                ?>
            </p>
        <?php elseif ($status === 'expired'): ?>
            <h1 class="error-message">Invoice Expired</h1>
            <p class="message">
                <?php
                    if ($order_id > 0) {
                        echo '<strong class="reference">Reference:</strong> Order #' . $order_id . '<br>';
                    }
                    echo 'Unfortunately, your invoice has expired. Please try again or contact support.';
                ?>
            </p>
        <?php else: ?>
            <h1 class="error-message">Invalid Access</h1>
            <p class="message">Invalid or missing transaction. Please try again or contact support.</p>
        <?php endif; ?>

        <a href="<?php echo esc_url(home_url()); ?>" class="home-btn">Return to Homepage</a>
    </div>
</body>
</html>
