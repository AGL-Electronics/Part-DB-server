import {Controller} from "@hotwired/stimulus";

export default class extends Controller
{
    connect() {
        this.handleChange = this._update.bind(this);
        this.element.addEventListener('change', this.handleChange);
        this._update();
    }

    disconnect() {
        this.element.removeEventListener('change', this.handleChange);
    }

    _update() {
        const target = this.element.querySelector('[data-add-to-order-target]');
        if (!target) {
            return;
        }
        const creating = target.value === 'new';
        this.element.querySelectorAll('[data-add-to-order-new]').forEach((element) => {
            element.classList.toggle('d-none', !creating);
            element.querySelectorAll('input').forEach((input) => { input.required = creating; });
        });
        this.element.querySelectorAll('[data-add-to-order-details]').forEach((element) => {
            element.classList.toggle('d-none', target.value === '');
        });
    }
}
