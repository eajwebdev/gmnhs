<div class="printarea">
    <header class="bdHead">
        <div class="row">
            <div class="logo-left" style="padding-left: 20px">
                <img src="{{ asset('/logo.png') }}" alt="Left Logo" >
            </div>

            <div class="logo-center col-md-12">
                <p class="text1">Republic of the Philippines</p>
                <p class="text2">GIL MONTILLA NATIONAL HIGH SCHOOL</p>
                <p class="text3">Sipalay City, Negros Occidental</p>
            </div>
        </div>
    </header>
    <table id="tableprnt" class="table table-bordered table-hover">
        <thead>
            <tr>
                <th class="col-md-4 bdleft">Property No.</th> <th class="col-md-6 bdright">{{ $inventory->property_no_generated  }}</th>
            </tr>
            <tr>
                <th class="bdleft">Description</th> <th class="bdright">{{ $inventory->item_descrip  }}</th>
            </tr>
            <tr>
                <th class="bdleft">Serial No.</th> <th class="bdright">{{ $inventory->serial_number  }}</th>
            </tr>
            <tr>
                <th class="bdleft">Unit Price</th> <th class="bdright">{{ $inventory->item_cost  }}</th>
            </tr>
            <tr>
                <th class="bdleft">Date Acquired</th> <th class="bdright">{{ $inventory->date_acquired  }}</th>
            </tr>
            <tr>
                <th class="bdleft">Department/Office</th> <th class="bdright">{{ $inventory->office_name  }}</th>
            </tr>
            <tr>
                <th class="bdleft">Person Accountable</th> <th class="bdright">{{ $inventory->person_accnt  }}</th>
            </tr>
        </thead>
    </table>
    <footer>
        <div>
            <div class="qrcode-container">
                <canvas id="qrcode"></canvas>
            </div>
            <div class="prnt-incharge">{{auth()->user()->fname}} {{auth()->user()->lname}}</div>
            <div class="prnt-role">Printed by</div>
            <div class="officer">{{ $user->fname  }} {{ $user->lname  }}</div>
            <div class="officerRole">{{ display_role($user->role) }}</div>
            <div class="stickerlabel">--- Do not remove ---</div>
        </div>
    </footer>
</div>

<script>
    var inputText= "{{ $inventory->property_no_generated  }}";
    var qr = new QRious({
        element: document.getElementById("qrcode"),
        value: inputText,
        size: 90,
    });
</script>
