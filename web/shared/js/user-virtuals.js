jQuery(document).ready(function() {
    var $virtualsTable = $('#virtuals-container');
    var $collectionHolder = $virtualsTable.find('tbody');
    var $addLink = $('#add-virtual');

    $collectionHolder.data('index', $collectionHolder.find('tr').length);

    $addLink.on('click', function(e) {
        e.preventDefault();

        var index = $collectionHolder.data('index');
        var prototype = $virtualsTable.data('prototype');
        var newForm = prototype.replace(/__name__/g, index);

        $collectionHolder.data('index', index + 1);

        var $newRow = $(newForm);
        $collectionHolder.append($newRow);
    });

    $collectionHolder.on('click', '.delete-virtual', function(e) {
        e.preventDefault();

        $(this).parents('tr').remove();
    });
});
