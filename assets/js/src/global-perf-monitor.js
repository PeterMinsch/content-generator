/**
 * Global Performance Monitor - Works on ANY WordPress admin page
 *
 * Just press Ctrl+Shift+P to toggle the monitor
 */

(function () {
	'use strict';

	let monitor = null;

	class GlobalPerformanceMonitor {
		constructor() {
			this.startTime = performance.now();
			this.lastFrameTime = performance.now();
			this.fps = 0;
			this.createDashboard();
			this.startMonitoring();
		}

		createDashboard() {
			const dashboard = document.createElement('div');
			dashboard.id = 'global-perf-dashboard';
			dashboard.style.cssText = `
				position: fixed;
				top: 50px;
				right: 20px;
				background: rgba(0,0,0,0.95);
				color: #0f0;
				padding: 15px;
				border-radius: 8px;
				font-family: 'Courier New', monospace;
				font-size: 13px;
				z-index: 999999;
				min-width: 320px;
				box-shadow: 0 4px 12px rgba(0,0,0,0.8);
				border: 2px solid #0f0;
			`;

			dashboard.innerHTML = `
				<div style="margin-bottom: 10px; color: #fff; font-weight: bold; border-bottom: 2px solid #0f0; padding-bottom: 8px; font-size: 14px;">
					⚡ GLOBAL PERFORMANCE MONITOR
				</div>
				<div style="margin: 8px 0; padding: 5px; background: rgba(0,255,0,0.1); border-radius: 3px;">
					<strong>FPS:</strong> <span id="global-perf-fps" style="color: #0f0; font-size: 16px; font-weight: bold;">--</span>
				</div>
				<div style="margin: 8px 0;">
					<strong>Memory:</strong> <span id="global-perf-memory" style="color: #0f0;">--</span>
				</div>
				<div style="margin: 8px 0;">
					<strong>DOM Nodes:</strong> <span id="global-perf-dom" style="color: #0f0;">--</span>
				</div>
				<div style="margin: 8px 0;">
					<strong>Page:</strong> <span id="global-perf-page" style="color: #0f0;">--</span>
				</div>
				<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #333; text-align: center;">
					<button id="close-global-perf" style="background: #f00; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 4px; font-weight: bold;">
						CLOSE (or press Ctrl+Shift+P)
					</button>
				</div>
			`;

			document.body.appendChild(dashboard);

			document.getElementById('close-global-perf').addEventListener('click', () => {
				this.destroy();
			});

			// Show current page
			const pageTitle = document.title || 'Unknown Page';
			document.getElementById('global-perf-page').textContent = pageTitle.substring(0, 40);
		}

		startMonitoring() {
			// Monitor FPS
			this.fpsInterval = setInterval(() => {
				this.measureFPS();
			}, 100); // Update 10 times per second for accuracy

			// Monitor memory
			this.memoryInterval = setInterval(() => {
				this.measureMemory();
			}, 1000);

			// Monitor DOM
			this.domInterval = setInterval(() => {
				this.measureDOM();
			}, 2000);
		}

		measureFPS() {
			const now = performance.now();
			const delta = now - this.lastFrameTime;
			this.fps = Math.round(1000 / delta);
			this.lastFrameTime = now;

			const color = this.fps >= 50 ? '#0f0' : this.fps >= 30 ? '#ff0' : '#f00';
			const fpsEl = document.getElementById('global-perf-fps');
			if (fpsEl) {
				fpsEl.style.color = color;
				fpsEl.textContent = this.fps + ' fps';
			}
		}

		measureMemory() {
			if (performance.memory) {
				const used = (performance.memory.usedJSHeapSize / 1048576).toFixed(2);
				const total = (performance.memory.totalJSHeapSize / 1048576).toFixed(2);

				const percentage = ((used / total) * 100).toFixed(0);
				const color = percentage < 70 ? '#0f0' : percentage < 85 ? '#ff0' : '#f00';

				const memEl = document.getElementById('global-perf-memory');
				if (memEl) {
					memEl.style.color = color;
					memEl.textContent = `${used} / ${total} MB (${percentage}%)`;
				}
			} else {
				const memEl = document.getElementById('global-perf-memory');
				if (memEl) {
					memEl.textContent = 'Not available';
				}
			}
		}

		measureDOM() {
			const totalNodes = document.getElementsByTagName('*').length;
			const domEl = document.getElementById('global-perf-dom');
			if (domEl) {
				domEl.textContent = totalNodes.toLocaleString();
			}
		}

		destroy() {
			clearInterval(this.fpsInterval);
			clearInterval(this.memoryInterval);
			clearInterval(this.domInterval);
			const dashboard = document.getElementById('global-perf-dashboard');
			if (dashboard) {
				dashboard.remove();
			}
			monitor = null;
		}
	}

	// Keyboard shortcut: Ctrl+Shift+P
	document.addEventListener('keydown', function (e) {
		if (e.ctrlKey && e.shiftKey && e.key === 'P') {
			e.preventDefault();
			if (monitor) {
				monitor.destroy();
			} else {
				monitor = new GlobalPerformanceMonitor();
			}
		}
	});

	// Also add a floating button
	window.addEventListener('load', function () {
		const button = document.createElement('button');
		button.id = 'global-perf-button';
		button.textContent = '📊';
		button.title = 'Toggle Performance Monitor (Ctrl+Shift+P)';
		button.style.cssText = `
			position: fixed;
			bottom: 20px;
			right: 20px;
			background: #0f0;
			color: #000;
			border: none;
			width: 50px;
			height: 50px;
			border-radius: 50%;
			font-size: 24px;
			cursor: pointer;
			z-index: 999998;
			box-shadow: 0 4px 8px rgba(0,0,0,0.3);
			transition: transform 0.2s;
		`;

		button.addEventListener('mouseenter', function () {
			this.style.transform = 'scale(1.1)';
		});

		button.addEventListener('mouseleave', function () {
			this.style.transform = 'scale(1)';
		});

		button.addEventListener('click', function () {
			if (monitor) {
				monitor.destroy();
			} else {
				monitor = new GlobalPerformanceMonitor();
			}
		});

		document.body.appendChild(button);
	});
})();
