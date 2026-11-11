import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
    connect() {
        this.handleInput = this._handleInput.bind(this);
        this.handleSubmit = this._handleSubmit.bind(this);
        this.handleClick = this._handleClick.bind(this);
        this.element.addEventListener('input', this.handleInput);
        this.element.addEventListener('submit', this.handleSubmit);
        this.element.addEventListener('click', this.handleClick);

        this.element.querySelector('[data-order-highlight]')?.scrollIntoView({block: 'center'});
    }

    disconnect() {
        this.element.removeEventListener('input', this.handleInput);
        this.element.removeEventListener('submit', this.handleSubmit);
        this.element.removeEventListener('click', this.handleClick);
    }

    _handleInput(event) {
        const target = event.target.closest('[data-order-target]');
        if (target) {
            const qty = target.closest('tr').querySelector('[data-order-qty]');
            if (qty && qty.dataset.touched !== '1') {
                const missing = Number(target.value) - Number(target.dataset.stock);
                qty.value = String(Math.max(0, Math.ceil(missing)));
            }

            return;
        }

        if (event.target.closest('[data-order-qty]')) {
            event.target.dataset.touched = '1';

            return;
        }

        const search = event.target.closest('[data-order-search]');
        if (search && search.value.trim() === '') {
            this._search('');
        }
    }

    _handleSubmit(event) {
        const form = event.target.closest('[data-order-search-form]');
        if (!form) {
            return;
        }
        event.preventDefault();
        this._search(form.querySelector('[data-order-search]').value);
    }

    _handleClick(event) {
        if (!event.target.closest('[data-order-search-clear]')) {
            return;
        }
        const input = this.element.querySelector('[data-order-search]');
        input.value = '';
        input.focus();
        this._search('');
    }

    _search(value) {
        const terms = value.toLowerCase().split(/\s+/).filter((term) => term !== '');
        const rows = Array.from(this.element.querySelectorAll('[data-order-line]'));
        let first = null;
        let visible = 0;
        rows.forEach((row) => {
            const position = row.querySelector('[data-order-position]')?.textContent.trim() ?? '';
            const text = (row.dataset.searchText ?? '') + ' ' + position;
            const match = terms.every((term) => text.includes(term));
            const highlighted = match && terms.length > 0;
            row.classList.toggle('d-none', !match);
            row.classList.toggle('table-warning', highlighted);
            if (match) {
                visible++;
            }
            if (highlighted && first === null) {
                first = row;
            }
        });

        const count = this.element.querySelector('[data-order-search-count]');
        if (count) {
            count.textContent = count.dataset.template.replace('__COUNT__', String(visible));
            count.classList.toggle('d-none', terms.length === 0);
            count.classList.toggle('text-danger', terms.length > 0 && visible === 0);
        }
        first?.scrollIntoView({block: 'center', behavior: 'smooth'});
    }
}
