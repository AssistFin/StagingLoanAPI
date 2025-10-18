@extends('admin.layouts.app')

@section('panel')
<div class="container">
  <h4 class="mb-4">Underwriting Configuration</h4>
  <form id="uwForm" method="POST" action="{{ route('admin.underwriting.store') }}">
    @csrf
    <input type="hidden" name="client_id" value="{{ auth()->guard('admin')->user()->id }}">

    <!-- BANKING SECTION -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">Banking Details</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label>Average Salary</label>
            <input type="text" name="average_salary" class="form-control" value="{{ $existingData ? $existingData->avgSalary : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Min Balance</label>
            <input type="text" name="min_balance" class="form-control" value="{{ $existingData ? $existingData->minBalance : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Avg Balance</label>
            <input type="text" name="avg_balance" class="form-control" value="{{ $existingData ? $existingData->avgBalance : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Bank Score</label>
            <input type="text" name="bank_score" class="form-control" value="{{ $existingData ? $existingData->bankScore : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Bounce in Last 1 Month</label>
            <input type="text" name="bounce_1_month" class="form-control" value="{{ $existingData ? $existingData->bounceLast1Month : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Bounce in Last 3 Month</label>
            <input type="text" name="bounce_3_month" class="form-control" value="{{ $existingData ? $existingData->bounceLast3Month : '' }}" required>
          </div>
        </div>
      </div>
    </div>

    <!-- BUREAU SECTION -->
    <div class="card mb-4">
      <div class="card-header bg-success text-white">Bureau Details</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label>Bureau Score</label>
            <input type="text" name="bureau_score" class="form-control" value="{{ $existingData ? $existingData->bureauScore : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>DPD in Last 30 Days</label>
            <input type="text" name="dpd_30" class="form-control" value="{{ $existingData ? $existingData->dpdLast30Days : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>DPD 30 Amt</label>
            <input type="text" name="dpd_30_amt" class="form-control" value="{{ $existingData ? $existingData->dpdamtLast30Days : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>DPD in Last 90 Days</label>
            <input type="text" name="dpd_90" class="form-control" value="{{ $existingData ? $existingData->dpdLast90Days : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>DPD 90 Amt</label>
            <input type="text" name="dpd_90_amt" class="form-control" value="{{ $existingData ? $existingData->dpdamtLast90Days : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Experience in Unsecured Loan</label>
            <select name="experience_unsecured" class="form-control" value="{{ $existingData ? $existingData->avgSalary : '' }}" required>
              <option value="Yes" {{ $existingData->expUnsecureLoan == 'Yes' ? 'selected' : '' }} >Yes</option>
              <option value="No" {{ $existingData->expUnsecureLoan == 'No' ? 'selected' : '' }} >No</option>
            </select>
          </div>
          <div class="col-md-4">
            <label>Leverage (Times)</label>
            <input type="text" name="leverage" class="form-control" value="{{ $existingData ? $existingData->leverage : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Exposure on Salary (In %)</label>
            <input type="text" name="exposure_on_salary" class="form-control" value="{{ $existingData ? $existingData->exposureOnSalary : '' }}" required>
          </div>
          <div class="col-md-4">
            <label>Max Loan Amount</label>
            <input type="text" name="maxLoanAmt" class="form-control" value="{{ $existingData ? $existingData->maxLoanAmt : '' }}" required>
          </div>
          <div class="col-md-12">
            <label>Remark</label>
            <textarea name="remark" class="form-control" required></textarea>
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
