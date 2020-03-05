"use strict"

document.addEventListener('DOMContentLoaded', function () {
	// IE11 Support: https://developer.mozilla.org/en-US/docs/Web/API/NodeList/forEach#Polyfill
	if ( window.NodeList && ! NodeList.prototype.forEach ) {
		NodeList.prototype.forEach = Array.prototype.forEach;
	}

	document.querySelectorAll('[data-gtm-on]').forEach(function (element) {
		var data = element.dataset,
			trigger = data.gtmOn;

		// Set the event listener.
		element.addEventListener(trigger, function () {
			// Check for custom variable.
			var variable = data.gtmVar || 'dataLayer',
			    entry = {};

			// Instantiate the dataLayer variable if it doesn't exist.
			window[variable] = window[variable] || [];

			data.gtmEvent && (entry.event = String(data.gtmEvent));
			data.gtmCategory && (entry.category = String(data.gtmCategory));
			data.gtmLabel && (entry.label = String(data.gtmLabel));
			data.gtmValue && (entry.value = Number(data.gtmValue));
			data.gtmFields && (entry.fields = JSON.parse(data.gtmFields));

			// Push the entry onto the dataLayer.
			window[variable].push(entry);
		});
	});
});
