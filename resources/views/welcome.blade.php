<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .error-code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }

        .title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #f1f5f9;
        }

        .description {
            font-size: 16px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: #6366f1;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #4f46e5;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #1e293b;
            color: #cbd5e1;
            border: 1px solid #334155;
        }

        .btn-secondary:hover {
            background-color: #334155;
            color: #ffffff;
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 90px;
            }

            .title {
                font-size: 22px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="error-code">404</div>
        <h1 class="title">Page Not Found</h1>
    </div>

</body>

</html>