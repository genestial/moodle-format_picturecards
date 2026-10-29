// This file is part of Moodle - http://moodle.org/
// Licensed under the GNU GPL v3 or later: http://www.gnu.org/copyleft/gpl.html

/** Live preview for the course colour palette.
 * @module format_picturecards/palette
 */
export const init = () => {
    const preview = document.querySelector('[data-picturecards-palette-preview]');
    if (!preview || preview.dataset.picturecardsInitialised) {
        return;
    }
    preview.dataset.picturecardsInitialised = '1';
    const presets = JSON.parse(preview.dataset.picturecardsPalettePreview);
    const form = preview.closest('form');
    if (!form) {
        return;
    }
    const select = form.elements.namedItem(preview.dataset.picturecardsPaletteSelect);
    const names = preview.dataset.picturecardsPaletteInputs ? JSON.parse(preview.dataset.picturecardsPaletteInputs) :
        Array.from({length: 5}, (_, index) => `${preview.dataset.picturecardsPalettePrefix}${index + 1}`);
    const inputs = names.map(name => form.elements.namedItem(name));
    if (!select || inputs.some(input => !input)) {
        return;
    }
    let origin = select.value !== 'custom' ? select.value : null;
    const values = () => inputs.map(input => input.value.toLowerCase());
    const matches = (key) => presets[key] && presets[key].every((colour, index) =>
        colour.toLowerCase() === values()[index]);
    const fill = (key) => {
        if (presets[key]) {
            inputs.forEach((input, index) => {
                input.value = presets[key][index];
                const current = input.closest('.form-colourpicker')?.querySelector('.currentcolour');
                if (current) {
                    current.style.backgroundColor = input.value;
                }
            });
        }
    };
    const update = () => {
        const colours = select.value === 'custom' ? values() : (presets[select.value] || presets.ocean);
        preview.replaceChildren(...colours.map(colour => {
            const swatch = document.createElement('span');
            swatch.style.backgroundColor = colour;
            swatch.setAttribute('role', 'img');
            swatch.setAttribute('aria-label', colour);
            swatch.title = colour;
            return swatch;
        }));
    };
    select.addEventListener('change', () => {
        if (select.value !== 'custom') {
            origin = select.value;
            fill(origin);
        }
        update();
    });
    const colourChanged = () => {
        select.value = origin && matches(origin) ? origin : 'custom';
        update();
    };
    inputs.forEach(input => input.addEventListener('input', colourChanged));
    inputs.forEach(input => input.addEventListener('change', colourChanged));
    // Moodle's site colour picker sets the input value without emitting input or change.
    form.addEventListener('click', event => {
        const picker = event.target.closest('.form-colourpicker');
        if (picker && event.target.closest('.admin_colourpicker .colourdialogue') &&
                inputs.some(input => input.closest('.form-colourpicker') === picker)) {
            queueMicrotask(colourChanged);
        }
    });
    form.addEventListener('submit', () => {
        if (origin) {
            select.value = matches(origin) ? origin : 'custom';
        }
    });
    if (origin) {
        fill(origin);
    }
    update();
};
