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
 * Tile/list controls and the appearance drawer.
 * @module format_picturecards/view
 * @copyright 2026 GENESTIAL
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ModalForm from 'core_form/modalform';
import Notification from 'core/notification';
import {getString} from 'core/str';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';

let initialised = false;
let layout = 'tiles';

/** Apply controls to current content, including replaced AJAX fragments. */
const refresh = () => {
    document.querySelectorAll('.format-picturecards .course-content').forEach(content =>
        content.classList.toggle('format-picturecards-list', layout === 'list'));
    document.querySelectorAll('.format-picturecards-course-body').forEach(body =>
        body.classList.toggle('format-picturecards-list', layout === 'list'));
    document.querySelectorAll('.format-picturecards-has-topiccards').forEach(body => {
        const cardids = new Set(Array.from(body.querySelectorAll('[data-picturecards-section-id]'),
            card => card.dataset.picturecardsSectionId));
        body.querySelectorAll('ul[data-for="course_sectionlist"] > .course-section').forEach(section =>
            section.classList.toggle('format-picturecards-carded-section', cardids.has(section.dataset.id)));
        const list = body.querySelector('ul[data-for="course_sectionlist"]');
        const firsttopic = list?.querySelector(':scope > .format-picturecards-carded-section');
        const toolbar = body.querySelector('.format-picturecards-course-toolbar');
        if (list && firsttopic && toolbar) {
            let holder = list.querySelector(':scope > .format-picturecards-course-toolbar-holder');
            if (!holder) {
                holder = document.createElement('li');
                holder.className = 'format-picturecards-course-toolbar-holder';
                holder.append(toolbar);
            }
            if (holder.nextElementSibling !== firsttopic) {
                list.insertBefore(holder, firsttopic);
            }
        }
    });
    document.querySelectorAll('.format-picturecards-course-toolbar [data-picturecards-layout]').forEach(button =>
        button.setAttribute('aria-pressed', String(button.dataset.picturecardsLayout === layout)));
};

/**
 * Open the core form in a styled side drawer, using its focus and validation support.
 * @param {HTMLElement} trigger appearance link
 */
const editAppearance = async(trigger) => {
    const form = new ModalForm({
        formClass: 'format_picturecards\\form\\appearance',
        args: {cmid: Number(trigger.dataset.picturecardsAppearance)},
        modalConfig: {title: await getString('tileappearance', 'format_picturecards'), large: false},
        returnFocus: trigger,
    });
    const item = trigger.closest('[data-for="cmitem"]');
    item?.classList.add('format-picturecards-selected');
    form.addEventListener(form.events.LOADED, () => {
        form.modal.getRoot().addClass('format-picturecards-drawer');
        form.modal.getRoot().on('hidden.bs.modal', () => item?.classList.remove('format-picturecards-selected'));
    });
    form.addEventListener(form.events.FORM_SUBMITTED, event => {
        item?.classList.remove('format-picturecards-selected');
        getCurrentCourseEditor().dispatch('cmState', [event.detail.cmid]).catch(Notification.exception);
    });
    form.addEventListener(form.events.FORM_CANCELLED, () => item?.classList.remove('format-picturecards-selected'));
    await form.show();
};

/**
 * Edit the image belonging to a topic rather than one of its activities.
 * @param {HTMLElement} trigger topic appearance button
 */
const editTopic = async(trigger) => {
    const form = new ModalForm({
        formClass: 'format_picturecards\\form\\topic_appearance',
        args: {sectionid: Number(trigger.dataset.picturecardsTopicAppearance)},
        modalConfig: {title: await getString('topicappearance', 'format_picturecards'), large: false},
        returnFocus: trigger,
    });
    form.addEventListener(form.events.LOADED, () => form.modal.getRoot().addClass('format-picturecards-drawer'));
    form.addEventListener(form.events.FORM_SUBMITTED, () => {
        window.location.reload();
    });
    await form.show();
};

/** Initialise once; delegated listeners survive Moodle's reactive updates. */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;
    refresh();
    document.addEventListener('click', event => {
        const topic = event.target.closest('[data-picturecards-topic-appearance]');
        if (topic) {
            event.preventDefault();
            editTopic(topic).catch(Notification.exception);
            return;
        }
        const appearance = event.target.closest('[data-picturecards-appearance]');
        if (appearance) {
            event.preventDefault();
            editAppearance(appearance).catch(Notification.exception);
            return;
        }
        const button = event.target.closest('[data-picturecards-layout]');
        if (!button) {
            return;
        }
        layout = button.dataset.picturecardsLayout;
        refresh();
    });
    const content = document.querySelector('.course-content');
    if (content) {
        new MutationObserver(refresh).observe(content, {childList: true, subtree: true});
    }
};

