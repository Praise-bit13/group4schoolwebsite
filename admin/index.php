<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_admin_page();
$csrfToken = csrf_token();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= escape_html($csrfToken) ?>">
    <title>Staff portal | Federal Polytechnic Kampe</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
    <script src="admin.js" defer></script>
  </head>
  <body class="admin-body">
    <header class="admin-header">
      <a class="brand" href="../index.html">
        <span class="brand-mark" aria-hidden="true">FPK</span>
        <span class="brand-name"><strong>Federal Polytechnic Kampe</strong><small>Staff portal</small></span>
      </a>
      <div class="admin-user">
        <span>Signed in as <?= escape_html((string) ($_SESSION['user_name'] ?? 'Staff')) ?></span>
        <form method="post" action="../logout.php">
          <input type="hidden" name="csrf_token" value="<?= escape_html($csrfToken) ?>">
          <button class="button button-outline admin-logout" type="submit">Sign out</button>
        </form>
      </div>
    </header>

    <main class="admin-main">
      <div class="admin-welcome">
        <p class="eyebrow eyebrow-green">School administration</p>
        <h1>Staff dashboard</h1>
        <p>Manage student records, study programmes, applications, and public school announcements.</p>
      </div>
      <p id="admin-status" class="form-message" role="status" aria-live="polite"></p>

      <section class="admin-panel" id="student-records">
        <div class="admin-panel-heading">
          <div><p class="eyebrow eyebrow-green">Student services</p><h2>Student records</h2></div>
          <p>Records are visible only to signed-in staff.</p>
        </div>
        <div class="admin-columns">
          <form id="student-form" class="admin-form admin-form-grid">
            <h3 id="student-form-title">Register a student record</h3>
            <input type="hidden" name="id">
            <label for="admission-number">Admission number</label>
            <input id="admission-number" name="admission_number" maxlength="32" required>
            <label for="first-name">First name</label>
            <input id="first-name" name="first_name" maxlength="80" required>
            <label for="last-name">Last name</label>
            <input id="last-name" name="last_name" maxlength="80" required>
            <label for="student-email">Email (optional)</label>
            <input id="student-email" name="email" type="email" maxlength="190">
            <label for="student-phone">Phone (optional)</label>
            <input id="student-phone" name="phone" type="tel" maxlength="30">
            <label for="student-dob">Date of birth (optional)</label>
            <input id="student-dob" name="date_of_birth" type="date">
            <label for="student-mode">Study mode</label>
            <select id="student-mode" name="study_mode" required>
              <option value="full_time">Full-time</option>
              <option value="part_time">Part-time</option>
            </select>
            <label for="student-department">Department</label>
            <select id="student-department" name="department_id" required><option value="">Loading departments…</option></select>
            <label for="student-programme">Programme</label>
            <select id="student-programme" name="programme_id" required><option value="">Choose a department first</option></select>
            <div class="admin-form-actions">
              <button class="button button-gold" type="submit">Save student</button>
              <button class="button button-quiet" id="student-cancel" type="button" hidden>Cancel edit</button>
            </div>
          </form>
          <div class="admin-list">
            <form id="student-search" class="search-form">
              <label for="student-query">Search admission number or name</label>
              <input id="student-query" type="search" maxlength="80" placeholder="Search records">
              <label for="study-filter">Study mode</label>
              <select id="study-filter">
                <option value="">All study modes</option>
                <option value="full_time">Full-time</option>
                <option value="part_time">Part-time</option>
              </select>
              <button class="button button-forest" type="submit">Search</button>
            </form>
            <div class="table-wrap">
              <table>
                <thead><tr><th>Student</th><th>Admission no.</th><th>Study mode</th><th>Programme</th><th></th></tr></thead>
                <tbody id="student-list"><tr><td colspan="5">Loading records…</td></tr></tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <section class="admin-panel">
        <div class="admin-panel-heading">
          <div><p class="eyebrow eyebrow-green">Academic structure</p><h2>Departments and programmes</h2></div>
          <p>Add departments and programmes before registering students.</p>
        </div>
        <div class="admin-columns">
          <div>
            <form id="department-form" class="admin-form">
              <h3 id="department-form-title">Add a department</h3>
              <input type="hidden" name="id">
              <label for="department-name">Department name</label>
              <input id="department-name" name="name" maxlength="120" required>
              <label for="department-code">Code</label>
              <input id="department-code" name="code" maxlength="20" required>
              <label for="department-description">Description (optional)</label>
              <textarea id="department-description" name="description" rows="2" maxlength="5000"></textarea>
              <div class="admin-form-actions">
                <button class="button button-gold" type="submit">Save department</button>
                <button class="button button-quiet" id="department-cancel" type="button" hidden>Cancel edit</button>
              </div>
            </form>
            <div class="table-wrap"><table><thead><tr><th>Department</th><th>Code</th><th></th></tr></thead><tbody id="department-list"></tbody></table></div>
          </div>
          <div>
            <form id="programme-form" class="admin-form">
              <h3 id="programme-form-title">Add a programme</h3>
              <input type="hidden" name="id">
              <label for="programme-name">Programme name</label>
              <input id="programme-name" name="name" maxlength="160" required>
              <label for="programme-code">Code</label>
              <input id="programme-code" name="code" maxlength="30" required>
              <label for="programme-department">Department</label>
              <select id="programme-department" name="department_id" required><option value="">Loading departments…</option></select>
              <label for="programme-award">Award (optional)</label>
              <input id="programme-award" name="award" maxlength="80">
              <label for="programme-duration">Duration in years (optional)</label>
              <input id="programme-duration" name="duration_years" type="number" min="1" max="12">
              <div class="admin-form-actions">
                <button class="button button-gold" type="submit">Save programme</button>
                <button class="button button-quiet" id="programme-cancel" type="button" hidden>Cancel edit</button>
              </div>
            </form>
            <div class="table-wrap"><table><thead><tr><th>Programme</th><th>Department</th><th></th></tr></thead><tbody id="programme-list"></tbody></table></div>
          </div>
        </div>
      </section>

      <section class="admin-panel" id="applications">
        <div class="admin-panel-heading">
          <div><p class="eyebrow eyebrow-green">Admissions</p><h2>Applications</h2></div>
          <p>Applicant contact details are visible only to signed-in staff.</p>
        </div>
        <div class="admin-list">
          <form id="application-search" class="search-form">
            <label for="application-query">Search reference, name, or email</label>
            <input id="application-query" type="search" maxlength="120" placeholder="Search applications">
            <label for="application-status-filter">Status</label>
            <select id="application-status-filter">
              <option value="">All statuses</option>
              <option value="submitted">Received</option>
              <option value="reviewing">Under review</option>
              <option value="accepted">Accepted</option>
              <option value="declined">Not accepted</option>
            </select>
            <button class="button button-forest" type="submit">Search</button>
          </form>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Applicant</th><th>Contact</th><th>Programme</th><th>Submitted</th><th>Status</th><th>Update</th></tr></thead>
              <tbody id="application-list"><tr><td colspan="6">Loading applications…</td></tr></tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="admin-panel" id="announcements">
        <div class="admin-panel-heading">
          <div><p class="eyebrow eyebrow-green">Public website</p><h2>Announcements</h2></div>
          <p>Published announcements appear on the public homepage.</p>
        </div>
        <div class="admin-columns">
          <form id="announcement-form" class="admin-form">
            <h3>Write an announcement</h3>
            <label for="announcement-title">Title</label>
            <input id="announcement-title" name="title" maxlength="180" required>
            <label for="announcement-body">Message</label>
            <textarea id="announcement-body" name="body" rows="6" maxlength="10000" required></textarea>
            <label for="announcement-status">Status</label>
            <select id="announcement-status" name="status">
              <option value="draft">Save as draft</option>
              <option value="published">Publish now</option>
            </select>
            <button class="button button-gold" type="submit">Save announcement</button>
          </form>
          <div class="admin-list">
            <h3>Recent announcements</h3>
            <div class="table-wrap"><table><thead><tr><th>Announcement</th><th>Status</th><th></th></tr></thead><tbody id="announcement-list"></tbody></table></div>
          </div>
        </div>
      </section>
      <a class="back-to-site" href="../index.html">← Return to public website</a>
    </main>
  </body>
</html>
