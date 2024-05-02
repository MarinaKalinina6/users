import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["checkbox", "toggle"];

    async block() {
        await this.executeAction('block');
    }

    async unblock() {
        await this.executeAction('unblock');
    }

    async delete() {
        await this.executeAction('delete');
    }

    async executeAction(name) {
        await fetch(`/${name}`, {
            method: "POST",
            body: JSON.stringify(this.selectedIds)
        });

        window.location.reload();
    }

    get selectedIds() {
        return this.checkboxTargets.reduce((ids, element) => {
            if (element.checked === true) {
                ids.push(element.dataset.id);
            }

            return ids;
        }, []);
    }

    toggleAll() {
        const isChecked = this.toggleTarget.checked;

        this.checkboxTargets.forEach((element) => {
            element.checked = isChecked;
        });
    }
}
