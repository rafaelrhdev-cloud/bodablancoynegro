(function () {
  "use strict";

  /* ============================================================
     0. Rutas de la API (ajusta si cambias la estructura de carpetas)
     Esperado: la carpeta /api vive al mismo nivel que este sitio
     (es decir, dentro del mismo docroot, en /api/*.php)
  ============================================================ */
  const API = {
    checkName: "api/check_name.php",
    confirm: "api/confirm.php",
  };

  /* ============================================================
     1. ID de dispositivo persistente (localStorage + cookie de respaldo)
     Esto se usa junto con el control en servidor para limitar
     1 confirmación por dispositivo/navegador.
  ============================================================ */
  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, (c) => {
      const r = (Math.random() * 16) | 0;
      const v = c === "x" ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  function getCookie(name) {
    const m = document.cookie.match(new RegExp("(?:^|; )" + name + "=([^;]*)"));
    return m ? decodeURIComponent(m[1]) : null;
  }
  function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${d.toUTCString()}; path=/; SameSite=Lax`;
  }

  function getDeviceId() {
    let id = null;
    try { id = localStorage.getItem("wid"); } catch (e) {}
    if (!id) id = getCookie("wid");
    if (!id) {
      id = uuid();
      try { localStorage.setItem("wid", id); } catch (e) {}
      setCookie("wid", id, 400);
    } else {
      // asegura que ambos almacenes estén sincronizados
      try { localStorage.setItem("wid", id); } catch (e) {}
      setCookie("wid", id, 400);
    }
    return id;
  }

  function hasAlreadyConfirmedLocally() {
    try { return localStorage.getItem("wid_confirmed") === "1"; } catch (e) {}
    return getCookie("wid_confirmed") === "1";
  }
  function markConfirmedLocally() {
    try { localStorage.setItem("wid_confirmed", "1"); } catch (e) {}
    setCookie("wid_confirmed", "1", 400);
  }

  /* ============================================================
     2. Intro — puerta de catedral
  ============================================================ */
  const doorIntro = document.getElementById("door-intro");
  const openBtn = document.getElementById("open-door-btn");
  const site = document.getElementById("site");

  function openDoor() {
    if (!doorIntro || doorIntro.classList.contains("opening")) return;
    doorIntro.classList.add("opening");
    document.body.style.overflow = "hidden";
    setTimeout(() => {
      doorIntro.classList.add("hidden-intro");
      document.body.style.overflow = "";
      site.hidden = false;
      requestAnimationFrame(initReveal);
      try { sessionStorage.setItem("door_opened", "1"); } catch (e) {}
    }, 2150);
  }

  if (openBtn) openBtn.addEventListener("click", openDoor);

  // Si el usuario ya abrió la puerta en esta sesión de navegación, saltar la intro
  try {
    if (sessionStorage.getItem("door_opened") === "1" && doorIntro) {
      doorIntro.classList.add("hidden-intro");
      document.body.style.overflow = "";
      site.hidden = false;
    }
  } catch (e) {}

  /* ============================================================
     3. Reveal on scroll
  ============================================================ */
  function initReveal() {
    const els = document.querySelectorAll(".reveal");
    if (!("IntersectionObserver" in window)) {
      els.forEach((el) => el.classList.add("in-view"));
      return;
    }
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("in-view");
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
    );
    els.forEach((el) => io.observe(el));
  }
  // por si el sitio ya estaba visible (intro saltada)
  if (site && !site.hidden) initReveal();

  /* ============================================================
     4. Navegación móvil
  ============================================================ */
  const navToggle = document.getElementById("nav-toggle");
  const navLinks = document.getElementById("nav-links");
  if (navToggle && navLinks) {
    navToggle.addEventListener("click", () => navLinks.classList.toggle("open"));
    navLinks.querySelectorAll("a").forEach((a) =>
      a.addEventListener("click", () => navLinks.classList.remove("open"))
    );
  }

  /* ============================================================
     5. RSVP — flujo de 2 pasos con límite de 5 pases y 1 por dispositivo
  ============================================================ */
  const nameForm = document.getElementById("rsvp-name-form");
  const confirmForm = document.getElementById("rsvp-confirm-form");
  const doneStep = document.querySelector('.rsvp-step[data-step="3"]');
  const nameInput = document.getElementById("guest-name");
  const nameMsg = document.getElementById("rsvp-name-msg");
  const greet = document.getElementById("rsvp-greet");
  const passesSelect = document.getElementById("guest-passes");
  const confirmMsg = document.getElementById("rsvp-confirm-msg");
  const doneText = document.getElementById("rsvp-done-text");

  const MAX_PASSES_HARD_CAP = 5;
  let validatedGuestName = "";
  let allowedPasses = 1;

  function showMsg(el, text, type) {
    el.textContent = text;
    el.className = "rsvp-msg" + (type ? " " + type : "");
  }

  function lockRsvpAlreadyDone(message) {
    nameForm.hidden = true;
    confirmForm.hidden = true;
    doneStep.hidden = false;
    doneText.textContent = message;
  }

  // Si este dispositivo ya confirmó antes, no permitir un segundo intento.
  if (hasAlreadyConfirmedLocally()) {
    document.addEventListener("DOMContentLoaded", () => {
      lockRsvpAlreadyDone("Ya registramos tu confirmación desde este dispositivo. ¡Gracias, te esperamos!");
    });
  }

  if (nameForm) {
    nameForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      if (hasAlreadyConfirmedLocally()) {
        lockRsvpAlreadyDone("Ya registramos tu confirmación desde este dispositivo.");
        return;
      }
      const name = nameInput.value.trim();
      if (!name) return;

      const submitBtn = nameForm.querySelector("button");
      submitBtn.disabled = true;
      showMsg(nameMsg, "Validando…", "");

      try {
        const res = await fetch(API.checkName, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name, device_id: getDeviceId() }),
        });
        const data = await res.json();

        if (!data.ok) {
          showMsg(nameMsg, data.message || "No encontramos ese nombre en la lista de invitados.", "error");
          submitBtn.disabled = false;
          return;
        }

        validatedGuestName = data.guest_name;
        allowedPasses = Math.min(parseInt(data.allowed_passes, 10) || 1, MAX_PASSES_HARD_CAP);

        passesSelect.innerHTML = "";
        for (let i = 1; i <= allowedPasses; i++) {
          const opt = document.createElement("option");
          opt.value = i;
          opt.textContent = i === 1 ? "1 persona" : `${i} personas`;
          passesSelect.appendChild(opt);
        }

        greet.textContent = `Hola, ${validatedGuestName}. Cuentas con ${allowedPasses} ${allowedPasses === 1 ? "pase" : "pases"}.`;
        nameForm.hidden = true;
        confirmForm.hidden = false;
      } catch (err) {
        showMsg(nameMsg, "Ocurrió un error de conexión. Intenta de nuevo.", "error");
        submitBtn.disabled = false;
      }
    });
  }

  if (confirmForm) {
    confirmForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      const submitBtn = confirmForm.querySelector("button");
      submitBtn.disabled = true;
      showMsg(confirmMsg, "Enviando…", "");

      const passes = Math.min(parseInt(passesSelect.value, 10) || 1, MAX_PASSES_HARD_CAP);

      try {
        const res = await fetch(API.confirm, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            name: validatedGuestName,
            passes,
            device_id: getDeviceId(),
          }),
        });
        const data = await res.json();

        if (!data.ok) {
          showMsg(confirmMsg, data.message || "No fue posible confirmar tu asistencia.", "error");
          submitBtn.disabled = false;
          return;
        }

        markConfirmedLocally();
        confirmForm.hidden = true;
        doneStep.hidden = false;
        doneText.textContent = `Gracias, ${validatedGuestName}. Confirmamos ${passes} ${passes === 1 ? "persona" : "personas"}. ¡Nos vemos en la boda!`;
      } catch (err) {
        showMsg(confirmMsg, "Ocurrió un error de conexión. Intenta de nuevo.", "error");
        submitBtn.disabled = false;
      }
    });
  }
})();
