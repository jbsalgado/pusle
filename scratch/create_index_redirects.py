import os

project_root = "/srv/http/pulse-plus"

# List of folders where we only want to write index.html at their top level (no recursion)
toplevel_only_dirs = {
    "vendor",
    "node_modules",
    "ThermalPrintDriver_App",
    "rawbtclone",
    "impressao_flutter",
    "pulse_app",
    "runtime",
    "scratch",
    ".gradle",
    ".idea",
    "build",
    "assets"
}

# Template for index.html (split signature to avoid matching itself)
title_part1 = "Redirecionando..."
title_part2 = " | Pulse Plus"
template = """<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TITLE_PLACEHOLDER</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #f8fafc;
            overflow: hidden;
        }
        .container {
            text-align: center;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            animation: fadeIn 0.6s ease-out;
        }
        .logo-placeholder {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite ease-in-out;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 0.75rem 0;
            letter-spacing: -0.025em;
        }
        p {
            color: #94a3b8;
            font-size: 0.95rem;
            margin: 0 0 2rem 0;
            line-height: 1.5;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border-top-color: #3b82f6;
            margin: 0 auto 2rem auto;
            animation: spin 1s linear infinite;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2), 0 2px 4px -1px rgba(37, 99, 235, 0.1);
        }
        .btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3), 0 4px 6px -2px rgba(37, 99, 235, 0.3);
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.05); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-placeholder">🔒</div>
        <div class="spinner"></div>
        <h1>Acesso Restrito</h1>
        <p>Por motivos de segurança, você está sendo redirecionado para a tela de login...</p>
        <a id="redirect-link" href="#" class="btn">Clique aqui se não for redirecionado</a>
    </div>

    <script type="text/javascript">
        (function() {
            var pathname = window.location.pathname;
            var targetUrl = '/index.php/auth/login'; // Fallback default (Scenario 1)

            if (pathname.includes('/pulse-plus')) {
                var parts = pathname.split('/pulse-plus');
                var prefix = parts[0]; // e.g. '/alex-birds' or '/alex-bird' or ''
                targetUrl = prefix + '/pulse-plus/web/index.php/auth/login';
            }

            // Update fallback link
            document.getElementById('redirect-link').href = targetUrl;
            
            // Perform redirect
            window.location.href = targetUrl;
        })();
    </script>
</body>
</html>
""".replace("TITLE_PLACEHOLDER", title_part1 + title_part2)

def create_index_file(path):
    global count_created, count_skipped, count_updated
    index_html_path = os.path.join(path, "index.html")

    # If it is the web root, DO NOT overwrite it (contains custom landing page)
    if path == os.path.join(project_root, "web"):
        if os.path.exists(index_html_path):
            count_skipped += 1
            return

    # Check if index.html already exists
    if os.path.exists(index_html_path):
        try:
            with open(index_html_path, "r", encoding="utf-8") as f:
                content = f.read()
            # If it's one of our generated redirect files, we overwrite it to update it
            if "Redirecionando..." in content and "Pulse Plus" in content:
                with open(index_html_path, "w", encoding="utf-8") as f:
                    f.write(template)
                count_updated += 1
            else:
                # Custom app page, do not touch
                count_skipped += 1
        except Exception as e:
            print(f"Error checking {index_html_path}: {e}")
            count_skipped += 1
    else:
        # File doesn't exist, create it
        try:
            with open(index_html_path, "w", encoding="utf-8") as f:
                f.write(template)
            print(f"Created: {index_html_path}")
            count_created += 1
        except Exception as e:
            print(f"Error creating {index_html_path}: {e}")
            count_skipped += 1

def generate_index_files():
    global count_created, count_skipped, count_updated
    count_created = 0
    count_skipped = 0
    count_updated = 0

    for dirpath, dirnames, filenames in os.walk(project_root):
        is_root = (dirpath == project_root)

        if is_root:
            new_dirnames = []
            for d in dirnames:
                if d == ".git":
                    continue
                elif d in toplevel_only_dirs:
                    # Write index.html to the top level of this folder only (do not recurse)
                    toplevel_path = os.path.join(project_root, d)
                    create_index_file(toplevel_path)
                else:
                    new_dirnames.append(d)
            dirnames[:] = new_dirnames
        else:
            # Recursively allowed directory
            dirnames[:] = [d for d in dirnames if d not in toplevel_only_dirs and d != ".git"]

        # Do not write index.html inside the scratch folder itself
        if os.path.basename(dirpath) == "scratch":
            continue

        create_index_file(dirpath)

    print("\nSummary:")
    print(f"Total index.html created: {count_created}")
    print(f"Total index.html updated: {count_updated}")
    print(f"Total directories skipped: {count_skipped}")

if __name__ == "__main__":
    generate_index_files()
