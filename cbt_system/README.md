# HUKP Computer Based Examination System
### Hassan Usman Katsina Polytechnic — ICT Department, Katsina

---

## 📋 SYSTEM REQUIREMENTS
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache / XAMPP / WAMP
- Web browser (Chrome, Firefox, Edge)

---

## 🚀 INSTALLATION STEPS

### Step 1 — Copy Files
Copy the entire `cbt_system/` folder into your web server root:
- XAMPP: `C:/xampp/htdocs/cbt_system/`
- WAMP:  `C:/wamp64/www/cbt_system/`

### Step 2 — Import Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click **"New"** to create a database named `hukp_cbt`
3. Select the database, click **"Import"**
4. Choose the file: `sql/hukp_cbt.sql`
5. Click **"Go"**

### Step 3 — Configure Database (if needed)
Edit `includes/config.php` and update:
```php
define('DB_HOST', 'localhost');  // your MySQL host
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'hukp_cbt');   // database name
define('BASE_URL', 'http://localhost/cbt_system/');
```

### Step 4 — Open in Browser
Visit: **http://localhost/cbt_system/**

---

## 🔐 DEFAULT LOGIN CREDENTIALS
RoleUsernamePasswordSuper 
Admin superadminAdmin@1234
Lecturerlecturer1Admin@1234
Student (reg number or email)rabe@student.hukp.edu.ngStudent@123

### Admin Portal → http://localhost/cbt_system/admin/login.php
| Role        | email        | Password     |
|-------------|-------------|--------------|
| Super Admin | hod@hukp.edu.ng  | password     |

### Student Portal → http://localhost/cbt_system/student/login.php
| Student              | email                | Password                       |
|----------------------|----------------------|--------------------------------|
| Rabiatu H. Buhari    | rabiatu@student.hukp.edu.ng   | Student@123  |
| Abubakar Yusuf       | abubakar@student.hukp.edu.ng   | Student@123  |
| Fatima Dan Ali       | fatima@student.hukp.edu.ng    | Student@123  |
| Muhammadu Rabe       | rabe@student.hukp.edu.ng    | Student@123  |
| Rukkaya Hamza        | rukkaya@student.hukp.edu.ng                         | Student@123  |

---

## 📁 FILE STRUCTURE
```
cbt_system/
├── index.php                   ← Landing page
├── .htaccess                   ← Security rules
├── sql/
│   └── hukp_cbt.sql            ← Full database schema + sample data
├── css/
│   └── style.css               ← Complete design system
├── includes/
│   ├── config.php              ← DB config & utility functions
│   ├── admin_header.php        ← Admin sidebar layout
│   └── admin_footer.php        ← Admin footer
├── admin/
│   ├── login.php               ← Admin authentication
│   ├── logout.php
│   ├── dashboard.php           ← Statistics overview
│   ├── exams.php               ← Manage all exams
│   ├── exam_create.php         ← Create / edit exams
│   ├── exam_detail.php         ← Single exam + all results + grade chart
│   ├── questions.php           ← Question bank (add / delete)
│   ├── results.php             ← All results with filters
│   ├── result_detail.php       ← Per-student full answer review
│   ├── students.php            ← Register & manage students
│   ├── admins.php              ← Admin user management (superadmin)
│   ├── courses.php             ← Courses & departments
│   └── profile.php             ← Admin profile & password
└── student/
    ├── login.php               ← Student authentication
    ├── logout.php
    ├── dashboard.php           ← Available exams & recent results
    ├── exam_start.php          ← Instructions + agreement page
    ├── exam.php                ← Live exam with timer & AJAX auto-save
    ├── result.php              ← Detailed result + answer review
    ├── my_results.php          ← Full result history with analytics
    └── profile.php             ← Student profile & password change
```

---

## ✨ KEY FEATURES

- ✅ **Automated Grading** — Instant score, grade (A–F), pass/fail on submit
- ✅ **Live Countdown Timer** — Auto-submits when time expires
- ✅ **AJAX Auto-Save** — Answers saved continuously, no data loss
- ✅ **Question Randomization** — Shuffled questions & options per student
- ✅ **Role-Based Access** — Superadmin, Admin, Lecturer, Student
- ✅ **Answer Review** — Students see correct/wrong answers after exam
- ✅ **Grade Distribution Charts** — Per-exam analytics (A–F breakdown)
- ✅ **Printable Reports** — All result pages are print-friendly
- ✅ **Security** — IP logging, session control, RBAC, SQL injection prevention
- ✅ **Responsive Design** — Works on desktop, tablet, and mobile

---

## 🎨 DESIGN SYSTEM
- **Primary Color:** Deep Forest Green (#025E73 → #0a2218)
- **Accent:** Gold (#D4A02A → #C9963A)
- **Typography:** Playfair Display (headings) + DM Sans (body)
- **Framework:** Pure PHP + Vanilla JS (no framework dependencies)

---

## 📞 GRADING SCALE
| Grade | Range    | Classification |
|-------|----------|----------------|
| A     | 70–100%  | Distinction    |
| B     | 60–69%   | Credit         |
| C     | 50–59%   | Merit          |
| D     | 45–49%   | Pass           |
| E     | 40–44%   | Pass           |
| F     | 0–39%    | Fail           |

---

© 2025 HUKP CBT System — Hassan Usman Katsina Polytechnic, ICT Department, Katsina State, Nigeria
