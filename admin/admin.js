const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const statusMessage = document.querySelector("#admin-status");
const departmentSelects = [
  document.querySelector("#student-department"),
  document.querySelector("#programme-department"),
];
const studentProgrammes = document.querySelector("#student-programme");
const departmentRecords = new Map();
const programmeRecords = new Map();
const studentRecords = new Map();

async function requestJson(path, options = {}) {
  const headers = new Headers(options.headers || {});
  headers.set("Accept", "application/json");
  if (options.body) {
    headers.set("Content-Type", "application/json");
  }
  if (options.method && options.method !== "GET") {
    headers.set("X-CSRF-Token", csrfToken);
  }

  const response = await fetch(path, { ...options, headers });
  let result;
  try {
    result = await response.json();
  } catch {
    throw new Error("The server returned an unreadable response. Check the PHP error log.");
  }
  if (!response.ok) {
    throw new Error(result.error || "The request could not be completed.");
  }
  return result;
}

function showStatus(message, isError = false) {
  statusMessage.textContent = message;
  statusMessage.classList.toggle("is-error", isError);
}

function createCell(row, value) {
  const cell = document.createElement("td");
  cell.textContent = value === null || value === undefined || value === "" ? "—" : String(value);
  row.append(cell);
  return cell;
}

function createActionButton(label, callback) {
  const button = document.createElement("button");
  button.type = "button";
  button.className = "row-action";
  button.textContent = label;
  button.addEventListener("click", callback);
  return button;
}

function replaceFormData(form, values) {
  for (const [name, value] of Object.entries(values)) {
    const field = form.elements.namedItem(name);
    if (field) {
      field.value = value ?? "";
    }
  }
}

async function loadDepartments() {
  const { departments } = await requestJson("../backend/departments.php");
  departmentRecords.clear();
  for (const department of departments) {
    departmentRecords.set(String(department.id), department);
  }

  for (const select of departmentSelects) {
    const previousValue = select.value;
    select.replaceChildren(new Option("Choose a department", ""));
    for (const department of departments) {
      if (Number(department.is_active) !== 1) {
        continue;
      }
      select.add(new Option(`${department.name} (${department.code})`, department.id));
    }
    if (previousValue && [...select.options].some((option) => option.value === previousValue)) {
      select.value = previousValue;
    }
  }

  const list = document.querySelector("#department-list");
  list.replaceChildren();
  for (const department of departments) {
    const row = document.createElement("tr");
    createCell(row, department.name);
    createCell(row, department.code);
    const action = document.createElement("td");
    action.append(createActionButton("Edit", () => {
      const form = document.querySelector("#department-form");
      replaceFormData(form, department);
      document.querySelector("#department-form-title").textContent = "Edit department";
      document.querySelector("#department-cancel").hidden = false;
      document.querySelector("#department-name").focus();
    }));
    row.append(action);
    list.append(row);
  }

  if (departments.length === 0) {
    const row = document.createElement("tr");
    createCell(row, "Add the school's approved department details.");
    list.append(row);
  }
}

async function loadProgrammes(departmentId = "") {
  const query = departmentId ? `?department_id=${encodeURIComponent(departmentId)}` : "";
  const { programmes } = await requestJson(`../backend/programmes.php${query}`);
  programmeRecords.clear();
  const list = document.querySelector("#programme-list");
  list.replaceChildren();

  for (const programme of programmes) {
    programmeRecords.set(String(programme.id), programme);
    const row = document.createElement("tr");
    createCell(row, `${programme.name} (${programme.code})`);
    createCell(row, departmentRecords.get(String(programme.department_id))?.name || "Department");
    const action = document.createElement("td");
    action.append(createActionButton("Edit", () => {
      const form = document.querySelector("#programme-form");
      replaceFormData(form, programme);
      document.querySelector("#programme-form-title").textContent = "Edit programme";
      document.querySelector("#programme-cancel").hidden = false;
      document.querySelector("#programme-name").focus();
    }));
    row.append(action);
    list.append(row);
  }

  if (programmes.length === 0) {
    const row = document.createElement("tr");
    createCell(row, "Add a department programme to get started.");
    list.append(row);
  }

  await updateStudentProgrammeOptions();
}

