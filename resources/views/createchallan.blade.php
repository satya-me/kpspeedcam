@extends('layouts.master')
@section('css')
    <style>
        .image-gallery {

            display: table;
        }


        .thumbnails {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .thumbnails {
            width: 124%;
        }

        .thumbnails img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .primary {
            width: 400px;
            height: 250px;


        }

        .primary img {
            width: 124%;
            height: 103%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .thumbnail:hover .thumbnail-image,
        .selected .thumbnail-image {
            border: 2px solid rgb(121, 162, 250);
        }

        .thumbnail-image {
            width: 120px;
            height: 60px;
            margin: 10px 5px;
            border: 4px solid transparent;
        }
    </style>
    <style>
        /* Styles for the modal overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Styles for the alert box */
        .alert-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Close button for the alert */
        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: large;
            color: brown;
        }

        .challan-msg {
            margin-top: 1rem;
            margin-bottom: 1rem;
            margin-left: 1rem;
            margin-right: 1rem;
        }
    </style>
@endsection
@section('content')
    <!--  Header End -->
    <div class="container-fluid">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title fw-semibold mb-4">Image of
                                Vehicle:{{ $AnalyserLatest ? $AnalyserLatest->license_number : '' }}
                            </h5>
                            <p>Image of
                                Ticket:{{ $AnalyserLatest ? $AnalyserLatest->ticket_number : '' }}
                            </p>
                            <div class="image-gallery">
                                @php
                                    $id = $AnalyserLatest ? $AnalyserLatest->id : '';
                                    $media = App\Models\AnalyserMedia::where('analyser_id', $id)->get();
                                    $location = App\Models\Location::where('location_id', $AnalyserLatest->location_id)->first();

                                @endphp
                                @if ($id)
                                    <main class="primary">
                                        <img src="{{ env('APP_URL') . '/storage/app/unzipMedia/' . $AnalyserLatest->ticket . '/' . $media[0]->filepath_full }}"
                                            alt="">
                                    </main>
                                    <aside class="thumbnails">
                                        @foreach ($media as $value)
                                            {{-- env('APP_URL') . '/storage/app/unzipMedia/' . $AnalyserLatest->ticket . '/' . $value->filepath_full --}}
                                            <a href="#" class="selected thumbnail"
                                                data-big="{{ env('APP_URL') . '/storage/app/unzipMedia/' . $AnalyserLatest->ticket . '/' . $value->filepath_full }}">
                                                <div class="thumbnail-image">
                                                    <img src="{{ env('APP_URL') . '/storage/app/unzipMedia/' . $AnalyserLatest->ticket . '/' . $value->filepath_full }}"
                                                        alt="">
                                                </div>
                                            </a>
                                        @endforeach
                                    </aside>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            {{-- {{ env('APP_URL') . '/storage/app/unzipMedia/' . $AnalyserLatest->ticket . '/beam/' . $count++ . '_' . $value->filepath_full }} --}}
                            <h5 class="card-title fw-semibold mb-4 ">Vehicle Details</h5>
                            <div class="card " style="border: 1px solid rgba(0, 0, 0, 0.2);">
                                @if ($id)
                                    <div class="card-body">
                                        <div class="details_area">
                                            <h6 class="card-title" style="font-size: 14px;">Vehicle No : </h6>
                                            <p style="font-size: 16px;"><b>{{ $AnalyserLatest->license_number }}</b></p>
                                        </div>
                                        <hr>
                                        <div class="details_area">
                                            <h6 class="card-title" style="font-size: 14px;">Date : </h6>
                                            <p style="font-size: 13px;"><b>{{ $AnalyserLatest->capture_at }}</b></p>
                                        </div>
                                        <div class="details_area">
                                            <h6 class="card-title" style="font-size: 14px;">Location : </h6>
                                            <p style="font-size: 13px;"><b>{{ $location->location_name }}</b></p>
                                        </div>
                                        <div class="details_area">
                                            <h6 class="card-title" style="font-size: 14px;">Speed Limit : </h6>
                                            <p style="font-size: 13px;">
                                                <b>{{ round($AnalyserLatest->speedLimit_kph) }}Km/h</b>
                                            </p>
                                        </div>
                                        <div class="details_area">
                                            <h6 class="card-title" style="font-size: 14px;">Speed Triggered: </h6>
                                            <p style="font-size: 13px;">
                                                <b>{{ round($AnalyserLatest->calculatedSpeed_kph) }}Km/h</b>
                                            </p>
                                        </div>
                                        <div class="details_area">
                                            <h6 class="card-title" style="font-size: 14px;">Type: </h6>
                                            <p style="font-size: 13px;"><b>{{ $AnalyserLatest->type }}</b></p>
                                        </div>

                                        <div class="mt-4">
                                            <a href="#" class="btn btn-primary" id="generateChallanButton">Submit</a>
                                            <a href="#" class="btn btn-outline-dark-light" id="refresh">Next</a>
                                        </div>
                                    </div>
                                @endif

                            </div>

                        </div>
                        <div class="col-md-3">
                            <h5 class="card-title fw-semibold mb-4">Ticket</h5>
                            <div class="card " style="border: 1px solid rgba(0, 0, 0, 0.2);">
                                <div class="card-body">
                                    @if ($id)
                                        @foreach ($LatestThree as $item)
                                            <a href="">
                                                <div class="ticket_item">
                                                    <h6 class="card-title"><span><i class="ti ti-ticket"></i></span>
                                                        {{ $item->ticket_number }}
                                                    </h6>
                                                    <p>{{ $item->capture_at }}</p>
                                                </div>
                                            </a>
                                            <hr>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- The custom alert overlay -->
    <div class="overlay" id="customAlert">
        <div class="alert-box">
            <span class="close-button" onclick="closeAlert()">&times;</span>
            <p class="challan-msg">Challan has been generated successfully</p>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $('.thumbnail').on('click', function() {
            var clicked = $(this);
            var newSelection = clicked.data('big');
            var $img = $('<img src="' + newSelection + '" alt="">');

            // Update the main image
            $('.primary img').remove(); // Remove the existing image if it exists
            $('.primary').append($img);

            // Update the selected class
            $('.thumbnail.selected').removeClass('selected');
            clicked.addClass('selected');
        });
    </script>
    <script>
        $("#refresh").click(function() {
            window.location.reload(true);
        });
    </script>
    <script>
        function showAlert() {
            document.getElementById("customAlert").style.display = "block";
        }

        function closeAlert() {
            document.getElementById("customAlert").style.display = "none";
        }

        // Event listener for the button
        document.getElementById("generateChallanButton").addEventListener("click", showAlert);
    </script>
@endsection
