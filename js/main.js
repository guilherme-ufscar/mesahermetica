/* ==============================================
   MESA HERMÉTICA — MAIN.JS
   Vanilla JS — Zero dependências
   Padrão: Coder Master
   ============================================== */

(function () {
  'use strict';

  // ─────────────────────────────────────────────
  // 1. DOM CACHE
  // ─────────────────────────────────────────────
  const header = document.querySelector('.header');
  const menuToggle = document.querySelector('.header__menu-toggle');
  const navMenu = document.getElementById('nav-menu');
  const navLinks = document.querySelectorAll('.header__link');
  const animatedElements = document.querySelectorAll('.animate-on-scroll');

  // ─────────────────────────────────────────────
  // 2. HEADER — Scroll Effect
  // ─────────────────────────────────────────────
  function handleHeaderScroll() {
    if (window.scrollY > 50) {
      header.classList.add('header--scrolled');
    } else {
      header.classList.remove('header--scrolled');
    }
  }

  // ─────────────────────────────────────────────
  // 3. MOBILE MENU — Toggle
  // ─────────────────────────────────────────────
  function toggleMenu() {
    const isOpen = navMenu.classList.toggle('is-open');
    menuToggle.classList.toggle('is-active', isOpen);
    menuToggle.setAttribute('aria-expanded', isOpen);

    // Trap body scroll when menu is open
    document.body.style.overflow = isOpen ? 'hidden' : '';
  }

  function closeMenu() {
    navMenu.classList.remove('is-open');
    menuToggle.classList.remove('is-active');
    menuToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  // ─────────────────────────────────────────────
  // 4. SCROLL REVEAL — Intersection Observer
  // ─────────────────────────────────────────────
  function initScrollReveal() {
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion) {
      // Show all elements immediately
      animatedElements.forEach(function (el) {
        el.classList.add('is-visible');
      });
      return;
    }

    var observerOptions = {
      root: null,
      rootMargin: '0px 0px -60px 0px',
      threshold: 0.1
    };

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    animatedElements.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ─────────────────────────────────────────────
  // 5. SMOOTH SCROLL — Nav Links
  // ─────────────────────────────────────────────
  function handleNavClick(e) {
    var href = this.getAttribute('href');
    if (href && href.startsWith('#')) {
      e.preventDefault();
      var target = document.querySelector(href);
      if (target) {
        var headerHeight = header.offsetHeight;
        var targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;

        window.scrollTo({
          top: targetPosition,
          behavior: 'smooth'
        });
      }
      closeMenu();
    }
  }

  // ─────────────────────────────────────────────
  // 6. ACTIVE NAV LINK — Scroll Spy
  // ─────────────────────────────────────────────
  function updateActiveNav() {
    var sections = document.querySelectorAll('section[id]');
    var scrollPos = window.scrollY + header.offsetHeight + 100;

    sections.forEach(function (section) {
      var sectionTop = section.offsetTop;
      var sectionHeight = section.offsetHeight;
      var sectionId = section.getAttribute('id');

      if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
        navLinks.forEach(function (link) {
          link.classList.remove('is-active');
          if (link.getAttribute('href') === '#' + sectionId) {
            link.classList.add('is-active');
          }
        });
      }
    });
  }

  // ─────────────────────────────────────────────
  // 7. CLOSE MENU ON OUTSIDE CLICK
  // ─────────────────────────────────────────────
  function handleOutsideClick(e) {
    if (navMenu.classList.contains('is-open') &&
        !navMenu.contains(e.target) &&
        !menuToggle.contains(e.target)) {
      closeMenu();
    }
  }

  // ─────────────────────────────────────────────
  // 8. CLOSE MENU ON ESCAPE KEY
  // ─────────────────────────────────────────────
  function handleEscapeKey(e) {
    if (e.key === 'Escape' && navMenu.classList.contains('is-open')) {
      closeMenu();
      menuToggle.focus();
    }
  }

  // ─────────────────────────────────────────────
  // 9. THROTTLE UTILITY
  // ─────────────────────────────────────────────
  function throttle(fn, delay) {
    var lastCall = 0;
    return function () {
      var now = Date.now();
      if (now - lastCall >= delay) {
        lastCall = now;
        fn.apply(this, arguments);
      }
    };
  }

  // ─────────────────────────────────────────────
  // 10. EVENT LISTENERS
  // ─────────────────────────────────────────────
  function init() {
    // Scroll events (throttled)
    var throttledScroll = throttle(function () {
      handleHeaderScroll();
      updateActiveNav();
    }, 100);

    window.addEventListener('scroll', throttledScroll, { passive: true });

    // Mobile menu toggle
    if (menuToggle) {
      menuToggle.addEventListener('click', toggleMenu);
    }

    // Nav link clicks — smooth scroll
    navLinks.forEach(function (link) {
      link.addEventListener('click', handleNavClick);
    });

    // Close menu on outside click
    document.addEventListener('click', handleOutsideClick);

    // Close menu on Escape
    document.addEventListener('keydown', handleEscapeKey);

    // Close menu on resize to desktop
    window.addEventListener('resize', throttle(function () {
      if (window.innerWidth > 768) {
        closeMenu();
      }
    }, 200));

    // Initialize scroll reveal
    initScrollReveal();

    // Initial calls
    handleHeaderScroll();
  }

  // ─────────────────────────────────────────────
  // 11. DOM READY
  // ─────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
