<?php
require_once __DIR__ . "/../includes/config.php";
session_write_close();
try {
    $search = $_POST['query']['generalSearch'] ?? '';
    $role = $_POST['query']['role'] ?? '';


    $sql = "SELECT 
                u.id,
                u.username,
                u.role,
                a.jabatan,
                COALESCE(t.name, a.name) AS name,
                COALESCE(t.phone, a.phone) AS phone
            FROM users u
            LEFT JOIN technician t ON u.username = t.username
            LEFT JOIN admin a ON u.username = a.username
            WHERE 1=1";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (
                        u.username LIKE :search
                        OR u.role LIKE :search
                        OR COALESCE(t.name, a.name) LIKE :search
                        OR COALESCE(t.phone, a.phone) LIKE :search
                    )";
        $params[':search'] = "%$search%";
    }

    if (!empty($role)) {
        $sql .= " AND u.role LIKE :role";
        $params[':role'] = $role;
    }


    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "data" => $users
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
