const menuToggle = document.querySelector(".menu-toggle");
const navigation = document.querySelector(".site-navigation");
const academicCatalog = document.querySelector("#academic-catalog-list");
const catalogSession = document.querySelector("#catalog-session");

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

function appendCatalogText(parent, tagName, className, text) {
  const element = document.createElement(tagName);
  if (className) {
    element.className = className;
  }
  element.textContent = text;
  parent.append(element);
  return element;
}

function renderAcademicCatalog(catalog) {
  academicCatalog.replaceChildren();
  catalogSession.textContent = catalog.current_session
    ? `Current academic session: ${catalog.current_session.name}`
    : "";

  if (catalog.departments.length === 0) {
    appendCatalogText(
      academicCatalog,
      "p",
      "catalog-message",
      "The school’s approved department and course information is being added. Please contact the school office for current study options.",
    );
    return;
  }

  const programmesByDepartment = new Map();
  for (const programme of catalog.programmes) {
    const departmentId = String(programme.department_id);
    if (!programmesByDepartment.has(departmentId)) {
      programmesByDepartment.set(departmentId, []);
    }
    programmesByDepartment.get(departmentId).push(programme);
  }

  const coursesByDepartment = new Map();
  for (const course of catalog.courses) {
    const departmentId = String(course.department_id);
    if (!coursesByDepartment.has(departmentId)) {
      coursesByDepartment.set(departmentId, []);
    }
    coursesByDepartment.get(departmentId).push(course);
  }

  for (const department of catalog.departments) {
    const card = document.createElement("article");
    card.className = "catalog-card";
    appendCatalogText(card, "p", "catalog-code", department.code);
    appendCatalogText(card, "h2", "", department.name);
    if (department.description) {
      appendCatalogText(card, "p", "catalog-description", department.description);
    }

    const programmes = programmesByDepartment.get(String(department.id)) || [];
    if (programmes.length > 0) {
      appendCatalogText(card, "h3", "catalog-subheading", "Programmes");
      const programmeList = document.createElement("ul");
      programmeList.className = "catalog-list";
      for (const programme of programmes) {
        const details = [
          programme.name,
          programme.award,
          programme.duration_years ? `${programme.duration_years} years` : "",
        ].filter(Boolean).join(" · ");
        appendCatalogText(programmeList, "li", "", details);
      }
      card.append(programmeList);
    }

    const departmentCourses = coursesByDepartment.get(String(department.id)) || [];
    const courses = departmentCourses.filter((course) => Number(course.is_suggested) !== 1);
    const suggestedCourses = departmentCourses.filter((course) => Number(course.is_suggested) === 1);
    appendCatalogText(card, "h3", "catalog-subheading", "Courses");
    if (courses.length > 0) {
      const courseList = document.createElement("ul");
      courseList.className = "catalog-list catalog-course-list";
      for (const course of courses) {
        appendCatalogText(
          courseList,
          "li",
          "",
          `${course.title}${course.credit_units ? ` · ${course.credit_units} units` : ""}`,
        );
      }
      card.append(courseList);
    } else {
      appendCatalogText(card, "p", "catalog-description", "No confirmed course titles have been provided for this department yet.");
    }

    if (suggestedCourses.length > 0) {
      appendCatalogText(card, "h3", "catalog-subheading suggested-heading", "Suggested courses · confirm with the school");
      const suggestedList = document.createElement("ul");
      suggestedList.className = "catalog-list suggested-course-list";
      for (const course of suggestedCourses) {
        appendCatalogText(suggestedList, "li", "", course.title);
      }
      card.append(suggestedList);
    }

    academicCatalog.append(card);
  }
}

async function loadAcademicCatalog() {
  academicCatalog.replaceChildren();
  appendCatalogText(academicCatalog, "p", "catalog-message", "Loading departments and courses…");
  try {
    const response = await fetch("./backend/catalog.php", {
      headers: { Accept: "application/json" },
    });
    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || "The course catalogue could not be loaded.");
    }
    renderAcademicCatalog(result);
  } catch (error) {
    academicCatalog.replaceChildren();
    appendCatalogText(
      academicCatalog,
      "p",
      "catalog-message is-error",
      `The course catalogue is unavailable: ${error.message}`,
    );
  }
}

loadAcademicCatalog();
