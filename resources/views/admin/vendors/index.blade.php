@extends('admin.layouts.app')

@section('panel')
<div class="container">
    <h3>Vendor / API Settings</h3>
    <table class="table table-bordered text-center">
        <thead>
            <tr>
                <th>Vendor / API</th>
                <th>KYC</th>
                <th>Credit Bureau</th>
                <th>BSA Report</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendors as $vendor)
                <tr>
                    <td>{{ $vendor->vendor }}</td>
                    <td>
                        <form action="{{ route('admin.vendors.toggle', [$vendor->id, 'kyc']) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-{{ $vendor->kyc == 1 ? 'success' : ($vendor->kyc == 0 ? 'secondary' : 'danger') }}" @if($vendor->kyc == 2) disabled @endif>
                            {{ $vendor->kyc == 1 ? 'ON' : ($vendor->kyc == 0 ? 'OFF' : 'X') }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <form action="{{ route('admin.vendors.toggle', [$vendor->id, 'credit_bureau']) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-{{ $vendor->credit_bureau == 1 ? 'success' : ($vendor->credit_bureau == 0 ? 'secondary' : 'danger') }}" @if($vendor->credit_bureau == 2) disabled @endif>
                            {{ $vendor->credit_bureau == 1 ? 'ON' : ($vendor->credit_bureau == 0 ? 'OFF' : 'X') }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <form action="{{ route('admin.vendors.toggle', [$vendor->id, 'bsa_report']) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-{{ $vendor->bsa_report == 1 ? 'success' : ($vendor->bsa_report == 0 ? 'secondary' : 'danger') }}" @if($vendor->bsa_report == 2) disabled @endif>
                            {{ $vendor->bsa_report == 1 ? 'ON' : ($vendor->bsa_report == 0 ? 'OFF' : 'X') }}
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

