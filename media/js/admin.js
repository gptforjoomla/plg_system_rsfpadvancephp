/**
 * Administrator behaviour for Advance PHP for RSForm!Pro.
 *
 * This script uses plain JavaScript and Bootstrap 5-compatible markup. It only
 * toggles visibility of PHP editor blocks; saving is handled by RSForm!Pro's
 * existing form save workflow.
 */
(() => {
  'use strict';

  const updateCodeBlock = (input) => {
    const targetId = input.dataset.codeTarget;
    const target = targetId ? document.getElementById(targetId) : null;

    if (!target) {
      return;
    }

    target.classList.toggle('d-none', input.value !== '1' || !input.checked);
  };

  document.addEventListener('change', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || !input.classList.contains('js-rsfpadvancephp-toggle')) {
      return;
    }

    document.querySelectorAll(`input[name="${CSS.escape(input.name)}"]`).forEach(updateCodeBlock);
  });

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-rsfpadvancephp-toggle').forEach(updateCodeBlock);
  });
})();
