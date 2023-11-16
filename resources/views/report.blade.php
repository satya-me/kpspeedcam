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
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">SL No</th>
                                    <th scope="col">Vehicle No</th>
                                    <th scope="col">Time</th>
                                    <th scope="col">Speed Limit</th>
                                    <th scope="col">Speed Violation</th>
                                    {{-- <th scope="col">Image</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $count = 0;
                                @endphp
                                @foreach ($Analyser as $item)
                                    <tr>
                                        <th scope="row">{{ ++$count }}</th>
                                        <td>{{ $item->license_number }}</td>
                                        <td>{{ $item->capture_at }}</td>
                                        <td>{{ round($item->speedLimit_kph) }} Km/h</td>
                                        <td>{{ round($item->calculatedSpeed_kph) }} Km/h</td>
                                        {{-- <td>
                                            <div class="d-flex gap-1">
                                                <div class="vc_img">
                                                    <img src="{{ asset('/') }}/assets/images/img1.jpg" alt="">
                                                </div>
                                                <div class="vc_img">
                                                    <img src="{{ asset('/') }}/assets/images/img1.jpg" alt="">
                                                </div>
                                                <div class="vc_img">
                                                    <img src="{{ asset('/') }}/assets/images/img1.jpg" alt="">
                                                </div>
                                            </div>

                                        </td> --}}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($Analyser->hasPages())
                        <ul class="pagination">
                            {{-- Previous Page Link --}}
                            @if ($Analyser->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">Previous</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $Analyser->previousPageUrl() }}"
                                        rel="prev">Previous</a></li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($Analyser->hasMorePages())
                                <li class="page-item"><a class="page-link" href="{{ $Analyser->nextPageUrl() }}"
                                        rel="next">Next</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link">Next</span></li>
                            @endif
                        </ul>
                    @endif
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
        $('.thumbnail').on('click', function() {
            var clicked = $(this);
            var newSelection = clicked.data('big');
            var $img = $('.primary').css("background-image", "url(" + newSelection + ")");
            clicked.parent().find('.thumbnail').removeClass('selected');
            clicked.addClass('selected');
            $('.primary').empty().append($img.hide().fadeIn('slow'));
        });
    </script>
@endsection
