<?php
require_once __DIR__ . '/functions.php';

$currentYear = (int) date('Y');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="何かに夢中でいた時間を記録する、ManiArc mini01の熱量宣言フォームです。">
    <title>熱量を宣言する | ManiArc mini01</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="input.php" aria-label="ManiArc mini01 ホーム">
            <span class="brand-mark">M</span>
            <span>ManiArc <small>mini01</small></span>
        </a>
        <nav>
            <a class="nav-link is-current" href="input.php">宣言する</a>
            <a class="nav-link" href="read.php">アーカイブ</a>
        </nav>
    </header>

    <main class="page-shell">
        <section class="intro">
            <p class="eyebrow">DECLARE YOUR PASSION</p>
            <h1>あなたの熱量を、<br>宣言しよう。</h1>
            <p>名前も、証拠も、誰かの評価も必要ありません。<br>あなたが夢中でいた時間を、自分のために残します。</p>
        </section>

        <form class="declaration-card" action="write.php" method="post">
            <div class="card-heading">
                <div>
                    <p class="card-label">HEAT DECLARATION</p>
                    <h2>熱量宣言書</h2>
                </div>
                <span class="document-number">MINI / 01</span>
            </div>

            <div class="field">
                <label for="category">
                    <span class="field-number">01</span>
                    何への熱量ですか？
                    <span class="required">必須</span>
                </label>
                <div class="select-wrap">
                    <select id="category" name="category" required>
                        <option value="" selected disabled>ジャンルを選択してください</option>
                        <?php foreach (CATEGORIES as $category): ?>
                            <option value="<?= h($category) ?>"><?= h($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="target_name">
                    <span class="field-number">02</span>
                    誰・何が、あなたをここに連れてきましたか？
                    <span class="required">必須</span>
                </label>
                <input id="target_name" name="target_name" type="text" maxlength="100"
                       placeholder="例：BTS、阪神タイガース、ブルーロック、ワイン" required>
            </div>

            <div class="field field-narrow">
                <label for="start_year">
                    <span class="field-number">03</span>
                    いつから夢中でしたか？
                    <span class="required">必須</span>
                </label>
                <div class="year-input">
                    <input id="start_year" name="start_year" type="number"
                        min="1900" max="<?= $currentYear ?>" inputmode="numeric"
                        placeholder="2024" required>
                    <span>年から</span>
                </div>
            </div>

            <fieldset class="field checkbox-field">
                <legend>
                    <span class="field-number">04</span>
                    どんな形で愛してきましたか？
                    <span class="required">1つ以上</span>
                </legend>
                <p class="field-note" id="love-types-note">あなたの感覚に近いものを、すべて選んでください。</p>
                <div class="checkbox-grid" aria-describedby="love-types-note">
                    <?php foreach (LOVE_TYPES as $loveType): ?>
                        <label class="checkbox-option">
                            <input type="checkbox" name="love_types[]" value="<?= h($loveType) ?>">
                            <span class="custom-check" aria-hidden="true"></span>
                            <span class="love-type-copy">
                                <strong><?= h($loveType) ?></strong>
                                <small><?= h(love_type_label('その他', $loveType)) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="field">
                <label for="comment">
                    <span class="field-number">05</span>
                    その熱量について、ひとこと
                    <span class="optional">任意</span>
                </label>
                <textarea id="comment" name="comment" rows="4" maxlength="500"
                        placeholder="例：ライブでの記憶が、自分の支えになっている"></textarea>
                <p class="character-count"><span id="comment-count">0</span> / 500</p>
            </div>

            <div class="form-footer">
                <p>個人名は保存しません。宣言はあなたのアーカイブに記録されます。</p>
                <button class="primary-button" type="submit">
                    <span>熱量を宣言する</span>
                    <span aria-hidden="true">→</span>
                </button>
            </div>
        </form>
    </main>

    <footer class="site-footer">
        <span>ManiArc mini01</span>
        <span>YOUR PASSION, ARCHIVED.</span>
    </footer>

    <script>
        const comment = document.getElementById('comment');
        const count = document.getElementById('comment-count');
        comment.addEventListener('input', () => {
            count.textContent = [...comment.value].length;
        });

        const form = document.querySelector('form');
        const category = document.getElementById('category');
        const loveTypes = [...document.querySelectorAll('input[name="love_types[]"]')];
        const loveTypeLabels = <?= json_encode(LOVE_TYPE_LABELS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        function updateLoveTypeLabels() {
            const labels = loveTypeLabels[category.value] || loveTypeLabels['その他'];
            loveTypes.forEach((checkbox) => {
                const copy = checkbox.closest('.checkbox-option').querySelector('.love-type-copy');
                copy.querySelector('strong').textContent = labels[checkbox.value] || checkbox.value;
                copy.querySelector('small').textContent = checkbox.value;
            });
        }

        category.addEventListener('change', updateLoveTypeLabels);
        updateLoveTypeLabels();

        form.addEventListener('submit', (event) => {
            const firstCheckbox = loveTypes[0];
            firstCheckbox.setCustomValidity(
                loveTypes.some((checkbox) => checkbox.checked)
                    ? ''
                    : '愛の形を1つ以上選択してください。'
            );
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
        loveTypes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => loveTypes[0].setCustomValidity(''));
        });
    </script>
</body>
</html>
