<?php
// Check if this is a request for the root URL (deployment status page)
$request_uri = $_SERVER['REQUEST_URI'];
$is_root_request = $request_uri === '/' || $request_uri === '/index.php' || $request_uri === '';

if ($is_root_request) {
    // Show deployment status page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AutoWash Hub API - Deployment Status</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #333;
            }
            
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 40px;
                text-align: center;
                max-width: 600px;
                width: 90%;
                position: relative;
                overflow: hidden;
            }
            
            .container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 5px;
                background: linear-gradient(90deg, #4CAF50, #2196F3, #9C27B0);
            }
            
            .status-icon {
                width: 80px;
                height: 80px;
                background: #4CAF50;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                animation: pulse 2s infinite;
            }
            
            .status-icon svg {
                width: 40px;
                height: 40px;
                fill: white;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            h1 {
                color: #2c3e50;
                margin-bottom: 10px;
                font-size: 2.5em;
                font-weight: 300;
            }
            
            .subtitle {
                color: #7f8c8d;
                font-size: 1.2em;
                margin-bottom: 30px;
            }
            
            .status-message {
                background: #e8f5e8;
                border: 1px solid #4CAF50;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                color: #2e7d32;
            }
            
            .deployment-info {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            }
            
            .deployment-info h3 {
                color: #495057;
                margin-bottom: 15px;
                font-size: 1.1em;
            }
            
            .info-item {
                display: flex;
                justify-content: space-between;
                margin: 10px 0;
                padding: 8px 0;
                border-bottom: 1px solid #e9ecef;
            }
            
            .info-item:last-child {
                border-bottom: none;
            }
            
            .info-label {
                font-weight: 600;
                color: #6c757d;
            }
            
            .info-value {
                color: #495057;
                font-family: 'Courier New', monospace;
            }
            
            .api-endpoints {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .api-endpoints h3 {
                color: #856404;
                margin-bottom: 15px;
            }
            
            .endpoint {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
                margin: 5px 0;
                font-family: 'Courier New', monospace;
                font-size: 0.9em;
                color: #495057;
            }
            
            .cors-testing {
                background: #e3f2fd;
                border: 1px solid #90caf9;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }
            
            .cors-testing h3 {
                color: #1565c0;
                margin-bottom: 15px;
            }
            
            .cors-testing p {
                color: #1976d2;
                margin-bottom: 15px;
                line-height: 1.5;
            }
            
            .test-links {
                display: flex;
                gap: 15px;
                margin: 15px 0;
                flex-wrap: wrap;
            }
            
            .test-link {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #2196f3;
                color: white;
                text-decoration: none;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
            }
            
            .test-link:hover {
                background: #1976d2;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(33, 150, 243, 0.4);
            }
            
            .test-icon {
                font-size: 1.2em;
            }
            
            .test-note {
                font-size: 0.9em;
                color: #0d47a1;
                font-style: italic;
                margin-top: 15px;
            }
            
            .footer {
                margin-top: 30px;
                color: #6c757d;
                font-size: 0.9em;
            }
            
            .footer a {
                color: #667eea;
                text-decoration: none;
            }
            
            .footer a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="status-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>
            
            <h1>AutoWash Hub API</h1>
            <p class="subtitle">Backend API Service</p>
            
            <div class="status-message">
                <strong>✅ Deployment Status: SUCCESSFUL</strong><br>
                Your API is now live and ready to serve requests!
            </div>
            
            <div class="deployment-info">
                <h3>📊 Deployment Information</h3>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value">🟢 Online</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Server:</span>
                    <span class="info-value">InfinityFree</span>
                </div>
                <div class="info-item">
                    <span class="info-label">PHP Version:</span>
                    <span class="info-value"><?php echo phpversion(); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Timestamp:</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i:s T'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Request URI:</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Test Files:</span>
                    <span class="info-value">📁 /test-cors.html, /test-post-cors.html</span>
                </div>
            </div>
            
            <div class="api-endpoints">
                <h3>🔗 Available API Endpoints</h3>
                <div class="endpoint">GET /get_customer_count</div>
                <div class="endpoint">GET /get_employee_count</div>
                <div class="endpoint">GET /get_all_customers</div>
                <div class="endpoint">GET /get_all_employees</div>
                <div class="endpoint">POST /add_customer</div>
                <div class="endpoint">POST /add_employee</div>
                <div class="endpoint">PUT /update_customer</div>
                <div class="endpoint">PUT /update_employee</div>
                <div class="endpoint">And many more...</div>
            </div>
            
            <div class="cors-testing">
                <h3>🧪 CORS Testing Tools</h3>
                <p>Test your CORS implementation with these tools:</p>
                <div class="test-links">
                    <a href="./test-cors.html" target="_blank" class="test-link">
                        <span class="test-icon">📡</span>
                        Basic CORS Test
                    </a>
                    <a href="./test-post-cors.html" target="_blank" class="test-link">
                        <span class="test-icon">📝</span>
                        POST CORS Test
                    </a>
                </div>
                <p class="test-note">These tools will help verify that your frontend can communicate with this API without CORS issues.</p>
            </div>
            
            <div class="footer">
                <p>🚀 AutoWash Hub API is successfully deployed on InfinityFree</p>
                <p>Frontend: <a href="https://autowash-hub.vercel.app" target="_blank">https://autowash-hub.vercel.app</a></p>
                <p>GitHub: <a href="https://github.com/markaducal" target="_blank">https://github.com/markaducal</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Include the main routes file for API requests
require_once "./routes.php";
?>