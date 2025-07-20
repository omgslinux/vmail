// assets/controllers/form-collection_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["collectionContainer"];
    static values = {
        index: { type: Number, default: 0 },
        prototype: String
    };

    connect() {
        // Establece el índice basado en los elementos existentes
        this.indexValue = this.collectionContainerTarget.children.length;
    }

    addCollectionElement(event) {
        event.preventDefault();

        const newItem = document.createElement('div');
        newItem.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.collectionContainerTarget.appendChild(newItem);

        // Si usas campos _delete para elementos persistidos
        this.setupDeleteHandlers(newItem);
        this.indexValue++;
    }

    removeItem(event) {
        event.preventDefault();
        const item = event.target.closest('[data-collection-item]');

        if (!item) return;

        // Para elementos persistidos (que tienen ID)
        const deleteInput = item.querySelector('input[name*="[_delete]"]');

        if (deleteInput) {
            // Marcamos para borrado en el servidor
            deleteInput.value = '1';
            item.style.display = 'none'; // Ocultamos en lugar de eliminar
        } else {
            // Para nuevos elementos no guardados aún
            item.remove();
        }
    }

    // Opcional: para inicializar handlers en elementos existentes
    setupDeleteHandlers(container) {
        container.querySelectorAll('[data-action="form-collection#removeItem"]').forEach(btn => {
            btn.addEventListener('click', this.removeItem.bind(this));
        });
    }
}
