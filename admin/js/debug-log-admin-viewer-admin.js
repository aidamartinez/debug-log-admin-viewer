jQuery(function($) {
	'use strict';

	// Wait for DOM to be ready
	$(document).ready(function() {
		// Initialize clipboard.js
		new ClipboardJS('.copy-log');
		new ClipboardJS('.backup-location');

		// Handle copy button feedback
		$('.copy-log').on('click', function() {
			var $button = $(this);
			$button.addClass('copied');
			setTimeout(function() {
				$button.removeClass('copied');
			}, 1500);
		});

		// Handle backup location copy feedback
		$('.backup-location').on('click', function() {
			var $location = $(this);
			var $feedback = $location.siblings('.copied-feedback');
			
			$feedback.fadeIn(200);
			setTimeout(function() {
				$feedback.fadeOut(200);
			}, 1500);
		});

		// Handle filter changes
		$('.log-filter').on('change', function() {
			var activeFilters = [];
			$('.log-filter:checked').each(function() {
				activeFilters.push($(this).data('type'));
			});
			
			// Update URL with new filters
			var newUrl = new URL(window.location.href);
			newUrl.searchParams.set('filters', activeFilters.join(','));
			newUrl.searchParams.set('log_page', '1'); // Reset to first page
			window.location.href = newUrl.toString();
		});

		// Handle log search
		$('#log-search').on('input', function() {
			var searchTerm = $(this).val().toLowerCase();
			$('.log-entry').each(function() {
				var entryText = $(this).text().toLowerCase();
				$(this).toggle(entryText.includes(searchTerm));
			});
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
