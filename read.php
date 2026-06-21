<?php
require_once __DIR__ . '/functions.php';

$records = [];
$dataFile = __DIR__ . '/data/data.csv';

if (is_readable($dataFile)) {
    $handle = fopen($dataFile, 'rb');

    if ($handle !== false) {
        if (flock($handle, LOCK_SH)) {
            while (($row = fgetcsv($handle)) !== false) {
                $record = normalize_csv_row($row);
                if ($record !== null) {
                    $records[] = $record;
                }
            }
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }
}

$records = array_reverse($records);
$totalYears = array_sum(array_map(
    static fn($record) => (int) $record['years'],
    $records
));

$categorySummary = [];
foreach ($records as $record) {
    if (!isset($categorySummary[$record['category']])) {
        $categorySummary[$record['category']] = [
            'count' => 0,
            'years' => 0,
        ];
    }
    $categorySummary[$record['category']]['count']++;
    $categorySummary[$record['category']]['years'] += (int) $record['years'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ManiArc mini01に記録された熱量宣言のアーカイブです。">
    <title>熱量アーカイブ | ManiArc mini01</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="input.php">
            <span class="brand-mark">M</span>
            <span>ManiArc <small>mini01</small></span>
        </a>
        <nav>
            <a class="nav-link" href="input.php">宣言する</a>
            <a class="nav-link is-current" href="read.php">アーカイブ</a>
        </nav>
    </header>

    <main class="archive-shell">
        <section class="archive-heading">
            <div>
                <p class="eyebrow">MY PASSION ARCHIVE</p>
                <h1>わたしの熱量パスポート</h1>
                <p>誰かと比べるためではなく、自分が夢中で生きた時間を確かめる場所。</p>
            </div>
            <div class="record-count">
                <strong><?= count($records) ?></strong>
                <span>PASSPORTS</span>
            </div>
        </section>

        <?php if (isset($_GET['updated'])): ?>
            <p class="update-notice">熱量の形を更新しました。最初の宣言はそのまま残っています。</p>
        <?php endif; ?>

        <?php if (count($records) > 0): ?>
            <section class="archive-total" aria-label="積み上げた熱量">
                <p>今日までに積み上がった、あなたの熱量</p>
                <strong><?= h($totalYears) ?><small> YEARS</small></strong>
                <span><?= count($records) ?>つの夢中を、ひとつのアーカイブに。</span>
            </section>

            <section class="master-passport" id="master-passport">
                <button class="master-passport-card" id="passport-branch-toggle"
                        type="button" aria-expanded="false" aria-controls="genre-branches">
                    <span class="master-passport-label">ALL PASSIONS / ONE PASSPORT</span>
                    <span class="master-passport-hint">
                        <span class="hint-closed">タップして、ジャンルごとにひらく</span>
                        <span class="hint-open">すべての熱量に戻る</span>
                    </span>

                    <span class="master-art" aria-hidden="true">
                        <?php foreach (array_keys($categorySummary) as $index => $category): ?>
                            <span class="master-orbit master-orbit-<?= ($index % 6) + 1 ?>"></span>
                        <?php endforeach; ?>
                        <strong><?= h($totalYears) ?></strong>
                        <small>TOTAL YEARS</small>
                    </span>

                    <span class="master-passport-copy">
                        <strong id="master-passport-title">すべての夢中</strong>
                        <small id="master-passport-meta">
                            <?= count($categorySummary) ?>ジャンル・<?= count($records) ?>つのパスポート
                        </small>
                    </span>
                </button>

                <div class="genre-branches" id="genre-branches" hidden>
                    <button class="genre-branch is-active" type="button" data-category-filter="all">
                        <span class="branch-color branch-all"></span>
                        <strong>すべて</strong>
                        <small><?= count($records) ?> PASSPORTS</small>
                    </button>
                    <?php foreach ($categorySummary as $category => $summary): ?>
                        <button class="genre-branch" type="button"
                                data-category-filter="<?= h($category) ?>"
                                data-years="<?= h($summary['years']) ?>"
                                data-variant="<?= passport_variant($category) ?>">
                            <span class="branch-color branch-<?= passport_variant($category) ?>"></span>
                            <strong><?= h($category) ?></strong>
                            <small><?= h($summary['count']) ?> PASSPORT<?= $summary['count'] === 1 ? '' : 'S' ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="passport-section-heading">
                <p class="eyebrow">PASSPORT COLLECTION</p>
                <h2 id="passport-list-title">すべてのパスポート</h2>
            </div>

            <div class="passport-grid">
                <?php foreach ($records as $record): ?>
                    <?php
                    $seed = $record['created_at'] . $record['target_name'];
                    $variant = passport_variant($seed);
                    $passportCode = strtoupper(substr(hash('sha256', $seed), 0, 12));
                    ?>
                    <article class="passport-card passport-variant-<?= $variant ?>"
                             data-passport-category="<?= h($record['category']) ?>">
                        <div class="passport-topline">
                            <span>MANIARC / PASSION PASSPORT</span>
                            <span><?= h(date('Y.m.d', strtotime($record['created_at']))) ?></span>
                        </div>

                        <div class="passport-art" aria-hidden="true">
                            <span class="orbit orbit-one"></span>
                            <span class="orbit orbit-two"></span>
                            <span class="orbit orbit-three"></span>
                            <strong><?= h($record['years']) ?></strong>
                            <small>YEARS</small>
                        </div>

                        <div class="passport-body">
                            <p class="passport-category"><?= h($record['category']) ?></p>
                            <h2><?= h($record['target_name']) ?></h2>
                            <p class="passport-period">
                                <?= h($record['start_year']) ?> — <?= h(date('Y')) ?>
                            </p>

                            <div class="passport-love-types">
                                <?php foreach ($record['love_types'] as $loveType): ?>
                                    <span><?= h(love_type_label($record['category'], $loveType)) ?></span>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($record['comment'] !== ''): ?>
                                <blockquote><?= nl2br(h($record['comment'])) ?></blockquote>
                            <?php endif; ?>
                        </div>

                        <div class="passport-code">
                            <span>MANIARC&lt;&lt;<?= h($passportCode) ?>&lt;&lt;</span>
                            <span>DECLARED <?= h($record['created_at']) ?></span>
                        </div>
                        <a class="passport-edit-link"
                           href="edit.php?id=<?= h(record_id(
                               $record['created_at'],
                               $record['category'],
                               $record['target_name'],
                               $record['start_year']
                           )) ?>">
                            熱量の形を編集する
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <section class="empty-state">
                <span class="empty-mark">M</span>
                <h2>まだ、パスポートはありません。</h2>
                <p>最初の熱量を宣言すると、ここにあなただけの一枚が生まれます。</p>
                <a class="primary-button" href="input.php">
                    <span>熱量を宣言する</span><span aria-hidden="true">→</span>
                </a>
            </section>
        <?php endif; ?>

        <?php if (count($records) > 0): ?>
            <div class="archive-footer-action">
                <a class="primary-button" href="input.php">
                    <span>新しい熱量を宣言する</span><span aria-hidden="true">＋</span>
                </a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <span>ManiArc mini01</span>
        <span>YOUR PASSION, ARCHIVED.</span>
    </footer>

    <?php if (count($records) > 0): ?>
        <script>
            const masterPassport = document.getElementById('master-passport');
            const branchToggle = document.getElementById('passport-branch-toggle');
            const branches = document.getElementById('genre-branches');
            const branchButtons = [...document.querySelectorAll('[data-category-filter]')];
            const passportCards = [...document.querySelectorAll('[data-passport-category]')];
            const masterTitle = document.getElementById('master-passport-title');
            const masterMeta = document.getElementById('master-passport-meta');
            const listTitle = document.getElementById('passport-list-title');
            const totalYears = <?= (int) $totalYears ?>;
            const totalPassports = <?= count($records) ?>;
            const totalGenres = <?= count($categorySummary) ?>;

            function selectCategory(button) {
                const category = button.dataset.categoryFilter;
                const isAll = category === 'all';

                branchButtons.forEach((item) => {
                    item.classList.toggle('is-active', item === button);
                });

                passportCards.forEach((card) => {
                    const matches = isAll || card.dataset.passportCategory === category;
                    card.hidden = !matches;
                });

                masterPassport.dataset.activeCategory = isAll ? '' : category;
                masterPassport.classList.toggle('is-filtered', !isAll);
                masterPassport.classList.remove(
                    'master-variant-1',
                    'master-variant-2',
                    'master-variant-3',
                    'master-variant-4',
                    'master-variant-5',
                    'master-variant-6'
                );

                if (isAll) {
                    masterTitle.textContent = 'すべての夢中';
                    masterMeta.textContent = `${totalGenres}ジャンル・${totalPassports}つのパスポート`;
                    listTitle.textContent = 'すべてのパスポート';
                    document.querySelector('.master-art strong').textContent = totalYears;
                } else {
                    masterPassport.classList.add(`master-variant-${button.dataset.variant}`);
                    const years = button.dataset.years;
                    const count = passportCards.filter(
                        (card) => card.dataset.passportCategory === category
                    ).length;
                    masterTitle.textContent = category;
                    masterMeta.textContent = `${count}つのパスポート`;
                    listTitle.textContent = `${category}のパスポート`;
                    document.querySelector('.master-art strong').textContent = years;
                }
            }

            branchToggle.addEventListener('click', () => {
                const isOpen = branchToggle.getAttribute('aria-expanded') === 'true';
                branchToggle.setAttribute('aria-expanded', String(!isOpen));
                branches.hidden = isOpen;
                masterPassport.classList.toggle('is-open', !isOpen);

                if (isOpen) {
                    selectCategory(branchButtons[0]);
                }
            });

            branchButtons.forEach((button) => {
                button.addEventListener('click', () => selectCategory(button));
            });
        </script>
    <?php endif; ?>
</body>
</html>
