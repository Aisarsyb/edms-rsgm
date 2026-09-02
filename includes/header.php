<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDMS - RSGM Universitas Airlangga</title>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Google Material Symbols Outlined -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">
    <!-- Main Custom CSS -->
    <link href="assets/css/style.css?v=<?= time(); ?>" rel="stylesheet">
    <!-- CSRF Token untuk keamanan request AJAX -->
    <?php if (function_exists('generate_csrf_token')): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
    <?php endif; ?>
</head>
<body class="flex flex-col h-screen text-on-surface bg-background">
