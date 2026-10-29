const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const departmentSelect = document.querySelector("#application-department");
const programmeSelect = document.querySelector("#application-programme");
const applicationForm = document.querySelector("#application-form");
const applicationMessage = document.querySelector("#application-message");
const statusForm = document.querySelector("#status-form");
const statusMessage = document.querySelector("#status-message");
const statusResult = document.querySelector("#status-result");
const menuToggle = document.querySelector(".menu-toggle");
const navigation = document.querySelector(".site-navigation");

document.querySelector("#current-year").textContent = String(new Date().getFullYear());

menuToggle.addEventListener("click", () => {
  const isOpen = menuToggle.getAttribute("aria-expanded") === "true";
  menuToggle.setAttribute("aria-expanded", String(!isOpen));
  menuToggle.setAttribute("aria-label", isOpen ? "Open navigation" : "Close navigation");
  navigation.classList.toggle("is-open", !isOpen);
});

navigation.addEventListener("click", (event) => {
  if (event.target instanceof HTMLAnchorElement && window.matchMedia("(max-width: 800px)").matches) {
    navigation.classList.remove("is-open");
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Open navigation");
  }
});

async function readJson(response) {
  let result;
  try {
    result = await response.json();
  } catch {
    throw new Error("The server returned an unreadable response.");
  }
  if (!response.ok) {
    throw new Error(result.error || "The request could not be completed.");
  }
  return result;
}

async function loadApplicationOptions() {
  const response = await fetch("./backend/catalog.php", {
    headers: { Accept: "application/json" },
  });
  const catalog = await readJson(response);
  const departments = new Map(catalog.departments.map((department) => [
    String(department.id),
    department,
  ]));
  const programmesByDepartment = new Map();
  for (const programme of catalog.programmes) {
    const key = String(programme.department_id);
    if (!programmesByDepartment.has(key)) {
      programmesByDepartment.set(key, []);
    }
    programmesByDepartment.get(key).push(programme);
  }

  departmentSelect.replaceChildren(new Option("Choose a department", ""));
  for (const department of departments.values()) {
    if ((programmesByDepartment.get(String(department.id)) || []).length > 0) {
      departmentSelect.add(new Option(department.name, department.id));
    }
  }
  if (departmentSelect.options.length === 1) {
    departmentSelect.replaceChildren(new Option("No programmes are available yet", ""));
    departmentSelect.disabled = true;
    applicationMessage.textContent = "The school has not published any application programmes yet. Please contact the school office.";
    applicationMessage.classList.add("is-error");
    applicationForm.querySelector('button[type="submit"]').disabled = true;
  }

  departmentSelect.addEventListener("change", () => {
    programmeSelect.replaceChildren(new Option(
      departmentSelect.value ? "Choose a programme" : "Choose a department first",
      "",
    ));
    programmeSelect.disabled = !departmentSelect.value;
    for (const programme of programmesByDepartment.get(departmentSelect.value) || []) {
      programmeSelect.add(new Option(programme.name, programme.id));
    }
  });
}

applicationForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  applicationMessage.textContent = "";
  applicationMessage.classList.remove("is-error");
  const submitButton = applicationForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  try {
    const values = Object.fromEntries(new FormData(applicationForm).entries());
    const response = await fetch("./backend/applications.php", {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-CSRF-Token": csrfToken,
      },
      body: JSON.stringify(values),
    });
    const result = await readJson(response);
    applicationForm.hidden = true;
    document.querySelector("#application-reference").textContent =
      `Your application reference is ${result.reference_code}.`;
    const receipt = document.querySelector("#application-receipt");
    receipt.hidden = false;
    receipt.focus();
    applicationMessage.textContent = result.message;
  } catch (error) {
    applicationMessage.textContent = error.message;
    applicationMessage.classList.add("is-error");
    submitButton.disabled = false;
  }
});

function appendStatusDetail(parent, label, value) {
  const detail = document.createElement("p");
  const heading = document.createElement("strong");
  heading.textContent = `${label}: `;
  detail.append(heading, document.createTextNode(value));
  parent.append(detail);
}

function formatDate(value) {
  if (!value) {
    return "";
  }
  const date = new Date(`${value.replace(" ", "T")}Z`);
  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(date);
}

statusForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  statusMessage.textContent = "";
  statusMessage.classList.remove("is-error");
  statusResult.hidden = true;
  statusResult.replaceChildren();
  const submitButton = statusForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  try {
    const response = await fetch("./backend/application-status.php", {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify(Object.fromEntries(new FormData(statusForm).entries())),
    });
    const result = await readJson(response);
    const application = result.application;
    const labels = {
      submitted: "Received",
      reviewing: "Under review",
      accepted: "Accepted",
      declined: "Not accepted",
    };
    const heading = document.createElement("h3");
    heading.textContent = `Application ${application.reference_code}`;
    statusResult.append(heading);
    appendStatusDetail(statusResult, "Status", labels[application.status] || application.status);
    appendStatusDetail(statusResult, "Programme", `${application.programme_name} · ${application.department_name}`);
    appendStatusDetail(statusResult, "Submitted", formatDate(application.created_at));
    if (application.reviewed_at) {
      appendStatusDetail(statusResult, "Last reviewed", formatDate(application.reviewed_at));
    }
    statusResult.hidden = false;
  } catch (error) {
    statusMessage.textContent = error.message;
    statusMessage.classList.add("is-error");
  } finally {
    submitButton.disabled = false;
  }
});

loadApplicationOptions().catch((error) => {
  departmentSelect.replaceChildren(new Option("Catalogue unavailable", ""));
  departmentSelect.disabled = true;
  applicationMessage.textContent = `Application options are unavailable: ${error.message}`;
  applicationMessage.classList.add("is-error");
  applicationForm.querySelector('button[type="submit"]').disabled = true;
});
