const editarmodal = $('#editar');

editarmodal.on('show.bs.modal', function (e) {
        console.log(e.relatedTarget);
        fetch(e.relatedTarget.dataset.url)
        .then( response => response.text())
        .then( body => document.getElementById('editar-content').innerHTML=body );
    }
);