async function updateStudentProgrammeOptions(selectedId = "") {
  const departmentId = document.querySelector("#student-department").value;
  studentProgrammes.replaceChildren(new Option(
    departmentId ? "Choose a programme" : "Choose a department first",
    "",
  ));
  if (!departmentId) {
    return;
  }

  const { programmes } = await requestJson(
    `../backend/programmes.php?department_id=${encodeURIComponent(departmentId)}`,
  );
  for (const programme of programmes) {
    if (Number(programme.is_active) === 1) {
      studentProgrammes.add(new Option(programme.name, programme.id));
    }
  }
  if (selectedId && [...studentProgrammes.options].some((option) => option.value === String(selectedId))) {
    studentProgrammes.value = String(selectedId);
  }
}

async function loadStudents() {
  const query = new URLSearchParams();
  const search = document.querySelector("#student-query").value.trim();
  const mode = document.querySelector("#study-filter").value;
  if (search) query.set("q", search);
  if (mode) query.set("study_mode", mode);

  const { students } = await requestJson(`../backend/students.php?${query.toString()}`);
  const list = document.querySelector("#student-list");
  studentRecords.clear();
  list.replaceChildren();

  for (const student of students) {
    studentRecords.set(String(student.id), student);
    const row = document.createElement("tr");
    createCell(row, `${student.first_name} ${student.last_name}`);
    createCell(row, student.admission_number);
    createCell(row, student.study_mode === "full_time" ? "Full-time" : "Part-time");
    createCell(row, student.programme_name);
    const action = document.createElement("td");
    action.append(createActionButton("Edit", async () => {
      const form = document.querySelector("#student-form");
      replaceFormData(form, student);
      document.querySelector("#student-form-title").textContent = "Edit student record";
      document.querySelector("#student-cancel").hidden = false;
      await updateStudentProgrammeOptions(student.programme_id);
      document.querySelector("#admission-number").focus();
    }));
    row.append(action);
    list.append(row);
  }

  if (students.length === 0) {
    const row = document.createElement("tr");
    createCell(row, "No student records match this search.");
    list.append(row);
  }
}

async function loadAnnouncements() {
  const { announcements } = await requestJson("../backend/announcements.php");
  const list = document.querySelector("#announcement-list");
  list.replaceChildren();

  for (const announcement of announcements) {
    const row = document.createElement("tr");
    createCell(row, announcement.title);
    createCell(row, announcement.status);
    const action = document.createElement("td");
    const nextStatus = announcement.status === "published" ? "draft" : "published";
    action.append(createActionButton(
      announcement.status === "published" ? "Unpublish" : "Publish",
      async () => {
        await requestJson("../backend/announcements.php", {
          method: "PUT",
          body: JSON.stringify({ id: announcement.id, status: nextStatus }),
        });
        showStatus("Announcement status updated.");
        await loadAnnouncements();
      },
    ));
    row.append(action);
    list.append(row);
  }

  if (announcements.length === 0) {
    const row = document.createElement("tr");
    createCell(row, "No announcements have been written yet.");
    list.append(row);
  }
}

const applicationStatuses = [
  ["submitted", "Received"],
  ["reviewing", "Under review"],
  ["accepted", "Accepted"],
  ["declined", "Not accepted"],
];

async function loadApplications() {
  const query = new URLSearchParams();
  const search = document.querySelector("#application-query").value.trim();
  const status = document.querySelector("#application-status-filter").value;
  if (search) query.set("q", search);
  if (status) query.set("status", status);

  const { applications } = await requestJson(`../backend/applications.php?${query.toString()}`);
  const list = document.querySelector("#application-list");
  list.replaceChildren();

  for (const application of applications) {
    const row = document.createElement("tr");
    createCell(row, `${application.first_name} ${application.last_name} · ${application.reference_code}`);
    createCell(row, `${application.email} · ${application.phone}`);
    createCell(row, `${application.programme_name} · ${application.department_name}`);
    createCell(row, new Intl.DateTimeFormat(undefined, { dateStyle: "medium" })
      .format(new Date(`${application.created_at.replace(" ", "T")}Z`)));

    const statusCell = document.createElement("td");
    const statusSelect = document.createElement("select");
    statusSelect.setAttribute("aria-label", `Status for ${application.reference_code}`);
    for (const [value, label] of applicationStatuses) {
      statusSelect.add(new Option(label, value));
    }
    statusSelect.value = application.status;
    statusCell.append(statusSelect);
    row.append(statusCell);

    const actions = document.createElement("td");
    const saveButton = createActionButton("Save", async () => {
      saveButton.disabled = true;
      try {
        await requestJson("../backend/applications.php", {
          method: "PUT",
          body: JSON.stringify({ id: application.id, status: statusSelect.value }),
        });
        showStatus(`Application ${application.reference_code} updated.`);
        await loadApplications();
      } catch (error) {
        showStatus(error.message, true);
      } finally {
        saveButton.disabled = false;
      }
    });
    actions.append(saveButton);
    row.append(actions);
    list.append(row);
  }

  if (applications.length === 0) {
    const row = document.createElement("tr");
    createCell(row, "No applications match this search.");
    list.append(row);
  }
}

