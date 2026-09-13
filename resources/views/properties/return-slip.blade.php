<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Return Slip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header img {
            width: 100%;
            display: block;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-size: 9px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        td {
            background-color: #ffffff;
        }

        /* Optional styling for better readability */
        th, td {
            border-color: #666; /* Border color */
        }

    </style>
</head>
<body>
    <header>
        <img src="{{ asset('logo.png') }}" alt="GMNHS logo">
    </header>
    <table>
        <thead>
            <tr>
                <th>ITEM NO.</th>
                <th>QTY.</th>
                <th>UNIT</th>
                <th>NAME</th>
                <th>DESCRIPTION</th>
                <th>UNIT VALUE</th>
                <th>TOTAL VALUE</th>
                <th>PROPERTY NUMBER</th>
                <th>DATE ACQUIRED</th>
                <th>FUND CODE</th>
                <th>END USER</th>
            </tr>
        </thead>
        <tbody>
            <!-- Rows of data would go here -->
            <!-- Example row: -->
            <tr>
                <td>1</td>
                <td>10</td>
                <td>pcs</td>
                <td>Sample Item</td>
                <td>Item Description</td>
                <td>$100.00</td>
                <td>$1000.00</td>
                <td>123456</td>
                <td>2024-11-13</td>
                <td>FC001</td>
                <td>End User Name</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
