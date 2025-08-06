<?php
if (!defined('ABSPATH')) exit;

$order_id = absint(get_query_var('order_id') ?? 0);
$order = wc_get_order($order_id);

$status = strtolower($_GET['status'] ?? '');

if (in_array($status, ['completed', 'paid']) && WC()->cart && !WC()->cart->is_empty()) {
    WC()->cart->empty_cart();
}

error_log('[Paytaca Debug] Raw $_GET: ' . json_encode($_GET));
error_log('[Paytaca Debug] order_id from get_query_var: ' . get_query_var('order_id'));

if (!$order) {
    $status = 'invalid';
} elseif (empty($status)) {
    $status = $order->get_status(); // completed, cancelled, failed, pending, on-hold
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
            switch ($status) {
                case 'completed': echo 'Payment Successful'; break;
                case 'paid':      echo 'Payment Successful'; break;
                case 'cancelled': echo 'Payment Cancelled'; break;
                case 'expired':   echo 'Payment Expired'; break;
                case 'failed':    echo 'Payment Failed'; break;
                case 'pending':   
                case 'on-hold':   echo 'Awaiting Payment'; break;
                default:          echo 'Invalid Order';
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

        #countdown {
            font-weight: bold;
            margin-top: 10px;
            font-size: 1.1rem;
            color: var(--primary-color);
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
            <img src="<?php echo esc_url(plugins_url('../assets/paytaca-icon.png', __FILE__)); ?>" alt="Paytaca Logo" class="logo">
        </div>

        <?php if (in_array($status, ['completed', 'paid'])): ?>
            <h1>Payment Successful!</h1>
            <p class="message">
                <strong class="reference">Reference:</strong> Order #<?php echo esc_html($order_id); ?><br>
                Thank you for your purchase. Your order has been paid.
            </p>

        <?php elseif ($status === 'cancelled'): ?>
            <h1 class="error-message">Payment Cancelled</h1>
            <p class="message">
                <strong class="reference">Reference:</strong> Order #<?php echo esc_html($order_id); ?><br>
                The payment was cancelled. You can try placing your order again.
            </p>

        <?php elseif ($status === 'expired'): ?>
            <h1 class="error-message">Payment Expired</h1>
            <p class="message">
                <strong class="reference">Reference:</strong> Order #<?php echo esc_html($order_id); ?><br>
                Your payment expired. Please try checking out again.
            </p>

        <?php elseif ($status === 'failed'): ?>
            <h1 class="error-message">Payment Failed</h1>
            <p class="message">
                <strong class="reference">Reference:</strong> Order #<?php echo esc_html($order_id); ?><br>
                Unfortunately, the payment failed. Please try again or contact support.
            </p>

        <?php elseif (in_array($status, ['pending', 'on-hold'])): ?>
            <h1>Awaiting Payment</h1>
            <p class="message">
                <strong class="reference">Reference:</strong> Order #<?php echo esc_html($order_id); ?><br>
                Your payment is still pending. This page will refresh once confirmed.
            </p>
            <p id="countdown">300s</p>
            <script>
                let countdown = 300;
                const countdownEl = document.getElementById('countdown');
                const interval = setInterval(() => {
                    countdown--;
                    countdownEl.textContent = countdown + 's';
                    if (countdown <= 0) clearInterval(interval);
                }, 1000);
            </script>

        <?php else: ?>
            <h1 class="error-message">Invalid Access</h1>
            <p class="message">
                Invalid or missing order. Please check the link or contact support.
            </p>
        <?php endif; ?>

        <a href="<?php echo esc_url(home_url()); ?>" class="home-btn">Return to Homepage</a>
    </div>
</body>
</html>
