(() => {
  const MODAL_SELECTOR = ".modal, .modal-creation, .contact-modal, .corbeille-modal";
  const FOCUSABLE_SELECTOR = [
    "a[href]",
    "button:not([disabled])",
    "input:not([disabled]):not([type='hidden'])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "[tabindex]:not([tabindex='-1'])",
  ].join(", ");

  const previousFocusMap = new WeakMap();
  const visibleStateMap = new WeakMap();
  const syncingModals = new WeakSet();

  function isVisible(element) {
    if (!(element instanceof HTMLElement)) {
      return false;
    }

    if (element.hasAttribute("hidden")) {
      return false;
    }

    const style = window.getComputedStyle(element);
    if (style.display === "none" || style.visibility === "hidden") {
      return false;
    }

    const rect = element.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
  }

  function ensureDialogAttributes(modal) {
    if (!modal.hasAttribute("role")) {
      modal.setAttribute("role", "dialog");
    }

    if (!modal.hasAttribute("aria-modal")) {
      modal.setAttribute("aria-modal", "true");
    }
  }

  function getFocusableElements(modal) {
    if (!(modal instanceof HTMLElement)) {
      return [];
    }

    return Array.from(modal.querySelectorAll(FOCUSABLE_SELECTOR))
      .filter((node) => node instanceof HTMLElement)
      .filter((node) => !node.hasAttribute("hidden"))
      .filter((node) => window.getComputedStyle(node).visibility !== "hidden")
      .filter((node) => window.getComputedStyle(node).display !== "none");
  }

  function focusFirstElement(modal) {
    const focusables = getFocusableElements(modal);
    const target = focusables[0] || modal;
    if (target instanceof HTMLElement) {
      target.focus({ preventScroll: true });
    }
  }

  function restorePreviousFocus(modal) {
    const previous = previousFocusMap.get(modal);
    if (previous instanceof HTMLElement && document.contains(previous)) {
      previous.focus({ preventScroll: true });
    }
  }

  function syncModalState(modal) {
    if (!(modal instanceof HTMLElement)) {
      return;
    }
    if (syncingModals.has(modal)) {
      return;
    }
    syncingModals.add(modal);

    try {
      ensureDialogAttributes(modal);
      const isNowVisible = isVisible(modal);
      const wasVisible = visibleStateMap.get(modal) === true;

      if (isNowVisible) {
        if (modal.getAttribute("aria-hidden") !== "false") {
          modal.setAttribute("aria-hidden", "false");
        }
        if (modal.hasAttribute("inert")) {
          modal.removeAttribute("inert");
        }
      } else {
        if (modal.getAttribute("aria-hidden") !== "true") {
          modal.setAttribute("aria-hidden", "true");
        }
        if (!modal.hasAttribute("inert")) {
          modal.setAttribute("inert", "");
        }
      }

      if (isNowVisible && !wasVisible) {
        const activeElement = document.activeElement;
        if (activeElement instanceof HTMLElement && !modal.contains(activeElement)) {
          previousFocusMap.set(modal, activeElement);
        }
        window.requestAnimationFrame(() => {
          focusFirstElement(modal);
        });
      }

      if (!isNowVisible && wasVisible) {
        restorePreviousFocus(modal);
      }

      visibleStateMap.set(modal, isNowVisible);
    } finally {
      syncingModals.delete(modal);
    }
  }

  function observeModal(modal) {
    if (!(modal instanceof HTMLElement)) {
      return;
    }

    if (modal.dataset.a11yObserved === "1") {
      syncModalState(modal);
      return;
    }

    modal.dataset.a11yObserved = "1";
    syncModalState(modal);

    const observer = new MutationObserver(() => {
      syncModalState(modal);
    });

    observer.observe(modal, {
      attributes: true,
      attributeFilter: ["class", "style", "hidden", "aria-hidden"],
    });
  }

  function getTopVisibleModal() {
    const modals = Array.from(document.querySelectorAll(MODAL_SELECTOR))
      .filter((node) => node instanceof HTMLElement)
      .filter((node) => isVisible(node));

    if (modals.length === 0) {
      return null;
    }

    return modals[modals.length - 1];
  }

  function onKeydown(event) {
    const activeModal = getTopVisibleModal();
    if (!(activeModal instanceof HTMLElement)) {
      return;
    }

    if (event.key === "Escape") {
      const closeButton = activeModal.querySelector(
        "[data-article-close-modal], [data-projet-close-modal], [data-webtv-close-modal], " +
          "[data-users-close-modal], [data-contact-close-modal], [data-comment-close-modal], " +
          "[data-webtv-close-creation], [data-article-close-creation], [data-projet-close-creation], " +
          "[data-corbeille-close], .close-modal, .close-btn, .close-modal-creation, .contact-close-modal, .corbeille-modal-close"
      );

      if (closeButton instanceof HTMLElement) {
        closeButton.click();
        event.preventDefault();
      }
      return;
    }

    if (event.key !== "Tab") {
      return;
    }

    const focusables = getFocusableElements(activeModal);
    if (focusables.length === 0) {
      activeModal.focus({ preventScroll: true });
      event.preventDefault();
      return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const current = document.activeElement;

    if (event.shiftKey && current === first) {
      last.focus({ preventScroll: true });
      event.preventDefault();
      return;
    }

    if (!event.shiftKey && current === last) {
      first.focus({ preventScroll: true });
      event.preventDefault();
    }
  }

  function init() {
    document.querySelectorAll(MODAL_SELECTOR).forEach(observeModal);

    const rootObserver = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        mutation.addedNodes.forEach((node) => {
          if (!(node instanceof HTMLElement)) {
            return;
          }

          if (node.matches(MODAL_SELECTOR)) {
            observeModal(node);
          }

          node.querySelectorAll?.(MODAL_SELECTOR).forEach(observeModal);
        });
      }
    });

    rootObserver.observe(document.body, {
      childList: true,
      subtree: true,
    });

    document.addEventListener("keydown", onKeydown, true);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
