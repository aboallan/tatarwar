CREATE DATABASE IF NOT EXISTS task_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE task_manager;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (name, email, password_hash, role)
VALUES
    ('Amina Al-Faisal', 'president@taskmaster.test', '$2y$12$j65mli2D4LRBmSjRqekwbe6BETddZniVqrQSrPq7XI7P4m1qPMwLG', 'president'),
    ('Omar Al-Hassan', 'manager@taskmaster.test', '$2y$12$KfuNnAYsRc5iWGUJV5omBu8nRfxcRLXlGgyIXoPKzQ/O..cuEqUBW', 'manager'),
    ('Layla Al-Salem', 'employee@taskmaster.test', '$2y$12$r4o2bbtALudjyN/x0z0jX.LaNQT12Y0RD45HV0NBCfshr1hJi3fGy', 'employee')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password_hash = VALUES(password_hash),
    role = VALUES(role);

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    department_id INT NOT NULL,
    priority VARCHAR(20) DEFAULT 'Medium',
    status VARCHAR(30) DEFAULT 'Pending',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_departments FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_tasks FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NULL,
    department_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    event_date DATE NOT NULL,
    priority VARCHAR(20) DEFAULT 'Medium',
    status VARCHAR(30) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_calendar_event_date (event_date),
    CONSTRAINT fk_calendar_events_tasks FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    CONSTRAINT fk_calendar_events_departments FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO departments (name) VALUES
    ('Office of the Secretary General'),
    ('General Department of Information Technology'),
    ('Cybersecurity Department'),
    ('Investment Agency'),
    ('General Department of Gardens and Landscaping'),
    ('General Department of Internal Audits'),
    ('General Department of Operations and Emergencies'),
    ('Department of Safety and Security'),
    ('Central Unit for Plan Approval'),
    ('Department of Land Affairs'),
    ('Environmental Health Department'),
    ('General Department of Cleaning'),
    ('Central City Municipality'),
    ('Northern Municipality')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Digital Infrastructure Audit', 'Complete the resiliency assessment for core datacenter services and report findings to the steering committee.', d.id, 'High', 'In Progress', DATE_ADD(CURDATE(), INTERVAL 5 DAY), NOW(), NOW()
FROM departments d
WHERE d.name = 'General Department of Information Technology'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Digital Infrastructure Audit' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Emergency Drill Readiness', 'Confirm equipment checklists and submit the cross-department readiness memo ahead of the city-wide drill.', d.id, 'Medium', 'Pending', DATE_ADD(CURDATE(), INTERVAL 2 DAY), NOW(), NOW()
FROM departments d
WHERE d.name = 'General Department of Operations and Emergencies'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Emergency Drill Readiness' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Investment Portfolio Review', 'Prepare quarterly briefing with performance highlights and risk actions for executive approval.', d.id, 'Medium', 'Completed', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM departments d
WHERE d.name = 'Investment Agency'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Investment Portfolio Review' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Public Park Revitalization', 'Finalize vendor contracts for the Riverside Park renovation and submit landscaping schedules.', d.id, 'High', 'Pending', DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)
FROM departments d
WHERE d.name = 'General Department of Gardens and Landscaping'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Public Park Revitalization' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'City Clean-Up Campaign', 'Launch the awareness campaign and coordinate resources with district offices.', d.id, 'Low', 'In Progress', DATE_ADD(CURDATE(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()
FROM departments d
WHERE d.name = 'General Department of Cleaning'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'City Clean-Up Campaign' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Cybersecurity Incident Simulation', 'Coordinate the tabletop exercise with remediation playbooks and report findings to leadership.', d.id, 'High', 'In Progress', DATE_ADD(CURDATE(), INTERVAL 7 DAY), NOW(), NOW()
FROM departments d
WHERE d.name = 'Cybersecurity Department'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Cybersecurity Incident Simulation' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Land Use Compliance Audit', 'Compile zoning approvals and flag parcels that require renewal before the quarterly review.', d.id, 'Medium', 'Pending', DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()
FROM departments d
WHERE d.name = 'Department of Land Affairs'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Land Use Compliance Audit' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Environmental Inspection Sweep', 'Deliver inspection summaries for high-risk districts and escalate unresolved breaches.', d.id, 'High', 'In Progress', DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()
FROM departments d
WHERE d.name = 'Environmental Health Department'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Environmental Inspection Sweep' AND t.department_id = d.id
  );

