<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Реєстрація - GLF BiKube</title>
    <style>
        body {
            font-family: sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo h1 {
            color: #667eea;
            font-size: 1.75rem;
            font-weight: bold;
            margin: 0;
        }
        .api-info {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .api-info h3 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin: 0 0 0.5rem 0;
        }
        .api-info p {
            font-size: 0.75rem;
            color: #6b7280;
            margin: 0.25rem 0;
        }
        .code-block {
            background: #1f2937;
            color: #10b981;
            padding: 0.75rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            overflow-x: auto;
            margin: 0.5rem 0;
        }
        .code-block code {
            color: inherit;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            width: 100%;
            text-align: center;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            margin-top: 1rem;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>🚴 GLF BiKube</h1>
        </div>
        
        <div class="api-info">
            <h3>📝 Використовуй API для реєстрації:</h3>
            <p><strong>Endpoint:</strong></p>
            <div class="code-block">
POST /api/v1/register
            </div>
            <p><strong>Приклад запиту:</strong></p>
            <div class="code-block">
{
  "name": "Ваше Ім'я",
  "email": "email@example.com",
  "password": "password"
}
            </div>
        </div>

        <a href="/admin/login" class="btn btn-secondary">
            ← Повернутися до входу
        </a>

        <div class="login-link">
            Вже маєте акаунт? 
            <a href="/admin/login">Увійти</a>
        </div>
    </div>
</body>
</html>

