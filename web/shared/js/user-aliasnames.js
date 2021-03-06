jQuery(document).ready(function() {
    var $aliasesTable = $('#aliasnames-container');
    var $collectionHolder = $aliasesTable.find('tbody');
    var $addLink = $('#add-aliasname');

    $collectionHolder.data('index', $collectionHolder.find('tr').length);

    $addLink.on('click', function(e) {
        e.preventDefault();

        var index = $collectionHolder.data('index');
        var prototype = $aliasesTable.data('prototype');
        var newForm = prototype.replace(/__name__/g, index);

        $collectionHolder.data('index', index + 1);

        var $newRow = $(newForm);
        $collectionHolder.append($newRow);
    });

    $collectionHolder.on('click', '.delete-aliasname', function(e) {
        e.preventDefault();

        $(this).parents('tr').remove();
    });
});
