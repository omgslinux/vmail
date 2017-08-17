jQuery(document).ready(function() {
    var $aliasesTable = $('#addressnames-container');
    var $collectionHolder = $aliasesTable.find('tbody');
    var $addLink = $('#add-addressname');

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

    $collectionHolder.on('click', '.delete-addressname', function(e) {
        e.preventDefault();

        $(this).parents('tr').remove();
    });
});
