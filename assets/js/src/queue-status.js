/**
 * Queue Status Page JavaScript
 *
 * Handles real-time queue status updates and user interactions.
 *
 * @package SEOGenerator
 */

( function() {
	'use strict';

	let updateInterval = null;

	/**
	 * Start queue monitoring with 30-second polling.
	 */
	function startQueueMonitoring() {
		// Initial update.
		updateQueueStatus();

		// Poll every 30 seconds.
		updateInterval = setInterval( updateQueueStatus, 30000 );
	}

	/**
	 * Stop queue monitoring.
	 */
	function stopQueueMonitoring() {
		if ( updateInterval ) {
			clearInterval( updateInterval );
			updateInterval = null;
		}
	}

	/**
	 * Fetch and update queue status from server.
	 */
	async function updateQueueStatus() {
		try {
			const formData = new FormData();
			formData.append( 'action', 'seo_queue_status' );
			formData.append( 'nonce', seoQueueData.nonce );

			const response = await fetch( seoQueueData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} );

			const result = await response.json();

			if ( result.success ) {
				updateStats( result.data.stats );
				updateEstimatedCompletion( result.data.estimated_completion );
				updatePauseButton( result.data.is_paused );

				// Update queue table if queue items are provided.
				if ( result.data.queue_items ) {
					updateQueueTable( result.data.queue_items );
				}

				// Show notification if queue completed.
				if ( result.data.stats.pending === 0 && result.data.stats.processing === 0 ) {
					showCompletionNotification( result.data.stats );
					stopQueueMonitoring();
				}
			}
		} catch ( error ) {
			console.error( 'Queue status update failed:', error );
		}
	}

	/**
	 * Update queue statistics in the UI.
	 *
	 * @param {Object} stats Queue statistics.
	 */
	function updateStats( stats ) {
		const pendingEl = document.getElementById( 'pending-count' );
		const processingEl = document.getElementById( 'processing-count' );
		const completedEl = document.getElementById( 'completed-count' );
		const failedEl = document.getElementById( 'failed-count' );

		if ( pendingEl ) {
			pendingEl.textContent = stats.pending;
		}
		if ( processingEl ) {
			processingEl.textContent = stats.processing;
		}
		if ( completedEl ) {
			completedEl.textContent = stats.completed;
		}
		if ( failedEl ) {
			failedEl.textContent = stats.failed;
		}
	}

	/**
	 * Update queue table with latest queue items.
	 *
	 * @param {Array} queueItems Array of queue item objects.
	 */
	function updateQueueTable( queueItems ) {
		const tbody = document.querySelector( '#queue-list tbody' );
		if ( ! tbody ) {
			return;
		}

		// Update each row based on post_id.
		queueItems.forEach( ( item ) => {
			const row = tbody.querySelector( `tr[data-post-id="${item.post_id}"]` );
			if ( ! row ) {
				return; // Row doesn't exist (might be new item, but we don't add new rows dynamically).
			}

			// Update status column.
			const statusCell = row.querySelector( '.column-status' );
			if ( statusCell ) {
				statusCell.innerHTML = getStatusHTML( item.status );
			}

			// Update scheduled time if changed.
			const scheduledCell = row.querySelector( '.column-scheduled' );
			if ( scheduledCell && item.scheduled_time ) {
				scheduledCell.textContent = item.scheduled_time;
			}

			// Update updated time if exists.
			const updatedCell = row.querySelector( '.column-updated' );
			if ( updatedCell && item.updated_at ) {
				updatedCell.textContent = item.updated_at;
			}

			// Hide cancel button if completed or failed.
			if ( item.status === 'completed' || item.status === 'failed' ) {
				const cancelBtn = row.querySelector( '.cancel-job' );
				if ( cancelBtn ) {
					cancelBtn.style.display = 'none';
				}
			}
		} );
	}

	/**
	 * Get HTML for status badge.
	 *
	 * @param {string} status Queue item status.
	 * @return {string} HTML for status badge.
	 */
	function getStatusHTML( status ) {
		const statusMap = {
			pending: '<span class="status-badge status-pending">Queued (ready)</span>',
			processing: '<span class="status-badge status-processing">Generating...</span>',
			completed: '<span class="status-badge status-completed">Completed</span>',
			failed: '<span class="status-badge status-failed">Failed</span>',
		};

		return statusMap[ status ] || '<span class="status-badge">' + status + '</span>';
	}

	/**
	 * Update estimated completion time.
	 *
	 * @param {string|null} estimatedTime Estimated completion datetime or null.
	 */
	function updateEstimatedCompletion( estimatedTime ) {
		const estimatedEl = document.getElementById( 'estimated-completion' );

		if ( estimatedEl ) {
			if ( estimatedTime ) {
				estimatedEl.textContent = 'Estimated completion: ' + estimatedTime;
			} else {
				estimatedEl.textContent = 'No pending jobs';
			}
		}
	}

	/**
	 * Update pause/resume button state.
	 *
	 * @param {boolean} isPaused Whether queue is paused.
	 */
	function updatePauseButton( isPaused ) {
		const pauseBtn = document.getElementById( 'pause-queue' );
		const resumeBtn = document.getElementById( 'resume-queue' );

		if ( isPaused ) {
			if ( pauseBtn ) {
				pauseBtn.style.display = 'none';
			}
			if ( resumeBtn ) {
				resumeBtn.style.display = 'inline-block';
			}
		} else {
			if ( pauseBtn ) {
				pauseBtn.style.display = 'inline-block';
			}
			if ( resumeBtn ) {
				resumeBtn.style.display = 'none';
			}
		}
	}

	/**
	 * Show completion notification.
	 *
	 * @param {Object} stats Final queue statistics.
	 */
	function showCompletionNotification( stats ) {
		const message = `Queue processing complete! ${stats.completed} jobs completed, ${stats.failed} failed.`;

		// Create WordPress-style notice.
		const notice = document.createElement( 'div' );
		notice.className = 'notice notice-success is-dismissible';
		notice.innerHTML = '<p>' + message + '</p>';

		const wrap = document.querySelector( '.seo-queue-wrap' );
		if ( wrap ) {
			wrap.insertBefore( notice, wrap.firstChild );
		}

		// Auto-dismiss after 10 seconds.
		setTimeout( () => {
			notice.remove();
		}, 10000 );
	}

	/**
	 * Handle pause queue button click.
	 */
	async function handlePauseQueue() {
		try {
			const formData = new FormData();
			formData.append( 'action', 'seo_queue_pause' );
			formData.append( 'nonce', seoQueueData.nonce );

			const response = await fetch( seoQueueData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} );

			const result = await response.json();

			if ( result.success ) {
				showNotice( result.data.message, 'success' );
				updateQueueStatus();
			} else {
				showNotice( result.data.message || 'Failed to pause queue', 'error' );
			}
		} catch ( error ) {
			console.error( 'Pause queue failed:', error );
			showNotice( 'Failed to pause queue', 'error' );
		}
	}

	/**
	 * Handle resume queue button click.
	 */
	async function handleResumeQueue() {
		try {
			const formData = new FormData();
			formData.append( 'action', 'seo_queue_resume' );
			formData.append( 'nonce', seoQueueData.nonce );

			const response = await fetch( seoQueueData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} );

			const result = await response.json();

			if ( result.success ) {
				showNotice( result.data.message, 'success' );
				updateQueueStatus();
				startQueueMonitoring(); // Resume monitoring.
			} else {
				showNotice( result.data.message || 'Failed to resume queue', 'error' );
			}
		} catch ( error ) {
			console.error( 'Resume queue failed:', error );
			showNotice( 'Failed to resume queue', 'error' );
		}
	}

	/**
	 * Handle clear queue button click.
	 */
	async function handleClearQueue() {
		if ( ! confirm( 'Are you sure you want to clear the entire queue? This cannot be undone.' ) ) {
			return;
		}

		try {
			const formData = new FormData();
			formData.append( 'action', 'seo_queue_clear' );
			formData.append( 'nonce', seoQueueData.nonce );

			const response = await fetch( seoQueueData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} );

			const result = await response.json();

			if ( result.success ) {
				showNotice( result.data.message, 'success' );
				location.reload(); // Reload page to update table.
			} else {
				showNotice( result.data.message || 'Failed to clear queue', 'error' );
			}
		} catch ( error ) {
			console.error( 'Clear queue failed:', error );
			showNotice( 'Failed to clear queue', 'error' );
		}
	}

	/**
	 * Handle cancel individual job button click.
	 *
	 * @param {number} postId Post ID to cancel.
	 */
	async function handleCancelJob( postId ) {
		if ( ! confirm( 'Are you sure you want to cancel this job?' ) ) {
			return;
		}

		try {
			const formData = new FormData();
			formData.append( 'action', 'seo_queue_cancel_job' );
			formData.append( 'nonce', seoQueueData.nonce );
			formData.append( 'post_id', postId );

			const response = await fetch( seoQueueData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} );

			const result = await response.json();

			if ( result.success ) {
				showNotice( result.data.message, 'success' );
				// Remove row from table.
				const row = document.querySelector( `tr[data-post-id="${postId}"]` );
				if ( row ) {
					row.remove();
				}
				updateQueueStatus();
			} else {
				showNotice( result.data.message || 'Failed to cancel job', 'error' );
			}
		} catch ( error ) {
			console.error( 'Cancel job failed:', error );
			showNotice( 'Failed to cancel job', 'error' );
		}
	}

	/**
	 * Show admin notice.
	 *
	 * @param {string} message Notice message.
	 * @param {string} type    Notice type (success, error, warning, info).
	 */
	function showNotice( message, type = 'info' ) {
		const notice = document.createElement( 'div' );
		notice.className = `notice notice-${type} is-dismissible`;
		notice.innerHTML = '<p>' + message + '</p>';

		const wrap = document.querySelector( '.seo-queue-wrap' );
		if ( wrap ) {
			wrap.insertBefore( notice, wrap.firstChild );
		}

		// Auto-dismiss after 5 seconds.
		setTimeout( () => {
			notice.remove();
		}, 5000 );
	}

	/**
	 * Initialize event listeners.
	 */
	function initializeEventListeners() {
		// Pause queue button.
		const pauseBtn = document.getElementById( 'pause-queue' );
		if ( pauseBtn ) {
			pauseBtn.addEventListener( 'click', handlePauseQueue );
		}

		// Resume queue button.
		const resumeBtn = document.getElementById( 'resume-queue' );
		if ( resumeBtn ) {
			resumeBtn.addEventListener( 'click', handleResumeQueue );
		}

		// Clear queue button.
		const clearBtn = document.getElementById( 'clear-queue' );
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', handleClearQueue );
		}

		// Cancel job buttons (event delegation).
		const queueList = document.getElementById( 'queue-list' );
		if ( queueList ) {
			queueList.addEventListener( 'click', ( event ) => {
				if ( event.target.classList.contains( 'cancel-job' ) ) {
					const postId = event.target.getAttribute( 'data-post-id' );
					if ( postId ) {
						handleCancelJob( parseInt( postId, 10 ) );
					}
				}
			} );
		}
	}

	/**
	 * Initialize queue status page.
	 */
	function init() {
		if ( typeof seoQueueData === 'undefined' ) {
			console.error( 'seoQueueData not found' );
			return;
		}

		initializeEventListeners();
		startQueueMonitoring();
	}

	// Initialize on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
