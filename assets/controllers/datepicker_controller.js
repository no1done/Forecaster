import { Controller } from '@hotwired/stimulus';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/*
 * Shows a date input as DD/MM/YYYY, whatever the browser's locale. The
 * original input stays underneath (hidden) and still submits YYYY-MM-DD.
 */
export default class extends Controller {
    connect() {
        this.picker = flatpickr(this.element, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            minDate: this.element.min || null,
            maxDate: this.element.max || null,
            locale: { firstDayOfWeek: 1 },
            // Typed dates are day first too.
            parseDate: (text, format) => {
                const dayFirst = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
                if (dayFirst) {
                    return new Date(Number(dayFirst[3]), Number(dayFirst[2]) - 1, Number(dayFirst[1]));
                }

                return flatpickr.parseDate(text, format);
            },
        });
        this.picker.altInput.placeholder = 'dd/mm/yyyy';
    }

    disconnect() {
        this.picker.destroy();
    }
}
