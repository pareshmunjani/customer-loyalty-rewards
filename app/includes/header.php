
<?php

$pageTitle = $pageTitle ?? 'Customer Loyalty Rewards';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/customer-loyalty-rewards/public/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">

        <a class="navbar-brand fw-bold brand-name"
           href="/customer-loyalty-rewards/public/">
            Loyalty Rewards
        </a>

        <span class="navbar-text small text-secondary">
            Customer Loyalty Program
        </span>

    </div>
</nav>

<main>
