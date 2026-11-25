
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

    <link rel="stylesheet" href="../../assets/css/style.css?v=20261125">

    <style>
        :root {
            --navy: #253b80;
            --navy-dark: #172b61;
            --primary-blue: #4169e1;
            --page-bg: #f3f6fc;
            --border-color: #e5eaf3;
            --muted: #6b7280;
        }

        body {
            background: var(--page-bg);
            color: #26334d;
            min-height: 100vh;
        }

        .admin-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(23, 43, 97, 0.04);
        }

        .brand-name {
            color: var(--navy-dark);
            font-weight: 750;
            letter-spacing: -0.3px;
        }

        .brand-subtitle {
            color: var(--muted);
            font-size: 12px;
        }

        .page-heading {
            color: var(--navy-dark);
            font-weight: 750;
            letter-spacing: -0.5px;
        }

        .page-description {
            color: var(--muted);
        }

        .success-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e9f8ef;
            color: #198754;
            border-radius: 13px;
            font-size: 24px;
            flex-shrink: 0;
        }

        .summary-box {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 5px 18px rgba(23, 43, 97, 0.04);
        }

        .summary-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .summary-value {
            color: var(--navy-dark);
            font-size: 21px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .page-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(23, 43, 97, 0.05);
        }

        .code-list {
            max-height: 460px;
            overflow-y: auto;
            background: #f7f9fe;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px 18px;
        }

        .code-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 5px;
            border-bottom: 1px solid var(--border-color);
        }

        .code-item:last-child {
            border-bottom: none;
        }

        .code-number {
            color: var(--muted);
            font-size: 13px;
            min-width: 32px;
        }

        .code-value {
            color: var(--navy-dark);
            font-family: monospace;
            font-size: 15px;
            letter-spacing: 0.5px;
            overflow-wrap: anywhere;
        }

        .security-alert {
            background: #fff8e8;
            border: 1px solid #f4dfaa;
            color: #795719;
            border-radius: 11px;
            padding: 16px;
        }

        .btn-primary {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            border-radius: 9px;
            padding: 10px 19px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--navy);
            border-color: var(--navy);
        }

        .btn-success {
            border-radius: 9px;
            padding: 10px 19px;
            font-weight: 600;
        }

        .btn-outline-secondary {
            border-radius: 9px;
            padding: 10px 19px;
            font-weight: 600;
        }

        @media (max-width: 576px) {
            .page-card {
                padding: 19px;
            }

            .summary-box {
                padding: 17px;
            }

            .page-heading {
                font-size: 24px;
            }

            .code-value {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>

<header class="admin-navbar py-3">
    <div class="container d-flex justify-content-between align-items-center gap-3">

        <div>
            <a href="../../index.php"
               class="text-decoration-none brand-name">
                Customer Loyalty Rewards
            </a>

            <div class="brand-subtitle">
                Admin Panel / Generated Reward Codes
            </div>
        </div>

        <a href="../dashboard.php"
           class="btn btn-outline-secondary btn-sm">
            Admin Dashboard
        </a>

    </div>
</header>

<main class="container py-4 py-md-5">

    <div class="d-flex align-items-center gap-3 mb-4">

        <div class="success-icon">
            &#10003;
        </div>

        <div>
            <h2 class="page-heading mb-1">
                Codes Generated Successfully!
            </h2>

            <p class="page-description mb-0">
                Your reward codes have been saved successfully.
                Download the CSV file and keep it secure for packaging.
            </p>
        </div>

    </div>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="summary-box">

                <div class="summary-label">
                    Product
                </div>

                <div class="summary-value">
                    <?= e($batch['product_name']) ?>
                </div>

            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-box">

                <div class="summary-label">
                    Generated Codes
                </div>

                <div class="summary-value">
                    <?= count($generatedCodes) ?>
                </div>

            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-box">

                <div class="summary-label">
                    Points Per Code
                </div>

                <div class="summary-value">
                    <?= (int) $batch['points_value'] ?> points
                </div>

            </div>
        </div>

    </div>

    <div class="page-card">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>
                <h5 class="fw-bold mb-1">
                    Generated Code List
                </h5>

                <p class="text-muted small mb-0">
                    These original codes are displayed only once.
                </p>
            </div>

            <button
                type="button"
                class="btn btn-success"
                onclick="downloadCSV()"
            >
                &#8681; Download CSV
            </button>

        </div>

        <div class="security-alert mb-4">

            <div class="d-flex gap-2">

                <span>&#9888;</span>

                <div>
                    <strong>Important Security Notice</strong>

                    <p class="mb-0 mt-1">
                        Download and securely save this CSV now.
                        The original codes cannot be recovered from the
                        database after you leave this page.
                    </p>
                </div>

            </div>

        </div>

        <div class="code-list mb-4">

            <?php foreach ($generatedCodes as $index => $code): ?>

                <div class="code-item">

                    <span class="code-number">
                        <?= $index + 1 ?>.
                    </span>

                    <strong class="code-value">
                        <?= e($code) ?>
                    </strong>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="d-flex flex-wrap gap-2">

            <a href="index.php" class="btn btn-primary">
                + Generate Another Batch
            </a>

            <a href="../dashboard.php" class="btn btn-outline-secondary">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
