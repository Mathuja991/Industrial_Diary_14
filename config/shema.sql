CREATE DATABASE IF NOT EXISTS group14;

USE group14;
-- Users table (for students and staff)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'mentor', 'staff') NOT NULL,
    full_name VARCHAR(100) NOT NULL
);

-- Diaries table (for storing student reports)
-- Create the diaries table
CREATE TABLE diaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    upload_date DATE NOT NULL,
    report TEXT,
    feedback TEXT,
    mentor_mark INT DEFAULT NULL,
    reviewed TINYINT(1) DEFAULT 0,  -- Indicates if the diary has been reviewed
    week_number INT NOT NULL,       -- Indicates the week number for the diary
    month INT NOT NULL,             -- Stores the month of submission
    year INT NOT NULL,              -- Stores the year of submission
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_diary_entry (student_id, week_number, month, year)  -- Prevents duplicate diary entries for the same week and month
);




-- Feedback table (for mentors or staff feedback)
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    diary_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    feedback TEXT,
    marks INT,
    FOREIGN KEY (diary_id) REFERENCES diaries(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE overall_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    submission_date DATE NOT NULL DEFAULT CURRENT_DATE,
    summary TEXT NOT NULL,
    challenges TEXT NOT NULL,
    improvements TEXT NOT NULL,
    status ENUM('pending', 'signed') DEFAULT 'pending',
    mentor_feedback TEXT,
    mentor_id INT,
    overallpro_mark INT DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE SET NULL
);



CREATE TABLE inspection_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,               -- Primary key for the table
    inspection_report_id INT UNIQUE NOT NULL,        -- Unique report ID (optional if using `id` as primary key)
    inspection_date DATE NOT NULL,
    inspector_name VARCHAR(255) NOT NULL,
    student_id INT NOT NULL,
    supervisor_remarks TEXT NOT NULL,
    student_remarks TEXT NOT NULL,
    lecturer_signature1 VARCHAR(255),
    lecturer_signature2 VARCHAR(255),
    inspec_mark INT,                                 -- Column to store inspection marks
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);



CREATE TABLE student (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reg_no VARCHAR(50) NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    email_id VARCHAR(100) NOT NULL,
    phone_no VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    index_no VARCHAR(50) NOT NULL,
    mentor_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE mentor (
    mentor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_id VARCHAR(100) NOT NULL,
    phone_no VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    working_organization VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_id VARCHAR(100) NOT NULL,
    phone_no VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE inspection_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    inspection_marks INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id)
);



CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    report_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL,
    description TEXT,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE student_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    inspection_mark INT NOT NULL,
    diary_mark INT NOT NULL,
    process_mark INT NOT NULL,
    total_mark INT NOT NULL,
    grade CHAR(1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id)
);


CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);
