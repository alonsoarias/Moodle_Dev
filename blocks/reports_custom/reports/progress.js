$(document).ready(function() {
    // Función para actualizar el reporte dinámicamente
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

    // Función para actualizar filtros activos para la navegación alfabética
    function updateActiveFilters() {
        $('.alphabet-filter a').removeClass('active');
        $('.alphabet-filter').each(function() {
            var filter = $(this).data('filter');
            var value = $('input[name="' + filter + '"]').val();
            $(this).find('a[data-letter="' + value + '"]').addClass('active');
        });
    }
    // Manejador para cambios en categoría que actualiza los cursos dinámicamente
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
    // Manejadores de eventos para filtros incluyendo cursos, tipo de usuario y campos de fecha
    $('#course, #usertype').change(function() {
        updateReport();
    });

    // Manejo específico de cambios en fechas
    $('#startdate, #enddate').change(function() {
        updateReport();
    });

    // Manejo de cambios en el campo de número de identificación dinámicamente
    $('#idnumber').on('input', function() {
        updateReport();
    });

    // Manejo de filtro alfabético
    $(document).on('click', '.alphabet-filter a', function(e) {
        e.preventDefault();
        var letter = $(this).data('letter');
        var filter = $(this).closest('.alphabet-filter').data('filter');
        $('input[name="' + filter + '"]').val(letter);
        updateReport();
    });
    // Inicializar paginación dinámicamente
    function initializePagination() {
        $('.paging_bar a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            $('input[name="page"]').val(page);
            updateReport();
        });
    }

    // Inicializar funciones al cargar la página
    initializePagination();
    updateActiveFilters();

    // Agregar allowed_categories a todos los envíos de formularios
    $('form').submit(function() {
        var allowedCategories = $('input[name="allowed_categories"]').val();
        if (allowedCategories) {
            $(this).append('<input type="hidden" name="allowed_categories" value="' + allowedCategories + '">');
        }
    });
});