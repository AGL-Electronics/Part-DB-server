import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
    connect() {
        this.handleChange = this._check.bind(this);
        this.element.addEventListener('change', this.handleChange);
        this.request = 0;
    }

    disconnect() {
        this.element.removeEventListener('change', this.handleChange);
    }

    async _check(event) {
        const select = event.target.closest('select[name="add_part"]');
        if (!select) {
            return;
        }
        const request = ++this.request;
        const partId = String(select.value || '').trim();
        if (!/^\d+$/.test(partId)) {
            this._render([]);

            return;
        }

        const url = this.element.dataset.openLinesUrl.replace('/part/0/', '/part/' + partId + '/');
        let lines = [];
        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
            if (response.ok) {
                lines = (await response.json()).lines ?? [];
            }
        } catch {
            lines = [];
        }
        if (request === this.request) {
            this._render(lines);
        }
    }

    _render(lines) {
        const warning = this.element.querySelector('[data-duplicate-warning]');
        const list = this.element.querySelector('[data-duplicate-warning-list]');
        if (!warning || !list) {
            return;
        }
        list.replaceChildren(...lines.map((line) => {
            const item = document.createElement('li');
            const link = document.createElement('a');
            link.href = line.url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = warning.dataset.itemTemplate
                .replace('__ORDER__', line.order)
                .replace('__QUANTITY__', String(line.outstanding));
            item.append(link);

            return item;
        }));
        warning.classList.toggle('d-none', lines.length === 0);
    }
}
