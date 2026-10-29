CREATE DATABASE IF NOT EXISTS school_website
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE school_website;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(20) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_departments_name (name),
  UNIQUE KEY uq_departments_code (code),
  KEY idx_departments_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS programmes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  code VARCHAR(30) NOT NULL,
  award VARCHAR(80) NULL,
  duration_years TINYINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_programmes_code (code),
  UNIQUE KEY uq_programmes_id_department (id, department_id),
  KEY idx_programmes_department_active (department_id, is_active),
  CONSTRAINT fk_programmes_department
    FOREIGN KEY (department_id) REFERENCES departments (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS academic_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(30) NOT NULL,
  starts_on DATE NULL,
  ends_on DATE NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_academic_sessions_name (name),
  KEY idx_academic_sessions_current (is_current)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL,
  title VARCHAR(160) NOT NULL,
  credit_units TINYINT UNSIGNED NULL,
  is_suggested TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_courses_code (code),
  KEY idx_courses_department_active (department_id, is_active),
  CONSTRAINT fk_courses_department
    FOREIGN KEY (department_id) REFERENCES departments (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  admission_number VARCHAR(32) NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(30) NULL,
  gender ENUM('female', 'male', 'other', 'prefer_not_to_say') NULL,
  date_of_birth DATE NULL,
  study_mode ENUM('full_time', 'part_time') NOT NULL,
  department_id BIGINT UNSIGNED NOT NULL,
  programme_id BIGINT UNSIGNED NOT NULL,
  academic_session_id BIGINT UNSIGNED NULL,
  status ENUM('active', 'graduated', 'withdrawn', 'suspended') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_admission_number (admission_number),
  KEY idx_students_study_mode (study_mode),
  KEY idx_students_department_programme (department_id, programme_id),
  KEY idx_students_session_status (academic_session_id, status),
  KEY idx_students_name (last_name, first_name),
  CONSTRAINT fk_students_department
    FOREIGN KEY (department_id) REFERENCES departments (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_students_programme_department
    FOREIGN KEY (programme_id, department_id)
    REFERENCES programmes (id, department_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_students_academic_session
    FOREIGN KEY (academic_session_id) REFERENCES academic_sessions (id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_students_created_by
    FOREIGN KEY (created_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS applications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference_code CHAR(16) NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  study_mode ENUM('full_time', 'part_time') NOT NULL,
  department_id BIGINT UNSIGNED NOT NULL,
  programme_id BIGINT UNSIGNED NOT NULL,
  previous_qualification VARCHAR(160) NULL,
  status ENUM('submitted', 'reviewing', 'accepted', 'declined') NOT NULL DEFAULT 'submitted',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_applications_reference (reference_code),
  KEY idx_applications_status_created (status, created_at),
  KEY idx_applications_name (last_name, first_name),
  KEY idx_applications_programme (department_id, programme_id),
  CONSTRAINT fk_applications_programme_department
    FOREIGN KEY (programme_id, department_id)
    REFERENCES programmes (id, department_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_applications_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_announcements_public (status, published_at),
  KEY idx_announcements_created_by (created_by),
  CONSTRAINT fk_announcements_created_by
    FOREIGN KEY (created_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Clearly labelled examples only. Replace with approved school department data.
INSERT INTO departments (name, code, description)
VALUES
  ('Sample - Computer Science', 'DEMO-CS', 'Demonstration record; replace with approved department details.'),
  ('Sample - Business Studies', 'DEMO-BUS', 'Demonstration record; replace with approved department details.')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Department names supplied by the school. Add approved programmes and courses separately.
INSERT INTO departments (name, code, description, is_active)
VALUES
  ('Wildlife and Ecotourism Management Department', 'WEM', NULL, 1),
  ('Animal Production Technology Department', 'APT', NULL, 1),
  ('Tourism Department', 'TOUR', NULL, 1),
  ('Basic Science Department', 'BASIC-SCI', NULL, 1),
  ('Vocational Department', 'VOC', NULL, 1),
  ('Computer Science', 'COMP-SCI', NULL, 1),
  ('Forestry Technology', 'FORESTRY', NULL, 1),
  ('Statistics', 'STAT', NULL, 1),
  ('Agricultural Technology', 'AGRI-TECH', NULL, 1),
  ('Agricultural Extension and Management', 'AEM', NULL, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  is_active = VALUES(is_active);

-- Course titles supplied by the school; CAT codes are internal database identifiers, not official course codes.
INSERT INTO courses (department_id, code, title, is_active)
SELECT d.id, course.code, course.title, 1
FROM departments d
JOIN (
  SELECT 'COMP-SCI' AS department_code, 'CAT-CS-001' AS code, 'Programming' AS title
  UNION ALL SELECT 'COMP-SCI', 'CAT-CS-002', 'Database'
  UNION ALL SELECT 'COMP-SCI', 'CAT-CS-003', 'Cybersecurity'
  UNION ALL SELECT 'COMP-SCI', 'CAT-CS-004', 'Ethical Hacking'
  UNION ALL SELECT 'APT', 'CAT-APT-001', 'Animal Breeding'
  UNION ALL SELECT 'APT', 'CAT-APT-002', 'Animal Husbandry'
) AS course ON course.department_code = d.code
ON DUPLICATE KEY UPDATE
  department_id = VALUES(department_id),
  title = VALUES(title),
  is_active = VALUES(is_active);

-- Illustrative suggestions only; these are not verified school offerings.
INSERT INTO courses (department_id, code, title, is_suggested, is_active)
SELECT d.id, course.code, course.title, 1, 1
FROM departments d
JOIN (
  SELECT 'WEM' AS department_code, 'SUG-WEM-01' AS code, 'Wildlife Ecology' AS title
  UNION ALL SELECT 'WEM', 'SUG-WEM-02', 'Biodiversity Conservation'
  UNION ALL SELECT 'WEM', 'SUG-WEM-03', 'Ecotourism Planning'
  UNION ALL SELECT 'WEM', 'SUG-WEM-04', 'Protected Area Management'
  UNION ALL SELECT 'APT', 'SUG-APT-01', 'Animal Nutrition'
  UNION ALL SELECT 'APT', 'SUG-APT-02', 'Livestock Production'
  UNION ALL SELECT 'APT', 'SUG-APT-03', 'Animal Health and Hygiene'
  UNION ALL SELECT 'APT', 'SUG-APT-04', 'Pasture and Forage Management'
  UNION ALL SELECT 'TOUR', 'SUG-TOUR-01', 'Tourism Planning and Development'
  UNION ALL SELECT 'TOUR', 'SUG-TOUR-02', 'Tour Guiding'
  UNION ALL SELECT 'TOUR', 'SUG-TOUR-03', 'Hospitality Operations'
  UNION ALL SELECT 'TOUR', 'SUG-TOUR-04', 'Travel Agency Operations'
  UNION ALL SELECT 'BASIC-SCI', 'SUG-BSCI-01', 'Biology'
  UNION ALL SELECT 'BASIC-SCI', 'SUG-BSCI-02', 'Chemistry'
  UNION ALL SELECT 'BASIC-SCI', 'SUG-BSCI-03', 'Physics'
  UNION ALL SELECT 'BASIC-SCI', 'SUG-BSCI-04', 'Environmental Science'
  UNION ALL SELECT 'VOC', 'SUG-VOC-01', 'Entrepreneurship'
  UNION ALL SELECT 'VOC', 'SUG-VOC-02', 'Technical Drawing'
  UNION ALL SELECT 'VOC', 'SUG-VOC-03', 'Workshop Practice'
  UNION ALL SELECT 'VOC', 'SUG-VOC-04', 'Applied Craft Skills'
  UNION ALL SELECT 'COMP-SCI', 'SUG-CS-01', 'Computer Fundamentals'
  UNION ALL SELECT 'COMP-SCI', 'SUG-CS-02', 'Web Development'
  UNION ALL SELECT 'COMP-SCI', 'SUG-CS-03', 'Computer Networks'
  UNION ALL SELECT 'COMP-SCI', 'SUG-CS-04', 'Software Engineering'
  UNION ALL SELECT 'FORESTRY', 'SUG-FOR-01', 'Forest Ecology'
  UNION ALL SELECT 'FORESTRY', 'SUG-FOR-02', 'Silviculture'
  UNION ALL SELECT 'FORESTRY', 'SUG-FOR-03', 'Forest Mensuration'
  UNION ALL SELECT 'FORESTRY', 'SUG-FOR-04', 'Forest Protection'
  UNION ALL SELECT 'STAT', 'SUG-STAT-01', 'Probability and Statistics'
  UNION ALL SELECT 'STAT', 'SUG-STAT-02', 'Statistical Inference'
  UNION ALL SELECT 'STAT', 'SUG-STAT-03', 'Survey Sampling'
  UNION ALL SELECT 'STAT', 'SUG-STAT-04', 'Regression Analysis'
  UNION ALL SELECT 'AGRI-TECH', 'SUG-AGT-01', 'Crop Production'
  UNION ALL SELECT 'AGRI-TECH', 'SUG-AGT-02', 'Soil Science'
  UNION ALL SELECT 'AGRI-TECH', 'SUG-AGT-03', 'Farm Machinery'
  UNION ALL SELECT 'AGRI-TECH', 'SUG-AGT-04', 'Irrigation and Drainage'
  UNION ALL SELECT 'AGRI-TECH', 'SUG-AGT-05', 'Post-Harvest Technology'
  UNION ALL SELECT 'AEM', 'SUG-AEM-01', 'Agricultural Extension Methods'
  UNION ALL SELECT 'AEM', 'SUG-AEM-02', 'Farm Management'
  UNION ALL SELECT 'AEM', 'SUG-AEM-03', 'Rural Sociology'
  UNION ALL SELECT 'AEM', 'SUG-AEM-04', 'Agribusiness Management'
) AS course ON course.department_code = d.code
ON DUPLICATE KEY UPDATE
  department_id = VALUES(department_id),
  title = VALUES(title),
  is_suggested = VALUES(is_suggested),
  is_active = VALUES(is_active);

INSERT INTO programmes (department_id, name, code, award, duration_years)
SELECT id, 'Sample - Computer Science', 'DEMO-CS-PROG', 'Demo programme', 2
FROM departments WHERE code = 'DEMO-CS'
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO programmes (department_id, name, code, award, duration_years)
SELECT id, 'Sample - Business Studies', 'DEMO-BUS-PROG', 'Demo programme', 2
FROM departments WHERE code = 'DEMO-BUS'
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO courses (department_id, code, title, credit_units)
SELECT id, 'DEMO-CS-101', 'Sample - Computing Fundamentals', 3
FROM departments WHERE code = 'DEMO-CS'
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO academic_sessions (name, starts_on, ends_on, is_current)
VALUES ('DEMO - Replace with current session', NULL, NULL, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);
