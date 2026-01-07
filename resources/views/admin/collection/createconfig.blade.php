@extends('admin.layouts.app')

@section('panel')
<div class="container">
  <h4 class="mb-4">Collection Configuration</h4>
  <form id="uwForm" method="POST" action="{{ route('admin.collection.ccstore') }}">
    @csrf
    <input type="hidden" name="client_id" value="{{ auth()->guard('admin')->user()->id }}">

    <!-- BANKING SECTION -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">Amount Offer Details</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label>Principal Off (%)</label>
            <input type="text" name="pr_off" class="form-control" value="" required>
          </div>
          <div class="col-md-4">
            <label>Interest Off (%)</label>
            <input type="text" name="in_off" class="form-control" value="" required>
          </div>
          <div class="col-md-4">
            <label>Penal Off (%)</label>
            <input type="text" name="pe_off" class="form-control" value="" required>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center">
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
  </form>
</div>
@endsection
