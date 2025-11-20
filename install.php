<?php
// install.php — Full database setup
require 'db.php';

try {
    // Disable foreign key checks to safely drop dependent tables
    $pdo->exec("
        SET FOREIGN_KEY_CHECKS = 0;
        DROP TABLE IF EXISTS grades;
        DROP TABLE IF EXISTS submissions;
        DROP TABLE IF EXISTS assignments;
        DROP TABLE IF EXISTS trainee_course_enrollments;
        DROP TABLE IF EXISTS user_calendar_events;
        DROP TABLE IF EXISTS trainees;
        DROP TABLE IF EXISTS tutors;
        DROP TABLE IF EXISTS courses;
        DROP TABLE IF EXISTS users;
        SET FOREIGN_KEY_CHECKS = 1;
    ");

    // Create users table
    $pdo->exec("
        CREATE TABLE users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('staff','tutor','trainee','admin') NOT NULL
        );
    ");

    // Create trainees table
    $pdo->exec("
        CREATE TABLE trainees (
            trainee_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            trainee_code VARCHAR(20) UNIQUE,
            photo VARCHAR(255),
            first_name VARCHAR(100),
            surname VARCHAR(100),
            date_of_birth DATE,
            disability_status ENUM('Yes','No') DEFAULT 'No',
            disability_type VARCHAR(255),
            address_line1 VARCHAR(255),
            town_city VARCHAR(100),
            postcode VARCHAR(20),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // Create tutors table
    $pdo->exec("
        CREATE TABLE tutors (
            tutor_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            first_name VARCHAR(100),
            surname VARCHAR(100),
            email VARCHAR(100),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // Create courses table
    $pdo->exec("
        CREATE TABLE courses (
            course_id INT AUTO_INCREMENT PRIMARY KEY,
            course_name VARCHAR(255) NOT NULL
        );
    ");

    // Create trainee course enrollments table
    $pdo->exec("
        CREATE TABLE trainee_course_enrollments (
            enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
            trainee_id INT,
            course_id INT,
            start_date DATE,
            expected_finish_date DATE,
            FOREIGN KEY (trainee_id) REFERENCES trainees(trainee_id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
        );
    ");

    // Create assignments table
    $pdo->exec("
        CREATE TABLE assignments (
            assignment_id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT,
            tutor_id INT,
            title VARCHAR(255),
            description TEXT,
            due_date DATE,
            FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
            FOREIGN KEY (tutor_id) REFERENCES tutors(tutor_id) ON DELETE CASCADE
        );
    ");

    // Create submissions table
    $pdo->exec("
        CREATE TABLE submissions (
            submission_id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT,
            trainee_id INT,
            file_path VARCHAR(255),
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id) ON DELETE CASCADE,
            FOREIGN KEY (trainee_id) REFERENCES trainees(trainee_id) ON DELETE CASCADE
        );
    ");

    // Create grades table
    $pdo->exec("
        CREATE TABLE grades (
            grade_id INT AUTO_INCREMENT PRIMARY KEY,
            submission_id INT UNIQUE,
            tutor_id INT,
            grade VARCHAR(50),
            feedback TEXT,
            graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (submission_id) REFERENCES submissions(submission_id) ON DELETE CASCADE,
            FOREIGN KEY (tutor_id) REFERENCES tutors(tutor_id) ON DELETE CASCADE
        );
    ");

    // Create universal calendar events table
    $pdo->exec("
        CREATE TABLE user_calendar_events (
            event_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            created_by ENUM('staff','tutor','admin') DEFAULT 'staff',
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        );
    ");

    // Insert default users
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$adminPass', 'staff')");

    $tutorPass = password_hash('tutor123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password, role) VALUES ('tutor1', '$tutorPass', 'tutor')");
    $tutorId = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO tutors (user_id, first_name, surname, email) VALUES ($tutorId, 'Jane', 'Doe', 'jane.doe@example.com')");

    $traineePass = password_hash('trainee123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (username, password, role) VALUES ('trainee1', '$traineePass', 'trainee')");
    $traineeId = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO trainees (user_id, trainee_code, first_name, surname, date_of_birth, town_city, postcode)
                VALUES ($traineeId, 'TR001', 'John', 'Smith', '2000-01-01', 'London', 'SE2 0XX')");

    echo "✅ Database installed successfully.";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>