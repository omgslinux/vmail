var aliasready = (callback) => {
    if (document.readyState != "loading") callback();
    else document.addEventListener("DOMContentLoaded", callback);
}

aliasready(() => {
  /* Do things after DOM has fully loaded */
    const editaliasesModal = document.querySelector('#editaliasesModal');
    // const editaliasesModal = document.querySelector('form');
    editaliasesModal.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'add-aliasname') {
            e.preventDefault();

            const aliasesTable = editaliasesModal.querySelector('#aliasnames-container');
            const collectionHolder = aliasesTable.querySelector('tbody');
            const collectionTableRow = collectionHolder.querySelector('tr');
            if (collectionTableRow==null) {
                collectionHolder.dataset.index = 0;
            } else {
                collectionHolder.dataset.index = collectionTableRow.length;
            }
            const index = parseInt(collectionHolder.dataset.index);
            //console.log(aliasesTable);
            const prototype = aliasesTable.dataset.prototype;
            const newForm = prototype.replace(/__name__/g, index);
            collectionHolder.dataset.index = index + 1;
            const newRow = document.createElement('tr');
            newRow.innerHTML = newForm;
            collectionHolder.appendChild(newRow);
        }
        if (e.target && e.target.classList.contains('delete-aliasname')) {
            e.preventDefault();
            e.target.parentNode.parentNode.remove();
        }
    });
});


/*
jQuery(document).ready(function() {
    var $aliasesModal = $('#editaliasesModal');


    $aliasesModal.on('click', '#add-aliasname', function(e) {
        e.preventDefault();

        var $aliasesTable = $('#aliasnames-container');
        var $collectionHolder = $aliasesTable.find('tbody');
        $collectionHolder.data('index', $collectionHolder.find('tr').length);
        var index = $collectionHolder.data('index');
        console.log($aliasesTable);
        var prototype = $aliasesTable.data('prototype');
        var newForm = prototype.replace(/__name__/g, index);

        $collectionHolder.data('index', index + 1);

        var $newRow = $(newForm);
        $collectionHolder.append($newRow);

        $collectionHolder.on('click', '.delete-aliasname', function(e) {
            e.preventDefault();

            $(this).parents('tr').remove();
        });
    });

});
*/
