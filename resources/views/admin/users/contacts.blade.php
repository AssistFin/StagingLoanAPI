@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <!-- Your detailed format for displaying contacts -->
        <div class="card">
            <div class="card-body">

                @if($contacts && is_array($contacts))
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contacts as $contact)
                                <tr>
                                    <td>{{ $contact['name'] ?? 'N/A' }}</td>
                                    <td>{{ $contact['phone'] ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No contacts information available.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
