-- ============================================================
-- HUKP ICT DEPARTMENT - COMPUTER BASED EXAMINATION SYSTEM
-- Hassan Usman Katsina Polytechnic, Katsina
-- Database: hukp_cbt
-- ============================================================

CREATE DATABASE IF NOT EXISTS hukp_cbt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hukp_cbt;

-- -----------------------------------------------
-- Table: departments
-- -----------------------------------------------
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- Table: courses
-- -----------------------------------------------
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_title VARCHAR(200) NOT NULL,
    credit_units INT DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: admins
-- -----------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin','admin','lecturer') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- Table: students
-- -----------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reg_number VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    level ENUM('ND1','ND2','HND1','HND2') DEFAULT 'ND1',
    gender ENUM('Male','Female') DEFAULT 'Male',
    phone VARCHAR(20),
    passport VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: exams
-- -----------------------------------------------
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_title VARCHAR(200) NOT NULL,
    instructions TEXT,
    duration_minutes INT NOT NULL DEFAULT 60,
    total_questions INT NOT NULL DEFAULT 40,
    pass_score INT NOT NULL DEFAULT 40,
    max_score INT NOT NULL DEFAULT 100,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    status ENUM('draft','active','closed') DEFAULT 'draft',
    shuffle_questions TINYINT(1) DEFAULT 1,
    shuffle_options TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admins(id)
);

-- -----------------------------------------------
-- Table: questions
-- -----------------------------------------------
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer ENUM('A','B','C','D') NOT NULL,
    marks INT NOT NULL DEFAULT 1,
    explanation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: exam_sessions (active exam attempts)