INSERT INTO tasks (title, description, department_id, priority, status, due_date, created_at, updated_at)
SELECT 'Safety Drill Certification', 'Gather drill sign-offs from facility leads and publish the compliance dashboard update.', d.id, 'Medium', 'Pending', DATE_ADD(CURDATE(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), NOW()
FROM departments d
WHERE d.name = 'Department of Safety and Security'
  AND NOT EXISTS (
      SELECT 1 FROM tasks t WHERE t.title = 'Safety Drill Certification' AND t.department_id = d.id
  );

INSERT INTO notifications (task_id, message, created_at)
SELECT t.id, 'Reminder sent to confirm infrastructure resiliency updates.', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM tasks t
JOIN departments d ON d.id = t.department_id
WHERE t.title = 'Digital Infrastructure Audit'
  AND d.name = 'General Department of Information Technology'
  AND NOT EXISTS (
      SELECT 1 FROM notifications n WHERE n.task_id = t.id
  );

INSERT INTO notifications (task_id, message, created_at)
SELECT t.id, 'Security exercise materials distributed to stakeholders.', DATE_SUB(NOW(), INTERVAL 3 HOUR)
FROM tasks t
JOIN departments d ON d.id = t.department_id
WHERE t.title = 'Cybersecurity Incident Simulation'
  AND d.name = 'Cybersecurity Department'
  AND NOT EXISTS (
      SELECT 1 FROM notifications n WHERE n.task_id = t.id
  );

INSERT INTO notifications (task_id, message, created_at)
SELECT t.id, 'Follow-up issued for environmental inspection deliverables.', DATE_SUB(NOW(), INTERVAL 8 HOUR)
FROM tasks t
JOIN departments d ON d.id = t.department_id
WHERE t.title = 'Environmental Inspection Sweep'
  AND d.name = 'Environmental Health Department'
  AND NOT EXISTS (
      SELECT 1 FROM notifications n WHERE n.task_id = t.id
  );

INSERT INTO notifications (task_id, message, created_at)
SELECT t.id, 'Follow-up issued for upcoming emergency readiness drill.', DATE_SUB(NOW(), INTERVAL 6 HOUR)
FROM tasks t
JOIN departments d ON d.id = t.department_id
WHERE t.title = 'Emergency Drill Readiness'
  AND d.name = 'General Department of Operations and Emergencies'
  AND NOT EXISTS (
      SELECT 1 FROM notifications n WHERE n.task_id = t.id
  );

INSERT INTO calendar_events (task_id, department_id, title, event_date, priority, status)
SELECT t.id, t.department_id, CONCAT(t.title, ' Deadline'), t.due_date, t.priority, t.status
FROM tasks t
WHERE t.due_date IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM calendar_events ce WHERE ce.task_id = t.id
  );

INSERT INTO calendar_events (department_id, title, event_date, priority, status)
SELECT d.id, 'Employee Onboarding Workshop', DATE_FORMAT(CURDATE(), '%Y-%m-05'), 'Medium', 'Scheduled'
FROM departments d
WHERE d.name = 'Office of the Secretary General'
  AND NOT EXISTS (
      SELECT 1 FROM calendar_events ce WHERE ce.title = 'Employee Onboarding Workshop'
        AND ce.event_date = DATE_FORMAT(CURDATE(), '%Y-%m-05')
        AND ce.department_id = d.id
  );

INSERT INTO calendar_events (department_id, title, event_date, priority, status)
SELECT d.id, 'Mobile App Beta Check-in', DATE_FORMAT(CURDATE(), '%Y-%m-12'), 'High', 'In Progress'
FROM departments d
WHERE d.name = 'General Department of Information Technology'
  AND NOT EXISTS (
      SELECT 1 FROM calendar_events ce WHERE ce.title = 'Mobile App Beta Check-in'
        AND ce.event_date = DATE_FORMAT(CURDATE(), '%Y-%m-12')
        AND ce.department_id = d.id
  );

INSERT INTO calendar_events (department_id, title, event_date, priority, status)
SELECT d.id, 'Quarterly Plan Review', DATE_FORMAT(CURDATE(), '%Y-%m-17'), 'Medium', 'Scheduled'
FROM departments d
WHERE d.name = 'Central Unit for Plan Approval'
  AND NOT EXISTS (
      SELECT 1 FROM calendar_events ce WHERE ce.title = 'Quarterly Plan Review'
        AND ce.event_date = DATE_FORMAT(CURDATE(), '%Y-%m-17')
        AND ce.department_id = d.id
  );

INSERT INTO calendar_events (department_id, title, event_date, priority, status)
SELECT d.id, 'Brand Refresh Showcase', DATE_FORMAT(CURDATE(), '%Y-%m-21'), 'Medium', 'Scheduled'
FROM departments d
WHERE d.name = 'Investment Agency'
  AND NOT EXISTS (
      SELECT 1 FROM calendar_events ce WHERE ce.title = 'Brand Refresh Showcase'
        AND ce.event_date = DATE_FORMAT(CURDATE(), '%Y-%m-21')
        AND ce.department_id = d.id
  );

INSERT INTO calendar_events (department_id, title, event_date, priority, status)
SELECT d.id, 'Safety Compliance Walkthrough', DATE_FORMAT(CURDATE(), '%Y-%m-24'), 'High', 'Scheduled'
FROM departments d
WHERE d.name = 'Department of Safety and Security'
  AND NOT EXISTS (
      SELECT 1 FROM calendar_events ce WHERE ce.title = 'Safety Compliance Walkthrough'
        AND ce.event_date = DATE_FORMAT(CURDATE(), '%Y-%m-24')
        AND ce.department_id = d.id
  );

INSERT INTO calendar_events (department_id, title, event_date, priority, status)
SELECT d.id, 'Green Corridor Site Visit', DATE_FORMAT(CURDATE(), '%Y-%m-28'), 'Low', 'Planned'
FROM departments d
WHERE d.name = 'General Department of Gardens and Landscaping'
  AND NOT EXISTS (
      SELECT 1 FROM calendar_events ce WHERE ce.title = 'Green Corridor Site Visit'
        AND ce.event_date = DATE_FORMAT(CURDATE(), '%Y-%m-28')
        AND ce.department_id = d.id
  );
