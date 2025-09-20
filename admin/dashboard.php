<?php
/**
 * Admin Dashboard - Placeholder
 * This file should be created by administrators when needed
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php?user_type=admin&error=login_required");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Paradise Resort</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <nav class="dashboard-nav">
            <div class="nav-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            </div>
            
            <div class="nav-user">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($_SESSION['admin_role']); ?></span>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>
        
        <main class="dashboard-main">
            <div class="dashboard-header">
                <h1>Welcome to Admin Dashboard</h1>
                <p>Successfully logged in with centralized authentication system</p>
            </div>
            
            <div class="dashboard-content">
                <div class="success-card">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Centralized Authentication Active</h3>
                    <p>The new unified login system is working correctly. All authentication is now handled centrally.</p>
                    
                    <div class="admin-actions">
                        <a href="../index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                        <a href="../login.php" class="btn btn-secondary">
                            <i class="fas fa-exchange-alt"></i> Switch User
                        </a>
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="info-card">
                        <h4>Authentication Status</h4>
                        <p><i class="fas fa-check text-success"></i> Centralized system active</p>
                        <p><i class="fas fa-trash text-danger"></i> Old auth files removed</p>
                        <p><i class="fas fa-shield-alt text-primary"></i> CSRF protection enabled</p>
                    </div>
                    
                    <div class="info-card">
                        <h4>Next Steps</h4>
                        <p>• Build admin management features</p>
                        <p>• Add user management tools</p>
                        <p>• Implement booking management</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
        }
        
        .dashboard-nav {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .nav-header h2 {
            color: white;
            margin: 0;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            color: white;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .user-role {
            font-size: 0.8rem;
            opacity: 0.8;
            text-transform: capitalize;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .dashboard-main {
            flex: 1;
            padding: 2rem;
        }
        
        .dashboard-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
        }
        
        .dashboard-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .success-icon {
            font-size: 3rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .admin-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
        }
        
        .info-card h4 {
            margin: 0 0 1rem 0;
            color: #333;
        }
        
        .info-card p {
            margin: 0.5rem 0;
            color: #666;
        }
        
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-primary { color: #007bff; }
    </style>
</body>
</html>
