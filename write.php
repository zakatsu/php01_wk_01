<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: input.php');
    exit;
}

$category = trim($_POST['category'] ?? '');
$targetName = trim($_POST['target_name'] ?? '');
$startYearRaw = trim((string) ($_POST['start_year'] ?? ''));
$comment = trim($_POST['comment'] ?? '');
$postedLoveTypes = $_POST['love_types'] ?? [];
$loveTypes = is_array($postedLoveTypes)
    ? array_values(array_intersect(LOVE_TYPES, $postedLoveTypes))
    : [];

$currentYear = (int) date('Y');
$startYear = filter_var($startYearRaw, FILTER_VALIDATE_INT);
$errors = [];

if (!in_array($category, CATEGORIES, true)) {
    $errors[] = 'ジャンルを選択してください。';
}

if ($targetName === '') {
    $errors[] = '対象名を入力してください。';
} elseif (text_length($targetName) > 100) {
    $errors[] = '対象名は100文字以内で入力してください。';
}

if ($startYear === false || $startYear < 1900 || $startYear > $currentYear) {
    $errors[] = '開始年は1900年から現在年までの数字で入力してください。';
}

if (count($loveTypes) === 0) {
    $errors[] = '愛の形を1つ以上選択してください。';
}

if (text_length($comment) > 500) {
    $errors[] = 'ひとことは500文字以内で入力してください。';
}

$saved = false;
$years = $startYear !== false ? max(0, $currentYear - $startYear) : 0;

if (count($errors) === 0) {
    $dataDirectory = __DIR__ . '/data';
    $dataFile = $dataDirectory . '/data.csv';

    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0775, true)) {
        $errors[] = '保存先を準備できませんでした。';
    } else {
        $handle = fopen($dataFile, 'ab');

        if ($handle === false) {
            $errors[] = 'アーカイブを開けませんでした。';
        } else {
            if (flock($handle, LOCK_EX)) {
                $saved = fputcsv($handle, [
                    date('Y-m-d H:i:s'),
                    $category,
                    $targetName,
                    $startYear,
                    $years,
                    implode(' / ', $loveTypes),
                    $comment,
                ]) !== false;
                fflush($handle);
                flock($handle, LOCK_UN);
            }
            fclose($handle);

            if (!$saved) {
                $errors[] = '記録の保存に失敗しました。時間をおいてもう一度お試しください。';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $saved ? '記録しました' : '入力内容をご確認ください' ?> | ManiArc mini01</title>
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
            <a class="nav-link" href="read.php">アーカイブ</a>
        </nav>
    </header>

    <main class="result-shell">
        <?php if ($saved): ?>
            <section class="result-card">
                <span class="result-stamp">ARCHIVED</span>
                <p class="eyebrow">PASSPORT ISSUED</p>
                <h1>あなたのパスポートが<br>生まれました。</h1>
                <div class="result-quote">
                    <span>“</span>
                    <p>あなたの<?= h($years) ?>年間は、<br>ここにある。</p>
                    <span>”</span>
                </div>
                <dl class="result-summary">
                    <div>
                        <dt>対象</dt>
                        <dd><?= h($targetName) ?></dd>
                    </div>
                    <div>
                        <dt>開始年</dt>
                        <dd><?= h($startYear) ?>年</dd>
                    </div>
                    <div>
                        <dt>愛の形</dt>
                        <dd>
                            <?php foreach ($loveTypes as $index => $loveType): ?>
                                <?= $index > 0 ? ' / ' : '' ?><?= h(love_type_label($category, $loveType)) ?>
                            <?php endforeach; ?>
                        </dd>
                    </div>
                </dl>
                <div class="result-actions">
                    <a class="primary-button" href="read.php">
                        <span>アーカイブを見る</span><span aria-hidden="true">→</span>
                    </a>
                    <a class="text-link" href="input.php">もう一度宣言する</a>
                </div>
            </section>
        <?php else: ?>
            <section class="result-card error-card">
                <p class="eyebrow">PLEASE CHECK</p>
                <h1>入力内容を<br>確認してください。</h1>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="primary-button" href="input.php">
                    <span>入力画面に戻る</span><span aria-hidden="true">←</span>
                </a>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <span>ManiArc mini01</span>
        <span>YOUR PASSION, ARCHIVED.</span>
    </footer>
</body>
</html>