function resetStudentForm() {
  const form = document.querySelector("#student-form");
  form.reset();
  form.elements.namedItem("id").value = "";
  document.querySelector("#student-form-title").textContent = "Register a student record";
  document.querySelector("#student-cancel").hidden = true;
  updateStudentProgrammeOptions().catch((error) => showStatus(error.message, true));
}

function resetDepartmentForm() {
  const form = document.querySelector("#department-form");
  form.reset();
  form.elements.namedItem("id").value = "";
  document.querySelector("#department-form-title").textContent = "Add a department";
  document.querySelector("#department-cancel").hidden = true;
}

function resetProgrammeForm() {
  const form = document.querySelector("#programme-form");
  form.reset();
  form.elements.namedItem("id").value = "";
  document.querySelector("#programme-form-title").textContent = "Add a programme";
  document.querySelector("#programme-cancel").hidden = true;
}

document.querySelector("#student-department").addEventListener("change", () => {
  updateStudentProgrammeOptions().catch((error) => showStatus(error.message, true));
});

document.querySelector("#student-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const values = Object.fromEntries(new FormData(form).entries());
  const isEditing = Boolean(values.id);
  try {
    await requestJson("../backend/students.php", {
      method: isEditing ? "PUT" : "POST",
      body: JSON.stringify(values),
    });
    resetStudentForm();
    await loadStudents();
    showStatus(isEditing ? "Student record updated." : "Student record created.");
  } catch (error) {
    showStatus(error.message, true);
  }
});

document.querySelector("#student-cancel").addEventListener("click", resetStudentForm);
document.querySelector("#student-search").addEventListener("submit", (event) => {
  event.preventDefault();
  loadStudents().catch((error) => showStatus(error.message, true));
});

document.querySelector("#department-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const values = Object.fromEntries(new FormData(form).entries());
  const isEditing = Boolean(values.id);
  try {
    await requestJson("../backend/departments.php", {
      method: isEditing ? "PUT" : "POST",
      body: JSON.stringify(values),
    });
    resetDepartmentForm();
    await loadDepartments();
    await loadProgrammes();
    showStatus(isEditing ? "Department updated." : "Department created.");
  } catch (error) {
    showStatus(error.message, true);
  }
});

document.querySelector("#department-cancel").addEventListener("click", resetDepartmentForm);
document.querySelector("#programme-department").addEventListener("change", (event) => {
  loadProgrammes(event.currentTarget.value).catch((error) => showStatus(error.message, true));
});

document.querySelector("#programme-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const values = Object.fromEntries(new FormData(form).entries());
  const isEditing = Boolean(values.id);
  try {
    await requestJson("../backend/programmes.php", {
      method: isEditing ? "PUT" : "POST",
      body: JSON.stringify(values),
    });
    resetProgrammeForm();
    await loadProgrammes();
    showStatus(isEditing ? "Programme updated." : "Programme created.");
  } catch (error) {
    showStatus(error.message, true);
  }
});

document.querySelector("#programme-cancel").addEventListener("click", resetProgrammeForm);

document.querySelector("#announcement-form").addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const values = Object.fromEntries(new FormData(form).entries());
  try {
    await requestJson("../backend/announcements.php", {
      method: "POST",
      body: JSON.stringify(values),
    });
    form.reset();
    await loadAnnouncements();
    showStatus("Announcement saved.");
  } catch (error) {
    showStatus(error.message, true);
  }
});

document.querySelector("#application-search").addEventListener("submit", (event) => {
  event.preventDefault();
  loadApplications().catch((error) => showStatus(error.message, true));
});

async function initializeDashboard() {
  try {
    await loadDepartments();
    await Promise.all([loadProgrammes(), loadStudents(), loadAnnouncements(), loadApplications()]);
  } catch (error) {
    showStatus(error.message, true);
  }
}

initializeDashboard();
