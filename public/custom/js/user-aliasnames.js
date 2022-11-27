jQuery(document).ready(function() {
    var $aliasesModal = $('#editaliasesModal');
    //var $addLink = $('#add-aliasname');


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
