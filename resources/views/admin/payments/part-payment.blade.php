<!DOCTYPE html>
<html>
<head>
    <title>Part Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body{
            background:#f5f7fb;
            font-family: Arial, Helvetica, sans-serif;
            padding:25px;
        }

        .container{
            max-width:480px;
            margin:0 auto;
        }

        .card{
            background:#ffffff;
            border-radius:16px;
            box-shadow:0 8px 24px rgba(0,0,0,0.08);
            padding:24px 26px;
        }

        h2{
            margin:0 0 6px 0;
        }

        .meta{
            color:#555;
            font-size:14px;
            margin-bottom:16px;
        }

        .amount-box{
            background:#f0f3ff;
            border-radius:10px;
            padding:10px 14px;
            margin-bottom:14px;
        }

        .options label{
            display:flex;
            gap:10px;
            align-items:center;
            border:1px solid #ddd;
            padding:12px 12px;
            border-radius:10px;
            cursor:pointer;
            margin-bottom:10px;
        }

        .options input[type="radio"]{
            transform:scale(1.2);
        }

        .input-group{
            margin-top:6px;
            display:flex;
            flex-direction:column;
        }

        .input-group input{
            padding:10px;
            border-radius:8px;
            border:1px solid #ccc;
            font-size:15px;
        }

        button{
            width:100%;
            padding:12px;
            margin-top:14px;
            border-radius:10px;
            border:none;
            background:#2b63ff;
            color:white;
            font-weight:600;
            font-size:15px;
            cursor:pointer;
        }

        button:disabled{
            opacity:.5;
            cursor:not-allowed;
        }

        small{
            color:#888;
        }

        .error{
            color:#d22;
            margin-top:4px;
            font-size:12px;
        }
    </style>
</head>

<body>
<div class="container">
    <div class="card">

        <h2>Loan Payment</h2>
        <div class="meta">Loan Application ID : {{ $lead->id }}</div>

        <div class="amount-box">
            <b>Outstanding Amount:</b> ₹{{ number_format($lead->total_dues ?? 0, 0) }}
        </div>

        <form method="POST" action="{{ route('partpay.initiate') }}" id="paymentForm">
            @csrf

            <input type="hidden" name="lead_id" value="{{ $lead->id }}">
            <input type="hidden" name="amount" id="dueAmount" value="{{ $lead->total_dues ?? 0 }}">

            <div class="options">

                <label>
                    <input type="radio" name="pay_mode" value="full" checked>
                    Full Payment (₹{{ number_format($lead->total_dues ?? 0, 0) }})
                </label>

                <label>
                    <input type="radio" name="pay_mode" value="half">
                    Half Payment (₹{{ number_format(($lead->total_dues ?? 0) / 2, 0) }})
                </label>

                <label>
                    <input type="radio" name="pay_mode" value="part">
                    Part Payment
                </label>

                <div class="input-group">
                    <input type="number"
                           name="amount"
                           id="amountInput"
                           placeholder="Enter part payment amount"
                           readonly>
                    <small>Minimum amount ₹ 1000</small>
                    <div class="error" id="amountError"></div>
                </div>

            </div>

            <button type="submit" id="payBtn" disabled>Proceed to Pay</button>

        </form>
    </div>
</div>

<script>
    const due = parseFloat(document.getElementById("dueAmount").value || 0);
    const radios = document.querySelectorAll("input[name='pay_mode']");
    const input = document.getElementById("amountInput");
    const error = document.getElementById("amountError");
    const button = document.getElementById("payBtn");

    function updateState(){
        const mode = document.querySelector("input[name='pay_mode']:checked").value;

        error.textContent = "";

        if(mode === "full"){
            input.value = due;
            input.readOnly = true;
            button.disabled = due <= 0;
        }
        else if(mode === "half"){
            input.value = Math.ceil(due/2);
            input.readOnly = true;
            button.disabled = due <= 0;
        }
        else{
            input.readOnly = false;
            input.value = "";
            button.disabled = true;
        }
    }

    radios.forEach(r => r.addEventListener("change", updateState));
    updateState();

    input.addEventListener("input", () => {
        let v = parseFloat(input.value || 0);

        if(v < 1000){
            error.textContent = "Minimum part payment amount is ₹1000";
            button.disabled = true;
        }
        else if(v > due){
            error.textContent = "Amount cannot exceed outstanding amount";
            button.disabled = true;
        }
        else{
            error.textContent = "";
            button.disabled = false;
        }
    });
</script>

</body>
</html>
