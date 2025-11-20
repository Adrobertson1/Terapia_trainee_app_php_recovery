<?php
function logAction(PDO $pdo, int|string $userId, string $role, string $action, string $detail = '', string $type = 'system'): void {
    $role = strtolower(trim($role));
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, role, action, action_type, action_detail, ip_address, timestamp)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $role, $action, $type, $detail, $ip]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}
?>