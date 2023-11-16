@extends('layouts.master')
@section('css')
    <style>
        .vc_img img {
            width: 50px;
            height: 50px;
        }

        .pagination {
            margin: 0;
            /* Remove default margin */
        }

        .edit-highlight {
            color: rgb(225, 65, 110);
        }
    </style>
@endsection
@section('content')
    <!--  Main wrapper -->

    <!--  Header End -->
    <div class="container-fluid">
        <!--  Row 1 -->
        <div class="row">
            <div class="col-lg-12 ">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('update.key') }}">
                            @csrf
                            <div class="form-group">
                                <label for="plate_reader_key">New PLATE_READER_KEY:</label>
                                <input type="text" class="form-control" name="plate_reader_key" id="plate_reader_key"
                                    value="{{ env('PLATE_READER_KEY') }}" required>
                            </div>
                            <button type="submit" class="btn btn-dark-light">Update Key</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 ">
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <h4 for="">Location:</h4>
                            <thead>
                                <tr>
                                    <th scope="col">SL No</th>
                                    <th scope="col">Location ID</th>
                                    <th scope="col">Location Name</th>
                                    <th scope="col">MAC ID</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $count = 0;
                                @endphp
                                @foreach ($location as $item)
                                    <tr>
                                        <td>{{ ++$count }}</td>
                                        <td>{{ $item->location_id }}</td>
                                        <td>{{ $item->location_name }}</td>
                                        <td>{{ $item->mac_id }}</td>
                                        <td><button class="btn btn-danger edit-btn">Edit</button></td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>

        <div class="py-6 px-6 text-center">
            <p class="mb-0 fs-4">Design and Developed by <a href="https://kotaielectronics.com/" target="_blank"
                    class="pe-1 text-primary text-decoration-underline">Kotai Electronics</a> </p>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('.edit-btn').on('click', function() {
                // Get the current row
                var row = $(this).closest('tr');

                // Disable editing for all other rows
                $('tr').not(row).find('td:not(:last-child)').prop('contenteditable', false);

                // Enable editing for the clicked row for "Location Name" and "MAC ID" columns
                var editableFields = row.find('td:nth-child(3), td:nth-child(4)');
                editableFields.prop('contenteditable', true);

                // Highlight the edit area by changing the background color
                editableFields.addClass('edit-highlight');

                // Change the button text to "Save"
                $(this).text('Save');

                // Change the button class to identify it as a save button
                $(this).removeClass('edit-btn').addClass('save-btn');

                // Attach the save button click event
                $(this).off('click').on('click', function() {
                    saveData(row, this);
                });
            });
        });

        function saveData(row, button) {
            // Save the changes to the database using AJAX
            var locationId = row.find('td:nth-child(2)').text();
            var locationName = row.find('td:nth-child(3)').text();
            var macId = row.find('td:nth-child(4)').text();
            console.log({
                locationId,
                locationName,
                macId
            });

            // Get CSRF token
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                type: 'POST',
                url: "{{ route('update-location') }}", // Replace with your Laravel route
                data: {
                    location_id: locationId,
                    location_name: locationName,
                    mac_id: macId,
                    // Add other data as needed
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    // Handle success
                    console.log('Data saved successfully');
                },
                error: function(error) {
                    // Handle error
                    console.error('Error saving data: ' + error.responseText);
                },
            });

            // Disable editing for all rows after saving
            $('td').prop('contenteditable', false);

            // Remove the edit-highlight class
            row.find('td:nth-child(3), td:nth-child(4)').removeClass('edit-highlight');

            // Change the button text back to "Edit"
            $(button).text('Edit');

            // Change the button class back to identify it as an edit button
            $(button).removeClass('save-btn').addClass('edit-btn');

            // Re-attach the edit button click event
            $(button).off('click').on('click', function() {
                enableEditing(row, button);
            });
        }

        function enableEditing(row, button) {
            $('.edit-btn').on('click', function() {
                // Get the current row
                var row = $(this).closest('tr');

                // Disable editing for all other rows
                $('tr').not(row).find('td:not(:last-child)').prop('contenteditable', false);

                // Enable editing for the clicked row for "Location Name" and "MAC ID" columns
                var editableFields = row.find('td:nth-child(3), td:nth-child(4)');
                editableFields.prop('contenteditable', true);

                // Highlight the edit area by changing the background color
                editableFields.addClass('edit-highlight');

                // Change the button text to "Save"
                $(this).text('Save');

                // Change the button class to identify it as a save button
                $(this).removeClass('edit-btn').addClass('save-btn');

                // Attach the save button click event
                $(this).off('click').on('click', function() {
                    saveData(row, this);
                });
            });
        }
    </script>
@endsection