-- -----------------------------------------------
CREATE TABLE exam_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    time_remaining INT NULL COMMENT 'Seconds remaining when last saved',
    status ENUM('in_progress','submitted','timed_out') DEFAULT 'in_progress',
    ip_address VARCHAR(45),
    UNIQUE KEY unique_attempt (student_id, exam_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: student_answers
-- -----------------------------------------------
CREATE TABLE student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer ENUM('A','B','C','D') NULL,
    is_correct TINYINT(1) DEFAULT 0,
    marks_obtained INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_answer (session_id, question_id),
    FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: results
-- -----------------------------------------------
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL UNIQUE,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    total_questions INT NOT NULL,
    answered INT NOT NULL DEFAULT 0,
    correct INT NOT NULL DEFAULT 0,
    wrong INT NOT NULL DEFAULT 0,
    skipped INT NOT NULL DEFAULT 0,
    raw_score INT NOT NULL DEFAULT 0,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    grade VARCHAR(5),
    status ENUM('pass','fail') DEFAULT 'fail',
    submitted_at DATETIME NOT NULL,
    FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- Table: activity_log
-- -----------------------------------------------
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin','student') NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(200) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Departments
INSERT INTO departments (name, code) VALUES
('Information and Communication Technology', 'ICT'),
('Computer Science', 'CSC'),
('Electrical Engineering', 'EEE'),
('Business Administration', 'BUS');

-- Courses
INSERT INTO courses (department_id, course_code, course_title, credit_units) VALUES
(1, 'ICT101', 'Introduction to Information Technology', 3),
(1, 'ICT202', 'Database Management Systems', 3),
(1, 'ICT301', 'Web Programming', 3),
(1, 'ICT303', 'Computer Networks', 3),
(1, 'ICT401', 'Software Engineering', 3),
(1, 'ICT403', 'Cybersecurity Fundamentals', 3),
(2, 'CSC101', 'Introduction to Computer Science', 3),
(2, 'CSC201', 'Data Structures and Algorithms', 3);

-- Admin accounts (password: Admin@1234)
INSERT INTO admins (full_name, email, username, password, role) VALUES
('Dr. Sani Sulaiman Isah', 'hod@hukp.edu.ng', 'superadmin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 'superadmin'),
('Mal. Umar Sani Dutsinma', 'umar.dutsinma@hukp.edu.ng', 'lecturer1', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 'lecturer'),
('Mal. Salisu Abdu', 'salisu.abdu@hukp.edu.ng', 'lecturer2', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 'lecturer');

-- Students (password: Student@123)
INSERT INTO students (reg_number, full_name, email, password, department_id, level, gender, phone) VALUES
('ICT/HND1/001/2024', 'Rabiatu Hussaini Buhari', 'rabiatu@student.hukp.edu.ng', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 1, 'HND1', 'Female', '08012345678'),
('ICT/HND1/002/2024', 'Abubakar Yusuf', 'abubakar@student.hukp.edu.ng', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 1, 'HND1', 'Male', '08023456789'),
('ICT/ND2/001/2024', 'Fatima Dan Ali', 'fatima@student.hukp.edu.ng', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 1, 'ND2', 'Female', '08034567890'),
('ICT/ND2/002/2024', 'Muhammadu Rabe', 'rabe@student.hukp.edu.ng', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 1, 'ND2', 'Male', '08045678901'),
('ICT/ND1/001/2024', 'Rukkaya Hamza Darma', 'rukkaya@student.hukp.edu.ng', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uEvKuu3jm', 1, 'ND1', 'Female', '08056789012');

-- Sample Exam
INSERT INTO exams (course_id, exam_title, instructions, duration_minutes, total_questions, pass_score, max_score, status, created_by) VALUES
(1, 'ICT101 - First Semester CBT Examination 2024/2025',
'1. Read each question carefully before answering.\n2. Each question carries equal marks.\n3. Do not refresh the page during the exam.\n4. Submit before the timer expires.\n5. You cannot re-enter the exam once submitted.',
60, 20, 40, 100, 'active', 1);

-- Sample Questions for the exam
INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks, explanation) VALUES
(1, 'What does ICT stand for?', 
'Information and Computer Technology', 
'Information and Communication Technology', 
'Integrated Computer Technology', 
'Internet and Communication Technology', 
'B', 1, 'ICT stands for Information and Communication Technology.'),

(1, 'Which of the following is an input device?',
'Monitor', 'Printer', 'Keyboard', 'Speaker',
'C', 1, 'A keyboard is an input device used to enter data into a computer.'),

(1, 'What is the full meaning of CPU?',
'Central Processing Unit', 'Computer Processing Unit', 'Central Program Unit', 'Core Processing Unit',
'A', 1, 'CPU stands for Central Processing Unit - the brain of the computer.'),

(1, 'Which storage device has the highest capacity?',
'Floppy Disk', 'CD-ROM', 'USB Flash Drive', 'Hard Disk Drive',
'D', 1, 'Hard Disk Drives (HDD) typically have the highest storage capacity among the listed options.'),

(1, 'What does RAM stand for?',
'Read Access Memory', 'Random Access Memory', 'Read And Memory', 'Rapid Access Memory',
'B', 1, 'RAM stands for Random Access Memory, which is the primary working memory of a computer.'),

(1, 'Which of the following is an operating system?',
'Microsoft Word', 'Google Chrome', 'Ubuntu Linux', 'Adobe Photoshop',
'C', 1, 'Ubuntu Linux is an operating system. The others are application software.'),

(1, 'What is the function of a router in a network?',
'Store data permanently', 'Print documents', 'Connect and direct network traffic between networks', 'Display output',
'C', 1, 'A router connects different networks and directs data packets between them.'),

(1, 'Which programming language is known as the language of the web?',
'Python', 'Java', 'HTML', 'C++',
'C', 1, 'HTML (HyperText Markup Language) is the foundational language of web pages.'),

(1, 'What does URL stand for?',
'Uniform Resource Locator', 'Universal Resource Link', 'Uniform Routing Language', 'United Resource Locator',
'A', 1, 'URL stands for Uniform Resource Locator, which is the web address of a resource.'),

(1, 'Which of the following is NOT a web browser?',
'Google Chrome', 'Mozilla Firefox', 'Microsoft Excel', 'Safari',
'C', 1, 'Microsoft Excel is a spreadsheet application, not a web browser.'),

(1, 'What is the binary representation of the decimal number 10?',
'1010', '1001', '1100', '0101',
'A', 1, 'Decimal 10 = Binary 1010 (8+2=10).'),

(1, 'Which component is known as the "brain" of the computer?',
'RAM', 'Hard Drive', 'CPU', 'Motherboard',
'C', 1, 'The CPU (Central Processing Unit) is commonly referred to as the brain of the computer.'),

(1, 'What does GUI stand for?',
'Graphical User Input', 'Graphical User Interface', 'General User Interface', 'Global User Input',
'B', 1, 'GUI stands for Graphical User Interface - a visual way to interact with computers.'),

(1, 'Which of the following is a high-level programming language?',
'Assembly', 'Machine code', 'Binary', 'Python',
'D', 1, 'Python is a high-level programming language. Assembly, machine code, and binary are low-level.'),

(1, 'What is the purpose of an antivirus software?',
'Speed up internet connection', 'Detect and remove malware', 'Increase storage space', 'Improve screen resolution',
'B', 1, 'Antivirus software is designed to detect, prevent, and remove malicious software (malware).'),

(1, 'Which of the following best describes cloud computing?',
'Computing done on physical hardware at home', 'Delivery of computing services over the internet', 'Using offline software only', 'Programming using cloud-shaped icons',
'B', 1, 'Cloud computing refers to delivery of computing services (servers, storage, databases) over the internet.'),

(1, 'What is the purpose of a firewall?',
'Cool down the computer processor', 'Monitor and control incoming/outgoing network traffic', 'Store backup data', 'Increase processing speed',
'B', 1, 'A firewall is a network security system that monitors and controls network traffic based on security rules.'),

(1, 'Which protocol is used for sending emails?',
'HTTP', 'FTP', 'SMTP', 'DNS',
'C', 1, 'SMTP (Simple Mail Transfer Protocol) is used for sending emails.'),

(1, 'What does the term "bandwidth" refer to in networking?',
'The physical width of a network cable', 'The maximum data transfer rate of a network', 'The number of computers in a network', 'The color of network packets',
'B', 1, 'Bandwidth refers to the maximum rate at which data can be transferred over a network in a given time.'),

(1, 'Which of the following is a characteristic of a database management system (DBMS)?',
'It only stores images', 'It manages and organizes data efficiently', 'It is used only for gaming', 'It replaces the CPU',
'B', 1, 'A DBMS efficiently manages, organizes, stores, and retrieves data in a structured manner.');
