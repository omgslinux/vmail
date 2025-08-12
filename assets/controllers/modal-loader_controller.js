// controllers/modal_loader_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  static targets = ['dialog']
  static values = {
    defaultSize: { type: String, default: 'modal-xl' }
  }

  connect() {
    if (!this.hasDialogTarget) {
      console.error('Missing <dialog> target inside modal-loader controller')
      return
    }

    this.dialogTarget.addEventListener('cancel', () => this.close())
    this.dialogTarget.addEventListener('click', (event) => {
      if (event.target === this.dialogTarget) {
        this.close()
      }
    })
  }

  open(event) {
      console.log("URL actual:", window.location.href);
    if (event?.preventDefault) {
      event.preventDefault()
    }

    this.dialogTarget.showModal();
    document.body.classList.add('overflow-hidden');

    const url = event.currentTarget.dataset.url
    if (!url) {
      console.error('Missing data-url')
      return
    }

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`)
        return response.text()
      })
      .then(html => {
        this.dialogTarget.innerHTML = html
        this.dialogTarget.classList.add(this.defaultSizeValue)
        this.dialogTarget.showModal();
        document.body.classList.add('overflow-hidden');

        const form = this.dialogTarget.querySelector('form')
        if (form) {
          form.addEventListener('submit', this.handleFormSubmit.bind(this))
        }
      })
      .catch(err => console.error('Modal fetch error:', err))
  }

  handleFormSubmit(event) {
    event.preventDefault()

    const form = event.target
    const url = form.action
    const formData = new FormData(form)

    fetch(url, {
      method: form.method,
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(async response => {
        const contentType = response.headers.get('Content-Type')
        if (contentType?.includes('application/json')) {
          const data = await response.json()
          if (data.success && data.redirectUrl) {
            window.location.href = data.redirectUrl
          } else {
            console.error('Unexpected JSON response', data)
          }
        } else {
          const html = await response.text()
          this.dialogTarget.innerHTML = html

          const newForm = this.dialogTarget.querySelector('form')
          if (newForm) {
            newForm.addEventListener('submit', this.handleFormSubmit.bind(this))
          }
        }
      })
      .catch(err => console.error('Form submit error:', err))
  }

  close() {
    if (this.hasDialogTarget && this.dialogTarget.open) {
      this.dialogTarget.close()
      this.dialogTarget.classList.remove(this.defaultSizeValue)
      this.dialogTarget.innerHTML = ''
    }
    document.body.classList.remove('overflow-hidden');
  }
}
