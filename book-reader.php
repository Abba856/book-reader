<?php
	$webRoot = __DIR__;
	$booksDir = $webRoot . '/Books';
	$allowedExtensions = ['pdf', 'txt', 'epub'];

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

			$path = $item->getPathname();
			$realPath = realpath($path);
			if ($realPath === false || strncmp($realPath, $booksRealPath . DIRECTORY_SEPARATOR, strlen($booksRealPath) + 1) !== 0) {
				continue;
			}

			$fileName = $item->getFilename();
			$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

			if (!in_array($ext, $allowedExtensions, true)) {
				continue;
			}

			$relativePath = ltrim(str_replace('\\', '/', substr($realPath, strlen($booksRealPath))), '/');
			$encodedRelativePath = implode('/', array_map('rawurlencode', explode('/', $relativePath)));

			$books[] = [
				'name' => $fileName,
				'relative' => $relativePath,
				'display' => pathinfo($relativePath, PATHINFO_FILENAME),
				'ext' => $ext,
				'url' => './Books/' . $encodedRelativePath,
				'size' => $item->getSize() ?: 0,
				'modified' => $item->getMTime() ?: 0
			];
		}
	}

	usort($books, function ($a, $b) {
		return strcasecmp($a['name'], $b['name']);
	});

	$bookMap = [];
	foreach ($books as $book) {
		$bookMap[$book['relative']] = $book;
	}

	$selectedInput = isset($_GET['book']) ? ltrim(str_replace('\\', '/', (string)$_GET['book']), '/') : '';
	$selectedBook = $selectedInput && isset($bookMap[$selectedInput]) ? $bookMap[$selectedInput] : ($books[0] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Book Reader</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		:root {
			--bg: #f4f7ff;
			--surface: #ffffff;
			--surface-soft: #f7f9ff;
			--text: #121826;
			--text-soft: #5a6579;
			--border: rgba(18, 24, 38, 0.1);
			--brand: #5b7cfa;
			--brand-2: #7a5cff;
			--shadow: 0 20px 40px rgba(22, 30, 50, 0.1);
			--radius: 16px;
			--radius-sm: 12px;
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

		* { box-sizing: border-box; margin: 0; padding: 0; }

		body {
			min-height: 100vh;
			font-family: Inter, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
			color: var(--text);
			background:
				radial-gradient(circle at 8% 0%, rgba(122, 92, 255, 0.2), transparent 35%),
				radial-gradient(circle at 95% 0%, rgba(91, 124, 250, 0.15), transparent 35%),
				var(--bg);
			padding: 22px;
		}

		.container {
			width: min(1280px, 100%);
			margin: 0 auto;
		}

		.topbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 16px;
		}

		.title h1 {
			font-size: clamp(1.3rem, 3vw, 2rem);
			margin-bottom: 4px;
		}

		.title p {
			color: var(--text-soft);
			font-size: 0.94rem;
		}

		.actions {
			display: flex;
			gap: 10px;
		}

		.btn {
			border: 1px solid var(--border);
			background: var(--surface);
			color: var(--text);
			border-radius: 999px;
			padding: 10px 14px;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-size: 0.92rem;
			font-weight: 600;
			cursor: pointer;
		}

		.btn.primary {
			background: linear-gradient(135deg, var(--brand), var(--brand-2));
			color: #fff;
			border-color: transparent;
		}

		.layout {
			display: grid;
			grid-template-columns: 330px 1fr;
			gap: 14px;
			min-height: calc(100vh - 120px);
		}

		.panel {
			background: var(--surface);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			box-shadow: var(--shadow);
			overflow: hidden;
		}

		.sidebar {
			display: flex;
			flex-direction: column;
		}

		.sidebar-head {
			padding: 14px;
			border-bottom: 1px solid var(--border);
		}

		.sidebar-head h2 {
			font-size: 1.05rem;
			margin-bottom: 10px;
		}

		#bookSearch {
			width: 100%;
			border: 1px solid var(--border);
			border-radius: 10px;
			background: var(--surface-soft);
			color: var(--text);
			padding: 10px 12px;
		}

		.book-list {
			list-style: none;
			padding: 10px;
			display: grid;
			gap: 8px;
			overflow: auto;
		}

		.book-item a {
			text-decoration: none;
			color: inherit;
			display: block;
			border: 1px solid var(--border);
			border-radius: 10px;
			background: var(--surface-soft);
			padding: 10px;
		}

		.book-item a.active {
			border-color: rgba(91, 124, 250, 0.5);
			background: rgba(91, 124, 250, 0.12);
		}

		.book-name {
			font-size: 0.92rem;
			line-height: 1.3;
			margin-bottom: 4px;
			word-break: break-word;
		}

		.book-meta {
			color: var(--text-soft);
			font-size: 0.78rem;
		}

		.viewer {
			display: flex;
			flex-direction: column;
		}

		.viewer-head {
			border-bottom: 1px solid var(--border);
			padding: 14px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 10px;
		}

		.viewer-title {
			font-size: 1rem;
			line-height: 1.35;
			word-break: break-word;
		}

		.viewer-body {
			padding: 14px;
			height: 100%;
		}

		.frame {
			width: 100%;
			height: calc(100vh - 240px);
			border: 1px solid var(--border);
			border-radius: 12px;
			background: #fff;
		}

		.text-view {
			height: calc(100vh - 240px);
			border: 1px solid var(--border);
			border-radius: 12px;
			background: var(--surface-soft);
			padding: 14px;
			overflow: auto;
			white-space: pre-wrap;
			line-height: 1.55;
			font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
			font-size: 0.9rem;
		}

		.empty {
			border: 1px dashed var(--border);
			border-radius: 12px;
			color: var(--text-soft);
			padding: 24px;
			text-align: center;
			background: var(--surface-soft);
		}

		@media (max-width: 980px) {
			.layout { grid-template-columns: 1fr; min-height: auto; }
			.frame, .text-view { height: 68vh; }
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="topbar">
			<div class="title">
				<h1><i class="fas fa-book-open-reader"></i> Book Reader</h1>
				<p>Read your files from the Books folder in one place.</p>
			</div>
			<div class="actions">
				<a class="btn" href="./index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
				<button class="btn primary" id="themeToggle" type="button"><i class="fas fa-moon"></i> Theme</button>
			</div>
		</div>

		<div class="layout">
			<aside class="panel sidebar">
				<div class="sidebar-head">
					<h2>Books (<?= count($books) ?>)</h2>
					<input type="text" id="bookSearch" placeholder="Search books..." autocomplete="off">
				</div>
				<ul class="book-list" id="bookList">
					<?php if (empty($books)): ?>
						<li class="book-item"><div class="empty">No supported books in <strong>/Books</strong>.</div></li>
					<?php else: ?>
						<?php foreach ($books as $book):
							$isActive = $selectedBook && $selectedBook['relative'] === $book['relative'];
							$sizeKb = max(1, (int)round($book['size'] / 1024));
							$modified = $book['modified'] > 0 ? date('Y-m-d', $book['modified']) : 'Unknown';
						?>
							<li class="book-item" data-title="<?= htmlspecialchars(strtolower($book['display'])) ?>">
								<a href="?book=<?= rawurlencode($book['relative']) ?>" class="<?= $isActive ? 'active' : '' ?>">
									<div class="book-name"><?= htmlspecialchars($book['display']) ?></div>
									<div class="book-meta"><?= htmlspecialchars(dirname($book['relative']) === '.' ? '/Books' : '/Books/' . dirname($book['relative'])) ?></div>
									<div class="book-meta"><?= strtoupper($book['ext']) ?> • <?= $sizeKb ?> KB • <?= htmlspecialchars($modified) ?></div>
								</a>
							</li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</aside>

			<section class="panel viewer">
				<div class="viewer-head">
					<div class="viewer-title">
						<?= $selectedBook ? htmlspecialchars($selectedBook['display']) : 'No book selected' ?>
					</div>
					<?php if ($selectedBook): ?>
						<a class="btn" href="<?= htmlspecialchars($selectedBook['url']) ?>" target="_blank" rel="noopener noreferrer">
							<i class="fas fa-up-right-from-square"></i> Open file
						</a>
					<?php endif; ?>
				</div>
				<div class="viewer-body">
					<?php if (!$selectedBook): ?>
						<div class="empty">Add books into <strong>/Books</strong> with PDF, TXT, or EPUB extension.</div>
					<?php elseif ($selectedBook['ext'] === 'pdf'): ?>
						<iframe class="frame" src="<?= htmlspecialchars($selectedBook['url']) ?>"></iframe>
					<?php elseif ($selectedBook['ext'] === 'txt'): ?>
						<?php
							$txtPath = $booksDir . '/' . $selectedBook['relative'];
							$txtContent = @file_get_contents($txtPath);
							if ($txtContent === false) {
								$txtContent = 'Unable to read this text file.';
							}
						?>
						<div class="text-view"><?= htmlspecialchars($txtContent) ?></div>
					<?php else: ?>
						<div class="empty">
							EPUB preview is not embedded yet. Use <strong>Open file</strong> to view it.
						</div>
					<?php endif; ?>
				</div>
			</section>
		</div>
	</div>

	<script>
		const themeToggle = document.getElementById('themeToggle');
		const themeIcon = themeToggle.querySelector('i');
		const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
		const savedTheme = localStorage.getItem('theme');
		const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');

		function setTheme(theme) {
			const dark = theme === 'dark';
			document.body.classList.toggle('dark', dark);
			themeIcon.classList.toggle('fa-sun', dark);
			themeIcon.classList.toggle('fa-moon', !dark);
			localStorage.setItem('theme', dark ? 'dark' : 'light');
		}

		setTheme(initialTheme);

		themeToggle.addEventListener('click', () => {
			const isDark = document.body.classList.contains('dark');
			setTheme(isDark ? 'light' : 'dark');
		});

		const bookSearch = document.getElementById('bookSearch');
		const rows = Array.from(document.querySelectorAll('.book-item[data-title]'));

		if (bookSearch && rows.length) {
			bookSearch.addEventListener('input', () => {
				const q = bookSearch.value.trim().toLowerCase();
				rows.forEach((row) => {
					const title = row.dataset.title || '';
					row.style.display = !q || title.includes(q) ? '' : 'none';
				});
			});
		}
	</script>
</body>
</html>
