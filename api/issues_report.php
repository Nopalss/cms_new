<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
try {
    $search = $_POST['query']['generalSearch'] ?? '';
    $issue_type = $_POST['query']['issue_type'] ?? '';
    $reported_by = $_POST['query']['tech_id'] ?? '';
    $dateInput = $_POST['query']['date'] ?? '';
    // Konversi format tanggal (dari MM/DD/YYYY → YYYY-MM-DD)
    $dateObj = DateTime::createFromFormat('m/d/Y', $dateInput);
    $date    = $dateObj ? $dateObj->format('Y-m-d') : null;
    $sql = "SELECT * FROM issues_report i WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (
            issue_id LIKE :search
                        schedule_id LIKE :search
                        reported_by LIKE :search
                        issue_type LIKE :search
                        description LIKE :search
                        created_at LIKE :search
                        status LIKE :search
                    )
    ";
        $params[':search'] = "%$search%";
    }


    if (!empty($issue_type)) {
        $sql .= " AND issue_type = :issue_type";
        $params[':issue_type'] = $issue_type;
    }

    if (!empty($reported_by)) {
        $sql .= " AND reported_by = :reported_by";
        $params[':reported_by'] = $reported_by;
    }

    if (!empty($date)) {
        $sql .= " AND created_at LIKE :search_date";
        $params[':search_date'] = "%$date%";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $issue_report = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "data" => $issue_report
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
