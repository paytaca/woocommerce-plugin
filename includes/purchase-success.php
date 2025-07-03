<?php
require_once 'C:/xampp/htdocs/wordpress/wp-load.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
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
    <!-- Confetti container -->
    <div class="confetti">
        <?php for ($i = 0; $i < 50; $i++): ?>
            <div class="confetti-piece" style="
                left: <?= rand(0, 100) ?>%;
                animation-delay: <?= rand(0, 2000) / 1000 ?>s;
                animation-duration: <?= rand(2000, 4000) / 1000 ?>s;
                transform: rotate(<?= rand(0, 360) ?>deg);
            "></div>
        <?php endfor; ?>
    </div>

    <div class="success-box">
        <div class="icon-wrapper">
            <span class="emoji">🎉</span>
            <img src="../assets/paytaca-icon.png" alt="Paytaca Logo" class="logo">
            <span class="emoji">🎉</span>
        </div>
        <h1>Payment Successful!</h1>
        <p class="message">Thank you for your purchase.</p>
        <p class="message"><strong class="reference">Reference:</strong> <?php echo $order_id > 0 ? 'Order #' . $order_id : 'Unknown'; ?></p>
        <p class="thank-you">We appreciate your trust in Paytaca.</p>
        <a href="<?php echo esc_url(home_url()); ?>" class="home-btn">Return to Homepage</a>
    </div>
</body>
</html>
