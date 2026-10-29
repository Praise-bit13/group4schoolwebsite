<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
$csrfToken = csrf_token();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#163b32">
    <meta name="csrf-token" content="<?= escape_html($csrfToken) ?>">
    <meta name="description" content="Apply to study at Federal Polytechnic Kampe and check the progress of your application.">
    <title>Apply | Federal Polytechnic Kampe</title>
    <link rel="stylesheet" href="styles.css">
    <script src="application.js" defer></script>
  </head>
  <body class="application-page">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="site-header">
      <a class="brand" href="index.html#home" aria-label="Federal Polytechnic Kampe home">
        <span class="brand-mark" aria-hidden="true">FPK</span>
        <span class="brand-name"><strong>Federal Polytechnic Kampe</strong><small>ILLIONIS · Knowledge · Skills · Opportunity</small></span>
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" aria-label="Open navigation">
        <span></span><span></span><span></span>
      </button>
      <nav class="site-navigation application-navigation" id="site-navigation" aria-label="Main navigation">
        <a class="nav-link" href="index.html#home">Home</a>
        <a class="nav-link" href="study.html">Study options</a>
        <a class="nav-link" href="index.html#admissions">Admissions</a>
        <a class="nav-link active" href="#apply" aria-current="page">Apply</a>
      </nav>
    </header>

    <main id="main-content" class="application-main section-wrap">
      <section class="application-intro">
        <p class="eyebrow eyebrow-green">Your next step</p>
        <h1>Apply to Federal Polytechnic Kampe</h1>
        <p>Submit your details and preferred study programme. Keep the application reference shown after submission; you’ll need it and your email address to check your status.</p>
        <p class="application-note">Submitting this form is an application enquiry, not an offer of admission. The school will review your details and contact you using the information provided.</p>
      </section>

      <div class="application-layout">
        <section class="application-panel" id="apply" aria-labelledby="apply-heading">
          <p class="eyebrow eyebrow-green">Applicant details</p>
          <h2 id="apply-heading">Start your application</h2>
          <p class="form-message" id="application-message" role="status" aria-live="polite"></p>
          <div class="application-receipt" id="application-receipt" hidden tabindex="-1" aria-live="polite">
            <h3>Application received</h3>
            <p id="application-reference"></p>
            <p>Save this reference securely. Check your status below using the same email address.</p>
          </div>
          <form id="application-form" class="application-form">
            <div class="application-fields">
              <label>First name
                <input name="first_name" autocomplete="given-name" maxlength="80" required>
              </label>
              <label>Last name
                <input name="last_name" autocomplete="family-name" maxlength="80" required>
              </label>
              <label>Email address
                <input name="email" type="email" autocomplete="email" maxlength="190" required>
              </label>
              <label>Phone number
                <input name="phone" type="tel" autocomplete="tel" maxlength="30" required>
              </label>
              <label>Study mode
                <select name="study_mode" required>
                  <option value="">Choose a study mode</option>
                  <option value="full_time">Full-time</option>
                  <option value="part_time">Part-time</option>
                </select>
              </label>
              <label>Department
                <select id="application-department" name="department_id" required>
                  <option value="">Loading departments…</option>
                </select>
              </label>
              <label class="application-field-wide">Programme
                <select id="application-programme" name="programme_id" required disabled>
                  <option value="">Choose a department first</option>
                </select>
              </label>
              <label class="application-field-wide">Highest previous qualification (optional)
                <input name="previous_qualification" maxlength="160" autocomplete="off">
              </label>
            </div>
            <label class="application-consent">
              <input type="checkbox" name="consent" value="yes" required>
              <span>I agree that the school may use these details to process and respond to this application.</span>
            </label>
            <label class="application-honeypot" aria-hidden="true">Leave this field empty
              <input name="website" tabindex="-1" autocomplete="off">
            </label>
            <button class="button button-gold" type="submit">Submit application <span aria-hidden="true">→</span></button>
          </form>
        </section>

        <section class="application-panel status-panel" id="check-status" aria-labelledby="status-heading">
          <p class="eyebrow eyebrow-green">Application updates</p>
          <h2 id="status-heading">Check your status</h2>
          <p>Use the application reference from your receipt and the email address you applied with.</p>
          <p class="form-message" id="status-message" role="status" aria-live="polite"></p>
          <form id="status-form" class="application-form">
            <label>Application reference
              <input name="reference_code" autocomplete="off" placeholder="FPK-XXXXXXXXXXXX" required>
            </label>
            <label>Email address
              <input name="email" type="email" autocomplete="email" maxlength="190" required>
            </label>
            <button class="button button-forest" type="submit">Check status</button>
          </form>
          <div class="status-result" id="status-result" hidden aria-live="polite"></div>
        </section>
      </div>
    </main>

    <footer class="site-footer">
      <a class="brand brand-footer" href="index.html#home">
        <span class="brand-mark" aria-hidden="true">FPK</span>
        <span class="brand-name"><strong>Federal Polytechnic Kampe, ILLIONIS</strong><small>Illinois, Kwara State</small></span>
      </a>
      <p>© <span id="current-year"></span> Federal Polytechnic Kampe. All rights reserved.</p>
      <a href="index.html#contact" class="back-to-top">Contact the school</a>
      <a href="login.php" class="staff-portal-link">Staff portal</a>
    </footer>
  </body>
</html>