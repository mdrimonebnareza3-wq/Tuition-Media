CREATE DATABASE IF NOT EXISTS tuition_media CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tuition_media;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS tutor_availability;
DROP TABLE IF EXISTS tuition_posts;
DROP TABLE IF EXISTS tutor_profiles;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','guardian','tutor','admin') NOT NULL,
    status ENUM('active','blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tutor_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    gender ENUM('Male','Female','Any') DEFAULT 'Any',
    education VARCHAR(160) DEFAULT '',
    university VARCHAR(180) DEFAULT '',
    subjects VARCHAR(255) DEFAULT '',
    classes VARCHAR(255) DEFAULT '',
    location VARCHAR(180) DEFAULT '',
    experience_years INT UNSIGNED DEFAULT 0,
    expected_salary DECIMAL(10,2) DEFAULT 0,
    teaching_mode ENUM('Home Tutoring','Online','Both') DEFAULT 'Home Tutoring',
    bio TEXT,
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tutor_salary(expected_salary),
    CONSTRAINT fk_tutor_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tuition_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    class_level VARCHAR(120) NOT NULL,
    location VARCHAR(180) NOT NULL,
    salary_min DECIMAL(10,2) DEFAULT 0,
    salary_max DECIMAL(10,2) DEFAULT 0,
    preferred_gender ENUM('Any','Male','Female') DEFAULT 'Any',
    teaching_mode ENUM('Home Tutoring','Online','Both') DEFAULT 'Home Tutoring',
    days_per_week TINYINT UNSIGNED DEFAULT 3,
    preferred_time VARCHAR(100) DEFAULT '',
    description TEXT,
    status ENUM('pending','approved','rejected','closed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tuition_search(subject,class_level,location,status),
    INDEX idx_tuition_salary(salary_min,salary_max),
    CONSTRAINT fk_tuition_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tutor_availability (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT UNSIGNED NOT NULL,
    day_of_week ENUM('Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    teaching_mode ENUM('Home Tutoring','Online','Both') DEFAULT 'Home Tutoring',
    CONSTRAINT fk_availability_tutor FOREIGN KEY(tutor_id) REFERENCES tutor_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tuition_id INT UNSIGNED NOT NULL,
    tutor_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending','accepted','rejected','withdrawn','completed','cancelled') DEFAULT 'pending',
    accepted_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    cancelled_at DATETIME DEFAULT NULL,
    cancellation_reason VARCHAR(500) DEFAULT NULL,
    cancelled_by_role ENUM('student','guardian','admin') DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application(tuition_id,tutor_id),
    CONSTRAINT fk_application_tuition FOREIGN KEY(tuition_id) REFERENCES tuition_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_application_tutor FOREIGN KEY(tutor_id) REFERENCES tutor_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tuition_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    tutor_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review(tuition_id,reviewer_id,tutor_id),
    CONSTRAINT fk_review_tuition FOREIGN KEY(tuition_id) REFERENCES tuition_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY(reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_tutor FOREIGN KEY(tutor_id) REFERENCES tutor_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    entity_type VARCHAR(30) DEFAULT NULL,
    entity_id INT UNSIGNED DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications(user_id,is_read),
    CONSTRAINT fk_notification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_user(user_id),
    INDEX idx_password_reset_expiry(expires_at),
    CONSTRAINT fk_password_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users(name,email,phone,password,role,status) VALUES
('System Administrator','admin@tuitionmedia.local','01700000001','$2y$12$Wek6ek0oPEhtEBONqJzLvO80FrcIPEHhNj3YWgTDMYJW8pJdFxlBK','admin','active'),
('Demo Tutor','tutor@tuitionmedia.local','01700000002','$2y$12$oIXpzRqPUvPPS3VbGu0dreSlVFGofXlruXjO8xNKDVZRa4wBSQgsa','tutor','active'),
('Demo Guardian','guardian@tuitionmedia.local','01700000003','$2y$12$pYHUYMULy1rMhlqxMCEPEeRUfygXxixa6t4JQlvMOYd4Tz7KBA8BS','guardian','active'),
('Demo Student','student@tuitionmedia.local','01700000004','$2y$12$H5KY4RsADj1vVWa5BYa2R.qTMBTxsAWSSs4bxImsZcrVgkjPFTLmG','student','active');

INSERT INTO tutor_profiles(user_id,gender,education,university,subjects,classes,location,experience_years,expected_salary,teaching_mode,bio,approval_status)
VALUES(2,'Male','BSc in Computer Science','BAIUST','Mathematics, Physics, ICT','Class 6-12','Cumilla Cantonment, Cumilla',3,8000,'Both','Patient and responsible tutor with strong skills in Mathematics, Physics and ICT. I focus on concept building, regular practice and exam preparation.','approved');

INSERT INTO tutor_availability(tutor_id,day_of_week,start_time,end_time,teaching_mode) VALUES
(1,'Saturday','17:00:00','20:00:00','Both'),
(1,'Monday','18:00:00','21:00:00','Home Tutoring'),
(1,'Wednesday','18:00:00','21:00:00','Online');

INSERT INTO tuition_posts(user_id,title,subject,class_level,location,salary_min,salary_max,preferred_gender,teaching_mode,days_per_week,preferred_time,description,status) VALUES
(3,'Need a Mathematics tutor for Class 9','Mathematics','Class 9','Kandirpar, Cumilla',6000,8000,'Any','Home Tutoring',3,'6:00 PM - 8:00 PM','Looking for a punctual tutor who can teach Mathematics clearly and take weekly tests.','approved'),
(4,'Physics and ICT tutor required','Physics, ICT','HSC 1st Year','Cumilla Cantonment',7000,10000,'Any','Both',3,'Evening','Need support for HSC Physics and ICT. Online classes are acceptable on some days.','approved');
