<?php
function getUserShifts($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT s.shift_name, s.start_time, s.end_time, s.shift_date, us.note
        FROM user_shifts us
        JOIN shifts s ON us.shift_id = s.shift_id
        WHERE us.user_id = ?
        ORDER BY s.shift_date DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>