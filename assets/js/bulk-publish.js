/**
 * Bulk Publish Dynamic Pages — Admin JavaScript
 *
 * Handles CSV upload, preview, column mapping, and publish actions.
 *
 * @package SEOGenerator
 */

(function ($) {
  'use strict';

  if (typeof bulkPublishData === 'undefined') return;

  const { ajaxUrl, nonce, pageTemplates } = bulkPublishData;

  let uploadedRows = [];
  let uploadedHeaders = [];

  // ── Step 1: CSV Upload ───────────────────────────────────────

  $('#bulk-publish-upload-form').on('submit', function (e) {
    e.preventDefault();

    const fileInput = document.getElementById('csv-file-input');
    if (!fileInput.files.length) {
      showStatus('#upload-status', 'Please select a CSV file.', 'error');
      return;
    }

    const formData = new FormData();
    formData.append('action', 'bulk_publish_upload');
    formData.append('nonce', nonce);
    formData.append('csv_file', fileInput.files[0]);

    showStatus('#upload-status', 'Uploading and parsing CSV...', 'info');

    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          uploadedHeaders = response.data.headers;
          uploadedRows = response.data.rows;

          showStatus('#upload-status',
            response.data.metadata.total_rows + ' rows found (' +
            response.data.metadata.valid_rows + ' valid).', 'success');

          renderPreview();
          $('#step-configure').slideDown();
          $('#step-publish').slideDown();
        } else {
          showStatus('#upload-status', response.data.message, 'error');
        }
      },
      error: function () {
        showStatus('#upload-status', 'Upload failed. Please try again.', 'error');
      },
    });
  });

  // ── Step 2: Preview ──────────────────────────────────────────

  function renderPreview() {
    const $thead = $('#csv-preview-thead').empty();
    const $tbody = $('#csv-preview-tbody').empty();

    // Headers.
    let headerRow = '<tr>';
    headerRow += '<th>#</th>';
    uploadedHeaders.forEach(function (h) {
      headerRow += '<th>' + escapeHtml(h) + '</th>';
    });
    headerRow += '</tr>';
    $thead.html(headerRow);

    // Rows.
    const maxPreview = Math.min(uploadedRows.length, 50);
    for (let i = 0; i < maxPreview; i++) {
      let row = '<tr><td>' + (i + 1) + '</td>';
      const cells = uploadedRows[i];
      for (let j = 0; j < uploadedHeaders.length; j++) {
        row += '<td>' + escapeHtml(cells[j] || '') + '</td>';
      }
      row += '</tr>';
      $tbody.append(row);
    }

    // Summary.
    const total = uploadedRows.length;
    const template = $('#page-template-select').val();
    const templateLabel = pageTemplates[template]
      ? pageTemplates[template].label
      : template;

    $('#csv-summary').html(
      '<strong>' + total + '</strong> pages will be published using the <strong>' +
      escapeHtml(templateLabel) + '</strong> template. ' +
      'AI will generate unique content for each page\'s content slots.'
    );
  }

  $('#page-template-select').on('change', renderPreview);

  // ── Step 3: Publish ──────────────────────────────────────────

  $('#btn-publish-immediate').on('click', function () {
    processPublish('immediate');
  });

  $('#btn-publish-queued').on('click', function () {
    processPublish('queued');
  });

  function processPublish(mode) {
    const template = $('#page-template-select').val();

    $('#btn-publish-immediate, #btn-publish-queued').prop('disabled', true);
    $('#publish-progress').show();
    updateProgress(0, 'Starting...');

    if (mode === 'immediate') {
      updateProgress(10, 'Generating AI content and publishing pages...');
    } else {
      updateProgress(10, 'Queuing pages for background processing...');
    }

    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'bulk_publish_process',
        nonce: nonce,
        mode: mode,
        page_template: template,
        column_mapping: JSON.stringify({}), // Auto-detect
      },
      timeout: 300000, // 5 minute timeout for immediate mode.
      success: function (response) {
        if (response.success) {
          updateProgress(100, 'Complete!');

          if (response.data.results) {
            renderResults(response.data);
          } else {
            // Queued mode.
            $('#step-results').slideDown();
            $('#results-summary')
              .css('background', '#d1e7dd')
              .html('<strong>' + response.data.message + '</strong>');
          }
        } else {
          updateProgress(0, 'Error: ' + response.data.message);
        }

        $('#btn-publish-immediate, #btn-publish-queued').prop('disabled', false);
      },
      error: function (xhr) {
        let msg = 'Request failed.';
        if (xhr.status === 0) msg = 'Request timed out. Try using "Queue" mode for large batches.';
        updateProgress(0, msg);
        $('#btn-publish-immediate, #btn-publish-queued').prop('disabled', false);
      },
    });
  }

  function renderResults(data) {
    $('#step-results').slideDown();

    const bgColor = data.failed > 0 ? '#fff3cd' : '#d1e7dd';
    $('#results-summary')
      .css('background', bgColor)
      .html(
        '<strong>' + data.succeeded + '</strong> pages published successfully.' +
        (data.failed > 0 ? ' <strong>' + data.failed + '</strong> failed.' : '')
      );

    const $tbody = $('#results-tbody').empty();
    if (!data.results) return;

    data.results.forEach(function (r) {
      const statusClass = r.success ? 'color: #0a7c3e' : 'color: #d63638';
      const statusIcon = r.success ? '&#10003;' : '&#10007;';

      $tbody.append(
        '<tr>' +
        '<td>' + escapeHtml(r.keyword) + '</td>' +
        '<td><code>/' + escapeHtml(r.slug) + '</code></td>' +
        '<td style="' + statusClass + '">' + statusIcon + '</td>' +
        '<td>' + escapeHtml(r.message) + '</td>' +
        '</tr>'
      );
    });
  }

  // ── Helpers ──────────────────────────────────────────────────

  function updateProgress(percent, text) {
    $('#progress-bar').css('width', percent + '%');
    $('#progress-text').text(text);
  }

  function showStatus(selector, message, type) {
    const colors = {
      success: '#d1e7dd',
      error: '#f8d7da',
      info: '#cfe2ff',
    };

    $(selector)
      .show()
      .html('<p style="margin: 0; padding: 8px 12px; background: ' +
        (colors[type] || colors.info) +
        '; border-radius: 4px;">' + escapeHtml(message) + '</p>');
  }

  function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
  }

})(jQuery);
