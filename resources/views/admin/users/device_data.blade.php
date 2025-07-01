@extends('admin.layouts.app')

@section('panel')
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>

                                <th>Device Info</th>
                                <th>Contacts</th>
                                <th>Location</th>
                                <th>Address</th>
                            </tr>
                            </thead>
                            <tbody>

                                    <tr>

                                        <td>
                                            <button class="btn btn-sm btn-outline--primary" type="button" data-toggle="collapse" data-target="#deviceInfo{{$user->id}}" aria-expanded="false" aria-controls="deviceInfo{{$user->id}}">
                                                View
                                            </button>
                                            <div class="collapse" id="deviceInfo{{$user->id}}">
                                                <div class="card card-body">
                                                @php
                                                   $deviceInfo = json_decode($user->device_info, true);
                                               @endphp
                                             @if($deviceInfo)
                                                    <ul class="list-unstyled">
                                                    <li>Model: {{ $deviceInfo['model'] ?? 'N/A' }}</li>
                                                    <li>Android OS Version: {{ $deviceInfo['androidVersion'] ?? $deviceInfo['iOSVersion'] ?? 'N/A' }}</li>
                                                    <li>Brand: {{ $deviceInfo['brand'] ?? 'N/A' }}</li>
                                                    <li>OS: {{ $deviceInfo['device'] ?? $deviceInfo['systemName'] ?? 'N/A' }}</li>
                                                    <li>Manufacturer: {{ $deviceInfo['manufacturer'] ?? 'N/A' }}</li>
                                                    <li>Product: {{ $deviceInfo['product'] ?? 'N/A' }}</li>
                                                    <li>Hardware: {{ $deviceInfo['hardware'] ?? 'N/A' }}</li>
                                                    <li>Device Name: {{ $deviceInfo['device_name'] ?? $deviceInfo['name'] ?? 'N/A' }}</li>
                                                    <li>Localized Model: {{ $deviceInfo['localizedModel'] ?? 'N/A' }}</li>
                                                    <li>Identifier for Vendor: {{ $deviceInfo['identifierForVendor'] ?? 'N/A' }}</li>
                                                    <li>Physical Device: {{ $deviceInfo['isPhysicalDevice'] ?? 'N/A' }}</li>

                                                    </ul>
                                            @else
                                            <p>No device information available.</p>
                                               @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <!-- <button class="btn btn-sm btn-outline--primary"  type="button" data-toggle="collapse" data-target="#contacts{{$user->id}}" aria-expanded="false" aria-controls="contacts{{$user->id}}">
                                                View
                                            </button> -->
                                            <a href="{{ route('admin.users.contact.details', $user->id) }}" class="btn btn-sm btn-outline--primary">
                                                View
                                            </a>


                                        </td>
                                        <td>{{ $user->latitude }}, {{ $user->longitude }} <br/> <a href="https://www.google.com/maps/search/?api=1&query={{ $user->latitude }},{{ $user->longitude }}" target="_blank" class="btn btn-sm btn-outline--primary">
                                            Open in Maps
                                        </a></td>

                                        <td>{{ $user->trackaddress }}</td>
                                    </tr>

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                <!-- Pagination or any other footer content if needed -->
            </div><!-- card end -->
        </div>
    </div>
@endsection
