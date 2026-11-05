<?php

require_once __DIR__ . '/cors.php';

$dataFile = __DIR__ . '/../data/messages.json';
$messages = [];

if (is_file($dataFile)) {
    $handle = fopen($dataFile, 'r');
    if ($handle) {
        flock($handle, LOCK_SH);
        $messages = json_decode(stream_get_contents($handle), true) ?: [];
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

// Les plus récents en premier
$messages = array_reverse($messages);
$total = count($messages);

function e(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatDate(?string $iso): string
{
    if (!$iso) return '';
    try {
        return (new DateTime($iso))->format('d/m/Y à H:i');
    } catch (Exception $ex) {
        return e($iso);
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Messages</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <main class="container">
        <header class="page-header">
            <h1>Messages reçus <span class="badge"><?= $total ?></span></h1>
            <?php if ($total > 0): ?>
                <input type="search" id="search" placeholder="Rechercher (nom, email, sujet…)" autocomplete="off">
            <?php endif; ?>
        </header>

        <?php if ($total === 0): ?>
            <div class="empty">
                <p class="empty-title">Aucun message pour le moment</p>
                <p>Les messages envoyés depuis le formulaire apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <div id="list" class="list">
                <?php foreach ($messages as $m): ?>
                    <?php
                        $fullName = trim(e($m['gender'] ?? '') . ' ' . e($m['firstname'] ?? '') . ' ' . e($m['lastname'] ?? ''));
                        $isCompany = !empty($m['isCompany']);
                    ?>
                    <article class="card">
                        <div class="card-head">
                            <h2><?= e($m['subject'] ?: '(Sans sujet)') ?></h2>
                            <span class="date"><?= formatDate($m['date'] ?? null) ?></span>
                        </div>

                        <p class="from">
                            <strong><?= $fullName !== '' ? $fullName : 'Anonyme' ?></strong>
                            <?php if ($isCompany): ?>
                                <span class="tag">Entreprise<?= !empty($m['company']) ? ' : ' . e($m['company']) : '' ?></span>
                            <?php else: ?>
                                <span class="tag tag-perso">Particulier</span>
                            <?php endif; ?>
                        </p>

                        <?php if (!empty($m['email'])): ?>
                            <p class="email"><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p>
                        <?php endif; ?>

                        <p class="message"><?= nl2br(e($m['message'] ?? '')) ?></p>
                        <p class="id">#<?= (int)($m['id'] ?? 0) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            <p id="no-result" class="empty" hidden>Aucun résultat pour cette recherche.</p>
        <?php endif; ?>
    </main>

    <?php if ($total > 0): ?>
        <script src="index.js"></script>
    <?php endif; ?>
</body>
</html>
