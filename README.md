# Federal Polytechnic Kampe website

This project keeps the public HTML/CSS/JavaScript website and provides a PHP 8+ and MySQL/MariaDB admissions and staff portal. Applicants can submit an application and check its status; authorized staff manage applications, student records, academic programmes, and announcements.

## Project structure

- `index.html`, `study.html`, `application.php`, `styles.css`, `script.js`, `study.js`, `application.js` — public website, study catalogue, online application and status checker.
- `database.sql` — database schema and clearly labelled demonstration departments, programmes, courses, and academic session.
- `config/database.php` — PDO connection. Credentials can be supplied through `SCHOOL_DB_HOST`, `SCHOOL_DB_PORT`, `SCHOOL_DB_NAME`, `SCHOOL_DB_USER`, and `SCHOOL_DB_PASSWORD`.
- `includes/auth.php`, `includes/functions.php` — session, authorization, CSRF, validation, and JSON response helpers.
- `login.php`, `logout.php`, `admin/` — protected staff sign-in and management dashboard.
- `backend/` — public application submission and status lookup; staff-only application review, student, department, and programme endpoints; published announcements and the study catalogue can be read publicly.
- `scripts/create-admin.php` — command-line tool for creating staff accounts without a hardcoded password.

## Run with XAMPP on Windows

1. Copy the project folder into `C:\xampp\htdocs\federal-polytechnic-kampe`. Keep the image files and the folders (`admin`, `backend`, `config`, `includes`, and `scripts`) together.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. This workspace's schema has already been imported into its local XAMPP MariaDB as `school_website`. If you are setting up another computer or the database is missing, open `http://localhost/phpmyadmin/`, select **Import**, choose `database.sql`, and run it. It creates the database and demo academic-structure records. Replace records beginning with `Sample -` or `DEMO` with approved school data before using them.
   If the database already exists, import the updated `database.sql` once to create the applications table. Existing tables and application records are not dropped.
4. Open a terminal and create the first staff account:

   ```powershell
   $securePassword = Read-Host "Staff password (12+ characters)" -AsSecureString
   $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
   try {
     $env:SCHOOL_ADMIN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
     & C:\xampp\php\php.exe C:\xampp\htdocs\federal-polytechnic-kampe\scripts\create-admin.php
   } finally {
     Remove-Item Env:SCHOOL_ADMIN_PASSWORD -ErrorAction SilentlyContinue
     [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
     $securePassword.Dispose()
   }
   ```

   Enter the staff name, email, and role (`admin` or `staff`) when prompted. The PowerShell password prompt is masked, and the script removes the environment variable after reading it. No administrator password is stored in this project.
5. Visit `http://localhost/federal-polytechnic-kampe/` for the public site and `http://localhost/federal-polytechnic-kampe/login.php` for staff sign-in.

The default connection is for a local XAMPP installation (`127.0.0.1:3306`, database `school_website`, user `root`, empty password). This is a local-development default only. Before hosting the site, create a least-privileged database account, configure the connection through environment variables, use HTTPS, and do not expose database credentials.

## Staff portal

- Staff sign in with an account created by the command-line tool. Sessions use HTTP-only cookies and expire after 30 minutes of inactivity.
- Student records can be created, searched by admission number or name, filtered by full-time/part-time mode, and edited. Admission numbers are unique; programme and department relationships are checked by both the API and database.
- Departments and programmes can be added and edited in the dashboard. Courses and academic sessions have schema tables and can be managed through phpMyAdmin until dedicated screens are needed.
- Add the school's approved programme records in the staff dashboard before accepting applications. The current database has department/course information but no approved programme records, so the public application form remains disabled until programmes are added.
- Applicants can submit their contact details, study mode, and programme choice at `application.php`. The receipt shows a random reference code; applicants check status using that code and the same email address. Staff can search applications and mark them received, under review, accepted, or not accepted in the dashboard.
- Application uploads, payments, automated admission decisions, and outbound email notifications are not part of this workflow. Staff should contact applicants using the details they supplied.
- Public study options include a database-backed catalogue of school-provided departments and courses, plus clearly labelled illustrative course suggestions that must be confirmed with the school. Suggested course rows are marked separately from provided course titles. Demo rows prefixed with `DEMO` or `Sample -` are deliberately hidden from public catalogue endpoints.
- Staff can save announcements as drafts, publish them on the public homepage, unpublish them, or archive them through the API.
- Student records are never returned by the public website. Mutating staff requests require an authenticated session and CSRF token.

## Database relationships

- `users` stores staff identities, roles, and `password_hash` values.
- `departments` owns `programmes` and `courses`.
- `students` reference one department and one programme; a composite foreign key prevents selecting a programme from a different department. A student may optionally reference an `academic_sessions` record.
- `applications` reference a valid department/programme pair and store a unique application reference and review status. Public status lookup requires both the reference and the applicant's email; applicant contact details are available only to signed-in staff.
- `announcements` stores public updates, publishing state, and the staff account that created each update.

The SQL includes no user passwords, applicant submissions, or student personal records. Its demo academic-structure rows are labelled and should be replaced before operational use. Run PHP lint checks using `C:\xampp\php\php.exe -l path\to\file.php`.

## Notes

- Run the project through XAMPP/Apache or another PHP-enabled web server. Opening `index.html` directly as a `file://` URL or serving it from a static-only preview will not run PHP routes.
- Application and student records contain personal information. Use HTTPS, a least-privileged database account, and appropriate retention and access policies before accepting real submissions.