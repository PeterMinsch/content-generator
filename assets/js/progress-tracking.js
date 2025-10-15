/**
 * SEO Generator Plugin - Progress Tracking
 * Real-time progress updates for AI generation and file uploads
 *
 * @package SEOGenerator
 */

(function() {
  'use strict';

  /**
   * Progress Tracker Class
   * Manages progress bar updates and status indicators
   */
  class ProgressTracker {
    constructor(container) {
      this.container = container;
      this.progressBar = container.querySelector('.seo-progress-bar__fill');
      this.progressList = container.querySelector('.seo-progress-list');
      this.footer = container.querySelector('.seo-progress-card__footer');
      this.currentProgress = 0;
      this.items = [];
      this.startTime = Date.now();
    }

    /**
     * Update progress percentage
     * @param {number} percentage - Progress percentage (0-100)
     */
    updateProgress(percentage) {
      this.currentProgress = Math.min(100, Math.max(0, percentage));
      if (this.progressBar) {
        this.progressBar.style.width = `${this.currentProgress}%`;
        this.progressBar.setAttribute('aria-valuenow', this.currentProgress);
      }

      // Update estimated completion time
      this.updateEstimatedTime();

      // Announce progress to screen readers
      this.announceProgress();
    }

    /**
     * Add progress item
     * @param {string} id - Item ID
     * @param {string} label - Item label
     * @param {string} status - Item status (queued, generating, completed, error)
     */
    addItem(id, label, status = 'queued') {
      const item = {
        id,
        label,
        status,
        element: this.createItemElement(id, label, status)
      };

      this.items.push(item);
      if (this.progressList) {
        this.progressList.appendChild(item.element);
      }
    }

    /**
     * Update item status
     * @param {string} id - Item ID
     * @param {string} status - New status
     */
    updateItemStatus(id, status) {
      const item = this.items.find(i => i.id === id);
      if (!item) return;

      item.status = status;
      const icon = item.element.querySelector('.seo-progress-item__icon');
      const statusClass = this.getStatusClass(status);

      // Update classes
      item.element.className = `seo-progress-item seo-progress-item--${status}`;
      if (icon) {
        icon.className = 'seo-progress-item__icon';
      }

      // Update icon
      if (icon) {
        icon.textContent = this.getStatusIcon(status);
      }

      // Update label if status is error
      if (status === 'error') {
        const label = item.element.querySelector('.seo-progress-item__label');
        if (label) {
          label.textContent = `${item.label} - Failed`;
        }
      }

      // Announce status change
      this.announceItemStatus(item.label, status);
    }

    /**
     * Create progress item element
     */
    createItemElement(id, label, status) {
      const item = document.createElement('div');
      item.className = `seo-progress-item seo-progress-item--${status}`;
      item.setAttribute('data-item-id', id);

      const icon = document.createElement('span');
      icon.className = 'seo-progress-item__icon';
      icon.textContent = this.getStatusIcon(status);
      icon.setAttribute('aria-hidden', 'true');

      const labelElement = document.createElement('span');
      labelElement.className = 'seo-progress-item__label';
      labelElement.textContent = label;

      item.appendChild(icon);
      item.appendChild(labelElement);

      return item;
    }

    /**
     * Get icon for status
     */
    getStatusIcon(status) {
      const icons = {
        queued: '⋯',
        generating: '⟳',
        completed: '✓',
        error: '✗'
      };
      return icons[status] || '⋯';
    }

    /**
     * Get CSS class for status
     */
    getStatusClass(status) {
      return `seo-progress-item--${status}`;
    }

    /**
     * Update estimated completion time
     */
    updateEstimatedTime() {
      if (!this.footer || this.currentProgress === 0) return;

      const elapsed = Date.now() - this.startTime;
      const estimatedTotal = (elapsed / this.currentProgress) * 100;
      const remaining = estimatedTotal - elapsed;

      if (remaining > 0 && this.currentProgress < 100) {
        const minutes = Math.floor(remaining / 60000);
        const seconds = Math.floor((remaining % 60000) / 1000);

        let timeString = '';
        if (minutes > 0) {
          timeString = `${minutes} min ${seconds} sec`;
        } else {
          timeString = `${seconds} sec`;
        }

        this.footer.textContent = `Est. completion: ${timeString}`;
      } else if (this.currentProgress === 100) {
        this.footer.textContent = 'Completed!';
      }
    }

    /**
     * Announce progress to screen readers
     */
    announceProgress() {
      let liveRegion = document.getElementById('progress-announcement');
      if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = 'progress-announcement';
        liveRegion.className = 'sr-live-polite';
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        document.body.appendChild(liveRegion);
      }

      // Only announce at intervals (every 10%)
      if (this.currentProgress % 10 === 0) {
        liveRegion.textContent = `Progress: ${this.currentProgress}%`;
      }
    }

    /**
     * Announce item status change
     */
    announceItemStatus(label, status) {
      let liveRegion = document.getElementById('progress-announcement');
      if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = 'progress-announcement';
        liveRegion.className = 'sr-live-polite';
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        document.body.appendChild(liveRegion);
      }

      const statusText = {
        generating: 'generating',
        completed: 'completed',
        error: 'failed'
      };

      if (statusText[status]) {
        liveRegion.textContent = `${label}: ${statusText[status]}`;
      }
    }

    /**
     * Complete progress
     */
    complete() {
      this.updateProgress(100);
      if (this.footer) {
        this.footer.textContent = 'All tasks completed!';
      }

      // Trigger completion event
      const event = new CustomEvent('seo-progress-complete');
      this.container.dispatchEvent(event);
    }

    /**
     * Reset progress
     */
    reset() {
      this.currentProgress = 0;
      this.items = [];
      this.startTime = Date.now();

      if (this.progressBar) {
        this.progressBar.style.width = '0%';
      }

      if (this.progressList) {
        this.progressList.innerHTML = '';
      }

      if (this.footer) {
        this.footer.textContent = '';
      }
    }
  }

  /**
   * Upload Progress Class
   * Manages file upload progress
   */
  class UploadProgress {
    constructor(container) {
      this.container = container;
      this.progressBar = container.querySelector('.seo-upload-progress__fill');
      this.nameElement = container.querySelector('.seo-upload-progress__name');
      this.sizeElement = container.querySelector('.seo-upload-progress__size');
      this.currentProgress = 0;
    }

    /**
     * Start upload
     * @param {string} fileName - File name
     * @param {number} fileSize - File size in bytes
     */
    start(fileName, fileSize) {
      this.show();

      if (this.nameElement) {
        this.nameElement.textContent = fileName;
      }

      if (this.sizeElement) {
        this.sizeElement.textContent = this.formatFileSize(fileSize);
      }

      this.updateProgress(0);
    }

    /**
     * Update upload progress
     * @param {number} percentage - Upload percentage (0-100)
     */
    updateProgress(percentage) {
      this.currentProgress = Math.min(100, Math.max(0, percentage));

      if (this.progressBar) {
        this.progressBar.style.width = `${this.currentProgress}%`;
        this.progressBar.setAttribute('aria-valuenow', this.currentProgress);
      }

      // Announce progress
      this.announceProgress();
    }

    /**
     * Mark upload as complete
     */
    complete() {
      this.updateProgress(100);
      this.container.classList.add('seo-upload-progress--success');

      // Add success animation
      setTimeout(() => {
        this.hide();
      }, 2000);

      // Trigger completion event
      const event = new CustomEvent('seo-upload-complete');
      this.container.dispatchEvent(event);
    }

    /**
     * Mark upload as error
     * @param {string} errorMessage - Error message
     */
    error(errorMessage) {
      this.container.classList.add('seo-upload-progress--error');

      if (this.nameElement) {
        this.nameElement.textContent = `Error: ${errorMessage}`;
      }

      // Trigger error event
      const event = new CustomEvent('seo-upload-error', {
        detail: { message: errorMessage }
      });
      this.container.dispatchEvent(event);
    }

    /**
     * Show progress indicator
     */
    show() {
      this.container.style.display = 'block';
      this.container.classList.add('animate-fadeIn');
    }

    /**
     * Hide progress indicator
     */
    hide() {
      this.container.classList.add('animate-fadeOut');
      setTimeout(() => {
        this.container.style.display = 'none';
        this.container.classList.remove('animate-fadeOut');
        this.reset();
      }, 300);
    }

    /**
     * Reset upload progress
     */
    reset() {
      this.currentProgress = 0;
      this.container.classList.remove('seo-upload-progress--success', 'seo-upload-progress--error');

      if (this.progressBar) {
        this.progressBar.style.width = '0%';
      }

      if (this.nameElement) {
        this.nameElement.textContent = '';
      }

      if (this.sizeElement) {
        this.sizeElement.textContent = '';
      }
    }

    /**
     * Format file size
     * @param {number} bytes - File size in bytes
     * @returns {string} Formatted size
     */
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';

      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));

      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Announce progress to screen readers
     */
    announceProgress() {
      let liveRegion = document.getElementById('upload-announcement');
      if (!liveRegion) {
        liveRegion = document.createElement('div');
        liveRegion.id = 'upload-announcement';
        liveRegion.className = 'sr-live-polite';
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        document.body.appendChild(liveRegion);
      }

      // Only announce at intervals (every 25%)
      if (this.currentProgress % 25 === 0 || this.currentProgress === 100) {
        liveRegion.textContent = `Upload progress: ${this.currentProgress}%`;
      }
    }
  }

  /**
   * Real-time Progress Polling
   * Poll server for generation status updates
   */
  class ProgressPoller {
    constructor(url, interval = 2000) {
      this.url = url;
      this.interval = interval;
      this.polling = false;
      this.pollTimeout = null;
    }

    /**
     * Start polling
     * @param {Function} callback - Callback function for updates
     */
    start(callback) {
      if (this.polling) return;

      this.polling = true;
      this.poll(callback);
    }

    /**
     * Stop polling
     */
    stop() {
      this.polling = false;
      if (this.pollTimeout) {
        clearTimeout(this.pollTimeout);
        this.pollTimeout = null;
      }
    }

    /**
     * Poll server for updates
     */
    async poll(callback) {
      if (!this.polling) return;

      try {
        const response = await fetch(this.url);
        const data = await response.json();

        if (callback && typeof callback === 'function') {
          callback(data);
        }

        // Continue polling if not complete
        if (data.status !== 'completed' && data.status !== 'error') {
          this.pollTimeout = setTimeout(() => this.poll(callback), this.interval);
        } else {
          this.stop();
        }
      } catch (error) {
        console.error('Polling error:', error);
        this.stop();
      }
    }
  }

  /**
   * Initialize progress trackers
   */
  function init() {
    // Initialize AI generation progress cards
    const progressCards = document.querySelectorAll('.seo-progress-card');
    progressCards.forEach(card => {
      const tracker = new ProgressTracker(card);
      card.progressTracker = tracker; // Store reference
    });

    // Initialize upload progress indicators
    const uploadProgress = document.querySelectorAll('.seo-upload-progress');
    uploadProgress.forEach(progress => {
      const tracker = new UploadProgress(progress);
      progress.uploadTracker = tracker; // Store reference
    });

    // Expose classes globally for use in other scripts
    window.SEOProgressTracker = ProgressTracker;
    window.SEOUploadProgress = UploadProgress;
    window.SEOProgressPoller = ProgressPoller;
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
