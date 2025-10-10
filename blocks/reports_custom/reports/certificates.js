$(document).ready(function() {
    // Function to update the report dynamically
    function updateReport() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: $('#filtersForm').serialize(),
            success: function(data) {
                $('#reportData').html($(data).find('#reportData').html());
                initializePagination();
            },
            error: function() {
                alert("Error updating report.");
            }
        });
    }

    // Function to update active filters for the alphabetical navigation
    function updateActiveFilters() {
        $('.alphabet-filter a').removeClass('active');
        $('.alphabet-filter').each(function() {
            var filter = $(this).data('filter');
            var value = $('input[name="' + filter + '"]').val();
            $(this).find('a[data-letter="' + value + '"]').addClass('active');
        });
    }

    // Handler for changes in category which updates courses dynamically
    $('#category').change(function() {
        var categoryId = $(this).val();
        var allowedCategories = $('input[name="allowed_categories"]').val();
        $.ajax({
            url: 'get_courses.php',
            type: 'GET',
            data: { 
                category: categoryId,
                allowed_categories: allowedCategories
            },
            success: function(data) {
                var courses = JSON.parse(data);
                $('#course').empty().append('<option value="">All</option>');
                $.each(courses, function(index, course) {
                    $('#course').append('<option value="' + course.id + '">' + course.fullname + '</option>');
                });
                updateReport();
            },
            error: function() {
                alert("Error loading courses.");
            }
        });
    });

    // Event handlers for filters including courses, user type, and date fields
    $('#course, #usertype').change(function() {
        updateReport();
    });

    // Specifically handling date changes
    $('#startdate, #enddate').change(function() {
        updateReport();
    });

    // Handling changes in the ID number input dynamically
    $('#idnumber').on('input', function() {
        updateReport();
    });

    // Alphabetical filter handling
    $(document).on('click', '.alphabet-filter a', function(e) {
        e.preventDefault();
        var letter = $(this).data('letter');
        var filter = $(this).closest('.alphabet-filter').data('filter');
        $('input[name="' + filter + '"]').val(letter);
        updateReport();
    });

    // Initialize pagination dynamically
    function initializePagination() {
        $('.paging_bar a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            $('input[name="page"]').val(page);
            updateReport();
        });
    }

    // Initialize functions on page load
    initializePagination();
    updateActiveFilters();

    // Add allowed_categories to all form submissions
    $('form').submit(function() {
        var allowedCategories = $('input[name="allowed_categories"]').val();
        if (allowedCategories) {
            $(this).append('<input type="hidden" name="allowed_categories" value="' + allowedCategories + '">');
        }
    });
});