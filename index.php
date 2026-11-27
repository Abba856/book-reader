<?php
    $webRoot = __DIR__;
    $directories = array_filter(glob($webRoot . '/*'), 'is_dir');

    // Exclude folders from project list (case-insensitive)
    $exclude = ['phpmyadmin', 'books', '.', '..'];

    $validDirs = array_values(array_filter($directories, function ($dir) use ($exclude) {
        $name = strtolower(basename($dir));
        return !in_array($name, $exclude, true);
    }));

    // Build spotlight projects (up to 3 random projects)
    $randomDirs = $validDirs;
    shuffle($randomDirs);
    $randomDirs = array_slice($randomDirs, 0, 3);

    $projectCount = count($validDirs);

    // Books directory info for Quick Access
    $booksDir = $webRoot . '/Books';
    $bookExtensions = ['pdf', 'txt', 'epub'];
    $books = [];
    $booksRealPath = realpath($booksDir);

    if ($booksRealPath !== false && is_dir($booksDir) && is_readable($booksDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($booksDir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $realPath = realpath($item->getPathname());
            if ($realPath === false || strncmp($realPath, $booksRealPath . DIRECTORY_SEPARATOR, strlen($booksRealPath) + 1) !== 0) {
                continue;
            }

            $ext = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
            if (!in_array($ext, $bookExtensions, true)) {
                continue;
            }

            $books[] = ltrim(str_replace('\\', '/', substr($realPath, strlen($booksRealPath))), '/');
        }
    }

    sort($books, SORT_NATURAL | SORT_FLAG_CASE);
    $booksCount = count($books);
    $year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Dev Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #f4f7ff;
            --surface: #ffffff;
            --surface-soft: #f7f9ff;
            --text: #121826;
            --text-soft: #5a6579;
            --border: rgba(18, 24, 38, 0.08);
            --brand: #5b7cfa;
            --brand-2: #7a5cff;
            --ok: #17b26a;
            --shadow: 0 20px 40px rgba(22, 30, 50, 0.08);
            --radius: 18px;
            --radius-sm: 12px;
            --transition: 220ms ease;
        }

        body.dark {
            --bg: #0c111d;
            --surface: #131b2b;
            --surface-soft: #182238;
            --text: #eff4ff;
            --text-soft: #a9b5cb;
            --border: rgba(239, 244, 255, 0.1);
            --brand: #7b93ff;
            --brand-2: #9c87ff;
            --shadow: 0 24px 50px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Inter, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            background:
                radial-gradient(circle at 5% 0%, rgba(122, 92, 255, 0.2), transparent 40%),
                radial-gradient(circle at 100% 10%, rgba(91, 124, 250, 0.15), transparent 35%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            transition: background-color var(--transition), color var(--transition);
        }

        .container {
            width: min(1200px, 92%);
            margin: 0 auto;
        }

        .hero {
            margin: 28px auto;
            padding: 30px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: linear-gradient(135deg, rgba(91, 124, 250, 0.95), rgba(122, 92, 255, 0.95));
            color: #fff;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 420px;
            height: 420px;
            top: -230px;
            right: -160px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
        }

        .hero-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .hero p {
            color: rgba(255, 255, 255, 0.9);
            max-width: 640px;
        }

        .theme-toggle {
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 999px;
            height: 42px;
            padding: 0 16px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            gap: 10px;
            align-items: center;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .hero-bottom {
            margin-top: 22px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            position: relative;
            z-index: 1;
        }

        .search-wrap {
            position: relative;
        }

        #search {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(7px);
            color: #fff;
            font-size: 1rem;
            padding: 12px 18px 12px 45px;
        }

        #search::placeholder {
            color: rgba(255, 255, 255, 0.85);
        }

        #search:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.85);
        }

        .search-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            opacity: 0.9;
        }

        .stat {
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(7px);
            padding: 12px 16px;
            text-align: center;
        }

        .stat .value {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat .label {
            font-size: 0.86rem;
            color: rgba(255, 255, 255, 0.85);
        }

        main {
            padding-bottom: 28px;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 22px;
            margin-bottom: 22px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
        }

        .panel h2 {
            font-size: 1.22rem;
        }

        .panel small {
            color: var(--text-soft);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 14px;
        }

        .card {
            background: var(--surface-soft);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: inherit;
            padding: 16px;
            transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 180px;
        }

        .card:hover {
            transform: translateY(-4px);
            border-color: rgba(91, 124, 250, 0.35);
            box-shadow: 0 12px 20px rgba(25, 35, 60, 0.12);
        }

        .card-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(91, 124, 250, 0.18), rgba(122, 92, 255, 0.2));
            color: var(--brand);
            font-size: 1.15rem;
        }

        .card h3 {
            font-size: 1.06rem;
            line-height: 1.35;
            word-break: break-word;
        }

        .card p {
            color: var(--text-soft);
            font-size: 0.92rem;
            flex-grow: 1;
        }

        .card .action {
            color: var(--brand);
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }

        .tag {
            align-self: flex-start;
            font-size: 0.72rem;
            padding: 4px 9px;
            border-radius: 999px;
            background: rgba(23, 178, 106, 0.14);
            color: var(--ok);
            border: 1px solid rgba(23, 178, 106, 0.25);
        }

        .empty {
            text-align: center;
            color: var(--text-soft);
            border: 1px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 22px;
            display: none;
        }

        .hidden {
            display: none;
        }

        footer {
            color: var(--text-soft);
            font-size: 0.9rem;
            text-align: center;
            padding: 10px 0 26px;
        }

        footer a {
            color: var(--brand);
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .hero-bottom {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 650px) {
            .hero {
                padding: 22px;
            }

            .hero-top {
                flex-direction: column;
                align-items: stretch;
            }

            .theme-toggle {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="hero">
            <div class="hero-top">
                <div>
                    <h1><i class="fas fa-layer-group"></i> Abba856 Local Dev Dashboard</h1>
                    <p>Fast access to your local tools and projects, with instant filtering and a clean, modern layout.</p>
                </div>
                <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                    <span>Theme</span>
                </button>
            </div>
            <div class="hero-bottom">
                <div class="search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="search" placeholder="Search projects by name..." autocomplete="off">
                </div>
                <div class="stat" aria-live="polite">
                    <div class="value" id="projectCount"><?= $projectCount ?></div>
                    <div class="label">Projects Available</div>
                </div>
            </div>
        </header>

        <main>
            <section class="panel">
                <div class="panel-header">
                    <h2>Quick Access</h2>
                    <small>Core tools and references</small>
                </div>
                <div class="grid">
                    <a href="/phpMyAdmin" class="card">
                        <div class="card-icon"><i class="fas fa-database"></i></div>
                        <span class="tag">Database</span>
                        <h3>phpMyAdmin</h3>
                        <p>Manage MySQL databases from your browser.</p>
                        <span class="action">Open tool <i class="fas fa-arrow-right"></i></span>
                    </a>

                    <a href="https://www.php.net/manual/en/index.php" class="card" target="_blank" rel="noopener noreferrer">
                        <div class="card-icon"><i class="fab fa-php"></i></div>
                        <span class="tag">Reference</span>
                        <h3>PHP Manual</h3>
                        <p>Official documentation and language reference.</p>
                        <span class="action">View docs <i class="fas fa-up-right-from-square"></i></span>
                    </a>

                    <a href="./book-reader.php" class="card">
                        <div class="card-icon"><i class="fas fa-book-open-reader"></i></div>
                        <span class="tag">Reading</span>
                        <h3>Book Reader</h3>
                        <p><?= $booksCount > 0 ? htmlspecialchars($booksCount . ' books available in /Books (including subfolders)') : 'No books found in /Books yet.' ?></p>
                        <span class="action">Open reader <i class="fas fa-arrow-right"></i></span>
                    </a>
                </div>
            </section>

            <?php if (!empty($randomDirs)): ?>
            <section class="panel">
                <div class="panel-header">
                    <h2>Spotlight</h2>
                    <small>Random picks to jump in quickly</small>
                </div>
                <div class="grid">
                    <?php foreach ($randomDirs as $dir):
                        $name = basename($dir);
                    ?>
                    <a href="./<?= rawurlencode($name) ?>/" class="card">
                        <div class="card-icon"><i class="fas fa-bolt"></i></div>
                        <span class="tag">Suggested</span>
                        <h3><?= htmlspecialchars(ucwords(str_replace(['-', '_'], ' ', $name))) ?></h3>
                        <p>Open this project instantly.</p>
                        <span class="action">Open project <i class="fas fa-arrow-right"></i></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <h2>Projects</h2>
                    <small id="resultInfo"><?= $projectCount ?> listed</small>
                </div>

                <div class="grid" id="projectGrid">
                    <?php foreach ($validDirs as $dir):
                        $name = basename($dir);
                        $displayName = ucwords(str_replace(['-', '_'], ' ', $name));
                    ?>
                    <a href="./<?= rawurlencode($name) ?>/" class="card project-card" data-name="<?= htmlspecialchars(strtolower($name)) ?>" data-display="<?= htmlspecialchars(strtolower($displayName)) ?>">
                        <div class="card-icon"><i class="fas fa-folder-tree"></i></div>
                        <span class="tag">Local Project</span>
                        <h3><?= htmlspecialchars($displayName) ?></h3>
                        <p>Open <?= htmlspecialchars($name) ?> and start working.</p>
                        <span class="action">Open project <i class="fas fa-arrow-right"></i></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="empty" id="emptyState">
                    <p><i class="fas fa-circle-info"></i> No projects match your search.</p>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?= $year ?> Local Dev Dashboard • Running on Fedora • <a href="https://github.com/Abba856" target="_blank" rel="noopener noreferrer">GitHub @Abba856</a></p>
        </footer>
    </div>

    <script>
        const searchInput = document.getElementById('search');
        const projectCards = Array.from(document.querySelectorAll('.project-card'));
        const emptyState = document.getElementById('emptyState');
        const resultInfo = document.getElementById('resultInfo');
        const projectCount = document.getElementById('projectCount');

        function applyFilter(term) {
            let visible = 0;
            const query = term.trim().toLowerCase();

            projectCards.forEach((card) => {
                const haystack = `${card.dataset.name} ${card.dataset.display}`;
                const match = !query || haystack.includes(query);
                card.classList.toggle('hidden', !match);
                if (match) visible += 1;
            });

            emptyState.style.display = visible === 0 ? 'block' : 'none';
            resultInfo.textContent = `${visible} ${visible === 1 ? 'result' : 'results'}`;
            projectCount.textContent = visible;
        }

        searchInput.addEventListener('input', () => applyFilter(searchInput.value));

        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const savedTheme = localStorage.getItem('theme');
        const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');

        function setTheme(theme) {
            const isDark = theme === 'dark';
            document.body.classList.toggle('dark', isDark);
            themeIcon.classList.toggle('fa-sun', isDark);
            themeIcon.classList.toggle('fa-moon', !isDark);
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        setTheme(initialTheme);

        themeToggle.addEventListener('click', () => {
            const isDark = document.body.classList.contains('dark');
            setTheme(isDark ? 'light' : 'dark');
        });
    </script>
</body>
</html>
