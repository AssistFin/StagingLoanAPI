@extends('admin.layouts.app')

@section('panel')

    <div class="row">
        <div class="col-lg-12">
            <div class="card  b-radius--10">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-end mb-3">
                        <a href="{{ route('admin.underwriting.create') }}" class="btn btn-success mb-3">Set U/W Configuration</a>
                    </div>
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>Sr No</th>
                                    <th>Name</th>
                                    <th>Added On</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($uwclogs as $uwclog)
                                    <tr>
                                        <td>{{ $uwclog->id }}</td>
                                        <td>{{ $uwclog->admin->name ?? 'Unknown' }}</td>
                                        <td>{{ $uwclog->created_at->format('d-m-Y H:i') }}</td>
                                        <td>
                                            <a class="btn btn-primary btn-sm">View Remark</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection