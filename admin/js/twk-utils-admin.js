jQuery(function($) {
	'use strict';

	// Wait for DOM to be ready
	$(document).ready(function() {
		// Initialize clipboard.js
		new ClipboardJS('.copy-log');

		// Handle copy button feedback
		$('.copy-log').on('click', function() {
			var $button = $(this);
			$button.addClass('copied');
			setTimeout(function() {
				$button.removeClass('copied');
			}, 1500);
		});

		// Search functionality
		var searchTimeout;
		$('#log-search').on('input', function() {
			clearTimeout(searchTimeout);
			var searchTerm = $(this).val().toLowerCase();

			searchTimeout = setTimeout(function() {
				$('.log-entry').each(function() {
					var $entry = $(this);
					var entryText = $entry.text().toLowerCase();
					var matchesSearch = searchTerm === '' || entryText.includes(searchTerm);
					var isVisible = !$entry.hasClass('hidden');

					// Only show entries that match both search and filter
					$entry.toggleClass('search-hidden', !matchesSearch);
					$entry.toggle(matchesSearch && isVisible);
				});
			}, 300);
		});

		// Filter functionality
		$('.debug-log-filters .log-filter').on('click', function() {
			var type = $(this).data('type');
			var checked = $(this).prop('checked');
			
			$('.log-entry.' + type).each(function() {
				var $entry = $(this);
				var matchesSearch = !$entry.hasClass('search-hidden');
				$entry.toggleClass('hidden', !checked);
				$entry.toggle(checked && matchesSearch);
			});
		});

		// Toggle All functionality
		var $toggleAll = $('<label class="toggle-all-label"><input type="checkbox" class="log-filter-all" checked> Toggle All</label>');
		$('.debug-log-filters').prepend($toggleAll);

		$('.log-filter-all').on('click', function() {
			var checked = $(this).prop('checked');
			$('.log-filter').prop('checked', checked);
			$('.log-entry').each(function() {
				var $entry = $(this);
				var matchesSearch = !$entry.hasClass('search-hidden');
				$entry.toggleClass('hidden', !checked);
				$entry.toggle(checked && matchesSearch);
			});
		});
	});
}); 