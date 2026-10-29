const slides = Array.from(document.querySelectorAll(".hero-slide"));
const slideDots = Array.from(document.querySelectorAll(".slider-dot"));
const previousButton = document.querySelector(".previous-slide");
const nextButton = document.querySelector(".next-slide");
const menuToggle = document.querySelector(".menu-toggle");
const navigation = document.querySelector(".site-navigation");
const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
let currentSlide = 0;
let slideTimer;

function showSlide(index) {
  currentSlide = (index + slides.length) % slides.length;
  slides.forEach((slide, slideIndex) => {
    const isActive = slideIndex === currentSlide;
    slide.hidden = !isActive;
    slide.classList.toggle("is-active", isActive);
    slideDots[slideIndex].classList.toggle("is-active", isActive);
    if (isActive) {
      slideDots[slideIndex].setAttribute("aria-current", "true");
    } else {
      slideDots[slideIndex].removeAttribute("aria-current");
    }
  });
}

function restartSlideTimer() {
  window.clearInterval(slideTimer);
  if (!reducedMotion.matches) {
    slideTimer = window.setInterval(() => showSlide(currentSlide + 1), 6000);
  }
}

previousButton.addEventListener("click", () => {
  showSlide(currentSlide - 1);
  restartSlideTimer();
});

nextButton.addEventListener("click", () => {
  showSlide(currentSlide + 1);
  restartSlideTimer();
});

slideDots.forEach((dot, index) => {
  dot.addEventListener("click", () => {
    showSlide(index);
    restartSlideTimer();
  });
});

document.querySelector(".hero").addEventListener("mouseenter", () => window.clearInterval(slideTimer));
document.querySelector(".hero").addEventListener("mouseleave", restartSlideTimer);
document.querySelector(".hero").addEventListener("focusin", () => window.clearInterval(slideTimer));
document.querySelector(".hero").addEventListener("focusout", restartSlideTimer);

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

document.querySelector("#current-year").textContent = String(new Date().getFullYear());

const publicAnnouncementList = document.querySelector("#public-announcement-list");
if (publicAnnouncementList) {
  fetch("./backend/announcements.php?public=1", { headers: { Accept: "application/json" } })
    .then(async (response) => {
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.error || "Announcements could not be loaded.");
      }
      return result.announcements;
    })
    .then((announcements) => {
      publicAnnouncementList.replaceChildren();
      if (announcements.length === 0) {
        const message = document.createElement("p");
        message.className = "announcement-message";
        message.textContent = "There are no announcements at this time.";
        publicAnnouncementList.append(message);
        return;
      }

      for (const announcement of announcements) {
        const card = document.createElement("article");
        card.className = "announcement-card";
        const date = document.createElement("time");
        date.dateTime = announcement.published_at;
        date.textContent = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" })
          .format(new Date(`${announcement.published_at.replace(" ", "T")}Z`));
        const title = document.createElement("h3");
        title.textContent = announcement.title;
        const body = document.createElement("p");
        body.textContent = announcement.body;
        card.append(date, title, body);
        publicAnnouncementList.append(card);
      }
    })
    .catch((error) => {
      const message = document.createElement("p");
      message.className = "announcement-message";
      message.textContent = `Announcements are unavailable: ${error.message}`;
      publicAnnouncementList.replaceChildren(message);
    });
}

restartSlideTimer();
