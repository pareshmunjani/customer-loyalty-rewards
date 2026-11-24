
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';

requireAdmin();

// Check whether a generation batch exists.
$generatedCodes = $_SESSION['generated_codes'] ?? [];
$batch = $_SESSION['generated_batch'] ?? null;

if (
    !is_array($generatedCodes) ||
    empty($generatedCodes) ||
    !is_array($batch)
) {
    setFlashMessage(
        'error',
        'No generated codes are available. Please generate a new batch.'
    );

    redirect('/customer-loyalty-rewards/public/admin/codes/index.php');
}

// Clear the codes from the session after the result page is loaded.
// Keep a local copy for rendering this response.
unset($_SESSION['generated_codes']);
unset($_SESSION['generated_batch']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Generated Codes | Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f7fb;
        }

        .page-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 28px;
        }

        .summary-box {
            background-color: #eef5ff;
            border: 1px solid #d7e7ff;
            border-radius: 8px;
            padding: 16px;
            height: 100%;
        }

        .code-list {
            max-height: 420px;
            overflow-y: auto;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }

        .code-item {
            font-family: monospace;
            font-size: 15px;
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .code-item:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">
    <div class="container d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Generated Reward Codes</small>
        </div>

        <a
            href="../dashboard.php"
            class="btn btn-outline-secondary btn-sm"
        >
            Admin Dashboard
        </a>

    </div>
</header>

<main class="container py-4">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Codes Generated Successfully!</h3>
        <p class="text-muted mb-0">
            Your reward codes have been saved. Download the CSV file
            and keep it secure for packaging.
        </p>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="summary-box">
                <small class="text-muted">Product</small>
                <h5 class="fw-bold mb-0">
                    <?= e($batch['product_name']) ?>
                </h5>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-box">
                <small class="text-muted">Generated Codes</small>
                <h5 class="fw-bold mb-0">
                    <?= count($generatedCodes) ?>
                </h5>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-box">
                <small class="text-muted">Points Per Code</small>
                <h5 class="fw-bold mb-0">
                    <?= (int) $batch['points_value'] ?> points
                </h5>
            </div>
        </div>

    </div>

    <div class="page-card">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

            <div>
                <h5 class="fw-bold mb-1">Generated Code List</h5>
                <p class="text-muted mb-0">
                    These original codes are displayed only once.
                </p>
            </div>

            <button
                type="button"
                class="btn btn-success"
                onclick="downloadCSV()"
            >
                Download CSV
            </button>

        </div>

        <div class="alert alert-warning">
            <strong>Important:</strong>
            Download and securely save this CSV now.
            The original codes cannot be recovered from the database
            after you leave this page.
        </div>

        <div class="code-list mb-3">

            <?php foreach ($generatedCodes as $index => $code): ?>

                <div class="code-item">
                    <span><?= $index + 1 ?>.</span>
                    <strong><?= e($code) ?></strong>
                </div>

            <?php endforeach; ?>

        </div>

        <div class="d-flex flex-wrap gap-2">

            <a
                href="index.php"
                class="btn btn-primary"
            >
                Generate Another Batch
            </a>

            <a
                href="../dashboard.php"
                class="btn btn-outline-secondary"
            >
                Admin Dashboard
            </a>

        </div>

    </div>

</main>

<script>
    const generatedCodes = <?= json_encode(
        $generatedCodes,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;

    const batch = <?= json_encode(
        $batch,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;

    function downloadCSV() {

        const rows = [
            ['Serial Number', 'Product Name', 'Reward Code', 'Points Per Code']
        ];

        generatedCodes.forEach((code, index) => {
            rows.push([
                index + 1,
                batch.product_name,
                code,
                batch.points_value
            ]);
        });

        // Quote CSV values and neutralize spreadsheet formula prefixes.
        const csvContent = rows.map(row =>
            row.map(value => {
                let text = String(value);

                if (/^[\s]*[=+\-@\t\r]/.test(text)) {
                    text = "'" + text;
                }

                return '"' + text.replace(/"/g, '""') + '"';
            }).join(',')
        ).join('\r\n');

        const blob = new Blob(
            ['\uFEFF' + csvContent],
            { type: 'text/csv;charset=utf-8;' }
        );

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download = 'reward-codes-' +
            new Date().toISOString().replace(/[:.]/g, '-') +
            '.csv';

        document.body.appendChild(link);
        link.click();
        link.remove();

        URL.revokeObjectURL(url);
    }
</script>

</body>
</html>
                