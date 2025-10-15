/**
 * SEO Generator Plugin - UI Interactions
 * Micro-interactions and component behaviors
 *
 * @package SEOGenerator
 */

(function() {
  'use strict';

  /**
   * Initialize all interactions when DOM is ready
   */
  function init() {
    initDropZone();
    initButtons();
    initDropdowns();
    initSearch();
    initFilters();
    initSidebar();
    initActionMenus();
    initModals();
    initTooltips();
  }

  /**
   * File Drop Zone Interactions
   * Handles drag-and-drop file upload
   */
  function initDropZone() {
    const dropZones = document.querySelectorAll('.seo-drop-zone');

    dropZones.forEach(dropZone => {
      const fileInput = dropZone.querySelector('input[type="file"]');

      // Prevent default drag behaviors
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
      });

      // Highlight drop zone when dragging over it
      ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
          dropZone.classList.add('drag-over');
        }, false);
      });

      ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
          dropZone.classList.remove('drag-over');
        }, false);
      });

      // Handle file drop
      dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        handleFiles(files, fileInput);
      }, false);

      // Handle click to browse
      dropZone.addEventListener('click', () => {
        fileInput.click();
      });

      // Handle file selection via input
      fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files, fileInput);
      });
    });
  }

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  function handleFiles(files, fileInput) {
    if (files.length > 0) {
      // Trigger custom event with file data
      const event = new CustomEvent('seo-file-selected', {
        detail: { files: files }
      });
      document.dispatchEvent(event);

      // Update UI with file name
      const fileName = files[0].name;
      const dropZone = fileInput.closest('.seo-drop-zone');
      const textElement = dropZone.querySelector('.seo-drop-zone__text');
      if (textElement) {
        textElement.textContent = `Selected: ${fileName}`;
      }
    }
  }

  /**
   * Button Click Animations
   * Add ripple effect and visual feedback
   */
  function initButtons() {
    const buttons = document.querySelectorAll('.seo-btn-primary, .seo-btn-secondary, .seo-btn-danger');

    buttons.forEach(button => {
      button.addEventListener('click', function(e) {
        // Create ripple element
        const ripple = document.createElement('span');
        ripple.classList.add('ripple-effect');

        // Calculate position
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        this.appendChild(ripple);

        // Remove ripple after animation
        setTimeout(() => {
          ripple.remove();
        }, 600);
      });
    });
  }

  /**
   * Dropdown Toggle Functionality
   * Show/hide dropdown menus
   */
  function initDropdowns() {
    const dropdownTriggers = document.querySelectorAll('[data-dropdown-trigger]');

    dropdownTriggers.forEach(trigger => {
      trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const dropdownId = trigger.getAttribute('data-dropdown-trigger');
        const dropdown = document.getElementById(dropdownId);

        if (dropdown) {
          const isOpen = dropdown.classList.contains('is-open');

          // Close all dropdowns
          closeAllDropdowns();

          // Toggle current dropdown
          if (!isOpen) {
            dropdown.classList.add('is-open');
            dropdown.classList.add('animate-slideDown');
            trigger.setAttribute('aria-expanded', 'true');

            // Focus first item in dropdown
            const firstItem = dropdown.querySelector('[role="menuitem"], a, button');
            if (firstItem) {
              firstItem.focus();
            }
          } else {
            trigger.setAttribute('aria-expanded', 'false');
          }
        }
      });

      // Keyboard support
      trigger.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          trigger.click();
        }
      });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', closeAllDropdowns);

    // Close dropdowns on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeAllDropdowns();
      }
    });
  }

  function closeAllDropdowns() {
    const openDropdowns = document.querySelectorAll('.seo-action-menu__dropdown.is-open, .seo-filter__dropdown.is-open');
    openDropdowns.forEach(dropdown => {
      dropdown.classList.remove('is-open');
      dropdown.classList.remove('animate-slideDown');

      // Reset aria-expanded on trigger
      const trigger = document.querySelector(`[data-dropdown-trigger="${dropdown.id}"]`);
      if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /**
   * Search Input Debouncing
   * Prevent excessive searches while typing
   */
  function initSearch() {
    const searchInputs = document.querySelectorAll('.seo-search__input');
    let searchTimeout;

    searchInputs.forEach(input => {
      input.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
          const searchTerm = e.target.value.trim();

          // Trigger custom search event
          const event = new CustomEvent('seo-search', {
            detail: { searchTerm: searchTerm }
          });
          document.dispatchEvent(event);

          // Update ARIA live region
          announceSearchResults(searchTerm);
        }, 300);
      });

      // Clear search on Escape
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          input.value = '';
          input.dispatchEvent(new Event('input'));
        }
      });
    });
  }

  function announceSearchResults(searchTerm) {
    let liveRegion = document.getElementById('search-results-announcement');
    if (!liveRegion) {
      liveRegion = document.createElement('div');
      liveRegion.id = 'search-results-announcement';
      liveRegion.className = 'sr-live-polite';
      liveRegion.setAttribute('role', 'status');
      liveRegion.setAttribute('aria-live', 'polite');
      document.body.appendChild(liveRegion);
    }

    if (searchTerm) {
      liveRegion.textContent = `Searching for "${searchTerm}"...`;
    } else {
      liveRegion.textContent = 'Showing all results';
    }
  }

  /**
   * Filter State Management
   * Track and update active filters
   */
  function initFilters() {
    const filterButtons = document.querySelectorAll('.seo-filter__button');
    const activeFilters = new Set();

    filterButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        const filterType = button.getAttribute('data-filter-type');
        const filterValue = button.getAttribute('data-filter-value');

        // Toggle filter
        const filterKey = `${filterType}:${filterValue}`;
        if (activeFilters.has(filterKey)) {
          activeFilters.delete(filterKey);
        } else {
          activeFilters.add(filterKey);
        }

        // Update badge count
        updateFilterBadge(button, activeFilters.size);

        // Trigger custom filter event
        const event = new CustomEvent('seo-filter-change', {
          detail: {
            filters: Array.from(activeFilters),
            filterType: filterType,
            filterValue: filterValue
          }
        });
        document.dispatchEvent(event);
      });
    });

    // Clear all filters
    const clearButtons = document.querySelectorAll('[data-clear-filters]');
    clearButtons.forEach(button => {
      button.addEventListener('click', () => {
        activeFilters.clear();
        filterButtons.forEach(btn => updateFilterBadge(btn, 0));

        const event = new CustomEvent('seo-filters-cleared');
        document.dispatchEvent(event);
      });
    });
  }

  function updateFilterBadge(button, count) {
    let badge = button.querySelector('.seo-filter__badge');
    if (count > 0) {
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'seo-filter__badge';
        button.appendChild(badge);
      }
      badge.textContent = count;
    } else if (badge) {
      badge.remove();
    }
  }

  /**
   * Sidebar Toggle for Mobile/Tablet
   * Hamburger menu functionality
   */
  function initSidebar() {
    const hamburger = document.querySelector('.seo-hamburger');
    const sidebar = document.querySelector('.seo-sidebar');
    const overlay = document.querySelector('.seo-sidebar-overlay');

    if (!hamburger || !sidebar) return;

    hamburger.addEventListener('click', () => {
      const isOpen = sidebar.classList.contains('is-open');

      if (isOpen) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    // Close sidebar when clicking overlay
    if (overlay) {
      overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
        closeSidebar();
      }
    });

    function openSidebar() {
      sidebar.classList.add('is-open');
      hamburger.classList.add('is-open');
      if (overlay) overlay.classList.add('is-visible');

      hamburger.setAttribute('aria-expanded', 'true');
      sidebar.setAttribute('aria-hidden', 'false');

      // Focus first navigation item
      const firstNavItem = sidebar.querySelector('.seo-sidebar__item');
      if (firstNavItem) {
        firstNavItem.focus();
      }
    }

    function closeSidebar() {
      sidebar.classList.remove('is-open');
      hamburger.classList.remove('is-open');
      if (overlay) overlay.classList.remove('is-visible');

      hamburger.setAttribute('aria-expanded', 'false');
      sidebar.setAttribute('aria-hidden', 'true');

      // Return focus to hamburger
      hamburger.focus();
    }
  }

  /**
   * Action Menu Interactions
   * Three-dot menu for list items
   */
  function initActionMenus() {
    const actionMenus = document.querySelectorAll('.seo-action-menu');

    actionMenus.forEach(menu => {
      const trigger = menu.querySelector('.seo-action-menu__trigger');
      const dropdown = menu.querySelector('.seo-action-menu__dropdown');

      if (!trigger || !dropdown) return;

      trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.contains('is-open');

        // Close all menus
        closeAllActionMenus();

        // Toggle current menu
        if (!isOpen) {
          dropdown.classList.add('is-open');
          trigger.setAttribute('aria-expanded', 'true');

          // Focus first item
          const firstItem = dropdown.querySelector('.seo-action-menu__item');
          if (firstItem) {
            firstItem.focus();
          }
        }
      });

      // Handle menu item selection
      const items = dropdown.querySelectorAll('.seo-action-menu__item');
      items.forEach(item => {
        item.addEventListener('click', (e) => {
          const action = item.getAttribute('data-action');
          const itemId = menu.closest('[data-item-id]')?.getAttribute('data-item-id');

          // Trigger custom action event
          const event = new CustomEvent('seo-action-menu-click', {
            detail: { action, itemId }
          });
          document.dispatchEvent(event);

          closeAllActionMenus();
        });
      });

      // Keyboard navigation in menu
      dropdown.addEventListener('keydown', (e) => {
        const items = Array.from(dropdown.querySelectorAll('.seo-action-menu__item'));
        const currentIndex = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          const nextIndex = (currentIndex + 1) % items.length;
          items[nextIndex].focus();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          const prevIndex = (currentIndex - 1 + items.length) % items.length;
          items[prevIndex].focus();
        } else if (e.key === 'Home') {
          e.preventDefault();
          items[0].focus();
        } else if (e.key === 'End') {
          e.preventDefault();
          items[items.length - 1].focus();
        }
      });
    });

    // Close menus when clicking outside
    document.addEventListener('click', closeAllActionMenus);
  }

  function closeAllActionMenus() {
    const openMenus = document.querySelectorAll('.seo-action-menu__dropdown.is-open');
    openMenus.forEach(menu => {
      menu.classList.remove('is-open');
      const trigger = menu.closest('.seo-action-menu')?.querySelector('.seo-action-menu__trigger');
      if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /**
   * Modal Management
   * Open/close modals with focus trapping
   */
  function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal-trigger]');

    modalTriggers.forEach(trigger => {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const modalId = trigger.getAttribute('data-modal-trigger');
        openModal(modalId);
      });
    });

    // Modal close buttons
    const closeButtons = document.querySelectorAll('[data-modal-close]');
    closeButtons.forEach(button => {
      button.addEventListener('click', () => {
        const modal = button.closest('.modal');
        if (modal) {
          closeModal(modal.id);
        }
      });
    });

    // Close on backdrop click
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
      backdrop.addEventListener('click', () => {
        const modals = document.querySelectorAll('.modal.is-open');
        modals.forEach(modal => closeModal(modal.id));
      });
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.is-open');
        openModals.forEach(modal => closeModal(modal.id));
      }
    });
  }

  let focusedElementBeforeModal;

  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Store currently focused element
    focusedElementBeforeModal = document.activeElement;

    modal.classList.add('is-open');
    modal.classList.add('animate-scaleIn');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');

    // Focus modal
    modal.setAttribute('tabindex', '-1');
    modal.focus();

    // Trap focus in modal
    trapFocus(modal);
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.remove('is-open');
    modal.classList.remove('animate-scaleIn');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');

    // Return focus to trigger
    if (focusedElementBeforeModal) {
      focusedElementBeforeModal.focus();
    }
  }

  function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled])'
    );
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];

    element.addEventListener('keydown', function(e) {
      if (e.key !== 'Tab') return;

      if (e.shiftKey) {
        if (document.activeElement === firstFocusable) {
          lastFocusable.focus();
          e.preventDefault();
        }
      } else {
        if (document.activeElement === lastFocusable) {
          firstFocusable.focus();
          e.preventDefault();
        }
      }
    });
  }

  /**
   * Tooltip Initialization
   * Show/hide tooltips on hover/focus
   */
  function initTooltips() {
    const tooltipTriggers = document.querySelectorAll('[data-tooltip]');

    tooltipTriggers.forEach(trigger => {
      let tooltip = null;

      const showTooltip = () => {
        const text = trigger.getAttribute('data-tooltip');
        tooltip = document.createElement('div');
        tooltip.className = 'seo-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        tooltip.textContent = text;
        document.body.appendChild(tooltip);

        // Position tooltip
        const rect = trigger.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.left = `${rect.left + rect.width / 2}px`;
        tooltip.style.top = `${rect.top - 10}px`;
        tooltip.style.transform = 'translate(-50%, -100%)';

        // Fade in
        setTimeout(() => {
          tooltip.classList.add('is-visible');
        }, 10);
      };

      const hideTooltip = () => {
        if (tooltip) {
          tooltip.remove();
          tooltip = null;
        }
      };

      trigger.addEventListener('mouseenter', showTooltip);
      trigger.addEventListener('mouseleave', hideTooltip);
      trigger.addEventListener('focus', showTooltip);
      trigger.addEventListener('blur', hideTooltip);
    });
  }

  /**
   * Initialize when DOM is ready
   */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
