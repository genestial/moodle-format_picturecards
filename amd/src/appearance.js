// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * Progressive enhancement for the tile appearance form.
 * @module format_picturecards/appearance
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Connect the visual icon, colour and focus controls to Moodle's native form fields.
 * @param {number} cmid activity identifier
 */
export const init = cmid => {
    const controls = document.querySelector('[data-picturecards-form="' + cmid + '"]');
    if (!controls || controls.dataset.initialised) {
        return;
    }
    controls.dataset.initialised = 'true';
    const form = controls.closest('form');
    const field = name => form.elements.namedItem(name);
    const preview = controls.querySelector('.format-picturecards-preview');
    const image = controls.querySelector('.format-picturecards-preview-image');
    const marker = controls.querySelector('.format-picturecards-focuspoint');
    const icon = controls.querySelector('.format-picturecards-preview-icon');
    const sync = () => {
        const mode = field('mediatype').value;
        const colour = field('colour').value;
        preview.className = 'format-picturecards-preview' + (mode === 'icon' ? ' format-picturecards-colour-' + colour : '');
        image.hidden = mode !== 'image' || !image.getAttribute('src');
        icon.hidden = mode !== 'icon';
        const selected = controls.querySelector('[data-picturecards-icon="' + field('iconkey').value + '"]');
        icon.className = 'format-picturecards-preview-icon ' + (selected?.dataset.iconClass || 'fa-solid fa-shapes');
        controls.querySelector('.format-picturecards-preview-default').hidden = mode !== 'default';
        controls.querySelector('.format-picturecards-icon-picker').hidden = mode !== 'icon';
        controls.querySelector('.format-picturecards-defaulthelp').hidden = mode !== 'default';
        controls.querySelector('.format-picturecards-focushelp').hidden = mode !== 'image';
        marker.hidden = image.hidden;
        const x = Math.max(0, Math.min(100, Number(field('focusx').value)));
        const y = Math.max(0, Math.min(100, Number(field('focusy').value)));
        image.style.objectPosition = x + '% ' + y + '%';
        marker.style.left = x + '%';
        marker.style.top = y + '%';
        controls.querySelectorAll('[data-picturecards-icon]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.picturecardsIcon === field('iconkey').value));
            button.className = 'format-picturecards-colour-' + colour;
        });
        controls.querySelectorAll('[data-picturecards-colour]').forEach(button =>
            button.setAttribute('aria-pressed', String(button.dataset.picturecardsColour === colour)));
    };
    controls.addEventListener('click', event => {
        const choice = event.target.closest('[data-picturecards-icon], [data-picturecards-colour]');
        if (choice) {
            const input = field(choice.dataset.picturecardsIcon ? 'iconkey' : 'colour');
            input.value = choice.dataset.picturecardsIcon || choice.dataset.picturecardsColour;
            input.dispatchEvent(new Event('change', {bubbles: true}));
        }
    });
    preview.addEventListener('click', event => {
        if (image.hidden) {
            return;
        }
        const bounds = preview.getBoundingClientRect();
        field('focusx').value = Math.round(100 * (event.clientX - bounds.left) / bounds.width);
        field('focusy').value = Math.round(100 * (event.clientY - bounds.top) / bounds.height);
        field('focusx').dispatchEvent(new Event('change', {bubbles: true}));
    });
    controls.querySelector('[data-picturecards-icon-search]').addEventListener('input', event => {
        const query = event.target.value.toLocaleLowerCase();
        controls.querySelectorAll('[data-picturecards-icon]').forEach(button => {
            button.hidden = !button.getAttribute('aria-label').toLocaleLowerCase().includes(query);
        });
    });
    form.addEventListener('change', sync);
    form.addEventListener('input', sync);
    sync();
};

