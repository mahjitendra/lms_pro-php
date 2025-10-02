<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 900;
            line-height: 1;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .btn-home {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            margin: 0 10px;
        }
        .btn-home:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        .error-details {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
            Internal Server Error
        </div>
        <p class="mb-4 opacity-75">
            Something went wrong on our end.<br>
            We're working to fix this issue. Please try again later.
        </p>
        
        <a href="/" class="btn-home">
            <i class="fas fa-home me-2"></i>
            Go Back Home
        </a>
        <a href="javascript:location.reload()" class="btn-home">
            <i class="fas fa-redo me-2"></i>
            Try Again
        </a>
        
        <div class="error-details">
            <h5><i class="fas fa-info-circle me-2"></i>What can you do?</h5>
            <ul class="list-unstyled mt-3">
                <li><i class="fas fa-check text-success me-2"></i>Try refreshing the page</li>
                <li><i class="fas fa-check text-success me-2"></i>Go back to the previous page</li>
                <li><i class="fas fa-check text-success me-2"></i>Contact support if the problem persists</li>
            </ul>
        </div>
    </div>
</body>
</html>