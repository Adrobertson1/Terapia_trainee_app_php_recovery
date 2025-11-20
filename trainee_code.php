<?php
require 'db.php';
session_start();

// Example: values from form submission
$user_id        = 2;
$first_name     = 'John';
$surname        = 'Doe';
$date_of_birth  = '1990-05-12';
$disability_status = 'No';
$address_line1  = '123 Example Street';
$town_city      = 'Erith';
$postcode       = 'DA8 1XY';
$email          = 'john.doe@example.com';
$password       = 'securehashedpassword'; // Ideally use password_hash()
$telephone      = '07700123456';
$start_date     = '2025-09-15';
$dbs_status     = 'Clear';
$dbs_date       = '2025-08-01';
$dbs_reference  = 'DBS123456';
$profile_image  = 'john_doe.jpg';
$is_archived    = 0;
$supervisor_id  = 1;

try {
    // Step 1: Insert trainee without trainee_code
    $stmt = $pdo->prepare("
        INSERT INTO trainees (
            user_id, first_name, surname, date_of_birth, disability_status,
            address_line1, town_city, postcode, email, password, telephone,
            start_date, dbs_status, dbs_date, dbs_reference, profile_image,
            is_archived, supervisor_id
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $user_id, $first_name, $surname, $date_of_birth, $disability_status,
        $address_line1, $town_city, $postcode, $email, $password, $telephone,
        $start_date, $dbs_status, $dbs_date, $dbs_reference, $profile_image,
        $is_archived, $supervisor_id
    ]);

    // Step 2: Get the auto-generated trainee_id
    $trainee_id = $pdo->lastInsertId();

    // Step 3: Generate trainee_code based on trainee_id
    $trainee_code = 'TRN-2025-' . str_pad($trainee_id, 3, '0', STR_PAD_LEFT);

    // Step 4: Update trainee_code
    $update = $pdo->prepare("UPDATE trainees SET trainee_code = ? WHERE trainee_id = ?");
    $update->execute([$trainee_code, $trainee_id]);

    echo "Trainee added successfully with code: $trainee_code";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>