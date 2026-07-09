<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Error</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .container { background: #fff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); max-width: 600px; width: 100%; border-top: 5px solid #ef4444; }
        h1 { color: #ef4444; margin-top: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 0.5rem; }
        p { line-height: 1.6; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .code-block { background: #f1f5f9; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem; color: #475569; overflow-x: auto; margin-bottom: 1.5rem; border: 1px solid #e2e8f0; }
        .instruction { background: #eff6ff; padding: 1rem; border-radius: 8px; border: 1px solid #bfdbfe; font-size: 0.9rem; }
        .instruction h3 { margin-top: 0; color: #1d4ed8; font-size: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Database Connection Error
        </h1>
        <p>Aplikasi gagal terhubung ke database. Hal ini biasanya terjadi karena konfigurasi database (Environment Variables) di Vercel belum disetel atau ada yang salah.</p>
        
        <div class="code-block">
            <strong>Detail Error:</strong><br>
            {{ $message ?? 'Unknown Database Error' }}
        </div>

        <div class="instruction">
            <h3>Solusi untuk Vercel:</h3>
            <ol style="margin-bottom: 0; padding-left: 1.2rem;">
                <li>Buka dashboard Vercel &gt; Settings &gt; Environment Variables.</li>
                <li>Pastikan Anda sudah mengisi <strong>DB_HOST</strong>, <strong>DB_PORT</strong>, <strong>DB_DATABASE</strong>, <strong>DB_USERNAME</strong>, dan <strong>DB_PASSWORD</strong>.</li>
                <li>Pastikan Anda menggunakan layanan Cloud Database MySQL (seperti Supabase, Aiven, atau PlanetScale), bukan <em>127.0.0.1</em>.</li>
                <li>Klik tombol <strong>Redeploy</strong> di menu Deployments.</li>
            </ol>
        </div>
    </div>
</body>
</html>
