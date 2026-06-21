<?php
require_once __DIR__ . '/functions.php';

$dataFile = __DIR__ . '/data/data.csv';
$requestedId = trim((string) ($_POST['id'] ?? $_GET['id'] ?? ''));
$record = null;
$errors = [];
$saved = false;

if ($requestedId === '') {
    $errors[] = '編集するパスポートが指定されていません。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && count($errors) === 0) {
    $targetName = trim($_POST['target_name'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $loveTypes = parse_love_types($_POST['love_types'] ?? []);

    if ($targetName === '') {
        $errors[] = '対象名を入力してください。';
    } elseif (text_length($targetName) > 100) {
        $errors[] = '対象名は100文字以内で入力してください。';
    }

    if (count($loveTypes) === 0) {
        $errors[] = '愛の形を1つ以上選択してください。';
    }

    if (text_length($comment) > 500) {
        $errors[] = 'ひとことは500文字以内で入力してください。';
    }

    if (count($errors) === 0) {
        $handle = fopen($dataFile, 'c+b');

        if ($handle === false || !flock($handle, LOCK_EX)) {
            $errors[] = 'アーカイブを更新できませんでした。';
            if (is_resource($handle)) {
                fclose($handle);
            }
        } else {
            $records = [];
            $found = false;
            rewind($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $item = normalize_csv_row($row);
                if ($item === null) {
                    continue;
                }

                $itemId = record_id(
                    $item['created_at'],
                    $item['category'],
                    $item['target_name'],
                    $item['start_year']
                );

                if ($itemId === $requestedId) {
                    $item['target_name'] = $targetName;
                    $item['love_types'] = $loveTypes;
                    $item['comment'] = $comment;
                    $item['years'] = max(0, (int) date('Y') - (int) $item['start_year']);
                    $record = $item;
                    $found = true;
                }

                $records[] = $item;
            }

            if (!$found) {
                $errors[] = '編集するパスポートが見つかりませんでした。';
            } else {
                rewind($handle);
                ftruncate($handle, 0);
                $writeSucceeded = true;

                foreach ($records as $item) {
                    if (fputcsv($handle, record_to_csv_row($item)) === false) {
                        $writeSucceeded = false;
                        break;
                    }
                }

                fflush($handle);

                if ($writeSucceeded) {
                    $saved = true;
                } else {
                    $errors[] = '変更内容の保存に失敗しました。';
                }
            }

            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && count($errors) === 0 && is_readable($dataFile)) {
    $handle = fopen($dataFile, 'rb');

    if ($handle !== false) {
        if (flock($handle, LOCK_SH)) {
            while (($row = fgetcsv($handle)) !== false) {
                $item = normalize_csv_row($row);
                if ($item === null) {
                    continue;
                }

                if (record_id(
                    $item['created_at'],
                    $item['category'],
                    $item['target_name'],
                    $item['start_year']
                ) === $requestedId) {
                    $record = $item;
                    break;
                }
            }
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }

    if ($record === null) {
        $errors[] = '編集するパスポートが見つかりませんでした。';
    }
}

if ($saved) {
    header('Location: read.php?updated=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>熱量の形を編集する | ManiArc mini01</title>
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

    <main class="page-shell edit-shell">
        <section class="intro edit-intro">
            <p class="eyebrow">UPDATE YOUR PASSION</p>
            <h1>熱量の形を、<br>いまの自分に。</h1>
            <p>愛し方は、時間とともに変わっていくもの。<br>ジャンルと開始年は、最初の宣言として残ります。</p>
        </section>

        <?php if ($record !== null): ?>
            <form class="declaration-card" method="post">
                <input type="hidden" name="id" value="<?= h($requestedId) ?>">

                <div class="card-heading">
                    <div>
                        <p class="card-label">PASSPORT UPDATE</p>
                        <h2><?= h($record['target_name']) ?></h2>
                    </div>
                    <span class="document-number">EDIT / 01</span>
                </div>

                <?php if (count($errors) > 0): ?>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="locked-fields">
                    <div>
                        <span>ジャンル</span>
                        <strong><?= h($record['category']) ?></strong>
                        <small>最初の宣言として固定されています</small>
                    </div>
                    <div>
                        <span>夢中になった年</span>
                        <strong><?= h($record['start_year']) ?>年</strong>
                        <small>最初の宣言として固定されています</small>
                    </div>
                </div>

                <div class="field">
                    <label for="target_name">
                        <span class="field-number">01</span>
                        対象名
                        <span class="required">必須</span>
                    </label>
                    <input id="target_name" name="target_name" type="text" maxlength="100"
                           value="<?= h($_POST['target_name'] ?? $record['target_name']) ?>" required>
                </div>

                <fieldset class="field checkbox-field">
                    <legend>
                        <span class="field-number">02</span>
                        いま、どんな形で愛していますか？
                        <span class="required">1つ以上</span>
                    </legend>
                    <p class="field-note">過去の形を残す必要はありません。いまの感覚を選んでください。</p>
                    <div class="checkbox-grid">
                        <?php
                        $selectedLoveTypes = $_SERVER['REQUEST_METHOD'] === 'POST'
                            ? parse_love_types($_POST['love_types'] ?? [])
                            : $record['love_types'];
                        ?>
                        <?php foreach (LOVE_TYPES as $loveType): ?>
                            <label class="checkbox-option">
                                <input type="checkbox" name="love_types[]" value="<?= h($loveType) ?>"
                                       <?= in_array($loveType, $selectedLoveTypes, true) ? 'checked' : '' ?>>
                                <span class="custom-check" aria-hidden="true"></span>
                                <span class="love-type-copy">
                                    <strong><?= h(love_type_label($record['category'], $loveType)) ?></strong>
                                    <small><?= h($loveType) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="field">
                    <label for="comment">
                        <span class="field-number">03</span>
                        いまの熱量について、ひとこと
                        <span class="optional">任意</span>
                    </label>
                    <textarea id="comment" name="comment" rows="4" maxlength="500"><?= h($_POST['comment'] ?? $record['comment']) ?></textarea>
                </div>

                <div class="form-footer">
                    <a class="text-link" href="read.php">変更せずに戻る</a>
                    <button class="primary-button" type="submit">
                        <span>変更を保存する</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <section class="result-card error-card">
                <p class="eyebrow">PASSPORT NOT FOUND</p>
                <h1>編集できませんでした。</h1>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="primary-button" href="read.php">アーカイブへ戻る</a>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <span>ManiArc mini01</span>
        <span>YOUR PASSION, ARCHIVED.</span>
    </footer>
</body>
</html>
