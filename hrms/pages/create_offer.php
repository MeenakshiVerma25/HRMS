<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$preview = isset($_GET['preview']) && $_GET['preview'] == '1';

if(empty($id)) {
    die("Invalid offer ID.");
}

$sql = "SELECT offers.*, candidates.full_name,
        designations.designation_name, locations.location_name
        FROM offers
        LEFT JOIN candidates   ON offers.candidate_id   = candidates.candidate_id
        LEFT JOIN designations ON offers.designation_id = designations.designation_id
        LEFT JOIN locations    ON offers.location_id    = locations.location_id
        WHERE offer_id = '$id'";

$data = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if(!$data) {
    die("Offer not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['full_name']) ?> — Offer Letter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 60px;
            color: #222;
            line-height: 1.7;
            background: #fff;
        }
        h2 {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }
        .details-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 6px 12px;
        }
        .details-table td:first-child {
            font-weight: bold;
            width: 200px;
        }
        .signature {
            margin-top: 60px;
        }
        @media print {
            body { padding: 30px; }
        }
    </style>
</head>
<body>

    <h2>Offer Letter</h2>

    <p>Date: <strong><?= htmlspecialchars(!empty($data['created_at']) ? date('d-m-Y', strtotime($data['created_at'])) : date('d-m-Y')) ?></strong></p>

    <p>Dear <strong><?= htmlspecialchars($data['full_name']) ?></strong>,</p>

    <p>
        We are pleased to offer you the position of
        <strong><?= htmlspecialchars($data['designation_name']) ?></strong> at our company.
        We were impressed with your background and believe you will be a valuable addition to our team.
    </p>

    <table class="details-table">
        <tr>
            <td>CTC</td>
            <td>: <?= htmlspecialchars($data['ctc']) ?></td>
        </tr>
        <tr>
            <td>Location</td>
            <td>: <?= htmlspecialchars($data['location_name']) ?></td>
        </tr>
        <tr>
            <td>Date of Joining</td>
            <td>: <?= htmlspecialchars($data['doj']) ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td>: <?= htmlspecialchars($data['status']) ?></td>
        </tr>
    </table>

    <p>
        Please confirm your acceptance of this offer by signing and returning a copy of this letter.
        We look forward to welcoming you to the team.
    </p>

    <div class="signature">
        <p>Regards,</p>
        <br><br>
        <p><strong>HR Team</strong></p>
    </div>

    <?php if(!$preview): ?>
    <script>
        window.print();
    </script>
    <?php endif; ?>

</body>
</html>