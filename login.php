<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT Usimpel Inovasi Indonesia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        /* Bagian Kiri - Form Login */
        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #ffffff;
            padding: 2rem;
        }
        
        .login-form {
            width: 100%;
            max-width: 400px;
        }
        
        .logo-mobile {
            display: none;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo-mobile img {
            max-width: 180px;
        }
        
        .login-header {
            margin-bottom: 2.5rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.3;
            font-weight: 700;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-header p {
            color: #5a6c7d;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        
        /* Welcome Icon */
        .welcome-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        /* App Description Styling */
        .app-description {
            background: linear-gradient(135deg, #e8f4fd, #f0f9ff);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3498db;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.1);
        }
        
        .app-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .app-title i {
            color: #3498db;
            font-size: 1.3rem;
        }
        
        .app-subtitle {
            color: #5a6c7d;
            font-size: 0.9rem;
            font-style: italic;
            margin: 0;
        }
        
        .input-group {
            margin-bottom: 1.8rem;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.7rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-group input {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .input-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            background: white;
            transform: translateY(-2px);
        }
        
        .input-group i {
            position: absolute;
            left: 18px;
            top: 50px;
            color: #5a6c7d;
            font-size: 1.3rem;
            transition: color 0.3s ease;
        }
        
        .input-group input:focus + i,
        .input-group input:focus ~ i {
            color: #3498db;
        }
        
        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }
        
        .login-button:hover {
            background: linear-gradient(135deg, #2980b9, #1abc9c);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .login-button:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #5a6c7d;
            font-size: 0.85rem;
        }
        
        /* Bagian Kanan - Brand Area */
        .brand-container {
            flex: 1;
            background: linear-gradient(135deg, #2c3e50, #1a2530);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: white;
        }
        
        .company-logo {
            max-width: 300px;
            margin-bottom: 2rem;
            animation: float 4s ease-in-out infinite;
        }
        
        .company-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .company-tagline {
            font-size: 1.2rem;
            text-align: center;
            max-width: 500px;
            line-height: 1.6;
            color: #ecf0f1;
        }
        
        .company-info {
            position: absolute;
            bottom: 20px;
            color: #d5dbdb;
            font-size: 0.9rem;
            text-align: center;
        }
        
        /* Animasi */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        
        /* Responsiveness */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .brand-container {
                display: none;
            }
            
            .logo-mobile {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Bagian Kiri - Form Login -->
    <div class="login-container">
        <div class="login-form">
            <div class="logo-mobile">
                <img src="assets/logo-usimpel.png" alt="Logo Usimpel" style="max-width: 150px;">
            </div>
            
            <div class="login-header">
                <div class="welcome-icon">
                    <i class="fas fa-network-wired"></i>
                </div>
                <h1>Selamat Datang di Aplikasi Supply Chain Management</h1>
                <p>Silakan masuk ke akun Anda untuk melanjutkan</p>
            </div>
            
        <form action="login_process.php" method="POST">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required>
                    <i class="fas fa-user"></i>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
                    <i class="fas fa-lock"></i>
                </div>
                
                <button type="submit" class="login-button">Masuk</button>
            </form>
            
            <div class="footer">
                <p>&copy; 2024 PT. Usimpel Inovasi Indonesia. Hak Cipta Dilindungi</p>
            </div>
        </div>
    </div>
    
    <!-- Bagian Kanan - Brand Area -->
    <div class="brand-container">
        <img src="assets/logo-usimpel.png" alt="Logo Usimpel" class="company-logo" style="max-width: 250px; background: white; padding: 25px; border-radius: 15px; margin-bottom: 2rem;">
        
        <div class="company-name">PT Usimpel Inovasi Indonesia</div>
        
        <div class="company-tagline">
            Menyediakan koneksi internet broadband nirkabel yang cepat, stabil, aman, dan terjangkau bagi masyarakat Indonesia sejak 2024.
        </div>
        
        <div class="company-info">
            <p>Powered by PT Usimpel Inovasi Indonesia</p>
        </div>
    </div>

</body>
</html>